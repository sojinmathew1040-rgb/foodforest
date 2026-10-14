<?php
// =========================================================================
// Food Forest Sanctuary — Room Photos Bulk Upload & Optimization API
// Handles secure multi-file upload for Villa/Cottage Gallery Showcase
// Supports Portrait and Landscape orientations with auto-compression
// =========================================================================

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');

if (ob_get_level()) {
    @ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/auth.php';
require_admin_auth();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';

ensure_upload_dir_exists();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method. POST required.']);
    exit;
}

$action = $_POST['action'] ?? '';
$gallery_type = trim($_POST['gallery_type'] ?? 'general');
if (!in_array($gallery_type, ['general', 'left', 'right'])) {
    $gallery_type = 'general';
}
$gallery_col = ($gallery_type === 'left') ? 'photos_left' : (($gallery_type === 'right') ? 'photos_right' : 'photos');

// Action 1: Set Primary Villa Cover Photo
if ($action === 'set_cover') {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $cover_url = trim($_POST['cover_url'] ?? '');
    if ($room_id > 0 && !empty($cover_url)) {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("UPDATE rooms SET image_url = ? WHERE id = ?");
            $stmt->execute([$cover_url, $room_id]);

            // Keep Walkthrough 3D tour settings in sync
            $rm_row = $pdo->query("SELECT slug, stay_type FROM rooms WHERE id = " . $room_id)->fetch(PDO::FETCH_ASSOC);
            if ($rm_row) {
                $st = $rm_row['stay_type'] ?? '';
                if ($st === 'treehouse' || $st === 'woodhouse' || stripos($rm_row['slug'], 'tree') !== false) {
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_woodhouse_cover', ?)")->execute([$cover_url]);
                } elseif ($st === 'mudhouse' || stripos($rm_row['slug'], 'mud') !== false) {
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_mudhouse_cover', ?)")->execute([$cover_url]);
                }
            }

            if (function_exists('sanctuary_purge_all_caches')) {
                sanctuary_purge_all_caches();
            }
            echo json_encode(['success' => true, 'message' => 'Primary villa cover photo updated and published successfully!', 'cover_url' => $cover_url]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error setting cover photo: ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Invalid room ID or cover photo URL.']);
    exit;
}

// Action 2: Delete Photo from Villa Gallery (General, Left Suite, or Right Suite)
if ($action === 'delete_photo') {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $target_url = trim($_POST['photo_url'] ?? '');
    if ($room_id > 0 && !empty($target_url)) {
        try {
            $pdo = get_db();
            $r_stmt = $pdo->prepare("SELECT {$gallery_col}, image_url, slug, stay_type FROM rooms WHERE id = ?");
            $r_stmt->execute([$room_id]);
            $r_data = $r_stmt->fetch(PDO::FETCH_ASSOC);
            if ($r_data) {
                $updated_list = [];
                if (!empty($r_data[$gallery_col])) {
                    $dec = json_decode($r_data[$gallery_col], true);
                    if (is_array($dec)) {
                        foreach ($dec as $item) {
                            $u = is_array($item) ? ($item['url'] ?? '') : (string)$item;
                            if ($u !== $target_url && $u !== '') {
                                $updated_list[] = is_array($item) ? $item : [
                                    'url' => $u,
                                    'title' => '',
                                    'description' => '',
                                    'order' => count($updated_list) + 1
                                ];
                            }
                        }
                    }
                }
                
                $current_cover = $r_data['image_url'] ?? '';
                $new_cover = $current_cover;
                $cover_updated = false;
                if ($gallery_col === 'photos' && $current_cover === $target_url) {
                    $new_cover = !empty($updated_list) ? ($updated_list[0]['url'] ?? '') : '';
                    $cover_updated = true;
                    // Keep walkthrough settings in sync
                    $st = $r_data['stay_type'] ?? '';
                    if (!empty($new_cover)) {
                        if ($st === 'treehouse' || $st === 'woodhouse' || stripos($r_data['slug'], 'tree') !== false) {
                            $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_woodhouse_cover', ?)")->execute([$new_cover]);
                        } elseif ($st === 'mudhouse' || stripos($r_data['slug'], 'mud') !== false) {
                            $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_mudhouse_cover', ?)")->execute([$new_cover]);
                        }
                    }
                }

                $new_json = !empty($updated_list) ? json_encode(array_values($updated_list), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
                if ($gallery_col === 'photos') {
                    $pdo->prepare("UPDATE rooms SET photos = ?, image_url = ? WHERE id = ?")->execute([$new_json, $new_cover, $room_id]);
                } else {
                    $pdo->prepare("UPDATE rooms SET {$gallery_col} = ? WHERE id = ?")->execute([$new_json, $room_id]);
                }

                if (function_exists('sanctuary_purge_all_caches')) {
                    sanctuary_purge_all_caches();
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Photo successfully removed from suite gallery.',
                    'gallery_type' => $gallery_type,
                    'cover_updated' => $cover_updated,
                    'new_cover' => $new_cover,
                    'remaining_count' => count($updated_list)
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error deleting photo: ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Invalid room ID or photo URL for deletion.']);
    exit;
}

// Action 3: In-Place Replace Photo
if ($action === 'replace_photo') {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $old_url = trim($_POST['old_url'] ?? '');
    
    $file_entry = null;
    if (!empty($_FILES['replacement_photo']['name'])) {
        $file_entry = $_FILES['replacement_photo'];
    } elseif (!empty($_FILES['room_photos']['name'])) {
        $file_entry = is_array($_FILES['room_photos']['name']) ? [
            'name' => $_FILES['room_photos']['name'][0],
            'type' => $_FILES['room_photos']['type'][0],
            'tmp_name' => $_FILES['room_photos']['tmp_name'][0],
            'error' => $_FILES['room_photos']['error'][0],
            'size' => $_FILES['room_photos']['size'][0]
        ] : $_FILES['room_photos'];
    } elseif (!empty($_FILES['file']['name'])) {
        $file_entry = $_FILES['file'];
    }

    if (!$file_entry || empty($file_entry['name'])) {
        echo json_encode(['success' => false, 'error' => 'No replacement image provided.']);
        exit;
    }

    $up_res = handle_image_upload($file_entry, 'room_gal', ['auto_compress' => true]);
    if (!$up_res['success'] || empty($up_res['path'])) {
        echo json_encode(['success' => false, 'error' => $up_res['error'] ?? 'Upload failed.']);
        exit;
    }

    $new_url = $up_res['path'];
    $new_dims = [0, 0];
    $new_orientation = 'landscape';
    $full_local = SANCTUARY_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($new_url, '/'));
    if (file_exists($full_local)) {
        $dims = @getimagesize($full_local);
        if ($dims) {
            $new_dims = [(int)$dims[0], (int)$dims[1]];
            if ($dims[1] > $dims[0]) $new_orientation = 'portrait';
            elseif ($dims[1] === $dims[0]) $new_orientation = 'square';
        }
    }

    try {
        $pdo = get_db();
        $r_stmt = $pdo->prepare("SELECT {$gallery_col}, image_url, slug, stay_type FROM rooms WHERE id = ?");
        $r_stmt->execute([$room_id]);
        $r_data = $r_stmt->fetch(PDO::FETCH_ASSOC);

        if ($r_data) {
            $updated_list = [];
            $found = false;
            if (!empty($r_data[$gallery_col])) {
                $dec = json_decode($r_data[$gallery_col], true);
                if (is_array($dec)) {
                    foreach ($dec as $item) {
                        $u = is_array($item) ? ($item['url'] ?? '') : (string)$item;
                        if ($u === $old_url) {
                            $found = true;
                            if (is_array($item)) {
                                $item['url'] = $new_url;
                                $item['width'] = $new_dims[0];
                                $item['height'] = $new_dims[1];
                                $item['orientation'] = $new_orientation;
                                $updated_list[] = $item;
                            } else {
                                $updated_list[] = [
                                    'url' => $new_url,
                                    'title' => '',
                                    'description' => '',
                                    'order' => count($updated_list) + 1,
                                    'orientation' => $new_orientation
                                ];
                            }
                        } else {
                            $updated_list[] = is_array($item) ? $item : [
                                'url' => $u,
                                'title' => '',
                                'description' => '',
                                'order' => count($updated_list) + 1
                            ];
                        }
                    }
                }
            }
            if (!$found) {
                $updated_list[] = [
                    'url' => $new_url,
                    'title' => '',
                    'description' => '',
                    'order' => count($updated_list) + 1,
                    'orientation' => $new_orientation
                ];
            }

            $cover_updated = false;
            $new_cover = $r_data['image_url'];
            if ($gallery_col === 'photos' && ($r_data['image_url'] === $old_url || empty($r_data['image_url']))) {
                $new_cover = $new_url;
                $cover_updated = true;
                $st = $r_data['stay_type'] ?? '';
                if ($st === 'treehouse' || $st === 'woodhouse' || stripos($r_data['slug'], 'tree') !== false) {
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_woodhouse_cover', ?)")->execute([$new_cover]);
                } elseif ($st === 'mudhouse' || stripos($r_data['slug'], 'mud') !== false) {
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_mudhouse_cover', ?)")->execute([$new_cover]);
                }
            }

            $new_json = json_encode(array_values($updated_list), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($gallery_col === 'photos') {
                $pdo->prepare("UPDATE rooms SET photos = ?, image_url = ? WHERE id = ?")->execute([$new_json, $new_cover, $room_id]);
            } else {
                $pdo->prepare("UPDATE rooms SET {$gallery_col} = ? WHERE id = ?")->execute([$new_json, $room_id]);
            }

            if (function_exists('sanctuary_purge_all_caches')) {
                sanctuary_purge_all_caches();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Photo replaced successfully!',
                'new_url' => $new_url,
                'old_url' => $old_url,
                'gallery_type' => $gallery_type,
                'cover_updated' => $cover_updated,
                'orientation' => $new_orientation,
                'dims_label' => ($new_dims[0] && $new_dims[1]) ? "{$new_dims[0]}×{$new_dims[1]}px" : ''
            ]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error replacing photo: ' . $e->getMessage()]);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Room not found.']);
    exit;
}

// Action 4: Save Entire Gallery (Re-ordering and Title/Description edits)
if ($action === 'save_gallery') {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $photos_raw = $_POST['photos'] ?? '';
    if ($room_id > 0) {
        try {
            $parsed_photos = [];
            if (is_array($photos_raw)) {
                $parsed_photos = $photos_raw;
            } elseif (is_string($photos_raw) && trim($photos_raw) !== '') {
                $dec = json_decode($photos_raw, true);
                if (is_array($dec)) {
                    $parsed_photos = $dec;
                }
            }
            
            $clean_list = [];
            foreach ($parsed_photos as $idx => $p) {
                if (is_array($p)) {
                    $u = trim($p['url'] ?? '');
                    if ($u !== '') {
                        $clean_list[] = [
                            'url' => $u,
                            'title' => trim($p['title'] ?? ''),
                            'description' => trim($p['description'] ?? ''),
                            'order' => isset($p['order']) ? (int)$p['order'] : ($idx + 1)
                        ];
                    }
                } elseif (is_string($p) && trim($p) !== '') {
                    $clean_list[] = [
                        'url' => trim($p),
                        'title' => '',
                        'description' => '',
                        'order' => $idx + 1
                    ];
                }
            }
            
            $new_json = !empty($clean_list) ? json_encode(array_values($clean_list), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
            $pdo = get_db();
            $pdo->prepare("UPDATE rooms SET {$gallery_col} = ? WHERE id = ?")->execute([$new_json, $room_id]);
            
            if (function_exists('sanctuary_purge_all_caches')) {
                sanctuary_purge_all_caches();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Gallery configuration and photo titles saved successfully!',
                'gallery_type' => $gallery_type,
                'count' => count($clean_list)
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error saving gallery: ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Invalid room ID.']);
    exit;
}

// Detect if payload exceeded PHP post_max_size
$content_length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($content_length > 0 && empty($_POST) && empty($_FILES)) {
    $post_max = ini_get('post_max_size') ?: '40M';
    echo json_encode([
        'success' => false,
        'error' => "The selected upload exceeds the server POST size limit ({$post_max}). Photos will be uploaded sequentially in smaller sizes."
    ]);
    exit;
}

// Find files entry
$files_entry = null;
if (!empty($_FILES['room_photos'])) {
    $files_entry = $_FILES['room_photos'];
} elseif (!empty($_FILES['photos'])) {
    $files_entry = $_FILES['photos'];
} elseif (!empty($_FILES['files'])) {
    $files_entry = $_FILES['files'];
} else {
    // Check for any field ending with _files or files_...
    foreach ($_FILES as $key => $val) {
        if (!empty($val['name'])) {
            $files_entry = $val;
            break;
        }
    }
}

if (!$files_entry || empty($files_entry['name'])) {
    echo json_encode(['success' => false, 'error' => 'No files uploaded or files array is empty.']);
    exit;
}

$uploaded_items = [];
$errors = [];

// Normalize single file to array format
$names = is_array($files_entry['name']) ? $files_entry['name'] : [$files_entry['name']];
$types = is_array($files_entry['type']) ? $files_entry['type'] : [$files_entry['type']];
$tmp_names = is_array($files_entry['tmp_name']) ? $files_entry['tmp_name'] : [$files_entry['tmp_name']];
$err_codes = is_array($files_entry['error']) ? $files_entry['error'] : [$files_entry['error']];
$sizes = is_array($files_entry['size']) ? $files_entry['size'] : [$files_entry['size']];

$count = count($names);

for ($i = 0; $i < $count; $i++) {
    if (empty($names[$i]) || ($err_codes[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        continue;
    }

    $single = [
        'name'     => $names[$i],
        'type'     => $types[$i] ?? '',
        'tmp_name' => $tmp_names[$i] ?? '',
        'error'    => $err_codes[$i] ?? UPLOAD_ERR_OK,
        'size'     => $sizes[$i] ?? 0
    ];

    $upload_res = handle_image_upload($single, 'room_gal', ['auto_compress' => true]);

    if ($upload_res['success'] && !empty($upload_res['path'])) {
        $full_local_path = SANCTUARY_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($upload_res['path'], '/'));
        
        $width = 0;
        $height = 0;
        $orientation = 'landscape';
        if (file_exists($full_local_path)) {
            $dims = @getimagesize($full_local_path);
            if ($dims) {
                $width = (int)$dims[0];
                $height = (int)$dims[1];
                if ($height > $width) {
                    $orientation = 'portrait';
                } elseif ($height === $width) {
                    $orientation = 'square';
                } else {
                    $orientation = 'landscape';
                }
            }
        }

        // Clean file name to create an attractive suggested title
        $raw_name = pathinfo($single['name'], PATHINFO_FILENAME);
        $clean_title = ucwords(trim(preg_replace('/[_\-\s\d]+/', ' ', $raw_name)));
        if (empty($clean_title) || strlen($clean_title) < 3) {
            $clean_title = 'Suite Showcase View';
        }

        $uploaded_items[] = [
            'url'         => $upload_res['path'],
            'filename'    => $upload_res['filename'],
            'title'       => $clean_title,
            'description' => '',
            'width'       => $width,
            'height'      => $height,
            'orientation' => $orientation,
            'size'        => $upload_res['optimization']['new_size'] ?? $single['size'],
            'dims_label'  => ($width && $height) ? "{$width}×{$height}px" : ''
        ];
    } else {
        $errors[] = ($single['name'] ?? 'File') . ': ' . ($upload_res['error'] ?? 'Upload failed.');
    }
}

if (empty($uploaded_items) && !empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(' | ', $errors)]);
    exit;
}

// Auto-persist uploaded photos to rooms in MySQL if room_id was provided
$target_room_id = (int)($_POST['room_id'] ?? 0);
$persisted_to_db = false;
$new_cover_url = null;

if ($target_room_id > 0 && !empty($uploaded_items)) {
    try {
        $pdo = get_db();
        $r_stmt = $pdo->prepare("SELECT {$gallery_col}, image_url, slug, stay_type FROM rooms WHERE id = ?");
        $r_stmt->execute([$target_room_id]);
        $r_data = $r_stmt->fetch(PDO::FETCH_ASSOC);
        if ($r_data) {
            $existing_photos = [];
            if (!empty($r_data[$gallery_col])) {
                $dec = json_decode($r_data[$gallery_col], true);
                if (is_array($dec)) {
                    foreach ($dec as $item) {
                        if (is_array($item)) {
                            $existing_photos[] = $item;
                        } elseif (is_string($item) && trim($item) !== '') {
                            $existing_photos[] = [
                                'url'         => trim($item),
                                'title'       => '',
                                'description' => '',
                                'order'       => count($existing_photos) + 1
                            ];
                        }
                    }
                }
            }

            foreach ($uploaded_items as $new_item) {
                $existing_photos[] = [
                    'url'         => $new_item['url'],
                    'title'       => $new_item['title'] ?? '',
                    'description' => $new_item['description'] ?? '',
                    'order'       => count($existing_photos) + 1,
                    'width'       => $new_item['width'] ?? 0,
                    'height'      => $new_item['height'] ?? 0,
                    'orientation' => $new_item['orientation'] ?? 'landscape'
                ];
            }

            $current_img = $r_data['image_url'] ?? '';
            $update_img = $current_img;
            if ($gallery_col === 'photos' && (empty($current_img) || strpos($current_img, 'exterior.png') !== false || strpos($current_img, 'placeholder') !== false)) {
                $update_img = $uploaded_items[0]['url'];
                $new_cover_url = $update_img;

                $st = $r_data['stay_type'] ?? '';
                if ($st === 'treehouse' || $st === 'woodhouse' || stripos($r_data['slug'], 'tree') !== false) {
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_woodhouse_cover', ?)")->execute([$update_img]);
                } elseif ($st === 'mudhouse' || stripos($r_data['slug'], 'mud') !== false) {
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('walkthrough_mudhouse_cover', ?)")->execute([$update_img]);
                }
            }

            $new_json = json_encode(array_values($existing_photos), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($gallery_col === 'photos') {
                $pdo->prepare("UPDATE rooms SET photos = ?, image_url = ? WHERE id = ?")->execute([$new_json, $update_img, $target_room_id]);
            } else {
                $pdo->prepare("UPDATE rooms SET {$gallery_col} = ? WHERE id = ?")->execute([$new_json, $target_room_id]);
            }

            if (function_exists('sanctuary_purge_all_caches')) {
                sanctuary_purge_all_caches();
            }
            $persisted_to_db = true;
        }
    } catch (Exception $e) {
        error_log("Auto-save room photos error: " . $e->getMessage());
    }
}

echo json_encode([
    'success'         => true,
    'count'           => count($uploaded_items),
    'photos'          => $uploaded_items,
    'gallery_type'    => $gallery_type,
    'persisted_to_db' => $persisted_to_db,
    'new_cover_url'   => $new_cover_url,
    'errors'          => $errors
]);
exit;
