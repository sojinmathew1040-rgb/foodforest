<?php
// =========================================================================
// Food Forest Sanctuary — Room Photos Bulk Upload & Optimization API
// Handles secure multi-file upload for Villa/Cottage Gallery Showcase
// Supports Portrait and Landscape orientations with auto-compression
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/auth.php';
require_admin_auth();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method. POST required.']);
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

echo json_encode([
    'success' => true,
    'count'   => count($uploaded_items),
    'photos'  => $uploaded_items,
    'errors'  => $errors
]);
exit;
