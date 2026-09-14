<?php
// =========================================================================
// Food Forest Sanctuary — Estate Settings & Cards-Based CMS Hub
// Modeled after Delight Builders: Card-by-Card Frontend Section Management
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';

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
$active_tab = $_GET['tab'] ?? 'estate';

$tab_titles = [
    'estate' => 'CARD 01 • ESTATE BRANDING & OPERATIONAL IDENTITY',
    'whatsapp' => 'CARD 02 • WHATSAPP & CONCIERGE CHANNELS',
    'hero' => 'CARD 03 • HERO MARQUEE & VISUAL BACKDROP',
    'climate' => 'CARD 04 • CLIMATE & HIGH RANGES ACCOLADES',
    'philosophy' => 'CARD 05 • SANCTUARY PHILOSOPHY & WELCOME MANIFESTO',
    'why' => 'CARD 06 • WHY FOOD FOREST? (EARTHEN COB & AGROFORESTRY)',
    'experiences' => 'CARD 07 • CURATED EXPERIENCES & RITUALS',
    'seasons' => 'CARD 08 • SEASONS OF KANTHALLOOR & HARVEST',
    'rooms' => 'CARD 09 • VILLAS, COTTAGES & LIVE TARIFFS',
    'gallery' => 'CARD 10 • VISUAL DIARY & PHOTOGRAPHY ARCHIVE',
    'testimonials' => 'CARD 11 • GUEST REFLECTIONS & VERIFIED REVIEWS',
    'protection' => 'CARD 12 • CONTENT PROTECTION & DEVTOOLS SHIELD',
    'security' => 'CARD 13 • SECURITY & MASTER PASSWORD',
    'backup' => 'CARD 14 • MYSQL DATABASE BACKUP & RESTORE'
];
if (!array_key_exists($active_tab, $tab_titles)) {
    $active_tab = 'estate';
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $form_type = $_POST['form_type'] ?? '';
        $active_tab = $_POST['active_tab'] ?? 'estate';

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
            $keys = ['hero_eyebrow', 'hero_title', 'hero_desc', 'hero_bg_image'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            $alert_message = 'Hero section marquee text & background visual updated.';
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
            $keys = ['welcome_badge', 'welcome_title', 'welcome_paragraph', 'welcome_image'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            $alert_message = 'Sanctuary philosophy narrative & featured portrait updated.';
        }

        // 6. Why Farmstay Story Card
        elseif ($form_type === 'why_settings') {
            $keys = ['why_badge', 'why_title', 'why_desc', 'why_image'];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            $alert_message = 'Why Food Forest farmstay story & visual updated.';
        }

        // 7. Curated Experiences Card (Header + All Experiences in MySQL)
        elseif ($form_type === 'experiences_settings') {
            if (!empty($_POST['delete_exp_id'])) {
                $del_id = (int)$_POST['delete_exp_id'];
                $del = $pdo->prepare("DELETE FROM experiences WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Curated ritual experience removed successfully.';
            } elseif (($_POST['action'] ?? '') === 'add_experience') {
                $title = trim($_POST['new_exp_title'] ?? '');
                $badge = trim($_POST['new_exp_badge'] ?? 'INCLUDED IN STAY');
                $timing = trim($_POST['new_exp_timing'] ?? '2 Hours • Morning');
                $desc = trim($_POST['new_exp_desc'] ?? '');
                $img = trim($_POST['new_exp_image'] ?? 'assets/images/01 (18).jpeg');
                if (!empty($title) && !empty($desc)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM experiences")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO experiences (title, badge, timing, description, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$title, $badge, $timing, $desc, $img, $max_order + 1]);
                    $alert_message = 'New curated ritual experience added successfully!';
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
                    $upd_exp = $pdo->prepare("UPDATE experiences SET title = ?, badge = ?, timing = ?, description = ?, image_url = ? WHERE id = ?");
                    foreach ($_POST['exp_id'] as $idx => $eid) {
                        $t = trim($_POST['exp_title'][$idx] ?? '');
                        $b = trim($_POST['exp_badge'][$idx] ?? '');
                        $tm = trim($_POST['exp_timing'][$idx] ?? '');
                        $d = trim($_POST['exp_desc'][$idx] ?? '');
                        $img = trim($_POST['exp_image'][$idx] ?? '');
                        $upd_exp->execute([$t, $b, $tm, $d, $img, (int)$eid]);
                    }
                }
                $alert_message = 'Curated Experiences header & all individual rituals updated successfully.';
            }
        }

        // 8. Seasons of Kanthalloor Card (Header + 4 Dynamic Seasons)
        elseif ($form_type === 'seasons_settings') {
            $keys = [
                'seasons_badge', 'seasons_title', 'seasons_desc',
                'season_1_name', 'season_1_months', 'season_1_desc', 'season_1_image',
                'season_2_name', 'season_2_months', 'season_2_desc', 'season_2_image',
                'season_3_name', 'season_3_months', 'season_3_desc', 'season_3_image',
                'season_4_name', 'season_4_months', 'season_4_desc', 'season_4_image',
            ];
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }
            $alert_message = 'Seasons of Kanthalloor calendar & seasonal visuals updated.';
        }

        // 9. Villas & Accommodations Card (Header + Both Rooms & Nightly Tariffs)
        elseif ($form_type === 'rooms_settings') {
            if (!empty($_POST['delete_room_id'])) {
                $del_id = (int)$_POST['delete_room_id'];
                $del = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $del->execute([$del_id]);
                $alert_message = 'Villa dwelling successfully removed from sanctuary.';
            } elseif (($_POST['action'] ?? '') === 'add_room') {
                $title = trim($_POST['new_room_title'] ?? '');
                $slug = trim($_POST['new_room_slug'] ?? '');
                if (empty($slug) && !empty($title)) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                }
                $rate = floatval($_POST['new_room_rate'] ?? 0);
                $el = trim($_POST['new_room_elevation'] ?? '1,600m Elevation');
                $cap = intval($_POST['new_room_capacity'] ?? 2);
                $desc = trim($_POST['new_room_desc'] ?? '');
                $img = trim($_POST['new_room_image'] ?? 'assets/images/treehouse_exterior.png');
                if (!empty($title) && !empty($slug) && $rate > 0) {
                    $existing = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE slug = ?");
                    $existing->execute([$slug]);
                    if ($existing->fetchColumn() > 0) {
                        $slug .= '-' . time();
                    }
                    $ins = $pdo->prepare("INSERT INTO rooms (slug, title, rate_per_night, elevation, max_guests, description, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$slug, $title, $rate, $el, $cap, $desc, $img]);
                    $alert_message = 'New villa / suite successfully registered and published!';
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

                // Update rooms & tariffs
                if (isset($_POST['room_id']) && is_array($_POST['room_id'])) {
                    $upd_room = $pdo->prepare("UPDATE rooms SET title = ?, elevation = ?, rate_per_night = ?, max_guests = ?, description = ?, image_url = ? WHERE id = ?");
                    foreach ($_POST['room_id'] as $idx => $rid) {
                        $t = trim($_POST['room_title'][$idx] ?? '');
                        $el = trim($_POST['room_elevation'][$idx] ?? '');
                        $rate = floatval($_POST['room_rate'][$idx] ?? 0);
                        $cap = intval($_POST['room_capacity'][$idx] ?? 2);
                        $d = trim($_POST['room_desc'][$idx] ?? '');
                        $img = trim($_POST['room_image'][$idx] ?? '');
                        $upd_room->execute([$t, $el, $rate, $cap, $d, $img, (int)$rid]);
                    }
                }
                $alert_message = 'Villas, architectural specifications & nightly tariffs successfully updated.';
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
                if (!empty($gt) && !empty($gimg)) {
                    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM gallery")->fetchColumn();
                    $ins = $pdo->prepare("INSERT INTO gallery (title, caption, tag, category, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                    $ins->execute([$gt, $gcap, $gtag, $gcat, $gimg, $max_order + 1]);
                    $alert_message = 'New photograph successfully added to the Visual Chronicle!';
                } else {
                    $alert_message = 'Photograph title and image path are required.';
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
$all_experiences = $pdo->query("SELECT * FROM experiences ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_rooms = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_gallery = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

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
    'seasons' => 'SEASONS OF KANTHALLOOR (DYNAMIC CMS)',
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

// On Page Load, activate selected tab without jumping
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('tab') || 'estate';
    if (typeof window.switchSettingsTab === 'function') {
        window.switchSettingsTab(activeTab, null, null, false);
    }
});
</script>

<!-- Subheader Status & Quick Action Toolbar -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding: 2px 2px;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <span class="adm-pulse-dot" style="background:#2ecc71; box-shadow: 0 0 10px #2ecc71;"></span>
        <span style="font-size: 13px; font-weight: 600; color: #FFFFFF; letter-spacing: 0.3px;">
            Frontend Configuration Sections — Click any card below to update public website content:
        </span>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <a href="../index.php" target="_blank" class="adm-btn-action outline" style="padding: 5px 12px; font-size: 11.5px;" title="Open Public Website">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Live Website</span>
        </a>
        <a href="settings.php?action=download_backup" class="adm-btn-action gold" style="padding: 5px 12px; font-size: 11.5px;" title="Export MySQL Database Dump">
            <i class="fa-solid fa-cloud-arrow-down"></i>
            <span>Download SQL</span>
        </a>
    </div>
</div>

<!-- ================================================================= -->
<!-- 4-COLUMN FRONTEND SECTIONS & SETTINGS CARDS GRID (DELIGHT UI)     -->
<!-- ================================================================= -->
<div class="adm-settings-cards-grid" id="adm-cards-top-grid">
    <!-- Card 1: Estate & Identity -->
    <div id="card-estate" class="adm-setting-card-btn <?php echo ($active_tab === 'estate') ? 'is-active' : ''; ?>" data-tab="estate" onclick="switchSettingsTab('estate', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'estate') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-leaf"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 01 • IDENTITY</span>
            <h4>Estate & Identity</h4>
            <p>Branding, check-in/out & currency</p>
        </div>
    </div>

    <!-- Card 2: WhatsApp & Concierge -->
    <div id="card-whatsapp" class="adm-setting-card-btn <?php echo ($active_tab === 'whatsapp') ? 'is-active' : ''; ?>" data-tab="whatsapp" onclick="switchSettingsTab('whatsapp', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'whatsapp') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon emerald"><i class="fa-brands fa-whatsapp"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 02 • CHANNELS</span>
            <h4>WhatsApp & Concierge</h4>
            <p>Direct line, telephone & address</p>
        </div>
    </div>

    <!-- Card 3: Hero Section & Atmosphere -->
    <div id="card-hero" class="adm-setting-card-btn <?php echo ($active_tab === 'hero') ? 'is-active' : ''; ?>" data-tab="hero" onclick="switchSettingsTab('hero', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'hero') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon purple"><i class="fa-solid fa-mountain-sun"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 03 • HERO</span>
            <h4>Hero Marquee & Visual</h4>
            <p>Headline, narrative & backdrop</p>
        </div>
    </div>

    <!-- Card 4: Climate & Accolades -->
    <div id="card-climate" class="adm-setting-card-btn <?php echo ($active_tab === 'climate') ? 'is-active' : ''; ?>" data-tab="climate" onclick="switchSettingsTab('climate', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'climate') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon cyan"><i class="fa-solid fa-temperature-half"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 04 • CLIMATE</span>
            <h4>Climate & Accolades</h4>
            <p>Top header ticker & microclimate</p>
        </div>
    </div>

    <!-- Card 5: Philosophy & Ethos -->
    <div id="card-philosophy" class="adm-setting-card-btn <?php echo ($active_tab === 'philosophy') ? 'is-active' : ''; ?>" data-tab="philosophy" onclick="switchSettingsTab('philosophy', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'philosophy') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon gold"><i class="fa-solid fa-compass-drafting"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 05 • ETHOS</span>
            <h4>Sanctuary Philosophy</h4>
            <p>Welcome manifesto & portrait</p>
        </div>
    </div>

    <!-- Card 6: Why Farmstay Story -->
    <div id="card-why" class="adm-setting-card-btn <?php echo ($active_tab === 'why') ? 'is-active' : ''; ?>" data-tab="why" onclick="switchSettingsTab('why', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'why') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon amber"><i class="fa-solid fa-seedling"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 06 • ARCHITECTURE</span>
            <h4>Why Food Forest?</h4>
            <p>Living soil & cob mudhouse</p>
        </div>
    </div>

    <!-- Card 7: Curated Experiences -->
    <div id="card-experiences" class="adm-setting-card-btn <?php echo ($active_tab === 'experiences') ? 'is-active' : ''; ?>" data-tab="experiences" onclick="switchSettingsTab('experiences', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'experiences') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon violet"><i class="fa-solid fa-person-hiking"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 07 • EXPERIENCES</span>
            <h4>Curated Experiences</h4>
            <p>Edit all 4 rituals, timings & photos</p>
        </div>
    </div>

    <!-- Card 8: Seasons of Kanthalloor -->
    <div id="card-seasons" class="adm-setting-card-btn <?php echo ($active_tab === 'seasons') ? 'is-active' : ''; ?>" data-tab="seasons" onclick="switchSettingsTab('seasons', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'seasons') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon terracotta"><i class="fa-solid fa-cloud-sun"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 08 • SEASONS</span>
            <h4>Seasons of Kanthalloor</h4>
            <p>Edit all 4 seasons, months & photos</p>
        </div>
    </div>

    <!-- Card 9: Villas & Accommodations -->
    <div id="card-rooms" class="adm-setting-card-btn <?php echo ($active_tab === 'rooms') ? 'is-active' : ''; ?>" data-tab="rooms" onclick="switchSettingsTab('rooms', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'rooms') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon rose"><i class="fa-solid fa-house-chimney"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 09 • VILLAS & RATES</span>
            <h4>Villas & Cottages</h4>
            <p>Edit Treehouse & Mudhouse tariffs</p>
        </div>
    </div>

    <!-- Card 10: Visual Diary (Gallery) -->
    <div id="card-gallery" class="adm-setting-card-btn <?php echo ($active_tab === 'gallery') ? 'is-active' : ''; ?>" data-tab="gallery" onclick="switchSettingsTab('gallery', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'gallery') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon teal"><i class="fa-solid fa-camera-retro"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 10 • GALLERY</span>
            <h4>Visual Diary (Gallery)</h4>
            <p>Edit 8 photographs, tags & titles</p>
        </div>
    </div>

    <!-- Card 11: Guest Reflections -->
    <div id="card-testimonials" class="adm-setting-card-btn <?php echo ($active_tab === 'testimonials') ? 'is-active' : ''; ?>" data-tab="testimonials" onclick="switchSettingsTab('testimonials', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'testimonials') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon blue"><i class="fa-solid fa-comment-dots"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 11 • REVIEWS</span>
            <h4>Guest Reflections</h4>
            <p>Edit traveler reviews, stars & quotes</p>
        </div>
    </div>

    <!-- Card 12: Content & Image Protection -->
    <div id="card-protection" class="adm-setting-card-btn <?php echo ($active_tab === 'protection') ? 'is-active' : ''; ?>" data-tab="protection" onclick="switchSettingsTab('protection', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'protection') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 12 • PROTECTION</span>
            <h4>Content Protection</h4>
            <p>Anti-copy & DevTools shield</p>
        </div>
    </div>

    <!-- Card 13: Security & Password -->
    <div id="card-security" class="adm-setting-card-btn <?php echo ($active_tab === 'security') ? 'is-active' : ''; ?>" data-tab="security" onclick="switchSettingsTab('security', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'security') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon indigo"><i class="fa-solid fa-key"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 13 • ACCESS</span>
            <h4>Security & Password</h4>
            <p>Admin credentials & password</p>
        </div>
    </div>

    <!-- Card 14: Backup Database -->
    <div id="card-backup" class="adm-setting-card-btn <?php echo ($active_tab === 'backup') ? 'is-active' : ''; ?>" data-tab="backup" onclick="switchSettingsTab('backup', this, event);">
        <div class="active-badge" style="<?php echo ($active_tab === 'backup') ? 'display:block;' : 'display:none;'; ?>"></div>
        <div class="adm-setting-card-icon gold"><i class="fa-solid fa-database"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 14 • SQL BACKUP</span>
            <h4>MySQL Database Backup</h4>
            <p>1-click phpMyAdmin SQL dump</p>
        </div>
    </div>
</div>

<!-- ================================================================= -->
<!-- ACTIVE CONFIGURATION SECTION INDICATOR BANNER                     -->
<!-- ================================================================= -->
<div class="adm-active-section-indicator" id="adm-active-indicator" style="background: linear-gradient(90deg, rgba(16, 31, 21, 0.95), rgba(8, 18, 11, 0.95)); border: 1px solid var(--adm-gold-border); border-radius: 10px; padding: 14px 20px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.3);">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span class="adm-pulse-dot" style="background: #2ecc71; box-shadow: 0 0 10px #2ecc71;"></span>
        <div>
            <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); letter-spacing: 1px; text-transform: uppercase; display: block;">LIVE SECTION EDITOR</span>
            <span style="font-size: 14px; color: #FFFFFF; font-weight: 700; letter-spacing: 0.5px;" id="active-tab-label">
                <?php echo e($tab_titles[$active_tab] ?? 'CARD 01 • ESTATE BRANDING & OPERATIONAL IDENTITY'); ?>
            </span>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 11.5px; color: var(--adm-text-muted);">Click any card above to switch sections</span>
        <button type="button" onclick="document.getElementById('adm-cards-top-grid').scrollIntoView({behavior:'smooth'});" class="adm-btn-action outline" style="padding: 5px 12px; font-size: 11px;">
            <i class="fa-solid fa-arrow-up"></i> All 14 Cards
        </button>
    </div>
</div>

<!-- ================================================================= -->
<!-- ACTIVE CONFIGURATION SECTION PANELS (DIRECTLY UNDERNEATH CARDS)   -->
<!-- ================================================================= -->
<div class="adm-settings-panels-container" id="adm-panels-container">

    <!-- -------------------------------------------------------------
         PANEL 1: ESTATE & IDENTITY
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'estate') ? 'is-active' : ''; ?>" id="pane-estate">
        <form action="settings.php?tab=estate" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 01</span>
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
         PANEL 2: WHATSAPP & CONCIERGE
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'whatsapp') ? 'is-active' : ''; ?>" id="pane-whatsapp">
        <form action="settings.php?tab=whatsapp" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 02</span>
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
         PANEL 3: HERO MARQUEE & VISUAL
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'hero') ? 'is-active' : ''; ?>" id="pane-hero">
        <form action="settings.php?tab=hero" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 03</span>
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
                    <label class="adm-form-label">Hero Background Visual URL</label>
                    <div style="display: flex; gap: 14px; align-items: flex-start;">
                        <div style="flex-grow: 1;">
                            <input type="text" name="hero_bg_image" id="input_hero_bg" class="adm-form-control" value="<?php echo e($s['hero_bg_image'] ?? 'assets/images/hero_forest.png'); ?>" oninput="document.getElementById('preview_hero_bg').src = this.value;" required>
                            <small style="color: var(--adm-text-muted); font-size: 11px;">Relative path or absolute URL to primary backdrop photo.</small>
                        </div>
                        <div style="width: 120px; height: 75px; border-radius: 8px; overflow: hidden; border: 1px solid var(--adm-border); background: #000; flex-shrink: 0;">
                            <img id="preview_hero_bg" src="../<?php echo e($s['hero_bg_image'] ?? 'assets/images/hero_forest.png'); ?>" alt="Hero Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/hero_forest.png';">
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
         PANEL 4: CLIMATE & ACCOLADES
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'climate') ? 'is-active' : ''; ?>" id="pane-climate">
        <form action="settings.php?tab=climate" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 04</span>
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
         PANEL 5: SANCTUARY PHILOSOPHY
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'philosophy') ? 'is-active' : ''; ?>" id="pane-philosophy">
        <form action="settings.php?tab=philosophy" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 05</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">SANCTUARY PHILOSOPHY & WELCOME MANIFESTO</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Control the 'Welcome to Food Forest' story, founders' ethos, and featured landscape photo.</p>
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
                    <label class="adm-form-label">Featured Portrait Photo URL</label>
                    <div style="display: flex; gap: 14px; align-items: flex-start;">
                        <div style="flex-grow: 1;">
                            <input type="text" name="welcome_image" id="input_welcome_img" class="adm-form-control" value="<?php echo e($s['welcome_image'] ?? 'assets/images/mudhouse_front.png'); ?>" oninput="document.getElementById('preview_welcome_img').src = this.value;" required>
                        </div>
                        <div style="width: 120px; height: 75px; border-radius: 8px; overflow: hidden; border: 1px solid var(--adm-border); background: #000; flex-shrink: 0;">
                            <img id="preview_welcome_img" src="../<?php echo e($s['welcome_image'] ?? 'assets/images/mudhouse_front.png'); ?>" alt="Welcome Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/mudhouse_front.png';">
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
         PANEL 6: WHY FOOD FOREST?
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'why') ? 'is-active' : ''; ?>" id="pane-why">
        <form action="settings.php?tab=why" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 06</span>
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
                    <label class="adm-form-label">Featured Image URL</label>
                    <div style="display: flex; gap: 14px; align-items: flex-start;">
                        <div style="flex-grow: 1;">
                            <input type="text" name="why_image" id="input_why_img" class="adm-form-control" value="<?php echo e($s['why_image'] ?? 'assets/images/mudhouse_living.png'); ?>" oninput="document.getElementById('preview_why_img').src = this.value;" required>
                        </div>
                        <div style="width: 120px; height: 75px; border-radius: 8px; overflow: hidden; border: 1px solid var(--adm-border); background: #000; flex-shrink: 0;">
                            <img id="preview_why_img" src="../<?php echo e($s['why_image'] ?? 'assets/images/mudhouse_living.png'); ?>" alt="Why Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/mudhouse_living.png';">
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
         PANEL 7: CURATED EXPERIENCES (FULL DYNAMIC EDITING)
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
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 07</span>
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
                <form action="settings.php?tab=experiences" method="POST">
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

                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Experience Title *</label>
                            <input type="text" name="new_exp_title" class="adm-form-control" placeholder="e.g. Organic Coffee Tasting & Bean Roasting" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Badge Tag *</label>
                            <input type="text" name="new_exp_badge" class="adm-form-control" placeholder="e.g. INCLUDED IN STAY, WORKSHOP, ADVENTURE" value="INCLUDED IN STAY" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Timing / Duration *</label>
                            <input type="text" name="new_exp_timing" class="adm-form-control" placeholder="e.g. 2 Hours • Morning" value="2 Hours • Morning" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Backdrop Photo Path *</label>
                            <input type="text" name="new_exp_image" class="adm-form-control" placeholder="e.g. assets/images/01 (18).jpeg" value="assets/images/01 (18).jpeg" required>
                        </div>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 16px;">
                        <label class="adm-form-label">Experience Description *</label>
                        <textarea name="new_exp_desc" rows="3" class="adm-form-control" placeholder="Describe the ritual, sensory highlights, atmosphere, and what guests will experience..." required></textarea>
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
            <form id="form-edit-experiences" action="settings.php?tab=experiences" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="form_type" value="experiences_settings">
                <input type="hidden" name="active_tab" value="experiences">

                <!-- Section Header Settings -->
                <div style="background: rgba(139, 92, 246, 0.08); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #c084fc; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 14px;">Section Header Copy</span>
                    <div class="adm-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Section Eyebrow</label>
                            <input type="text" name="experiences_badge" class="adm-form-control" value="<?php echo e($s['experiences_badge'] ?? 'Curated Journeys'); ?>" required>
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
                    <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-experience');" style="padding: 6px 12px; font-size: 11.5px;">
                        <i class="fa-solid fa-plus"></i> Add Another
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <?php if (empty($all_experiences)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-person-hiking" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No experiences found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-experience');">
                                <i class="fa-solid fa-plus-circle"></i> Add First Experience
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_experiences as $idx => $exp): ?>
                        <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 18px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 12px; font-weight: 700; color: #c084fc; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Experience #<?php echo ($idx + 1); ?>
                                    </span>
                                    <span style="font-size: 11px; color: var(--adm-text-muted);">ID: <?php echo $exp['id']; ?></span>
                                </div>
                                <button type="submit" name="delete_exp_id" value="<?php echo $exp['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="return confirm('Permanently delete experience <?php echo e(addslashes($exp['title'])); ?>? This cannot be undone.');" title="Delete this experience">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </div>
                            <input type="hidden" name="exp_id[]" value="<?php echo $exp['id']; ?>">

                            <div class="adm-form-group" style="margin-bottom: 12px;">
                                <label class="adm-form-label">Experience Title</label>
                                <input type="text" name="exp_title[]" class="adm-form-control" value="<?php echo e($exp['title']); ?>" required>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                <div class="adm-form-group">
                                    <label class="adm-form-label">Badge Tag</label>
                                    <input type="text" name="exp_badge[]" class="adm-form-control" value="<?php echo e($exp['badge']); ?>" required>
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-form-label">Timing / Duration</label>
                                    <input type="text" name="exp_timing[]" class="adm-form-control" value="<?php echo e($exp['timing']); ?>" required>
                                </div>
                            </div>

                            <div class="adm-form-group" style="margin-bottom: 12px;">
                                <label class="adm-form-label">Description</label>
                                <textarea name="exp_desc[]" rows="3" class="adm-form-control" required><?php echo e($exp['description']); ?></textarea>
                            </div>

                            <div class="adm-form-group">
                                <label class="adm-form-label">Image Path</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="text" name="exp_image[]" class="adm-form-control" value="<?php echo e($exp['image_url']); ?>" oninput="document.getElementById('exp_prev_<?php echo $exp['id']; ?>').src='../'+this.value;" required>
                                    <img id="exp_prev_<?php echo $exp['id']; ?>" src="../<?php echo e($exp['image_url']); ?>" alt="Exp" style="width: 50px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid var(--adm-border);" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
         PANEL 8: SEASONS OF KANTHALLOOR (FULL DYNAMIC EDITING)
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'seasons') ? 'is-active' : ''; ?>" id="pane-seasons">
        <form action="settings.php?tab=seasons" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="form_type" value="seasons_settings">
            <input type="hidden" name="active_tab" value="seasons">

            <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="adm-setting-card-icon terracotta"><i class="fa-solid fa-cloud-sun"></i></div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                <span class="adm-pulse-dot" style="width: 5px; height: 5px; background: #2ecc71; margin-right: 4px;"></span> EDITING SECTION
                            </span>
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 08</span>
                        </div>
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; letter-spacing: 1px; color: #FFFFFF; margin: 4px 0 0;">SEASONS OF KANTHALLOOR (DYNAMIC CMS)</h3>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">Directly configure the header and all 4 seasonal cards (Monsoon, Winter, Harvest & Summer) with photos.</p>
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
                <!-- Header -->
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

                <!-- 4 Dynamic Seasons -->
                <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0 0 16px;">
                    <i class="fa-solid fa-calendar-days"></i> The 4 Seasonal Cycles
                </h4>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <!-- Season 1: Monsoon -->
                    <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 18px;">
                        <span style="font-size: 12px; font-weight: 700; color: #f59e0b; text-transform: uppercase;">Season 1 • Monsoon</span>
                        <div class="adm-form-group" style="margin: 10px 0;">
                            <label class="adm-form-label">Season Name</label>
                            <input type="text" name="season_1_name" class="adm-form-control" value="<?php echo e($s['season_1_name'] ?? 'Monsoon Magic'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Calendar Months</label>
                            <input type="text" name="season_1_months" class="adm-form-control" value="<?php echo e($s['season_1_months'] ?? 'June - September'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Atmosphere Description</label>
                            <textarea name="season_1_desc" rows="3" class="adm-form-control" required><?php echo e($s['season_1_desc'] ?? 'Lush, deep green landscapes, rising mist, heavy refreshing rainfall, and crisp cold mountain breeze.'); ?></textarea>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Backdrop Photo</label>
                            <input type="text" name="season_1_image" class="adm-form-control" value="<?php echo e($s['season_1_image'] ?? 'assets/images/01 (9).jpeg'); ?>" required>
                        </div>
                    </div>

                    <!-- Season 2: Winter -->
                    <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 18px;">
                        <span style="font-size: 12px; font-weight: 700; color: #f59e0b; text-transform: uppercase;">Season 2 • Winter</span>
                        <div class="adm-form-group" style="margin: 10px 0;">
                            <label class="adm-form-label">Season Name</label>
                            <input type="text" name="season_2_name" class="adm-form-control" value="<?php echo e($s['season_2_name'] ?? 'Cozy Winter'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Calendar Months</label>
                            <input type="text" name="season_2_months" class="adm-form-control" value="<?php echo e($s['season_2_months'] ?? 'October - February'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Atmosphere Description</label>
                            <textarea name="season_2_desc" rows="3" class="adm-form-control" required><?php echo e($s['season_2_desc'] ?? 'Chilly mist, clear bright blue skies, warm sunlit afternoons, and snug campfire nights under starry skies.'); ?></textarea>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Backdrop Photo</label>
                            <input type="text" name="season_2_image" class="adm-form-control" value="<?php echo e($s['season_2_image'] ?? 'assets/images/01 (8).jpeg'); ?>" required>
                        </div>
                    </div>

                    <!-- Season 3: Harvest -->
                    <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 18px;">
                        <span style="font-size: 12px; font-weight: 700; color: #f59e0b; text-transform: uppercase;">Season 3 • Harvest</span>
                        <div class="adm-form-group" style="margin: 10px 0;">
                            <label class="adm-form-label">Season Name</label>
                            <input type="text" name="season_3_name" class="adm-form-control" value="<?php echo e($s['season_3_name'] ?? 'Harvest Season'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Calendar Months</label>
                            <input type="text" name="season_3_months" class="adm-form-control" value="<?php echo e($s['season_3_months'] ?? 'March - May'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Atmosphere Description</label>
                            <textarea name="season_3_desc" rows="3" class="adm-form-control" required><?php echo e($s['season_3_desc'] ?? 'Fruits and blossoms heavy on the branches. Perfect time to pick apples, plums, peaches, and berries.'); ?></textarea>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Backdrop Photo</label>
                            <input type="text" name="season_3_image" class="adm-form-control" value="<?php echo e($s['season_3_image'] ?? 'assets/images/01 (19).jpeg'); ?>" required>
                        </div>
                    </div>

                    <!-- Season 4: Summer -->
                    <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 18px;">
                        <span style="font-size: 12px; font-weight: 700; color: #f59e0b; text-transform: uppercase;">Season 4 • Summer</span>
                        <div class="adm-form-group" style="margin: 10px 0;">
                            <label class="adm-form-label">Season Name</label>
                            <input type="text" name="season_4_name" class="adm-form-control" value="<?php echo e($s['season_4_name'] ?? 'Cool Summer'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Calendar Months</label>
                            <input type="text" name="season_4_months" class="adm-form-control" value="<?php echo e($s['season_4_months'] ?? 'April - June'); ?>" required>
                        </div>
                        <div class="adm-form-group" style="margin-bottom: 10px;">
                            <label class="adm-form-label">Atmosphere Description</label>
                            <textarea name="season_4_desc" rows="3" class="adm-form-control" required><?php echo e($s['season_4_desc'] ?? 'Pleasant, breezy weather. Kanthalloor acts as a cool refuge from the sweltering heat of the plains.'); ?></textarea>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Backdrop Photo</label>
                            <input type="text" name="season_4_image" class="adm-form-control" value="<?php echo e($s['season_4_image'] ?? 'assets/images/01 (33).jpeg'); ?>" required>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(197, 160, 89, 0.15);">
                    <button type="submit" class="adm-btn-action gold" style="padding: 12px 28px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>SAVE SEASONS CONFIGURATION</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- -------------------------------------------------------------
         PANEL 9: VILLAS & COTTAGES (DYNAMIC TARIFFS & SPECS)
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
                        <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 09</span>
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
                <form action="settings.php?tab=rooms" method="POST">
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
                            <label class="adm-form-label">Suite Title *</label>
                            <input type="text" name="new_room_title" class="adm-form-control" placeholder="e.g. The Orchard Stone Cottage" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">URL Slug (lowercase-dashes)</label>
                            <input type="text" name="new_room_slug" class="adm-form-control" placeholder="e.g. stone-cottage (leave blank to auto-generate)">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Nightly Rate (₹) *</label>
                            <input type="number" step="100" name="new_room_rate" class="adm-form-control" placeholder="e.g. 12500" required style="font-weight: bold; color: var(--adm-gold);">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Elevation Tag *</label>
                            <input type="text" name="new_room_elevation" class="adm-form-control" placeholder="e.g. 1,600m High Ridge" value="1,600m High Ridge" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Max Guest Capacity *</label>
                            <input type="number" min="1" max="10" name="new_room_capacity" class="adm-form-control" value="2" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Primary Suite Photo Path *</label>
                            <input type="text" name="new_room_image" class="adm-form-control" placeholder="e.g. assets/images/mudhouse_living.png" value="assets/images/mudhouse_living.png" required>
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
            <form id="form-edit-rooms" action="settings.php?tab=rooms" method="POST">
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

                <!-- Suites Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-bed"></i> Architectural Suites & Nightly Rates (<?php echo count($all_rooms); ?>)
                    </h4>
                    <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-room');" style="padding: 6px 12px; font-size: 11.5px;">
                        <i class="fa-solid fa-plus"></i> Add Another
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <?php if (empty($all_rooms)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-bed" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No villas found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-room');">
                                <i class="fa-solid fa-plus-circle"></i> Add First Villa
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_rooms as $idx => $room): ?>
                        <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 13px; font-weight: 700; color: #fb7185; text-transform: uppercase;">
                                        <?php echo e($room['title'] ?? $room['slug']); ?>
                                    </span>
                                    <span style="font-size: 11px; color: var(--adm-gold); background: rgba(197, 160, 89, 0.1); padding: 3px 8px; border-radius: 6px;">
                                        ID: <?php echo $room['id']; ?>
                                    </span>
                                </div>
                                <button type="submit" name="delete_room_id" value="<?php echo $room['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="return confirm('Permanently delete villa <?php echo e(addslashes($room['title'])); ?>? This cannot be undone.');" title="Delete this villa">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </div>
                            <input type="hidden" name="room_id[]" value="<?php echo $room['id']; ?>">

                            <div class="adm-form-group" style="margin-bottom: 12px;">
                                <label class="adm-form-label">Suite Title</label>
                                <input type="text" name="room_title[]" class="adm-form-control" value="<?php echo e($room['title'] ?? ''); ?>" required>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                <div class="adm-form-group">
                                    <label class="adm-form-label">Nightly Rate (₹)</label>
                                    <input type="number" step="100" name="room_rate[]" class="adm-form-control" value="<?php echo (float)$room['rate_per_night']; ?>" required style="font-weight: bold; color: var(--adm-gold);">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-form-label">Elevation Tag</label>
                                    <input type="text" name="room_elevation[]" class="adm-form-control" value="<?php echo e($room['elevation']); ?>" required>
                                </div>
                            </div>

                            <div class="adm-form-group" style="margin-bottom: 12px;">
                                <label class="adm-form-label">Guest Capacity (Max Guests)</label>
                                <input type="number" min="1" max="10" name="room_capacity[]" class="adm-form-control" value="<?php echo e($room['max_guests'] ?? 2); ?>" required>
                            </div>

                            <div class="adm-form-group" style="margin-bottom: 12px;">
                                <label class="adm-form-label">Architectural Bio / Description</label>
                                <textarea name="room_desc[]" rows="3" class="adm-form-control" required><?php echo e($room['description']); ?></textarea>
                            </div>

                            <div class="adm-form-group">
                                <label class="adm-form-label">Primary Suite Photo</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="text" name="room_image[]" class="adm-form-control" value="<?php echo e($room['image_url']); ?>" oninput="document.getElementById('room_prev_<?php echo $room['id']; ?>').src='../'+this.value;" required>
                                    <img id="room_prev_<?php echo $room['id']; ?>" src="../<?php echo e($room['image_url']); ?>" alt="Room" style="width: 50px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid var(--adm-border);" onerror="this.src='../assets/images/treehouse_exterior.png';">
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
                <form action="settings.php?tab=gallery" method="POST">
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
                        <div class="adm-form-group">
                            <label class="adm-form-label">Image File Path *</label>
                            <input type="text" name="new_gal_image" class="adm-form-control" placeholder="e.g. assets/images/01 (1).jpeg" value="assets/images/01 (1).jpeg" required>
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
            <form id="form-edit-gallery" action="settings.php?tab=gallery" method="POST">
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
                    <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');" style="padding: 6px 12px; font-size: 11.5px;">
                        <i class="fa-solid fa-plus"></i> Add Another
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <?php if (empty($all_gallery)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-camera-retro" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No gallery photographs found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-gallery');">
                                <i class="fa-solid fa-plus-circle"></i> Add First Photograph
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_gallery as $idx => $photo): ?>
                        <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 16px;">
                            <input type="hidden" name="gallery_id[]" value="<?php echo $photo['id']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 8px;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2dd4bf; text-transform: uppercase;">
                                    Photo #<?php echo ($idx + 1); ?>
                                </span>
                                <button type="submit" name="delete_gal_id" value="<?php echo $photo['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="return confirm('Permanently delete this photograph (<?php echo e(addslashes($photo['title'])); ?>)?');" title="Delete this photograph">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </div>
                            <div style="display: flex; gap: 12px; margin-bottom: 10px;">
                                <img id="gal_prev_<?php echo $photo['id']; ?>" src="../<?php echo e($photo['image_url']); ?>" alt="Photo" style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--adm-border);" onerror="this.src='../assets/images/treehouse_exterior.png';">
                                <div style="flex-grow: 1;">
                                    <label class="adm-form-label" style="font-size: 10.5px;">Photo Title</label>
                                    <input type="text" name="gal_title[]" class="adm-form-control" value="<?php echo e($photo['title']); ?>" required style="font-size: 12px; padding: 6px 10px;">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                <div>
                                    <label class="adm-form-label" style="font-size: 10.5px;">Tag</label>
                                    <input type="text" name="gal_tag[]" class="adm-form-control" value="<?php echo e($photo['tag']); ?>" style="font-size: 12px; padding: 6px 10px;">
                                </div>
                                <div>
                                    <label class="adm-form-label" style="font-size: 10.5px;">Caption</label>
                                    <input type="text" name="gal_caption[]" class="adm-form-control" value="<?php echo e($photo['caption']); ?>" style="font-size: 12px; padding: 6px 10px;">
                                </div>
                            </div>
                            <div>
                                <label class="adm-form-label" style="font-size: 10.5px;">Image File Path</label>
                                <input type="text" name="gal_image[]" class="adm-form-control" value="<?php echo e($photo['image_url']); ?>" oninput="document.getElementById('gal_prev_<?php echo $photo['id']; ?>').src='../'+this.value;" required style="font-size: 12px; padding: 6px 10px;">
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
                <form action="settings.php?tab=testimonials" method="POST">
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
            <form id="form-edit-testimonials" action="settings.php?tab=testimonials" method="POST">
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
                    <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-testimonial');" style="padding: 6px 12px; font-size: 11.5px;">
                        <i class="fa-solid fa-plus"></i> Add Another
                    </button>
                </div>

                <!-- Testimonials Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <?php if (empty($all_testimonials)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: rgba(8, 18, 12, 0.6); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 12px;">
                            <i class="fa-solid fa-comment-dots" style="font-size: 32px; color: var(--adm-gold); opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                            <p style="color: var(--adm-text-secondary); font-size: 14px; margin: 0 0 14px;">No guest testimonials found in the database.</p>
                            <button type="button" class="adm-btn-add-pill" onclick="toggleAddNewDrawer('drawer-add-testimonial');">
                                <i class="fa-solid fa-plus-circle"></i> Add First Testimonial
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($all_testimonials as $idx => $t): ?>
                        <div style="background: rgba(8, 18, 11, 0.7); border: 1px solid var(--adm-border); border-radius: 12px; padding: 18px;">
                            <input type="hidden" name="testimonial_id[]" value="<?php echo $t['id']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 8px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 12px; font-weight: 700; color: #60a5fa; text-transform: uppercase;">
                                        Review #<?php echo ($idx + 1); ?>
                                    </span>
                                    <div style="color: var(--adm-gold); font-size: 11px;">
                                        <?php for ($i = 0; $i < (int)$t['stars']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                                    </div>
                                </div>
                                <button type="submit" name="delete_testimonial_id" value="<?php echo $t['id']; ?>" formnovalidate class="adm-btn-danger-outline" onclick="return confirm('Permanently delete review by <?php echo e(addslashes($t['guest_name'])); ?>? This cannot be undone.');" title="Delete this review">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </div>

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
         PANEL 12: CONTENT & IMAGE PROTECTION
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'protection') ? 'is-active' : ''; ?>" id="pane-protection">
        <form action="settings.php?tab=protection" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 12</span>
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
         PANEL 13: SECURITY & ADMIN PROFILE
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'security') ? 'is-active' : ''; ?>" id="pane-security">
        <form action="settings.php?tab=security" method="POST">
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
                            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 700; letter-spacing: 0.8px;">CARD 13</span>
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
         PANEL 14: MYSQL DATABASE BACKUP
         ------------------------------------------------------------- -->
    <div class="adm-card adm-settings-tab-pane <?php echo ($active_tab === 'backup') ? 'is-active' : ''; ?>" id="pane-backup">
        <div class="adm-card-header" style="border-bottom: 1px solid var(--adm-border); padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: rgba(16, 31, 21, 0.4);">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon gold"><i class="fa-solid fa-database"></i></div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 10px; font-weight: 700; color: var(--adm-gold); background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.3); padding: 2px 8px; border-radius: 4px; letter-spacing: 0.5px;">SYSTEM TOOL • CARD 14</span>
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
        </div>
    </div>

</div> <!-- End .adm-settings-panels-container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
