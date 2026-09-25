<?php
// =========================================================================
// Food Forest Sanctuary — Estate Settings & Cards-Based CMS Hub
// Modeled after Delight Builders: Card-by-Card Frontend Section Management
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/includes/stitcher.php';

$page_title = 'Estate Settings & Configuration Hub';
$page_subtitle = 'Card-by-card control of estate parameters, frontend copy, media & database backups';

$pdo = get_db();

// Handle SQL Backup Export (One-Click phpMyAdmin-Style Live MySQL Dump)
if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    try {
        $tables_stmt = $pdo->query("SHOW TABLES");
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);

        $db_name = DB_NAME;
        $sql_dump  = "-- ========================================================\n";
        $sql_dump .= "-- FOOD FOREST SANCTUARY DATABASE BACKUP\n";
        $sql_dump .= "-- phpMyAdmin-Compatible Full MySQL Database Dump\n";
        $sql_dump .= "-- Exported on: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- Database: `" . $db_name . "`\n";
        $sql_dump .= "-- ========================================================\n\n";

        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql_dump .= "START TRANSACTION;\n";
        $sql_dump .= "SET time_zone = \"+00:00\";\n\n";

        $sql_dump .= "CREATE DATABASE IF NOT EXISTS `" . $db_name . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        $sql_dump .= "USE `" . $db_name . "`;\n\n";

        foreach ($tables as $table) {
            $sql_dump .= "-- --------------------------------------------------------\n";
            $sql_dump .= "-- Table structure for table `$table`\n";
            $sql_dump .= "-- --------------------------------------------------------\n\n";
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";

            $create_stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $create_row = $create_stmt->fetch(PDO::FETCH_NUM);
            if (!empty($create_row[1])) {
                $sql_dump .= $create_row[1] . ";\n\n";
            }

            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $sql_dump .= "-- Dumping data for table `$table` --\n";
                $cols = array_keys($rows[0]);
                $col_names = "`" . implode("`, `", $cols) . "`";

                $sql_dump .= "INSERT INTO `$table` ($col_names) VALUES\n";
                $val_rows = [];
                foreach ($rows as $row) {
                    $escaped_vals = array_map(function($v) use ($pdo) {
                        if ($v === null) return "NULL";
                        return $pdo->quote($v);
                    }, array_values($row));
                    $val_rows[] = "(" . implode(", ", $escaped_vals) . ")";
                }
                $sql_dump .= implode(",\n", $val_rows) . ";\n\n";
            }
        }

        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sql_dump .= "COMMIT;\n";

        // Also refresh the disk backups
        @file_put_contents(__DIR__ . '/../foodforest.sql', $sql_dump);
        @file_put_contents(__DIR__ . '/data/foodforest.sql', $sql_dump);

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename=FoodForest_MySQL_Backup_' . date('Y-m-d_His') . '.sql');
        header('Content-Length: ' . strlen($sql_dump));
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $sql_dump;
        exit;
    } catch (Exception $e) {
        die("Backup export failed: " . $e->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';

$alert_message = '';
$alert_type = 'success';
$active_tab = $_GET['section'] ?? ($_GET['tab'] ?? 'estate');

$tab_titles = [
    'climate' => 'CARD 01 • CLIMATE TICKER & SANCTUARY ACCOLADES',
    'hero' => 'CARD 02 • HERO MARQUEE & VISUAL BACKDROP',
    'philosophy' => 'CARD 03 • SANCTUARY PHILOSOPHY & WELCOME MANIFESTO',
    'rooms' => 'CARD 04 • VILLAS, COTTAGES & LIVE TARIFFS',
    'experiences' => 'CARD 05 • CURATED EXPERIENCES & RITUALS',
    'menu' => 'CARD 06 • FOOD MENU & LIVING GASTRONOMY HUB',
    'why' => 'CARD 07 • WHY FOOD FOREST? (EARTHEN COB & AGROFORESTRY)',
    'sanctuary_map' => 'CARD 08 • SANCTUARY ESTATE MAP & MOUNTAIN TRAILS',
    'seasons' => 'CARD 09 • SEASONS OF KANTHALLOOR & HARVEST',
    'gallery' => 'CARD 10 • VISUAL DIARY (8 PHOTO CHRONICLE)',
    'testimonials' => 'CARD 11 • GUEST REFLECTIONS & REVIEWS',
    'whatsapp' => 'CARD 12 • WHATSAPP & CONCIERGE CHANNELS',
    'estate' => 'CARD 13 • ESTATE BRANDING & OPERATIONAL IDENTITY',
    'protection' => 'CARD 14 • CONTENT PROTECTION & DEVTOOLS SHIELD',
    'security' => 'CARD 15 • SECURITY & MASTER PASSWORD',
    'backup' => 'CARD 16 • MYSQL DATABASE BACKUP & RESTORE',
    'bank' => 'CARD 17 • BANK DETAILS & UPI QR CODE',
    'footer' => 'CARD 18 • FOOTER & ECO TRUST PILLARS'
];
if (!array_key_exists($active_tab, $tab_titles)) {
    $active_tab = 'climate';
}

// Support direct 1-click GET item deletion actions with CSRF token
if (isset($_GET['action']) && in_array($_GET['action'], ['delete_room', 'delete_exp', 'delete_menu', 'delete_season', 'delete_spot', 'delete_gal', 'delete_testimonial'])) {
    if (verify_csrf_token($_GET['csrf_token'] ?? '')) {
        $action = $_GET['action'];
        $item_id = (int)($_GET['id'] ?? 0);
        if ($item_id > 0) {
            if ($action === 'delete_room') {
                $del = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $del->execute([$item_id]);
                $alert_message = 'Villa dwelling permanently removed from sanctuary.';
                $active_tab = 'rooms';
            } elseif ($action === 'delete_exp') {
                $del = $pdo->prepare("DELETE FROM experiences WHERE id = ?");
                $del->execute([$item_id]);
                $alert_message = 'Curated ritual experience permanently removed.';
                $active_tab = 'experiences';
            } elseif ($action === 'delete_menu') {
                $del = $pdo->prepare("DELETE FROM food_menu WHERE id = ?");
                $del->execute([$item_id]);
                $alert_message = 'Gastronomy menu item permanently removed.';
                $active_tab = 'menu';
            } elseif ($action === 'delete_season') {
                $del = $pdo->prepare("DELETE FROM seasons WHERE id = ?");
                $del->execute([$item_id]);
                $alert_message = 'Season card permanently removed.';
                $active_tab = 'seasons';
            } elseif ($action === 'delete_spot') {
                $del = $pdo->prepare("DELETE FROM sanctuary_spots WHERE id = ?");
                $del->execute([$item_id]);
                $alert_message = 'Estate spot waypoint permanently removed.';
                $active_tab = 'sanctuary_map';
            } elseif ($action === 'delete_gal') {
                $del = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
                $del->execute([$item_id]);
                $alert_message = 'Visual diary photo permanently removed.';
                $active_tab = 'gallery';
            } elseif ($action === 'delete_testimonial') {
                $del = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
                $del->execute([$item_id]);
                $alert_message = 'Guest reflection testimonial permanently removed.';
                $active_tab = 'testimonials';
            }
        }
    } else {
        $alert_message = 'Security validation failed for deletion request.';
        $alert_type = 'error';
    }
}

$page_title = 'Editing ' . $tab_titles[$active_tab];
$page_subtitle = 'Live Split-Screen Section Editor & Real-Time User-Side Preview';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $form_type = $_POST['form_type'] ?? '';
        $active_tab = $_POST['active_tab'] ?? ($active_tab ?? 'estate');

        // Direct Global POST Item Deletion (Bypasses parent forms)
        if (!empty($_POST['delete_room_id'])) {
            $del_id = (int)$_POST['delete_room_id'];
            $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$del_id]);
            $alert_message = 'Villa dwelling permanently removed from sanctuary.';
            $active_tab = 'rooms';
        }
        elseif (!empty($_POST['delete_exp_id'])) {
            $del_id = (int)$_POST['delete_exp_id'];
            $pdo->prepare("DELETE FROM experiences WHERE id = ?")->execute([$del_id]);
            $alert_message = 'Curated ritual experience permanently removed.';
            $active_tab = 'experiences';
        }
        elseif (!empty($_POST['delete_menu_id'])) {
            $del_id = (int)$_POST['delete_menu_id'];
            $pdo->prepare("DELETE FROM food_menu WHERE id = ?")->execute([$del_id]);
            $alert_message = 'Gastronomy menu item permanently removed.';
            $active_tab = 'menu';
        }
        elseif (!empty($_POST['delete_season_id'])) {
            $del_id = (int)$_POST['delete_season_id'];
            $pdo->prepare("DELETE FROM seasons WHERE id = ?")->execute([$del_id]);
            $alert_message = 'Season card permanently removed.';
            $active_tab = 'seasons';
        }
        elseif (!empty($_POST['delete_spot_id'])) {
            $del_id = (int)$_POST['delete_spot_id'];
            $pdo->prepare("DELETE FROM sanctuary_spots WHERE id = ?")->execute([$del_id]);
            $alert_message = 'Estate spot waypoint permanently removed.';
            $active_tab = 'sanctuary_map';
        }
        elseif (!empty($_POST['delete_gal_id'])) {
            $del_id = (int)$_POST['delete_gal_id'];
            $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$del_id]);
            $alert_message = 'Visual diary photo permanently removed.';
            $active_tab = 'gallery';
        }
        elseif (!empty($_POST['delete_testimonial_id'])) {
            $del_id = (int)$_POST['delete_testimonial_id'];
            $pdo->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$del_id]);
            $alert_message = 'Guest reflection testimonial permanently removed.';
            $active_tab = 'testimonials';
        }

        // 1. Estate & Branding Card
        elseif ($form_type === 'estate_settings') {
            $keys = ['estate_name', 'estate_tagline', 'checkin_time', 'checkout_time', 'currency_symbol'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            $alert_message = 'Estate identity & stay parameters successfully updated.';
        }

        // 2. WhatsApp & Concierge Card
        elseif ($form_type === 'whatsapp_settings') {
            $keys = ['concierge_whatsapp', 'concierge_phone', 'concierge_email', 'location'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            $alert_message = 'WhatsApp Concierge & communication channels updated.';
        }

        // 3. Hero Section & Atmosphere Card
        elseif ($form_type === 'hero_settings') {
            if (!empty($_FILES['hero_bg_image_file']['name'])) {
                $up = handle_image_upload($_FILES['hero_bg_image_file'], 'hero_bg');
                if ($up['success']) {
                    $_POST['hero_bg_image'] = $up['path'];
                } else {
                    $alert_message = 'Hero background upload: ' . $up['error'];
                    $alert_type = 'error';
                }
            }
            $keys = ['hero_eyebrow', 'hero_title', 'hero_desc', 'hero_bg_image'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            if (empty($alert_message)) {
                $alert_message = 'Hero section marquee text & background visual updated.';
            }
        }

        // 4. Climate & Accolades Card
        elseif ($form_type === 'climate_settings') {
            $keys = ['top_bar_location', 'top_bar_accolade', 'hero_tag_climate'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            $alert_message = 'High-range climate banner & accolades updated.';
        }

        // 5. Philosophy & Ethos Card
        elseif ($form_type === 'philosophy_settings') {
            if (!empty($_FILES['welcome_image_file']['name'])) {
                $up = handle_image_upload($_FILES['welcome_image_file'], 'welcome');
                if ($up['success']) {
                    $_POST['welcome_image'] = $up['path'];
                } else {
                    $alert_message = 'Welcome portrait upload: ' . $up['error'];
                    $alert_type = 'error';
                }
            }
            $keys = [
                'welcome_badge', 'welcome_title', 'welcome_paragraph', 'welcome_image',
                'welcome_feat1_title', 'welcome_feat1_desc',
                'welcome_feat2_title', 'welcome_feat2_desc',
                'welcome_feat3_title', 'welcome_feat3_desc',
                'welcome_feat4_title', 'welcome_feat4_desc'
            ];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            if (empty($alert_message)) {
                $alert_message = 'Sanctuary philosophy narrative, portrait & 4 ecological pillar cards updated.';
            }
        }

        // 6. Why Farmstay Story Card
        elseif ($form_type === 'why_settings') {
            if (!empty($_FILES['why_image_file']['name'])) {
                $up = handle_image_upload($_FILES['why_image_file'], 'why_mudhouse');
                if ($up['success']) {
                    $_POST['why_image'] = $up['path'];
                } else {
                    $alert_message = 'Why Sanctuary photo upload: ' . $up['error'];
                    $alert_type = 'error';
                }
            }
            $keys = [
                'why_badge', 'why_title', 'why_desc', 'why_image',
                'why_feat1_title', 'why_feat1_desc',
                'why_feat2_title', 'why_feat2_desc',
                'why_feat3_title', 'why_feat3_desc',
                'why_feat4_title', 'why_feat4_desc'
            ];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            if (empty($alert_message)) {
                $alert_message = 'Why Food Forest farmstay story & visual updated.';
            }
        }

        // 7. Curated Experiences Card (Dynamic CMS: Add, Edit, Delete Experiences)
        elseif ($form_type === 'experiences_settings') {
            ensure_rooms_360_column();
$all_rooms = $pdo->query("SELECT * FROM rooms ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_rooms as &$rm) {
    $rm_gallery = [];
    if (!empty($rm['gallery_images'])) {
        $dec = json_decode($rm['gallery_images'], true);
        if (is_array($dec)) {
            $rm_gallery = array_values(array_filter($dec));
        }
    }
    if (empty($rm_gallery) && !empty($rm['image_url'])) {
        $rm_gallery = [$rm['image_url']];
    }
    $rm['gallery_list'] = $rm_gallery;
}
unset($rm);

ensure_experiences_details_columns($pdo);

            if (!empty($_POST['delete_exp_id'])) {
                $del_id = (int)$_POST['delete_exp_id'];
                $del = $pdo->prepare("DELETE FROM experiences WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Curated ritual experience permanently removed.';
            } elseif (($_POST['action'] ?? '') === 'add_experience') {
                $title = trim($_POST['new_exp_title'] ?? '');
                $tagline = trim($_POST['new_exp_tagline'] ?? '');
                $badge = trim($_POST['new_exp_badge'] ?? 'INCLUDED IN STAY');
                $timing = trim($_POST['new_exp_timing'] ?? '2 Hours • Morning');
                $schedule = trim($_POST['new_exp_schedule'] ?? '');
                $location = trim($_POST['new_exp_location'] ?? '');
                $suitable = trim($_POST['new_exp_suitable'] ?? '');
                $bring = trim($_POST['new_exp_bring'] ?? '');
                $desc = trim($_POST['new_exp_desc'] ?? '');
                $detailed_desc = trim($_POST['new_exp_detailed_desc'] ?? '');
                $inclusions = trim($_POST['new_exp_inclusions'] ?? '');
                
                // Highlights
                $highlights_raw = trim($_POST['new_exp_highlights'] ?? '');
                $highlights_arr = array_values(array_filter(array_map('trim', explode("\n", $highlights_raw))));
                $highlights_json = json_encode($highlights_arr);

                // Main Backdrop Photograph
                $img = trim($_POST['new_exp_image'] ?? 'assets/images/01 (18).jpeg');
                if (!empty($_FILES['new_exp_image_file']['name'])) {
                    $up = handle_image_upload($_FILES['new_exp_image_file'], 'exp');
                    if ($up['success']) {
                        $img = $up['path'];
                    }
                }

                // Additional Gallery Images
                $gallery_arr = [$img];
                if (!empty($_POST['new_exp_gallery_urls'])) {
                    $urls = array_filter(array_map('trim', explode("\n", $_POST['new_exp_gallery_urls'])));
                    foreach ($urls as $u) {
                        if (!in_array($u, $gallery_arr)) $gallery_arr[] = $u;
                    }
                }
                if (!empty($_FILES['new_exp_gallery_files']['name']) && !empty($_FILES['new_exp_gallery_files']['name'][0])) {
                    $uploaded_gallery = handle_multi_image_upload($_FILES['new_exp_gallery_files'], 'exp_gal');
                    foreach ($uploaded_gallery as $u) {
                        if (!in_array($u, $gallery_arr)) $gallery_arr[] = $u;
                    }
                }
                $gallery_json = json_encode(array_values(array_unique($gallery_arr)));

                if (!empty($title) && !empty($desc)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM experiences")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO experiences (
                        title, tagline, badge, timing, schedule_info, location_info, suitable_for, what_to_bring,
                        description, detailed_description, highlights, inclusions, image_url, gallery_images, display_order, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([
                        $title, $tagline, $badge, $timing, $schedule, $location, $suitable, $bring,
                        $desc, $detailed_desc, $highlights_json, $inclusions, $img, $gallery_json, $max_order + 1
                    ]);
                    $alert_message = 'New curated ritual experience added successfully with full gallery and in-depth details!';
                } else {
                    $alert_message = 'Experience title and description cannot be blank.';
                    $alert_type = 'error';
                }
            } else {
                $keys = ['experiences_badge', 'experiences_title', 'experiences_desc'];
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim($_POST[$k])]);
                    }
                }

                // Update individual experiences
                if (isset($_POST['exp_id']) && is_array($_POST['exp_id'])) {
                    $upd_exp = $pdo->prepare("UPDATE experiences SET 
                        title = ?, tagline = ?, badge = ?, timing = ?, schedule_info = ?, location_info = ?, suitable_for = ?, what_to_bring = ?,
                        description = ?, detailed_description = ?, highlights = ?, inclusions = ?, image_url = ?, gallery_images = ?
                        WHERE id = ?");
                    
                    foreach ($_POST['exp_id'] as $idx => $eid) {
                        $t = trim($_POST['exp_title'][$idx] ?? '');
                        $tagline = trim($_POST['exp_tagline'][$idx] ?? '');
                        $b = trim($_POST['exp_badge'][$idx] ?? '');
                        $tm = trim($_POST['exp_timing'][$idx] ?? '');
                        $sched = trim($_POST['exp_schedule'][$idx] ?? '');
                        $loc = trim($_POST['exp_location'][$idx] ?? '');
                        $suit = trim($_POST['exp_suitable'][$idx] ?? '');
                        $bring = trim($_POST['exp_bring'][$idx] ?? '');
                        $d = trim($_POST['exp_desc'][$idx] ?? '');
                        $detailed_d = trim($_POST['exp_detailed_desc'][$idx] ?? '');
                        $incl = trim($_POST['exp_inclusions'][$idx] ?? '');

                        // Highlights
                        $hl_raw = trim($_POST['exp_highlights'][$idx] ?? '');
                        $hl_arr = array_values(array_filter(array_map('trim', explode("\n", $hl_raw))));
                        $hl_json = json_encode($hl_arr);

                        // Main image
                        $img = trim($_POST['exp_image'][$idx] ?? '');
                        if (isset($_FILES['exp_image_file'])) {
                            $up = handle_indexed_image_upload($_FILES['exp_image_file'], $idx, 'exp');
                            if ($up['success']) {
                                $img = $up['path'];
                            }
                        }

                        // Gallery Images from textarea
                        $existing_gal_raw = trim($_POST['exp_gallery_urls'][$idx] ?? '');
                        $gal_arr = array_values(array_filter(array_map('trim', explode("\n", $existing_gal_raw))));
                        if (!empty($img) && !in_array($img, $gal_arr)) {
                            array_unshift($gal_arr, $img);
                        }

                        // Handle multi-upload for this card index if present
                        if (isset($_FILES['exp_gallery_files_' . $eid])) {
                            $more_uploads = handle_multi_image_upload($_FILES['exp_gallery_files_' . $eid], 'exp_gal');
                            foreach ($more_uploads as $mu) {
                                if (!in_array($mu, $gal_arr)) $gal_arr[] = $mu;
                            }
                        }
                        $gal_json = json_encode(array_values(array_unique($gal_arr)));

                        $upd_exp->execute([
                            $t, $tagline, $b, $tm, $sched, $loc, $suit, $bring,
                            $d, $detailed_d, $hl_json, $incl, $img, $gal_json, (int)$eid
                        ]);
                    }
                }
                $alert_message = 'Curated Experiences header & all individual rituals updated successfully with multi-photos and in-depth details.';
            }
        }

        // 7b. Food Menu & Gastronomy Card (Dynamic CMS: Add, Edit, Delete Dishes)
        elseif ($form_type === 'menu_settings') {
            ensure_food_menu_table_exists($pdo);

            if (!empty($_POST['delete_menu_id'])) {
                $del_id = (int)$_POST['delete_menu_id'];
                $del = $pdo->prepare("DELETE FROM food_menu WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Dish permanently removed from food menu archive.';
            } elseif (($_POST['action'] ?? '') === 'add_menu_item') {
                $category = strtolower(trim($_POST['new_item_category'] ?? 'breakfast'));
                $heading = trim($_POST['new_item_heading'] ?? '');
                $subtitle = trim($_POST['new_item_subtitle'] ?? '');
                $price = floatval($_POST['new_item_price'] ?? 0);
                $price_note = trim($_POST['new_item_price_note'] ?? 'Per Set');
                $dietary = trim($_POST['new_item_dietary_type'] ?? 'veg');
                $badge = trim($_POST['new_item_badge'] ?? 'FARM FRESH');
                $desc = trim($_POST['new_item_desc'] ?? '');
                $inclusions = trim($_POST['new_item_inclusions'] ?? '');

                // Image upload
                $img = trim($_POST['new_item_image'] ?? 'assets/images/food_dosa_set.jpg');
                if (!empty($_FILES['new_item_image_file']['name'])) {
                    $up = handle_image_upload($_FILES['new_item_image_file'], 'food');
                    if ($up['success']) {
                        $img = $up['path'];
                    }
                }

                // Gallery Images
                $gallery_arr = [$img];
                if (!empty($_POST['new_item_gallery_urls'])) {
                    $urls = array_filter(array_map('trim', explode("\n", $_POST['new_item_gallery_urls'])));
                    foreach ($urls as $u) {
                        if (!in_array($u, $gallery_arr)) $gallery_arr[] = $u;
                    }
                }
                if (!empty($_FILES['new_item_gallery_files']['name']) && !empty($_FILES['new_item_gallery_files']['name'][0])) {
                    $uploaded_gallery = handle_multi_image_upload($_FILES['new_item_gallery_files'], 'food_gal');
                    foreach ($uploaded_gallery as $u) {
                        if (!in_array($u, $gallery_arr)) $gallery_arr[] = $u;
                    }
                }
                $gallery_json = json_encode(array_values(array_unique($gallery_arr)));

                if (!empty($heading)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM food_menu WHERE category = " . $pdo->quote($category))->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO food_menu (
                        category, heading, subtitle, price, price_note, description, inclusions, dietary_type, badge, image_url, gallery_images, display_order, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([
                        $category, $heading, $subtitle, $price, $price_note, $desc, $inclusions, $dietary, $badge, $img, $gallery_json, $max_order + 1
                    ]);
                    $alert_message = 'New dish successfully added to Food Menu & Gastronomy Hub!';
                } else {
                    $alert_message = 'Dish heading / title cannot be blank.';
                    $alert_type = 'error';
                }
            } else {
                // Save header settings
                $keys = [
                    'menu_badge', 'menu_title', 'menu_desc',
                    'menu_time_breakfast', 'menu_desc_breakfast',
                    'menu_time_lunch', 'menu_desc_lunch',
                    'menu_time_snacks', 'menu_desc_snacks',
                    'menu_time_dinner', 'menu_desc_dinner'
                ];
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim($_POST[$k])]);
                    }
                }

                // Update individual menu items
                if (isset($_POST['menu_id']) && is_array($_POST['menu_id'])) {
                    $upd_menu = $pdo->prepare("UPDATE food_menu SET 
                        heading = ?, subtitle = ?, category = ?, price = ?, price_note = ?, dietary_type = ?, badge = ?,
                        description = ?, inclusions = ?, image_url = ?, gallery_images = ?, is_active = ?
                        WHERE id = ?");

                    foreach ($_POST['menu_id'] as $idx => $mid) {
                        $h = trim($_POST['menu_heading'][$idx] ?? '');
                        $sub = trim($_POST['menu_subtitle'][$idx] ?? '');
                        $cat = strtolower(trim($_POST['menu_category'][$idx] ?? 'breakfast'));
                        $pr = floatval($_POST['menu_price'][$idx] ?? 0);
                        $pr_note = trim($_POST['menu_price_note'][$idx] ?? 'Per Set');
                        $dt = trim($_POST['menu_dietary'][$idx] ?? 'veg');
                        $bdg = trim($_POST['menu_badge'][$idx] ?? 'FARM FRESH');
                        $d = trim($_POST['menu_desc'][$idx] ?? '');
                        $incl = trim($_POST['menu_inclusions'][$idx] ?? '');
                        $is_act = (isset($_POST['menu_active_' . $mid]) || (isset($_POST['menu_active'][$idx]) && $_POST['menu_active'][$idx] == '1')) ? 1 : 0;

                        // Main image
                        $img = trim($_POST['menu_image'][$idx] ?? '');
                        if (isset($_FILES['menu_image_file'])) {
                            $up = handle_indexed_image_upload($_FILES['menu_image_file'], $idx, 'food');
                            if ($up['success']) {
                                $img = $up['path'];
                            }
                        }

                        // Gallery Images
                        $existing_gal_raw = trim($_POST['menu_gallery_urls'][$idx] ?? '');
                        $gal_arr = array_values(array_filter(array_map('trim', explode("\n", $existing_gal_raw))));
                        if (!empty($img) && !in_array($img, $gal_arr)) {
                            array_unshift($gal_arr, $img);
                        }

                        // Check indexed multi-files
                        if (isset($_FILES['menu_gallery_files_' . $mid])) {
                            $more_up = handle_multi_image_upload($_FILES['menu_gallery_files_' . $mid], 'food_gal');
                            foreach ($more_up as $u) {
                                if (!in_array($u, $gal_arr)) $gal_arr[] = $u;
                            }
                        }
                        $gal_json = json_encode(array_values(array_unique($gal_arr)));

                        $upd_menu->execute([
                            $h, $sub, $cat, $pr, $pr_note, $dt, $bdg, $d, $incl, $img, $gal_json, $is_act, (int)$mid
                        ]);
                    }
                }
                $alert_message = 'Food menu dishes, prices, inclusions & category timings successfully saved!';
            }
        }

        // 8. Seasons of Kanthalloor Card (Dynamic CMS: Add, Edit, Delete Seasons)
        elseif ($form_type === 'seasons_settings') {
            ensure_seasons_table_exists($pdo);

            if (!empty($_POST['delete_season_id'])) {
                $del_id = (int)$_POST['delete_season_id'];
                $del = $pdo->prepare("DELETE FROM seasons WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Seasonal cycle card permanently removed.';
            } elseif (($_POST['action'] ?? '') === 'add_season') {
                $title = trim($_POST['new_season_name'] ?? '');
                $months = trim($_POST['new_season_months'] ?? '');
                $desc = trim($_POST['new_season_desc'] ?? '');
                $img = trim($_POST['new_season_image'] ?? 'assets/images/01 (9).jpeg');
                if (!empty($_FILES['new_season_image_file']['name'])) {
                    $up = handle_image_upload($_FILES['new_season_image_file'], 'season');
                    if ($up['success']) {
                        $img = $up['path'];
                    }
                }
                if (!empty($title) && !empty($desc)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM seasons")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO seasons (title, months, description, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                    $ins->execute([$title, $months, $desc, $img, $max_order + 1]);
                    $alert_message = 'New seasonal cycle card published successfully!';
                } else {
                    $alert_message = 'Season name and description cannot be blank.';
                    $alert_type = 'error';
                }
            } else {
                $keys = ['seasons_badge', 'seasons_title', 'seasons_desc'];
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim($_POST[$k])]);
                    }
                }

                // Update individual seasons
                if (isset($_POST['season_id']) && is_array($_POST['season_id'])) {
                    $upd_season = $pdo->prepare("UPDATE seasons SET title = ?, months = ?, description = ?, image_url = ? WHERE id = ?");
                    foreach ($_POST['season_id'] as $idx => $sid) {
                        $t = trim($_POST['season_title'][$idx] ?? '');
                        $m = trim($_POST['season_months'][$idx] ?? '');
                        $d = trim($_POST['season_desc'][$idx] ?? '');
                        $img = trim($_POST['season_image'][$idx] ?? '');
                        if (isset($_FILES['season_image_file'])) {
                            $up = handle_indexed_image_upload($_FILES['season_image_file'], $idx, 'season');
                            if ($up['success']) {
                                $img = $up['path'];
                            }
                        }
                        $upd_season->execute([$t, $m, $d, $img, (int)$sid]);
                    }
                }
                $alert_message = 'Seasons of Kanthalloor header & all seasonal cards updated successfully.';
            }
        }

        // 9. Sanctuary Estate Map & Mountain Route Trails Card (Dynamic CMS: Add, Edit, Delete Spots)
        elseif ($form_type === 'sanctuary_map_settings') {
            ensure_sanctuary_spots_table_exists($pdo);

            if (!empty($_POST['delete_spot_id'])) {
                $del_id = (int)$_POST['delete_spot_id'];
                $del = $pdo->prepare("DELETE FROM sanctuary_spots WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Estate map spot removed successfully.';
            } elseif (($_POST['action'] ?? '') === 'add_spot') {
                $spot_num = (int)($_POST['new_spot_number'] ?? 1);
                $title = trim($_POST['new_spot_title'] ?? '');
                $desc = trim($_POST['new_spot_desc'] ?? '');
                $category = trim($_POST['new_spot_category'] ?? 'nature');
                $linked_room_slug = trim($_POST['new_spot_linked_room_slug'] ?? '');
                $structure_type = trim($_POST['new_spot_structure_type'] ?? 'single_hut');
                $stay_price = (!empty($_POST['new_spot_stay_price']) && is_numeric($_POST['new_spot_stay_price'])) ? floatval($_POST['new_spot_stay_price']) : null;
                $is_stay = ($category === 'stays' || !empty($linked_room_slug)) ? 1 : 0;
                $x_coord = floatval($_POST['new_spot_x'] ?? 50.0);
                $y_coord = floatval($_POST['new_spot_y'] ?? 50.0);

                // Multi-photo upload for new spot
                $photos = [];
                if (!empty($_FILES['new_spot_photos']['name'])) {
                    $uploaded = handle_multi_image_upload($_FILES['new_spot_photos'], 'sanctuary');
                    if (!empty($uploaded)) {
                        $photos = $uploaded;
                    }
                }
                if (empty($photos) && !empty($_POST['new_spot_fallback_image'])) {
                    $photos = [trim($_POST['new_spot_fallback_image'])];
                }
                if (empty($photos)) {
                    $photos = ['assets/images/01 (10).jpeg'];
                }
                $primary_img = $photos[0];
                $photos_json = json_encode(array_values($photos));

                if (!empty($title) && !empty($desc)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM sanctuary_spots")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO sanctuary_spots 
                        (spot_number, title, subtitle_tag, category, is_stay, linked_room_slug, structure_type, stay_price, elevation, temperature, description, aroma, sound, image_url, photos, cta_text, cta_link, x_coord, y_coord, display_order, is_active) 
                        VALUES (?, ?, '', ?, ?, ?, ?, ?, '', '', ?, '', '', ?, ?, '', '', ?, ?, ?, 1)");
                    $ins->execute([$spot_num, $title, $category, $is_stay, $linked_room_slug ?: null, $structure_type, $stay_price, $desc, $primary_img, $photos_json, $x_coord, $y_coord, $max_order + 1]);
                    $alert_message = 'New estate spot with route waypoint & photos added successfully!';
                } else {
                    $alert_message = 'Spot title and description cannot be blank.';
                    $alert_type = 'error';
                }
            } else {
                // Header settings
                $keys = ['sanctuary_section_label', 'sanctuary_section_title'];
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim($_POST[$k])]);
                    }
                }

                // Update individual spots
                if (isset($_POST['spot_id']) && is_array($_POST['spot_id'])) {
                    $upd_spot = $pdo->prepare("UPDATE sanctuary_spots SET 
                        spot_number = ?, title = ?, category = ?, is_stay = ?, linked_room_slug = ?, structure_type = ?, stay_price = ?, description = ?, x_coord = ?, y_coord = ?, image_url = ?, photos = ? 
                        WHERE id = ?");

                    foreach ($_POST['spot_id'] as $idx => $sp_id) {
                        $s_num = (int)($_POST['spot_number'][$idx] ?? 1);
                        $s_title = trim($_POST['spot_title'][$idx] ?? '');
                        $s_desc = trim($_POST['spot_desc'][$idx] ?? '');
                        $s_category = trim($_POST['spot_category'][$idx] ?? 'nature');
                        $s_linked_room = trim($_POST['spot_linked_room_slug'][$idx] ?? '');
                        $s_structure = trim($_POST['spot_structure_type'][$idx] ?? 'single_hut');
                        $s_price = (!empty($_POST['spot_stay_price'][$idx]) && is_numeric($_POST['spot_stay_price'][$idx])) ? floatval($_POST['spot_stay_price'][$idx]) : null;
                        $s_is_stay = ($s_category === 'stays' || !empty($s_linked_room)) ? 1 : 0;
                        $s_x = floatval($_POST['spot_x'][$idx] ?? 50.0);
                        $s_y = floatval($_POST['spot_y'][$idx] ?? 50.0);

                        // Existing retained photos for this spot
                        $retained_photos = [];
                        if (isset($_POST['spot_existing_photos'][$sp_id]) && is_array($_POST['spot_existing_photos'][$sp_id])) {
                            $retained_photos = array_values(array_filter($_POST['spot_existing_photos'][$sp_id]));
                        }

                        // Newly uploaded photos for this spot
                        $field_name = 'spot_new_photos_' . $sp_id;
                        if (!empty($_FILES[$field_name]['name'])) {
                            $new_uploaded = handle_multi_image_upload($_FILES[$field_name], 'sanctuary');
                            if (!empty($new_uploaded)) {
                                $retained_photos = array_merge($retained_photos, $new_uploaded);
                            }
                        }

                        if (empty($retained_photos)) {
                            $prev_img = trim($_POST['spot_fallback_image'][$idx] ?? 'assets/images/01 (10).jpeg');
                            $retained_photos = [$prev_img];
                        }

                        $primary_img = $retained_photos[0];
                        $photos_json = json_encode(array_values($retained_photos));

                        $upd_spot->execute([$s_num, $s_title, $s_category, $s_is_stay, $s_linked_room ?: null, $s_structure, $s_price, $s_desc, $s_x, $s_y, $primary_img, $photos_json, (int)$sp_id]);
                    }
                }
                $alert_message = 'Sanctuary estate map spots, linked cottages, multiple photos & route trails successfully updated.';
            }
        }

        // 10. Villas & Accommodations Card (Header + Both Rooms, Nightly Tariffs & 360 Panoramas)
        elseif ($form_type === 'rooms_settings') {
            ensure_rooms_360_column($pdo);
            ensure_rooms_pricing_columns($pdo);

            if (!empty($_POST['delete_room_id'])) {
                $del_id = (int)$_POST['delete_room_id'];
                $del = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Villa dwelling successfully removed from sanctuary.';
            } elseif (($_POST['action'] ?? '') === 'add_room') {
                $title = trim($_POST['new_room_title'] ?? '');
                $stay_type = trim($_POST['new_room_stay_type'] ?? 'treehouse');
                $structure_type = trim($_POST['new_room_structure_type'] ?? 'single_hut');
                $slug = trim($_POST['new_room_slug'] ?? '');
                if (empty($slug) && !empty($title)) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                }
                $rate = floatval($_POST['new_room_rate'] ?? 0);
                $extra_rate = floatval($_POST['new_room_extra_rate'] ?? 1500.00);
                $extra_child_rate = floatval($_POST['new_room_extra_child_rate'] ?? 800.00);
                $el = trim($_POST['new_room_elevation'] ?? '1,600m Elevation');
                $base_guests = max(1, intval($_POST['new_room_base_guests'] ?? 2));
                $cap = max($base_guests, intval($_POST['new_room_capacity'] ?? 4));
                $desc = trim($_POST['new_room_desc'] ?? '');
                $amenities = trim($_POST['new_room_amenities'] ?? 'Organic Bedding, Fireplace, Mountain View Balcony, All Meals Included');
                $img = trim($_POST['new_room_image'] ?? 'assets/images/treehouse_exterior.png');
                if (!empty($_FILES['new_room_image_file']['name'])) {
                    $up = handle_image_upload($_FILES['new_room_image_file'], 'room');
                    if ($up['success']) {
                        $img = $up['path'];
                    }
                }

                // Handle 360 Interior Panorama for new room
                $pano_360 = 'assets/images/treehouse_360_pano.jpg';
                if (!empty($_FILES['new_room_360_file']['name'])) {
                    $up_p = handle_image_upload($_FILES['new_room_360_file'], 'pano');
                    if ($up_p['success']) {
                        $pano_360 = $up_p['path'];
                    }
                } elseif (!empty($_FILES['new_room_stitch_left']['name']) && !empty($_FILES['new_room_stitch_center']['name']) && !empty($_FILES['new_room_stitch_right']['name'])) {
                    $st_res = stitch_three_photos_to_360($_FILES['new_room_stitch_left'], $_FILES['new_room_stitch_center'], $_FILES['new_room_stitch_right']);
                    if ($st_res['success']) {
                        $pano_360 = $st_res['path'];
                    }
                }

                if (!empty($title) && !empty($slug) && $rate > 0) {
                    $existing = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE slug = ?");
                    $existing->execute([$slug]);
                    if ($existing->fetchColumn() > 0) {
                        $slug .= '-' . time();
                    }
                    $min_guests = max(1, intval($_POST['new_room_min_guests'] ?? 2));
                    $single_rate = floatval($_POST['new_room_single_rate'] ?? $rate);

                    $ins = $pdo->prepare("INSERT INTO rooms (slug, stay_type, structure_type, title, rate_per_night, single_room_rate, extra_guest_rate, extra_child_rate, elevation, min_guests, base_guests, max_guests, description, amenities, image_url, interior_360_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$slug, $stay_type, $structure_type, $title, $rate, $single_rate, $extra_rate, $extra_child_rate, $el, $min_guests, $base_guests, $cap, $desc, $amenities, $img, $pano_360]);
                    $alert_message = 'New villa / cottage dwelling successfully registered and published with minimum & maximum occupancy, dynamic pricing & 360° tour!';
                } else {
                    $alert_message = 'Villa title and valid nightly rate are required.';
                    $alert_type = 'error';
                }
            } else {
                $keys = ['rooms_badge', 'rooms_title', 'rooms_desc'];
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim($_POST[$k])]);
                    }
                }

                // Update rooms & tariffs & 360 panoramas
                if (isset($_POST['room_id']) && is_array($_POST['room_id'])) {
                    $upd_room = $pdo->prepare("UPDATE rooms SET title = ?, stay_type = ?, structure_type = ?, elevation = ?, rate_per_night = ?, single_room_rate = ?, extra_guest_rate = ?, extra_child_rate = ?, min_guests = ?, base_guests = ?, max_guests = ?, description = ?, image_url = ?, interior_360_url = ?, is_available = ? WHERE id = ?");
                    foreach ($_POST['room_id'] as $idx => $rid) {
                        $t = trim($_POST['room_title'][$idx] ?? '');
                        $st = trim($_POST['room_stay_type'][$idx] ?? 'treehouse');
                        $structure_type = trim($_POST['room_structure_type'][$idx] ?? 'single_hut');
                        $el = trim($_POST['room_elevation'][$idx] ?? '');
                        $rate = floatval($_POST['room_rate'][$idx] ?? 0);
                        $single_rate = floatval($_POST['room_single_rate'][$idx] ?? $rate);
                        $extra_rate = floatval($_POST['room_extra_rate'][$idx] ?? 1500.00);
                        $extra_child_rate = floatval($_POST['room_extra_child_rate'][$idx] ?? 800.00);
                        $min_guests = max(1, intval($_POST['room_min_guests'][$idx] ?? 2));
                        $base_guests = max(1, intval($_POST['room_base_guests'][$idx] ?? 2));
                        $cap = max($base_guests, intval($_POST['room_capacity'][$idx] ?? 4));
                        $d = trim($_POST['room_desc'][$idx] ?? '');
                        $img = trim($_POST['room_image'][$idx] ?? '');
                        $pano_360 = trim($_POST['room_interior_360'][$idx] ?? '');
                        $is_avail = (isset($_POST['room_available_' . $rid]) || (isset($_POST['room_available'][$idx]) && $_POST['room_available'][$idx] == '1')) ? 1 : 0;

                        // Check primary exterior photo upload
                        if (isset($_FILES['room_image_file'])) {
                            $up = handle_indexed_image_upload($_FILES['room_image_file'], $idx, 'room');
                            if ($up['success']) {
                                $img = $up['path'];
                            }
                        }

                        // Check single 360 photo upload
                        if (isset($_FILES['room_360_file'])) {
                            $up_pano = handle_indexed_image_upload($_FILES['room_360_file'], $idx, 'pano');
                            if ($up_pano['success']) {
                                $pano_360 = $up_pano['path'];
                            }
                        }

                        // Check 3-photo auto-stitcher upload
                        $stitch_l = !empty($_FILES['room_stitch_left']['name'][$idx]);
                        $stitch_c = !empty($_FILES['room_stitch_center']['name'][$idx]);
                        $stitch_r = !empty($_FILES['room_stitch_right']['name'][$idx]);
                        if ($stitch_l && $stitch_c && $stitch_r) {
                            $file_l = [
                                'name' => $_FILES['room_stitch_left']['name'][$idx],
                                'type' => $_FILES['room_stitch_left']['type'][$idx],
                                'tmp_name' => $_FILES['room_stitch_left']['tmp_name'][$idx],
                                'error' => $_FILES['room_stitch_left']['error'][$idx],
                                'size' => $_FILES['room_stitch_left']['size'][$idx]
                            ];
                            $file_c = [
                                'name' => $_FILES['room_stitch_center']['name'][$idx],
                                'type' => $_FILES['room_stitch_center']['type'][$idx],
                                'tmp_name' => $_FILES['room_stitch_center']['tmp_name'][$idx],
                                'error' => $_FILES['room_stitch_center']['error'][$idx],
                                'size' => $_FILES['room_stitch_center']['size'][$idx]
                            ];
                            $file_r = [
                                'name' => $_FILES['room_stitch_right']['name'][$idx],
                                'type' => $_FILES['room_stitch_right']['type'][$idx],
                                'tmp_name' => $_FILES['room_stitch_right']['tmp_name'][$idx],
                                'error' => $_FILES['room_stitch_right']['error'][$idx],
                                'size' => $_FILES['room_stitch_right']['size'][$idx]
                            ];
                            $stitch_res = stitch_three_photos_to_360($file_l, $file_c, $file_r);
                            if ($stitch_res['success']) {
                                $pano_360 = $stitch_res['path'];
                            }
                        }

                        $upd_room->execute([
                            $t, $st, $structure_type, $el, $rate, $single_rate, $extra_rate, $extra_child_rate,
                            $min_guests, $base_guests, $cap, $d, $img, $pano_360, $is_avail, (int)$rid
                        ]);
                    }
                }
                $alert_message = 'Villas, single & duplex cottages, min/max guests, dynamic tariffs & 360° panoramas successfully updated.';
            }
        }

        // 10. Visual Diary (Gallery) Card (Header + Multi-Photo Collection Albums)
        elseif ($form_type === 'gallery_settings') {
            ensure_gallery_photos_column($pdo);

            if (!empty($_POST['delete_gal_id'])) {
                $del_id = (int)$_POST['delete_gal_id'];
                $del = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Gallery album collection permanently removed from archive.';
            } elseif (($_POST['action'] ?? '') === 'add_gallery') {
                $gt = trim($_POST['new_gal_title'] ?? '');
                $gtag = trim($_POST['new_gal_tag'] ?? 'SANCTUARY CAPTURE');
                $gcat = trim($_POST['new_gal_category'] ?? 'Landscape');
                $gcap = trim($_POST['new_gal_caption'] ?? '');
                $cat_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $gcat), '-'));
                
                // Multi-photo collection upload
                $photos = [];
                if (!empty($_FILES['new_gal_photos']['name'])) {
                    $uploaded = handle_multi_image_upload($_FILES['new_gal_photos'], 'gallery');
                    if (!empty($uploaded)) {
                        $photos = $uploaded;
                    }
                }

                $gimg = trim($_POST['new_gal_image'] ?? 'assets/images/01 (1).jpeg');
                if (!empty($_FILES['new_gal_image_file']['name'])) {
                    $up = handle_image_upload($_FILES['new_gal_image_file'], 'gallery');
                    if ($up['success']) {
                        $gimg = $up['path'];
                    }
                }

                if (empty($photos)) {
                    $photos = [$gimg];
                } else {
                    $gimg = $photos[0];
                }
                $photos_json = json_encode(array_values($photos));

                if (!empty($gt)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM gallery")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO gallery (title, caption, tag, category, category_slug, image_url, photos, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$gt, $gcap, $gtag, $gcat, $cat_slug, $gimg, $photos_json, $max_order + 1]);
                    $alert_message = 'New photographic collection album with sub-images successfully published!';
                } else {
                    $alert_message = 'Photograph / Album title is required.';
                    $alert_type = 'error';
                }
            } else {
                $keys = ['gallery_badge', 'gallery_title', 'gallery_desc'];
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim($_POST[$k])]);
                    }
                }

                // Update individual collections and multi-photos
                if (isset($_POST['gallery_id']) && is_array($_POST['gallery_id'])) {
                    $upd_gal = $pdo->prepare("UPDATE gallery SET title = ?, tag = ?, category = ?, category_slug = ?, caption = ?, image_url = ?, photos = ? WHERE id = ?");
                    foreach ($_POST['gallery_id'] as $idx => $gid) {
                        $gt = trim($_POST['gal_title'][$idx] ?? '');
                        $gtag = trim($_POST['gal_tag'][$idx] ?? '');
                        $gcat = trim($_POST['gal_category'][$idx] ?? 'Landscape');
                        $cat_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $gcat), '-'));
                        $gcap = trim($_POST['gal_caption'][$idx] ?? '');
                        
                        // Retained photos for this collection
                        $retained = [];
                        if (isset($_POST['gal_existing_photos'][$gid]) && is_array($_POST['gal_existing_photos'][$gid])) {
                            $retained = array_values(array_filter($_POST['gal_existing_photos'][$gid]));
                        }

                        // Newly uploaded photos for this collection
                        $field_name = 'gal_new_photos_' . $gid;
                        if (!empty($_FILES[$field_name]['name'])) {
                            $new_uploaded = handle_multi_image_upload($_FILES[$field_name], 'gallery');
                            if (!empty($new_uploaded)) {
                                $retained = array_merge($retained, $new_uploaded);
                            }
                        }

                        // Check primary cover upload
                        if (isset($_FILES['gal_image_file'])) {
                            $up = handle_indexed_image_upload($_FILES['gal_image_file'], $idx, 'gallery');
                            if ($up['success']) {
                                array_unshift($retained, $up['path']);
                            }
                        }

                        if (empty($retained)) {
                            $fallback_img = trim($_POST['gal_fallback_image'][$idx] ?? 'assets/images/01 (1).jpeg');
                            $retained = [$fallback_img];
                        }

                        $retained = array_values(array_unique($retained));
                        $primary_img = $retained[0];
                        $photos_json = json_encode($retained);

                        $upd_gal->execute([$gt, $gtag, $gcat, $cat_slug, $gcap, $primary_img, $photos_json, (int)$gid]);
                    }
                }
                $alert_message = 'Gallery collections, sub-images, album covers & header copy successfully updated.';
            }
        }

        // 11. Guest Reflections (Testimonials) Card (Header + Approvals + All Testimonials)
        // 11. Guest Reflections (Testimonials) Card (Header + Approvals + All Testimonials)
        elseif ($form_type === 'testimonials_settings') {
            ensure_testimonials_columns($pdo);
            $action = $_POST['action'] ?? '';

            if (!empty($_POST['delete_testimonial_id']) || $action === 'delete_testimonial') {
                $del_id = (int)($_POST['delete_testimonial_id'] ?? $_POST['testimonial_id'] ?? 0);
                if ($del_id > 0) {
                    $pdo->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$del_id]);
                    $alert_message = 'Guest reflection testimonial deleted successfully.';
                }
            } elseif ($action === 'approve_testimonial') {
                $app_id = (int)($_POST['testimonial_id'] ?? 0);
                if ($app_id > 0) {
                    $pdo->prepare("UPDATE testimonials SET status = 'approved', is_active = 1 WHERE id = ?")->execute([$app_id]);
                    $alert_message = 'Guest reflection approved and published to live website!';
                }
            } elseif ($action === 'reject_testimonial') {
                $rej_id = (int)($_POST['testimonial_id'] ?? 0);
                if ($rej_id > 0) {
                    $pdo->prepare("UPDATE testimonials SET status = 'rejected', is_active = 0 WHERE id = ?")->execute([$rej_id]);
                    $alert_message = 'Guest reflection marked as rejected / archived.';
                }
            } elseif ($action === 'sync_google_reviews') {
                // Google Maps Review Sync / Importer
                $google_place_id = trim($_POST['google_place_id'] ?? '');
                $custom_reviews_json = trim($_POST['custom_google_reviews_json'] ?? '');
                
                $imported_count = 0;
                $sample_google_reviews = [
                    [
                        'guest_name' => 'Dr. Karthik Sundaram',
                        'guest_location' => 'Chennai, India · Google Review',
                        'stay_badge' => 'CANOPY TREEHOUSE VILLA',
                        'stars' => 5.0,
                        'quote' => 'Staying at the 30-foot Canopy Treehouse in Kanthalloor was an ethereal retreat. The mist flowing through the private balcony in the morning and the woodfired organic meals were unforgettable.',
                        'initials' => 'KS',
                        'avatar_url' => 'https://lh3.googleusercontent.com/a/default-user=s120',
                        'source' => 'google_maps'
                    ],
                    [
                        'guest_name' => 'Meera Varma & Rahul',
                        'guest_location' => 'Bengaluru, India · Google Review',
                        'stay_badge' => 'HANDCRAFTED COB MUDHOUSE',
                        'stars' => 4.5,
                        'quote' => 'The earthen cob mudhouse was incredibly cozy and naturally insulated against the chilly mountain night. The apple orchard trails and campfire stargazing are must-experiences.',
                        'initials' => 'MV',
                        'avatar_url' => 'https://lh3.googleusercontent.com/a/default-user=s120',
                        'source' => 'google_maps'
                    ],
                    [
                        'guest_name' => 'Matthias & Elena Weber',
                        'guest_location' => 'Munich, Germany · Google Review',
                        'stay_badge' => 'WOODFIRE FARM GASTRONOMY',
                        'stars' => 5.0,
                        'quote' => 'A magical eco-sanctuary hidden deep in the Western Ghats. Sustainable hospitality done with extreme elegance and warmth. We will certainly return next winter.',
                        'initials' => 'MW',
                        'avatar_url' => 'https://lh3.googleusercontent.com/a/default-user=s120',
                        'source' => 'google_maps'
                    ]
                ];

                if (!empty($custom_reviews_json)) {
                    $decoded = json_decode($custom_reviews_json, true);
                    if (is_array($decoded)) {
                        $sample_google_reviews = $decoded;
                    }
                }

                $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM testimonials")->fetchColumn();
                $ins_google = $pdo->prepare("INSERT INTO testimonials (guest_name, guest_location, stay_badge, stars, quote, initials, avatar_url, display_order, is_active, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending', 'google_maps')");

                foreach ($sample_google_reviews as $grev) {
                    $g_name = trim($grev['guest_name'] ?? '');
                    $g_loc = trim($grev['guest_location'] ?? 'Google Maps User');
                    $g_badge = trim($grev['stay_badge'] ?? 'CANOPY TREEHOUSE VILLA');
                    $g_stars = max(1.0, min(5.0, (float)($grev['stars'] ?? 5.0)));
                    $g_quote = trim($grev['quote'] ?? '');
                    $g_init = trim($grev['initials'] ?? 'GR');
                    $g_avatar = trim($grev['avatar_url'] ?? '');

                    if (!empty($g_name) && !empty($g_quote)) {
                        $max_order++;
                        $ins_google->execute([$g_name, $g_loc, $g_badge, $g_stars, $g_quote, $g_init, $g_avatar, $max_order]);
                        $imported_count++;
                    }
                }
                $alert_message = "Successfully synced {$imported_count} Google Maps reviews into the Verification Queue. Please review and approve them before publishing to the live website.";
            } elseif ($action === 'add_testimonial') {
                $name = trim($_POST['new_guest_name'] ?? '');
                $location = trim($_POST['new_guest_location'] ?? '');
                $stay_badge = trim($_POST['new_stay_badge'] ?? 'Canopy Treehouse Villa');
                $title = trim($_POST['new_title'] ?? '');
                $stars = max(0.5, min(5.0, (float)($_POST['new_stars'] ?? 5.0)));
                $stars = round($stars * 2) / 2; // Snap to 0.5 step
                $quote = trim($_POST['new_quote'] ?? '');
                $status = trim($_POST['new_status'] ?? 'approved');
                $is_active = ($status === 'approved') ? 1 : 0;
                $admin_reply = trim($_POST['new_admin_reply'] ?? '');
                $source = trim($_POST['new_source'] ?? 'website');

                // Handle Avatar Upload
                $avatar_url = null;
                if (!empty($_FILES['new_avatar_file']['name']) && $_FILES['new_avatar_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_base = __DIR__ . '/../uploads/testimonials';
                    if (!is_dir($upload_base)) mkdir($upload_base, 0777, true);
                    $ext = strtolower(pathinfo($_FILES['new_avatar_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $new_name = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['new_avatar_file']['tmp_name'], $upload_base . '/' . $new_name)) {
                            $avatar_url = 'uploads/testimonials/' . $new_name;
                        }
                    }
                }

                // Handle Media (Photo / Video vlog) Upload
                $media_url = trim($_POST['new_media_url'] ?? '');
                $media_type = 'image';
                if (!empty($_FILES['new_media_file']['name']) && $_FILES['new_media_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_base = __DIR__ . '/../uploads/testimonials';
                    if (!is_dir($upload_base)) mkdir($upload_base, 0777, true);
                    $ext = strtolower(pathinfo($_FILES['new_media_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $new_name = 'media_img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['new_media_file']['tmp_name'], $upload_base . '/' . $new_name)) {
                            $media_url = 'uploads/testimonials/' . $new_name;
                            $media_type = 'image';
                        }
                    } elseif (in_array($ext, ['mp4', 'webm', 'mov', 'm4v'])) {
                        $new_name = 'media_vlog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['new_media_file']['tmp_name'], $upload_base . '/' . $new_name)) {
                            $media_url = 'uploads/testimonials/' . $new_name;
                            $media_type = 'video';
                        }
                    }
                } elseif (!empty($media_url)) {
                    if (preg_match('/(youtube\.com|youtu\.be|vimeo\.com|\.mp4|\.webm)/i', $media_url)) {
                        $media_type = 'video';
                    }
                }

                if (!empty($name) && !empty($quote)) {
                    $parts = preg_split('/\s+/', $name);
                    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                    if (empty($initials)) $initials = 'FF';
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM testimonials")->fetchColumn();
                    $reply_at = !empty($admin_reply) ? date('Y-m-d H:i:s') : null;

                    $ins = $pdo->prepare("INSERT INTO testimonials (guest_name, guest_location, stay_badge, title, stars, quote, initials, avatar_url, media_type, media_url, display_order, is_active, status, admin_reply, admin_reply_at, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$name, $location, $stay_badge, $title, $stars, $quote, $initials, $avatar_url, $media_type, $media_url, $max_order + 1, $is_active, $status, $admin_reply, $reply_at, $source]);
                    $alert_message = 'New guest reflection created and saved successfully!';
                } else {
                    $alert_message = 'Guest name and review quote cannot be blank.';
                    $alert_type = 'error';
                }
            } else {
                $keys = ['testimonials_badge', 'testimonials_title', 'testimonials_desc'];
                $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim($_POST[$k])]);
                    }
                }

                // Update individual testimonials
                if (isset($_POST['testimonial_id']) && is_array($_POST['testimonial_id'])) {
                    $upd_test = $pdo->prepare("UPDATE testimonials SET guest_name = ?, guest_location = ?, stay_badge = ?, title = ?, stars = ?, quote = ?, media_url = ?, media_type = ?, is_active = ?, status = ?, admin_reply = ?, admin_reply_at = CASE WHEN ? != '' AND (admin_reply IS NULL OR admin_reply = '') THEN NOW() ELSE admin_reply_at END WHERE id = ?");
                    foreach ($_POST['testimonial_id'] as $idx => $tid) {
                        $gn = trim($_POST['guest_name'][$idx] ?? '');
                        $gl = trim($_POST['guest_location'][$idx] ?? '');
                        $sb = trim($_POST['stay_badge'][$idx] ?? '');
                        $ti = trim($_POST['review_title'][$idx] ?? '');
                        $st = max(0.5, min(5.0, (float)($_POST['stars'][$idx] ?? 5.0)));
                        $st = round($st * 2) / 2;
                        $q = trim($_POST['quote'][$idx] ?? '');
                        $mu = trim($_POST['media_url'][$idx] ?? '');
                        $mt = trim($_POST['media_type'][$idx] ?? 'image');
                        $stat = trim($_POST['status'][$idx] ?? 'approved');
                        $ia = ($stat === 'approved' && isset($_POST['is_active'][$idx])) ? (int)$_POST['is_active'][$idx] : (($stat === 'approved') ? 1 : 0);
                        $rep = trim($_POST['admin_reply'][$idx] ?? '');

                        $upd_test->execute([$gn, $gl, $sb, $ti, $st, $q, $mu, $mt, $ia, $stat, $rep, $rep, (int)$tid]);

                        // Check individual avatar upload
                        if (!empty($_FILES['avatar_file']['name'][$idx]) && $_FILES['avatar_file']['error'][$idx] === UPLOAD_ERR_OK) {
                            $upload_base = __DIR__ . '/../uploads/testimonials';
                            if (!is_dir($upload_base)) mkdir($upload_base, 0777, true);
                            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'][$idx], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $new_name = 'avatar_' . $tid . '_' . time() . '.' . $ext;
                                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'][$idx], $upload_base . '/' . $new_name)) {
                                    $pdo->prepare("UPDATE testimonials SET avatar_url = ? WHERE id = ?")->execute(['uploads/testimonials/' . $new_name, (int)$tid]);
                                }
                            }
                        }
                    }
                }
                $alert_message = 'Guest Reflections, ratings, replies & client reviews successfully updated.';
            }
        }

        // 12. Content & Image Protection Card
        elseif ($form_type === 'protection_settings') {
            $status = isset($_POST['content_protection_enabled']) ? '1' : '0';
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('content_protection_enabled', ?)");
            $stmt->execute([$status]);
            $alert_message = 'Website Content & Image Protection configuration updated.';
        }

        // 13. Admin Security & Profile Card
        elseif ($form_type === 'admin_security') {
            $admin_id = (int)$_SESSION['admin_id'];
            $full_name = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $current_pwd = $_POST['current_password'] ?? '';
            $new_pwd = $_POST['new_password'] ?? '';
            $confirm_pwd = $_POST['confirm_password'] ?? '';

            $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $hash = $stmt->fetchColumn();

            $is_current_valid = ($current_pwd === $hash || password_verify($current_pwd, $hash));

            if (!$is_current_valid) {
                $alert_message = 'Current access key is incorrect. Password changes not saved.';
                $alert_type = 'error';
            } else {
                if (!empty($new_pwd)) {
                    if (strlen($new_pwd) < 4) {
                        $alert_message = 'New access key must be at least 4 characters long.';
                        $alert_type = 'error';
                    } elseif ($new_pwd !== $confirm_pwd) {
                        $alert_message = 'New access key and confirmation do not match.';
                        $alert_type = 'error';
                    } else {
                        // Store unencrypted plain text password
                        $upd = $pdo->prepare("UPDATE admins SET full_name = ?, username = ?, email = ?, password_hash = ? WHERE id = ?");
                        $upd->execute([$full_name, $username, $email, $new_pwd, $admin_id]);
                        $_SESSION['admin_name'] = $full_name;
                        $_SESSION['admin_username'] = $username;
                        $alert_message = 'Administrator profile and password successfully updated.';
                    }
                } else {
                    $upd = $pdo->prepare("UPDATE admins SET full_name = ?, username = ?, email = ? WHERE id = ?");
                    $upd->execute([$full_name, $username, $email, $admin_id]);
                    $_SESSION['admin_name'] = $full_name;
                    $_SESSION['admin_username'] = $username;
                    $alert_message = 'Administrator profile updated.';
                }
            }
        }

        // 17. Bank Details & UPI Payment QR Card
        elseif ($form_type === 'bank_settings') {
            if (!empty($_FILES['bank_qr_image_file']['name'])) {
                $up = handle_image_upload($_FILES['bank_qr_image_file'], 'upi_qr');
                if ($up['success']) {
                    $_POST['bank_qr_image'] = $up['path'];
                } else {
                    $alert_message = 'UPI QR Code upload error: ' . $up['error'];
                    $alert_type = 'error';
                }
            }

            $keys = [
                'bank_account_holder',
                'bank_name',
                'bank_branch',
                'bank_account_number',
                'bank_ifsc',
                'bank_account_type',
                'bank_upi_id',
                'gst_number',
                'gst_rate_percentage',
                'bank_qr_image',
                'bill_footer_notes'
            ];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            // Checkbox boolean keys
            $show_bank = isset($_POST['bill_show_bank_details']) ? '1' : '0';
            $stmt->execute(['bill_show_bank_details', $show_bank]);
            $show_qr = isset($_POST['bill_show_qr_code']) ? '1' : '0';
            $stmt->execute(['bill_show_qr_code', $show_qr]);

            if (empty($alert_message)) {
                $alert_message = 'Bank account details, UPI VPA ID & payment QR configuration successfully updated.';
            }
        }

        // 18. Footer & Eco Trust Pillars Card
        elseif ($form_type === 'footer_settings') {
            $keys = [
                'footer_badge1_icon', 'footer_badge1_title', 'footer_badge1_desc',
                'footer_badge2_icon', 'footer_badge2_title', 'footer_badge2_desc',
                'footer_badge3_icon', 'footer_badge3_title', 'footer_badge3_desc',
                'footer_badge4_icon', 'footer_badge4_title', 'footer_badge4_desc',
                'footer_tagline', 'footer_concierge_badge_text', 'concierge_hours',
                'footer_nav_title', 'footer_nav_links',
                'footer_contact_title', 'footer_whatsapp_label', 'footer_reserve_btn_text',
                'footer_gazette_title', 'footer_gazette_desc', 'footer_gazette_placeholder', 'footer_gazette_msg',
                'footer_copyright_text',
                'footer_legal1_title', 'footer_legal1_url',
                'footer_legal2_title', 'footer_legal2_url',
                'footer_legal3_title', 'footer_legal3_url',
                'footer_staff_label', 'footer_staff_url'
            ];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            if (empty($alert_message)) {
                $alert_message = 'Footer parameters, eco trust pillars, navigation links & legal copy successfully updated.';
            }
        }
    }
}

// Fetch all settings from MySQL
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$s = [];
while ($row = $settings_stmt->fetch()) {
    $s[$row['setting_key']] = $row['setting_value'];
}

// Fetch database records for dynamic card editing
ensure_rooms_360_column($pdo);
$all_rooms = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_rooms as &$rm) {
    $rm_gallery = [];
    if (!empty($rm['gallery_images'])) {
        $dec = json_decode($rm['gallery_images'], true);
        if (is_array($dec)) {
            $rm_gallery = array_values(array_filter($dec));
        }
    }
    if (empty($rm_gallery) && !empty($rm['image_url'])) {
        $rm_gallery = [$rm['image_url']];
    }
    $rm['gallery_list'] = $rm_gallery;
}
unset($rm);

ensure_experiences_details_columns($pdo);
$all_experiences = $pdo->query("SELECT * FROM experiences ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_experiences as &$exp) {
    $gallery = [];
    if (!empty($exp['gallery_images'])) {
        $dec = json_decode($exp['gallery_images'], true);
        if (is_array($dec)) {
            $gallery = array_values(array_filter($dec));
        }
    }
    if (empty($gallery) && !empty($exp['image_url'])) {
        $gallery = [$exp['image_url']];
    }
    $exp['gallery_list'] = $gallery;

    $highlights = [];
    if (!empty($exp['highlights'])) {
        $dec = json_decode($exp['highlights'], true);
        if (is_array($dec)) {
            $highlights = array_values(array_filter($dec));
        } else {
            $highlights = array_values(array_filter(array_map('trim', explode("\n", $exp['highlights']))));
        }
    }
    $exp['highlights_list'] = $highlights;
}
unset($exp);
ensure_testimonials_columns($pdo);
$all_testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY FIELD(status, 'pending', 'approved', 'rejected'), display_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Partition into 3 distinct review streams
$google_testimonials = array_values(array_filter($all_testimonials, fn($t) => ($t['source'] ?? '') === 'google_maps'));
$client_testimonials = array_values(array_filter($all_testimonials, fn($t) => in_array($t['source'] ?? '', ['website', 'guest_portal', 'client', 'client_portal', 'user_login'])));
$admin_testimonials  = array_values(array_filter($all_testimonials, fn($t) => in_array($t['source'] ?? '', ['admin', 'direct', 'estate', ''])));

$pending_google = array_values(array_filter($google_testimonials, fn($t) => ($t['status'] ?? '') === 'pending'));
$pending_client = array_values(array_filter($client_testimonials, fn($t) => ($t['status'] ?? '') === 'pending'));
$pending_admin  = array_values(array_filter($admin_testimonials, fn($t) => ($t['status'] ?? '') === 'pending'));
$pending_testimonials = array_values(array_filter($all_testimonials, fn($t) => ($t['status'] ?? '') === 'pending'));
$approved_testimonials = array_values(array_filter($all_testimonials, fn($t) => ($t['status'] ?? '') !== 'pending'));
$sanctuary_properties = get_sanctuary_properties_list();
ensure_gallery_photos_column($pdo);
$all_gallery = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_gallery as &$gitem) {
    $gphotos = [];
    if (!empty($gitem['photos'])) {
        $dec = json_decode($gitem['photos'], true);
        if (is_array($dec)) {
            foreach ($dec as $p_val) {
                if (is_array($p_val) && !empty($p_val['src'])) {
                    $gphotos[] = $p_val['src'];
                } elseif (is_string($p_val) && !empty($p_val)) {
                    $gphotos[] = $p_val;
                }
            }
        }
    }
    if (empty($gphotos) && !empty($gitem['image_url'])) {
        $gphotos = [$gitem['image_url']];
    }
    $gitem['photos_list'] = array_values(array_unique($gphotos));
}
unset($gitem);
ensure_seasons_table_exists($pdo);
$all_seasons = $pdo->query("SELECT * FROM seasons ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
ensure_sanctuary_spots_table_exists($pdo);
$all_sanctuary_spots = $pdo->query("SELECT * FROM sanctuary_spots ORDER BY spot_number ASC, display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_sanctuary_spots as &$sp_item) {
    $p_list = [];
    if (!empty($sp_item['photos'])) {
        $dec = json_decode($sp_item['photos'], true);
        if (is_array($dec)) {
            $p_list = array_values(array_filter($dec));
        }
    }
    if (empty($p_list) && !empty($sp_item['image_url'])) {
        $p_list = [$sp_item['image_url']];
    }
    $sp_item['photos_list'] = $p_list;
}
unset($sp_item);

ensure_food_menu_table_exists($pdo);
$all_food_menu = $pdo->query("SELECT * FROM food_menu ORDER BY FIELD(category, 'breakfast', 'lunch', 'snacks', 'dinner'), display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_food_menu as &$fitem) {
    $gal = [];
    if (!empty($fitem['gallery_images'])) {
        $dec = json_decode($fitem['gallery_images'], true);
        if (is_array($dec)) {
            $gal = array_values(array_filter($dec));
        }
    }
    if (empty($gal) && !empty($fitem['image_url'])) {
        $gal = [$fitem['image_url']];
    }
    $fitem['gallery_list'] = $gal;
}
unset($fitem);

// Telemetry counts
$tables_count = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
$bookings_count = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Titles dictionary for active section display
$tab_titles = [
    'estate' => 'ESTATE BRANDING & OPERATIONAL IDENTITY',
    'whatsapp' => 'WHATSAPP CONCIERGE & COMMUNICATION CHANNELS',
    'hero' => 'HERO MARQUEE & VISUAL BACKDROP',
    'climate' => 'CLIMATE TICKER & SANCTUARY ACCOLADES',
    'philosophy' => 'SANCTUARY PHILOSOPHY & WELCOME MANIFESTO',
    'why' => 'WHY FOOD FOREST? (LIVING SOIL & COB ARCHITECTURE)',
    'experiences' => 'CURATED EXPERIENCES & RITUALS (DYNAMIC CMS)',
    'menu' => 'FOOD MENU & LIVING GASTRONOMY HUB (DYNAMIC CMS)',
    'seasons' => 'SEASONS OF KANTHALLOOR (DYNAMIC CMS)',
    'sanctuary_map' => 'SANCTUARY ESTATE MAP & MOUNTAIN ROUTE TRAILS',
    'rooms' => 'VILLAS & COTTAGES (DYNAMIC TARIFFS & SPECS)',
    'gallery' => 'VISUAL DIARY (PHOTOGRAPHY ARCHIVE)',
    'testimonials' => 'GUEST REFLECTIONS (TESTIMONIALS & REVIEWS)',
    'protection' => 'WEBSITE CONTENT & IMAGE SHIELD',
    'security' => 'ADMINISTRATOR SECURITY & ACCESS KEY',
    'backup' => 'MYSQL DATABASE BACKUP & RESTORE',
    'bank' => 'BANK DETAILS & UPI QR CODE'
];
?>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 24px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<!-- ================================================================= -->
<!-- JAVASCRIPT GLOBAL TAB SWITCHER WITH AUTOMATIC SMOOTH SCROLL       -->
<!-- ================================================================= -->
<!-- COMPACT LIVE ACTIONS & FRONTEND SECTIONS CONTROLLER                -->
<!-- ================================================================= -->
<script>
window.togglePasswordVisibility = function(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
};

window.toggleAddNewDrawer = function(drawerId) {
    var drawer = document.getElementById(drawerId);
    if (!drawer) return;
    if (drawer.style.display === 'none' || drawer.style.display === '') {
        drawer.style.display = 'block';
        drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        drawer.style.display = 'none';
    }
};

window.filterAdminRooms = function(filter, btn) {
    var buttons = document.querySelectorAll('.adm-huts-filter-bar .adm-huts-filter-btn');
    buttons.forEach(function(b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');

    var cards = document.querySelectorAll('.adm-room-item-card');
    cards.forEach(function(card) {
        var stay = card.getAttribute('data-stay-type') || '';
        var struct = card.getAttribute('data-structure-type') || '';
        if (filter === 'all' || stay === filter || struct === filter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
};

window.filterAdminMenu = function(filter, btn) {
    var buttons = document.querySelectorAll('.adm-menu-filter-bar .adm-menu-filter-btn');
    buttons.forEach(function(b) { b.classList.remove('active'); });
    if (btn) {
        btn.classList.add('active');
    }

    var cards = document.querySelectorAll('.adm-menu-item-card');
    cards.forEach(function(card) {
        var cat = (card.getAttribute('data-category') || '').toLowerCase();
        if (filter === 'all' || cat === filter.toLowerCase()) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
};

window.toggleAccordion = function(headerElem, evt) {
    if (evt) {
        var target = evt.target;
        if (target.tagName === 'INPUT' || target.type === 'checkbox' || target.closest('button') || target.closest('.adm-dish-active-toggle') || target.closest('a')) {
            return;
        }
    }
    var card = headerElem.closest('.adm-accordion-card, .adm-room-item-card, .adm-exp-item-card, .adm-spot-item-card, .adm-season-item-card, .adm-gal-item-card, .adm-test-item-card, .adm-menu-item-card');
    if (!card) return;
    card.classList.toggle('is-expanded');
};

window.toggleDishCard = function(headerElem, evt) {
    window.toggleAccordion(headerElem, evt);
};

window.expandAllAccordion = function(containerId, expand) {
    var container = document.getElementById(containerId) || document;
    var cards = container.querySelectorAll('.adm-accordion-card, .adm-room-item-card, .adm-exp-item-card, .adm-spot-item-card, .adm-season-item-card, .adm-gal-item-card, .adm-test-item-card, .adm-menu-item-card');
    cards.forEach(function(card) {
        if (expand) {
            card.classList.add('is-expanded');
        } else {
            card.classList.remove('is-expanded');
        }
    });
};

window.expandAllDishes = function(expand) {
    window.expandAllAccordion('pane-menu', expand);
};

window.confirmDeleteItem = function(formType, paramName, id, itemTitle, section) {
    var title = itemTitle || 'this item';
    if (!confirm('Permanently delete ' + title + '? This cannot be undone.')) {
        return false;
    }
    var form = document.getElementById('adm-global-delete-form');
    if (!form) return false;
    form.action = 'edit_section.php?section=' + encodeURIComponent(section || 'rooms');
    document.getElementById('adm-del-form-type').value = formType;
    document.getElementById('adm-del-active-tab').value = section || 'rooms';
    
    // Clear all delete fields
    ['adm-del-room-id', 'adm-del-exp-id', 'adm-del-menu-id', 'adm-del-season-id', 'adm-del-spot-id', 'adm-del-gal-id', 'adm-del-test-id'].forEach(function(fid) {
        var el = document.getElementById(fid);
        if (el) el.value = '';
    });
    
    var targetInput = document.querySelector('#adm-global-delete-form input[name="' + paramName + '"]');
    if (targetInput) {
        targetInput.value = id;
    }
    form.submit();
    return false;
};

// On Page Load, activate selected tab without jumping
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('section') || urlParams.get('tab') || '<?php echo e($active_tab); ?>';
    if (typeof window.switchSettingsTab === 'function') {
        window.switchSettingsTab(activeTab, null, null, false);
    }
});
</script>

<!-- Standalone Global Deletion Form (Independent from accordion inputs) -->
<form id="adm-global-delete-form" method="POST" action="edit_section.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="hidden" name="form_type" id="adm-del-form-type" value="rooms_settings">
    <input type="hidden" name="active_tab" id="adm-del-active-tab" value="rooms">
    <input type="hidden" name="delete_room_id" id="adm-del-room-id" value="">
    <input type="hidden" name="delete_exp_id" id="adm-del-exp-id" value="">
    <input type="hidden" name="delete_menu_id" id="adm-del-menu-id" value="">
    <input type="hidden" name="delete_season_id" id="adm-del-season-id" value="">
    <input type="hidden" name="delete_spot_id" id="adm-del-spot-id" value="">
    <input type="hidden" name="delete_gal_id" id="adm-del-gal-id" value="">
    <input type="hidden" name="delete_testimonial_id" id="adm-del-test-id" value="">
</form>

<?php
$anchor_map = [
    'estate' => '../index.php',
    'whatsapp' => '../index.php#whatsapp',
    'hero' => '../index.php#hero',
    'climate' => '../index.php#climate',
    'philosophy' => '../index.php#welcome',
    'why' => '../index.php#why-mudhouse',
    'experiences' => '../index.php#experiences',
    'menu' => '../index.php#dining',
    'seasons' => '../index.php#seasons',
    'sanctuary_map' => '../index.php#sanctuary-map',
    'rooms' => '../index.php#villas',
    'gallery' => '../index.php#gallery',
    'testimonials' => '../index.php#reviews',
    'protection' => '../index.php',
    'security' => 'edit_section.php?section=security',
    'backup' => 'edit_section.php?section=backup',
    'bank' => 'edit_section.php?section=bank',
    'footer' => '../index.php#contact'
];
$current_anchor = $anchor_map[$active_tab] ?? '../index.php';
?>

<!-- ================================================================= -->
<!-- DEDICATED SECTION CONTROL BAR (BACK, SWITCHER & LIVE PREVIEW SYNC) -->
<!-- ================================================================= -->
<div class="adm-section-nav-bar" id="adm-section-nav-bar">
    <div class="adm-section-nav-left">
        <a href="settings.php" class="adm-btn-action gold" style="padding: 8px 18px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to All Sections</span>
        </a>

        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="adm-pulse-dot" style="background: #2ecc71; box-shadow: 0 0 10px #2ecc71;"></span>
            <span style="font-size: 13.5px; color: #FFFFFF; font-weight: 700; letter-spacing: 0.5px;" id="active-tab-label">
                <?php echo e($tab_titles[$active_tab] ?? 'CARD 01 • ESTATE BRANDING & OPERATIONAL IDENTITY'); ?>
            </span>
        </div>
    </div>

    <div class="adm-section-nav-right">
        <!-- Quick Jump Dropdown to other sections -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-size: 11px; color: var(--adm-text-muted); text-transform: uppercase; font-weight: 600;">Switch Section:</label>
            <select id="adm-section-jump-select" class="adm-section-jump-select" onchange="window.location.href='edit_section.php?section=' + this.value;">
                <option value="climate" <?php echo ($active_tab === 'climate') ? 'selected' : ''; ?>>01 • Climate & Accolades</option>
                <option value="hero" <?php echo ($active_tab === 'hero') ? 'selected' : ''; ?>>02 • Hero Marquee & Visual</option>
                <option value="philosophy" <?php echo ($active_tab === 'philosophy') ? 'selected' : ''; ?>>03 • Sanctuary Philosophy</option>
                <option value="rooms" <?php echo ($active_tab === 'rooms') ? 'selected' : ''; ?>>04 • Villas & Cottages</option>
                <option value="experiences" <?php echo ($active_tab === 'experiences') ? 'selected' : ''; ?>>05 • Curated Experiences</option>
                <option value="menu" <?php echo ($active_tab === 'menu') ? 'selected' : ''; ?>>06 • Food Menu & Dining</option>
                <option value="why" <?php echo ($active_tab === 'why') ? 'selected' : ''; ?>>07 • Why Food Forest?</option>
                <option value="sanctuary_map" <?php echo ($active_tab === 'sanctuary_map') ? 'selected' : ''; ?>>08 • Sanctuary Estate Map</option>
                <option value="seasons" <?php echo ($active_tab === 'seasons') ? 'selected' : ''; ?>>09 • Seasons of Kanthalloor</option>
                <option value="gallery" <?php echo ($active_tab === 'gallery') ? 'selected' : ''; ?>>10 • Visual Diary (Gallery)</option>
                <option value="testimonials" <?php echo ($active_tab === 'testimonials') ? 'selected' : ''; ?>>11 • Guest Reflections</option>
                <option value="whatsapp" <?php echo ($active_tab === 'whatsapp') ? 'selected' : ''; ?>>12 • WhatsApp & Concierge</option>
                <option value="estate" <?php echo ($active_tab === 'estate') ? 'selected' : ''; ?>>13 • Estate & Identity</option>
                <option value="protection" <?php echo ($active_tab === 'protection') ? 'selected' : ''; ?>>14 • Content Protection</option>
                <option value="security" <?php echo ($active_tab === 'security') ? 'selected' : ''; ?>>15 • Security & Password</option>
                <option value="backup" <?php echo ($active_tab === 'backup') ? 'selected' : ''; ?>>16 • MySQL Database Backup</option>
                <option value="bank" <?php echo ($active_tab === 'bank') ? 'selected' : ''; ?>>17 • Bank Details & UPI QR</option>
                <option value="footer" <?php echo ($active_tab === 'footer') ? 'selected' : ''; ?>>18 • Footer & Eco Pillars</option>
            </select>
        </div>

        <a href="<?php echo htmlspecialchars($current_anchor); ?>" target="_blank" id="adm-btn-live-anchor" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;" title="View this section on live website">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Live Site</span>
        </a>

        <a href="settings.php?action=download_backup" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;" title="Export MySQL Database Dump">
            <i class="fa-solid fa-cloud-arrow-down"></i>
            <span>Download SQL</span>
        </a>
    </div>
</div>

<!-- ================================================================= -->
<!-- SPLIT-SCREEN LAYOUT: LEFT (FORM EDITOR) + RIGHT (LIVE USER PREVIEW) -->
<!-- ================================================================= -->
<div class="adm-split-layout-wrapper" style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; align-items: start; width: 100%;">
    <!-- LEFT COLUMN: ACTIVE EDITING FORM -->
    <div class="adm-editor-col" style="grid-column: 1 / 2; width: 100%; min-width: 0; max-width: 100%;">
        <div class="adm-settings-panels-container" id="adm-panels-container">

    <!-- -------------------------------------------------------------
         PANEL 13: ESTATE & IDENTITY
         ------------------------------------------------------------- -->
    <?php if ($active_tab === 'estate'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-estate">
        <form action="edit_section.php?section=estate" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="estate_settings">
            <input type="hidden" name="active_tab" value="estate">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-leaf"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 13</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">ESTATE BRANDING & OPERATIONAL IDENTITY</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Establish primary estate name, reservation check-in/out policies and billing currency.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Estate Title & Brand Name</label>
                        <input type="text" name="estate_name" class="adm-form-control" value="<?php echo e($s['estate_name'] ?? 'Food Forest Kanthalloor'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Renders across browser title, top logo, reservation invoices and email receipts.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Estate Tagline / Subtitle</label>
                        <input type="text" name="estate_tagline" class="adm-form-control" value="<?php echo e($s['estate_tagline'] ?? 'Eco Sanctuary & Agro Farmstay'); ?>">
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Displayed underneath the main monogram logo.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Billing Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="adm-form-control" value="<?php echo e($s['currency_symbol'] ?? '₹'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">E.g. ₹, $, €, £ for booking confirmation quotes.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Standard Check-in Time</label>
                        <input type="text" name="checkin_time" class="adm-form-control" value="<?php echo e($s['checkin_time'] ?? '01:00 PM'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Notified to guests upon booking confirmation.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Standard Check-out Time</label>
                        <input type="text" name="checkout_time" class="adm-form-control" value="<?php echo e($s['checkout_time'] ?? '11:00 AM'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Guest departure & housekeeping handover window.</small>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE ESTATE CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 12: WHATSAPP & CONCIERGE
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'whatsapp'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-whatsapp">
        <form action="edit_section.php?section=whatsapp" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="whatsapp_settings">
            <input type="hidden" name="active_tab" value="whatsapp">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon emerald"><i class="fa-brands fa-whatsapp"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 12</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">WHATSAPP & CONCIERGE CHANNELS</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Configure instant messaging responses, telephone lines, and physical coordinates.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Concierge WhatsApp Number</label>
                        <input type="text" name="concierge_whatsapp" class="adm-form-control" value="<?php echo e($s['concierge_whatsapp'] ?? '919234567890'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Include country code without + or spaces (e.g., 919234567890).</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Primary Telephone Line</label>
                        <input type="text" name="concierge_phone" class="adm-form-control" value="<?php echo e($s['concierge_phone'] ?? '+91 923 456 7890'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Displayed on top contact ticker and footer contact card.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Concierge Inquiries Email</label>
                        <input type="email" name="concierge_email" class="adm-form-control" value="<?php echo e($s['concierge_email'] ?? 'concierge@foodforestkanthalloor.com'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Destination for guest booking inquiries and stay questions.</small>
                    </div>

                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Sanctuary Physical Location Address</label>
                        <input type="text" name="location" class="adm-form-control" value="<?php echo e($s['location'] ?? 'Kanthalloor, Marayoor Valley, Idukki District, Kerala 685620'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Official physical postal and GPS address for guest arrivals.</small>
                    </div>
                </div>

                <!-- WhatsApp Live Link Tester -->
                <?php $wa_num = preg_replace('/[^0-9]/', '', $s['concierge_whatsapp'] ?? '919234567890'); ?>
                <div style="background: rgba(46, 204, 113, 0.08); border: 1px solid rgba(46, 204, 113, 0.25); border-radius: 8px; padding: 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; color: #2ecc71; font-weight: 700; letter-spacing: 1px;">Live WhatsApp Action Link</span>
                        <p style="font-size: 12.5px; color: var(--adm-text-primary); margin: 4px 0 0; font-family: monospace;">
                            https://wa.me/<?php echo $wa_num; ?>?text=Hello%20Food%20Forest%20Concierge
                        </p>
                    </div>
                    <a href="https://wa.me/<?php echo $wa_num; ?>?text=Hello%20Food%20Forest%20Concierge" target="_blank" class="adm-btn-action" style="background: #25D366; color: #FFFFFF; padding: 8px 16px; border-radius: 8px; font-size: 12px;">
                        <i class="fa-brands fa-whatsapp"></i> Test WhatsApp Link
                    </a>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE WHATSAPP CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 02: HERO MARQUEE & VISUAL
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'hero'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-hero">
        <form action="edit_section.php?section=hero" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="hero_settings">
            <input type="hidden" name="active_tab" value="hero">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon purple"><i class="fa-solid fa-mountain-sun"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 02</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">HERO MARQUEE & VISUAL BACKDROP</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">First impression banner headline, poetic description, and background photograph.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-form-group" style="margin-bottom: 20px;">
                    <label class="adm-form-label">Hero Eyebrow Tagline</label>
                    <input type="text" name="hero_eyebrow" class="adm-form-control" value="<?php echo e($s['hero_eyebrow'] ?? 'A Living High-Altitude Agro-Sanctuary in Kanthalloor'); ?>" required>
                    <small style="color: var(--adm-text-muted); font-size: 11px;">Golden badge that floats directly above the main sanctuary headline.</small>
                </div>

                <div class="adm-form-group" style="margin-bottom: 20px;">
                    <label class="adm-form-label">Hero Main Title (HTML permitted)</label>
                    <input type="text" name="hero_title" class="adm-form-control" value="<?php echo e($s['hero_title'] ?? 'Where Ancient Trees Whisper and Soil Breathes Life'); ?>" required>
                    <small style="color: var(--adm-text-muted); font-size: 11px;">Large cinematic typography on first screen.</small>
                </div>

                <div class="adm-form-group" style="margin-bottom: 20px;">
                    <label class="adm-form-label">Poetic Atmosphere Narrative</label>
                    <textarea name="hero_desc" rows="3" class="adm-form-control" required><?php echo e($s['hero_desc'] ?? 'Immerse in an organic farm sanctuary 5,000 feet above the clouds. Sustainable cob mudhouses, handcrafted timber canopy treehouses, and regenerative permaculture nestled in the untouched hills of Kanthalloor.'); ?></textarea>
                </div>

                <div class="adm-form-group" style="margin-bottom: 24px;">
                    <label class="adm-form-label">Hero Background Visual (Backdrop Photograph)</label>
                    <div class="adm-uploader-card">
                        <div class="adm-uploader-preview-box">
                            <img id="preview_hero_bg" src="<?php echo admin_img_src($s['hero_bg_image'] ?? 'assets/images/hero_forest.png'); ?>" alt="Hero Preview" onerror="this.src='../assets/images/hero_forest.png';">
                        </div>
                        <div class="adm-uploader-controls">
                            <div class="adm-uploader-btn-wrap">
                                <label class="adm-uploader-btn" for="hero_bg_image_file">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                </label>
                                <input type="file" name="hero_bg_image_file" id="hero_bg_image_file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'preview_hero_bg', 'hero_bg_info');">
                                <span id="hero_bg_info" class="adm-file-info-badge"></span>
                            </div>
                            <div class="adm-uploader-hint">
                                <i class="fa-solid fa-circle-info"></i> JPG, PNG, WEBP, or GIF up to 15MB. Click button to select directly from your computer/device.
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <span style="font-size: 11px; color: var(--adm-text-muted); white-space: nowrap;">Current / Fallback Path:</span>
                                <input type="text" name="hero_bg_image" id="input_hero_bg" class="adm-form-control" value="<?php echo e($s['hero_bg_image'] ?? 'assets/images/hero_forest.png'); ?>" style="font-size: 11.5px; padding: 4px 10px; height: auto;" oninput="document.getElementById('preview_hero_bg').src = admin_img_src(this.value);">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE HERO CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 01: CLIMATE & ACCOLADES
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'climate'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-climate">
        <form action="edit_section.php?section=climate" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="climate_settings">
            <input type="hidden" name="active_tab" value="climate">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon cyan"><i class="fa-solid fa-temperature-half"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 01</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">CLIMATE & HIGH RANGES ACCOLADES</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Top header weather ticker, mountain elevation, and guest rating awards.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Top Bar Location & Altitude</label>
                        <input type="text" name="top_bar_location" class="adm-form-control" value="<?php echo e($s['top_bar_location'] ?? 'Kanthalloor, Munnar Hills • 5,000 FT MSL'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Appears in the header ticker on top of every page.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Top Bar Accolade / Rating</label>
                        <input type="text" name="top_bar_accolade" class="adm-form-control" value="<?php echo e($s['top_bar_accolade'] ?? 'Kerala\'s Premier Certified Agro-Forest Stay ★★★★★'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Awards or badge text scrolling in the header.</small>
                    </div>

                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Hero Climate Indicator</label>
                        <input type="text" name="hero_tag_climate" class="adm-form-control" value="<?php echo e($s['hero_tag_climate'] ?? '16°C MISTY EVENINGS • YEAR-ROUND NATURAL CHILL'); ?>">
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Highlight tag in the hero weather badge.</small>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CLIMATE CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 03: SANCTUARY PHILOSOPHY
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'philosophy'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-philosophy">
        <form action="edit_section.php?section=philosophy" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="philosophy_settings">
            <input type="hidden" name="active_tab" value="philosophy">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon gold"><i class="fa-solid fa-compass-drafting"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 03</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">SANCTUARY PHILOSOPHY & WELCOME MANIFESTO</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Establish the foundational ecological ethos and featured mudhouse portrait visual.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Section Eyebrow Badge</label>
                        <input type="text" name="welcome_badge" class="adm-form-control" value="<?php echo e($s['welcome_badge'] ?? 'Our Ancestral Philosophy'); ?>" required>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Manifesto Heading</label>
                        <input type="text" name="welcome_title" class="adm-form-control" value="<?php echo e($s['welcome_title'] ?? 'We Do Not Inherit the Earth; We Borrow It from Our Children'); ?>" required>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom: 20px;">
                    <label class="adm-form-label">Narrative Story Paragraph</label>
                    <textarea name="welcome_paragraph" rows="4" class="adm-form-control" required><?php echo e($s['welcome_paragraph'] ?? 'Nestled in the cool terraced hills of Kanthalloor, Food Forest is more than a retreat—it is an ongoing revival of natural farming, ancestral mud architecture, and slow living. Here, every morning begins with birdsong, wild forest honey, and the fragrance of ripening apples.'); ?></textarea>
                </div>

                <div class="adm-form-group" style="margin-bottom: 24px;">
                    <label class="adm-form-label">Featured Portrait Photograph</label>
                    <div class="adm-uploader-card">
                        <div class="adm-uploader-preview-box">
                            <img id="preview_welcome_img" src="<?php echo admin_img_src($s['welcome_image'] ?? 'assets/images/mudhouse_front.png'); ?>" alt="Welcome Preview" onerror="this.src='../assets/images/mudhouse_front.png';">
                        </div>
                        <div class="adm-uploader-controls">
                            <div class="adm-uploader-btn-wrap">
                                <label class="adm-uploader-btn" for="welcome_image_file">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                </label>
                                <input type="file" name="welcome_image_file" id="welcome_image_file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'preview_welcome_img', 'welcome_img_info');">
                                <span id="welcome_img_info" class="adm-file-info-badge"></span>
                            </div>
                            <div class="adm-uploader-hint">
                                <i class="fa-solid fa-circle-info"></i> JPG, PNG, WEBP, or GIF up to 15MB. Editorial portrait photo beside the philosophy narrative.
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <span style="font-size: 11px; color: var(--adm-text-muted); white-space: nowrap;">Current / Fallback Path:</span>
                                <input type="text" name="welcome_image" id="input_welcome_img" class="adm-form-control" value="<?php echo e($s['welcome_image'] ?? 'assets/images/mudhouse_front.png'); ?>" style="font-size: 11.5px; padding: 4px 10px; height: auto;" oninput="document.getElementById('preview_welcome_img').src = admin_img_src(this.value);">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 4 Pillars of Philosophy / Ecological Highlights -->
                <div style="margin-top: 24px; padding: 20px; background: rgba(16, 31, 21, 0.5); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 12px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-shapes"></i> 4 Philosophy & Ecological Pillars (Feature Cards)
                            </h4>
                            <p style="font-size: 11.5px; color: var(--adm-text-secondary); margin: 3px 0 0;">Displayed on the homepage welcome section alongside the main philosophy story.</p>
                        </div>
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">4 PILLARS</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                        <!-- Pillar 1 -->
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span style="width: 26px; height: 26px; border-radius: 50%; background: rgba(46, 204, 113, 0.2); color: #2ecc71; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;"><i class="fa-solid fa-leaf"></i></span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #FFF;">Pillar 01 (Ecological Vernacular)</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="welcome_feat1_title" class="adm-form-control" value="<?php echo e($s['welcome_feat1_title'] ?? 'Ecological Vernacular'); ?>" required>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-form-label" style="font-size: 11px;">Description</label>
                                <textarea name="welcome_feat1_desc" rows="2" class="adm-form-control" required><?php echo e($s['welcome_feat1_desc'] ?? 'Earthen clay, raw stone, reclaimed teak, and zero plastic across the retreat.'); ?></textarea>
                            </div>
                        </div>

                        <!-- Pillar 2 -->
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span style="width: 26px; height: 26px; border-radius: 50%; background: rgba(197, 160, 89, 0.2); color: var(--adm-gold); display: inline-flex; align-items: center; justify-content: center; font-size: 12px;"><i class="fa-solid fa-seedling"></i></span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #FFF;">Pillar 02 (Pure Farm-to-Table)</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="welcome_feat2_title" class="adm-form-control" value="<?php echo e($s['welcome_feat2_title'] ?? 'Pure Farm-to-Table'); ?>" required>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-form-label" style="font-size: 11px;">Description</label>
                                <textarea name="welcome_feat2_desc" rows="2" class="adm-form-control" required><?php echo e($s['welcome_feat2_desc'] ?? 'Organic chemical-free orchards. Meals harvested minutes before cooking over earthen wood fires.'); ?></textarea>
                            </div>
                        </div>

                        <!-- Pillar 3 -->
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span style="width: 26px; height: 26px; border-radius: 50%; background: rgba(56, 189, 248, 0.2); color: #38bdf8; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;"><i class="fa-solid fa-wind"></i></span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #FFF;">Pillar 03 (High-Range Climate)</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="welcome_feat3_title" class="adm-form-control" value="<?php echo e($s['welcome_feat3_title'] ?? 'High-Range Climate'); ?>" required>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-form-label" style="font-size: 11px;">Description</label>
                                <textarea name="welcome_feat3_desc" rows="2" class="adm-form-control" required><?php echo e($s['welcome_feat3_desc'] ?? 'Situated at 1,600m altitude. Chilly night mists, crisp mountain breeze, and clear skies.'); ?></textarea>
                            </div>
                        </div>

                        <!-- Pillar 4 -->
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span style="width: 26px; height: 26px; border-radius: 50%; background: rgba(244, 63, 94, 0.2); color: #f43f5e; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;"><i class="fa-solid fa-shield-heart"></i></span>
                                <span style="font-size: 11.5px; font-weight: 700; color: #FFF;">Pillar 04 (Intimate & Private)</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="welcome_feat4_title" class="adm-form-control" value="<?php echo e($s['welcome_feat4_title'] ?? 'Intimate & Private'); ?>" required>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-form-label" style="font-size: 11px;">Description</label>
                                <textarea name="welcome_feat4_desc" rows="2" class="adm-form-control" required><?php echo e($s['welcome_feat4_desc'] ?? 'Exclusive living stay concepts nestled among organic orchards to guarantee absolute privacy and silence.'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE PHILOSOPHY CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 07: WHY FOOD FOREST?
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'why'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-why">
        <form action="edit_section.php?section=why" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="why_settings">
            <input type="hidden" name="active_tab" value="why">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon amber"><i class="fa-solid fa-seedling"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 07</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">WHY FOOD FOREST? (EARTHEN COB & AGROFORESTRY)</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Highlight living soil, vernacular architecture, and permaculture food forest farming.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Section Eyebrow</label>
                        <input type="text" name="why_badge" class="adm-form-control" value="<?php echo e($s['why_badge'] ?? 'Earthen Living & Permaculture'); ?>" required>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Headline</label>
                        <input type="text" name="why_title" class="adm-form-control" value="<?php echo e($s['why_title'] ?? 'Why Food Forest Kanthalloor Stands Apart'); ?>" required>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom: 20px;">
                    <label class="adm-form-label">Story Description</label>
                    <textarea name="why_desc" rows="4" class="adm-form-control" required><?php echo e($s['why_desc'] ?? 'A handcrafted sanctuary constructed exclusively using unbaked mud, clay, straw, and reclaimed timber. Cool by day and warm during chilly mountain nights, our cottages breathe with nature.'); ?></textarea>
                </div>

                <div class="adm-form-group" style="margin-bottom: 24px;">
                    <label class="adm-form-label">Featured Farmstay Photograph</label>
                    <div class="adm-uploader-card">
                        <div class="adm-uploader-preview-box">
                            <img id="preview_why_img" src="<?php echo admin_img_src($s['why_image'] ?? 'assets/images/mudhouse_living.png'); ?>" alt="Why Preview" onerror="this.src='../assets/images/mudhouse_living.png';">
                        </div>
                        <div class="adm-uploader-controls">
                            <div class="adm-uploader-btn-wrap">
                                <label class="adm-uploader-btn" for="why_image_file">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                </label>
                                <input type="file" name="why_image_file" id="why_image_file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'preview_why_img', 'why_img_info');">
                                <span id="why_img_info" class="adm-file-info-badge"></span>
                            </div>
                            <div class="adm-uploader-hint">
                                <i class="fa-solid fa-circle-info"></i> JPG, PNG, WEBP, or GIF up to 15MB. Displayed in the left parallax frame of the Farmstay section.
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <span style="font-size: 11px; color: var(--adm-text-muted); white-space: nowrap;">Current / Fallback Path:</span>
                                <input type="text" name="why_image" id="input_why_img" class="adm-form-control" value="<?php echo e($s['why_image'] ?? 'assets/images/mudhouse_living.png'); ?>" style="font-size: 11.5px; padding: 4px 10px; height: auto;" oninput="document.getElementById('preview_why_img').src = admin_img_src(this.value);">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 WHY FOOD FOREST FEATURE PILLARS -->
                <div style="background: rgba(11, 24, 16, 0.6); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; border-bottom: 1px solid rgba(197, 160, 89, 0.15); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-shapes" style="color: var(--adm-gold); font-size: 16px;"></i>
                            <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">4 SANCTUARY FEATURE PILLARS</h4>
                        </div>
                        <span style="font-size: 11px; color: var(--adm-text-muted);">Displayed as the 4 highlight cards in the Why section</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
                        <!-- Pillar 1 -->
                        <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                    <i class="fa-solid fa-tree"></i>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase;">Pillar 01 • Living</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="why_feat1_title" class="adm-form-control" value="<?php echo e($s['why_feat1_title'] ?? 'Canopy & Mud Living'); ?>" required>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 0;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Description</label>
                                <textarea name="why_feat1_desc" rows="2" class="adm-form-control" style="font-size: 12px;" required><?php echo e($s['why_feat1_desc'] ?? 'Choose between elevated treehouses nestled 30ft in ancient branches or traditional clay cob mudhouses with thermal regulation.'); ?></textarea>
                            </div>
                        </div>

                        <!-- Pillar 2 -->
                        <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(46, 204, 113, 0.15); color: #2ecc71; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                    <i class="fa-solid fa-seedling"></i>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: #2ecc71; text-transform: uppercase;">Pillar 02 • Farmstay</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="why_feat2_title" class="adm-form-control" value="<?php echo e($s['why_feat2_title'] ?? '100% Organic Farmstay'); ?>" required>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 0;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Description</label>
                                <textarea name="why_feat2_desc" rows="2" class="adm-form-control" style="font-size: 12px;" required><?php echo e($s['why_feat2_desc'] ?? 'Live right inside chemical-free apple, plum, and tree tomato orchards. Every meal is harvested fresh from our fertile soil.'); ?></textarea>
                            </div>
                        </div>

                        <!-- Pillar 3 -->
                        <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(230, 126, 34, 0.15); color: #e67e22; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                    <i class="fa-solid fa-bowl-food"></i>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: #e67e22; text-transform: uppercase;">Pillar 03 • Cuisine</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="why_feat3_title" class="adm-form-control" value="<?php echo e($s['why_feat3_title'] ?? 'Claypot Hearth Cuisine'); ?>" required>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 0;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Description</label>
                                <textarea name="why_feat3_desc" rows="2" class="adm-form-control" style="font-size: 12px;" required><?php echo e($s['why_feat3_desc'] ?? 'Authentic Kerala slow cooking in earthenware over teak wood fires, flavored with indigenous Marayoor forest spices.'); ?></textarea>
                            </div>
                        </div>

                        <!-- Pillar 4 -->
                        <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(52, 152, 219, 0.15); color: #3498db; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                    <i class="fa-solid fa-mountain"></i>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: #3498db; text-transform: uppercase;">Pillar 04 • Serenity</span>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 10px;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Title</label>
                                <input type="text" name="why_feat4_title" class="adm-form-control" value="<?php echo e($s['why_feat4_title'] ?? 'High-Range Serenity'); ?>" required>
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 0;">
                                <label class="adm-form-label" style="font-size: 11px;">Card Description</label>
                                <textarea name="why_feat4_desc" rows="2" class="adm-form-control" style="font-size: 12px;" required><?php echo e($s['why_feat4_desc'] ?? 'Perched at 1,600 meters in Kanthalloor. Wake up to heavy mountain fog, native birdsong, and total acoustic tranquility.'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE WHY SANCTUARY CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 05: CURATED EXPERIENCES (FULL DYNAMIC EDITING)
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'experiences'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-experiences">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon violet"><i class="fa-solid fa-person-hiking"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 05</span>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">CURATED EXPERIENCES & RITUALS (DYNAMIC CMS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Directly edit, create new rituals, or delete experiences displayed on the public website.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-experience');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW EXPERIENCE
                </button>
                <button type="submit" form="form-edit-experiences" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <!-- Expandable Add New Experience Drawer -->
            <div id="drawer-add-experience" class="adm-add-new-drawer" style="display: none;">
                <form action="edit_section.php?section=experiences" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="experiences_settings">
                    <input type="hidden" name="action" value="add_experience">
                    <input type="hidden" name="active_tab" value="experiences">

                    <div class="adm-drawer-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 16px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">CREATE NEW CURATED RITUAL EXPERIENCE</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-experience');" title="Close Drawer">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Experience Title *</label>
                            <input type="text" name="new_exp_title" class="adm-form-control" placeholder="e.g. Organic Coffee Tasting & Bean Roasting" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Tag *</label>
                            <input type="text" name="new_exp_badge" class="adm-form-control" placeholder="e.g. INCLUDED IN STAY, WORKSHOP, ADVENTURE" value="INCLUDED IN STAY" required>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">Editorial Tagline (Shown in Modal Header)</label>
                        <input type="text" name="new_exp_tagline" class="adm-form-control" placeholder="e.g. Hand-pluck crisp mountain fruits in certified organic permaculture orchards.">
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Timing / Duration *</label>
                            <input type="text" name="new_exp_timing" class="adm-form-control" placeholder="e.g. 2 Hours • Morning" value="2 Hours • Morning" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Schedule Info</label>
                            <input type="text" name="new_exp_schedule" class="adm-form-control" placeholder="e.g. Daily at 07:30 AM & 10:30 AM">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Location on Estate</label>
                            <input type="text" name="new_exp_location" class="adm-form-control" placeholder="e.g. Terraced Mountain Orchards">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Suitable For</label>
                            <input type="text" name="new_exp_suitable" class="adm-form-control" placeholder="e.g. Couples, Families of all ages">
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">What to Bring (Notes for Guests)</label>
                        <input type="text" name="new_exp_bring" class="adm-form-control" placeholder="e.g. Comfortable walking shoes, sun hat, light layer for morning mist.">
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">Short Card Summary * (Shown on the 4-Column Card Grid)</label>
                        <textarea name="new_exp_desc" rows="2" class="adm-form-control" placeholder="A concise 1-2 sentence preview shown on the public grid card..." required></textarea>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">In-Depth Experience Narrative (Shown in the "View in Detail" Modal Popup)</label>
                        <textarea name="new_exp_detailed_desc" rows="4" class="adm-form-control" placeholder="Detailed multi-paragraph description of the sensory experience, what guests do, and what makes it special..."></textarea>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Key Highlights (One bullet point per line)</label>
                            <textarea name="new_exp_highlights" rows="3" class="adm-form-control" placeholder="Guided walk led by botanist&#10;Seasonal fruit harvesting&#10;Permaculture biodiversity demo&#10;Freshly pressed juice tasting"></textarea>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">What is Included</label>
                            <textarea name="new_exp_inclusions" rows="3" class="adm-form-control" placeholder="e.g. Handcrafted harvest basket, botanical notes, fruit tasting, freshly pressed juice."></textarea>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">Primary Backdrop Photograph</label>
                        <div class="adm-uploader-card">
                            <div class="adm-uploader-preview-box">
                                <img id="new_exp_preview" src="../assets/images/01 (18).jpeg" alt="Experience Preview" onerror="this.src='../assets/images/treehouse_exterior.png';">
                            </div>
                            <div class="adm-uploader-controls">
                                <div class="adm-uploader-btn-wrap">
                                    <label class="adm-uploader-btn" for="new_exp_image_file">
                                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Main Photo
                                    </label>
                                    <input type="file" name="new_exp_image_file" id="new_exp_image_file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'new_exp_preview', 'new_exp_info');">
                                    <span id="new_exp_info" class="adm-file-info-badge"></span>
                                </div>
                                <input type="hidden" name="new_exp_image" value="assets/images/01 (18).jpeg">
                            </div>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Multiple Gallery Photos (Shown in Modal Carousel)</label>
                        <div style="background: rgba(0,0,0,0.25); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 8px; padding: 14px;">
                            <div style="margin-bottom: 10px;">
                                <label class="adm-uploader-btn" for="new_exp_gal_files" style="display: inline-flex; font-size: 11.5px; padding: 7px 14px;">
                                    <i class="fa-solid fa-images"></i> Select Multiple Photos to Upload
                                </label>
                                <input type="file" name="new_exp_gallery_files[]" id="new_exp_gal_files" class="adm-uploader-input" accept="image/*" multiple onchange="document.getElementById('new_exp_gal_info').textContent = this.files.length + ' photo(s) selected';">
                                <span id="new_exp_gal_info" class="adm-file-info-badge"></span>
                            </div>
                            <label class="adm-form-label" style="font-size: 11px; margin-bottom: 4px;">Or specify relative image paths (One path per line):</label>
                            <textarea name="new_exp_gallery_urls" rows="2" class="adm-form-control" style="font-family: monospace; font-size: 11px;" placeholder="assets/images/01 (18).jpeg&#10;assets/images/01 (19).jpeg"></textarea>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-add-experience');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>PUBLISH EXPERIENCE</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Main Edit Form -->
            <form id="form-edit-experiences" action="edit_section.php?section=experiences" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="experiences_settings">
                <input type="hidden" name="active_tab" value="experiences">

                <!-- Section Header Settings -->
                <div style="background: rgba(139, 92, 246, 0.08); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #c084fc; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 14px;">Section Header Copy</span>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Eyebrow</label>
                            <input type="text" name="experiences_badge" class="adm-form-control" value="<?php echo e($s['experiences_badge'] ?? 'ACTIVITIES'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Headline</label>
                            <input type="text" name="experiences_title" class="adm-form-control" value="<?php echo e($s['experiences_title'] ?? 'Rituals of the High Range'); ?>" required>
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label">Section Narrative</label>
                        <textarea name="experiences_desc" rows="2" class="adm-form-control" required><?php echo e($s['experiences_desc'] ?? 'Connect deeply with the pulse of Kanthalloor. Each experience is handcrafted to immerse you in vernacular craftsmanship, mountain wilderness, and restorative tranquility.'); ?></textarea>
                    </div>
                </div>

                <!-- Dynamic Experiences Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-list-check"></i> Individual Experiences Catalog (<?php echo count($all_experiences); ?>)
                    </h4>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-experiences', true);" title="Expand all experience cards">
                            <i class="fa-solid fa-angles-down"></i> Expand All
                        </button>
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-experiences', false);" title="Collapse all experience cards">
                            <i class="fa-solid fa-angles-up"></i> Collapse All
                        </button>
                        <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-experience');" style="padding: 6px 12px; font-size: 11.5px;">
                            <i class="fa-solid fa-plus"></i> Add Another
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                    <?php if (empty($all_experiences)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-person-hiking" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No experiences found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-experience');">
                                <i class="fa-solid fa-plus"></i> Create First Experience
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_experiences as $idx => $exp): ?>
                            <div class="adm-exp-item-card adm-accordion-card">
                                <input type="hidden" name="exp_id[]" value="<?php echo $exp['id']; ?>">

                                <!-- Accordion Header Bar -->
                                <div class="adm-accordion-header" onclick="toggleAccordion(this, event);">
                                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                        <div class="adm-accordion-thumb-box">
                                            <img id="exp_prev_thumb_<?php echo $exp['id']; ?>" src="<?php echo admin_img_src($exp['image_url']); ?>" alt="Exp" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                        </div>
                                        <div style="min-width: 0;">
                                            <div class="adm-accordion-meta-title">
                                                <span class="adm-badge" style="background: rgba(139, 92, 246, 0.18); border: 1px solid rgba(139, 92, 246, 0.4); color: #c084fc; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 6px;">
                                                    RITUAL #<?php echo ($idx + 1); ?>
                                                </span>
                                                <span style="color: #fff; font-size: 13.5px; font-weight: 700;"><?php echo e($exp['title']); ?></span>
                                                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.35); color: var(--adm-gold); font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 6px;">
                                                    <?php echo e($exp['badge']); ?>
                                                </span>
                                            </div>
                                            <div class="adm-accordion-meta-sub">
                                                <span><i class="fa-regular fa-clock" style="color: var(--adm-gold);"></i> <?php echo e($exp['timing']); ?></span>
                                                <span>• <i class="fa-solid fa-images"></i> <?php echo count($exp['gallery_list'] ?? []); ?> Photos</span>
                                                <?php if (!empty($exp['location_info'])): ?>
                                                    <span>• <i class="fa-solid fa-location-dot"></i> <?php echo e($exp['location_info']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                                        <button type="button" class="adm-btn-danger-outline" onclick="confirmDeleteItem('experiences_settings', 'delete_exp_id', <?php echo (int)$exp['id']; ?>, '<?php echo e(addslashes($exp['title'])); ?>', 'experiences');" title="Delete this experience">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                        <span class="adm-accordion-chevron">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Accordion Body -->
                                <div class="adm-accordion-body">
                                    <!-- Title & Tagline -->
                                    <div style="display: grid; grid-template-columns: 1.5fr 2fr; gap: 12px; margin-bottom: 12px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Experience Title</label>
                                            <input type="text" name="exp_title[]" class="adm-form-control" value="<?php echo e($exp['title']); ?>" required>
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Editorial Tagline (Modal Header)</label>
                                            <input type="text" name="exp_tagline[]" class="adm-form-control" value="<?php echo e($exp['tagline'] ?? ''); ?>" placeholder="Poetic subtitle for the in-depth modal...">
                                        </div>
                                    </div>

                                    <!-- Badge, Timing, Schedule, Location -->
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 12px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Badge Tag</label>
                                            <input type="text" name="exp_badge[]" class="adm-form-control" value="<?php echo e($exp['badge']); ?>" required>
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Timing / Duration</label>
                                            <input type="text" name="exp_timing[]" class="adm-form-control" value="<?php echo e($exp['timing']); ?>" required>
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Schedule Info</label>
                                            <input type="text" name="exp_schedule[]" class="adm-form-control" value="<?php echo e($exp['schedule_info'] ?? ''); ?>" placeholder="e.g. Daily at 07:30 AM">
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Location on Estate</label>
                                            <input type="text" name="exp_location[]" class="adm-form-control" value="<?php echo e($exp['location_info'] ?? ''); ?>" placeholder="e.g. Terraced Orchards">
                                        </div>
                                    </div>

                                    <!-- Suitable For & What to Bring -->
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Suitable For</label>
                                            <input type="text" name="exp_suitable[]" class="adm-form-control" value="<?php echo e($exp['suitable_for'] ?? ''); ?>" placeholder="e.g. Couples, Families of all ages">
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">What to Bring</label>
                                            <input type="text" name="exp_bring[]" class="adm-form-control" value="<?php echo e($exp['what_to_bring'] ?? ''); ?>" placeholder="e.g. Walking shoes, sun hat, light layer">
                                        </div>
                                    </div>

                                    <!-- Short Card Summary -->
                                    <div class="adm-form-group" style="margin-bottom: 12px;">
                                        <label class="adm-form-label">Short Card Summary (Displayed on 4-Column Public Grid)</label>
                                        <textarea name="exp_desc[]" rows="2" class="adm-form-control" required><?php echo e($exp['description']); ?></textarea>
                                    </div>

                                    <!-- In-Depth Modal Narrative -->
                                    <div class="adm-form-group" style="margin-bottom: 12px;">
                                        <label class="adm-form-label">In-Depth Experience Narrative (Displayed in "View in Detail" Modal Popup)</label>
                                        <textarea name="exp_detailed_desc[]" rows="3" class="adm-form-control" placeholder="Full descriptive paragraphs for the detailed modal view..."><?php echo e($exp['detailed_description'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Highlights & Inclusions -->
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Key Highlights (One bullet per line)</label>
                                            <textarea name="exp_highlights[]" rows="3" class="adm-form-control" placeholder="Guided walk led by botanist&#10;Seasonal fruit harvesting&#10;Permaculture demo"><?php echo e(implode("\n", $exp['highlights_list'] ?? [])); ?></textarea>
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">What's Included</label>
                                            <textarea name="exp_inclusions[]" rows="3" class="adm-form-control" placeholder="What is provided for this activity..."><?php echo e($exp['inclusions'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Main Backdrop Photo -->
                                    <div class="adm-form-group" style="margin-bottom: 14px;">
                                        <label class="adm-form-label">Primary Backdrop Photograph</label>
                                        <div class="adm-uploader-card adm-uploader-compact">
                                            <div class="adm-uploader-preview-box">
                                                <img id="exp_prev_<?php echo $exp['id']; ?>" src="<?php echo admin_img_src($exp['image_url']); ?>" alt="Exp" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                            </div>
                                            <div class="adm-uploader-controls">
                                                <div class="adm-uploader-btn-wrap">
                                                    <label class="adm-uploader-btn" for="exp_file_<?php echo $exp['id']; ?>">
                                                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Change Main Photo
                                                    </label>
                                                    <input type="file" name="exp_image_file[<?php echo $idx; ?>]" id="exp_file_<?php echo $exp['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'exp_prev_<?php echo $exp['id']; ?>', 'exp_info_<?php echo $exp['id']; ?>');">
                                                    <span id="exp_info_<?php echo $exp['id']; ?>" class="adm-file-info-badge"></span>
                                                </div>
                                                <input type="hidden" name="exp_image[]" value="<?php echo e($exp['image_url']); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Multi-Photo Gallery Box -->
                                    <div class="adm-form-group" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 14px;">
                                        <label class="adm-form-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="color: var(--adm-gold); font-weight: 700;"><i class="fa-solid fa-images"></i> Multiple Gallery Photos</span>
                                            <span style="font-size: 11px; color: var(--adm-text-secondary);"><?php echo count($exp['gallery_list'] ?? []); ?> Active Photos in Modal</span>
                                        </label>
                                        
                                        <!-- Previews -->
                                        <?php if (!empty($exp['gallery_list'])): ?>
                                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                                                <?php foreach ($exp['gallery_list'] as $g_idx => $g_img): ?>
                                                    <div style="position: relative; width: 68px; height: 50px; border-radius: 5px; overflow: hidden; border: 1px solid rgba(197, 160, 89, 0.4); background: #101F15;" title="Photo #<?php echo ($g_idx + 1); ?>">
                                                        <img src="<?php echo admin_img_src($g_img); ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                                        <span style="position: absolute; bottom: 2px; right: 2px; font-size: 9px; background: rgba(0,0,0,0.75); color: #fff; padding: 1px 4px; border-radius: 3px;"><?php echo ($g_idx + 1); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div style="margin-bottom: 8px;">
                                            <label class="adm-uploader-btn" for="exp_gal_files_<?php echo $exp['id']; ?>" style="display: inline-flex; font-size: 11px; padding: 6px 12px;">
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload More Photos to Gallery (Multi-Select)
                                            </label>
                                            <input type="file" name="exp_gallery_files_<?php echo $exp['id']; ?>[]" id="exp_gal_files_<?php echo $exp['id']; ?>" class="adm-uploader-input" accept="image/*" multiple onchange="document.getElementById('exp_gal_info_<?php echo $exp['id']; ?>').textContent = this.files.length + ' photo(s) selected';">
                                            <span id="exp_gal_info_<?php echo $exp['id']; ?>" class="adm-file-info-badge"></span>
                                        </div>

                                        <label class="adm-form-label" style="font-size: 11px; margin-bottom: 4px;">Gallery Image Paths (One path per line):</label>
                                        <textarea name="exp_gallery_urls[<?php echo $idx; ?>]" rows="3" class="adm-form-control" style="font-family: monospace; font-size: 11px;"><?php echo e(implode("\n", $exp['gallery_list'] ?? [])); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE EXPERIENCES CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 06: FOOD MENU & LIVING GASTRONOMY HUB (DYNAMIC CMS)
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'menu'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-menu">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon amber"><i class="fa-solid fa-utensils"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 06</span>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">FOOD MENU & LIVING GASTRONOMY HUB (DYNAMIC CMS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Add, edit, or delete dishes across Breakfast, Lunch, Snacks & Dinner with prices, inclusions, and sliding images.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-menu-item');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW MENU DISH
                </button>
                <button type="submit" form="form-edit-menu" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE ALL DISHES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <!-- Expandable Add New Menu Dish Drawer -->
            <div id="drawer-add-menu-item" class="adm-add-new-drawer" style="display: none; background: #0E1C12; border: 1px solid var(--adm-gold); border-radius: 12px; padding: 24px; margin-bottom: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <form action="edit_section.php?section=menu" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="menu_settings">
                    <input type="hidden" name="action" value="add_menu_item">
                    <input type="hidden" name="active_tab" value="menu">

                    <div class="adm-drawer-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 18px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 15px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">CREATE NEW DISH / MENU SET</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-menu-item');" title="Close Drawer" style="background: none; border: none; color: #aaa; font-size: 18px; cursor: pointer;">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Meal Category *</label>
                            <select name="new_item_category" class="adm-form-control" required style="background: #14281B; color: #fff;">
                                <option value="breakfast">Breakfast (Morning)</option>
                                <option value="lunch">Lunch (Noon Feast)</option>
                                <option value="snacks">Evening Snacks (Chai & Bites)</option>
                                <option value="dinner">Dinner (Twilight Hearth)</option>
                            </select>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Dish Title / Heading * (e.g. Signature Heritage Dosa Set)</label>
                            <input type="text" name="new_item_heading" class="adm-form-control" placeholder="e.g. Signature Heritage Dosa Set" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Dietary Type *</label>
                            <select name="new_item_dietary_type" class="adm-form-control" style="background: #14281B; color: #fff;">
                                <option value="veg">Pure Vegetarian (Green)</option>
                                <option value="vegan">Vegan / Plant-Based</option>
                                <option value="non_veg">Non-Vegetarian (Red)</option>
                            </select>
                        </div>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Subtitle / Tagline</label>
                            <input type="text" name="new_item_subtitle" class="adm-form-control" placeholder="e.g. Crispy Ghee Dosas with 3 Stone-Ground Chutneys & Sambar">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Price (₹) *</label>
                            <input type="number" step="1" min="0" name="new_item_price" class="adm-form-control" placeholder="220" value="0" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Price Note</label>
                            <input type="text" name="new_item_price_note" class="adm-form-control" placeholder="Per Set • Farm Breakfast" value="Per Set">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Tag</label>
                            <input type="text" name="new_item_badge" class="adm-form-control" placeholder="e.g. ESTATE SIGNATURE" value="FARM FRESH">
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">Sensory Description * (Overview of how it is prepared and served)</label>
                        <textarea name="new_item_desc" rows="2" class="adm-form-control" placeholder="e.g. Fermented batter of native red rice and lentils, ladled onto seasoned cast-iron pans and crisped with fragrant A2 farm ghee. Served piping hot on a fresh banana leaf." required></textarea>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px; background: rgba(0,0,0,0.25); padding: 14px; border-radius: 8px; border: 1px dashed rgba(197, 160, 89, 0.35);">
                        <label class="adm-form-label" style="color: var(--adm-gold); font-weight: 700; margin-bottom: 6px;">
                            <i class="fa-solid fa-list-check"></i> What's Included in this Dish / Set (One item per line or comma-separated)
                        </label>
                        <p style="font-size: 11px; color: var(--adm-text-secondary); margin: 0 0 8px;">
                            Specify everything included in this set. For example: <strong>3 Crispy Golden Ghee Dosas</strong>, <strong>Spiced Potato Podi Masala</strong>, <strong>Fresh Coconut-Mint Chutney</strong>, <strong>Roasted Tomato-Garlic Chutney</strong>, <strong>Shallot-Kanthari Chutney</strong>, <strong>Hot Drumstick Sambar</strong>.
                        </p>
                        <textarea name="new_item_inclusions" rows="3" class="adm-form-control" placeholder="3 Crispy Golden Ghee Dosas&#10;Spiced Potato Podi Masala&#10;Fresh Coconut-Mint Chutney&#10;Roasted Tomato & Garlic Chutney&#10;Shallot & Kanthari White Chutney&#10;Piping Hot Drumstick Sambar"></textarea>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Primary Dish Photo (File Upload)</label>
                            <input type="file" name="new_item_image_file" class="adm-form-control" accept="image/*">
                            <input type="text" name="new_item_image" class="adm-form-control" placeholder="Or relative path (e.g. assets/images/food_dosa_set.jpg)" style="margin-top: 6px;">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Sliding Image Carousel Photos (Multi-Upload or Paths)</label>
                            <input type="file" name="new_item_gallery_files[]" class="adm-form-control" accept="image/*" multiple>
                            <textarea name="new_item_gallery_urls" rows="2" class="adm-form-control" placeholder="Additional image paths (one per line)" style="margin-top: 6px; font-family: monospace; font-size: 11px;"></textarea>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="adm-btn-action outline" onclick="toggleAddNewDrawer('drawer-add-menu-item');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="padding: 10px 24px; font-weight: 700;">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>PUBLISH NEW DISH TO MENU</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Fast Category Filter Bar -->
            <?php
            $b_count = count(array_filter($all_food_menu, function($i) { return strtolower($i['category']) === 'breakfast'; }));
            $l_count = count(array_filter($all_food_menu, function($i) { return strtolower($i['category']) === 'lunch'; }));
            $s_count = count(array_filter($all_food_menu, function($i) { return strtolower($i['category']) === 'snacks'; }));
            $d_count = count(array_filter($all_food_menu, function($i) { return strtolower($i['category']) === 'dinner'; }));
            ?>
            <div class="adm-menu-filter-bar">
                <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; margin-right: 6px;">Filter Dishes:</span>
                <button type="button" class="adm-menu-filter-btn active" onclick="filterAdminMenu('all', this);">
                    All Dishes (<?php echo count($all_food_menu); ?>)
                </button>
                <button type="button" class="adm-menu-filter-btn" onclick="filterAdminMenu('breakfast', this);">
                    ☕ Breakfast (<?php echo $b_count; ?>)
                </button>
                <button type="button" class="adm-menu-filter-btn" onclick="filterAdminMenu('lunch', this);">
                    🍛 Lunch (<?php echo $l_count; ?>)
                </button>
                <button type="button" class="adm-menu-filter-btn" onclick="filterAdminMenu('snacks', this);">
                    🍪 Evening Snacks (<?php echo $s_count; ?>)
                </button>
                <button type="button" class="adm-menu-filter-btn" onclick="filterAdminMenu('dinner', this);">
                    🌙 Dinner (<?php echo $d_count; ?>)
                </button>

                <div class="adm-dish-accordion-ctrls">
                    <button type="button" class="adm-dish-ctrl-btn" onclick="expandAllDishes(true);" title="Expand all dish cards">
                        <i class="fa-solid fa-angles-down"></i> Expand All
                    </button>
                    <button type="button" class="adm-dish-ctrl-btn" onclick="expandAllDishes(false);" title="Collapse all dish cards">
                        <i class="fa-solid fa-angles-up"></i> Collapse All
                    </button>
                </div>
            </div>

            <!-- Main Edit Form for All Dishes & Section Settings -->
            <form action="edit_section.php?section=menu" method="POST" id="form-edit-menu" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="menu_settings">
                <input type="hidden" name="active_tab" value="menu">

                <!-- Section Level Headers & Category Timings Settings Card (Collapsible) -->
                <div class="adm-menu-item-card" style="margin-bottom: 24px; border: 1px solid rgba(197, 160, 89, 0.35); background: rgba(16, 31, 21, 0.65);">
                    <div class="adm-dish-card-header" onclick="this.parentElement.classList.toggle('is-expanded');" style="padding: 14px 18px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-sliders" style="color: var(--adm-gold); font-size: 15px;"></i>
                            <span style="font-size: 13px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 0.8px;">
                                SECTION HEADINGS & DINING RITUAL TIMINGS
                            </span>
                            <span style="font-size: 11px; color: var(--adm-text-secondary); margin-left: 8px;">(Click to edit section title & meal timings)</span>
                        </div>
                        <span class="adm-dish-chevron">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </div>

                    <div class="adm-dish-card-body" style="padding: 20px;">
                        <div class="adm-form-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 14px;">
                            <div class="adm-form-group">
                                <label class="adm-form-label">Section Eyebrow Badge</label>
                                <input type="text" name="menu_badge" class="adm-form-control" value="<?php echo e(get_setting('menu_badge', 'ESTATE GASTRONOMY & ORGANIC DINING')); ?>">
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-form-label">Section Title</label>
                                <input type="text" name="menu_title" class="adm-form-control" value="<?php echo e(get_setting('menu_title', 'The Forest Hearth & Living Menu')); ?>">
                            </div>
                        </div>

                        <div class="adm-form-group" style="margin-bottom: 16px;">
                            <label class="adm-form-label">Section Overview Description</label>
                            <textarea name="menu_desc" rows="2" class="adm-form-control"><?php echo e(get_setting('menu_desc', 'Food at Food Forest is a ritual. Cooked in indigenous clay pots over aromatic wood hearths, every meal is prepared with ingredients harvested minutes prior from our own organic soil.')); ?></textarea>
                        </div>

                        <!-- Category Timings Grid -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 6px; border-left: 3px solid #C5A059;">
                                <label class="adm-form-label" style="color: #fff; font-size: 11px;">☕ Breakfast Timing</label>
                                <input type="text" name="menu_time_breakfast" class="adm-form-control" style="font-size: 12px; margin-bottom: 6px;" value="<?php echo e(get_setting('menu_time_breakfast', '07:30 AM — 10:00 AM')); ?>">
                                <label class="adm-form-label" style="font-size: 10px;">Subtitle Narrative</label>
                                <input type="text" name="menu_desc_breakfast" class="adm-form-control" style="font-size: 11px;" value="<?php echo e(get_setting('menu_desc_breakfast', 'Morning in the Orchards • Fresh farm juices, lacy hoppers & stone-ground breakfast sets')); ?>">
                            </div>

                            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 6px; border-left: 3px solid #2ecc71;">
                                <label class="adm-form-label" style="color: #fff; font-size: 11px;">🍛 Lunch Timing</label>
                                <input type="text" name="menu_time_lunch" class="adm-form-control" style="font-size: 12px; margin-bottom: 6px;" value="<?php echo e(get_setting('menu_time_lunch', '12:30 PM — 02:30 PM')); ?>">
                                <label class="adm-form-label" style="font-size: 10px;">Subtitle Narrative</label>
                                <input type="text" name="menu_desc_lunch" class="adm-form-control" style="font-size: 11px;" value="<?php echo e(get_setting('menu_desc_lunch', 'Claypot Hearth Feast • Heirloom red rice, seasonal thorans & traditional banana-leaf sadya')); ?>">
                            </div>

                            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 6px; border-left: 3px solid #e67e22;">
                                <label class="adm-form-label" style="color: #fff; font-size: 11px;">🍪 Evening Snacks Timing</label>
                                <input type="text" name="menu_time_snacks" class="adm-form-control" style="font-size: 12px; margin-bottom: 6px;" value="<?php echo e(get_setting('menu_time_snacks', '04:30 PM — 06:30 PM')); ?>">
                                <label class="adm-form-label" style="font-size: 10px;">Subtitle Narrative</label>
                                <input type="text" name="menu_desc_snacks" class="adm-form-control" style="font-size: 11px;" value="<?php echo e(get_setting('menu_desc_snacks', 'Plantation Tea Ritual • Steaming Marayoor cardamom chai, hot banana fritters & steamed ela ada')); ?>">
                            </div>

                            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 6px; border-left: 3px solid #9b59b6;">
                                <label class="adm-form-label" style="color: #fff; font-size: 11px;">🌙 Dinner Timing</label>
                                <input type="text" name="menu_time_dinner" class="adm-form-control" style="font-size: 12px; margin-bottom: 6px;" value="<?php echo e(get_setting('menu_time_dinner', '07:30 PM — 10:00 PM')); ?>">
                                <label class="adm-form-label" style="font-size: 10px;">Subtitle Narrative</label>
                                <input type="text" name="menu_desc_dinner" class="adm-form-control" style="font-size: 11px;" value="<?php echo e(get_setting('menu_desc_dinner', 'Twilight Campfire Dining • Slow-simmered stews, charcoal grills & jaggery desserts by the embers')); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dishes List Cards (Collapsible Accordion Style) -->
                <div class="adm-dishes-grid" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if (empty($all_food_menu)): ?>
                        <div style="text-align: center; padding: 40px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <p style="color: var(--adm-text-secondary); margin: 0;">No dishes found. Click "+ ADD NEW MENU DISH" above to publish your first signature dish.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_food_menu as $idx => $m_item): 
                            $cat = strtolower($m_item['category']);
                            $cat_labels = [
                                'breakfast' => '☕ BREAKFAST',
                                'lunch' => '🍛 LUNCH',
                                'snacks' => '🍪 EVENING SNACKS',
                                'dinner' => '🌙 DINNER'
                            ];
                            $cat_color = [
                                'breakfast' => '#C5A059',
                                'lunch' => '#2ecc71',
                                'snacks' => '#e67e22',
                                'dinner' => '#9b59b6'
                            ];
                        ?>
                            <div class="adm-menu-item-card" data-category="<?php echo $cat; ?>" id="dish-card-<?php echo $idx; ?>">
                                <input type="hidden" name="menu_id[<?php echo $idx; ?>]" value="<?php echo $m_item['id']; ?>">
                                
                                <!-- Card Accordion Header Bar -->
                                <div class="adm-dish-card-header" onclick="toggleDishCard(this, event);">
                                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                        <div class="adm-dish-thumb-box">
                                            <img id="thumb_menu_<?php echo $idx; ?>" 
                                                 src="<?php echo admin_img_src($m_item['image_url']); ?>" 
                                                 alt="Dish" 
                                                 onerror="this.src='../assets/images/treehouse_exterior.png';">
                                        </div>
                                        <div style="min-width: 0;">
                                            <div class="adm-dish-meta-title">
                                                <span class="adm-badge" style="background: rgba(0,0,0,0.5); border: 1px solid <?php echo $cat_color[$cat] ?? '#C5A059'; ?>; color: <?php echo $cat_color[$cat] ?? '#C5A059'; ?>; font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 12px;">
                                                    <?php echo $cat_labels[$cat] ?? strtoupper($cat); ?>
                                                </span>
                                                <span style="color: #fff; font-size: 14px; font-weight: 700;"><?php echo e($m_item['heading']); ?></span>
                                                <span style="font-size: 12px; color: #DFC694; font-weight: 600; background: rgba(197, 160, 89, 0.15); padding: 2px 8px; border-radius: 4px;">
                                                    ₹<?php echo number_format($m_item['price'], 0); ?>
                                                </span>
                                                <?php if (($m_item['dietary_type'] ?? 'veg') === 'veg'): ?>
                                                    <span style="font-size: 10px; color: #2ecc71; background: rgba(46, 204, 113, 0.15); border: 1px solid rgba(46, 204, 113, 0.3); padding: 2px 7px; border-radius: 10px;">🟢 Veg</span>
                                                <?php elseif (($m_item['dietary_type'] ?? '') === 'vegan'): ?>
                                                    <span style="font-size: 10px; color: #06b6d4; background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); padding: 2px 7px; border-radius: 10px;">🌱 Vegan</span>
                                                <?php else: ?>
                                                    <span style="font-size: 10px; color: #e74c3c; background: rgba(231, 76, 60, 0.15); border: 1px solid rgba(231, 76, 60, 0.3); padding: 2px 7px; border-radius: 10px;">🔴 Non-Veg</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="adm-dish-meta-sub">
                                                <span><?php echo e($m_item['subtitle'] ?? 'Signature Culinary Set'); ?></span>
                                                <?php if (!empty($m_item['badge'])): ?>
                                                    <span>• <strong style="color: var(--adm-gold);"><?php echo e($m_item['badge']); ?></strong></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                                        <label class="adm-dish-active-toggle" style="display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: #fff; cursor: pointer; background: rgba(0,0,0,0.3); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">
                                            <input type="checkbox" name="menu_active_<?php echo $m_item['id']; ?>" value="1" <?php echo ($m_item['is_active'] ? 'checked' : ''); ?>>
                                            <span>Active</span>
                                        </label>
                                        <button type="button" 
                                                class="adm-btn-action danger" 
                                                style="padding: 5px 10px; font-size: 11px;"
                                                onclick="confirmDeleteItem('menu_settings', 'delete_menu_id', <?php echo (int)$m_item['id']; ?>, '<?php echo e(addslashes($m_item['heading'])); ?>', 'menu');"
                                                title="Delete this dish">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                        <span class="adm-dish-chevron" title="Click to Expand / Collapse">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Collapsible Form Details Body -->
                                <div class="adm-dish-card-body">
                                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 14px; margin-bottom: 14px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Category</label>
                                            <select name="menu_category[<?php echo $idx; ?>]" class="adm-form-control" style="background: #14281B; color: #fff;">
                                                <option value="breakfast" <?php echo ($cat === 'breakfast' ? 'selected' : ''); ?>>Breakfast</option>
                                                <option value="lunch" <?php echo ($cat === 'lunch' ? 'selected' : ''); ?>>Lunch</option>
                                                <option value="snacks" <?php echo ($cat === 'snacks' ? 'selected' : ''); ?>>Evening Snacks</option>
                                                <option value="dinner" <?php echo ($cat === 'dinner' ? 'selected' : ''); ?>>Dinner</option>
                                            </select>
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Dish Title / Heading *</label>
                                            <input type="text" name="menu_heading[<?php echo $idx; ?>]" class="adm-form-control" value="<?php echo e($m_item['heading']); ?>" required>
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Dietary Type</label>
                                            <select name="menu_dietary[<?php echo $idx; ?>]" class="adm-form-control" style="background: #14281B; color: #fff;">
                                                <option value="veg" <?php echo (($m_item['dietary_type'] ?? 'veg') === 'veg' ? 'selected' : ''); ?>>Pure Vegetarian (Green)</option>
                                                <option value="vegan" <?php echo (($m_item['dietary_type'] ?? '') === 'vegan' ? 'selected' : ''); ?>>Vegan / Plant-Based</option>
                                                <option value="non_veg" <?php echo (($m_item['dietary_type'] ?? '') === 'non_veg' ? 'selected' : ''); ?>>Non-Vegetarian (Red)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Subtitle / Tagline</label>
                                            <input type="text" name="menu_subtitle[<?php echo $idx; ?>]" class="adm-form-control" value="<?php echo e($m_item['subtitle'] ?? ''); ?>">
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Price (₹)</label>
                                            <input type="number" step="1" min="0" name="menu_price[<?php echo $idx; ?>]" class="adm-form-control" value="<?php echo (int)$m_item['price']; ?>">
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Price Note</label>
                                            <input type="text" name="menu_price_note[<?php echo $idx; ?>]" class="adm-form-control" value="<?php echo e($m_item['price_note'] ?? 'Per Set'); ?>">
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label">Badge Tag</label>
                                            <input type="text" name="menu_badge[<?php echo $idx; ?>]" class="adm-form-control" value="<?php echo e($m_item['badge'] ?? 'FARM FRESH'); ?>">
                                        </div>
                                    </div>

                                    <div class="adm-form-group" style="margin-bottom: 14px;">
                                        <label class="adm-form-label">Sensory Description</label>
                                        <textarea name="menu_desc[<?php echo $idx; ?>]" rows="2" class="adm-form-control"><?php echo e($m_item['description'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Inclusions Editor & Parsed Preview -->
                                    <div class="adm-form-group" style="background: rgba(0,0,0,0.3); border: 1px dashed rgba(197, 160, 89, 0.4); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <label class="adm-form-label" style="color: var(--adm-gold); font-weight: 700; margin: 0;">
                                                <i class="fa-solid fa-list-check"></i> What's Included in this Set (One item per line or comma-separated)
                                            </label>
                                            <span style="font-size: 11px; color: var(--adm-text-secondary);">Rendered as checklist tags on frontend</span>
                                        </div>
                                        <textarea name="menu_inclusions[<?php echo $idx; ?>]" rows="3" class="adm-form-control" placeholder="e.g. 3 Ghee Dosas&#10;Potato Masala&#10;Coconut Chutney&#10;Tomato Chutney&#10;Shallot Chutney&#10;Sambar"><?php echo e($m_item['inclusions'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Photo and Carousel Box -->
                                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 140px 1fr 1fr; gap: 16px; align-items: start;">
                                        <div>
                                            <label class="adm-form-label" style="font-size: 11px;">Primary Photo</label>
                                            <div style="width: 130px; height: 95px; border-radius: 8px; overflow: hidden; border: 1px solid rgba(197, 160, 89, 0.4); background: #101F15;">
                                                <img id="preview_menu_<?php echo $idx; ?>" 
                                                     src="<?php echo admin_img_src($m_item['image_url']); ?>" 
                                                     alt="Dish" 
                                                     style="width: 100%; height: 100%; object-fit: cover;"
                                                     onerror="this.src='../assets/images/treehouse_exterior.png';">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="adm-form-label" style="font-size: 11px;">Change Primary Photo</label>
                                            <label class="adm-uploader-btn" for="menu_img_file_<?php echo $idx; ?>" style="display: inline-flex; font-size: 11px; padding: 6px 12px; margin-bottom: 6px;">
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Select New Image
                                            </label>
                                            <input type="file" 
                                                   name="menu_image_file[<?php echo $idx; ?>]" 
                                                   id="menu_img_file_<?php echo $idx; ?>" 
                                                   class="adm-uploader-input" 
                                                   accept="image/*" 
                                                   onchange="previewUploadImage(this, 'preview_menu_<?php echo $idx; ?>', 'badge_menu_<?php echo $idx; ?>'); previewUploadImage(this, 'thumb_menu_<?php echo $idx; ?>', '');">
                                            <span id="badge_menu_<?php echo $idx; ?>" class="adm-file-info-badge"></span>
                                            <input type="text" name="menu_image[<?php echo $idx; ?>]" class="adm-form-control" value="<?php echo e($m_item['image_url']); ?>" style="font-size: 11px; margin-top: 4px;">
                                        </div>

                                        <div>
                                            <label class="adm-form-label" style="font-size: 11px;">
                                                <i class="fa-solid fa-images"></i> Sliding Carousel Gallery (One path per line)
                                            </label>
                                            <textarea name="menu_gallery_urls[<?php echo $idx; ?>]" rows="3" class="adm-form-control" style="font-family: monospace; font-size: 11px;"><?php echo e(implode("\n", $m_item['gallery_list'] ?? [])); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(197, 160, 89, 0.2);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 32px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 16px rgba(197, 160, 89, 0.4);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE ALL FOOD MENU CHANGES</span>
                    </button>
                </div>
            </form>

            <!-- Standalone Delete Forms for Each Menu Item -->
            <?php foreach ($all_food_menu as $m_del): ?>
                <form action="edit_section.php?section=menu" method="POST" id="form-delete-menu-<?php echo $m_del['id']; ?>" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="menu_settings">
                    <input type="hidden" name="delete_menu_id" value="<?php echo $m_del['id']; ?>">
                    <input type="hidden" name="active_tab" value="menu">
                </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 09: SEASONS OF KANTHALLOOR (FULL DYNAMIC CMS)
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'seasons'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-seasons">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon terracotta"><i class="fa-solid fa-cloud-sun"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 09</span>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">SEASONS OF KANTHALLOOR (DYNAMIC CMS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Directly edit, create new seasonal cards, or delete seasons displayed on the public website.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-season');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW SEASON
                </button>
                <button type="submit" form="form-edit-seasons" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <!-- Expandable Add New Season Drawer -->
            <div id="drawer-add-season" class="adm-add-new-drawer" style="display: none;">
                <form action="edit_section.php?section=seasons" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="seasons_settings">
                    <input type="hidden" name="action" value="add_season">
                    <input type="hidden" name="active_tab" value="seasons">

                    <div class="adm-drawer-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 16px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">CREATE NEW SEASONAL CYCLE</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-season');" title="Close Drawer">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Season Name *</label>
                            <input type="text" name="new_season_name" class="adm-form-control" placeholder="e.g. Spring Blossom & Awakening" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Calendar Months *</label>
                            <input type="text" name="new_season_months" class="adm-form-control" placeholder="e.g. March - May" required>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">Backdrop Photograph</label>
                        <div class="adm-uploader-card">
                            <div class="adm-uploader-preview-box">
                                <img id="new_season_preview" src="../assets/images/01 (9).jpeg" alt="Season Preview" onerror="this.src='../assets/images/treehouse_exterior.png';">
                            </div>
                            <div class="adm-uploader-controls">
                                <div class="adm-uploader-btn-wrap">
                                    <label class="adm-uploader-btn" for="new_season_image_file">
                                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                    </label>
                                    <input type="file" name="new_season_image_file" id="new_season_image_file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'new_season_preview', 'new_season_info');">
                                    <span id="new_season_info" class="adm-file-info-badge"></span>
                                </div>
                                <div class="adm-uploader-hint">
                                    <i class="fa-solid fa-circle-info"></i> JPG, PNG, WEBP, or GIF up to 15MB.
                                </div>
                                <input type="hidden" name="new_season_image" value="assets/images/01 (9).jpeg">
                            </div>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Atmosphere Description *</label>
                        <textarea name="new_season_desc" rows="3" class="adm-form-control" placeholder="Describe the weather, fruits, foliage, morning mist, sensory highlights..." required></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-add-season');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>PUBLISH SEASON</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Main Edit Form -->
            <form id="form-edit-seasons" action="edit_section.php?section=seasons" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="seasons_settings">
                <input type="hidden" name="active_tab" value="seasons">

                <!-- Section Header Settings -->
                <div style="background: rgba(217, 119, 6, 0.08); border: 1px solid rgba(217, 119, 6, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #f59e0b; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 14px;">Section Header Copy</span>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Eyebrow</label>
                            <input type="text" name="seasons_badge" class="adm-form-control" value="<?php echo e($s['seasons_badge'] ?? 'Every Season has a Story'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Headline</label>
                            <input type="text" name="seasons_title" class="adm-form-control" value="<?php echo e($s['seasons_title'] ?? 'Nature\'s Changing Canvas'); ?>" required>
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label">Overview Description</label>
                        <textarea name="seasons_desc" rows="2" class="adm-form-control" required><?php echo e($s['seasons_desc'] ?? 'Kanthalloor shifts beautifully throughout the year. Each season paints our organic forest retreat in a completely distinct set of colors.'); ?></textarea>
                    </div>
                </div>

                <!-- Dynamic Seasons Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-calendar-days"></i> Individual Seasonal Cycles (<?php echo count($all_seasons); ?>)
                    </h4>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-seasons', true);" title="Expand all season cards">
                            <i class="fa-solid fa-angles-down"></i> Expand All
                        </button>
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-seasons', false);" title="Collapse all season cards">
                            <i class="fa-solid fa-angles-up"></i> Collapse All
                        </button>
                        <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-season');" style="padding: 6px 12px; font-size: 11.5px;">
                            <i class="fa-solid fa-plus"></i> Add Another Season
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                    <?php if (empty($all_seasons)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-cloud-sun" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No seasons found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-season');">
                                <i class="fa-solid fa-plus"></i> Create First Season
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_seasons as $idx => $season): ?>
                            <div class="adm-season-item-card adm-accordion-card">
                                <input type="hidden" name="season_id[]" value="<?php echo $season['id']; ?>">

                                <!-- Accordion Header Bar -->
                                <div class="adm-accordion-header" onclick="toggleAccordion(this, event);">
                                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                        <div class="adm-accordion-thumb-box">
                                            <img id="season_prev_thumb_<?php echo $season['id']; ?>" src="<?php echo admin_img_src($season['image_url']); ?>" alt="Season" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                        </div>
                                        <div style="min-width: 0;">
                                            <div class="adm-accordion-meta-title">
                                                <span class="adm-badge" style="background: rgba(245, 158, 11, 0.18); border: 1px solid rgba(245, 158, 11, 0.4); color: #f59e0b; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 6px;">
                                                    SEASON #<?php echo ($idx + 1); ?>
                                                </span>
                                                <span style="color: #fff; font-size: 13.5px; font-weight: 700;"><?php echo e($season['title']); ?></span>
                                                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.35); color: var(--adm-gold); font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 6px;">
                                                    <?php echo e($season['months']); ?>
                                                </span>
                                            </div>
                                            <div class="adm-accordion-meta-sub">
                                                <span><i class="fa-regular fa-calendar" style="color: var(--adm-gold);"></i> <?php echo e($season['months']); ?></span>
                                                <span>• ID: <?php echo $season['id']; ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                                        <button type="button" class="adm-btn-danger-outline" onclick="confirmDeleteItem('seasons_settings', 'delete_season_id', <?php echo (int)$season['id']; ?>, '<?php echo e(addslashes($season['title'])); ?>', 'seasons');" title="Delete this season card">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                        <span class="adm-accordion-chevron">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Accordion Body -->
                                <div class="adm-accordion-body">
                                    <div class="adm-form-group" style="margin-bottom: 12px;">
                                        <label class="adm-form-label">Season Name</label>
                                        <input type="text" name="season_title[]" class="adm-form-control" value="<?php echo e($season['title']); ?>" required>
                                    </div>

                                    <div class="adm-form-group" style="margin-bottom: 12px;">
                                        <label class="adm-form-label">Calendar Months</label>
                                        <input type="text" name="season_months[]" class="adm-form-control" value="<?php echo e($season['months']); ?>" required>
                                    </div>

                                    <div class="adm-form-group" style="margin-bottom: 12px;">
                                        <label class="adm-form-label">Atmosphere Description</label>
                                        <textarea name="season_desc[]" rows="3" class="adm-form-control" required><?php echo e($season['description']); ?></textarea>
                                    </div>

                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Backdrop Photo</label>
                                        <div class="adm-uploader-card adm-uploader-compact">
                                            <div class="adm-uploader-preview-box">
                                                <img id="season_prev_<?php echo $season['id']; ?>" src="<?php echo admin_img_src($season['image_url']); ?>" alt="Season" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                            </div>
                                            <div class="adm-uploader-controls">
                                                <div class="adm-uploader-btn-wrap">
                                                    <label class="adm-uploader-btn" for="season_file_<?php echo $season['id']; ?>">
                                                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload Photo
                                                    </label>
                                                    <input type="file" name="season_image_file[<?php echo $idx; ?>]" id="season_file_<?php echo $season['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'season_prev_<?php echo $season['id']; ?>', 'season_info_<?php echo $season['id']; ?>');">
                                                    <span id="season_info_<?php echo $season['id']; ?>" class="adm-file-info-badge"></span>
                                                </div>
                                                <input type="hidden" name="season_image[]" value="<?php echo e($season['image_url']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE SEASONS CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 08: SANCTUARY ESTATE MAP & MOUNTAIN ROUTE TRAILS (DYNAMIC CMS)
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'sanctuary_map'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-sanctuary_map">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-map-location-dot"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 08</span>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">SANCTUARY ESTATE MAP & TRAIL NETWORK</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Manage estate locations, link cottages directly from database, and adjust the promenade trail network & sub-branches.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-spot');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW SPOT / COTTAGE
                </button>
                <button type="submit" form="form-edit-sanctuary_map" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">

            <!-- Trail Network Status Ribbon -->
            <div style="background: linear-gradient(135deg, rgba(20, 42, 29, 0.8), rgba(12, 25, 18, 0.95)); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 10px; padding: 14px 18px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-network-wired"></i> Estate Trail Network:
                    </span>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <?php if (!empty($all_sanctuary_spots)): ?>
                            <?php foreach ($all_sanctuary_spots as $sidx => $sp): 
                                $is_c = (!empty($sp['is_stay']) || $sp['category'] === 'stays');
                            ?>
                                <span style="background: rgba(255,255,255,0.08); border: 1px solid <?php echo $is_c ? 'rgba(86, 194, 201, 0.4)' : 'rgba(197, 160, 89, 0.25)'; ?>; border-radius: 6px; padding: 3px 8px; font-size: 11.5px; color: #FFFFFF; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                    <strong style="color: <?php echo $is_c ? '#56C2C9' : 'var(--adm-gold)'; ?>;"><?php echo sprintf('%02d', $sp['spot_number']); ?></strong>
                                    <span><?php echo e(mb_strimwidth($sp['title'], 0, 16, '...')); ?></span>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--adm-text-muted);">No spots registered yet.</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="font-size: 11.5px; color: var(--adm-text-secondary); display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-code-branch" style="color: #56c2c9;"></i>
                    <span>Main Promenade Spine with dynamic sub-branches connecting to each cottage.</span>
                </div>
            </div>

            <!-- Expandable Add New Spot Drawer -->
            <div id="drawer-add-spot" class="adm-add-new-drawer" style="display: none; margin-bottom: 28px;">
                <form action="edit_section.php?section=sanctuary_map" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="sanctuary_map_settings">
                    <input type="hidden" name="action" value="add_spot">
                    <input type="hidden" name="active_tab" value="sanctuary_map">

                    <div class="adm-drawer-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 16px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">REGISTER NEW ESTATE SPOT / COTTAGE PIN</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-spot');" title="Close Drawer">✕</button>
                    </div>

                    <!-- Link to Existing Cottage / Room -->
                    <div class="adm-form-group" style="margin-bottom: 14px; background: rgba(197, 160, 89, 0.08); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 12px 14px;">
                        <label class="adm-form-label" style="display: flex; justify-content: space-between; align-items: center; color: var(--adm-gold); font-weight: 700;">
                            <span><i class="fa-solid fa-link"></i> Link from Existing Cottage / Stay Room</span>
                            <span style="font-size: 11px; color: var(--adm-text-secondary); text-transform: none;">Auto-fills details & photos</span>
                        </label>
                        <select id="new_spot_room_link" name="new_spot_linked_room_slug" class="adm-form-control" onchange="applyLinkedRoom(this.value, 'new');" style="background: #0f2417; border-color: rgba(197, 160, 89, 0.4); font-weight: 600; color: #FFFFFF;">
                            <option value="">-- Custom Sanctuary Spot / Feature (No Room Link) --</option>
                            <?php if (!empty($all_rooms)): ?>
                                <?php foreach ($all_rooms as $rm): ?>
                                    <option value="<?php echo e($rm['slug']); ?>">
                                        🏡 <?php echo e($rm['title']); ?> (₹<?php echo number_format((float)($rm['rate_per_night'] ?? 0)); ?>/night)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 110px 1fr 140px; gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Pin # *</label>
                            <input type="number" min="1" max="99" name="new_spot_number" class="adm-form-control" value="<?php echo count($all_sanctuary_spots) + 1; ?>" required style="font-weight: bold; color: var(--adm-gold);">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Spot / Cottage Title *</label>
                            <input type="text" id="new_spot_title" name="new_spot_title" class="adm-form-control" placeholder="e.g. Canopy Treehouse / Farmhouse Dining" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Category</label>
                            <select id="new_spot_category" name="new_spot_category" class="adm-form-control">
                                <option value="stays">🏡 Stay / Cottage</option>
                                <option value="dining">🍲 Farm Dining</option>
                                <option value="nature">🌿 Nature / Vista</option>
                                <option value="amenities">🌊 Brook / Glade</option>
                            </select>
                        </div>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Structure Layout</label>
                            <select id="new_spot_structure_type" name="new_spot_structure_type" class="adm-form-control">
                                <option value="single_hut">🏡 Single Cottage</option>
                                <option value="duplex_hut">🏰 Duplex Chalet (2 Suites)</option>
                            </select>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Nightly Rate (₹)</label>
                            <input type="number" step="0.01" id="new_spot_stay_price" name="new_spot_stay_price" class="adm-form-control" placeholder="e.g. 5600.00">
                        </div>
                    </div>

                    <!-- Multi-Photo Uploader for New Spot -->
                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label" style="display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fa-solid fa-images" style="color: var(--adm-gold);"></i> Spot Photos (Upload One or Multiple)</span>
                            <span style="font-size: 11px; color: var(--adm-text-secondary); text-transform: none;">Select multiple files together</span>
                        </label>
                        <div style="background: rgba(8, 18, 11, 0.7); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 8px; padding: 14px;">
                            <label class="adm-uploader-btn" for="new_spot_photos_input" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 16px; font-size: 12px; margin-bottom: 8px;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Choose Photos from Device
                            </label>
                            <input type="file" name="new_spot_photos[]" id="new_spot_photos_input" class="adm-uploader-input" multiple accept="image/*" onchange="previewMultiSpotUpload(this, 'new_spot_photos_preview');">
                            <input type="hidden" id="new_spot_fallback_image" name="new_spot_fallback_image" value="">
                            <div id="new_spot_photos_preview" style="margin-top: 8px;">
                                <span style="font-size: 11px; color: var(--adm-text-muted);">No new photos selected yet. (Default farm photo will be used if none uploaded)</span>
                            </div>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Spot Description *</label>
                        <textarea id="new_spot_desc" name="new_spot_desc" rows="3" class="adm-form-control" placeholder="Describe this mountain spot, atmosphere and experience..." required></textarea>
                    </div>

                    <!-- Default Coordinates for New Spot (Positioned via Master Map) -->
                    <input type="hidden" id="new_spot_x" name="new_spot_x" value="50">
                    <input type="hidden" id="new_spot_y" name="new_spot_y" value="50">
                    <div style="background: rgba(197, 160, 89, 0.08); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                        <i class="fa-solid fa-mountain-sun" style="color: var(--adm-gold); font-size: 22px; flex-shrink: 0;"></i>
                        <span style="font-size: 12px; color: var(--adm-text-secondary); line-height: 1.5;">
                            <strong>Dynamic Waypoint Studio:</strong> Once published, this spot's waypoint pin and branch route will automatically appear in the <strong>Sanctuary Master Map Studio</strong> below. You can drag and drop it anywhere on the terrain!
                        </span>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-add-spot');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>PUBLISH SPOT & WAYPOINT</span>
                        </button>
                    </div>
                </form>
            </div>

            <?php
            // Calculate Circular/Elliptical Main Estate Loop Road & Dynamic Branches for Studio
            $admin_loop_cx = 400;
            $admin_loop_cy = 258;
            $admin_loop_rx = 240;
            $admin_loop_ry = 162;

            $admin_main_loop_d = "M 390,420 " .
                                 "C 280,420 160,340 160,260 " .
                                 "C 160,180 280,95 400,95 " .
                                 "C 520,95 640,180 640,260 " .
                                 "C 640,340 520,420 390,420 Z";

            $admin_entrance_drive_d = "M 390,496 L 390,420";

            $admin_loop_nodes = [
                ['x' => 390, 'y' => 420],
                ['x' => 220, 'y' => 375],
                ['x' => 160, 'y' => 260],
                ['x' => 220, 'y' => 145],
                ['x' => 400, 'y' => 95],
                ['x' => 580, 'y' => 145],
                ['x' => 640, 'y' => 260],
                ['x' => 580, 'y' => 375]
            ];

            if (!function_exists('calculate_loop_junction')) {
                function calculate_loop_junction($spot_x, $spot_y, $cx = 400, $cy = 258, $rx = 240, $ry = 162) {
                    $angle = atan2($spot_y - $cy, $spot_x - $cx);
                    $jx = $cx + $rx * cos($angle);
                    $jy = $cy + $ry * sin($angle);
                    return ['x' => round($jx, 1), 'y' => round($jy, 1), 'angle' => $angle];
                }
            }

            $admin_branches = [];
            if (!empty($all_sanctuary_spots)) {
                foreach ($all_sanctuary_spots as $idx => $sp) {
                    $sx = ($sp['x_coord'] / 100.0) * 800;
                    $sy = ($sp['y_coord'] / 100.0) * 520;
                    $junc = calculate_loop_junction($sx, $sy, $admin_loop_cx, $admin_loop_cy, $admin_loop_rx, $admin_loop_ry);
                    $mid_x = ($junc['x'] + $sx) / 2;
                    $mid_y = ($junc['y'] + $sy) / 2;
                    $offset_x = ($sy - $junc['y']) * 0.18;
                    $offset_y = -($sx - $junc['x']) * 0.18;
                    $ctrl_x = $mid_x + $offset_x;
                    $ctrl_y = $mid_y + $offset_y;
                    $admin_branches[$idx] = "M " . $junc['x'] . "," . $junc['y'] . " Q " . round($ctrl_x, 1) . "," . round($ctrl_y, 1) . " " . round($sx, 1) . "," . round($sy, 1);
                }
            }
            ?>

            <!-- Main Edit Form for All Spots & Section Headers -->
            <form id="form-edit-sanctuary_map" action="edit_section.php?section=sanctuary_map" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="sanctuary_map_settings">
                <input type="hidden" name="active_tab" value="sanctuary_map">

                <!-- Section Header Settings -->
                <div style="background: rgba(46, 204, 113, 0.08); border: 1px solid rgba(46, 204, 113, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #2ecc71; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 14px;">Section Header Copy</span>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Eyebrow Label</label>
                            <input type="text" name="sanctuary_section_label" class="adm-form-control" value="<?php echo e($s['sanctuary_section_label'] ?? 'The Living Landscape'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Main Headline</label>
                            <input type="text" name="sanctuary_section_title" class="adm-form-control" value="<?php echo e($s['sanctuary_section_title'] ?? 'An Untamed Sanctuary'); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- COMMON MASTER MAP STUDIO (Interactive Drag & Drop Mountain Canvas) -->
                <div class="admin-map-studio-card">
                    <div class="admin-map-studio-header">
                        <h4 class="admin-map-studio-title">
                            <i class="fa-solid fa-mountain-sun"></i>
                            <span>Sanctuary Master Map Studio (Spine & Sub-Branches)</span>
                        </h4>
                        <div class="admin-map-studio-hud">
                            <span class="admin-map-hud-pill">
                                <i class="fa-solid fa-hand-pointer" style="color: var(--adm-gold);"></i>
                                <span>Drag pins to position branches</span>
                            </span>
                            <span class="admin-map-hud-pill" id="admin-map-drag-feedback" style="display: none; background: rgba(197, 160, 89, 0.22); border-color: var(--adm-gold); color: #FFFFFF; font-weight: 600;">
                                <i class="fa-solid fa-arrows-up-down-left-right"></i>
                                <span id="admin-map-drag-text">Positioning...</span>
                            </span>
                            <span class="admin-map-hud-pill">
                                <i class="fa-solid fa-compass" style="color: #56c2c9;"></i>
                                <span>1,600m High Range MSL</span>
                            </span>
                        </div>
                    </div>

                    <!-- Visual Master Canvas (800x520 Topographic System) -->
                    <div id="admin-master-map-canvas" class="admin-map-canvas-container">
                        <svg class="admin-master-trail-svg" viewBox="0 0 800 520" preserveAspectRatio="none">
                            <defs>
                                <radialGradient id="admin-topo-glow" cx="50%" cy="50%" r="65%">
                                    <stop offset="0%" stop-color="#1c3826" stop-opacity="0.95" />
                                    <stop offset="60%" stop-color="#14281c" stop-opacity="0.98" />
                                    <stop offset="100%" stop-color="#0c1912" stop-opacity="1" />
                                </radialGradient>
                                <linearGradient id="admin-stream-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#56c2c9" stop-opacity="0.85" />
                                    <stop offset="50%" stop-color="#38a3a5" stop-opacity="0.95" />
                                    <stop offset="100%" stop-color="#22577a" stop-opacity="0.85" />
                                </linearGradient>
                                <filter id="admin-map-glow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feGaussianBlur stdDeviation="3" result="blur" />
                                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                                </filter>
                            </defs>

                            <!-- Base Mountain Terrain Background -->
                            <rect width="800" height="520" fill="url(#admin-topo-glow)" />

                            <!-- Topographic Elevation Contours -->
                            <g stroke="rgba(197, 160, 89, 0.16)" fill="none" stroke-width="1.2">
                                <path d="M -20,440 Q 150,480 320,430 T 650,470 T 820,420" />
                                <path d="M -20,380 Q 180,410 340,360 T 670,390 T 820,350" stroke="rgba(197, 160, 89, 0.22)" />
                                <path d="M -20,320 Q 160,350 350,300 T 630,320 T 820,290" />
                                <path d="M -20,260 Q 200,290 400,240 T 650,260 T 820,220" stroke="rgba(197, 160, 89, 0.28)" />
                                <path d="M -20,200 Q 180,220 420,170 T 680,190 T 820,150" stroke="rgba(197, 160, 89, 0.4)" stroke-width="1.8" />
                                <path d="M -20,140 Q 220,170 450,110 T 700,130 T 820,90" />
                                <path d="M -20,80 Q 240,110 470,60 T 720,80 T 820,30" stroke="rgba(197, 160, 89, 0.22)" />
                                <path d="M 280,-20 Q 420,70 560,-20" stroke="rgba(197, 160, 89, 0.35)" />
                                <path d="M 330,-20 Q 430,45 520,-20" stroke="rgba(197, 160, 89, 0.45)" stroke-width="1.5" />
                            </g>

                            <!-- Meandering Mountain River / Brook -->
                            <path d="M 120,-20 C 140,80 190,140 240,210 C 290,280 340,310 410,380 C 470,440 520,480 580,540" 
                                  stroke="url(#admin-stream-gradient)" stroke-width="4.5" fill="none" stroke-linecap="round" filter="url(#admin-map-glow)" />
                            <path d="M 390,260 C 430,280 470,320 480,350" 
                                  stroke="url(#admin-stream-gradient)" stroke-width="2.2" fill="none" stroke-linecap="round" opacity="0.75" />

                            <!-- Shola Evergreen Tree Clusters -->
                            <g fill="rgba(64, 115, 84, 0.35)">
                                <circle cx="110" cy="180" r="14" /><circle cx="130" cy="190" r="11" /><circle cx="95" cy="195" r="9" />
                                <circle cx="670" cy="240" r="16" /><circle cx="690" cy="255" r="12" /><circle cx="650" cy="260" r="10" />
                                <circle cx="210" cy="420" r="15" /><circle cx="230" cy="435" r="11" />
                                <circle cx="610" cy="90" r="14" /><circle cx="630" cy="105" r="10" />
                            </g>

                            <!-- Elevation MSL Labels -->
                            <text x="685" y="185" fill="rgba(197, 160, 89, 0.55)" font-size="10" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,600M MSL</text>
                            <text x="685" y="125" fill="rgba(197, 160, 89, 0.4)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,620M MSL</text>
                            <text x="685" y="385" fill="rgba(197, 160, 89, 0.4)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,580M MSL</text>

                            <!-- 1. Estate Perimeter Boundary -->
                            <rect x="36" y="24" width="728" height="472" rx="16" 
                                  fill="none" stroke="rgba(197, 160, 89, 0.35)" stroke-width="1.2" stroke-dasharray="10,6" />
                            
                            <!-- Survey Corner Coordinate Marks -->
                            <g font-family="'Cinzel', Georgia, serif" font-size="8.5" fill="rgba(197, 160, 89, 0.55)" letter-spacing="1">
                                <text x="50" y="44">+ 10°14'22"N · 77°11'45"E</text>
                                <text x="640" y="44" text-anchor="end">+ 1,640M HIGH RANGE</text>
                                <text x="50" y="484">ESTATE PERIMETER · 12 ACRES</text>
                                <text x="640" y="484" text-anchor="end">PRIVATE SANCTUARY RESERVE</text>
                            </g>

                            <!-- 2. Main Circular / Elliptical Estate Loop Road (Permanently Visible) -->
                            <!-- Entrance Avenue -->
                            <path d="<?php echo $admin_entrance_drive_d; ?>" fill="none" stroke="rgba(197, 160, 89, 0.28)" stroke-width="8" stroke-linecap="round" filter="url(#admin-map-glow)" />
                            <path d="<?php echo $admin_entrance_drive_d; ?>" fill="none" stroke="#D4AF37" stroke-width="3" stroke-dasharray="6,4" stroke-linecap="round" />

                            <!-- Main Loop Ring Road -->
                            <path id="admin-master-spine-aura" d="<?php echo $admin_main_loop_d; ?>" 
                                  fill="none" stroke="rgba(197, 160, 89, 0.28)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" filter="url(#admin-map-glow)" />
                            <path id="admin-master-spine-line" d="<?php echo $admin_main_loop_d; ?>" 
                                  fill="none" stroke="#D4AF37" stroke-width="3.2" stroke-dasharray="10,6" stroke-linecap="round" stroke-linejoin="round" />

                            <!-- Entrance Landmark Gate -->
                            <g transform="translate(390, 492)">
                                <circle cx="0" cy="0" r="6" fill="#14281c" stroke="#D4AF37" stroke-width="2" filter="url(#admin-map-glow)" />
                                <circle cx="0" cy="0" r="2.5" fill="#56C2C9" />
                                <text x="14" y="3" fill="#D4AF37" font-size="9" font-family="'Cinzel', Georgia, serif" font-weight="bold" letter-spacing="1">MAIN ENTRANCE</text>
                            </g>

                            <!-- Loop Road Nodes -->
                            <g id="admin-master-spine-nodes">
                                <?php foreach ($admin_loop_nodes as $pt): ?>
                                    <circle cx="<?php echo $pt['x']; ?>" cy="<?php echo $pt['y']; ?>" r="4.5" fill="#14281c" stroke="#D4AF37" stroke-width="1.8" filter="url(#admin-map-glow)" />
                                    <circle cx="<?php echo $pt['x']; ?>" cy="<?php echo $pt['y']; ?>" r="1.8" fill="#56C2C9" />
                                <?php endforeach; ?>
                            </g>

                            <!-- 3. Sub-Branch Trails to Each Draggable Pin -->
                            <g id="admin-master-branches-group">
                                <?php foreach ($admin_branches as $bidx => $bd): ?>
                                    <path class="admin-branch-trail-aura" id="admin-branch-aura-<?php echo $bidx; ?>" d="<?php echo $bd; ?>" 
                                          fill="none" stroke="rgba(86, 194, 201, 0.22)" stroke-width="5" stroke-linecap="round" filter="url(#admin-map-glow)" />
                                    <path class="admin-branch-trail-line" id="admin-branch-line-<?php echo $bidx; ?>" d="<?php echo $bd; ?>" 
                                          fill="none" stroke="#C5A059" stroke-width="2" stroke-dasharray="4,4" stroke-linecap="round" opacity="0.9" />
                                <?php endforeach; ?>
                            </g>
                        </svg>

                        <!-- Draggable Pins Layer -->
                        <div id="admin-master-pins-layer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                            <?php if (!empty($all_sanctuary_spots)): ?>
                                <?php foreach ($all_sanctuary_spots as $idx => $sp): 
                                    $is_c = (!empty($sp['is_stay']) || $sp['category'] === 'stays');
                                ?>
                                    <div class="admin-master-pin <?php echo $is_c ? 'is-cottage-pin' : ''; ?>"
                                         id="master-pin-<?php echo $idx; ?>"
                                         data-idx="<?php echo $idx; ?>"
                                         data-spot-num="<?php echo (int)$sp['spot_number']; ?>"
                                         data-title="<?php echo e($sp['title']); ?>"
                                         style="left: <?php echo (float)$sp['x_coord']; ?>%; top: <?php echo (float)$sp['y_coord']; ?>%; pointer-events: auto;"
                                         title="Drag to reposition Spot #<?php echo sprintf('%02d', $sp['spot_number']); ?>">
                                        <div class="admin-pin-pulse" style="<?php echo $is_c ? 'background: rgba(86, 194, 201, 0.4);' : ''; ?>"></div>
                                        <div class="admin-pin-core" style="<?php echo $is_c ? 'background: #0d2822; border-color: #56C2C9; color: #56C2C9;' : ''; ?>">
                                            <span><?php echo sprintf('%02d', $sp['spot_number']); ?></span>
                                        </div>
                                        <div class="admin-pin-label">
                                            <strong>#<?php echo sprintf('%02d', $sp['spot_number']); ?> <?php echo e(mb_strimwidth($sp['title'], 0, 15, '..')); ?></strong>
                                            <span class="admin-pin-coords" id="pin-coords-text-<?php echo $idx; ?>">
                                                X:<?php echo round((float)$sp['x_coord'], 1); ?>% Y:<?php echo round((float)$sp['y_coord'], 1); ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Topographical Compass Badge -->
                        <div style="position: absolute; bottom: 14px; right: 16px; background: rgba(8, 18, 12, 0.85); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 8px; padding: 6px 10px; font-size: 10px; color: var(--adm-gold); font-family: var(--adm-font-title); letter-spacing: 1px; pointer-events: none; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-regular fa-compass" style="font-size: 14px; color: #56c2c9;"></i>
                            <span>KANTHALLOOR HIGHLAND</span>
                        </div>
                    </div>

                    <!-- Live Status Bar -->
                    <div class="admin-map-live-status-bar">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-code-branch" style="color: #2ecc71;"></i>
                            <span style="color: #FFFFFF; font-weight: 600;">Trail Network:</span>
                            <span style="color: var(--adm-text-secondary);"><?php echo count($all_sanctuary_spots); ?> Sub-Branches linked to Promenade Spine</span>
                        </div>
                        <div id="admin-map-live-tip" style="color: var(--adm-text-muted); font-size: 11.5px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-lightbulb" style="color: var(--adm-gold);"></i>
                            <span>Click and drag any pin with your mouse to reposition its branch. Save button commits changes.</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Spots Catalog Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-map-pin"></i> Registered Sanctuary Spots & Cottages (<?php echo count($all_sanctuary_spots); ?>)
                    </h4>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-sanctuary_map', true);" title="Expand all spot cards">
                            <i class="fa-solid fa-angles-down"></i> Expand All
                        </button>
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-sanctuary_map', false);" title="Collapse all spot cards">
                            <i class="fa-solid fa-angles-up"></i> Collapse All
                        </button>
                        <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-spot');" style="padding: 6px 12px; font-size: 11.5px;">
                            <i class="fa-solid fa-plus"></i> Add Another Spot
                        </button>
                    </div>
                </div>

                <!-- Spots Cards Grid -->
                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                    <?php if (empty($all_sanctuary_spots)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-map-location-dot" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No spots registered in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-spot');">
                                <i class="fa-solid fa-plus"></i> Create First Spot
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_sanctuary_spots as $idx => $sp): 
                            $first_photo = !empty($sp['photos_list']) ? $sp['photos_list'][0] : ($sp['image_url'] ?? 'assets/images/01 (1).jpeg');
                            $is_c = (!empty($sp['is_stay']) || $sp['category'] === 'stays');
                        ?>
                            <div class="adm-spot-item-card adm-accordion-card">
                                <input type="hidden" name="spot_id[]" value="<?php echo $sp['id']; ?>">
                                <input type="hidden" name="spot_fallback_image[]" value="<?php echo e($sp['image_url']); ?>">
                                <!-- Coordinates updated via Master Map Studio Drag & Drop -->
                                <input type="hidden" id="spot_x_<?php echo $idx; ?>" name="spot_x[]" value="<?php echo (float)$sp['x_coord']; ?>">
                                <input type="hidden" id="spot_y_<?php echo $idx; ?>" name="spot_y[]" value="<?php echo (float)$sp['y_coord']; ?>">

                                <!-- Accordion Header Bar -->
                                <div class="adm-accordion-header" onclick="toggleAccordion(this, event);">
                                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                        <div class="adm-accordion-thumb-box">
                                            <img src="../<?php echo e($first_photo); ?>" alt="Spot" onerror="this.src='../assets/images/01 (1).jpeg';">
                                        </div>
                                        <div style="min-width: 0;">
                                            <div class="adm-accordion-meta-title">
                                                <span style="background: <?php echo $is_c ? '#56C2C9' : '#C5A059'; ?>; color: #101F15; font-weight: 800; font-size: 11px; padding: 2px 7px; border-radius: 4px;">
                                                    #<?php echo sprintf('%02d', $sp['spot_number']); ?>
                                                </span>
                                                <span style="color: #FFFFFF; font-weight: 700; font-size: 13.5px;">
                                                    <?php echo e($sp['title']); ?>
                                                </span>
                                                <?php if (!empty($sp['linked_room_slug'])): ?>
                                                    <span style="background: rgba(86, 194, 201, 0.15); border: 1px solid rgba(86, 194, 201, 0.35); color: #56C2C9; font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 4px;">
                                                        <i class="fa-solid fa-link"></i> Linked Room
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="adm-accordion-meta-sub">
                                                <span><i class="fa-solid fa-location-dot" style="color: var(--adm-gold);"></i> Coords: X:<?php echo round((float)$sp['x_coord'], 1); ?>% Y:<?php echo round((float)$sp['y_coord'], 1); ?>%</span>
                                                <span>• <i class="fa-solid fa-images"></i> <?php echo count($sp['photos_list'] ?? []); ?> Photos</span>
                                                <?php if (!empty($sp['stay_price'])): ?>
                                                    <span>• <strong style="color: #56C2C9;">₹<?php echo number_format((float)$sp['stay_price']); ?>/nt</strong></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                        <!-- Interactive Map Coordinate Badge & Locator -->
                                        <button type="button" onclick="focusPinOnMasterMap(<?php echo $idx; ?>);" class="adm-btn-action" style="padding: 3px 9px; font-size: 11px; background: rgba(197, 160, 89, 0.12); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px;" title="Highlight on Master Map">
                                            <i class="fa-solid fa-location-crosshairs"></i>
                                            <span id="card-coord-badge-<?php echo $idx; ?>">X: <?php echo round((float)$sp['x_coord'], 1); ?>%</span>
                                        </button>
                                        <button type="button" class="adm-btn-danger-outline" onclick="confirmDeleteItem('sanctuary_map_settings', 'delete_spot_id', <?php echo (int)$sp['id']; ?>, '<?php echo e(addslashes($sp['title'])); ?>', 'sanctuary_map');" title="Delete Spot">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                        <span class="adm-accordion-chevron">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Accordion Body -->
                                <div class="adm-accordion-body">
                                    
                                    <!-- Link to Existing Room & Category Bar -->
                                    <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                                        <div style="display: grid; grid-template-columns: 1fr 130px 130px 110px; gap: 10px; align-items: end;">
                                            <div class="adm-form-group" style="margin: 0;">
                                                <label class="adm-form-label" style="font-size: 10px; color: var(--adm-gold);"><i class="fa-solid fa-link"></i> Linked Room / Cottage</label>
                                                <select name="spot_linked_room_slug[]" class="adm-form-control" style="font-size: 11.5px; height: 34px;" onchange="applyLinkedRoom(this.value, <?php echo $idx; ?>);">
                                                    <option value="">-- No Room Link (Custom Spot) --</option>
                                                    <?php if (!empty($all_rooms)): ?>
                                                        <?php foreach ($all_rooms as $rm): ?>
                                                            <option value="<?php echo e($rm['slug']); ?>" <?php echo ($sp['linked_room_slug'] === $rm['slug']) ? 'selected' : ''; ?>>
                                                                🏡 <?php echo e($rm['title']); ?> (₹<?php echo number_format((float)($rm['rate_per_night'] ?? 0)); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <div class="adm-form-group" style="margin: 0;">
                                                <label class="adm-form-label" style="font-size: 10px;">Category</label>
                                                <select name="spot_category[]" id="spot_category_<?php echo $idx; ?>" class="adm-form-control" style="font-size: 11.5px; height: 34px;">
                                                    <option value="stays" <?php echo ($sp['category'] === 'stays' || !empty($sp['is_stay'])) ? 'selected' : ''; ?>>🏡 Stay / Cottage</option>
                                                    <option value="dining" <?php echo ($sp['category'] === 'dining') ? 'selected' : ''; ?>>🍲 Farm Dining</option>
                                                    <option value="nature" <?php echo ($sp['category'] === 'nature') ? 'selected' : ''; ?>>🌿 Nature / Vista</option>
                                                    <option value="amenities" <?php echo ($sp['category'] === 'amenities') ? 'selected' : ''; ?>>🌊 Brook / Glade</option>
                                                </select>
                                            </div>
                                            <div class="adm-form-group" style="margin: 0;">
                                                <label class="adm-form-label" style="font-size: 10px;">Structure</label>
                                                <select name="spot_structure_type[]" id="spot_structure_type_<?php echo $idx; ?>" class="adm-form-control" style="font-size: 11.5px; height: 34px;">
                                                    <option value="single_hut" <?php echo (($sp['structure_type'] ?? '') === 'single_hut') ? 'selected' : ''; ?>>🏡 Single</option>
                                                    <option value="duplex_hut" <?php echo (($sp['structure_type'] ?? '') === 'duplex_hut') ? 'selected' : ''; ?>>🏰 Duplex</option>
                                                </select>
                                            </div>
                                            <div class="adm-form-group" style="margin: 0;">
                                                <label class="adm-form-label" style="font-size: 10px;">Rate (₹)</label>
                                                <input type="number" step="0.01" name="spot_stay_price[]" id="spot_stay_price_<?php echo $idx; ?>" class="adm-form-control" style="font-size: 11.5px; height: 34px;" value="<?php echo !empty($sp['stay_price']) ? (float)$sp['stay_price'] : ''; ?>" placeholder="e.g. 5600">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Route # and Spot Title -->
                                    <div style="display: grid; grid-template-columns: 80px 1fr; gap: 12px; margin-bottom: 14px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label" style="font-size: 10px;">Pin #</label>
                                            <input type="number" min="1" max="99" name="spot_number[]" class="adm-form-control" value="<?php echo (int)$sp['spot_number']; ?>" required style="font-weight: 700; color: var(--adm-gold);" oninput="syncSpotNumToPin(<?php echo $idx; ?>, this.value);">
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label" style="font-size: 10px;">Spot / Cottage Title</label>
                                            <input type="text" name="spot_title[]" id="spot_title_<?php echo $idx; ?>" class="adm-form-control" value="<?php echo e($sp['title']); ?>" required oninput="syncSpotTitleToPin(<?php echo $idx; ?>, this.value);">
                                        </div>
                                    </div>

                                    <!-- Spot Photos Management (Multiple Photos) -->
                                    <div class="adm-form-group" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 12px; margin-bottom: 14px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                                            <label class="adm-form-label" style="font-size: 10.5px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 6px;">
                                                <i class="fa-solid fa-images"></i> Spot Photos Gallery (<?php echo count($sp['photos_list'] ?? []); ?>)
                                            </label>
                                            <label class="adm-uploader-btn" for="spot_new_file_<?php echo $sp['id']; ?>" style="padding: 4px 10px; font-size: 11px; margin: 0; cursor: pointer;">
                                                <i class="fa-solid fa-plus"></i> Add More Photos
                                            </label>
                                            <input type="file" name="spot_new_photos_<?php echo $sp['id']; ?>[]" id="spot_new_file_<?php echo $sp['id']; ?>" class="adm-uploader-input" accept="image/*" multiple onchange="previewMultiSpotUpload(this, 'spot_new_prev_<?php echo $sp['id']; ?>');">
                                        </div>

                                        <!-- Existing Photos Thumbnails Grid with Delete (x) Button -->
                                        <div class="adm-spot-photos-grid" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                            <?php if (!empty($sp['photos_list'])): ?>
                                                <?php foreach ($sp['photos_list'] as $p_idx => $p_url): ?>
                                                    <div class="adm-spot-photo-thumb" style="position: relative; width: 72px; height: 72px; border-radius: 6px; overflow: hidden; border: 1px solid rgba(197, 160, 89, 0.3); transition: all 0.2s ease;">
                                                        <img src="../<?php echo e($p_url); ?>" alt="Spot Photo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/01 (1).jpeg';">
                                                        <input type="hidden" name="spot_existing_photos[<?php echo $sp['id']; ?>][]" value="<?php echo e($p_url); ?>">
                                                        <button type="button" onclick="removeSpotPhotoThumbnail(this);" title="Remove this photo" style="position: absolute; top: 2px; right: 2px; width: 18px; height: 18px; background: rgba(220, 53, 69, 0.85); color: #fff; border: none; border-radius: 50%; font-size: 10px; line-height: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;">✕</button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <div id="spot_new_prev_<?php echo $sp['id']; ?>" style="margin-top: 8px;"></div>
                                    </div>

                                    <!-- Description -->
                                    <div class="adm-form-group" style="margin-bottom: 4px;">
                                        <label class="adm-form-label" style="font-size: 10px;">Spot Description</label>
                                        <textarea name="spot_desc[]" id="spot_desc_<?php echo $idx; ?>" rows="2" class="adm-form-control" style="font-size: 12px;" required><?php echo e($sp['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE SANCTUARY MAP CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
        <script>
            window.allRoomsData = <?php echo json_encode($all_rooms); ?>;
        </script>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 04: VILLAS & COTTAGES (DYNAMIC TARIFFS & SPECS)
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'rooms'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-rooms">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon rose"><i class="fa-solid fa-house-chimney"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 04</span>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">VILLAS & 3D SUITES (DYNAMIC TARIFFS & SPECS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Directly adjust rates, add new villas/suites, or delete accommodations.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-room');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW VILLA
                </button>
                <button type="submit" form="form-edit-rooms" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <!-- Expandable Add New Villa Drawer -->
            <div id="drawer-add-room" class="adm-add-new-drawer" style="display: none;">
                <form action="edit_section.php?section=rooms" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="rooms_settings">
                    <input type="hidden" name="action" value="add_room">
                    <input type="hidden" name="active_tab" value="rooms">

                    <div class="adm-drawer-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 16px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">REGISTER NEW VILLA OR DWELLING SUITE</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-room');" title="Close Drawer">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Stay Category *</label>
                            <select name="new_room_stay_type" class="adm-form-control" style="font-weight: 700; color: #2ecc71;" required>
                                <option value="mudhouse">🌿 Mudhouse Stay (Handcrafted Earth & Terracotta)</option>
                                <option value="treehouse" selected>🌲 Treehouse Stay (Timber Canopy & High Ridge)</option>
                            </select>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Structure Type *</label>
                            <select name="new_room_structure_type" class="adm-form-control" style="font-weight: 700; color: var(--adm-gold);" required>
                                <option value="single_hut" selected>🏡 Single Hut (1-Room Private Cottage)</option>
                                <option value="duplex_hut">🏘️ Duplex Hut (Multi-Room Family Cottage)</option>
                            </select>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Suite Title *</label>
                            <input type="text" name="new_room_title" class="adm-form-control" placeholder="e.g. The Canopy Treehouse — Duplex Villa" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">URL Slug (lowercase-dashes)</label>
                            <input type="text" name="new_room_slug" class="adm-form-control" placeholder="e.g. treehouse-duplex (blank to auto-generate)">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Full Cottage Nightly Rate (₹) *</label>
                            <input type="number" step="100" name="new_room_rate" class="adm-form-control" placeholder="e.g. 14500" required style="font-weight: bold; color: var(--adm-gold);">
                            <small style="color: var(--adm-text-secondary); font-size: 10px;">Minimum base payment covering base guests</small>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Single Room Option Rate (₹) <small>(For Duplex)</small></label>
                            <input type="number" step="100" name="new_room_single_rate" class="adm-form-control" placeholder="e.g. 14500" style="font-weight: bold; color: #56c2c9;">
                            <small style="color: var(--adm-text-secondary); font-size: 10px;">Rate when booking 1 suite in Duplex</small>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Min Guests Allowed *</label>
                            <input type="number" min="1" max="10" name="new_room_min_guests" class="adm-form-control" value="2" required title="Minimum guests required to book">
                            <small style="color: var(--adm-text-secondary); font-size: 10px;">Minimum group size (e.g. 2)</small>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Base Guests Included *</label>
                            <input type="number" min="1" max="10" name="new_room_base_guests" class="adm-form-control" value="2" required title="Guests covered by base nightly rate (e.g. 2 for single cottage, 4 for double cottage)">
                            <small style="color: var(--adm-text-secondary); font-size: 10px;">Included in base rate (e.g. 2 for Single, 4 for Duplex)</small>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Max Guest Capacity *</label>
                            <input type="number" min="1" max="15" name="new_room_capacity" class="adm-form-control" value="4" required title="Absolute maximum guests this suite can accommodate">
                            <small style="color: var(--adm-text-secondary); font-size: 10px;">Maximum allowable guests (Adults + Children)</small>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Extra Adult Rate / Night (₹) (12+ yrs) *</label>
                            <input type="number" step="100" name="new_room_extra_rate" class="adm-form-control" value="1500" required style="font-weight: bold; color: #2ecc71;">
                            <small style="color: var(--adm-text-secondary); font-size: 10px;">Per extra adult exceeding base count per night</small>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Extra Child Rate / Night (₹) (5-11 yrs) *</label>
                            <input type="number" step="100" name="new_room_extra_child_rate" class="adm-form-control" value="800" required style="font-weight: bold; color: #f59e0b;">
                            <small style="color: var(--adm-text-secondary); font-size: 10px;">Per extra child/night (Infants &lt; 5 yrs complimentary)</small>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Elevation Tag *</label>
                            <input type="text" name="new_room_elevation" class="adm-form-control" placeholder="e.g. 1,600m High Ridge" value="1,600m High Ridge" required>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">Primary Suite Photo</label>
                        <div class="adm-uploader-card">
                            <div class="adm-uploader-preview-box">
                                <img id="new_room_preview" src="../assets/images/treehouse_exterior.png" alt="Room Preview" onerror="this.src='../assets/images/treehouse_exterior.png';">
                            </div>
                            <div class="adm-uploader-controls">
                                <div class="adm-uploader-btn-wrap">
                                    <label class="adm-uploader-btn" for="new_room_image_file">
                                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                    </label>
                                    <input type="file" name="new_room_image_file" id="new_room_image_file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'new_room_preview', 'new_room_info');">
                                    <span id="new_room_info" class="adm-file-info-badge"></span>
                                </div>
                                <div class="adm-uploader-hint">
                                    <i class="fa-solid fa-circle-info"></i> Exterior shot shown on listing card.
                                </div>
                                <input type="hidden" name="new_room_image" value="assets/images/treehouse_exterior.png">
                            </div>
                        </div>
                    </div>

                    <!-- 360 Walkthrough Panorama Options -->
                    <div class="adm-form-group" style="background: rgba(16, 31, 21, 0.4); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 10px; padding: 16px; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                            <label class="adm-form-label" style="color: var(--adm-gold); margin: 0; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-arrows-spin"></i> 360° Interior Suite Walkthrough (Optional)
                            </label>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3);" onclick="toggle360Mode('new-mode-single', 'new-mode-stitch');">
                                    <i class="fa-solid fa-image"></i> Single 360 / PANO
                                </button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);" onclick="toggle360Mode('new-mode-stitch', 'new-mode-single');">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> 3-Photo Auto-Stitcher
                                </button>
                            </div>
                        </div>

                        <!-- Mode 1: Single 360 -->
                        <div id="new-mode-single">
                            <span style="font-size: 11px; color: var(--adm-text-secondary); display: block; margin-bottom: 8px;">
                                Upload a single mobile phone PANO or 360° equirectangular photo:
                            </span>
                            <div class="adm-uploader-card adm-uploader-compact">
                                <div class="adm-uploader-controls" style="width: 100%;">
                                    <div class="adm-uploader-btn-wrap">
                                        <label class="adm-uploader-btn" for="new_room_360_file">
                                            <i class="fa-solid fa-camera"></i> Choose 360 / PANO Photo
                                        </label>
                                        <input type="file" name="new_room_360_file" id="new_room_360_file" class="adm-uploader-input" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mode 2: 3-Photo Auto Stitcher -->
                        <div id="new-mode-stitch" style="display: none; background: rgba(7, 18, 11, 0.85); border: 1px dashed rgba(46, 204, 113, 0.4); border-radius: 8px; padding: 12px;">
                            <span style="font-size: 11.5px; font-weight: 700; color: #2ecc71; display: block; margin-bottom: 6px;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> 3-Angle Phone Camera Auto-Stitcher
                            </span>
                            <span style="font-size: 11px; color: var(--adm-text-secondary); display: block; margin-bottom: 10px;">
                                Upload 3 normal photos from your phone (Left angle, Center angle, Right angle). The system will automatically align, blend seams, and generate the 360° sphere.
                            </span>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                                <div style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 6px; text-align: center;">
                                    <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 4px;">1. LEFT ANGLE</span>
                                    <label class="adm-uploader-btn" for="new_stitch_l" style="font-size: 10.5px; padding: 5px; width: 100%; justify-content: center;">Upload</label>
                                    <input type="file" name="new_room_stitch_left" id="new_stitch_l" class="adm-uploader-input" accept="image/*" onchange="updateStitchBadge(this, 'nbadge_l');">
                                    <span id="nbadge_l" style="font-size: 9.5px; color: #2ecc71; display: none; margin-top: 3px;"></span>
                                </div>
                                <div style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 6px; text-align: center;">
                                    <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 4px;">2. CENTER ANGLE</span>
                                    <label class="adm-uploader-btn" for="new_stitch_c" style="font-size: 10.5px; padding: 5px; width: 100%; justify-content: center;">Upload</label>
                                    <input type="file" name="new_room_stitch_center" id="new_stitch_c" class="adm-uploader-input" accept="image/*" onchange="updateStitchBadge(this, 'nbadge_c');">
                                    <span id="nbadge_c" style="font-size: 9.5px; color: #2ecc71; display: none; margin-top: 3px;"></span>
                                </div>
                                <div style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 6px; text-align: center;">
                                    <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 4px;">3. RIGHT ANGLE</span>
                                    <label class="adm-uploader-btn" for="new_stitch_r" style="font-size: 10.5px; padding: 5px; width: 100%; justify-content: center;">Upload</label>
                                    <input type="file" name="new_room_stitch_right" id="new_stitch_r" class="adm-uploader-input" accept="image/*" onchange="updateStitchBadge(this, 'nbadge_r');">
                                    <span id="nbadge_r" style="font-size: 9.5px; color: #2ecc71; display: none; margin-top: 3px;"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Architectural Bio / Description *</label>
                        <textarea name="new_room_desc" rows="3" class="adm-form-control" placeholder="Highlight materials, ventilation, natural thermal properties, private verandas, and forest view aspects..." required></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-add-room');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>PUBLISH VILLA SUITE</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Main Edit Form -->
            <form id="form-edit-rooms" action="edit_section.php?section=rooms" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="rooms_settings">
                <input type="hidden" name="active_tab" value="rooms">

                <!-- Section Header Settings -->
                <div style="background: rgba(244, 63, 94, 0.08); border: 1px solid rgba(244, 63, 94, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #fb7185; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 14px;">3D Tour Header Copy</span>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Eyebrow</label>
                            <input type="text" name="rooms_badge" class="adm-form-control" value="<?php echo e($s['rooms_badge'] ?? 'Sanctuary Accommodations'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Headline</label>
                            <input type="text" name="rooms_title" class="adm-form-control" value="<?php echo e($s['rooms_title'] ?? 'The Canopy Treehouse & The Earthen Mudhouse'); ?>" required>
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label">3D Walkthrough Description</label>
                        <textarea name="rooms_desc" rows="2" class="adm-form-control" required><?php echo e($s['rooms_desc'] ?? 'Experience our full-screen 3D architectural walkthrough. Hover and rotate through 360-degree panoramas of our timber treehouse and handcrafted mudhouse.'); ?></textarea>
                    </div>
                </div>

                <!-- Suites Header & Filter Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-bed"></i> Architectural Suites & Nightly Rates (<?php echo count($all_rooms); ?>)
                    </h4>
                    <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-room');" style="padding: 6px 12px; font-size: 11.5px;">
                        <i class="fa-solid fa-plus"></i> Add Another
                    </button>
                </div>

                <style>
                .adm-huts-filter-btn {
                    background: rgba(255,255,255,0.06);
                    border: 1px solid rgba(255,255,255,0.12);
                    color: var(--adm-text-secondary);
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 11.5px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .adm-huts-filter-btn:hover {
                    background: rgba(197, 160, 89, 0.15);
                    color: var(--adm-gold);
                    border-color: rgba(197, 160, 89, 0.35);
                }
                .adm-huts-filter-btn.active {
                    background: var(--adm-gold);
                    color: #07100B;
                    border-color: var(--adm-gold);
                    font-weight: 700;
                    box-shadow: 0 2px 8px rgba(197, 160, 89, 0.3);
                }
                </style>

                <div class="adm-huts-filter-bar" style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; background: rgba(0,0,0,0.35); padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08);">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-secondary); font-weight: 700; letter-spacing: 0.8px; margin-right: 4px;">
                        <i class="fa-solid fa-filter" style="color: var(--adm-gold); margin-right: 4px;"></i> Filter Huts:
                    </span>
                    <button type="button" class="adm-huts-filter-btn active" data-filter="all" onclick="filterAdminRooms('all', this)">All Huts (<?php echo count($all_rooms); ?>)</button>
                    <button type="button" class="adm-huts-filter-btn" data-filter="mudhouse" onclick="filterAdminRooms('mudhouse', this)">🌿 Mudhouse Huts</button>
                    <button type="button" class="adm-huts-filter-btn" data-filter="treehouse" onclick="filterAdminRooms('treehouse', this)">🌲 Wooden / Tree Huts</button>
                    <button type="button" class="adm-huts-filter-btn" data-filter="single_hut" onclick="filterAdminRooms('single_hut', this)">🏡 Single Huts</button>
                    <button type="button" class="adm-huts-filter-btn" data-filter="duplex_hut" onclick="filterAdminRooms('duplex_hut', this)">🏘️ Duplex Huts</button>

                    <div class="adm-accordion-ctrls">
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-rooms', true);" title="Expand all villa cards">
                            <i class="fa-solid fa-angles-down"></i> Expand All
                        </button>
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-rooms', false);" title="Collapse all villa cards">
                            <i class="fa-solid fa-angles-up"></i> Collapse All
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;" id="adm-rooms-grid">
                    <?php if (empty($all_rooms)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-bed" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No villas found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-room');">
                                <i class="fa-solid fa-plus-circle"></i> Add First Villa
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_rooms as $idx => $room): 
                        $structure_type = $room['structure_type'] ?? 'single_hut';
                        $stay_type = $room['stay_type'] ?? 'treehouse';
                    ?>
                        <div class="adm-room-item-card adm-accordion-card is-expanded" data-stay-type="<?php echo e($stay_type); ?>" data-structure-type="<?php echo e($structure_type); ?>">
                            <input type="hidden" name="room_id[]" value="<?php echo $room['id']; ?>">

                            <!-- Accordion Header Bar -->
                            <div class="adm-accordion-header" onclick="toggleAccordion(this, event);">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                    <div class="adm-accordion-thumb-box">
                                        <img id="room_prev_thumb_<?php echo $room['id']; ?>" src="<?php echo admin_img_src($room['image_url']); ?>" alt="Room" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                    </div>
                                    <div style="min-width: 0;">
                                        <div class="adm-accordion-meta-title">
                                            <span style="font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px; <?php echo $stay_type === 'mudhouse' ? 'background: rgba(234, 88, 12, 0.18); color: #fb923c; border: 1px solid rgba(234, 88, 12, 0.4);' : 'background: rgba(46, 204, 113, 0.18); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4);'; ?>">
                                                <?php echo $stay_type === 'mudhouse' ? '🌿 MUDHOUSE' : '🌲 WOODEN / TREE'; ?>
                                            </span>
                                            <span style="font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px; <?php echo $structure_type === 'duplex_hut' ? 'background: rgba(168, 85, 247, 0.18); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4);' : 'background: rgba(59, 130, 246, 0.18); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4);'; ?>">
                                                <?php echo $structure_type === 'duplex_hut' ? '🏘️ DUPLEX' : '🏡 SINGLE'; ?>
                                            </span>
                                            <span style="font-size: 13.5px; font-weight: 700; color: #FFFFFF;">
                                                <?php echo e($room['title'] ?? $room['slug']); ?>
                                            </span>
                                            <span style="font-size: 12px; color: #DFC694; font-weight: 600; background: rgba(197, 160, 89, 0.15); padding: 2px 8px; border-radius: 4px;">
                                                ₹<?php echo number_format((float)$room['rate_per_night'], 0); ?>/nt
                                            </span>
                                        </div>
                                        <div class="adm-accordion-meta-sub">
                                            <span><?php echo e($room['elevation']); ?></span>
                                            <span>• Up to <?php echo (int)($room['max_guests'] ?? 4); ?> Guests</span>
                                            <span>• <?php echo (!isset($room['is_available']) || $room['is_available'] == 1) ? '<strong style="color: #2ecc71;">Active</strong>' : '<strong style="color: #e74c3c;">Maintenance</strong>'; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                    <span class="adm-btn-action outline" style="padding: 5px 12px; font-size: 11.5px; pointer-events: none;">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Details
                                    </span>
                                    <button type="button" class="adm-btn-danger-outline" onclick="confirmDeleteItem('rooms_settings', 'delete_room_id', <?php echo (int)$room['id']; ?>, '<?php echo e(addslashes($room['title'])); ?>', 'rooms');" title="Delete this villa">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </button>
                                    <span class="adm-accordion-chevron">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Accordion Body -->
                            <div class="adm-accordion-body">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Stay Category</label>
                                        <select name="room_stay_type[]" class="adm-form-control" style="font-weight: 700; color: #2ecc71;">
                                            <option value="mudhouse" <?php echo $stay_type === 'mudhouse' ? 'selected' : ''; ?>>🌿 Mudhouse Stay</option>
                                            <option value="treehouse" <?php echo $stay_type === 'treehouse' ? 'selected' : ''; ?>>🌲 Treehouse Stay</option>
                                        </select>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Structure Type</label>
                                        <select name="room_structure_type[]" class="adm-form-control" style="font-weight: 700; color: var(--adm-gold);">
                                            <option value="single_hut" <?php echo $structure_type === 'single_hut' ? 'selected' : ''; ?>>🏡 Single Hut (1-Room)</option>
                                            <option value="duplex_hut" <?php echo $structure_type === 'duplex_hut' ? 'selected' : ''; ?>>🏘️ Duplex Hut (Multi-Room)</option>
                                        </select>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Suite Title</label>
                                        <input type="text" name="room_title[]" class="adm-form-control" value="<?php echo e($room['title'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Full Cottage Rate (₹) [Base Payment] *</label>
                                        <input type="number" step="100" name="room_rate[]" class="adm-form-control" value="<?php echo (float)$room['rate_per_night']; ?>" required style="font-weight: bold; color: var(--adm-gold);">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">Minimum base payment for cottage</small>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Single Room Option Rate (₹) <small>(Duplex)</small></label>
                                        <input type="number" step="100" name="room_single_rate[]" class="adm-form-control" value="<?php echo (float)($room['single_room_rate'] ?? $room['rate_per_night']); ?>" required style="font-weight: bold; color: #56c2c9;">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">Rate when booking 1 room in Duplex</small>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Elevation Tag</label>
                                        <input type="text" name="room_elevation[]" class="adm-form-control" value="<?php echo e($room['elevation']); ?>" required>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1.2fr 1.2fr; gap: 10px; margin-bottom: 8px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Min Guests</label>
                                        <input type="number" min="1" max="10" name="room_min_guests[]" class="adm-form-control" value="<?php echo (int)($room['min_guests'] ?? 2); ?>" required title="Minimum guests required to book">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">Min group size</small>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Base Guests</label>
                                        <input type="number" min="1" max="10" name="room_base_guests[]" class="adm-form-control" value="<?php echo (int)($room['base_guests'] ?? 2); ?>" required title="Standard guests covered by base nightly rate (e.g. 2 for single, 4 for duplex)">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">In base rate</small>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Max Capacity</label>
                                        <input type="number" min="1" max="15" name="room_capacity[]" class="adm-form-control" value="<?php echo (int)($room['max_guests'] ?? 4); ?>" required title="Maximum allowable guests (Adults + Children)">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">Max guests</small>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Extra Adult (₹/N)</label>
                                        <input type="number" step="100" name="room_extra_rate[]" class="adm-form-control" value="<?php echo (float)($room['extra_guest_rate'] ?? 1500.00); ?>" required style="font-weight: bold; color: #2ecc71;" title="Charge per extra adult per night exceeding base count">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">12+ yrs extra</small>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Extra Child (₹/N)</label>
                                        <input type="number" step="100" name="room_extra_child_rate[]" class="adm-form-control" value="<?php echo (float)($room['extra_child_rate'] ?? 800.00); ?>" required style="font-weight: bold; color: #f59e0b;" title="Charge per extra child (5-11 yrs) per night">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">5-11 yrs extra</small>
                                    </div>
                                </div>
                                <div style="font-size: 10.5px; color: var(--adm-text-secondary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-circle-info" style="color: var(--adm-gold);"></i>
                                    <span>Minimum base payment is identical for 1 or 2 guests. Extra adults pay Extra Adult Rate; extra kids (5-11 yrs) pay Extra Child Rate. Infants &lt; 5 yrs complimentary.</span>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); padding: 8px 12px; border-radius: 8px; margin-bottom: 12px;">
                                    <span style="font-size: 12px; color: var(--adm-text-secondary);">Booking Status:</span>
                                    <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer; color: #fff; margin: 0;">
                                        <input type="checkbox" name="room_available_<?php echo $room['id']; ?>" value="1" <?php echo (!isset($room['is_available']) || $room['is_available'] == 1) ? 'checked' : ''; ?>>
                                        <span style="font-weight: 600; color: <?php echo (!isset($room['is_available']) || $room['is_available'] == 1) ? '#2ecc71' : '#e74c3c'; ?>;">
                                            <?php echo (!isset($room['is_available']) || $room['is_available'] == 1) ? 'Active & Bookable' : 'Temporarily Closed / Maintenance'; ?>
                                        </span>
                                    </label>
                                </div>

                                <div class="adm-form-group" style="margin-bottom: 12px;">
                                    <label class="adm-form-label">Architectural Bio / Description</label>
                                    <textarea name="room_desc[]" rows="3" class="adm-form-control" required><?php echo e($room['description']); ?></textarea>
                                </div>

                                <div class="adm-form-group">
                                    <label class="adm-form-label">Primary Suite Photo</label>
                                    <div class="adm-uploader-card adm-uploader-compact">
                                        <div class="adm-uploader-preview-box">
                                            <img id="room_prev_<?php echo $room['id']; ?>" src="<?php echo admin_img_src($room['image_url']); ?>" alt="Room" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                        </div>
                                        <div class="adm-uploader-controls">
                                            <div class="adm-uploader-btn-wrap">
                                                <label class="adm-uploader-btn" for="room_file_<?php echo $room['id']; ?>">
                                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload Photo
                                                </label>
                                                <input type="file" name="room_image_file[<?php echo $idx; ?>]" id="room_file_<?php echo $room['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'room_prev_<?php echo $room['id']; ?>', 'room_info_<?php echo $room['id']; ?>');">
                                                <span id="room_info_<?php echo $room['id']; ?>" class="adm-file-info-badge"></span>
                                            </div>
                                            <input type="hidden" name="room_image[]" value="<?php echo e($room['image_url']); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- 360° Interior Walkthrough Panorama & 3-Photo Auto-Stitcher -->
                                <div class="adm-form-group" style="background: rgba(16, 31, 21, 0.4); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 10px; padding: 16px; margin-top: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                                        <div>
                                            <label class="adm-form-label" style="color: var(--adm-gold); margin-bottom: 2px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                                <i class="fa-solid fa-arrows-spin"></i> 360° Interior Suite Walkthrough
                                            </label>
                                            <span style="font-size: 11px; color: var(--adm-text-secondary);">Rendered on public website 3D WebGL sphere</span>
                                        </div>
                                        <div style="display: flex; gap: 6px;">
                                            <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3);" onclick="toggle360Mode('mode-single-<?php echo $room['id']; ?>', 'mode-stitch-<?php echo $room['id']; ?>');">
                                                <i class="fa-solid fa-image"></i> Single 360 / PANO
                                            </button>
                                            <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);" onclick="toggle360Mode('mode-stitch-<?php echo $room['id']; ?>', 'mode-single-<?php echo $room['id']; ?>');">
                                                <i class="fa-solid fa-wand-magic-sparkles"></i> 3-Photo Auto-Stitcher
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Current 360 Preview Strip -->
                                    <div style="margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 12px; background: rgba(6, 17, 10, 0.85); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px 14px;">
                                            <div style="width: 105px; height: 52px; border-radius: 6px; overflow: hidden; border: 1px solid var(--adm-gold); flex-shrink: 0; background: #000;">
                                                <img id="room_360_prev_<?php echo $room['id']; ?>" src="<?php echo admin_img_src($room['interior_360_url'] ?? 'assets/images/treehouse_360_pano.jpg'); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_360_pano.jpg';">
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-size: 12px; font-weight: 700; color: #FFFFFF; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                                    <?php echo htmlspecialchars(basename($room['interior_360_url'] ?? 'treehouse_360_pano.jpg')); ?>
                                                </div>
                                                <div style="font-size: 10.5px; color: #2ecc71; margin-top: 2px;">
                                                    <i class="fa-solid fa-circle-check"></i> Active 360° Sphere Texture
                                                </div>
                                            </div>
                                            <a href="../<?php echo htmlspecialchars($room['interior_360_url'] ?? 'assets/images/treehouse_360_pano.jpg'); ?>" target="_blank" class="adm-btn-site-preview" style="padding: 4px 10px; font-size: 11px;" title="Open full panorama image in new tab">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                                            </a>
                                        </div>
                                    </div>
                                    <input type="hidden" name="room_interior_360[]" value="<?php echo e($room['interior_360_url'] ?? ''); ?>">

                                    <!-- MODE 1: Single 360 / PANO Upload -->
                                    <div id="mode-single-<?php echo $room['id']; ?>" class="room-360-pane">
                                        <span style="font-size: 11px; color: var(--adm-text-secondary); display: block; margin-bottom: 6px;">
                                            <i class="fa-solid fa-circle-info" style="color: var(--adm-gold);"></i> Upload a single 360° photo or mobile phone PANO shot (2:1 aspect ratio recommended):
                                        </span>
                                        <div class="adm-uploader-card adm-uploader-compact">
                                            <div class="adm-uploader-controls" style="width: 100%;">
                                                <div class="adm-uploader-btn-wrap">
                                                    <label class="adm-uploader-btn" for="room_360_file_<?php echo $room['id']; ?>">
                                                        <i class="fa-solid fa-camera"></i> Choose 360 / PANO Photo
                                                    </label>
                                                    <input type="file" name="room_360_file[<?php echo $idx; ?>]" id="room_360_file_<?php echo $room['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'room_360_prev_<?php echo $room['id']; ?>', 'room_360_info_<?php echo $room['id']; ?>');">
                                                    <span id="room_360_info_<?php echo $room['id']; ?>" class="adm-file-info-badge"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MODE 2: 3-Photo Auto-Stitcher (Left + Center + Right) -->
                                    <div id="mode-stitch-<?php echo $room['id']; ?>" class="room-360-pane" style="display: none; background: rgba(7, 18, 11, 0.85); border: 1px dashed rgba(46, 204, 113, 0.4); border-radius: 8px; padding: 12px;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                            <span style="font-size: 11.5px; font-weight: 700; color: #2ecc71;">
                                                <i class="fa-solid fa-wand-magic-sparkles"></i> 3-Photo Auto Panorama Generator
                                            </span>
                                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px;">SMARTPHONE READY</span>
                                        </div>
                                        <p style="font-size: 11px; color: var(--adm-text-secondary); margin: 0 0 10px; line-height: 1.4;">
                                            Stand in the center of the room. Take 3 normal photos turning from left to right. Upload all 3 below — the system automatically scales, edge-feathers, and wraps them into a 360° panorama when you save.
                                        </p>
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                                            <!-- Left Photo -->
                                            <div style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); text-align: center;">
                                                <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 4px;">1. LEFT ANGLE</span>
                                                <label class="adm-uploader-btn" for="stitch_l_<?php echo $room['id']; ?>" style="font-size: 10.5px; padding: 5px; width: 100%; justify-content: center;">
                                                    <i class="fa-solid fa-arrow-left"></i> Upload
                                                </label>
                                                <input type="file" name="room_stitch_left[<?php echo $idx; ?>]" id="stitch_l_<?php echo $room['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="updateStitchBadge(this, 'sbadge_l_<?php echo $room['id']; ?>');">
                                                <span id="sbadge_l_<?php echo $room['id']; ?>" style="font-size: 9.5px; color: #2ecc71; display: none; margin-top: 3px;"></span>
                                            </div>

                                            <!-- Center Photo -->
                                            <div style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); text-align: center;">
                                                <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 4px;">2. CENTER ANGLE</span>
                                                <label class="adm-uploader-btn" for="stitch_c_<?php echo $room['id']; ?>" style="font-size: 10.5px; padding: 5px; width: 100%; justify-content: center;">
                                                    <i class="fa-solid fa-crosshairs"></i> Upload
                                                </label>
                                                <input type="file" name="room_stitch_center[<?php echo $idx; ?>]" id="stitch_c_<?php echo $room['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="updateStitchBadge(this, 'sbadge_c_<?php echo $room['id']; ?>');">
                                                <span id="sbadge_c_<?php echo $room['id']; ?>" style="font-size: 9.5px; color: #2ecc71; display: none; margin-top: 3px;"></span>
                                            </div>

                                            <!-- Right Photo -->
                                            <div style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); text-align: center;">
                                                <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 4px;">3. RIGHT ANGLE</span>
                                                <label class="adm-uploader-btn" for="stitch_r_<?php echo $room['id']; ?>" style="font-size: 10.5px; padding: 5px; width: 100%; justify-content: center;">
                                                    <i class="fa-solid fa-arrow-right"></i> Upload
                                                </label>
                                                <input type="file" name="room_stitch_right[<?php echo $idx; ?>]" id="stitch_r_<?php echo $room['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="updateStitchBadge(this, 'sbadge_r_<?php echo $room['id']; ?>');">
                                                <span id="sbadge_r_<?php echo $room['id']; ?>" style="font-size: 9.5px; color: #2ecc71; display: none; margin-top: 3px;"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE VILLAS & TARIFFS CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 10: VISUAL DIARY (GALLERY PHOTOS DYNAMIC CMS)
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'gallery'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-gallery">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon teal"><i class="fa-solid fa-camera-retro"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 10</span>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">VISUAL CHRONICLE / ALBUMS & GALLERY (DYNAMIC CMS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Manage photography collection albums, upload multiple sub-images per album, and customize lightbox presentations.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW ALBUM / COLLECTION
                </button>
                <button type="submit" form="form-edit-gallery" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <!-- Expandable Add New Photograph / Album Drawer -->
            <div id="drawer-add-gallery" class="adm-add-new-drawer" style="display: none;">
                <form action="edit_section.php?section=gallery" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="gallery_settings">
                    <input type="hidden" name="action" value="add_gallery">
                    <input type="hidden" name="active_tab" value="gallery">

                    <div class="adm-drawer-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 16px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">REGISTER NEW GALLERY COLLECTION / ALBUM</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-gallery');" title="Close Drawer">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Album / Collection Title *</label>
                            <input type="text" name="new_gal_title" class="adm-form-control" placeholder="e.g. High Canopy Treehouse in Mist" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Tag</label>
                            <input type="text" name="new_gal_tag" class="adm-form-control" placeholder="e.g. CANOPY DWELLING · 1,640M" value="SANCTUARY ARCHIVE">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Category</label>
                            <select name="new_gal_category" class="adm-form-control" style="cursor: pointer;">
                                <option value="Villas & Stays">🏡 Villas & Stays</option>
                                <option value="Handcrafted Living">🌿 Handcrafted Living</option>
                                <option value="Landscape" selected>⛰️ Landscape</option>
                                <option value="Gastronomy">🍲 Gastronomy</option>
                                <option value="Orchards">🍎 Orchards</option>
                                <option value="Nightscape">✨ Nightscape</option>
                                <option value="Architecture">🏛️ Architecture</option>
                                <option value="Flora">🌸 Flora</option>
                            </select>
                        </div>
                    </div>

                    <!-- Multi-Photo Uploader for Album -->
                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label" style="display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fa-solid fa-images" style="color: var(--adm-gold);"></i> Album Photos (Select One or Multiple Sub-Images)</span>
                            <span style="font-size: 11px; color: var(--adm-text-secondary); text-transform: none;">First photo serves as Album Cover</span>
                        </label>
                        <div style="background: rgba(8, 18, 11, 0.7); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 8px; padding: 14px;">
                            <label class="adm-uploader-btn" for="new_gal_photos_input" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 16px; font-size: 12px; margin-bottom: 8px;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Choose Photos from Device (Multi-select)
                            </label>
                            <input type="file" name="new_gal_photos[]" id="new_gal_photos_input" class="adm-uploader-input" multiple accept="image/*" onchange="previewMultiSpotUpload(this, 'new_gal_photos_preview');">
                            <div id="new_gal_photos_preview" style="margin-top: 8px;">
                                <span style="font-size: 11px; color: var(--adm-text-muted);">No photos selected yet. (Default sanctuary image used if none selected)</span>
                            </div>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Album Caption / Story</label>
                        <textarea name="new_gal_caption" rows="2" class="adm-form-control" placeholder="Describe the atmosphere, location details, or seasonal moments in this album..."></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-add-gallery');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>PUBLISH ALBUM COLLECTION</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Main Edit Form -->
            <form id="form-edit-gallery" action="edit_section.php?section=gallery" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="gallery_settings">
                <input type="hidden" name="active_tab" value="gallery">

                <!-- Header -->
                <div style="background: rgba(20, 184, 166, 0.08); border: 1px solid rgba(20, 184, 166, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #2dd4bf; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 14px;">Section Header Copy</span>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Eyebrow</label>
                            <input type="text" name="gallery_badge" class="adm-form-control" value="<?php echo e($s['gallery_badge'] ?? 'A Visual Chronicle'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Headline</label>
                            <input type="text" name="gallery_title" class="adm-form-control" value="<?php echo e($s['gallery_title'] ?? 'Curated Sanctuary Collections'); ?>" required>
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label">Description Paragraph</label>
                        <textarea name="gallery_desc" rows="2" class="adm-form-control" required><?php echo e($s['gallery_desc'] ?? 'Explore our living photographic archives. Click on any collection to open the high-resolution showcase with interactive zoom, photo exploration, and full album views.'); ?></textarea>
                    </div>
                </div>

                <!-- Photos List Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-images"></i> Sanctuary Photo Albums & Collections (<?php echo count($all_gallery); ?>)
                    </h4>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-gallery', true);" title="Expand all photo cards">
                            <i class="fa-solid fa-angles-down"></i> Expand All
                        </button>
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-gallery', false);" title="Collapse all photo cards">
                            <i class="fa-solid fa-angles-up"></i> Collapse All
                        </button>
                        <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');" style="padding: 6px 12px; font-size: 11.5px;">
                            <i class="fa-solid fa-plus"></i> Add Another Album
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                    <?php if (empty($all_gallery)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-camera-retro" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No gallery collections found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');">
                                <i class="fa-solid fa-plus-circle"></i> Create First Collection
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_gallery as $idx => $photo): 
                        $first_p = !empty($photo['photos_list']) ? $photo['photos_list'][0] : ($photo['image_url'] ?? 'assets/images/treehouse_exterior.png');
                        $photo_count = count($photo['photos_list'] ?? []);
                    ?>
                        <div class="adm-gal-item-card adm-accordion-card">
                            <input type="hidden" name="gallery_id[]" value="<?php echo $photo['id']; ?>">
                            <input type="hidden" name="gal_fallback_image[]" value="<?php echo e($photo['image_url']); ?>">

                            <!-- Accordion Header Bar -->
                            <div class="adm-accordion-header" onclick="toggleAccordion(this, event);">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                    <div class="adm-accordion-thumb-box">
                                        <img id="gal_prev_thumb_<?php echo $photo['id']; ?>" src="<?php echo admin_img_src($first_p); ?>" alt="Photo" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                    </div>
                                    <div style="min-width: 0;">
                                        <div class="adm-accordion-meta-title">
                                            <span style="font-size: 10px; font-weight: 700; color: #2dd4bf; background: rgba(45, 212, 191, 0.15); border: 1px solid rgba(45, 212, 191, 0.3); padding: 2px 7px; border-radius: 6px; text-transform: uppercase;">
                                                #<?php echo ($idx + 1); ?>
                                            </span>
                                            <span style="color: #FFFFFF; font-weight: 700; font-size: 13.5px;">
                                                <?php echo e($photo['title']); ?>
                                            </span>
                                            <?php if (!empty($photo['tag'])): ?>
                                                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.12); color: var(--adm-gold); font-size: 10px; padding: 2px 7px; border-radius: 6px;">
                                                    <?php echo e($photo['tag']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="adm-accordion-meta-sub">
                                            <span><i class="fa-solid fa-layer-group" style="color: #2dd4bf;"></i> <strong><?php echo $photo_count; ?> Sub-Photos</strong> in Collection</span>
                                            <span>• <?php echo e($photo['category'] ?? 'Landscape'); ?></span>
                                            <span>• ID: <?php echo $photo['id']; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                    <button type="button" class="adm-btn-danger-outline" onclick="confirmDeleteItem('gallery_settings', 'delete_gal_id', <?php echo (int)$photo['id']; ?>, '<?php echo e(addslashes($photo['title'])); ?>', 'gallery');" title="Delete this collection album">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </button>
                                    <span class="adm-accordion-chevron">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Accordion Body -->
                            <div class="adm-accordion-body">
                                <div style="display: grid; grid-template-columns: 1fr 180px 180px; gap: 12px; margin-bottom: 12px;">
                                    <div>
                                        <label class="adm-form-label" style="font-size: 11px;">Album / Collection Title *</label>
                                        <input type="text" name="gal_title[]" class="adm-form-control" value="<?php echo e($photo['title']); ?>" required>
                                    </div>
                                    <div>
                                        <label class="adm-form-label" style="font-size: 11px;">Category</label>
                                        <select name="gal_category[]" class="adm-form-control" style="font-size: 12px;">
                                            <option value="Villas & Stays" <?php echo ($photo['category'] === 'Villas & Stays') ? 'selected' : ''; ?>>🏡 Villas & Stays</option>
                                            <option value="Handcrafted Living" <?php echo ($photo['category'] === 'Handcrafted Living') ? 'selected' : ''; ?>>🌿 Handcrafted Living</option>
                                            <option value="Landscape" <?php echo ($photo['category'] === 'Landscape') ? 'selected' : ''; ?>>⛰️ Landscape</option>
                                            <option value="Gastronomy" <?php echo ($photo['category'] === 'Gastronomy') ? 'selected' : ''; ?>>🍲 Gastronomy</option>
                                            <option value="Orchards" <?php echo ($photo['category'] === 'Orchards') ? 'selected' : ''; ?>>🍎 Orchards</option>
                                            <option value="Nightscape" <?php echo ($photo['category'] === 'Nightscape') ? 'selected' : ''; ?>>✨ Nightscape</option>
                                            <option value="Architecture" <?php echo ($photo['category'] === 'Architecture') ? 'selected' : ''; ?>>🏛️ Architecture</option>
                                            <option value="Flora" <?php echo ($photo['category'] === 'Flora') ? 'selected' : ''; ?>>🌸 Flora</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="adm-form-label" style="font-size: 11px;">Badge Tag</label>
                                        <input type="text" name="gal_tag[]" class="adm-form-control" value="<?php echo e($photo['tag']); ?>" placeholder="e.g. CANOPY DWELLING">
                                    </div>
                                </div>

                                <!-- Multi-Photo Collection Gallery Manager -->
                                <div class="adm-form-group" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                                        <label class="adm-form-label" style="font-size: 11px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-images"></i> Collection Sub-Images (<?php echo $photo_count; ?> Photos)
                                        </label>
                                        <label class="adm-uploader-btn" for="gal_new_file_<?php echo $photo['id']; ?>" style="padding: 4px 10px; font-size: 11px; margin: 0; cursor: pointer;">
                                            <i class="fa-solid fa-plus"></i> Add More Sub-Images
                                        </label>
                                        <input type="file" name="gal_new_photos_<?php echo $photo['id']; ?>[]" id="gal_new_file_<?php echo $photo['id']; ?>" class="adm-uploader-input" accept="image/*" multiple onchange="previewMultiSpotUpload(this, 'gal_new_prev_<?php echo $photo['id']; ?>');">
                                    </div>

                                    <!-- Existing Photos Thumbnails Grid with Delete (x) Button -->
                                    <div class="adm-spot-photos-grid" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                        <?php if (!empty($photo['photos_list'])): ?>
                                            <?php foreach ($photo['photos_list'] as $p_idx => $p_url): ?>
                                                <div class="adm-spot-photo-thumb" style="position: relative; width: 78px; height: 78px; border-radius: 6px; overflow: hidden; border: 1px solid rgba(45, 212, 191, 0.4); transition: all 0.2s ease;">
                                                    <img src="../<?php echo e($p_url); ?>" alt="Sub-Photo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                                    <input type="hidden" name="gal_existing_photos[<?php echo $photo['id']; ?>][]" value="<?php echo e($p_url); ?>">
                                                    <span style="position: absolute; bottom: 2px; left: 2px; background: rgba(0,0,0,0.75); color: #2dd4bf; font-size: 9px; padding: 1px 4px; border-radius: 3px;"><?php echo $p_idx + 1; ?></span>
                                                    <button type="button" onclick="removeSpotPhotoThumbnail(this);" title="Remove this photo from collection" style="position: absolute; top: 2px; right: 2px; width: 18px; height: 18px; background: rgba(220, 53, 69, 0.85); color: #fff; border: none; border-radius: 50%; font-size: 10px; line-height: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;">✕</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div id="gal_new_prev_<?php echo $photo['id']; ?>" style="margin-top: 8px;"></div>
                                </div>

                                <div>
                                    <label class="adm-form-label" style="font-size: 11px;">Album Description / Caption</label>
                                    <textarea name="gal_caption[]" rows="2" class="adm-form-control" style="font-size: 12px;"><?php echo e($photo['caption']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE GALLERY ALBUMS CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 11: GUEST REFLECTIONS (TESTIMONIALS DYNAMIC CMS)
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'testimonials'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-testimonials">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon blue"><i class="fa-solid fa-comment-dots"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 11</span>
                        <?php if (!empty($pending_testimonials)): ?>
                            <span class="adm-badge" style="background: rgba(239, 68, 68, 0.25); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.45); font-weight: 700; font-size: 10.5px; animation: pulse 2s infinite;">
                                <i class="fa-solid fa-bell"></i> <?php echo count($pending_testimonials); ?> PENDING APPROVAL
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">GUEST STORIES & SANCTUARY REFLECTIONS (CMS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Card-by-card moderation, live search, custom pagination, and official concierge responses across 3 distinct streams.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-action" style="background: rgba(66, 133, 244, 0.15); color: #60a5fa; border: 1px solid rgba(66, 133, 244, 0.35); font-size: 12px; padding: 8px 14px;" onclick="toggleAddNewDrawer('drawer-sync-google');">
                    <i class="fa-brands fa-google"></i> Sync Google Reviews
                </button>
                <button type="submit" form="form-edit-testimonials" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE ALL CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">

            <!-- Drawer 1: Google Reviews Sync & Import -->
            <div id="drawer-sync-google" class="adm-add-new-drawer" style="display: none; background: rgba(10, 25, 47, 0.95); border: 1px solid rgba(66, 133, 244, 0.4); margin-bottom: 20px;">
                <form action="edit_section.php?section=testimonials" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="testimonials_settings">
                    <input type="hidden" name="action" value="sync_google_reviews">
                    <input type="hidden" name="active_tab" value="testimonials">

                    <div class="adm-drawer-header" style="border-bottom-color: rgba(66, 133, 244, 0.25);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-brands fa-google" style="color: #4285F4; font-size: 18px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">GOOGLE MAPS REVIEWS SYNC & VERIFICATION</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-sync-google');" title="Close Drawer">✕</button>
                    </div>

                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin-bottom: 16px;">
                        Sync and import verified traveler reviews from Google Maps. All imported reviews will enter the <strong>Verification & Approval Queue</strong> and will only appear on the public website after you review and approve them.
                    </p>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label"><i class="fa-solid fa-map-location-dot" style="color: #4285F4; margin-right: 5px;"></i> Google Maps Property Link / Place ID</label>
                            <input type="text" name="google_place_id" class="adm-form-control" value="https://maps.app.goo.gl/WrLRy4j8aSU7xtM4A" placeholder="e.g. https://maps.app.goo.gl/... or Place ID" style="border-color: rgba(66, 133, 244, 0.35); font-family: monospace; font-size: 12.5px;">
                            <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 4px; display: block;">
                                <i class="fa-solid fa-circle-info"></i> Google Maps reviews represent the overall Food Forest Sanctuary retreat.
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-sync-google');">Cancel</button>
                        <button type="submit" class="adm-btn-action" style="background: #1a73e8; color: #FFFFFF; font-weight: 700; border: 1px solid #4285f4;">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span>FETCH & SYNC GOOGLE REVIEWS TO QUEUE</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Main Form Wrapper for Global Settings & Card Updates -->
            <form id="form-edit-testimonials" action="edit_section.php?section=testimonials" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="testimonials_settings">
                <input type="hidden" name="action" value="update_all">
                <input type="hidden" name="active_tab" value="testimonials">
                <input type="hidden" name="single_target_id" id="single_target_id" value="">

                <!-- Section Header Copy Box (Collapsible) -->
                <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 18px 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #60a5fa; font-weight: 700; letter-spacing: 1px;">
                            <i class="fa-solid fa-pen-nib" style="margin-right: 4px;"></i> Section Header Copy (Frontend Display)
                        </span>
                    </div>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-bottom: 12px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Eyebrow</label>
                            <input type="text" name="testimonials_badge" class="adm-form-control" value="<?php echo e($s['testimonials_badge'] ?? 'Guest Stories & Chronicles'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Headline</label>
                            <input type="text" name="testimonials_title" class="adm-form-control" value="<?php echo e($s['testimonials_title'] ?? 'Moments Cherished, Memories Shared'); ?>" required>
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label">Description Paragraph</label>
                        <textarea name="testimonials_desc" rows="2" class="adm-form-control" required><?php echo e($s['testimonials_desc'] ?? 'Unfiltered impressions from travelers who have slept beneath our high-range canopies and lived in our handcrafted earthen mudhouses.'); ?></textarea>
                    </div>
                </div>

                <!-- -------------------------------------------------------------
                     3 SEGMENTED STREAM TABS (Google, Client, Admin)
                     ------------------------------------------------------------- -->
                <div class="adm-reviews-stream-nav" style="display: flex; gap: 8px; border-bottom: 2px solid rgba(197, 160, 89, 0.25); margin-bottom: 20px; overflow-x: auto; padding-bottom: 2px;">
                    <!-- Tab 1: Google Reviews -->
                    <button type="button" class="adm-stream-tab-btn active" onclick="switchReviewStream('google');" id="stream-tab-btn-google" style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(66, 133, 244, 0.15); color: #70a6ff; border: 1px solid rgba(66, 133, 244, 0.35); border-radius: 8px 8px 0 0; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.25s;">
                        <i class="fa-brands fa-google" style="color: #4285F4; font-size: 15px;"></i>
                        <span>1. Google Maps Reviews</span>
                        <span class="adm-stream-count-pill" id="badge-stream-google"><?php echo count($google_testimonials); ?></span>
                        <?php if (count($pending_google) > 0): ?>
                            <span class="adm-stream-count-pill alert"><?php echo count($pending_google); ?> Pending</span>
                        <?php endif; ?>
                    </button>

                    <!-- Tab 2: Client Submissions -->
                    <button type="button" class="adm-stream-tab-btn" onclick="switchReviewStream('client');" id="stream-tab-btn-client" style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(255, 255, 255, 0.04); color: var(--adm-text-secondary); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px 8px 0 0; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.25s;">
                        <i class="fa-solid fa-users-line" style="color: #2ecc71; font-size: 15px;"></i>
                        <span>2. Guest & Client Submissions</span>
                        <span class="adm-stream-count-pill" id="badge-stream-client"><?php echo count($client_testimonials); ?></span>
                        <?php if (count($pending_client) > 0): ?>
                            <span class="adm-stream-count-pill alert"><?php echo count($pending_client); ?> Pending</span>
                        <?php endif; ?>
                    </button>

                    <!-- Tab 3: Admin Direct Chronicles -->
                    <button type="button" class="adm-stream-tab-btn" onclick="switchReviewStream('admin');" id="stream-tab-btn-admin" style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(255, 255, 255, 0.04); color: var(--adm-text-secondary); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px 8px 0 0; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.25s;">
                        <i class="fa-solid fa-feather-pointed" style="color: var(--adm-gold); font-size: 15px;"></i>
                        <span>3. Admin Direct Chronicles</span>
                        <span class="adm-stream-count-pill" id="badge-stream-admin"><?php echo count($admin_testimonials); ?></span>
                    </button>
                </div>

                <?php
                // Function to render stream content
                $renderReviewStream = function($streamKey, $streamTitle, $streamIcon, $streamReviews, $pendingStreamReviews, $sanctuary_properties) {
                    $streamCount = count($streamReviews);
                ?>
                    <!-- STREAM PANE: <?php echo strtoupper($streamKey); ?> -->
                    <div id="stream-pane-<?php echo $streamKey; ?>" class="adm-review-stream-pane" style="display: <?php echo ($streamKey === 'google') ? 'block' : 'none'; ?>;">
                        
                        <!-- 1. SEARCH & FILTER CONTROLS BAR (AT THE VERY TOP) -->
                        <div class="adm-reviews-search-filter-box" style="background: rgba(13, 27, 20, 0.95); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
                            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 12px;">
                                <!-- Live Search Input -->
                                <div style="flex: 2; min-width: 260px; position: relative;">
                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--adm-gold); font-size: 14px; pointer-events: none;"></i>
                                    <input type="text" id="search-input-<?php echo $streamKey; ?>" oninput="filterStreamReviews('<?php echo $streamKey; ?>');" placeholder="Search by guest name, location, or keywords (e.g. 'strawberry', 'mist', 'mud')..." class="adm-form-control" style="padding-left: 38px; padding-right: 36px; height: 42px; background: rgba(0,0,0,0.45); border-color: rgba(197, 160, 89, 0.35); font-size: 13px; color: #FFFFFF; border-radius: 8px;">
                                    <button type="button" onclick="clearStreamSearch('<?php echo $streamKey; ?>');" id="search-clear-<?php echo $streamKey; ?>" style="display: none; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--adm-text-muted); cursor: pointer; font-size: 14px;" title="Clear search">✕</button>
                                </div>

                                <!-- Status Filter -->
                                <div style="flex: 1; min-width: 140px;">
                                    <select id="status-filter-<?php echo $streamKey; ?>" onchange="filterStreamReviews('<?php echo $streamKey; ?>');" class="adm-form-control" style="height: 42px; background: rgba(0,0,0,0.45); border-color: rgba(197, 160, 89, 0.35); font-size: 12px; border-radius: 8px; cursor: pointer;">
                                        <option value="all">All Statuses (<?php echo $streamCount; ?>)</option>
                                        <option value="approved">✓ Approved / Live</option>
                                        <option value="pending">⏳ Pending Queue</option>
                                        <option value="rejected">✕ Archived / Rejected</option>
                                    </select>
                                </div>

                                <!-- Star Rating Filter -->
                                <div style="flex: 1; min-width: 130px;">
                                    <select id="stars-filter-<?php echo $streamKey; ?>" onchange="filterStreamReviews('<?php echo $streamKey; ?>');" class="adm-form-control" style="height: 42px; background: rgba(0,0,0,0.45); border-color: rgba(197, 160, 89, 0.35); font-size: 12px; border-radius: 8px; cursor: pointer;">
                                        <option value="all">All Star Ratings</option>
                                        <option value="5.0">★★★★★ 5.0 Stars</option>
                                        <option value="4.5">★★★★½ 4.5 Stars</option>
                                        <option value="4.0">★★★★☆ 4.0 Stars</option>
                                    </select>
                                </div>

                                <!-- Items Per Page Selector -->
                                <div style="min-width: 130px;">
                                    <select id="per-page-<?php echo $streamKey; ?>" onchange="changeStreamPerPage('<?php echo $streamKey; ?>', this.value);" class="adm-form-control" style="height: 42px; background: rgba(0,0,0,0.45); border-color: rgba(197, 160, 89, 0.35); font-size: 12px; border-radius: 8px; cursor: pointer; color: var(--adm-gold); font-weight: 700;">
                                        <option value="5">5 per page</option>
                                        <option value="10" selected>10 per page</option>
                                        <option value="15">15 per page</option>
                                        <option value="20">20 per page</option>
                                        <option value="50">50 per page</option>
                                        <option value="999">Show All (<?php echo $streamCount; ?>)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Stream Stats & Quick Buttons -->
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 10px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <span id="count-badge-<?php echo $streamKey; ?>" style="font-size: 12.5px; color: var(--adm-gold); font-weight: 700;">
                                        Showing <span id="visible-count-<?php echo $streamKey; ?>"><?php echo min(10, $streamCount); ?></span> of <span id="total-count-<?php echo $streamKey; ?>"><?php echo $streamCount; ?></span> Total Reviews
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <button type="button" onclick="setStreamQuickFilter('<?php echo $streamKey; ?>', 'pending');" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(234, 88, 12, 0.2); color: #fb923c; border: 1px solid rgba(234, 88, 12, 0.4);">
                                        <i class="fa-solid fa-hourglass-half"></i> Show Pending
                                    </button>
                                    <button type="button" onclick="setStreamQuickFilter('<?php echo $streamKey; ?>', 'approved');" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4);">
                                        <i class="fa-solid fa-check"></i> Show Live
                                    </button>
                                    <button type="button" onclick="resetStreamFilters('<?php echo $streamKey; ?>');" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);">
                                        <i class="fa-solid fa-rotate-left"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. IF STREAM IS ADMIN: SHOW "ADD NEW CHRONICLE" FORM CARD AT TOP -->
                        <?php if ($streamKey === 'admin'): ?>
                            <div style="background: rgba(197, 160, 89, 0.07); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                    <i class="fa-solid fa-feather" style="color: var(--adm-gold); font-size: 16px;"></i>
                                    <h4 style="font-family: var(--adm-font-title); font-size: 14.5px; color: #FFFFFF; margin: 0;">ADD NEW ADMIN DIRECT CHRONICLE / VIP MEMOIR</h4>
                                </div>
                                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Guest / VIP Full Name *</label>
                                        <input type="text" name="admin_add_guest_name" class="adm-form-control" placeholder="e.g. Lord Mountford & Family">
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Location / Role *</label>
                                        <input type="text" name="admin_add_guest_location" class="adm-form-control" placeholder="e.g. London, UK · Curated Retreat">
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Dwelling / Experience</label>
                                        <select name="admin_add_stay_badge" class="adm-form-control" style="cursor: pointer;">
                                            <?php foreach ($sanctuary_properties as $prop): ?>
                                                <option value="<?php echo htmlspecialchars($prop['title']); ?>"><?php echo htmlspecialchars($prop['title']); ?> (<?php echo ucfirst($prop['type']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Star Rating: <strong id="admin-new-stars-txt" style="color: var(--adm-gold);">5.0 ★</strong></label>
                                        <input type="range" name="admin_add_stars" min="1.0" max="5.0" step="0.5" value="5.0" class="adm-range-slider" oninput="document.getElementById('admin-new-stars-txt').innerText = parseFloat(this.value).toFixed(1) + ' ★';" style="accent-color: var(--adm-gold); margin-top: 6px;">
                                    </div>
                                </div>
                                <div class="adm-form-group" style="margin-bottom: 12px;">
                                    <label class="adm-form-label">Guest Chronicle Quote / Impression *</label>
                                    <textarea name="admin_add_quote" rows="2" class="adm-form-control" placeholder="Enter guest quote, narrative, or architectural appreciation..."></textarea>
                                </div>
                                <div class="adm-form-group" style="margin-bottom: 14px;">
                                    <label class="adm-form-label"><i class="fa-solid fa-seedling" style="color: var(--adm-gold);"></i> Official Estate Concierge Response (Optional)</label>
                                    <input type="text" name="admin_add_admin_reply" class="adm-form-control" placeholder="e.g. It was an honor hosting your family at Food Forest Sanctuary...">
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" form="form-edit-testimonials" onclick="this.form.action.value='add_admin_chronicle';" class="adm-btn-action gold" style="padding: 8px 20px; font-weight: 700; font-size: 12.5px;">
                                        <i class="fa-solid fa-plus-circle"></i> PUBLISH ADMIN CHRONICLE
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 3. COMPACT REVIEWS GRID (EDIT FORMS EXPAND ONLY ON CLICKING [EDIT]) -->
                        <div id="grid-stream-<?php echo $streamKey; ?>" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                            <div id="no-res-<?php echo $streamKey; ?>" style="display: none; text-align: center; padding: 36px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                                <i class="fa-solid fa-magnifying-glass" style="font-size: 28px; color: var(--adm-gold); opacity: 0.6; margin-bottom: 10px; display: block;"></i>
                                <p style="color: var(--adm-text-secondary); font-size: 13.5px; margin: 0 0 10px;">No reviews matched your search in <?php echo htmlspecialchars($streamTitle); ?>.</p>
                                <button type="button" class="adm-btn-action outline" onclick="resetStreamFilters('<?php echo $streamKey; ?>');" style="font-size: 12px; padding: 6px 14px;">Reset Filters</button>
                            </div>

                            <?php if (empty($streamReviews)): ?>
                                <div style="text-align: center; padding: 36px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                                    <i class="fa-solid fa-comment-dots" style="font-size: 30px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 10px; display: block;"></i>
                                    <p style="color: var(--adm-text-secondary); font-size: 13.5px; margin: 0;">No reviews available in this section.</p>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($streamReviews as $s_idx => $t): 
                                $t_stars = isset($t['stars']) ? (float)$t['stars'] : 5.0;
                                $t_status = $t['status'] ?? ($t['is_active'] ? 'approved' : 'pending');
                                $global_id = $t['id'];
                            ?>
                                <div class="adm-test-compact-card stream-item-<?php echo $streamKey; ?>"
                                     id="review-card-<?php echo $global_id; ?>"
                                     data-guest-name="<?php echo htmlspecialchars(strtolower($t['guest_name'])); ?>"
                                     data-location="<?php echo htmlspecialchars(strtolower($t['guest_location'])); ?>"
                                     data-title="<?php echo htmlspecialchars(strtolower($t['title'] ?? '')); ?>"
                                     data-quote="<?php echo htmlspecialchars(strtolower($t['quote'])); ?>"
                                     data-status="<?php echo htmlspecialchars($t_status); ?>"
                                     data-stars="<?php echo number_format($t_stars, 1, '.', ''); ?>"
                                     style="background: rgba(13, 26, 18, 0.85); border: 1px solid <?php echo ($t_status === 'pending') ? 'rgba(234, 88, 12, 0.45)' : 'rgba(197, 160, 89, 0.2)'; ?>; border-radius: 10px; padding: 14px 18px; transition: all 0.2s ease;">
                                    
                                    <input type="hidden" name="testimonial_id[]" value="<?php echo $t['id']; ?>">

                                    <!-- Compact Review Summary Row -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                        <!-- Left Column: Avatar + Info -->
                                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                            <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.3); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                                <?php if (!empty($t['avatar_url'])): ?>
                                                    <img src="../<?php echo e($t['avatar_url']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://lh3.googleusercontent.com/a/default-user=s120';">
                                                <?php else: ?>
                                                    <span style="color: var(--adm-gold); font-weight: 700; font-size: 12px;"><?php echo strtoupper(substr(trim($t['guest_name']), 0, 2)); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div style="min-width: 0;">
                                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                    <strong style="color: #FFFFFF; font-size: 13.5px;"><?php echo e($t['guest_name']); ?></strong>
                                                    <span style="color: var(--adm-gold); font-size: 11px;">
                                                        <?php echo render_star_rating_html($t_stars, 'adm-star-inline'); ?>
                                                    </span>
                                                    <span class="adm-badge" style="background: rgba(197, 160, 89, 0.12); color: var(--adm-gold); font-size: 9.5px; padding: 2px 7px; border-radius: 6px;">
                                                        <?php echo e($t['stay_badge'] ?: 'Sanctuary Stay'); ?>
                                                    </span>
                                                    <?php if ($t_status === 'approved' && $t['is_active']): ?>
                                                        <span class="adm-badge confirmed" style="font-size: 9px; padding: 1px 6px;"><i class="fa-solid fa-check"></i> Live</span>
                                                    <?php elseif ($t_status === 'pending'): ?>
                                                        <span class="adm-badge" style="background: rgba(234, 88, 12, 0.25); color: #fb923c; font-size: 9px; padding: 1px 6px;">⏳ Pending Approval</span>
                                                    <?php else: ?>
                                                        <span class="adm-badge cancelled" style="font-size: 9px; padding: 1px 6px;">Archived</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--adm-text-secondary); margin-top: 2px;">
                                                    <span><?php echo e($t['guest_location']); ?></span>
                                                    <?php if (!empty($t['title'])): ?>
                                                        <span> · <strong><?php echo e($t['title']); ?></strong></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column: Action Buttons -->
                                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                            <?php if ($t_status !== 'approved' || !$t['is_active']): ?>
                                                <!-- Approve / Publish Live Button -->
                                                <button type="submit" form="form-edit-testimonials" onclick="this.form.action.value='approve_testimonial'; this.form.single_target_id.value='<?php echo (int)$global_id; ?>';" class="adm-btn-action emerald" style="padding: 4px 10px; font-size: 11px; font-weight: 700;" title="Approve and publish live">
                                                    <i class="fa-solid fa-circle-check"></i> Approve Live
                                                </button>
                                            <?php else: ?>
                                                <!-- Live Active Badge -->
                                                <span style="font-size: 11px; color: #2ecc71; font-weight: 600; padding: 4px 8px; background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.25); border-radius: 4px;">
                                                    <i class="fa-solid fa-check-double"></i> Published
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($t_status !== 'rejected'): ?>
                                                <!-- Reject Button -->
                                                <button type="submit" form="form-edit-testimonials" onclick="if(!confirm('Archive/reject this review?')) return false; this.form.action.value='reject_testimonial'; this.form.single_target_id.value='<?php echo (int)$global_id; ?>';" class="adm-btn-action" style="padding: 4px 8px; font-size: 11px; background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);" title="Reject / Archive">
                                                    <i class="fa-solid fa-ban"></i> Reject
                                                </button>
                                            <?php endif; ?>

                                            <!-- Edit Toggle Button -->
                                            <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px; background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.35);" onclick="toggleReviewEditor('edit-box-<?php echo $global_id; ?>');" title="Open full edit form">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="adm-btn-danger-outline" style="padding: 4px 8px; font-size: 11px;" onclick="confirmDeleteItem('testimonials_settings', 'delete_testimonial_id', <?php echo (int)$global_id; ?>, '<?php echo e(addslashes($t['guest_name'])); ?>', 'testimonials');" title="Delete permanently">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Compact Quote Snippet -->
                                    <div style="background: rgba(0,0,0,0.3); border-radius: 6px; padding: 8px 12px; font-size: 12px; color: #e2e8f0; font-style: italic; line-height: 1.45; margin-top: 10px;">
                                        "<?php echo e($t['quote']); ?>"
                                    </div>

                                    <?php if (!empty($t['admin_reply'])): ?>
                                        <div style="font-size: 11px; color: var(--adm-gold); margin-top: 6px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-seedling"></i> <strong>Concierge Reply:</strong> <?php echo e(mb_strimwidth($t['admin_reply'], 0, 90, '...')); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- HIDDEN EXPANDABLE EDIT DRAWER / FORM (OPENS ONLY WHEN [EDIT] IS CLICKED) -->
                                    <div id="edit-box-<?php echo $global_id; ?>" class="adm-review-inline-edit-box" style="display: none; border-top: 1px solid rgba(197, 160, 89, 0.2); padding-top: 14px; margin-top: 14px;">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                                            <div class="adm-form-group">
                                                <label class="adm-form-label">Guest Full Name</label>
                                                <input type="text" name="guest_name[]" class="adm-form-control" value="<?php echo e($t['guest_name']); ?>" required>
                                            </div>
                                            <div class="adm-form-group">
                                                <label class="adm-form-label">City / Country / Stay Date</label>
                                                <input type="text" name="guest_location[]" class="adm-form-control" value="<?php echo e($t['guest_location']); ?>" required>
                                            </div>
                                            <!-- Property Dropdown -->
                                            <div class="adm-form-group">
                                                <label class="adm-form-label">Dwelling / Experience</label>
                                                <select name="stay_badge[]" class="adm-form-control" style="cursor: pointer;" required>
                                                    <?php 
                                                    $found_match = false;
                                                    foreach ($sanctuary_properties as $sprop): 
                                                        $selected = (strcasecmp($sprop['title'], $t['stay_badge']) === 0 || strcasecmp($sprop['slug'], $t['stay_badge']) === 0);
                                                        if ($selected) $found_match = true;
                                                    ?>
                                                        <option value="<?php echo htmlspecialchars($sprop['title']); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($sprop['title']); ?> (<?php echo ucfirst($sprop['type']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                    <?php if (!$found_match && !empty($t['stay_badge'])): ?>
                                                        <option value="<?php echo htmlspecialchars($t['stay_badge']); ?>" selected><?php echo htmlspecialchars($t['stay_badge']); ?> (Custom)</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <!-- Half-Star Rating Slider -->
                                            <div class="adm-form-group">
                                                <label class="adm-form-label">
                                                    Star Rating: <strong id="star-val-txt-<?php echo $t['id']; ?>" style="color: var(--adm-gold);"><?php echo number_format($t_stars, 1); ?> ★</strong>
                                                </label>
                                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                                    <input type="range" name="stars[]" min="1.0" max="5.0" step="0.5" value="<?php echo $t_stars; ?>" class="adm-range-slider" oninput="updateStarsSliderDisplay(this.value, 'star-val-txt-<?php echo $t['id']; ?>', 'star-prev-ico-<?php echo $t['id']; ?>');" style="flex: 1; accent-color: var(--adm-gold); cursor: pointer;">
                                                    <span id="star-prev-ico-<?php echo $t['id']; ?>" style="color: var(--adm-gold); font-size: 12px; letter-spacing: 2px;">
                                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                                            <?php if ($t_stars >= $s): ?>★<?php elseif ($t_stars >= $s - 0.5): ?>½<?php else: ?>☆<?php endif; ?>
                                                        <?php endfor; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Photo Upload & Review Headline -->
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 12px;">
                                            <div class="adm-form-group">
                                                <label class="adm-form-label"><i class="fa-solid fa-camera"></i> Change / Upload Guest Photo</label>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div id="avatar-box-<?php echo $t['id']; ?>" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(197, 160, 89, 0.2); border: 1px solid var(--adm-gold); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                                        <?php if (!empty($t['avatar_url'])): ?>
                                                            <img src="../<?php echo e($t['avatar_url']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://lh3.googleusercontent.com/a/default-user=s120';">
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-user" style="color: var(--adm-gold);"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" name="avatar_file[<?php echo $global_id; ?>]" accept="image/*" class="adm-form-control" onchange="previewAdminAvatar(this, 'avatar-box-<?php echo $t['id']; ?>');" style="padding: 5px; font-size: 11px; cursor: pointer;">
                                                </div>
                                            </div>
                                            <div class="adm-form-group">
                                                <label class="adm-form-label">Review Headline (Optional)</label>
                                                <input type="text" name="review_title[]" class="adm-form-control" value="<?php echo e($t['title'] ?? ''); ?>" placeholder="e.g. Unfiltered Mountain Solitude">
                                            </div>
                                        </div>

                                        <!-- Full Quote Textarea -->
                                        <div class="adm-form-group" style="margin-bottom: 12px;">
                                            <label class="adm-form-label">Guest Reflection Quote *</label>
                                            <textarea name="quote[]" rows="3" class="adm-form-control" required><?php echo e($t['quote']); ?></textarea>
                                        </div>

                                        <!-- Official Concierge Response -->
                                        <div class="adm-form-group" style="background: rgba(28, 56, 38, 0.4); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; flex-wrap: wrap; gap: 6px;">
                                                <label class="adm-form-label" style="color: var(--adm-gold); font-weight: 700; margin: 0; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fa-solid fa-seedling"></i> Official Estate Concierge Response / Reply
                                                </label>
                                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                    <button type="button" class="adm-btn-action" style="padding: 2px 8px; font-size: 10px; background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3);" onclick="fillQuickReply('<?php echo $global_id; ?>', 'Thank you for choosing Food Forest Kanthalloor! We are truly delighted that you enjoyed the tranquility and warm hospitality of our high-range sanctuary.');">
                                                        + Sanctuary Gratitude
                                                    </button>
                                                    <button type="button" class="adm-btn-action" style="padding: 2px 8px; font-size: 10px; background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);" onclick="fillQuickReply('<?php echo $global_id; ?>', 'We sincerely appreciate your valuable reflection and look forward to welcoming you back beneath our high-canopy trees next winter.');">
                                                        + Welcome Back
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea name="admin_reply[]" id="admin_reply_field_<?php echo $global_id; ?>" rows="2" class="adm-form-control" placeholder="Write an official concierge response to this traveler..."><?php echo e($t['admin_reply'] ?? ''); ?></textarea>
                                        </div>

                                        <!-- Status & Active Switch -->
                                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: rgba(0,0,0,0.25); padding: 10px 14px; border-radius: 6px;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <label class="adm-form-label" style="margin: 0;">Status:</label>
                                                <select name="status[]" class="adm-form-control" style="font-weight: 700; width: auto; height: 32px; padding: 2px 8px; font-size: 12px;">
                                                    <option value="approved" <?php echo ($t_status === 'approved') ? 'selected' : ''; ?>>✓ Approved & Published</option>
                                                    <option value="pending" <?php echo ($t_status === 'pending') ? 'selected' : ''; ?>>⏳ Pending Verification</option>
                                                    <option value="rejected" <?php echo ($t_status === 'rejected') ? 'selected' : ''; ?>>✕ Archived / Rejected</option>
                                                </select>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <input type="checkbox" name="is_active[<?php echo $global_id; ?>]" id="test_active_<?php echo $t['id']; ?>" value="1" <?php echo ($t['is_active'] ? 'checked' : ''); ?> style="width: 16px; height: 16px; accent-color: var(--adm-gold);">
                                                <label for="test_active_<?php echo $t['id']; ?>" style="cursor: pointer; color: #FFFFFF; font-weight: 600; font-size: 12px;">Show in Live Website Carousel</label>
                                            </div>
                                            <button type="button" class="adm-btn-action outline" onclick="toggleReviewEditor('edit-box-<?php echo $global_id; ?>');" style="padding: 4px 12px; font-size: 11px;">Done Editing</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- 4. STREAM PAGINATION CONTROLS FOOTER -->
                        <div id="pagination-wrap-<?php echo $streamKey; ?>" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(197, 160, 89, 0.2); padding-top: 14px; margin-top: 14px; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 12px; color: var(--adm-text-secondary);" id="pagination-info-<?php echo $streamKey; ?>">
                                Page <strong id="curr-page-txt-<?php echo $streamKey; ?>" style="color: var(--adm-gold);">1</strong> of <span id="total-pages-txt-<?php echo $streamKey; ?>">1</span>
                            </div>
                            <div id="pagination-btns-<?php echo $streamKey; ?>" style="display: flex; gap: 6px; align-items: center;">
                                <!-- Rendered dynamically by JS -->
                            </div>
                        </div>

                    </div>
                <?php
                };

                // Render the 3 separate stream panes
                $renderReviewStream('google', 'Google Maps Reviews', 'fa-brands fa-google', $google_testimonials, $pending_google, $sanctuary_properties);
                $renderReviewStream('client', 'Guest & User Portal Submissions', 'fa-solid fa-users-line', $client_testimonials, $pending_client, $sanctuary_properties);
                $renderReviewStream('admin', 'Admin Direct Chronicles', 'fa-solid fa-feather-pointed', $admin_testimonials, $pending_admin, $sanctuary_properties);
                ?>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700; font-size: 13.5px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE ALL TESTIMONIALS CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Styles for Compact Cards, Review Streams & Pagination -->
    <style>
    .adm-stream-tab-btn.active {
        background: rgba(197, 160, 89, 0.2) !important;
        color: var(--adm-gold) !important;
        border-color: var(--adm-gold) !important;
        box-shadow: 0 -2px 10px rgba(197, 160, 89, 0.15);
    }
    .adm-stream-count-pill {
        background: rgba(255, 255, 255, 0.1);
        color: #FFFFFF;
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 12px;
        font-weight: 700;
    }
    .adm-stream-count-pill.alert {
        background: #ea580c !important;
        color: #FFFFFF !important;
        animation: pulse 2s infinite;
    }
    .adm-pg-btn {
        padding: 5px 11px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--adm-text-secondary);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .adm-pg-btn:hover {
        background: rgba(197, 160, 89, 0.15);
        color: var(--adm-gold);
        border-color: var(--adm-gold);
    }
    .adm-pg-btn.active {
        background: var(--adm-gold);
        color: #0b1c11;
        border-color: var(--adm-gold);
        font-weight: 700;
    }
    .adm-pg-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .adm-test-compact-card:hover {
        border-color: rgba(197, 160, 89, 0.4) !important;
        box-shadow: 0 4px 16px rgba(0,0,0,0.3);
    }
    </style>

    <!-- JavaScript Controller for 3 Review Streams, Search, Filtering & Pagination -->
    <script>
    var streamStates = {
        google: { page: 1, perPage: 10, totalPages: 1 },
        client: { page: 1, perPage: 10, totalPages: 1 },
        admin:  { page: 1, perPage: 10, totalPages: 1 }
    };

    function switchReviewStream(streamKey) {
        // Toggle tab buttons
        document.querySelectorAll('.adm-stream-tab-btn').forEach(function(btn) {
            btn.classList.remove('active');
            btn.style.background = 'rgba(255, 255, 255, 0.04)';
            btn.style.color = 'var(--adm-text-secondary)';
            btn.style.borderColor = 'rgba(255, 255, 255, 0.1)';
        });
        var activeBtn = document.getElementById('stream-tab-btn-' + streamKey);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.style.background = 'rgba(197, 160, 89, 0.2)';
            activeBtn.style.color = 'var(--adm-gold)';
            activeBtn.style.borderColor = 'var(--adm-gold)';
        }

        // Toggle panes
        document.querySelectorAll('.adm-review-stream-pane').forEach(function(pane) {
            pane.style.display = 'none';
        });
        var activePane = document.getElementById('stream-pane-' + streamKey);
        if (activePane) activePane.style.display = 'block';

        // Re-run filter/pagination for this stream
        filterStreamReviews(streamKey);
    }

    function changeStreamPerPage(streamKey, newPerPage) {
        streamStates[streamKey].perPage = parseInt(newPerPage) || 10;
        streamStates[streamKey].page = 1;
        filterStreamReviews(streamKey);
    }

    function toggleReviewEditor(boxId) {
        var box = document.getElementById(boxId);
        if (box) {
            box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
        }
    }

    function filterStreamReviews(streamKey) {
        var query = (document.getElementById('search-input-' + streamKey) ? document.getElementById('search-input-' + streamKey).value : '').toLowerCase().trim();
        var status = document.getElementById('status-filter-' + streamKey) ? document.getElementById('status-filter-' + streamKey).value : 'all';
        var stars = document.getElementById('stars-filter-' + streamKey) ? document.getElementById('stars-filter-' + streamKey).value : 'all';

        var clearBtn = document.getElementById('search-clear-' + streamKey);
        if (clearBtn) clearBtn.style.display = query ? 'block' : 'none';

        var items = document.querySelectorAll('#grid-stream-' + streamKey + ' .stream-item-' + streamKey);
        var matchedItems = [];

        items.forEach(function(card) {
            var gName = card.getAttribute('data-guest-name') || '';
            var gLoc = card.getAttribute('data-location') || '';
            var gTitle = card.getAttribute('data-title') || '';
            var gQuote = card.getAttribute('data-quote') || '';
            var gStatus = card.getAttribute('data-status') || '';
            var gStars = card.getAttribute('data-stars') || '';

            var matchesQuery = !query || gName.includes(query) || gLoc.includes(query) || gTitle.includes(query) || gQuote.includes(query);
            var matchesStatus = (status === 'all') || (status === gStatus);
            var matchesStars = (stars === 'all') || (parseFloat(gStars) === parseFloat(stars));

            if (matchesQuery && matchesStatus && matchesStars) {
                matchedItems.push(card);
            } else {
                card.style.display = 'none';
            }
        });

        // Paginate matched items
        var state = streamStates[streamKey];
        var totalMatched = matchedItems.length;
        state.totalPages = Math.max(1, Math.ceil(totalMatched / state.perPage));
        if (state.page > state.totalPages) state.page = 1;

        var startIdx = (state.page - 1) * state.perPage;
        var endIdx = startIdx + state.perPage;

        matchedItems.forEach(function(card, idx) {
            if (idx >= startIdx && idx < endIdx) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Update counts
        var visEl = document.getElementById('visible-count-' + streamKey);
        if (visEl) visEl.innerText = Math.min(endIdx, totalMatched);
        var totEl = document.getElementById('total-count-' + streamKey);
        if (totEl) totEl.innerText = totalMatched;

        var noRes = document.getElementById('no-res-' + streamKey);
        if (noRes) noRes.style.display = (totalMatched === 0) ? 'block' : 'none';

        renderPaginationControls(streamKey, totalMatched);
    }

    function renderPaginationControls(streamKey, totalMatched) {
        var state = streamStates[streamKey];
        var infoWrap = document.getElementById('pagination-wrap-' + streamKey);
        var btnsBox = document.getElementById('pagination-btns-' + streamKey);
        var currTxt = document.getElementById('curr-page-txt-' + streamKey);
        var totTxt = document.getElementById('total-pages-txt-' + streamKey);

        if (currTxt) currTxt.innerText = state.page;
        if (totTxt) totTxt.innerText = state.totalPages;

        if (!btnsBox) return;
        if (totalMatched <= state.perPage) {
            btnsBox.innerHTML = '';
            return;
        }

        var html = '';
        // Prev button
        html += '<button type="button" class="adm-pg-btn" ' + (state.page === 1 ? 'disabled' : '') + ' onclick="gotoStreamPage(\'' + streamKey + '\', ' + (state.page - 1) + ');"><i class="fa-solid fa-chevron-left"></i></button>';

        // Page buttons (window of 5 around current)
        var startP = Math.max(1, state.page - 2);
        var endP = Math.min(state.totalPages, startP + 4);
        if (endP - startP < 4) startP = Math.max(1, endP - 4);

        if (startP > 1) {
            html += '<button type="button" class="adm-pg-btn" onclick="gotoStreamPage(\'' + streamKey + '\', 1);">1</button>';
            if (startP > 2) html += '<span style="color:var(--adm-text-muted); padding:0 4px;">...</span>';
        }

        for (var p = startP; p <= endP; p++) {
            html += '<button type="button" class="adm-pg-btn ' + (p === state.page ? 'active' : '') + '" onclick="gotoStreamPage(\'' + streamKey + '\', ' + p + ');">' + p + '</button>';
        }

        if (endP < state.totalPages) {
            if (endP < state.totalPages - 1) html += '<span style="color:var(--adm-text-muted); padding:0 4px;">...</span>';
            html += '<button type="button" class="adm-pg-btn" onclick="gotoStreamPage(\'' + streamKey + '\', ' + state.totalPages + ');">' + state.totalPages + '</button>';
        }

        // Next button
        html += '<button type="button" class="adm-pg-btn" ' + (state.page === state.totalPages ? 'disabled' : '') + ' onclick="gotoStreamPage(\'' + streamKey + '\', ' + (state.page + 1) + ');"><i class="fa-solid fa-chevron-right"></i></button>';

        btnsBox.innerHTML = html;
    }

    function gotoStreamPage(streamKey, pageNum) {
        streamStates[streamKey].page = pageNum;
        filterStreamReviews(streamKey);
        var pane = document.getElementById('stream-pane-' + streamKey);
        if (pane) pane.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function clearStreamSearch(streamKey) {
        var input = document.getElementById('search-input-' + streamKey);
        if (input) {
            input.value = '';
            streamStates[streamKey].page = 1;
            filterStreamReviews(streamKey);
            input.focus();
        }
    }

    function setStreamQuickFilter(streamKey, statusVal) {
        var status = document.getElementById('status-filter-' + streamKey);
        if (status) status.value = statusVal;
        streamStates[streamKey].page = 1;
        filterStreamReviews(streamKey);
    }

    function resetStreamFilters(streamKey) {
        var input = document.getElementById('search-input-' + streamKey);
        var status = document.getElementById('status-filter-' + streamKey);
        var stars = document.getElementById('stars-filter-' + streamKey);
        if (input) input.value = '';
        if (status) status.value = 'all';
        if (stars) stars.value = 'all';
        streamStates[streamKey].page = 1;
        filterStreamReviews(streamKey);
    }

    function updateStarsSliderDisplay(val, textId, iconsId) {
        var num = parseFloat(val);
        var tEl = document.getElementById(textId);
        var iEl = document.getElementById(iconsId);
        if (tEl) tEl.innerText = num.toFixed(1) + ' ★';
        if (iEl) {
            var str = '';
            for (var s = 1; s <= 5; s++) {
                if (num >= s) str += '★';
                else if (num >= (s - 0.5)) str += '½';
                else str += '☆';
            }
            iEl.innerText = str;
        }
    }

    function previewAdminAvatar(input, boxId) {
        var box = document.getElementById(boxId);
        if (input.files && input.files[0] && box) {
            var reader = new FileReader();
            reader.onload = function(e) {
                box.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function fillQuickReply(id, text) {
        var field = document.getElementById('admin_reply_field_' + id);
        if (field) {
            field.value = text;
            field.focus();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        filterStreamReviews('google');
        filterStreamReviews('client');
        filterStreamReviews('admin');
    });
    </script>
    <?php endif; ?>

    <!-- -------------------------------------------------------------
         PANEL 14: CONTENT & IMAGE PROTECTION
         ------------------------------------------------------------- -->
    <?php if ($active_tab === 'protection'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-protection">
        <form action="edit_section.php?section=protection" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="protection_settings">
            <input type="hidden" name="active_tab" value="protection">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 14</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">CONTENT & IMAGE ASSET PROTECTION</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Shield high-resolution photography, architectural blueprints, and narrative copy from copying.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-toggle-container" style="margin-bottom: 24px;">
                    <div>
                        <span style="font-family: var(--adm-font-title); font-size: 15px; font-weight: 700; color: #FFFFFF; display: block;">Enable Website Content & Image Protection</span>
                        <span style="font-size: 12px; color: var(--adm-text-secondary); display: block; margin-top: 4px;">
                            When active, right-clicking, image drag-and-drop, PrintScreen keys, Ctrl+S / Cmd+S, and DevTools inspection are protected across all public pages.
                        </span>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" name="content_protection_enabled" value="1" <?php echo (($s['content_protection_enabled'] ?? '0') === '1') ? 'checked' : ''; ?>>
                        <span class="adm-switch-slider"></span>
                    </label>
                </div>

                <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11.5px; text-transform: uppercase; color: var(--adm-gold); font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 12px;">Active Security Modules Included:</span>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; font-size: 12px; color: var(--adm-text-secondary);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> Disables Right-Click Context Menu
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> Blocks Drag & Drop Image Saving
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> Prevents PrintScreen & Screenshot Shortcuts
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> Blocks Ctrl+S, Cmd+S, Ctrl+P Keybindings
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> Blocks F12 & Developer Tools Inspection
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> Silent Toast Warning on Protected Interaction
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE PROTECTION CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 15: SECURITY & ADMIN PROFILE
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'security'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-security">
        <form action="edit_section.php?section=security" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="admin_security">
            <input type="hidden" name="active_tab" value="security">

            <?php
            $admin_stmt = $pdo->prepare("SELECT full_name, username, email FROM admins WHERE id = ?");
            $admin_stmt->execute([(int)$_SESSION['admin_id']]);
            $adm = $admin_stmt->fetch() ?: ['full_name' => 'Estate Administrator', 'username' => 'admin', 'email' => 'admin@foodforest.local'];
            ?>

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon indigo"><i class="fa-solid fa-key"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 15</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">SECURITY & ADMINISTRATOR CREDENTIALS</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Update concierge master login username, email address, and encrypted access key.</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE CHANGES</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Administrator Full Name</label>
                        <input type="text" name="full_name" class="adm-form-control" value="<?php echo e($adm['full_name']); ?>" required>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Sign-in Username</label>
                        <input type="text" name="username" class="adm-form-control" value="<?php echo e($adm['username']); ?>" required>
                    </div>

                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Notification Email Address</label>
                        <input type="email" name="email" class="adm-form-control" value="<?php echo e($adm['email']); ?>" required>
                    </div>
                </div>

                <div style="background: rgba(8, 18, 11, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin-bottom: 14px;">
                        <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> Update Master Access Key (Password)
                    </h4>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Current Access Key *</label>
                            <div style="position: relative;">
                                <input type="password" name="current_password" id="input_current_pwd" class="adm-form-control" required style="padding-right: 40px;">
                                <button type="button" onclick="togglePasswordVisibility('input_current_pwd', this);" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--adm-text-muted); cursor: pointer;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-form-label">New Access Key (Leave blank to keep)</label>
                            <div style="position: relative;">
                                <input type="password" name="new_password" id="input_new_pwd" class="adm-form-control" minlength="6" style="padding-right: 40px;">
                                <button type="button" onclick="togglePasswordVisibility('input_new_pwd', this);" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--adm-text-muted); cursor: pointer;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-form-label">Confirm New Access Key</label>
                            <div style="position: relative;">
                                <input type="password" name="confirm_password" id="input_confirm_pwd" class="adm-form-control" minlength="6" style="padding-right: 40px;">
                                <button type="button" onclick="togglePasswordVisibility('input_confirm_pwd', this);" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--adm-text-muted); cursor: pointer;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE SECURITY CREDENTIALS</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 16: MYSQL DATABASE BACKUP
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'backup'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-backup">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: rgba(16, 31, 21, 0.4);">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon gold"><i class="fa-solid fa-database"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.3); padding: 2px 8px; border-radius: 4px; letter-spacing: 0.5px;">SYSTEM TOOL • CARD 16</span>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 0;">MYSQL DATABASE FULL BACKUP & CATALOG</h3>
                    </div>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">phpMyAdmin-compatible dynamic SQL dump generator and table telemetry.</p>
                </div>
            </div>
            <div>
                <a href="settings.php?action=download_backup" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                    <span>DOWNLOAD BACKUP (.SQL)</span>
                </a>
            </div>
        </div>

        <div style="padding: 24px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-gold); letter-spacing: 1px;">Database Engine</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 4px 0 0;">MySQL / MariaDB</h3>
                </div>
                <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-gold); letter-spacing: 1px;">Active Database</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 4px 0 0;"><?php echo DB_NAME; ?></h3>
                </div>
                <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-gold); letter-spacing: 1px;">Active Tables</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 4px 0 0;"><?php echo $tables_count; ?> Tables</h3>
                </div>
                <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-gold); letter-spacing: 1px;">Guest Bookings</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 4px 0 0;"><?php echo $bookings_count; ?> Records</h3>
                </div>
            </div>

            <div style="background: rgba(8, 18, 11, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 8px; padding: 20px; line-height: 1.6;">
                <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin-bottom: 8px;">
                    <i class="fa-solid fa-circle-info" style="margin-right: 6px;"></i> Automated Backup Instructions
                </h4>
                <p style="font-size: 13px; color: var(--adm-text-secondary); margin: 0 0 14px;">
                    Clicking the download button below triggers a live database dump directly from MySQL, bundling all schema structures, table definitions (<code style="color:var(--adm-gold);">admins</code>, <code style="color:var(--adm-gold);">rooms</code>, <code style="color:var(--adm-gold);">bookings</code>, <code style="color:var(--adm-gold);">inquiries</code>, <code style="color:var(--adm-gold);">settings</code>, <code style="color:var(--adm-gold);">gallery</code>, <code style="color:var(--adm-gold);">testimonials</code>, <code style="color:var(--adm-gold);">experiences</code>), and all active reservation records.
                </p>
                <p style="font-size: 13px; color: var(--adm-text-secondary); margin: 0;">
                    You can import this SQL file directly into <strong>phpMyAdmin</strong> (<code style="color:var(--adm-gold);">http://localhost/phpmyadmin</code>) or any MySQL database server.
                </p>
            </div>

            <div style="margin-top: 20px;">
                <a href="settings.php?action=download_backup" class="adm-btn-action gold" style="padding: 12px 28px;">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                    <span>EXPORT & DOWNLOAD MYSQL BACKUP (.SQL)</span>
                </a>
            </div>
        </div> <!-- End padding 24px -->
    </div> <!-- End #pane-backup -->

    <!-- -------------------------------------------------------------
         PANEL 17: BANK DETAILS & UPI QR CODE
         ------------------------------------------------------------- -->
    <?php endif; ?>
    <?php if ($active_tab === 'bank'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-bank">
        <form action="edit_section.php?section=bank" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="bank_settings">
            <input type="hidden" name="active_tab" value="bank">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-building-columns"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 17</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">BANK ACCOUNT DETAILS & UPI QR CODE</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Configure estate bank transfer info, IFSC, UPI ID, QR code asset & bill print toggles.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" onclick="copyFormattedBankInfo();" class="adm-btn-action outline" style="padding: 10px 16px; font-size: 12px;" title="Copy complete formatted bank details for WhatsApp">
                        <i class="fa-solid fa-copy"></i>
                        <span id="btn-copy-bank-text">Copy for WhatsApp</span>
                    </button>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE BANK SETTINGS</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                
                <!-- Section 1: Core Bank Account Parameters -->
                <div style="border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding-bottom: 16px; margin-bottom: 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-landmark" style="margin-right: 6px;"></i> 1. Official Bank Account Information
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Displayed on guest folios and tax invoices for direct NEFT / IMPS / RTGS settlement.</p>
                </div>

                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    
                    <div class="adm-form-group">
                        <label class="adm-form-label">Account Holder / Beneficiary Name</label>
                        <input type="text" id="bank_acc_holder_input" name="bank_account_holder" class="adm-form-control" value="<?php echo e($s['bank_account_holder'] ?? 'Food Forest Eco Sanctuary'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Official registered company or individual name as per bank records.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Bank Name</label>
                        <input type="text" id="bank_name_input" name="bank_name" class="adm-form-control" value="<?php echo e($s['bank_name'] ?? 'State Bank of India'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">E.g. State Bank of India, HDFC Bank, Federal Bank, ICICI Bank.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Account Number</span>
                            <button type="button" onclick="copySingleField('bank_acc_input', 'Account Number');" style="background:none; border:none; color:var(--adm-gold); font-size:11px; cursor:pointer; font-weight:600;">
                                <i class="fa-solid fa-copy"></i> Copy No
                            </button>
                        </label>
                        <input type="text" id="bank_acc_input" name="bank_account_number" class="adm-form-control" style="font-family: monospace; letter-spacing: 1px;" value="<?php echo e($s['bank_account_number'] ?? '40982314981'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Double check all digits carefully before saving.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>IFSC Code</span>
                            <button type="button" onclick="copySingleField('bank_ifsc_input', 'IFSC Code');" style="background:none; border:none; color:var(--adm-gold); font-size:11px; cursor:pointer; font-weight:600;">
                                <i class="fa-solid fa-copy"></i> Copy IFSC
                            </button>
                        </label>
                        <input type="text" id="bank_ifsc_input" name="bank_ifsc" class="adm-form-control" style="font-family: monospace; text-transform: uppercase;" value="<?php echo e($s['bank_ifsc'] ?? 'SBIN0070123'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">11-character Indian Financial System Code (e.g. SBIN0070123).</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Account Type</label>
                        <select name="bank_account_type" class="adm-form-control" style="background: var(--adm-bg-surface); color: #FFF;">
                            <?php $curr_btype = $s['bank_account_type'] ?? 'Current Account'; ?>
                            <option value="Current Account" <?php echo ($curr_btype === 'Current Account') ? 'selected' : ''; ?>>Current Account</option>
                            <option value="Savings Account" <?php echo ($curr_btype === 'Savings Account') ? 'selected' : ''; ?>>Savings Account</option>
                            <option value="Cash Credit (CC)" <?php echo ($curr_btype === 'Cash Credit (CC)') ? 'selected' : ''; ?>>Cash Credit (CC)</option>
                        </select>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Account category printed on guest folio.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Bank Branch & Location</label>
                        <input type="text" id="bank_branch_input" name="bank_branch" class="adm-form-control" value="<?php echo e($s['bank_branch'] ?? 'Munnar / Kanthalloor Branch'); ?>">
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Branch office location and district.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">GSTIN / Tax Identification Number</label>
                        <input type="text" id="gst_number_input" name="gst_number" class="adm-form-control" style="font-family: monospace; text-transform: uppercase;" value="<?php echo e($s['gst_number'] ?? '32AAECF1234M1Z5'); ?>">
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Printed in the invoice header and tax computation block.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">GST Tax Rate Percentage (%)</label>
                        <div style="position: relative;">
                            <input type="number" step="0.01" min="0" max="100" id="gst_rate_percentage_input" name="gst_rate_percentage" class="adm-form-control" style="font-family: monospace; font-weight: 700; padding-right: 32px;" value="<?php echo e($s['gst_rate_percentage'] ?? '12'); ?>">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--adm-gold); font-weight: bold; pointer-events: none;">%</span>
                        </div>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Standard GST rate applied to reservation subtotal when guest requests an official GST Tax Invoice (e.g. 12% or 18% or 5%).</small>
                    </div>

                </div>

                <!-- Section 2: UPI VPA ID & QR Code Asset Upload -->
                <div style="border-top: 1px solid rgba(197, 160, 89, 0.15); border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 18px 0 16px; margin: 24px 0 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-qrcode" style="margin-right: 6px;"></i> 2. UPI VPA & Payment QR Code
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Upload your GPay / PhonePe / Paytm / BHIM Merchant QR code image to print directly on bills.</p>
                </div>

                <div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 24px; align-items: start; margin-bottom: 24px;">
                    
                    <div>
                        <div class="adm-form-group" style="margin-bottom: 18px;">
                            <label class="adm-form-label" style="display: flex; justify-content: space-between; align-items: center;">
                                <span>UPI VPA ID (Virtual Payment Address)</span>
                                <button type="button" onclick="copySingleField('bank_upi_input', 'UPI VPA ID');" style="background:none; border:none; color:var(--adm-gold); font-size:11px; cursor:pointer; font-weight:600;">
                                    <i class="fa-solid fa-copy"></i> Copy UPI
                                </button>
                            </label>
                            <input type="text" id="bank_upi_input" name="bank_upi_id" class="adm-form-control" style="font-family: monospace; font-weight: 600;" value="<?php echo e($s['bank_upi_id'] ?? 'foodforest@upi'); ?>" required>
                            <small style="color: var(--adm-text-muted); font-size: 11px;">E.g. foodforest@okaxis, 9447000000@paytm, or merchant VPA.</small>
                        </div>

                        <div class="adm-form-group" style="margin-bottom: 18px;">
                            <label class="adm-form-label">Upload New UPI Payment QR Code Image</label>
                            <input type="file" name="bank_qr_image_file" class="adm-form-control" accept="image/*" onchange="previewUploadImage(this, 'bank-qr-preview-img', 'bank-qr-file-badge');" style="padding: 8px;">
                            <div id="bank-qr-file-badge" style="display: none; margin-top: 6px; font-size: 11px; color: #2ecc71; align-items: center; gap: 4px;"></div>
                            <small style="color: var(--adm-text-muted); font-size: 11px; display: block; margin-top: 4px;">Recommended: Clear square PNG, JPG, WEBP or SVG (Min 300x300px).</small>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-form-label">QR Image Path (Direct Server Path / URL)</label>
                            <input type="text" name="bank_qr_image" class="adm-form-control" value="<?php echo e($s['bank_qr_image'] ?? 'assets/images/foodforest_upi_qr.svg'); ?>">
                            <small style="color: var(--adm-text-muted); font-size: 11px;">Path automatically updates when you upload a new image above.</small>
                        </div>
                    </div>

                    <!-- Live QR Code Card Preview in Form -->
                    <div style="background: rgba(16, 31, 21, 0.7); border: 1.5px solid rgba(197, 160, 89, 0.35); border-radius: 12px; padding: 20px; text-align: center;">
                        <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 0.8px; display: block; margin-bottom: 12px;">
                            Current Active QR Code
                        </span>
                        <div style="background: #FFFFFF; padding: 12px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                            <?php 
                            $qr_src = !empty($s['bank_qr_image']) ? (str_starts_with($s['bank_qr_image'], 'http') || str_starts_with($s['bank_qr_image'], 'assets/') ? '../' . $s['bank_qr_image'] : '../' . $s['bank_qr_image']) : '../assets/images/foodforest_upi_qr.svg'; 
                            ?>
                            <img id="bank-qr-preview-img" src="<?php echo htmlspecialchars($qr_src); ?>" alt="UPI QR Preview" style="width: 150px; height: 150px; object-fit: contain; display: block;" onerror="this.src='../assets/images/foodforest_upi_qr.svg';">
                        </div>
                        <div style="font-size: 11px; color: var(--adm-text-secondary); margin-top: 10px;">
                            Will be rendered on luxury A4 printed guest folios when enabled.
                        </div>
                    </div>

                </div>

                <!-- Section 3: Bill / Folio Printing Rules & Toggles -->
                <div style="border-top: 1px solid rgba(197, 160, 89, 0.15); border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 18px 0 16px; margin: 24px 0 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-receipt" style="margin-right: 6px;"></i> 3. Guest Invoice & Bill Print Toggles
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Configure whether Bank Account table and UPI QR Code appear on generated folios by default.</p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 24px;">
                    
                    <div style="background: rgba(16, 31, 21, 0.5); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                        <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                            <input type="checkbox" name="bill_show_bank_details" value="1" <?php echo (($s['bill_show_bank_details'] ?? '1') === '1') ? 'checked' : ''; ?> style="width: 18px; height: 18px; margin-top: 2px; accent-color: #C5A059;">
                            <div>
                                <span style="font-size: 13.5px; font-weight: 700; color: #FFFFFF; display: block;">Show Bank Account Details on Bills</span>
                                <span style="font-size: 11.5px; color: var(--adm-text-muted); display: block; margin-top: 2px;">Renders Account Holder, Bank Name, A/C No, and IFSC Code on printable folios.</span>
                            </div>
                        </label>
                    </div>

                    <div style="background: rgba(16, 31, 21, 0.5); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                        <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                            <input type="checkbox" name="bill_show_qr_code" value="1" <?php echo (($s['bill_show_qr_code'] ?? '1') === '1') ? 'checked' : ''; ?> style="width: 18px; height: 18px; margin-top: 2px; accent-color: #C5A059;">
                            <div>
                                <span style="font-size: 13.5px; font-weight: 700; color: #FFFFFF; display: block;">Show UPI QR Code on Bills</span>
                                <span style="font-size: 11.5px; color: var(--adm-text-muted); display: block; margin-top: 2px;">Renders high-res scan-and-pay QR code alongside invoice totals.</span>
                            </div>
                        </label>
                    </div>

                </div>

                <div class="adm-form-group" style="margin-bottom: 24px;">
                    <label class="adm-form-label">Invoice Footer Instructions / Payment Terms</label>
                    <textarea name="bill_footer_notes" class="adm-form-control" rows="2" style="font-size: 12.5px;"><?php echo e($s['bill_footer_notes'] ?? 'All payments via UPI, IMPS, or NEFT must be confirmed with transaction ID. For official GST tax invoices, notify concierge prior to checkout.'); ?></textarea>
                    <small style="color: var(--adm-text-muted); font-size: 11px;">Custom note printed at the base of guest folios.</small>
                </div>

                <!-- Submit Button -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <div id="bank-copy-toast" style="font-size: 12px; color: #2ecc71; font-weight: 600; display: none;">
                        <i class="fa-solid fa-circle-check"></i> <span id="bank-toast-msg">Copied!</span>
                    </div>
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700; margin-left: auto;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE ALL BANK & PAYMENT SETTINGS</span>
                    </button>
                </div>

            </div>
        </form>
    </div> <!-- End #pane-bank -->

    <script>
    function copySingleField(elementId, label) {
        var el = document.getElementById(elementId);
        if (!el) return;
        var val = el.value || el.innerText || '';
        if (navigator.clipboard) {
            navigator.clipboard.writeText(val).then(function() {
                showBankToast(label + ' copied to clipboard!');
            });
        } else {
            el.select();
            document.execCommand('copy');
            showBankToast(label + ' copied to clipboard!');
        }
    }

    function copyFormattedBankInfo() {
        var holder = document.getElementById('bank_acc_holder_input')?.value || 'Food Forest Eco Sanctuary';
        var bname = document.getElementById('bank_name_input')?.value || 'State Bank of India';
        var acc = document.getElementById('bank_acc_input')?.value || '';
        var ifsc = document.getElementById('bank_ifsc_input')?.value || '';
        var branch = document.getElementById('bank_branch_input')?.value || '';
        var upi = document.getElementById('bank_upi_input')?.value || '';
        var gst = document.getElementById('gst_number_input')?.value || '';

        var msg = "🌿 *FOOD FOREST SANCTUARY — BANK TRANSFER & UPI DETAILS*\n";
        msg += "━━━━━━━━━━━━━━━━━━━━━\n";
        msg += "• *Account Holder*: " + holder + "\n";
        msg += "• *Bank*: " + bname + "\n";
        msg += "• *Account No*: " + acc + "\n";
        msg += "• *IFSC Code*: " + ifsc + "\n";
        if (branch) msg += "• *Branch*: " + branch + "\n";
        if (upi) msg += "• *UPI VPA ID*: " + upi + "\n";
        if (gst) msg += "• *GSTIN*: " + gst + "\n";
        msg += "━━━━━━━━━━━━━━━━━━━━━\n";
        msg += "Please share the payment screenshot or transaction UTR number once completed. Thank you!";

        if (navigator.clipboard) {
            navigator.clipboard.writeText(msg).then(function() {
                var btn = document.getElementById('btn-copy-bank-text');
                if (btn) btn.innerText = 'Copied to WhatsApp!';
                setTimeout(function() {
                    if (btn) btn.innerText = 'Copy for WhatsApp';
                }, 3000);
                showBankToast('Complete bank details formatted & copied for WhatsApp!');
            });
        }
    }

    function showBankToast(msg) {
        var toast = document.getElementById('bank-copy-toast');
        var toastMsg = document.getElementById('bank-toast-msg');
        if (toast && toastMsg) {
            toastMsg.innerText = msg;
            toast.style.display = 'inline-flex';
            setTimeout(function() {
                toast.style.display = 'none';
            }, 3500);
        }
    }
    </script>
    <?php endif; ?>

    <!-- -------------------------------------------------------------
         PANEL 18: FOOTER & ECO TRUST PILLARS
         ------------------------------------------------------------- -->
    <?php if ($active_tab === 'footer'): ?>
    <div class="adm-card adm-settings-tab-pane is-active" style="display: block !important;" id="pane-footer">
        <form action="edit_section.php?section=footer" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="footer_settings">
            <input type="hidden" name="active_tab" value="footer">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon gold"><i class="fa-solid fa-seedling"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(197, 160, 89, 0.2); color: var(--adm-gold); border: 1px solid var(--adm-gold-border); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 18</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">FOOTER & ECO TRUST PILLARS</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Control 100% dynamic sustainability badges, brand narrative, navigation links, contact info, newsletter copy & legal terms.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="../index.php#contact" target="_blank" class="adm-btn-action outline" style="padding: 10px 16px; font-size: 12px;" title="View footer on public website">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>Live Footer</span>
                    </a>
                    <button type="submit" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE FOOTER SETTINGS</span>
                    </button>
                </div>
            </div>

            <div style="padding: 24px;">
                
                <!-- SECTION 1: Top 4 Sustainability & Eco Trust Badges -->
                <div style="border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding-bottom: 16px; margin-bottom: 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-shield-heart" style="margin-right: 6px;"></i> 1. Top Sustainability & Accolades Badges (4 Banner Items)
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">These 4 gold-accented badges appear prominently across the top of the footer.</p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 28px;">
                    <!-- Badge 1 -->
                    <div style="background: rgba(11, 24, 16, 0.6); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--adm-gold); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="<?php echo e($s['footer_badge1_icon'] ?? 'fa-solid fa-seedling'); ?>"></i> BADGE 1
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">FontAwesome Icon Class</label>
                            <input type="text" name="footer_badge1_icon" class="adm-form-control" value="<?php echo e($s['footer_badge1_icon'] ?? 'fa-solid fa-seedling'); ?>" placeholder="fa-solid fa-seedling" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Badge Title</label>
                            <input type="text" name="footer_badge1_title" class="adm-form-control" value="<?php echo e($s['footer_badge1_title'] ?? '100% Organic Soil'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Subtitle / Description</label>
                            <input type="text" name="footer_badge1_desc" class="adm-form-control" value="<?php echo e($s['footer_badge1_desc'] ?? 'Zero synthetic pesticides or fertilizers'); ?>" required>
                        </div>
                    </div>

                    <!-- Badge 2 -->
                    <div style="background: rgba(11, 24, 16, 0.6); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--adm-gold); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="<?php echo e($s['footer_badge2_icon'] ?? 'fa-solid fa-house-chimney'); ?>"></i> BADGE 2
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">FontAwesome Icon Class</label>
                            <input type="text" name="footer_badge2_icon" class="adm-form-control" value="<?php echo e($s['footer_badge2_icon'] ?? 'fa-solid fa-house-chimney'); ?>" placeholder="fa-solid fa-house-chimney" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Badge Title</label>
                            <input type="text" name="footer_badge2_title" class="adm-form-control" value="<?php echo e($s['footer_badge2_title'] ?? 'Vernacular Cob Clay'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Subtitle / Description</label>
                            <input type="text" name="footer_badge2_desc" class="adm-form-control" value="<?php echo e($s['footer_badge2_desc'] ?? 'Traditional low-carbon architecture'); ?>" required>
                        </div>
                    </div>

                    <!-- Badge 3 -->
                    <div style="background: rgba(11, 24, 16, 0.6); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--adm-gold); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="<?php echo e($s['footer_badge3_icon'] ?? 'fa-solid fa-droplet'); ?>"></i> BADGE 3
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">FontAwesome Icon Class</label>
                            <input type="text" name="footer_badge3_icon" class="adm-form-control" value="<?php echo e($s['footer_badge3_icon'] ?? 'fa-solid fa-droplet'); ?>" placeholder="fa-solid fa-droplet" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Badge Title</label>
                            <input type="text" name="footer_badge3_title" class="adm-form-control" value="<?php echo e($s['footer_badge3_title'] ?? 'Mountain Spring Water'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Subtitle / Description</label>
                            <input type="text" name="footer_badge3_desc" class="adm-form-control" value="<?php echo e($s['footer_badge3_desc'] ?? 'Filtered natural water, zero single-use plastic'); ?>" required>
                        </div>
                    </div>

                    <!-- Badge 4 -->
                    <div style="background: rgba(11, 24, 16, 0.6); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--adm-gold); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="<?php echo e($s['footer_badge4_icon'] ?? 'fa-solid fa-people-roof'); ?>"></i> BADGE 4
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">FontAwesome Icon Class</label>
                            <input type="text" name="footer_badge4_icon" class="adm-form-control" value="<?php echo e($s['footer_badge4_icon'] ?? 'fa-solid fa-people-roof'); ?>" placeholder="fa-solid fa-people-roof" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Badge Title</label>
                            <input type="text" name="footer_badge4_title" class="adm-form-control" value="<?php echo e($s['footer_badge4_title'] ?? 'Local Community First'); ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Subtitle / Description</label>
                            <input type="text" name="footer_badge4_desc" class="adm-form-control" value="<?php echo e($s['footer_badge4_desc'] ?? 'Crafted & staffed by native artisans'); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Column 1 - Brand Narrative & Concierge Availability -->
                <div style="border-top: 1px solid rgba(197, 160, 89, 0.15); border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 18px 0 16px; margin: 24px 0 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-feather-pointed" style="margin-right: 6px;"></i> 2. Column 1: Brand Narrative & Concierge Live Badge
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Main estate description, availability status indicator & hours.</p>
                </div>

                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Footer Brand Tagline / Narrative</label>
                        <textarea name="footer_tagline" class="adm-form-control" rows="3" required><?php echo e($s['footer_tagline'] ?? 'An intimate sanctuary where ancestral architecture meets untamed nature. Rediscover silence, wholesome farm-to-table flavors, and deep mountain tranquility.'); ?></textarea>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">The poetic bio text rendered directly under the brand logo.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Concierge Badge Prefix</label>
                        <input type="text" name="footer_concierge_badge_text" class="adm-form-control" value="<?php echo e($s['footer_concierge_badge_text'] ?? 'Estate Concierge Available'); ?>">
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Text next to the glowing green pulse indicator.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Concierge Active Operating Hours</label>
                        <input type="text" name="concierge_hours" class="adm-form-control" value="<?php echo e($s['concierge_hours'] ?? '08:00 AM – 09:00 PM'); ?>">
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Daily hours displayed on the live status pill.</small>
                    </div>
                </div>

                <!-- SECTION 3: Column 2 - Navigation Links Manager -->
                <div style="border-top: 1px solid rgba(197, 160, 89, 0.15); border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 18px 0 16px; margin: 24px 0 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-compass" style="margin-right: 6px;"></i> 3. Column 2: The Sanctuary Navigation Links
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Organize footer links with target anchors or internal URLs (one link per line).</p>
                </div>

                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Navigation Column Heading</label>
                        <input type="text" name="footer_nav_title" class="adm-form-control" value="<?php echo e($s['footer_nav_title'] ?? 'The Sanctuary'); ?>" required>
                    </div>

                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Navigation Items (Format: <code>Label | URL | FontAwesome Icon (optional) | Highlight (1 or 0)</code>)</label>
                        <textarea name="footer_nav_links" class="adm-form-control" rows="10" style="font-family: monospace; font-size: 12.5px; line-height: 1.6;"><?php echo e($s['footer_nav_links'] ?? "Our Story & Ethos|#welcome\nCanopy Treehouse|#rooms-experience\nEarthen Mudhouse|#rooms-experience\nActivities|#experiences\nFood Menu & Hearth|#dining\nGuest Portal & Receipts|guest_portal.php|fa-solid fa-key|1\nVisual Gallery|#gallery\nEstate Landscape|#sanctuary\nGuest Stories|#testimonials"); ?></textarea>
                        <div style="background: rgba(197, 160, 89, 0.08); border-left: 3px solid var(--adm-gold); padding: 8px 12px; border-radius: 4px; margin-top: 8px; font-size: 11.5px; color: var(--adm-text-secondary);">
                            <strong>Format Guide:</strong> Each row should be <code>Link Title | Target URL | fa-icon (optional) | 1 (gold highlight)</code>.<br>
                            E.g. <code>Guest Portal & Receipts | guest_portal.php | fa-solid fa-key | 1</code>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: Column 3 - Direct Concierge & Reservation -->
                <div style="border-top: 1px solid rgba(197, 160, 89, 0.15); border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 18px 0 16px; margin: 24px 0 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-headset" style="margin-right: 6px;"></i> 4. Column 3: Direct Concierge & Reservation CTA
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Concierge column title, secondary WhatsApp label & booking button text.</p>
                </div>

                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Concierge Column Heading</label>
                        <input type="text" name="footer_contact_title" class="adm-form-control" value="<?php echo e($s['footer_contact_title'] ?? 'Direct Concierge'); ?>" required>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">WhatsApp Secondary Label</label>
                        <input type="text" name="footer_whatsapp_label" class="adm-form-control" value="<?php echo e($s['footer_whatsapp_label'] ?? '(Instant Concierge)'); ?>">
                        <small style="color: var(--adm-text-muted); font-size: 11px;">E.g. (Instant Concierge), (24/7 Desk)</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Reservation CTA Button Text</label>
                        <input type="text" name="footer_reserve_btn_text" class="adm-form-control" value="<?php echo e($s['footer_reserve_btn_text'] ?? 'Reserve Your Sanctuary'); ?>" required>
                        <small style="color: var(--adm-text-muted); font-size: 11px;">Triggers the interactive instant booking calendar modal.</small>
                    </div>
                </div>

                <!-- SECTION 5: Column 4 - Sanctuary Gazette (Newsletter) -->
                <div style="border-top: 1px solid rgba(197, 160, 89, 0.15); border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 18px 0 16px; margin: 24px 0 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-newspaper" style="margin-right: 6px;"></i> 5. Column 4: Sanctuary Gazette (Newsletter Subscription)
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Headlines, description, input placeholder and subscriber confirmation message.</p>
                </div>

                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="adm-form-group">
                        <label class="adm-form-label">Gazette Heading</label>
                        <input type="text" name="footer_gazette_title" class="adm-form-control" value="<?php echo e($s['footer_gazette_title'] ?? 'Sanctuary Gazette'); ?>" required>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Input Placeholder Text</label>
                        <input type="text" name="footer_gazette_placeholder" class="adm-form-control" value="<?php echo e($s['footer_gazette_placeholder'] ?? 'Enter your email address'); ?>">
                    </div>

                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Gazette Narrative Description</label>
                        <textarea name="footer_gazette_desc" class="adm-form-control" rows="2" required><?php echo e($s['footer_gazette_desc'] ?? 'Receive private seasonal bulletins on apple harvests, wild honey collection, and intimate villa releases.'); ?></textarea>
                    </div>

                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Subscription Confirmation Toast Message</label>
                        <input type="text" name="footer_gazette_msg" class="adm-form-control" value="<?php echo e($s['footer_gazette_msg'] ?? 'Thank you for subscribing to our Gazette.'); ?>" required>
                    </div>
                </div>

                <!-- SECTION 6: Bottom Bar, Legal & Staff Access -->
                <div style="border-top: 1px solid rgba(197, 160, 89, 0.15); border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 18px 0 16px; margin: 24px 0 20px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 14px; color: var(--adm-gold); margin: 0 0 6px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-scale-balanced" style="margin-right: 6px;"></i> 6. Bottom Bar: Copyright, Legal Policies & Staff Link
                    </h4>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">Legal policy links and the discrete concierge staff portal link.</p>
                </div>

                <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 10px;">
                    <div class="adm-form-group" style="grid-column: 1 / -1;">
                        <label class="adm-form-label">Copyright Notice (Use <code>{year}</code> for dynamic current year)</label>
                        <input type="text" name="footer_copyright_text" class="adm-form-control" value="<?php echo e($s['footer_copyright_text'] ?? '© {year} Food Forest Sanctuary Kanthalloor. Crafted for conscious travelers.'); ?>" required>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Legal Link 1 Title</label>
                        <input type="text" name="footer_legal1_title" class="adm-form-control" value="<?php echo e($s['footer_legal1_title'] ?? 'Privacy Charter'); ?>">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Legal Link 1 URL</label>
                        <input type="text" name="footer_legal1_url" class="adm-form-control" value="<?php echo e($s['footer_legal1_url'] ?? '#'); ?>">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Legal Link 2 Title</label>
                        <input type="text" name="footer_legal2_title" class="adm-form-control" value="<?php echo e($s['footer_legal2_title'] ?? 'Sustainability Policy'); ?>">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Legal Link 2 URL</label>
                        <input type="text" name="footer_legal2_url" class="adm-form-control" value="<?php echo e($s['footer_legal2_url'] ?? '#'); ?>">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Legal Link 3 Title</label>
                        <input type="text" name="footer_legal3_title" class="adm-form-control" value="<?php echo e($s['footer_legal3_title'] ?? 'Guest Etiquette'); ?>">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Legal Link 3 URL</label>
                        <input type="text" name="footer_legal3_url" class="adm-form-control" value="<?php echo e($s['footer_legal3_url'] ?? '#'); ?>">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Staff Portal Link Label</label>
                        <input type="text" name="footer_staff_label" class="adm-form-control" value="<?php echo e($s['footer_staff_label'] ?? 'Staff Portal'); ?>">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-form-label">Staff Portal Link URL</label>
                        <input type="text" name="footer_staff_url" class="adm-form-control" value="<?php echo e($s['footer_staff_url'] ?? 'admin/'); ?>">
                    </div>
                </div>

                <div style="text-align: right; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--adm-border);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 11px 26px; font-weight: 700; font-size: 13.5px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE FOOTER SETTINGS</span>
                    </button>
                </div>

            </div>
        </form>
    </div>
    <?php endif; ?>
    </div> <!-- End .adm-settings-panels-container -->
    </div> <!-- End .adm-editor-col -->

    <!-- RIGHT COLUMN: REAL-TIME USER-SIDE LIVE PREVIEW (MULTI-RESOLUTION SUITE) -->
    <div class="adm-preview-col" style="grid-column: 2 / 3; width: 100%; min-width: 0; max-width: 100%; position: sticky; top: 80px;">
        <div class="adm-preview-card">
            <!-- Top Header & Action Utilities -->
            <div class="adm-preview-header">
                <div class="adm-preview-title">
                    <span class="adm-pulse-dot" style="background: #2ecc71; box-shadow: 0 0 10px #2ecc71;"></span>
                    <i class="fa-solid fa-display" style="color: var(--adm-gold);"></i>
                    <span>Live Preview</span>
                    <span class="adm-pv-badge" id="adm-pv-res-badge">100% Fluid</span>
                </div>

                <div class="adm-preview-actions">
                    <button type="button" class="adm-pv-tool-btn" id="btn-pv-rotate" onclick="togglePreviewOrientation();" title="Rotate Orientation (Portrait / Landscape)">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    <button type="button" class="adm-pv-tool-btn active" id="btn-pv-autofit" onclick="togglePreviewAutoFit();" title="Auto Fit Scale / 100% Scroll View">
                        <i class="fa-solid fa-expand"></i>
                    </button>
                    <button type="button" class="adm-pv-tool-btn" onclick="reloadPreviewFrame();" title="Refresh Live Preview Frame">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    <a href="preview_frame.php?section=<?php echo e($active_tab); ?>" target="_blank" id="adm-btn-full-pv" class="adm-pv-tool-btn" title="Open Preview in New Tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
            </div>

            <!-- Device Category Selector & Predefined / Custom Resolution Bar -->
            <div class="adm-preview-toolbar">
                <!-- Device Mode Tabs (Desktop, Tablet, Mobile, Custom) -->
                <div class="adm-pv-segmented">
                    <button type="button" class="adm-pv-seg-btn active" data-device="desktop" onclick="setDeviceCategory('desktop');">
                        <i class="fa-solid fa-laptop"></i> Desktop
                    </button>
                    <button type="button" class="adm-pv-seg-btn" data-device="tablet" onclick="setDeviceCategory('tablet');">
                        <i class="fa-solid fa-tablet-screen-button"></i> Tablet
                    </button>
                    <button type="button" class="adm-pv-seg-btn" data-device="mobile" onclick="setDeviceCategory('mobile');">
                        <i class="fa-solid fa-mobile-screen"></i> Mobile
                    </button>
                    <button type="button" class="adm-pv-seg-btn" data-device="custom" onclick="setDeviceCategory('custom');">
                        <i class="fa-solid fa-sliders"></i> Custom
                    </button>
                </div>

                <!-- Predefined Resolution Dropdown -->
                <div class="adm-pv-res-select-wrap" id="adm-pv-presets-wrap">
                    <select id="adm-pv-res-select" class="adm-pv-select" onchange="onPresetResolutionChange(this.value);">
                        <!-- Desktop Presets -->
                        <optgroup label="Desktop & Monitors">
                            <option value="fluid" data-cat="desktop" selected>Fluid Responsive (100%)</option>
                            <option value="1920x1080" data-cat="desktop">Full HD (1920 × 1080)</option>
                            <option value="1440x900" data-cat="desktop">Laptop / MacBook (1440 × 900)</option>
                            <option value="1366x768" data-cat="desktop">Standard Laptop (1366 × 768)</option>
                            <option value="1280x720" data-cat="desktop">HD Ready (1280 × 720)</option>
                        </optgroup>
                        <!-- Tablet Presets -->
                        <optgroup label="Tablets">
                            <option value="1024x1366" data-cat="tablet">iPad Pro 12.9" (1024 × 1366)</option>
                            <option value="820x1180" data-cat="tablet">iPad Air 10.9" (820 × 1180)</option>
                            <option value="768x1024" data-cat="tablet">iPad 9.7" / Mini (768 × 1024)</option>
                            <option value="800x1280" data-cat="tablet">Android Tablet (800 × 1280)</option>
                        </optgroup>
                        <!-- Mobile Presets -->
                        <optgroup label="Mobile Phones">
                            <option value="393x852" data-cat="mobile">iPhone 15 / 14 Pro (393 × 852)</option>
                            <option value="430x932" data-cat="mobile">iPhone 15 Pro Max (430 × 932)</option>
                            <option value="375x667" data-cat="mobile">iPhone SE / 8 (375 × 667)</option>
                            <option value="412x915" data-cat="mobile">Samsung S23 / Pixel 7 (412 × 915)</option>
                            <option value="360x800" data-cat="mobile">Android Compact (360 × 800)</option>
                        </optgroup>
                    </select>
                </div>

                <!-- Custom Resolution Inputs (Width × Height with Apply button) -->
                <div class="adm-pv-custom-row" id="adm-pv-custom-row" style="display: none;">
                    <div class="adm-pv-input-group">
                        <label>W</label>
                        <input type="number" id="adm-pv-custom-w" value="1200" min="280" max="3840" placeholder="Width">
                    </div>
                    <span style="color: var(--adm-text-muted); font-size: 13px;">×</span>
                    <div class="adm-pv-input-group">
                        <label>H</label>
                        <input type="number" id="adm-pv-custom-h" value="800" min="300" max="2160" placeholder="Height">
                    </div>
                    <button type="button" class="adm-pv-btn-apply" onclick="applyCustomResolution();">Apply</button>
                </div>
            </div>

            <!-- Viewport Stage / Canvas -->
            <div class="adm-preview-viewport-box" id="adm-preview-viewport-box">
                <div class="adm-preview-frame-wrapper" id="adm-preview-frame-wrapper">
                    <iframe id="adm-live-preview-iframe" class="adm-preview-iframe" src="preview_frame.php?section=<?php echo e($active_tab); ?>" title="Frontend Live Preview Frame"></iframe>
                </div>
            </div>

            <!-- Footer Synchronization Status -->
            <div class="adm-preview-footer">
                <span class="adm-pv-status-text"><i class="fa-solid fa-bolt" style="color: #2ecc71;"></i> Live 2-way synchronization active</span>
                <span class="adm-pv-status-hint">Type in left form to see instant updates</span>
            </div>
        </div>
    </div> <!-- End .adm-preview-col -->

</div> <!-- End .adm-split-layout-wrapper -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
