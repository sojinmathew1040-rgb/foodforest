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
    'backup' => 'CARD 16 • MYSQL DATABASE BACKUP & RESTORE'
];
if (!array_key_exists($active_tab, $tab_titles)) {
    $active_tab = 'climate';
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

        // 1. Estate & Branding Card
        if ($form_type === 'estate_settings') {
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
            $keys = ['welcome_badge', 'welcome_title', 'welcome_paragraph', 'welcome_image'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            if (empty($alert_message)) {
                $alert_message = 'Sanctuary philosophy narrative & featured portrait updated.';
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
            $keys = ['why_badge', 'why_title', 'why_desc', 'why_image'];
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
                if (empty($photos)) {
                    $photos = ['assets/images/01 (10).jpeg'];
                }
                $primary_img = $photos[0];
                $photos_json = json_encode(array_values($photos));

                if (!empty($title) && !empty($desc)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM sanctuary_spots")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO sanctuary_spots 
                        (spot_number, title, subtitle_tag, category, elevation, temperature, description, aroma, sound, image_url, photos, cta_text, cta_link, x_coord, y_coord, display_order, is_active) 
                        VALUES (?, ?, '', 'nature', '', '', ?, '', '', ?, ?, '', '', ?, ?, ?, 1)");
                    $ins->execute([$spot_num, $title, $desc, $primary_img, $photos_json, $x_coord, $y_coord, $max_order + 1]);
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
                        spot_number = ?, title = ?, description = ?, x_coord = ?, y_coord = ?, image_url = ?, photos = ? 
                        WHERE id = ?");

                    foreach ($_POST['spot_id'] as $idx => $sp_id) {
                        $s_num = (int)($_POST['spot_number'][$idx] ?? 1);
                        $s_title = trim($_POST['spot_title'][$idx] ?? '');
                        $s_desc = trim($_POST['spot_desc'][$idx] ?? '');
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

                        $upd_spot->execute([$s_num, $s_title, $s_desc, $s_x, $s_y, $primary_img, $photos_json, (int)$sp_id]);
                    }
                }
                $alert_message = 'Sanctuary estate map spots, multiple photos & route trails successfully updated.';
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

        // 10. Visual Diary (Gallery) Card (Header + Gallery Photos)
        elseif ($form_type === 'gallery_settings') {
            if (!empty($_POST['delete_gal_id'])) {
                $del_id = (int)$_POST['delete_gal_id'];
                $del = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Photograph permanently removed from gallery archive.';
            } elseif (($_POST['action'] ?? '') === 'add_gallery') {
                $gt = trim($_POST['new_gal_title'] ?? '');
                $gtag = trim($_POST['new_gal_tag'] ?? 'SANCTUARY CAPTURE');
                $gcat = trim($_POST['new_gal_category'] ?? 'Landscape');
                $gcap = trim($_POST['new_gal_caption'] ?? '');
                $gimg = trim($_POST['new_gal_image'] ?? 'assets/images/01 (1).jpeg');
                if (!empty($_FILES['new_gal_image_file']['name'])) {
                    $up = handle_image_upload($_FILES['new_gal_image_file'], 'gallery');
                    if ($up['success']) {
                        $gimg = $up['path'];
                    }
                }
                if (!empty($gt) && !empty($gimg)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM gallery")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO gallery (title, caption, tag, category, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$gt, $gcap, $gtag, $gcat, $gimg, $max_order + 1]);
                    $alert_message = 'New photograph successfully added to the Visual Chronicle!';
                } else {
                    $alert_message = 'Photograph title and image are required.';
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

                // Update individual photos
                if (isset($_POST['gallery_id']) && is_array($_POST['gallery_id'])) {
                    $upd_gal = $pdo->prepare("UPDATE gallery SET title = ?, tag = ?, caption = ?, image_url = ? WHERE id = ?");
                    foreach ($_POST['gallery_id'] as $idx => $gid) {
                        $gt = trim($_POST['gal_title'][$idx] ?? '');
                        $gtag = trim($_POST['gal_tag'][$idx] ?? '');
                        $gcap = trim($_POST['gal_caption'][$idx] ?? '');
                        $gimg = trim($_POST['gal_image'][$idx] ?? '');
                        if (isset($_FILES['gal_image_file'])) {
                            $up = handle_indexed_image_upload($_FILES['gal_image_file'], $idx, 'gallery');
                            if ($up['success']) {
                                $gimg = $up['path'];
                            }
                        }
                        $upd_gal->execute([$gt, $gtag, $gcap, $gimg, (int)$gid]);
                    }
                }
                $alert_message = 'Visual Chronicle (Gallery) header & photos successfully updated.';
            }
        }

        // 11. Guest Reflections (Testimonials) Card (Header + All Testimonials)
        elseif ($form_type === 'testimonials_settings') {
            if (!empty($_POST['delete_testimonial_id'])) {
                $del_id = (int)$_POST['delete_testimonial_id'];
                $del = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Guest reflection testimonial deleted successfully.';
            } elseif (($_POST['action'] ?? '') === 'add_testimonial') {
                $name = trim($_POST['new_guest_name'] ?? '');
                $location = trim($_POST['new_guest_location'] ?? '');
                $stay_badge = trim($_POST['new_stay_badge'] ?? 'CANOPY TREEHOUSE');
                $stars = max(1, min(5, (int)($_POST['new_stars'] ?? 5)));
                $quote = trim($_POST['new_quote'] ?? '');
                if (!empty($name) && !empty($quote)) {
                    $parts = preg_split('/\s+/', $name);
                    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                    if (empty($initials)) $initials = 'FF';
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM testimonials")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO testimonials (guest_name, guest_location, stay_badge, stars, quote, initials, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$name, $location, $stay_badge, $stars, $quote, $initials, $max_order + 1]);
                    $alert_message = 'New verified guest testimonial created and published successfully!';
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
                    $upd_test = $pdo->prepare("UPDATE testimonials SET guest_name = ?, guest_location = ?, stay_badge = ?, stars = ?, quote = ? WHERE id = ?");
                    foreach ($_POST['testimonial_id'] as $idx => $tid) {
                        $gn = trim($_POST['guest_name'][$idx] ?? '');
                        $gl = trim($_POST['guest_location'][$idx] ?? '');
                        $sb = trim($_POST['stay_badge'][$idx] ?? '');
                        $st = intval($_POST['stars'][$idx] ?? 5);
                        $q = trim($_POST['quote'][$idx] ?? '');
                        $upd_test->execute([$gn, $gl, $sb, $st, $q, (int)$tid]);
                    }
                }
                $alert_message = 'Guest Reflections & client reviews successfully updated.';
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

            if (!password_verify($current_pwd, $hash)) {
                $alert_message = 'Current access key is incorrect. Password changes not saved.';
                $alert_type = 'error';
            } else {
                if (!empty($new_pwd)) {
                    if (strlen($new_pwd) < 6) {
                        $alert_message = 'New access key must be at least 6 characters long.';
                        $alert_type = 'error';
                    } elseif ($new_pwd !== $confirm_pwd) {
                        $alert_message = 'New access key and confirmation do not match.';
                        $alert_type = 'error';
                    } else {
                        $new_hash = password_hash($new_pwd, PASSWORD_BCRYPT);
                        $upd = $pdo->prepare("UPDATE admins SET full_name = ?, username = ?, email = ?, password_hash = ? WHERE id = ?");
                        $upd->execute([$full_name, $username, $email, $new_hash, $admin_id]);
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
    }
}

// Fetch all settings from MySQL
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$s = [];
while ($row = $settings_stmt->fetch()) {
    $s[$row['setting_key']] = $row['setting_value'];
}

// Fetch database records for dynamic card editing
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
ensure_rooms_pricing_columns($pdo);
$all_rooms = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_gallery = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
ensure_seasons_table_exists($pdo);
$all_seasons = $pdo->query("SELECT * FROM seasons ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
ensure_sanctuary_spots_table_exists($pdo);
$all_sanctuary_spots = $pdo->query("SELECT * FROM sanctuary_spots ORDER BY spot_number ASC, display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

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
    'backup' => 'MYSQL DATABASE BACKUP & RESTORE'
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

// On Page Load, activate selected tab without jumping
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('section') || urlParams.get('tab') || '<?php echo e($active_tab); ?>';
    if (typeof window.switchSettingsTab === 'function') {
        window.switchSettingsTab(activeTab, null, null, false);
    }
});
</script>

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
    'backup' => 'edit_section.php?section=backup'
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'estate') ? 'is-active' : ''; ?>" id="pane-estate">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'whatsapp') ? 'is-active' : ''; ?>" id="pane-whatsapp">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'hero') ? 'is-active' : ''; ?>" id="pane-hero">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'climate') ? 'is-active' : ''; ?>" id="pane-climate">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'philosophy') ? 'is-active' : ''; ?>" id="pane-philosophy">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'why') ? 'is-active' : ''; ?>" id="pane-why">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'experiences') ? 'is-active' : ''; ?>" id="pane-experiences">
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
                                        <button type="submit" name="delete_exp_id" value="<?php echo $exp['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="event.stopPropagation(); return confirm('Permanently delete experience <?php echo e(addslashes($exp['title'])); ?>? This cannot be undone.');" title="Delete this experience">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'menu') ? 'is-active' : ''; ?>" id="pane-menu">
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
                                        <label class="adm-dish-active-toggle" style="display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: #fff; cursor: pointer; background: rgba(0,0,0,0.3); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);" onclick="event.stopPropagation();">
                                            <input type="checkbox" name="menu_active_<?php echo $m_item['id']; ?>" value="1" <?php echo ($m_item['is_active'] ? 'checked' : ''); ?> onclick="event.stopPropagation();">
                                            <span>Active</span>
                                        </label>
                                        <button type="submit" 
                                                form="form-delete-menu-<?php echo $m_item['id']; ?>" 
                                                class="adm-btn-action danger" 
                                                style="padding: 5px 10px; font-size: 11px;"
                                                onclick="event.stopPropagation(); return confirm('Are you sure you want to permanently delete \'<?php echo addslashes($m_item['heading']); ?>\' from the menu?');"
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'seasons') ? 'is-active' : ''; ?>" id="pane-seasons">
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
                                        <button type="submit" name="delete_season_id" value="<?php echo $season['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="event.stopPropagation(); return confirm('Permanently delete season <?php echo e(addslashes($season['title'])); ?>? This cannot be undone.');" title="Delete this season card">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'sanctuary_map') ? 'is-active' : ''; ?>" id="pane-sanctuary_map">
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
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">SANCTUARY ESTATE MAP & MOUNTAIN ROUTE TRAILS</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Manage estate locations, common kitchen, villas, pool, BBQ shed & connected hiking route trail.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-spot');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW SPOT / WAYPOINT
                </button>
                <button type="submit" form="form-edit-sanctuary_map" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">

            <!-- Mountain Route Trail Ribbon -->
            <div style="background: linear-gradient(135deg, rgba(20, 42, 29, 0.8), rgba(12, 25, 18, 0.95)); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 10px; padding: 14px 18px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-route"></i> Route Map Sequence:
                    </span>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <?php if (!empty($all_sanctuary_spots)): ?>
                            <?php foreach ($all_sanctuary_spots as $sidx => $sp): ?>
                                <span style="background: rgba(255,255,255,0.08); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 6px; padding: 3px 8px; font-size: 11.5px; color: #FFFFFF; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                    <strong style="color: var(--adm-gold);"><?php echo sprintf('%02d', $sp['spot_number']); ?></strong>
                                    <span><?php echo e(mb_strimwidth($sp['title'], 0, 18, '...')); ?></span>
                                </span>
                                <?php if ($sidx < count($all_sanctuary_spots) - 1): ?>
                                    <i class="fa-solid fa-arrow-right" style="font-size: 10px; color: var(--adm-gold); opacity: 0.7;"></i>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--adm-text-muted);">No spots registered yet.</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="font-size: 11.5px; color: var(--adm-text-secondary); display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-circle-info" style="color: var(--adm-gold);"></i>
                    <span>Spots connect in numerical sequence (1 → 2 → 3...) on the live map.</span>
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
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">REGISTER NEW ESTATE SPOT / ROUTE WAYPOINT</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-spot');" title="Close Drawer">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: 140px 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Route Spot Number *</label>
                            <input type="number" min="1" max="99" name="new_spot_number" class="adm-form-control" value="<?php echo count($all_sanctuary_spots) + 1; ?>" required style="font-weight: bold; color: var(--adm-gold);">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Spot Title / Name *</label>
                            <input type="text" name="new_spot_title" class="adm-form-control" placeholder="e.g. Cedar Treehouse / Organic Kitchen" required>
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
                            <div id="new_spot_photos_preview" style="margin-top: 8px;">
                                <span style="font-size: 11px; color: var(--adm-text-muted);">No new photos selected yet. (Default farm photo will be used if none uploaded)</span>
                            </div>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Spot Description *</label>
                        <textarea name="new_spot_desc" rows="3" class="adm-form-control" placeholder="Describe this mountain spot, atmosphere and experience..." required></textarea>
                    </div>

                    <!-- Default Coordinates for New Spot (Positioned via Master Map) -->
                    <input type="hidden" id="new_spot_x" name="new_spot_x" value="50">
                    <input type="hidden" id="new_spot_y" name="new_spot_y" value="50">
                    <div style="background: rgba(197, 160, 89, 0.08); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                        <i class="fa-solid fa-mountain-sun" style="color: var(--adm-gold); font-size: 22px; flex-shrink: 0;"></i>
                        <span style="font-size: 12px; color: var(--adm-text-secondary); line-height: 1.5;">
                            <strong>Dynamic Waypoint Studio:</strong> Once published, this spot's waypoint pin will automatically appear in the <strong>Sanctuary Master Map Studio</strong> below. You can drag and drop it anywhere on the mountain terrain using your mouse!
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
            // Calculate dynamic initial SVG route trail for Master Map Studio (viewBox 0 0 800 520)
            $admin_route_pts = [];
            if (!empty($all_sanctuary_spots)) {
                foreach ($all_sanctuary_spots as $sp) {
                    $admin_route_pts[] = [
                        'x' => ($sp['x_coord'] / 100.0) * 800,
                        'y' => ($sp['y_coord'] / 100.0) * 520
                    ];
                }
            }
            $admin_route_d = '';
            if (count($admin_route_pts) > 1) {
                $admin_route_d = "M " . round($admin_route_pts[0]['x'], 1) . "," . round($admin_route_pts[0]['y'], 1);
                for ($i = 0; $i < count($admin_route_pts) - 1; $i++) {
                    $p0 = $admin_route_pts[$i];
                    $p1 = $admin_route_pts[$i + 1];
                    $mx = ($p0['x'] + $p1['x']) / 2;
                    $my = ($p0['y'] + $p1['y']) / 2;
                    $dx = $p1['x'] - $p0['x'];
                    $dy = $p1['y'] - $p0['y'];
                    $cx = $mx - ($dy * 0.12);
                    $cy = $my + ($dx * 0.12);
                    $admin_route_d .= " Q " . round($cx, 1) . "," . round($cy, 1) . " " . round($p1['x'], 1) . "," . round($p1['y'], 1);
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
                            <span>Sanctuary Master Map Studio</span>
                        </h4>
                        <div class="admin-map-studio-hud">
                            <span class="admin-map-hud-pill">
                                <i class="fa-solid fa-hand-pointer" style="color: var(--adm-gold);"></i>
                                <span>Drag pins with mouse to position</span>
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

                            <!-- Live Connected Trail Lines (Redrawn Dynamically on Drag) -->
                            <path id="admin-master-trail-aura" d="<?php echo $admin_route_d; ?>" 
                                  fill="none" stroke="rgba(197, 160, 89, 0.28)" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" filter="url(#admin-map-glow)" />
                            <path id="admin-master-trail-line" d="<?php echo $admin_route_d; ?>" 
                                  fill="none" stroke="#C5A059" stroke-width="2.5" stroke-dasharray="6,6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <!-- Draggable Pins Layer -->
                        <div id="admin-master-pins-layer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                            <?php if (!empty($all_sanctuary_spots)): ?>
                                <?php foreach ($all_sanctuary_spots as $idx => $sp): ?>
                                    <div class="admin-master-pin"
                                         id="master-pin-<?php echo $idx; ?>"
                                         data-idx="<?php echo $idx; ?>"
                                         data-spot-num="<?php echo (int)$sp['spot_number']; ?>"
                                         data-title="<?php echo e($sp['title']); ?>"
                                         style="left: <?php echo (float)$sp['x_coord']; ?>%; top: <?php echo (float)$sp['y_coord']; ?>%; pointer-events: auto;"
                                         title="Drag to reposition Spot #<?php echo sprintf('%02d', $sp['spot_number']); ?>">
                                        <div class="admin-pin-pulse"></div>
                                        <div class="admin-pin-core">
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
                            <i class="fa-solid fa-route" style="color: #2ecc71;"></i>
                            <span style="color: #FFFFFF; font-weight: 600;">Hiking Route Sequence:</span>
                            <span style="color: var(--adm-text-secondary);"><?php echo count($all_sanctuary_spots); ?> Waypoints Connected (1 → 2 → 3...)</span>
                        </div>
                        <div id="admin-map-live-tip" style="color: var(--adm-text-muted); font-size: 11.5px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-lightbulb" style="color: var(--adm-gold);"></i>
                            <span>Click and drag any pin with your mouse to reposition. Save button below commits changes.</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Spots Catalog Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-map-pin"></i> Registered Sanctuary Spots & Route Waypoints (<?php echo count($all_sanctuary_spots); ?>)
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
                                                <span style="background: #C5A059; color: #101F15; font-weight: 800; font-size: 11px; padding: 2px 7px; border-radius: 4px;">
                                                    #<?php echo sprintf('%02d', $sp['spot_number']); ?>
                                                </span>
                                                <span style="color: #FFFFFF; font-weight: 700; font-size: 13.5px;">
                                                    <?php echo e($sp['title']); ?>
                                                </span>
                                            </div>
                                            <div class="adm-accordion-meta-sub">
                                                <span><i class="fa-solid fa-location-dot" style="color: var(--adm-gold);"></i> Coords: X:<?php echo round((float)$sp['x_coord'], 1); ?>% Y:<?php echo round((float)$sp['y_coord'], 1); ?>%</span>
                                                <span>• <i class="fa-solid fa-images"></i> <?php echo count($sp['photos_list'] ?? []); ?> Photos</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                        <!-- Interactive Map Coordinate Badge & Locator -->
                                        <button type="button" onclick="event.stopPropagation(); focusPinOnMasterMap(<?php echo $idx; ?>);" class="adm-btn-action" style="padding: 3px 9px; font-size: 11px; background: rgba(197, 160, 89, 0.12); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px;" title="Highlight on Master Map">
                                            <i class="fa-solid fa-location-crosshairs"></i>
                                            <span id="card-coord-badge-<?php echo $idx; ?>">X: <?php echo round((float)$sp['x_coord'], 1); ?>%</span>
                                        </button>
                                        <button type="submit" name="delete_spot_id" value="<?php echo $sp['id']; ?>" class="adm-btn-danger-outline" onclick="event.stopPropagation(); return confirm('Delete this spot waypoint from the estate map?');" title="Delete Spot">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                        <span class="adm-accordion-chevron">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Accordion Body -->
                                <div class="adm-accordion-body">
                                    <!-- Route # and Spot Title -->
                                    <div style="display: grid; grid-template-columns: 90px 1fr; gap: 12px; margin-bottom: 14px;">
                                        <div class="adm-form-group">
                                            <label class="adm-form-label" style="font-size: 10px;">Route #</label>
                                            <input type="number" min="1" max="99" name="spot_number[]" class="adm-form-control" value="<?php echo (int)$sp['spot_number']; ?>" required style="font-weight: 700; color: var(--adm-gold);" oninput="syncSpotNumToPin(<?php echo $idx; ?>, this.value);">
                                        </div>
                                        <div class="adm-form-group">
                                            <label class="adm-form-label" style="font-size: 10px;">Spot Title</label>
                                            <input type="text" name="spot_title[]" class="adm-form-control" value="<?php echo e($sp['title']); ?>" required oninput="syncSpotTitleToPin(<?php echo $idx; ?>, this.value);">
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
                                        <textarea name="spot_desc[]" rows="2" class="adm-form-control" style="font-size: 12px;" required><?php echo e($sp['description']); ?></textarea>
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
    </div>

    <!-- -------------------------------------------------------------
         PANEL 04: VILLAS & COTTAGES (DYNAMIC TARIFFS & SPECS)
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'rooms') ? 'is-active' : ''; ?>" id="pane-rooms">
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
                        <div class="adm-room-item-card adm-accordion-card" data-stay-type="<?php echo e($stay_type); ?>" data-structure-type="<?php echo e($structure_type); ?>">
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
                                    <button type="submit" name="delete_room_id" value="<?php echo $room['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="event.stopPropagation(); return confirm('Permanently delete villa <?php echo e(addslashes($room['title'])); ?>? This cannot be undone.');" title="Delete this villa">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'gallery') ? 'is-active' : ''; ?>" id="pane-gallery">
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
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">VISUAL CHRONICLE / GALLERY (DYNAMIC CMS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Directly manage sanctuary photography archive, add new photos, or remove existing photos.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW PHOTOGRAPH
                </button>
                <button type="submit" form="form-edit-gallery" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <!-- Expandable Add New Photograph Drawer -->
            <div id="drawer-add-gallery" class="adm-add-new-drawer" style="display: none;">
                <form action="edit_section.php?section=gallery" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="gallery_settings">
                    <input type="hidden" name="action" value="add_gallery">
                    <input type="hidden" name="active_tab" value="gallery">

                    <div class="adm-drawer-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 16px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">UPLOAD / REGISTER NEW GALLERY PHOTOGRAPH</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-gallery');" title="Close Drawer">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Photograph Title *</label>
                            <input type="text" name="new_gal_title" class="adm-form-control" placeholder="e.g. Morning Mist Over High Shola Valley" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Tag</label>
                            <input type="text" name="new_gal_tag" class="adm-form-control" placeholder="e.g. ALPINE HORIZON · 1,600M" value="SANCTUARY ARCHIVE">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Category</label>
                            <select name="new_gal_category" class="adm-form-control" style="cursor: pointer;">
                                <option value="Landscape" selected>Landscape</option>
                                <option value="Villas & Stays">Villas & Stays</option>
                                <option value="Handcrafted Living">Handcrafted Living</option>
                                <option value="Gastronomy">Gastronomy</option>
                                <option value="Orchards">Orchards</option>
                                <option value="Nightscape">Nightscape</option>
                                <option value="Architecture">Architecture</option>
                                <option value="Flora">Flora</option>
                            </select>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label">Photograph File</label>
                        <div class="adm-uploader-card">
                            <div class="adm-uploader-preview-box">
                                <img id="new_gal_preview" src="../assets/images/01 (1).jpeg" alt="Gallery Preview" onerror="this.src='../assets/images/treehouse_exterior.png';">
                            </div>
                            <div class="adm-uploader-controls">
                                <div class="adm-uploader-btn-wrap">
                                    <label class="adm-uploader-btn" for="new_gal_image_file">
                                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                    </label>
                                    <input type="file" name="new_gal_image_file" id="new_gal_image_file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'new_gal_preview', 'new_gal_info');">
                                    <span id="new_gal_info" class="adm-file-info-badge"></span>
                                </div>
                                <div class="adm-uploader-hint">
                                    <i class="fa-solid fa-circle-info"></i> JPG, PNG, WEBP, or GIF up to 15MB.
                                </div>
                                <input type="hidden" name="new_gal_image" value="assets/images/01 (1).jpeg">
                            </div>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Caption / Description</label>
                        <textarea name="new_gal_caption" rows="2" class="adm-form-control" placeholder="Describe the scene, photography angle, or season..."></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-add-gallery');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>PUBLISH PHOTOGRAPH</span>
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
                            <input type="text" name="gallery_title" class="adm-form-control" value="<?php echo e($s['gallery_title'] ?? 'Glimpses of the Sanctuary'); ?>" required>
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label">Description Paragraph</label>
                        <textarea name="gallery_desc" rows="2" class="adm-form-control" required><?php echo e($s['gallery_desc'] ?? 'A living photographic archive of slow living, morning fog across the high ranges, and handcrafted earthen architecture.'); ?></textarea>
                    </div>
                </div>

                <!-- Photos List Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-images"></i> Sanctuary Photography Archive (<?php echo count($all_gallery); ?>)
                    </h4>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-gallery', true);" title="Expand all photo cards">
                            <i class="fa-solid fa-angles-down"></i> Expand All
                        </button>
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-gallery', false);" title="Collapse all photo cards">
                            <i class="fa-solid fa-angles-up"></i> Collapse All
                        </button>
                        <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');" style="padding: 6px 12px; font-size: 11.5px;">
                            <i class="fa-solid fa-plus"></i> Add Another
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                    <?php if (empty($all_gallery)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-camera-retro" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No gallery photographs found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');">
                                <i class="fa-solid fa-plus-circle"></i> Add First Photograph
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_gallery as $idx => $photo): ?>
                        <div class="adm-gal-item-card adm-accordion-card">
                            <input type="hidden" name="gallery_id[]" value="<?php echo $photo['id']; ?>">

                            <!-- Accordion Header Bar -->
                            <div class="adm-accordion-header" onclick="toggleAccordion(this, event);">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                    <div class="adm-accordion-thumb-box">
                                        <img id="gal_prev_thumb_<?php echo $photo['id']; ?>" src="<?php echo admin_img_src($photo['image_url']); ?>" alt="Photo" onerror="this.src='../assets/images/treehouse_exterior.png';">
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
                                            <span><?php echo e($photo['caption'] ?: 'Sanctuary High-Range visual'); ?></span>
                                            <span>• ID: <?php echo $photo['id']; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                    <button type="submit" name="delete_gal_id" value="<?php echo $photo['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="event.stopPropagation(); return confirm('Permanently delete this photograph (<?php echo e(addslashes($photo['title'])); ?>)?');" title="Delete this photograph">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </button>
                                    <span class="adm-accordion-chevron">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Accordion Body -->
                            <div class="adm-accordion-body">
                                <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                                    <div style="flex-grow: 1;">
                                        <label class="adm-form-label" style="font-size: 11px;">Photo Title *</label>
                                        <input type="text" name="gal_title[]" class="adm-form-control" value="<?php echo e($photo['title']); ?>" required>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                    <div>
                                        <label class="adm-form-label" style="font-size: 11px;">Tag</label>
                                        <input type="text" name="gal_tag[]" class="adm-form-control" value="<?php echo e($photo['tag']); ?>">
                                    </div>
                                    <div>
                                        <label class="adm-form-label" style="font-size: 11px;">Caption</label>
                                        <input type="text" name="gal_caption[]" class="adm-form-control" value="<?php echo e($photo['caption']); ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="adm-form-label" style="font-size: 11px;">Photograph File</label>
                                    <div class="adm-uploader-card adm-uploader-compact">
                                        <div class="adm-uploader-preview-box">
                                            <img id="gal_prev_<?php echo $photo['id']; ?>" src="<?php echo admin_img_src($photo['image_url']); ?>" alt="Photo" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                        </div>
                                        <div class="adm-uploader-controls">
                                            <div class="adm-uploader-btn-wrap">
                                                <label class="adm-uploader-btn" for="gal_file_<?php echo $photo['id']; ?>" style="padding: 5px 12px; font-size: 11px;">
                                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload Photo from Device
                                                </label>
                                                <input type="file" name="gal_image_file[<?php echo $idx; ?>]" id="gal_file_<?php echo $photo['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'gal_prev_<?php echo $photo['id']; ?>', 'gal_info_<?php echo $photo['id']; ?>');">
                                                <span id="gal_info_<?php echo $photo['id']; ?>" class="adm-file-info-badge"></span>
                                            </div>
                                            <input type="hidden" name="gal_image[]" value="<?php echo e($photo['image_url']); ?>">
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
                        <span>SAVE GALLERY CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 11: GUEST REFLECTIONS (TESTIMONIALS DYNAMIC CMS)
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'testimonials') ? 'is-active' : ''; ?>" id="pane-testimonials">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon blue"><i class="fa-solid fa-comment-dots"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                        </span>
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 11</span>
                    </div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">GUEST STORIES & REFLECTIONS (DYNAMIC CMS)</h3>
                    <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Directly edit, create new, or delete traveler reviews, star ratings, and reflections.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-testimonial');">
                    <i class="fa-solid fa-plus-circle"></i> + ADD NEW TESTIMONIAL
                </button>
                <button type="submit" form="form-edit-testimonials" class="adm-btn-action gold" style="padding: 10px 22px; font-weight: 700; font-size: 13px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>SAVE CHANGES</span>
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <!-- Expandable Add New Testimonial Drawer -->
            <div id="drawer-add-testimonial" class="adm-add-new-drawer" style="display: none;">
                <form action="edit_section.php?section=testimonials" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="form_type" value="testimonials_settings">
                    <input type="hidden" name="action" value="add_testimonial">
                    <input type="hidden" name="active_tab" value="testimonials">

                    <div class="adm-drawer-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-plus" style="color: #2ecc71; font-size: 16px;"></i>
                            <h4 style="color: #FFFFFF; margin: 0; font-size: 14px; font-family: var(--adm-font-title); letter-spacing: 0.5px;">CREATE & PUBLISH NEW GUEST REFLECTION</h4>
                        </div>
                        <button type="button" class="adm-drawer-close" onclick="toggleAddNewDrawer('drawer-add-testimonial');" title="Close Drawer">✕</button>
                    </div>

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Guest Full Name *</label>
                            <input type="text" name="new_guest_name" class="adm-form-control" placeholder="e.g. Ananya & Siddharth Nair" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">City / Country / Stay Date *</label>
                            <input type="text" name="new_guest_location" class="adm-form-control" placeholder="e.g. Kochi, India · Stayed Nov 2025" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Stayed At / Badge *</label>
                            <input type="text" name="new_stay_badge" class="adm-form-control" placeholder="e.g. CANOPY TREEHOUSE" value="CANOPY TREEHOUSE" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Rating Stars *</label>
                            <select name="new_stars" class="adm-form-control" required style="cursor: pointer;">
                                <option value="5" selected>5 Stars (Exceptional ★★★★★)</option>
                                <option value="4">4 Stars (Very Good ★★★★☆)</option>
                                <option value="3">3 Stars (Good ★★★☆☆)</option>
                                <option value="2">2 Stars (Fair ★★☆☆☆)</option>
                                <option value="1">1 Star (Poor ★☆☆☆☆)</option>
                            </select>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Guest Quote / Reflection Narrative *</label>
                        <textarea name="new_quote" rows="3" class="adm-form-control" placeholder="Describe the guest's authentic experience, breakfast impressions, starry nights, or mudhouse comfort..." required></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.12);" onclick="toggleAddNewDrawer('drawer-add-testimonial');">Cancel</button>
                        <button type="submit" class="adm-btn-action emerald" style="font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>PUBLISH NEW TESTIMONIAL</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Main Edit Existing Form -->
            <form id="form-edit-testimonials" action="edit_section.php?section=testimonials" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="testimonials_settings">
                <input type="hidden" name="active_tab" value="testimonials">

                <!-- Header Copy Box -->
                <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #60a5fa; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 14px;">Section Header Copy</span>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 14px;">
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

                <!-- Dynamic Reviews List Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-star"></i> Verified Guest Testimonials (<?php echo count($all_testimonials); ?>)
                    </h4>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-testimonials', true);" title="Expand all testimonial cards">
                            <i class="fa-solid fa-angles-down"></i> Expand All
                        </button>
                        <button type="button" class="adm-accordion-ctrl-btn" onclick="expandAllAccordion('pane-testimonials', false);" title="Collapse all testimonial cards">
                            <i class="fa-solid fa-angles-up"></i> Collapse All
                        </button>
                        <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-testimonial');" style="padding: 6px 12px; font-size: 11.5px;">
                            <i class="fa-solid fa-plus"></i> Add Another
                        </button>
                    </div>
                </div>

                <!-- Testimonials Grid -->
                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                    <?php if (empty($all_testimonials)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-comment-dots" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No guest testimonials found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-testimonial');">
                                <i class="fa-solid fa-plus-circle"></i> Add First Testimonial
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_testimonials as $idx => $t): ?>
                        <div class="adm-test-item-card adm-accordion-card">
                            <input type="hidden" name="testimonial_id[]" value="<?php echo $t['id']; ?>">

                            <!-- Accordion Header Bar -->
                            <div class="adm-accordion-header" onclick="toggleAccordion(this, event);">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                    <div class="adm-accordion-thumb-box" style="background: rgba(59, 130, 246, 0.15); display: flex; align-items: center; justify-content: center; color: #60a5fa; font-weight: 800; font-size: 13px;">
                                        <span><?php echo strtoupper(substr(trim($t['guest_name']), 0, 2)); ?></span>
                                    </div>
                                    <div style="min-width: 0;">
                                        <div class="adm-accordion-meta-title">
                                            <span style="font-size: 10px; font-weight: 700; color: #60a5fa; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); padding: 2px 7px; border-radius: 6px; text-transform: uppercase;">
                                                REVIEW #<?php echo ($idx + 1); ?>
                                            </span>
                                            <span style="color: #FFFFFF; font-weight: 700; font-size: 13.5px;">
                                                <?php echo e($t['guest_name']); ?>
                                            </span>
                                            <span style="color: var(--adm-gold); font-size: 11px; margin-left: 4px;">
                                                <?php for ($i = 0; $i < (int)$t['stars']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                                            </span>
                                            <span class="adm-badge" style="background: rgba(197, 160, 89, 0.12); color: var(--adm-gold); font-size: 10px; padding: 2px 7px; border-radius: 6px;">
                                                <?php echo e($t['stay_badge']); ?>
                                            </span>
                                        </div>
                                        <div class="adm-accordion-meta-sub">
                                            <span><?php echo e($t['guest_location']); ?></span>
                                            <span>• "<?php echo e(mb_strimwidth($t['quote'], 0, 50, '...')); ?>"</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                    <button type="submit" name="delete_testimonial_id" value="<?php echo $t['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="event.stopPropagation(); return confirm('Permanently delete review by <?php echo e(addslashes($t['guest_name'])); ?>? This cannot be undone.');" title="Delete this review">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </button>
                                    <span class="adm-accordion-chevron">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Accordion Body -->
                            <div class="adm-accordion-body">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Guest Name</label>
                                        <input type="text" name="guest_name[]" class="adm-form-control" value="<?php echo e($t['guest_name']); ?>" required>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">City / Country</label>
                                        <input type="text" name="guest_location[]" class="adm-form-control" value="<?php echo e($t['guest_location']); ?>" required>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Stayed At / Badge</label>
                                        <input type="text" name="stay_badge[]" class="adm-form-control" value="<?php echo e($t['stay_badge']); ?>" required>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label">Stars (1-5)</label>
                                        <input type="number" min="1" max="5" name="stars[]" class="adm-form-control" value="<?php echo (int)$t['stars']; ?>" required>
                                    </div>
                                </div>

                                <div class="adm-form-group">
                                    <label class="adm-form-label">Guest Quote / Reflection</label>
                                    <textarea name="quote[]" rows="3" class="adm-form-control" required><?php echo e($t['quote']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE TESTIMONIALS CONFIGURATION</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 14: CONTENT & IMAGE PROTECTION
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'protection') ? 'is-active' : ''; ?>" id="pane-protection">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'security') ? 'is-active' : ''; ?>" id="pane-security">
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
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'backup') ? 'is-active' : ''; ?>" id="pane-backup">
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
