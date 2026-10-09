<?php
// =========================================================================
// Food Forest Sanctuary — Estate Settings & Configuration Hub (16-Card Grid)
// Modeled after Delight Builders: Click any card to enter dedicated split editor
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';

$page_title = 'Estate Settings & Configuration Hub';
$page_subtitle = 'Select any section below to open its dedicated Split-Screen Editor & Real-Time User Preview';

$pdo = get_db();

// Handle Food Ordering Toggle Action (AJAX or POST)
if (isset($_POST['action']) && $_POST['action'] === 'toggle_food_ordering') {
    $new_status = (!empty($_POST['enabled']) && $_POST['enabled'] == '1') ? '1' : '0';
    set_setting('food_ordering_enabled', $new_status, $pdo);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'enabled' => ($new_status === '1'),
            'message' => ($new_status === '1') ? 'Guest In-Cottage Food Ordering is now ENABLED.' : 'Guest In-Cottage Food Ordering is now DISABLED.'
        ]);
        exit;
    }
    header('Location: settings.php?msg=food_ordering_updated');
    exit;
}

// Handle 360 Walkthrough Toggle Action (AJAX or POST)
if (isset($_POST['action']) && $_POST['action'] === 'toggle_walkthrough_360') {
    $new_status = (!empty($_POST['enabled']) && $_POST['enabled'] == '1') ? '1' : '0';
    set_setting('walkthrough_360_enabled', $new_status, $pdo);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'enabled' => ($new_status === '1'),
            'message' => ($new_status === '1') ? '360° Walkthrough is now ENABLED on Homepage.' : '360° Walkthrough is now DISABLED (Section hidden from guests).'
        ]);
        exit;
    }
    header('Location: settings.php?msg=walkthrough_status_updated');
    exit;
}

// Handle Walkthrough Room Link Update
if (isset($_POST['action']) && $_POST['action'] === 'update_walkthrough_rooms') {
    $wood_id = (int)($_POST['walkthrough_woodhouse_room_id'] ?? 0);
    $mud_id = (int)($_POST['walkthrough_mudhouse_room_id'] ?? 0);
    if ($wood_id > 0) set_setting('walkthrough_woodhouse_room_id', (string)$wood_id, $pdo);
    if ($mud_id > 0) set_setting('walkthrough_mudhouse_room_id', (string)$mud_id, $pdo);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Linked walkthrough rooms updated successfully.']);
        exit;
    }
    header('Location: settings.php?msg=walkthrough_rooms_updated');
    exit;
}

// Handle Multi-Tier GST Settings Update Action (AJAX or POST)
if (isset($_POST['action']) && $_POST['action'] === 'update_gst_settings') {
    $rate_cottage = max(0, min(100, (float)($_POST['gst_rate_cottage'] ?? 12)));
    $rate_food = max(0, min(100, (float)($_POST['gst_rate_food'] ?? 5)));
    $rate_other = max(0, min(100, (float)($_POST['gst_rate_other'] ?? 18)));
    $gst_num = strtoupper(trim($_POST['gst_number'] ?? ''));
    $gst_legal = trim($_POST['gst_legal_name'] ?? '');

    set_setting('gst_rate_cottage', (string)$rate_cottage, $pdo);
    set_setting('gst_rate_food', (string)$rate_food, $pdo);
    set_setting('gst_rate_other', (string)$rate_other, $pdo);
    set_setting('gst_rate_percentage', (string)$rate_cottage, $pdo);
    if (!empty($gst_num)) set_setting('gst_number', $gst_num, $pdo);
    if (!empty($gst_legal)) set_setting('gst_legal_name', $gst_legal, $pdo);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'rates' => [
                'cottage' => $rate_cottage,
                'food' => $rate_food,
                'other' => $rate_other
            ],
            'gst_number' => $gst_num,
            'gst_legal_name' => $gst_legal,
            'message' => "GST Tax Rates updated: Food ({$rate_food}%), Cottage ({$rate_cottage}%), Other ({$rate_other}%)."
        ]);
        exit;
    }
    header('Location: settings.php?msg=gst_updated');
    exit;
}

// Handle Resort Branding & Logo Update Action (AJAX or POST)
if (isset($_POST['action']) && $_POST['action'] === 'save_resort_branding') {
    $site_name = trim($_POST['site_name'] ?? 'FOOD FOREST');
    $site_tagline = trim($_POST['site_tagline'] ?? 'KANTHALLOOR • ECO SANCTUARY');
    $currency_symbol = trim($_POST['currency_symbol'] ?? '₹');
    $checkin_time = trim($_POST['checkin_time'] ?? '02:00 PM');
    $checkout_time = trim($_POST['checkout_time'] ?? '11:00 AM');

    if (!empty($site_name)) {
        set_setting('site_name', $site_name, $pdo);
        set_setting('estate_name', $site_name, $pdo);
    }
    if (!empty($site_tagline)) {
        set_setting('site_tagline', $site_tagline, $pdo);
        set_setting('estate_tagline', $site_tagline, $pdo);
    }
    if (!empty($currency_symbol)) set_setting('currency_symbol', $currency_symbol, $pdo);
    if (!empty($checkin_time)) set_setting('checkin_time', $checkin_time, $pdo);
    if (!empty($checkout_time)) set_setting('checkout_time', $checkout_time, $pdo);

    $logo_url = get_setting('site_logo', '');
    if (!empty($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
        $logo_url = '';
        set_setting('site_logo', '', $pdo);
    } elseif (!empty($_FILES['site_logo_file']['name']) && $_FILES['site_logo_file']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['site_logo_file'], 'branding', ['max_dimension' => 1200]);
        if ($up['success']) {
            $logo_url = $up['path'];
            set_setting('site_logo', $logo_url, $pdo);
        } else {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Logo upload failed: ' . $up['error']]);
                exit;
            }
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Resort Branding, Logo, and Stay Policy updated across all public pages & portals!',
            'site_name' => $site_name,
            'site_tagline' => $site_tagline,
            'currency_symbol' => $currency_symbol,
            'checkin_time' => $checkin_time,
            'checkout_time' => $checkout_time,
            'site_logo' => $logo_url
        ]);
        exit;
    }
    header('Location: settings.php?msg=branding_updated');
    exit;
}

// Handle Session Inactivity Logout Timings Update (AJAX or POST)
if (isset($_POST['action']) && $_POST['action'] === 'save_session_timings') {
    $admin_mins = max(5, min(1440, (int)($_POST['admin_session_timeout_minutes'] ?? 15)));
    $client_mins = max(5, min(1440, (int)($_POST['client_session_timeout_minutes'] ?? 15)));

    set_setting('admin_session_timeout_minutes', (string)$admin_mins, $pdo);
    set_setting('client_session_timeout_minutes', (string)$client_mins, $pdo);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Session Timings saved: Admin auto-logout at {$admin_mins} mins, Guest auto-logout at {$client_mins} mins.",
            'admin_mins' => $admin_mins,
            'client_mins' => $client_mins
        ]);
        exit;
    }
    header('Location: settings.php?msg=sessions_updated');
    exit;
}

// Handle Live Preview Toggles Update (AJAX or POST)
if (isset($_POST['action']) && $_POST['action'] === 'save_live_preview_settings') {
    $global_preview = (!empty($_POST['live_preview_enabled']) && $_POST['live_preview_enabled'] == '1') ? '1' : '0';
    set_setting('live_preview_enabled', $global_preview, $pdo);

    $cards_config = $_POST['cards_config'] ?? [];
    if (!is_array($cards_config)) {
        $cards_config = json_decode((string)$cards_config, true) ?: [];
    }
    set_setting('live_preview_cards_config', json_encode($cards_config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $pdo);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Live Preview configuration updated successfully for all section editors.',
            'global_preview' => ($global_preview === '1'),
            'cards_config' => $cards_config
        ]);
        exit;
    }
    header('Location: settings.php?msg=preview_settings_updated');
    exit;
}

// Handle Room Audit Checklist Management (AJAX or POST)
if (isset($_POST['action']) && $_POST['action'] === 'manage_room_audit_checklist') {
    $subaction = trim($_POST['subaction'] ?? 'save_all');

    if ($subaction === 'reset_defaults') {
        $defaults = [
            ['item' => 'Physical Room Key & Brass Keychain', 'qty' => 1, 'notes' => 'Handover at reception'],
            ['item' => 'TV Unit & Set-Top Box', 'qty' => 1, 'notes' => 'Screen undamaged, cables intact'],
            ['item' => 'TV Remote & Set-Top Remote', 'qty' => 2, 'notes' => 'Both remotes working with batteries'],
            ['item' => 'Electric Water Kettle & Ceramic Tray', 'qty' => 1, 'notes' => 'Clean & working condition'],
            ['item' => 'Artisan Coffee Mugs & Spoons', 'qty' => 2, 'notes' => 'Earthen/ceramic sets intact'],
            ['item' => 'Glass Water Pitcher / Spring Bottles', 'qty' => 2, 'notes' => 'Sanitized glass carafes'],
            ['item' => 'Hairdryer (Grooming Kit)', 'qty' => 1, 'notes' => 'Kept in bathroom drawer'],
            ['item' => 'Emergency High-Beam LED Torch', 'qty' => 1, 'notes' => 'Rechargeable forest torch'],
            ['item' => 'Heavy-Duty Walking Umbrellas', 'qty' => 2, 'notes' => 'In umbrella stand at entrance'],
            ['item' => 'Mosquito Vaporizer Unit', 'qty' => 1, 'notes' => 'Plugged in bedside socket'],
            ['item' => 'Organic Cotton Bath & Hand Towels', 'qty' => 4, 'notes' => '2 Large Bath + 2 Hand Towels'],
            ['item' => 'Heavy Duck-Down Quilts & Blankets', 'qty' => 2, 'notes' => 'In wardrobe / master bed'],
            ['item' => 'Balcony Cane Chairs & Teak Table', 'qty' => 1, 'notes' => 'Sit-out furniture undamaged'],
            ['item' => 'Fireplace Guard / Tool Set', 'qty' => 1, 'notes' => 'If hearth villa']
        ];
        set_setting('room_audit_checklist', json_encode($defaults, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $pdo);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Room inspection checklist reset to factory 14 items.',
                'items' => $defaults
            ]);
            exit;
        }
        header('Location: settings.php?msg=audit_checklist_reset');
        exit;
    }

    $raw_items = $_POST['items'] ?? [];
    if (is_string($raw_items)) {
        $raw_items = json_decode($raw_items, true) ?: [];
    }
    $clean_items = [];
    if (is_array($raw_items)) {
        foreach ($raw_items as $it) {
            $name = trim($it['item'] ?? ($it['name'] ?? ''));
            if ($name === '') continue;
            $qty = max(1, (int)($it['qty'] ?? 1));
            $notes = trim($it['notes'] ?? ($it['category'] ?? ''));
            $clean_items[] = [
                'item' => $name,
                'qty' => $qty,
                'notes' => $notes
            ];
        }
    }
    set_setting('room_audit_checklist', json_encode($clean_items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $pdo);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Room inspection checklist items updated successfully (' . count($clean_items) . ' items saved).',
            'items' => $clean_items
        ]);
        exit;
    }
    header('Location: settings.php?msg=audit_checklist_saved');
    exit;
}

// Handle User Profiles & Concierge Notes Management (AJAX from General Settings)
if (isset($_POST['action']) && $_POST['action'] === 'manage_general_users') {
    header('Content-Type: application/json');
    $subaction = trim($_POST['subaction'] ?? '');
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($subaction === 'toggle_verify') {
        $chk = $pdo->prepare("SELECT is_verified, full_name FROM users WHERE id = ?");
        $chk->execute([$user_id]);
        $u_row = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$u_row) {
            echo json_encode(['success' => false, 'message' => 'User profile not found.']);
            exit;
        }
        $new_verified = ($u_row['is_verified'] == 1) ? 0 : 1;
        $upd = $pdo->prepare("UPDATE users SET is_verified = ?, verified_at = " . ($new_verified ? "NOW()" : "NULL") . " WHERE id = ?");
        $upd->execute([$new_verified, $user_id]);
        echo json_encode([
            'success' => true,
            'is_verified' => $new_verified,
            'message' => ($new_verified ? "Verified blue tick granted to '{$u_row['full_name']}'." : "Verification blue tick removed from '{$u_row['full_name']}'.")
        ]);
        exit;
    }

    if ($subaction === 'toggle_status') {
        $chk = $pdo->prepare("SELECT status, full_name FROM users WHERE id = ?");
        $chk->execute([$user_id]);
        $u_row = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$u_row) {
            echo json_encode(['success' => false, 'message' => 'User profile not found.']);
            exit;
        }
        $new_status = ($u_row['status'] == 1) ? 0 : 1;
        $upd = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $upd->execute([$new_status, $user_id]);
        echo json_encode([
            'success' => true,
            'status' => $new_status,
            'message' => ($new_status ? "Profile for '{$u_row['full_name']}' is now ACTIVE." : "Profile for '{$u_row['full_name']}' is now DISABLED.")
        ]);
        exit;
    }

    if ($subaction === 'save_notes') {
        $notes = trim($_POST['admin_notes'] ?? '');
        $upd = $pdo->prepare("UPDATE users SET admin_notes = ? WHERE id = ?");
        $upd->execute([$notes, $user_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Concierge feedback & guest notes saved. Staff will notice this during next booking!'
        ]);
        exit;
    }

    if ($subaction === 'reset_password') {
        $new_pwd = $_POST['new_password'] ?? '';
        if (empty($new_pwd) || strlen($new_pwd) < 4) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters long.']);
            exit;
        }
        $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $upd->execute([$hash, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Password for this profile has been reset successfully.']);
        exit;
    }

    if ($subaction === 'edit_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (empty($full_name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Full Name and Email are required.']);
            exit;
        }
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->execute([$email, $user_id]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email is already used by another account.']);
            exit;
        }
        $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $upd->execute([$full_name, $email, $phone, $user_id]);
        echo json_encode(['success' => true, 'message' => 'User profile updated successfully.']);
        exit;
    }

    if ($subaction === 'delete_user') {
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'User profile deleted permanently.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown user management action.']);
    exit;
}

// Handle Admin Security & Profile Update (AJAX from General Settings)
if (isset($_POST['action']) && $_POST['action'] === 'update_admin_security') {
    header('Content-Type: application/json');
    $admin_id = (int)$_SESSION['admin_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_pwd = $_POST['current_password'] ?? '';
    $new_pwd = $_POST['new_password'] ?? '';
    $confirm_pwd = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Full name and username cannot be blank.']);
        exit;
    }

    $chk = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $chk->execute([$username, $admin_id]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username is already taken by another admin.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT password_hash, avatar_url FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $adm_rec = $stmt->fetch(PDO::FETCH_ASSOC);
    $stored_hash = $adm_rec['password_hash'] ?? '';
    $avatar_url = $adm_rec['avatar_url'] ?? null;

    if (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
        $avatar_url = null;
    } elseif (!empty($_FILES['avatar_file']['name']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $up = handle_image_upload($_FILES['avatar_file'], 'admin_avatar', ['max_dimension' => 600, 'quality' => 88]);
        if ($up['success']) {
            $avatar_url = $up['path'];
        }
    }

    if (!empty($new_pwd) || !empty($current_pwd)) {
        $is_valid = ($current_pwd === $stored_hash || password_verify($current_pwd, $stored_hash));
        if (!$is_valid) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }
        if (strlen($new_pwd) < 4) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 4 characters long.']);
            exit;
        }
        if ($new_pwd !== $confirm_pwd) {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
            exit;
        }
        $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE admins SET full_name = ?, username = ?, email = ?, avatar_url = ?, password_hash = ? WHERE id = ?");
        $upd->execute([$full_name, $username, $email, $avatar_url, $hash, $admin_id]);
    } else {
        $upd = $pdo->prepare("UPDATE admins SET full_name = ?, username = ?, email = ?, avatar_url = ? WHERE id = ?");
        $upd->execute([$full_name, $username, $email, $avatar_url, $admin_id]);
    }

    $_SESSION['admin_name'] = $full_name;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_email'] = $email;
    $_SESSION['admin_avatar'] = $avatar_url;

    echo json_encode([
        'success' => true,
        'message' => 'Administrator profile & security credentials updated successfully!',
        'full_name' => $full_name,
        'username' => $username,
        'email' => $email,
        'avatar_url' => $avatar_url
    ]);
    exit;
}

// Handle Navigation Menu Update Action (Strictly Edit-Only: No Add/Delete)
if (isset($_POST['action']) && $_POST['action'] === 'update_navigation_menu') {
    if (!empty($_POST['reset_defaults'])) {
        $default_menu = get_default_navigation_menu();
        set_setting('site_navigation_menu', json_encode($default_menu, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $pdo);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Navigation menu reset to original default items.',
                'items' => $default_menu
            ]);
            exit;
        }
        header('Location: settings.php?msg=nav_menu_reset');
        exit;
    }

    $raw_items = $_POST['items'] ?? [];
    if (!is_array($raw_items) || empty($raw_items)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No menu items received.']);
            exit;
        }
        header('Location: settings.php?error=nav_menu_empty');
        exit;
    }

    // Strictly enforce the 10 existing items: NO ADD OR DELETE MENU
    $default_menu = get_default_navigation_menu();
    $updated_menu = [];

    foreach ($default_menu as $idx => $def_item) {
        $item_id = (int)$def_item['id'];
        $incoming = $raw_items[$item_id] ?? ($raw_items[$idx] ?? []);

        $label = trim($incoming['label'] ?? $def_item['label']);
        if ($label === '') $label = $def_item['label'];

        $url = trim($incoming['url'] ?? $def_item['url']);
        if ($url === '') $url = $def_item['url'];

        $icon = trim($incoming['icon'] ?? '');
        $highlight = !empty($incoming['highlight']);
        $show_in_desktop = !empty($incoming['show_in_desktop']);

        $updated_menu[] = [
            'id' => $item_id,
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'highlight' => $highlight,
            'show_in_desktop' => $show_in_desktop
        ];
    }

    set_setting('site_navigation_menu', json_encode($updated_menu, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $pdo);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Navigation menu updated successfully.',
            'items' => $updated_menu
        ]);
        exit;
    }
    header('Location: settings.php?msg=nav_menu_updated');
    exit;
}

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

        // Also refresh the canonical disk backup in db folder
        @file_put_contents(__DIR__ . '/../db/foodforest.sql', $sql_dump);

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

$food_ordering_enabled = (get_setting('food_ordering_enabled', '1') !== '0');
$walkthrough_360_enabled = (get_setting('walkthrough_360_enabled', '1') !== '0');
$gst_rate_cottage = (float)get_setting('gst_rate_cottage', '12');
$gst_rate_food = (float)get_setting('gst_rate_food', '5');
$gst_rate_other = (float)get_setting('gst_rate_other', '18');
$gst_number = get_setting('gst_number', '32AAECF1234M1Z5');
$gst_legal_name = get_setting('gst_legal_name', 'Food Forest Eco Sanctuary');

$all_rooms_list = get_all_rooms($pdo);

// Find active woodhouse / treehouse room
$woodhouse_room_id = (int)get_setting('walkthrough_woodhouse_room_id', 0);
$woodhouse_room = null;
if ($woodhouse_room_id > 0) {
    foreach ($all_rooms_list as $r) {
        if ((int)$r['id'] === $woodhouse_room_id) { $woodhouse_room = $r; break; }
    }
}
if (!$woodhouse_room) {
    foreach ($all_rooms_list as $r) {
        $st = $r['stay_type'] ?? '';
        $sl = $r['slug'] ?? '';
        $ti = $r['title'] ?? '';
        if ($st === 'treehouse' || stripos($sl, 'tree') !== false || stripos($ti, 'tree') !== false || stripos($sl, 'wood') !== false || stripos($ti, 'wood') !== false) {
            $woodhouse_room = $r;
            break;
        }
    }
}
if (!$woodhouse_room && !empty($all_rooms_list)) {
    $woodhouse_room = $all_rooms_list[0];
}

// Find active mudhouse room
$mudhouse_room_id = (int)get_setting('walkthrough_mudhouse_room_id', 0);
$mudhouse_room = null;
if ($mudhouse_room_id > 0) {
    foreach ($all_rooms_list as $r) {
        if ((int)$r['id'] === $mudhouse_room_id) { $mudhouse_room = $r; break; }
    }
}
if (!$mudhouse_room) {
    foreach ($all_rooms_list as $r) {
        $st = $r['stay_type'] ?? '';
        $sl = $r['slug'] ?? '';
        $ti = $r['title'] ?? '';
        if ($st === 'mudhouse' || stripos($sl, 'mud') !== false || stripos($ti, 'mud') !== false) {
            $mudhouse_room = $r;
            break;
        }
    }
}
if (!$mudhouse_room && !empty($all_rooms_list)) {
    $mudhouse_room = $all_rooms_list[count($all_rooms_list) - 1];
}

$wood_pano_url = get_setting('walkthrough_woodhouse_pano', $woodhouse_room['interior_360_url'] ?? 'assets/images/treehouse_360_pano.jpg');
$wood_cover_url = get_setting('walkthrough_woodhouse_cover', $woodhouse_room['image_url'] ?? 'assets/images/treehouse_exterior.png');

$mud_pano_url = get_setting('walkthrough_mudhouse_pano', $mudhouse_room['interior_360_url'] ?? 'assets/images/mudhouse_360_pano.jpg');
$mud_cover_url = get_setting('walkthrough_mudhouse_cover', $mudhouse_room['image_url'] ?? 'assets/images/mudhouse_exterior.png');
$nav_menu_items = get_navigation_menu();

$site_logo = get_setting('site_logo', '');
$site_name = get_setting('site_name', 'FOOD FOREST');
$site_tagline = get_setting('site_tagline', 'KANTHALLOOR • ECO SANCTUARY');
$checkin_time = get_setting('checkin_time', '02:00 PM');
$checkout_time = get_setting('checkout_time', '11:00 AM');
$currency_symbol = get_setting('currency_symbol', '₹');
$admin_session_timeout_minutes = (int)get_setting('admin_session_timeout_minutes', '15');
$client_session_timeout_minutes = (int)get_setting('client_session_timeout_minutes', '15');
$live_preview_enabled = (get_setting('live_preview_enabled', '1') !== '0');
$live_preview_cards_config = json_decode(get_setting('live_preview_cards_config', '{}'), true) ?: [];
$room_audit_checklist = json_decode(get_setting('room_audit_checklist', '[]'), true) ?: [];
if (empty($room_audit_checklist)) {
    $room_audit_checklist = [
        ['item' => 'Physical Room Key & Brass Keychain', 'qty' => 1, 'notes' => 'Handover at reception'],
        ['item' => 'TV Unit & Set-Top Box', 'qty' => 1, 'notes' => 'Screen undamaged, cables intact'],
        ['item' => 'TV Remote & Set-Top Remote', 'qty' => 2, 'notes' => 'Both remotes working with batteries'],
        ['item' => 'Electric Water Kettle & Ceramic Tray', 'qty' => 1, 'notes' => 'Clean & working condition'],
        ['item' => 'Artisan Coffee Mugs & Spoons', 'qty' => 2, 'notes' => 'Earthen/ceramic sets intact'],
        ['item' => 'Glass Water Pitcher / Spring Bottles', 'qty' => 2, 'notes' => 'Sanitized glass carafes'],
        ['item' => 'Hairdryer (Grooming Kit)', 'qty' => 1, 'notes' => 'Kept in bathroom drawer'],
        ['item' => 'Emergency High-Beam LED Torch', 'qty' => 1, 'notes' => 'Rechargeable forest torch'],
        ['item' => 'Heavy-Duty Walking Umbrellas', 'qty' => 2, 'notes' => 'In umbrella stand at entrance'],
        ['item' => 'Mosquito Vaporizer Unit', 'qty' => 1, 'notes' => 'Plugged in bedside socket'],
        ['item' => 'Organic Cotton Bath & Hand Towels', 'qty' => 4, 'notes' => '2 Large Bath + 2 Hand Towels'],
        ['item' => 'Heavy Duck-Down Quilts & Blankets', 'qty' => 2, 'notes' => 'In wardrobe / master bed'],
        ['item' => 'Balcony Cane Chairs & Teak Table', 'qty' => 1, 'notes' => 'Sit-out furniture undamaged'],
        ['item' => 'Fireplace Guard / Tool Set', 'qty' => 1, 'notes' => 'If hearth villa']
    ];
}
$all_registered_users = $pdo->query("SELECT id, username, full_name, email, phone, avatar_url, is_google_verified, is_verified, status, admin_notes, last_login, created_at FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$current_admin_user = current_admin();
?>

<!-- Subheader Status & Search / Quick Action Toolbar -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px; padding: 4px 2px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span class="adm-pulse-dot" style="background:#2ecc71; box-shadow: 0 0 10px #2ecc71;"></span>
        <div>
            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; letter-spacing: 0.3px; display: block;">
                Estate Configuration &amp; Frontend Management
            </span>
            <span style="font-size: 12px; color: var(--adm-text-muted);">
                Click the master General Settings hub or any section card below to customize estate parameters and frontend sections:
            </span>
        </div>
    </div>
    
    <div style="display: flex; align-items: center; gap: 10px;">
        <div style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--adm-text-muted); font-size: 12px;"></i>
            <input type="text" id="adm-card-filter-input" placeholder="Search 18 sections..." onkeyup="filterSettingsCards(this.value);" style="background: var(--adm-bg-surface); border: 1px solid var(--adm-gold-border); border-radius: 20px; padding: 7px 14px 7px 32px; font-size: 12px; color: #FFF; outline: none; width: 200px;">
        </div>

        <button type="button" onclick="openGeneralSettingsModal('branding');" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);" title="Open General Settings Hub">
            <i class="fa-solid fa-sliders"></i>
            <span>General Settings</span>
        </button>
        <a href="../index.php" target="_blank" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;" title="Open Public Website">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Live Website</span>
        </a>
        <a href="settings.php?action=download_backup" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;" title="Export MySQL Database Dump">
            <i class="fa-solid fa-cloud-arrow-down"></i>
            <span>Download SQL</span>
        </a>
    </div>
</div>

<!-- ================================================================= -->
<!-- 18 UNIQUE FRONTEND & ESTATE CONFIGURATION CARDS GRID               -->
<!-- ================================================================= -->
<div class="adm-settings-cards-grid" id="adm-cards-top-grid">

    <!-- Master Card: GENERAL SETTINGS & ESTATE CONFIGURATION -->
    <div class="adm-setting-card-btn" id="adm-card-general-settings" data-title="general settings branding logo name tagline gst food ordering preview session timeout logout password profile picture user management room audit checklist" style="display: flex; cursor: pointer; border: 1.5px solid rgba(197, 160, 89, 0.45); background: linear-gradient(135deg, rgba(197, 160, 89, 0.14) 0%, rgba(10, 25, 16, 0.95) 100%); grid-column: 1 / -1; box-shadow: 0 8px 30px rgba(0,0,0,0.45);" onclick="openGeneralSettingsModal('branding');">
        <div class="adm-setting-card-icon gold" style="width: 52px; height: 52px; font-size: 22px; background: rgba(197, 160, 89, 0.22); color: var(--adm-gold); border: 1.5px solid rgba(197, 160, 89, 0.5); flex-shrink: 0;">
            <i class="fa-solid fa-sliders"></i>
        </div>
        <div class="adm-setting-card-content" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="adm-setting-card-num" style="color: var(--adm-gold); font-weight: 800; font-size: 11px; letter-spacing: 1px;">
                        MASTER CONFIGURATION • GENERAL SETTINGS
                    </span>
                    <span class="adm-badge" style="background: rgba(197, 160, 89, 0.25); color: #FFF; border: 1px solid var(--adm-gold); font-size: 9.5px; padding: 1px 8px; font-weight: 700;">
                        NEW HUB
                    </span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span class="adm-badge" style="background: rgba(46, 204, 113, 0.18); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.35); font-size: 10px;">
                        <i class="fa-solid fa-circle-check"></i> System Operational
                    </span>
                </div>
            </div>
            <h4 style="font-size: 17px; margin-top: 5px; color: #FFFFFF; font-family: var(--adm-font-title);">General Settings &amp; Operations</h4>
            <p style="font-size: 12.5px; color: var(--adm-text-secondary); margin-top: 3px;">
                Resort Logo &amp; Identity • Multi-Tier GST Rates • Guest Food Ordering • Live Preview Toggles • Logout Timings • User Profiles &amp; Blue Tick • Admin Security • Room Audit Checklist
            </p>
            <div style="margin-top: 10px; display: flex; gap: 10px; font-size: 11px; flex-wrap: wrap; align-items: center;">
                <span style="color: var(--adm-gold); font-weight: 600;"><i class="fa-solid fa-image"></i> Logo &amp; Name</span> • 
                <span style="color: #38BDF8; font-weight: 600;"><i class="fa-solid fa-receipt"></i> Multi-Tier GST</span> • 
                <span style="color: #2ECC71; font-weight: 600;"><i class="fa-solid fa-utensils"></i> Food Ordering</span> • 
                <span style="color: #A855F7; font-weight: 600;"><i class="fa-solid fa-users"></i> Users Hub (Blue Tick)</span> • 
                <span style="color: #F59E0B; font-weight: 600;"><i class="fa-solid fa-clock"></i> Logout Timings</span> • 
                <span style="color: #EC4899; font-weight: 600;"><i class="fa-solid fa-clipboard-check"></i> Room Audit</span>
            </div>
            <span style="font-size: 12px; color: var(--adm-gold); font-weight: 700; margin-top: 10px; display: inline-flex; align-items: center; gap: 6px;">
                Open General Settings Hub <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </div>

    <!-- Card 01: Climate & Accolades (Top Header Ticker) -->
    <a href="edit_section.php?section=climate" class="adm-setting-card-btn" data-title="climate accolades weather temperature header ticker high ranges" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon cyan"><i class="fa-solid fa-temperature-half"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 01 • CLIMATE</span>
            <h4>Climate & Accolades</h4>
            <p>Top header ticker & microclimate</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 02: Hero Section & Atmosphere -->
    <a href="edit_section.php?section=hero" class="adm-setting-card-btn" data-title="hero marquee visual headline backdrop banner background" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon purple"><i class="fa-solid fa-mountain-sun"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 02 • HERO</span>
            <h4>Hero Marquee & Visual</h4>
            <p>Headline, narrative & backdrop</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 03: Philosophy & Ethos (Welcome Intro) -->
    <a href="edit_section.php?section=philosophy" class="adm-setting-card-btn" data-title="philosophy ethos welcome manifesto portrait story" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon gold"><i class="fa-solid fa-compass-drafting"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 03 • ETHOS</span>
            <h4>Sanctuary Philosophy</h4>
            <p>Welcome manifesto & portrait</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 04: Villas & Accommodations -->
    <a href="edit_section.php?section=rooms" class="adm-setting-card-btn" data-title="villas cottages rooms treehouse mudhouse rates tariffs pricing" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon rose"><i class="fa-solid fa-house-chimney"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 04 • VILLAS & RATES</span>
            <h4>Villas & Cottages</h4>
            <p>Edit Treehouse & Mudhouse tariffs</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 05: Curated Experiences -->
    <a href="edit_section.php?section=experiences" class="adm-setting-card-btn" data-title="curated experiences rituals timings harvest starlight mud workshop" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon violet"><i class="fa-solid fa-person-hiking"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 05 • EXPERIENCES</span>
            <h4>Curated Experiences</h4>
            <p>Edit all 4 rituals, timings & photos</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 06: Food Menu & Gastronomy Hub -->
    <a href="edit_section.php?section=menu" class="adm-setting-card-btn" data-title="food menu gastronomy dining breakfast lunch dinner dishes items" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon amber"><i class="fa-solid fa-utensils"></i></div>
        <div class="adm-setting-card-content">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="adm-setting-card-num">CARD 06 • GASTRONOMY</span>
                <span id="card06-status-badge" class="adm-badge" style="<?php echo $food_ordering_enabled ? 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);' : 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);'; ?> font-size: 9px; padding: 1px 6px;">
                    <?php echo $food_ordering_enabled ? 'Ordering Active' : 'Ordering Paused'; ?>
                </span>
            </div>
            <h4>Food Menu & Dining</h4>
            <p>Breakfast, lunch, snacks & dinner dishes</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 07: Why Farmstay Story (Cob Architecture) -->
    <a href="edit_section.php?section=why" class="adm-setting-card-btn" data-title="why food forest architecture cob mudhouse living soil farmstay" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon amber"><i class="fa-solid fa-seedling"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 07 • ARCHITECTURE</span>
            <h4>Why Food Forest?</h4>
            <p>Living soil & cob mudhouse</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 08: Sanctuary Estate Map & Mountain Route Trails -->
    <a href="edit_section.php?section=sanctuary_map" class="adm-setting-card-btn" data-title="map sanctuary route trail spots viewpoints pins coordinates" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-map-location-dot"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 08 • MAP & ROUTE</span>
            <h4>Sanctuary Estate Map</h4>
            <p>Route trail (1→2→3→4), villas & spots</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 09: Seasons of Kanthalloor -->
    <a href="edit_section.php?section=seasons" class="adm-setting-card-btn" data-title="seasons kanthalloor weather harvest months winter spring monsoon autumn" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon terracotta"><i class="fa-solid fa-cloud-sun"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 09 • SEASONS</span>
            <h4>Seasons of Kanthalloor</h4>
            <p>Edit all 4 seasons, months & photos</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 10: Visual Diary (Gallery) -->
    <a href="edit_section.php?section=gallery" class="adm-setting-card-btn" data-title="visual diary gallery photos photography archive images" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon teal"><i class="fa-solid fa-camera-retro"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 10 • GALLERY</span>
            <h4>Visual Diary (Gallery)</h4>
            <p>Edit 8 photographs, tags & titles</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 11: Guest Reflections -->
    <a href="edit_section.php?section=testimonials" class="adm-setting-card-btn" data-title="guest reflections reviews testimonials stars quotes traveler" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon blue"><i class="fa-solid fa-comment-dots"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 11 • REVIEWS</span>
            <h4>Guest Reflections</h4>
            <p>Edit traveler reviews, stars & quotes</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 12: WhatsApp & Concierge Channels -->
    <a href="edit_section.php?section=whatsapp" class="adm-setting-card-btn" data-title="whatsapp concierge phone telephone address location direct line" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon emerald"><i class="fa-brands fa-whatsapp"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 12 • CHANNELS</span>
            <h4>WhatsApp & Concierge</h4>
            <p>Direct line, telephone & address</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 13: Content & Image Protection -->
    <a href="edit_section.php?section=protection" class="adm-setting-card-btn" data-title="content protection shield security devtools anti copy image shield" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 13 • PROTECTION</span>
            <h4>Content Protection</h4>
            <p>Anti-copy & DevTools shield</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 14: Bank Details & UPI Payment QR -->
    <a href="edit_section.php?section=bank" class="adm-setting-card-btn" data-title="bank payment upi qr code account ifsc branch billing transfer folio invoice" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-building-columns"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 14 • BANK & UPI QR</span>
            <h4>Bank Details & UPI QR</h4>
            <p>A/C number, IFSC, QR upload & bill print</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 15: Footer & Eco Trust Pillars -->
    <a href="edit_section.php?section=footer" class="adm-setting-card-btn" data-title="footer badges sustainability brand pillars legal copyright sanctuary gazette contact concierge navigation links" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon gold"><i class="fa-solid fa-seedling"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 15 • FOOTER</span>
            <h4>Footer & Eco Trust Pillars</h4>
            <p>Trust badges, navigation, bio, gazette & legal</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 16: 360° Dynamic Walkthrough & Panoramas -->
    <div class="adm-setting-card-btn" id="adm-walkthrough-card-btn" data-title="360 dynamic walkthrough virtual tour webgl panorama woodhouse treehouse mudhouse interactive framing" style="display: flex; cursor: pointer;" onclick="openWalkthroughModal();">
        <div class="adm-setting-card-icon <?php echo $walkthrough_360_enabled ? 'emerald' : 'rose'; ?>" id="card16-icon">
            <i class="fa-solid fa-arrows-spin"></i>
        </div>
        <div class="adm-setting-card-content" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="adm-setting-card-num" style="color: <?php echo $walkthrough_360_enabled ? '#2ecc71' : '#f87171'; ?>;">CARD 16 • 360° WALKTHROUGH</span>
                <span id="card16-status-badge" class="adm-badge" style="<?php echo $walkthrough_360_enabled ? 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);' : 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);'; ?> font-size: 9.5px; padding: 1px 6px;">
                    <?php echo $walkthrough_360_enabled ? 'Live / On' : 'Hidden / Off'; ?>
                </span>
            </div>
            <h4>360° Walkthrough Tour</h4>
            <p>Woodhouse &amp; Mudhouse 360° panoramas &amp; crop framing</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Configure 360° Tours <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </div>

    <!-- Card 17: Analytics & Business Reports Hub -->
    <a href="reports.php" class="adm-setting-card-btn" data-title="reports analytics evaluation revenue bookings food ordering weekly monthly quarterly yearly item wise sales audit" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon gold" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.35);"><i class="fa-solid fa-chart-line"></i></div>
        <div class="adm-setting-card-content" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="adm-setting-card-num" style="color: var(--adm-gold);">CARD 17 • REPORTS &amp; AUDIT</span>
                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); font-size: 9.5px; padding: 1px 6px;">Analytics Hub</span>
            </div>
            <h4>Business &amp; Operations Reports</h4>
            <p>Weekly, monthly, quarterly, yearly &amp; dish-wise evaluation</p>
            <div style="margin-top: 8px; display: flex; gap: 6px; font-size: 10.5px;">
                <span style="color: #2ECC71; font-weight: 600;"><i class="fa-solid fa-bed"></i> Bookings</span> • 
                <span style="color: #F59E0B; font-weight: 600;"><i class="fa-solid fa-utensils"></i> Dining</span> • 
                <span style="color: #38BDF8; font-weight: 600;"><i class="fa-solid fa-arrow-trend-up"></i> RevPAR</span>
            </div>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Reports Hub <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 18: Navigation Menu CMS (Strictly Edit-Only, 10 Fixed Items) -->
    <div class="adm-setting-card-btn" id="adm-nav-menu-card-btn" data-title="menu navigation navbar drawer links mobile menu the sanctuary villas stays interactive map booking activities our menu landscape gallery guest stories guest portal contact header" style="display: flex; cursor: pointer;" onclick="openNavMenuModal();">
        <div class="adm-setting-card-icon amber" style="background: rgba(245, 158, 11, 0.15); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35);">
            <i class="fa-solid fa-bars"></i>
        </div>
        <div class="adm-setting-card-content" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="adm-setting-card-num" style="color: #F59E0B;">CARD 18 • MENU</span>
                <span class="adm-badge" style="background: rgba(245, 158, 11, 0.15); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 9.5px; padding: 1px 6px;">10 Items (Edit Only)</span>
            </div>
            <h4>Navigation Menu</h4>
            <p>Customize labels, links &amp; icons for the 10 sanctuary menu items</p>
            <div style="margin-top: 8px; display: flex; gap: 6px; font-size: 10.5px; flex-wrap: wrap;">
                <span style="color: #2ECC71; font-weight: 600;"><i class="fa-solid fa-pen"></i> Edit In-Place</span> • 
                <span style="color: var(--adm-gold); font-weight: 600;"><i class="fa-solid fa-mobile-screen"></i> Mobile Drawer</span> • 
                <span style="color: #38BDF8; font-weight: 600;"><i class="fa-solid fa-lock"></i> Fixed Count</span>
            </div>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Edit Navigation Menu <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </div>

</div>

<!-- Modal: Interactive 360 Walkthrough & Panoramas Management -->
<div id="modal-walkthrough-360" style="display: none; position: fixed; inset: 0; background: rgba(3, 10, 6, 0.85); z-index: 99990; align-items: center; justify-content: center; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); padding: 20px;">
    <div style="background: linear-gradient(145deg, #0e1e13 0%, #08130c 100%); border: 1.5px solid var(--adm-gold-border); border-radius: 16px; width: 100%; max-width: 1120px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 16px 50px rgba(0,0,0,0.65); overflow: hidden;">
        
        <!-- Modal Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); flex-wrap: wrap; gap: 14px; background: rgba(0,0,0,0.25);">
            <div style="display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1;">
                <div class="adm-setting-card-icon <?php echo $walkthrough_360_enabled ? 'emerald' : 'rose'; ?>" id="modal-walkthrough-icon" style="width: 42px; height: 42px; font-size: 18px; border-radius: 10px; flex-shrink: 0; background: <?php echo $walkthrough_360_enabled ? 'rgba(197, 160, 89, 0.18)' : 'rgba(239, 68, 68, 0.15)'; ?>; color: <?php echo $walkthrough_360_enabled ? 'var(--adm-gold)' : '#f87171'; ?>; border: 1px solid <?php echo $walkthrough_360_enabled ? 'rgba(197, 160, 89, 0.4)' : 'rgba(239, 68, 68, 0.35)'; ?>;">
                    <i class="fa-solid fa-arrows-spin"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 3px; flex-wrap: wrap;">
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">
                            360° Walkthrough Tour Management
                        </h3>
                        <span id="modal-walkthrough-status-badge" class="adm-badge" style="<?php echo $walkthrough_360_enabled ? 'background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4);' : 'background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);'; ?> font-size: 10.5px; padding: 2px 9px; font-weight: 700; border-radius: 6px;">
                            <?php echo $walkthrough_360_enabled ? '<i class="fa-solid fa-circle-check"></i> LIVE ON HOMEPAGE' : '<i class="fa-solid fa-circle-pause"></i> DISABLED / HIDDEN'; ?>
                        </span>
                    </div>
                    <p style="font-size: 11.5px; color: var(--adm-text-secondary); margin: 0;">
                        Configure interactive 3D WebGL tours, 2:1 panoramas &amp; 16:9 covers for Woodhouse &amp; Mudhouse
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" id="modal-toggle-walkthrough-btn" onclick="toggleWalkthroughStatus(<?php echo $walkthrough_360_enabled ? '0' : '1'; ?>);" class="adm-btn-action <?php echo $walkthrough_360_enabled ? 'rose' : 'emerald'; ?>" style="padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 8px;">
                    <i class="fa-solid <?php echo $walkthrough_360_enabled ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                    <span id="modal-toggle-walkthrough-text"><?php echo $walkthrough_360_enabled ? 'Disable 360 Tour' : 'Enable 360 Tour'; ?></span>
                </button>

                <a href="../index.php#rooms-experience" target="_blank" class="adm-btn-action outline" style="padding: 8px 14px; font-size: 12px;" title="Preview 360 Tour on Live Website">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Live Preview</span>
                </a>

                <button type="button" onclick="closeWalkthroughModal();" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #FFF; width: 34px; height: 34px; border-radius: 8px; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" title="Close Modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Auto-Compression & Image Optimization Control Bar -->
        <div style="background: rgba(16, 185, 129, 0.08); border-bottom: 1px solid rgba(16, 185, 129, 0.22); padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <label class="adm-compress-option-pill" style="margin: 0; cursor: pointer; background: rgba(16, 185, 129, 0.16); border-color: rgba(16, 185, 129, 0.45);" title="Automatically compress and optimize high-resolution 360° panoramas & exterior covers">
                    <input type="checkbox" id="walkthrough-auto-compress-toggle" name="auto_compress" value="1" checked onchange="onWalkthroughCompressToggle(this.checked);">
                    <span><i class="fa-solid fa-bolt" style="color:#10B981;"></i> Auto-compress &amp; resize</span>
                    <span class="badge-rec">⚡ RECOMMENDED</span>
                </label>
                <span style="font-size: 11.5px; color: var(--adm-text-secondary);">
                    Downsamples high-res (10MB–30MB) 360° panoramas &amp; covers to WebGL-optimized 2:1 spheres for 70–90% faster page loads.
                </span>
            </div>
            <a href="edit_section.php?section=media" target="_blank" class="adm-btn-action outline" style="padding: 6px 12px; font-size: 11px; gap: 5px;" title="Fine-tune JPEG quality, dimensions & WebP sandbox in Media Optimizer">
                <i class="fa-solid fa-sliders"></i>
                <span>Image Optimizer Settings</span>
            </a>
        </div>

        <!-- Modal Body (Scrollable Dual-Column Tour Editor) -->
        <div style="padding: 22px 24px; overflow-y: auto; flex: 1;">
            <div id="walkthrough-operations-panel" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
                
                <!-- STAY 1: WOODHOUSE (Timber Treehouse / Alpine Chalet) -->
                <div style="background: rgba(6, 17, 10, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 12px; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">🪵</span>
                            <div>
                                <h4 style="margin: 0; font-family: var(--adm-font-title); font-size: 14px; color: #FFFFFF; letter-spacing: 0.5px;">
                                    WOODHOUSE 360° TOUR
                                </h4>
                                <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600;">Timber Treehouse &amp; Alpine Chalet</span>
                            </div>
                        </div>
                        <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); font-size: 10px; padding: 2px 8px;">
                            CONCEPT TAB 01
                        </span>
                    </div>

                    <!-- Linked Room Selector -->
                    <div style="margin-bottom: 16px;">
                        <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                            Linked Room / Cottage Entity:
                        </label>
                        <select id="walkthrough_woodhouse_room_select" class="adm-form-control" style="font-size: 12px; padding: 8px 10px;" onchange="updateWalkthroughRoom('woodhouse', this);">
                            <?php foreach ($all_rooms_list as $r_opt): 
                                $is_sel = ($woodhouse_room && (int)$woodhouse_room['id'] === (int)$r_opt['id']);
                            ?>
                                <option value="<?php echo $r_opt['id']; ?>" <?php echo $is_sel ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r_opt['title']); ?> (₹<?php echo number_format($r_opt['rate_per_night'] ?? 0); ?>/night)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- A. 360° Interior Panorama -->
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-street-view" style="color: var(--adm-gold);"></i> 360° Interior Panorama
                            </span>
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px;">
                                2:1 Equirectangular
                            </span>
                        </div>

                        <!-- Panorama Thumbnail -->
                        <div style="width: 100%; height: 130px; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid var(--adm-gold-border); background: #000; margin-bottom: 10px;">
                            <img id="prev_woodhouse_pano" src="../<?php echo htmlspecialchars($wood_pano_url); ?>" alt="Woodhouse 360 Panorama" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_360_pano.jpg';">
                            <span style="position: absolute; bottom: 6px; left: 8px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #FFF; font-size: 9.5px; padding: 2px 7px; border-radius: 4px; font-family: monospace;">
                                <?php echo htmlspecialchars(basename($wood_pano_url)); ?>
                            </span>
                        </div>

                        <!-- Action Buttons: Upload & Crop OR Crop Existing -->
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="adm-btn-action gold" style="flex: 1; padding: 7px 12px; font-size: 11.5px; font-weight: 700; justify-content: center;" onclick="triggerWalkthroughUpload('woodhouse', 'pano');" title="Upload new photo and crop to 2:1 panorama">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Upload &amp; Crop 360 Photo</span>
                            </button>
                            <button type="button" class="adm-btn-action outline" style="padding: 7px 12px; font-size: 11.5px;" onclick="cropExistingWalkthrough('woodhouse', 'pano');" title="Re-crop existing 360 panorama">
                                <i class="fa-solid fa-crop-simple"></i>
                                <span>Crop / Frame</span>
                            </button>
                        </div>
                    </div>

                    <!-- B. Stage 1 Exterior Cover Photo -->
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-camera" style="color: var(--adm-gold);"></i> Stage 1 Exterior Cover
                            </span>
                            <span class="adm-badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 9.5px;">
                                16:9 Widescreen
                            </span>
                        </div>

                        <!-- Exterior Thumbnail -->
                        <div style="width: 100%; height: 110px; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid rgba(255,255,255,0.12); background: #000; margin-bottom: 10px;">
                            <img id="prev_woodhouse_cover" src="../<?php echo htmlspecialchars($wood_cover_url); ?>" alt="Woodhouse Cover" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png';">
                            <span style="position: absolute; bottom: 6px; left: 8px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #FFF; font-size: 9.5px; padding: 2px 7px; border-radius: 4px; font-family: monospace;">
                                <?php echo htmlspecialchars(basename($wood_cover_url)); ?>
                            </span>
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="adm-btn-action gold" style="flex: 1; padding: 7px 12px; font-size: 11.5px; font-weight: 700; justify-content: center;" onclick="triggerWalkthroughUpload('woodhouse', 'cover');" title="Upload new photo and crop to 16:9 widescreen">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Upload &amp; Crop 16:9 Cover</span>
                            </button>
                            <button type="button" class="adm-btn-action outline" style="padding: 7px 12px; font-size: 11.5px;" onclick="cropExistingWalkthrough('woodhouse', 'cover');" title="Re-crop existing cover photo">
                                <i class="fa-solid fa-crop-simple"></i>
                                <span>Crop 16:9</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STAY 2: MUDHOUSE (Handcrafted Earthen Cob Suite) -->
                <div style="background: rgba(6, 17, 10, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 12px; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">🌿</span>
                            <div>
                                <h4 style="margin: 0; font-family: var(--adm-font-title); font-size: 14px; color: #FFFFFF; letter-spacing: 0.5px;">
                                    MUDHOUSE 360° TOUR
                                </h4>
                                <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600;">Earthen Cob Sanctuary Suite</span>
                            </div>
                        </div>
                        <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); font-size: 10px; padding: 2px 8px;">
                            CONCEPT TAB 02
                        </span>
                    </div>

                    <!-- Linked Room Selector -->
                    <div style="margin-bottom: 16px;">
                        <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                            Linked Room / Cottage Entity:
                        </label>
                        <select id="walkthrough_mudhouse_room_select" class="adm-form-control" style="font-size: 12px; padding: 8px 10px;" onchange="updateWalkthroughRoom('mudhouse', this);">
                            <?php foreach ($all_rooms_list as $r_opt): 
                                $is_sel = ($mudhouse_room && (int)$mudhouse_room['id'] === (int)$r_opt['id']);
                            ?>
                                <option value="<?php echo $r_opt['id']; ?>" <?php echo $is_sel ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r_opt['title']); ?> (₹<?php echo number_format($r_opt['rate_per_night'] ?? 0); ?>/night)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- A. 360° Interior Panorama -->
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-street-view" style="color: var(--adm-gold);"></i> 360° Interior Panorama
                            </span>
                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px;">
                                2:1 Equirectangular
                            </span>
                        </div>

                        <!-- Panorama Thumbnail -->
                        <div style="width: 100%; height: 130px; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid var(--adm-gold-border); background: #000; margin-bottom: 10px;">
                            <img id="prev_mudhouse_pano" src="../<?php echo htmlspecialchars($mud_pano_url); ?>" alt="Mudhouse 360 Panorama" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/mudhouse_360_pano.jpg';">
                            <span style="position: absolute; bottom: 6px; left: 8px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #FFF; font-size: 9.5px; padding: 2px 7px; border-radius: 4px; font-family: monospace;">
                                <?php echo htmlspecialchars(basename($mud_pano_url)); ?>
                            </span>
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="adm-btn-action gold" style="flex: 1; padding: 7px 12px; font-size: 11.5px; font-weight: 700; justify-content: center;" onclick="triggerWalkthroughUpload('mudhouse', 'pano');" title="Upload new photo and crop to 2:1 panorama">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Upload &amp; Crop 360 Photo</span>
                            </button>
                            <button type="button" class="adm-btn-action outline" style="padding: 7px 12px; font-size: 11.5px;" onclick="cropExistingWalkthrough('mudhouse', 'pano');" title="Re-crop existing 360 panorama">
                                <i class="fa-solid fa-crop-simple"></i>
                                <span>Crop / Frame</span>
                            </button>
                        </div>
                    </div>

                    <!-- B. Stage 1 Exterior Cover Photo -->
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-camera" style="color: var(--adm-gold);"></i> Stage 1 Exterior Cover
                            </span>
                            <span class="adm-badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 9.5px;">
                                16:9 Widescreen
                            </span>
                        </div>

                        <!-- Exterior Thumbnail -->
                        <div style="width: 100%; height: 110px; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid rgba(255,255,255,0.12); background: #000; margin-bottom: 10px;">
                            <img id="prev_mudhouse_cover" src="../<?php echo htmlspecialchars($mud_cover_url); ?>" alt="Mudhouse Cover" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/mudhouse_exterior.png';">
                            <span style="position: absolute; bottom: 6px; left: 8px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #FFF; font-size: 9.5px; padding: 2px 7px; border-radius: 4px; font-family: monospace;">
                                <?php echo htmlspecialchars(basename($mud_cover_url)); ?>
                            </span>
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="adm-btn-action gold" style="flex: 1; padding: 7px 12px; font-size: 11.5px; font-weight: 700; justify-content: center;" onclick="triggerWalkthroughUpload('mudhouse', 'cover');" title="Upload new photo and crop to 16:9 widescreen">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Upload &amp; Crop 16:9 Cover</span>
                            </button>
                            <button type="button" class="adm-btn-action outline" style="padding: 7px 12px; font-size: 11.5px;" onclick="cropExistingWalkthrough('mudhouse', 'cover');" title="Re-crop existing cover photo">
                                <i class="fa-solid fa-crop-simple"></i>
                                <span>Crop 16:9</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 14px 24px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.25);">
            <span style="font-size: 11.5px; color: var(--adm-text-muted);">
                <i class="fa-solid fa-circle-info" style="color: var(--adm-gold); margin-right: 4px;"></i>
                Photo crops &amp; room bindings are saved immediately upon confirmation.
            </span>
            <button type="button" class="adm-btn-action gold" onclick="closeWalkthroughModal();" style="padding: 7px 20px; font-size: 12px;">
                Done / Close
            </button>
        </div>

    </div>
</div>

<!-- Hidden File Input for Walkthrough Uploads -->
<input type="file" id="walkthrough-file-input" data-no-compress="true" style="display: none;" accept="image/*" onchange="onWalkthroughFileSelected(this);">

<!-- =========================================================================
     MODAL: INTERACTIVE 360 WALKTHROUGH & COVER PHOTO CROPPER
     ========================================================================= -->
<div id="walkthrough-crop-modal" class="adm-cropper-modal-overlay" style="display: none;">
    <div class="adm-cropper-modal-dialog">
        <div class="adm-cropper-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="adm-setting-card-icon gold" style="width: 38px; height: 38px; font-size: 15px;">
                    <i class="fa-solid fa-crop-simple"></i>
                </div>
                <div>
                    <h3 id="cropper-modal-title" style="margin: 0; font-size: 16px; color: #FFFFFF; font-family: var(--adm-font-title); letter-spacing: 0.5px;">
                        CROP &amp; FRAME WALKTHROUGH PHOTO
                    </h3>
                    <p id="cropper-modal-desc" style="margin: 2px 0 0; font-size: 11.5px; color: var(--adm-text-secondary);">
                        Crop and adjust your photo. Standard 2:1 ratio recommended for 360° panoramas; 16:9 for exterior stages.
                    </p>
                </div>
            </div>
            <button type="button" class="adm-drawer-close" onclick="closeWalkthroughCropper();" title="Close Modal">✕</button>
        </div>

        <div class="adm-cropper-modal-body">
            <!-- Aspect Ratio Toolbar -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; background: rgba(6, 17, 10, 0.7); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(197, 160, 89, 0.2);">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;" id="cropper-ratio-buttons">
                    <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase;">Ratio:</span>
                    <button type="button" class="adm-crop-ratio-btn active" data-ratio="2" onclick="setCropperRatio(2/1, this);">
                        <i class="fa-solid fa-panorama"></i> 2:1 Panorama
                    </button>
                    <button type="button" class="adm-crop-ratio-btn" data-ratio="1.7777777778" onclick="setCropperRatio(16/9, this);">
                        <i class="fa-solid fa-film"></i> 16:9 Widescreen
                    </button>
                    <button type="button" class="adm-crop-ratio-btn" data-ratio="1.3333333333" onclick="setCropperRatio(4/3, this);">
                        <i class="fa-solid fa-image"></i> 4:3 Standard
                    </button>
                    <button type="button" class="adm-crop-ratio-btn" data-ratio="1" onclick="setCropperRatio(1, this);">
                        <i class="fa-solid fa-square"></i> 1:1 Square
                    </button>
                    <button type="button" class="adm-crop-ratio-btn" data-ratio="NaN" onclick="setCropperRatio(NaN, this);">
                        <i class="fa-solid fa-vector-square"></i> Free Aspect
                    </button>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button" class="adm-btn-action" style="padding: 5px 10px; font-size: 11px;" onclick="if(activeCropperInstance) activeCropperInstance.rotate(-90);" title="Rotate 90° Left">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                    <button type="button" class="adm-btn-action" style="padding: 5px 10px; font-size: 11px;" onclick="if(activeCropperInstance) activeCropperInstance.rotate(90);" title="Rotate 90° Right">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    <button type="button" class="adm-btn-action" style="padding: 5px 10px; font-size: 11px;" onclick="if(activeCropperInstance) activeCropperInstance.reset();" title="Reset Crop Frame">
                        <i class="fa-solid fa-arrows-rotate"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Cropper Image Canvas Container -->
            <div style="position: relative; width: 100%; height: 420px; background: #060E08; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                <img id="cropper-target-img" src="" alt="Crop Target" style="max-width: 100%; max-height: 100%; display: block;">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 11px; color: var(--adm-text-secondary);">
                <span><i class="fa-solid fa-circle-info" style="color: var(--adm-gold);"></i> Drag corner handles to resize. Drag image to reposition within the framing box.</span>
                <span id="cropper-dims-indicator" style="font-family: monospace; color: #2ecc71; font-weight: 700;">--</span>
            </div>
        </div>

        <div class="adm-cropper-modal-footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <label class="adm-compress-option-pill" style="margin: 0; cursor: pointer; background: rgba(16, 185, 129, 0.16); border-color: rgba(16, 185, 129, 0.45);" title="Auto-compress and resize high-res photos">
                <input type="checkbox" id="cropper-auto-compress-toggle" value="1" checked>
                <span><i class="fa-solid fa-bolt" style="color:#10B981;"></i> Auto-compress &amp; resize</span>
                <span class="badge-rec">⚡ RECOMMENDED</span>
            </label>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15);" onclick="closeWalkthroughCropper();">
                    Cancel
                </button>
                <button type="button" id="btn-confirm-crop" class="adm-btn-action gold" style="padding: 10px 24px; font-weight: 700;" onclick="confirmWalkthroughCrop();">
                    <i class="fa-solid fa-check"></i>
                    <span>Apply Crop &amp; Upload Photo</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Navigation Menu Management (Strictly Edit-Only CMS) -->
<div id="modal-nav-menu" style="display: none; position: fixed; inset: 0; background: rgba(3, 10, 6, 0.88); z-index: 99990; align-items: center; justify-content: center; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); padding: 16px;">
    <div style="background: linear-gradient(145deg, #0e1e13 0%, #08130c 100%); border: 1.5px solid var(--adm-gold-border); border-radius: 16px; width: 100%; max-width: 1180px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 16px 50px rgba(0,0,0,0.7); overflow: hidden;">
        
        <!-- Modal Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.3); flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon amber" style="width: 42px; height: 42px; font-size: 18px; border-radius: 10px; background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35);">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <h3 style="font-family: var(--adm-font-title); font-size: 16px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">
                            Navigation Menu Configuration
                        </h3>
                        <span class="adm-badge" style="background: rgba(245, 158, 11, 0.15); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35); font-size: 10px; padding: 2px 8px;">
                            <i class="fa-solid fa-lock"></i> Strictly Edit-Only • Fixed 10 Items
                        </span>
                    </div>
                    <p style="font-size: 11.5px; color: var(--adm-text-secondary); margin: 3px 0 0;">
                        Edit labels, links, and icons for the 10 Sanctuary menu items (Add/Delete disabled to safeguard layout)
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeNavMenuModal();" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #FFF; width: 34px; height: 34px; border-radius: 8px; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- System Constraint Notice Banner -->
        <div style="padding: 10px 24px; background: rgba(197, 160, 89, 0.08); border-bottom: 1px solid rgba(197, 160, 89, 0.2); font-size: 11.5px; color: var(--adm-text-secondary); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-info" style="color: var(--adm-gold);"></i>
                <span><strong>Policy:</strong> Menu addition and deletion are permanently disabled. You can edit existing labels, links, FontAwesome icons, gold highlight, and top header visibility.</span>
            </div>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600;">
                <i class="fa-solid fa-eye"></i> Live Mobile Drawer Sync Active
            </span>
        </div>

        <!-- Modal Body (Two-Column Responsive: Form on Left, Live Preview on Right) -->
        <div style="display: flex; flex: 1; overflow-y: auto; padding: 20px 24px; gap: 24px; flex-wrap: wrap;">
            
            <!-- Left Column: 10 Fixed Item Edit Rows -->
            <form id="nav-menu-form" onsubmit="saveNavMenu(event);" style="flex: 1 1 620px; display: flex; flex-direction: column; gap: 12px;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-size: 12px; font-weight: 700; color: #FFFFFF; text-transform: uppercase; letter-spacing: 0.5px;">
                        Menu Items (10 Items Fixed)
                    </span>
                    <span style="font-size: 11px; color: var(--adm-text-muted);">
                        Changes update live preview automatically
                    </span>
                </div>

                <?php foreach ($nav_menu_items as $n_idx => $m_item): 
                    $m_id = (int)$m_item['id'];
                    $m_label = $m_item['label'] ?? '';
                    $m_url = $m_item['url'] ?? '';
                    $m_icon = $m_item['icon'] ?? '';
                    $m_highlight = !empty($m_item['highlight']);
                    $m_desktop = !empty($m_item['show_in_desktop']);
                ?>
                <div class="nav-menu-item-row" data-id="<?php echo $m_id; ?>" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 12px 14px; transition: all 0.2s ease;">
                    <input type="hidden" name="items[<?php echo $m_id; ?>][id]" value="<?php echo $m_id; ?>">
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 24px; height: 24px; border-radius: 6px; background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; font-family: monospace;">
                                #<?php echo $m_id; ?>
                            </span>
                            <span style="font-size: 12px; font-weight: 600; color: #FFF;" id="row-title-badge-<?php echo $m_id; ?>">
                                <?php echo htmlspecialchars($m_label); ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: #E5C378;" title="Display this item with luxury gold accent font">
                                <input type="checkbox" name="items[<?php echo $m_id; ?>][highlight]" id="nav-item-highlight-<?php echo $m_id; ?>" value="1" <?php echo $m_highlight ? 'checked' : ''; ?> onchange="updateMenuLivePreview();">
                                <span><i class="fa-solid fa-star" style="font-size: 10px;"></i> Gold Accent</span>
                            </label>
                            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--adm-text-secondary);" title="Display in top navbar as well as mobile drawer">
                                <input type="checkbox" name="items[<?php echo $m_id; ?>][show_in_desktop]" id="nav-item-desktop-<?php echo $m_id; ?>" value="1" <?php echo $m_desktop ? 'checked' : ''; ?>>
                                <span><i class="fa-solid fa-desktop" style="font-size: 10px;"></i> Desktop Nav</span>
                            </label>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.4fr 1.2fr 1fr; gap: 10px;">
                        <div>
                            <label style="font-size: 10.5px; color: var(--adm-text-muted); display: block; margin-bottom: 3px;">Menu Label / Name</label>
                            <input type="text" name="items[<?php echo $m_id; ?>][label]" id="nav-item-label-<?php echo $m_id; ?>" value="<?php echo htmlspecialchars($m_label); ?>" required class="adm-form-control" style="font-size: 12px; padding: 6px 10px;" oninput="updateMenuLivePreview();">
                        </div>
                        <div>
                            <label style="font-size: 10.5px; color: var(--adm-text-muted); display: block; margin-bottom: 3px;">Link Destination / URL</label>
                            <input type="text" name="items[<?php echo $m_id; ?>][url]" id="nav-item-url-<?php echo $m_id; ?>" value="<?php echo htmlspecialchars($m_url); ?>" required class="adm-form-control" style="font-size: 11.5px; padding: 6px 10px; font-family: monospace;" oninput="updateMenuLivePreview();">
                        </div>
                        <div>
                            <label style="font-size: 10.5px; color: var(--adm-text-muted); display: block; margin-bottom: 3px;">Icon (FontAwesome)</label>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <span id="icon-preview-box-<?php echo $m_id; ?>" style="width: 32px; height: 32px; border-radius: 6px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); display: inline-flex; align-items: center; justify-content: center; color: <?php echo $m_highlight ? '#C5A059' : '#FFF'; ?>; font-size: 12px; flex-shrink: 0;">
                                    <?php if (!empty($m_icon)): ?>
                                        <i class="<?php echo htmlspecialchars($m_icon); ?>"></i>
                                    <?php else: ?>
                                        <span style="opacity: 0.3; font-size: 10px;">—</span>
                                    <?php endif; ?>
                                </span>
                                <input type="text" name="items[<?php echo $m_id; ?>][icon]" id="nav-item-icon-<?php echo $m_id; ?>" value="<?php echo htmlspecialchars($m_icon); ?>" placeholder="e.g. fa-solid fa-key" class="adm-form-control" style="font-size: 11.5px; padding: 6px 8px; font-family: monospace;" oninput="updateMenuLivePreview();">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </form>

            <!-- Right Column: Live Mobile Drawer Mockup Preview (Matching User Screenshot) -->
            <div style="flex: 0 0 320px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; font-weight: 700; color: #FFFFFF; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-mobile-screen"></i> Mobile Drawer Preview
                    </span>
                    <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px; padding: 1px 6px;">
                        Live Interactive
                    </span>
                </div>

                <!-- Phone Frame Container -->
                <div style="background: #040906; border: 2px solid rgba(197, 160, 89, 0.35); border-radius: 24px; padding: 16px 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); position: sticky; top: 0;">
                    <!-- Phone Speaker & Notch -->
                    <div style="display: flex; justify-content: center; margin-bottom: 12px;">
                        <div style="width: 48px; height: 4px; background: rgba(255,255,255,0.2); border-radius: 2px;"></div>
                    </div>

                    <!-- Inner Drawer Canvas (Matches User Screenshot Exact Aesthetic) -->
                    <div id="mobile-menu-live-preview-box" style="background: radial-gradient(ellipse at center, #0B1910 0%, #050E08 100%); border-radius: 14px; padding: 24px 14px; min-height: 480px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 14px; box-shadow: inset 0 0 40px rgba(0,0,0,0.8); border: 1px solid rgba(255,255,255,0.05);">
                        <!-- 10 Live Rendered Items -->
                        <?php foreach ($nav_menu_items as $n_idx => $m_item): 
                            $m_id = (int)$m_item['id'];
                            $m_label = $m_item['label'] ?? '';
                            $m_icon = $m_item['icon'] ?? '';
                            $m_highlight = !empty($m_item['highlight']);
                        ?>
                        <div id="preview-item-<?php echo $m_id; ?>" style="font-family: 'Cinzel', 'Playfair Display', Georgia, serif; font-size: 13.5px; letter-spacing: 0.8px; color: <?php echo $m_highlight ? '#C5A059' : '#DDE5E0'; ?>; transition: color 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 7px; line-height: 1.3;">
                            <?php if (!empty($m_icon)): ?>
                                <i class="<?php echo htmlspecialchars($m_icon); ?>" id="preview-icon-<?php echo $m_id; ?>" style="font-size: 12px; color: <?php echo $m_highlight ? '#C5A059' : '#8FA89B'; ?>;"></i>
                            <?php else: ?>
                                <i id="preview-icon-<?php echo $m_id; ?>" style="display: none; font-size: 12px;"></i>
                            <?php endif; ?>
                            <span id="preview-text-<?php echo $m_id; ?>"><?php echo htmlspecialchars($m_label); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 10px; text-align: center; font-size: 10px; color: var(--adm-text-muted);">
                        Exact 1:1 sanctuary drawer render
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; border-top: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.3); flex-wrap: wrap; gap: 12px;">
            <button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 12px; padding: 8px 16px;" onclick="resetNavMenuDefaults();" title="Reset all 10 items to factory defaults">
                <i class="fa-solid fa-rotate-left"></i> Reset to Defaults
            </button>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15); font-size: 12px; padding: 8px 18px;" onclick="closeNavMenuModal();">
                    Cancel
                </button>
                <button type="button" id="btn-save-nav-menu" class="adm-btn-action gold" style="padding: 8px 24px; font-size: 12px; font-weight: 700;" onclick="document.getElementById('nav-menu-form').requestSubmit();">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Navigation Menu</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- =========================================================================
     MASTER GENERAL SETTINGS & ESTATE CONFIGURATION MODAL (8 INTEGRATED TABS)
     ========================================================================= -->
<div id="modal-general-settings" style="display: none; position: fixed; inset: 0; background: rgba(3, 10, 6, 0.88); z-index: 99995; align-items: center; justify-content: center; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 16px;">
    <div id="modal-general-settings-card" style="background: linear-gradient(145deg, #0e1c12 0%, #07120a 100%); border: 1.5px solid rgba(197, 160, 89, 0.45); border-radius: 18px; width: 100%; max-width: 1100px; max-height: 94vh; display: flex; flex-direction: column; box-shadow: 0 24px 70px rgba(0,0,0,0.85); overflow: hidden; position: relative;">

        <!-- Modal Top Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 26px; border-bottom: 1px solid rgba(197, 160, 89, 0.25); background: rgba(0,0,0,0.35); flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="adm-setting-card-icon gold" style="width: 44px; height: 44px; font-size: 20px; border-radius: 10px; flex-shrink: 0; background: rgba(197, 160, 89, 0.2); border: 1px solid var(--adm-gold);">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <h3 style="font-family: var(--adm-font-title); font-size: 17px; color: #FFFFFF; margin: 0; letter-spacing: 0.8px;">
                            GENERAL SETTINGS &amp; ESTATE CONFIGURATION
                        </h3>
                        <span class="adm-badge" style="background: rgba(197, 160, 89, 0.2); color: #FFF; border: 1px solid var(--adm-gold); font-size: 10px; padding: 2px 8px; font-weight: 700;">
                            MASTER HUB
                        </span>
                    </div>
                    <span style="font-size: 11.5px; color: var(--adm-text-secondary); margin-top: 2px; display: block;">
                        Branding &bull; Multi-Tier GST &bull; Food Ordering &bull; Live Previews &bull; Logout Timings &bull; Admin Security &bull; Guest Hub &bull; Room Audit
                    </span>
                </div>
            </div>
            <button type="button" onclick="closeGeneralSettingsModal();" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #FFF; width: 34px; height: 34px; border-radius: 8px; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;" aria-label="Close dialog"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- 8 Tabs Header Bar -->
        <div style="display: flex; gap: 6px; padding: 10px 20px; background: rgba(0,0,0,0.45); border-bottom: 1px solid rgba(255,255,255,0.08); overflow-x: auto; flex-shrink: 0; scrollbar-width: thin;" id="general-settings-tabs-bar">
            <button type="button" class="adm-gen-tab-btn active" data-tab="branding" onclick="switchGeneralTab('branding');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid var(--adm-gold); background: rgba(197, 160, 89, 0.22); color: #FFF; white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-compass-drafting" style="color: var(--adm-gold);"></i> Branding &amp; Logo
            </button>
            <button type="button" class="adm-gen-tab-btn" data-tab="gst" onclick="switchGeneralTab('gst');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--adm-text-secondary); white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-receipt" style="color: #38BDF8;"></i> Multi-Tier GST
            </button>
            <button type="button" class="adm-gen-tab-btn" data-tab="food" onclick="switchGeneralTab('food');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--adm-text-secondary); white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-utensils" style="color: #2ECC71;"></i> Food Ordering
            </button>
            <button type="button" class="adm-gen-tab-btn" data-tab="preview" onclick="switchGeneralTab('preview');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--adm-text-secondary); white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-display" style="color: #A855F7;"></i> Live Preview
            </button>
            <button type="button" class="adm-gen-tab-btn" data-tab="sessions" onclick="switchGeneralTab('sessions');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--adm-text-secondary); white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-clock" style="color: #F59E0B;"></i> Session Timings
            </button>
            <button type="button" class="adm-gen-tab-btn" data-tab="security" onclick="switchGeneralTab('security');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--adm-text-secondary); white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-user-shield" style="color: #EC4899;"></i> Admin Security
            </button>
            <button type="button" class="adm-gen-tab-btn" data-tab="users" onclick="switchGeneralTab('users');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--adm-text-secondary); white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-users" style="color: #06B6D4;"></i> User Profiles Hub
                <span class="adm-badge" style="background: rgba(56, 189, 248, 0.2); color: #38BDF8; font-size: 10px; padding: 1px 6px; border-radius: 10px;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo count($all_registered_users); ?>
                </span>
            </button>
            <button type="button" class="adm-gen-tab-btn" data-tab="audit" onclick="switchGeneralTab('audit');" style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--adm-text-secondary); white-space: nowrap; transition: all 0.2s;">
                <i class="fa-solid fa-clipboard-check" style="color: #10B981;"></i> Room Audit Checklist
            </button>
        </div>

        <!-- Scrollable Modal Content Container -->
        <div style="flex: 1; overflow-y: auto; padding: 24px 28px; scrollbar-width: thin;" id="general-settings-content-area">

            <!-- ========================================================= -->
            <!-- TAB 1: RESORT BRANDING & LOGO                             -->
            <!-- ========================================================= -->
            <div id="gen-pane-branding" class="adm-gen-tab-pane" style="display: block;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Resort Identity, Logo &amp; Stay Policy</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">Updating the logo or name dynamically propagates everywhere: header, footer, guest portal, receipts, invoices &amp; admin sidebar.</p>
                    </div>
                    <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 11px;">
                        <i class="fa-solid fa-globe"></i> Universal Dynamic Sync
                    </span>
                </div>

                <form id="form-general-branding" onsubmit="saveGeneralBranding(event);" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_resort_branding">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="remove_logo" id="gen-branding-remove-logo" value="0">

                    <!-- Logo Upload & Live Preview Card -->
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 22px;">
                        <label style="font-size: 12.5px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 12px;">
                            <i class="fa-solid fa-image"></i> Resort Brand Logo (Header, Footer, Receipts &amp; Portals)
                        </label>
                        <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                            <!-- Logo Preview Box -->
                            <div id="gen-logo-preview-box" style="width: 130px; height: 130px; border-radius: 12px; background: rgba(5, 14, 9, 0.85); border: 2px dashed rgba(197, 160, 89, 0.45); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 8px; position: relative; box-shadow: inset 0 0 20px rgba(0,0,0,0.6);">
                                <?php if (!empty($site_logo)): ?>
                                    <img id="gen-logo-preview-img" src="../<?php echo htmlspecialchars($site_logo); ?>" alt="Resort Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <div id="gen-logo-placeholder" style="text-align: center; color: var(--adm-text-muted);">
                                        <i class="fa-solid fa-mountain-sun" style="font-size: 32px; color: var(--adm-gold); opacity: 0.6; margin-bottom: 6px; display: block;"></i>
                                        <span style="font-size: 10px; letter-spacing: 0.5px;">NO LOGO UPLOADED</span>
                                    </div>
                                    <img id="gen-logo-preview-img" src="" alt="Resort Logo" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                                <?php endif; ?>
                            </div>

                            <div style="flex: 1; min-width: 250px;">
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px; flex-wrap: wrap;">
                                    <label class="adm-btn-action gold" style="cursor: pointer; padding: 8px 16px; font-size: 12px; margin: 0;">
                                        <i class="fa-solid fa-cloud-arrow-up"></i> Choose New Logo Image
                                        <input type="file" name="site_logo_file" id="gen-logo-file-input" accept="image/*" style="display: none;" onchange="previewGeneralLogo(this);">
                                    </label>
                                    <button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); font-size: 12px; padding: 8px 14px;" onclick="removeGeneralLogo();">
                                        <i class="fa-solid fa-trash-can"></i> Remove Logo
                                    </button>
                                </div>
                                <p style="font-size: 11.5px; color: var(--adm-text-muted); margin: 0; line-height: 1.5;">
                                    Recommended format: PNG or SVG with transparent background, or crisp WebP. Max 1200px width. If removed, the resort name typography is rendered in gold serif font automatically.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Estate Identity Fields Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 22px;">
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                                Official Resort / Estate Name:
                            </label>
                            <input type="text" name="site_name" id="gen-site-name" class="adm-form-control" value="<?php echo htmlspecialchars($site_name); ?>" required style="font-size: 13px; font-weight: 700;">
                        </div>
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                                Resort Tagline / Subtitle:
                            </label>
                            <input type="text" name="site_tagline" id="gen-site-tagline" class="adm-form-control" value="<?php echo htmlspecialchars($site_tagline); ?>" required style="font-size: 13px;">
                        </div>
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                                Currency Symbol (Invoices &amp; Tariffs):
                            </label>
                            <input type="text" name="currency_symbol" id="gen-currency-symbol" class="adm-form-control" value="<?php echo htmlspecialchars($currency_symbol); ?>" placeholder="e.g. ₹" style="font-size: 13px; font-weight: 700;">
                        </div>
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                                Standard Check-In Time:
                            </label>
                            <input type="text" name="checkin_time" id="gen-checkin-time" class="adm-form-control" value="<?php echo htmlspecialchars($checkin_time); ?>" placeholder="e.g. 02:00 PM" style="font-size: 13px;">
                        </div>
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                                Standard Check-Out Time:
                            </label>
                            <input type="text" name="checkout_time" id="gen-checkout-time" class="adm-form-control" value="<?php echo htmlspecialchars($checkout_time); ?>" placeholder="e.g. 11:00 AM" style="font-size: 13px;">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" id="btn-save-gen-branding" class="adm-btn-action gold" style="padding: 10px 26px; font-size: 13px; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Resort Branding &amp; Policy
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: MULTI-TIER GST & TAXATION                          -->
            <!-- ========================================================= -->
            <div id="gen-pane-gst" class="adm-gen-tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Multi-Tier Goods &amp; Services Tax (GST)</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">Under Indian GST regulations, luxury eco-resorts require distinct slabs for Dining (5%), Villa Accommodation (12%), and Ancillary Services (18%).</p>
                    </div>
                    <span class="adm-badge" style="background: rgba(56, 189, 248, 0.15); color: #38BDF8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 11px;">
                        <i class="fa-solid fa-landmark"></i> Tax Slabs Compliant
                    </span>
                </div>

                <form id="form-general-gst" onsubmit="saveGeneralGst(event);">
                    <input type="hidden" name="action" value="update_gst_settings">
                    <input type="hidden" name="ajax" value="1">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 22px;">
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 12px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="font-size: 12px; font-weight: 700; color: #F59E0B;">
                                    <i class="fa-solid fa-utensils"></i> Food &amp; Dining GST Rate (%)
                                </label>
                                <span class="adm-badge" style="background: rgba(245, 158, 11, 0.2); color: #F59E0B; font-size: 10px;">Standard: 5%</span>
                            </div>
                            <input type="number" step="0.01" min="0" max="100" name="gst_rate_food" id="gen-gst-rate-food" value="<?php echo htmlspecialchars($gst_rate_food); ?>" required class="adm-form-control" style="font-size: 15px; font-weight: 700; color: #F59E0B;" oninput="updateGenGstSimulator();">
                            <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 5px; display: block;">Applies to in-cottage dining &amp; restaurant orders.</span>
                        </div>

                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(56, 189, 248, 0.35); border-radius: 12px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="font-size: 12px; font-weight: 700; color: #38BDF8;">
                                    <i class="fa-solid fa-bed"></i> Cottage / Room GST Rate (%)
                                </label>
                                <span class="adm-badge" style="background: rgba(56, 189, 248, 0.2); color: #38BDF8; font-size: 10px;">Standard: 12%</span>
                            </div>
                            <input type="number" step="0.01" min="0" max="100" name="gst_rate_cottage" id="gen-gst-rate-cottage" value="<?php echo htmlspecialchars($gst_rate_cottage); ?>" required class="adm-form-control" style="font-size: 15px; font-weight: 700; color: #38BDF8;" oninput="updateGenGstSimulator();">
                            <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 5px; display: block;">Applies to villa night stay reservations &amp; room tariffs.</span>
                        </div>

                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(192, 132, 252, 0.35); border-radius: 12px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="font-size: 12px; font-weight: 700; color: #C084FC;">
                                    <i class="fa-solid fa-spa"></i> Other Services / Activities GST (%)
                                </label>
                                <span class="adm-badge" style="background: rgba(192, 132, 252, 0.2); color: #C084FC; font-size: 10px;">Standard: 18%</span>
                            </div>
                            <input type="number" step="0.01" min="0" max="100" name="gst_rate_other" id="gen-gst-rate-other" value="<?php echo htmlspecialchars($gst_rate_other); ?>" required class="adm-form-control" style="font-size: 15px; font-weight: 700; color: #C084FC;" oninput="updateGenGstSimulator();">
                            <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 5px; display: block;">Applies to safari, plantation tours, BBQ &amp; damages.</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 16px; margin-bottom: 22px;">
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                                Official GSTIN (Tax Identification Number):
                            </label>
                            <input type="text" name="gst_number" id="gen-gst-number" value="<?php echo htmlspecialchars($gst_number); ?>" placeholder="e.g. 32AABCU9603R1ZX" class="adm-form-control" style="font-size: 13px; font-family: monospace; letter-spacing: 1px; text-transform: uppercase;">
                        </div>
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">
                                Legal Registered Entity / Business Name:
                            </label>
                            <input type="text" name="gst_legal_name" id="gen-gst-legal-name" value="<?php echo htmlspecialchars($gst_legal_name); ?>" placeholder="e.g. FOOD FOREST SANCTUARY RESORTS PRIVATE LIMITED" class="adm-form-control" style="font-size: 13px;">
                        </div>
                    </div>

                    <!-- Live Tax Calculation Simulator -->
                    <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; margin-bottom: 22px;">
                        <h5 style="color: var(--adm-gold); font-size: 12px; text-transform: uppercase; margin: 0 0 12px; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-calculator"></i> Real-Time Invoice Tax Computation Simulator (Example Folio)
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 12px;">
                            <div style="background: rgba(255,255,255,0.03); padding: 10px 14px; border-radius: 8px;">
                                <div style="color: var(--adm-text-muted);">Cottage Stay (₹10,000)</div>
                                <div style="font-size: 14px; font-weight: 700; color: #38BDF8; margin-top: 3px;" id="sim-tax-cottage">₹1,200.00 (12%)</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.03); padding: 10px 14px; border-radius: 8px;">
                                <div style="color: var(--adm-text-muted);">Food Orders (₹2,500)</div>
                                <div style="font-size: 14px; font-weight: 700; color: #F59E0B; margin-top: 3px;" id="sim-tax-food">₹125.00 (5%)</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.03); padding: 10px 14px; border-radius: 8px;">
                                <div style="color: var(--adm-text-muted);">Safari / Other (₹1,000)</div>
                                <div style="font-size: 14px; font-weight: 700; color: #C084FC; margin-top: 3px;" id="sim-tax-other">₹180.00 (18%)</div>
                            </div>
                            <div style="background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.3); padding: 10px 14px; border-radius: 8px;">
                                <div style="color: #2ECC71;">Total Tax on Folio</div>
                                <div style="font-size: 15px; font-weight: 800; color: #2ECC71; margin-top: 3px;" id="sim-tax-total">₹1,505.00</div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" id="btn-save-gen-gst" class="adm-btn-action gold" style="padding: 10px 26px; font-size: 13px; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> Save GST Configuration
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: GUEST FOOD ORDERING OPERATIONS                      -->
            <!-- ========================================================= -->
            <div id="gen-pane-food" class="adm-gen-tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Guest In-Cottage Food Ordering</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">Control whether residents staying in villas can browse the menu and order dishes from their mobile guest portal.</p>
                    </div>
                </div>

                <!-- Hero Toggle Card -->
                <div id="gen-food-toggle-card" style="background: rgba(0,0,0,0.35); border: 1.5px solid <?php echo $food_ordering_enabled ? 'rgba(46, 204, 113, 0.45)' : 'rgba(239, 68, 68, 0.45)'; ?>; border-radius: 14px; padding: 24px; margin-bottom: 22px; transition: all 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px;">
                        <div style="display: flex; align-items: center; gap: 18px;">
                            <div class="adm-setting-card-icon <?php echo $food_ordering_enabled ? 'emerald' : 'rose'; ?>" id="gen-food-ordering-icon" style="width: 52px; height: 52px; font-size: 22px; border-radius: 12px; flex-shrink: 0;">
                                <i class="fa-solid <?php echo $food_ordering_enabled ? 'fa-utensils' : 'fa-ban'; ?>"></i>
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                                    <h4 style="font-size: 17px; color: #FFF; margin: 0; font-weight: 700;">Resident Ordering Status</h4>
                                    <span id="gen-food-ordering-badge" class="adm-badge" style="<?php echo $food_ordering_enabled ? 'background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4);' : 'background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);'; ?> font-size: 11px; padding: 2px 10px; font-weight: 700;">
                                        <?php echo $food_ordering_enabled ? '<i class="fa-solid fa-circle-check"></i> ACTIVE & ACCEPTING ORDERS' : '<i class="fa-solid fa-circle-pause"></i> DISABLED / MENU HIDDEN'; ?>
                                    </span>
                                </div>
                                <p style="font-size: 12.5px; color: var(--adm-text-secondary); margin: 0;" id="gen-food-ordering-desc">
                                    <?php echo $food_ordering_enabled ? 'In-house guests can place room service meal orders directly from the guest portal.' : 'Food ordering is currently paused. Guests cannot submit new food orders.'; ?>
                                </p>
                            </div>
                        </div>

                        <div>
                            <button type="button" id="btn-toggle-gen-food" class="adm-btn-action <?php echo $food_ordering_enabled ? 'rose' : 'emerald'; ?>" style="padding: 10px 22px; font-size: 13px; font-weight: 700;" onclick="toggleFoodOrderingStatus('<?php echo $food_ordering_enabled ? '0' : '1'; ?>');">
                                <i class="fa-solid <?php echo $food_ordering_enabled ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                <span id="gen-food-btn-label"><?php echo $food_ordering_enabled ? 'Disable Food Ordering' : 'Enable Food Ordering'; ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Shortcuts to Operations -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                    <a href="edit_section.php?section=menu" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 18px; text-decoration: none; display: flex; align-items: center; gap: 14px; transition: border-color 0.2s;" onmouseover="this.style.borderColor='var(--adm-gold)';" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';">
                        <div class="adm-setting-card-icon gold" style="width: 42px; height: 42px; font-size: 18px; border-radius: 8px; flex-shrink: 0;"><i class="fa-solid fa-book-open"></i></div>
                        <div>
                            <h5 style="color: #FFF; margin: 0; font-size: 14px;">Menu CMS Editor</h5>
                            <span style="font-size: 11.5px; color: var(--adm-text-muted);">Edit categories, prices, ingredients &amp; photos</span>
                        </div>
                    </a>

                    <a href="../kitchen.php" target="_blank" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 18px; text-decoration: none; display: flex; align-items: center; gap: 14px; transition: border-color 0.2s;" onmouseover="this.style.borderColor='#38BDF8';" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';">
                        <div class="adm-setting-card-icon cyan" style="width: 42px; height: 42px; font-size: 18px; border-radius: 8px; flex-shrink: 0;"><i class="fa-solid fa-kitchen-set"></i></div>
                        <div>
                            <h5 style="color: #FFF; margin: 0; font-size: 14px;">Live Kitchen Screen (KDS)</h5>
                            <span style="font-size: 11.5px; color: var(--adm-text-muted);">Live order ticket stream with chimes &amp; status updates</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 4: LIVE PREVIEW CONTROLS                              -->
            <!-- ========================================================= -->
            <div id="gen-pane-preview" class="adm-gen-tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Split-Screen Live Preview Controls</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">Enable or disable the real-time split-screen preview globally, or toggle individual cards to give form editors full width.</p>
                    </div>
                </div>

                <form id="form-general-preview" onsubmit="saveGeneralPreview(event);">
                    <input type="hidden" name="action" value="save_live_preview_settings">
                    <input type="hidden" name="ajax" value="1">

                    <!-- Global Switch Card -->
                    <div style="background: rgba(0,0,0,0.3); border: 1.5px solid rgba(168, 85, 247, 0.4); border-radius: 12px; padding: 20px; margin-bottom: 22px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                            <div>
                                <span class="adm-badge" style="background: rgba(168, 85, 247, 0.2); color: #C084FC; font-size: 10px; font-weight: 700; margin-bottom: 6px; display: inline-block;">
                                    MASTER SWITCH
                                </span>
                                <h4 style="color: #FFF; margin: 0; font-size: 15px;">Global Live Preview Engine</h4>
                                <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 3px 0 0;">If toggled OFF, live preview is suppressed globally across all 18 section editors for maximum editing screen real-estate.</p>
                            </div>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: #FFF;">
                                <input type="checkbox" name="live_preview_enabled" id="gen-preview-global-toggle" value="1" <?php echo $live_preview_enabled ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: #A855F7;">
                                <span>Enable Live Preview Globally</span>
                            </label>
                        </div>
                    </div>

                    <!-- Per-Card Grid Toggles -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase;">
                            <i class="fa-solid fa-list-check"></i> Individual Card Live Preview Toggles
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="setAllCardPreviews(true);">Select All</button>
                            <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="setAllCardPreviews(false);">Deselect All</button>
                        </div>
                    </div>

                    <?php
                    $section_preview_cards = [
                        'climate' => ['num' => '01', 'name' => 'Climate & Accolades'],
                        'hero' => ['num' => '02', 'name' => 'Hero Marquee & Visual'],
                        'philosophy' => ['num' => '03', 'name' => 'Sanctuary Philosophy'],
                        'rooms' => ['num' => '04', 'name' => 'Villas & Cottages'],
                        'experiences' => ['num' => '05', 'name' => 'Curated Experiences'],
                        'menu' => ['num' => '06', 'name' => 'Food Menu & Dining'],
                        'why' => ['num' => '07', 'name' => 'Why Food Forest?'],
                        'sanctuary_map' => ['num' => '08', 'name' => 'Sanctuary Estate Map'],
                        'seasons' => ['num' => '09', 'name' => 'Seasons of Kanthalloor'],
                        'gallery' => ['num' => '10', 'name' => 'Visual Diary (Gallery)'],
                        'testimonials' => ['num' => '11', 'name' => 'Guest Reflections'],
                        'whatsapp' => ['num' => '12', 'name' => 'WhatsApp & Concierge'],
                        'protection' => ['num' => '13', 'name' => 'Content Protection'],
                        'bank' => ['num' => '14', 'name' => 'Bank Details & UPI QR'],
                        'footer' => ['num' => '15', 'name' => 'Footer & Eco Pillars']
                    ];
                    ?>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px; margin-bottom: 22px;">
                        <?php foreach ($section_preview_cards as $c_key => $c_info):
                            $c_active = ($live_preview_cards_config[$c_key] ?? '1') !== '0';
                        ?>
                        <label style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.04)';" onmouseout="this.style.background='rgba(0,0,0,0.25)';">
                            <span style="font-size: 12px; color: #FFF;">
                                <strong style="color: var(--adm-gold); font-size: 10.5px;"><?php echo $c_info['num']; ?></strong> &bull; <?php echo $c_info['name']; ?>
                            </span>
                            <input type="checkbox" name="cards_config[<?php echo $c_key; ?>]" class="gen-card-pv-check" value="1" <?php echo $c_active ? 'checked' : ''; ?> style="accent-color: #10B981; width: 16px; height: 16px;">
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" id="btn-save-gen-preview" class="adm-btn-action gold" style="padding: 10px 26px; font-size: 13px; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Live Preview Configuration
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 5: SESSION LOGOUT TIMINGS                             -->
            <!-- ========================================================= -->
            <div id="gen-pane-sessions" class="adm-gen-tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Security Session Inactivity Auto-Logout</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">Specify how long idle sessions can remain active before requiring the administrator or guest to sign in again.</p>
                    </div>
                    <span class="adm-badge" style="background: rgba(245, 158, 11, 0.15); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 11px;">
                        <i class="fa-solid fa-hourglass-half"></i> Inactivity Guard
                    </span>
                </div>

                <form id="form-general-sessions" onsubmit="saveGeneralSessions(event);">
                    <input type="hidden" name="action" value="save_session_timings">
                    <input type="hidden" name="ajax" value="1">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
                        <!-- Admin Inactivity -->
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 12px; padding: 20px;">
                            <label style="font-size: 13px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 8px;">
                                <i class="fa-solid fa-user-shield"></i> Admin Control Panel Auto-Logout (Minutes)
                            </label>
                            <input type="number" name="admin_session_timeout_minutes" id="gen-admin-timeout" min="5" max="1440" value="<?php echo $admin_session_timeout_minutes; ?>" required class="adm-form-control" style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-admin-timeout').value=15;">15 Mins (Default)</button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-admin-timeout').value=30;">30 Mins</button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-admin-timeout').value=60;">1 Hour</button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-admin-timeout').value=120;">2 Hours</button>
                            </div>
                            <span style="font-size: 11.5px; color: var(--adm-text-muted);">Terminates idle administrative sessions to prevent unauthorized folio access on unattended terminals.</span>
                        </div>

                        <!-- Client Inactivity -->
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(56, 189, 248, 0.35); border-radius: 12px; padding: 20px;">
                            <label style="font-size: 13px; font-weight: 700; color: #38BDF8; display: block; margin-bottom: 8px;">
                                <i class="fa-solid fa-mobile-screen"></i> Client / Guest Portal Auto-Logout (Minutes)
                            </label>
                            <input type="number" name="client_session_timeout_minutes" id="gen-client-timeout" min="5" max="1440" value="<?php echo $client_session_timeout_minutes; ?>" required class="adm-form-control" style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: #38BDF8;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-client-timeout').value=15;">15 Mins (Default)</button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-client-timeout').value=30;">30 Mins</button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-client-timeout').value=60;">1 Hour</button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('gen-client-timeout').value=180;">3 Hours</button>
                            </div>
                            <span style="font-size: 11.5px; color: var(--adm-text-muted);">Protects client reservation records and dining folio when guests access their portal on shared devices.</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" id="btn-save-gen-sessions" class="adm-btn-action gold" style="padding: 10px 26px; font-size: 13px; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Inactivity Timings
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 6: ADMIN SECURITY & PROFILE                           -->
            <!-- ========================================================= -->
            <div id="gen-pane-security" class="adm-gen-tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Administrator Profile &amp; Password</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">Update current administrator credentials, change login password and upload your personal profile picture.</p>
                    </div>
                    <span class="adm-badge" style="background: rgba(236, 72, 153, 0.15); color: #EC4899; border: 1px solid rgba(236, 72, 153, 0.3); font-size: 11px;">
                        <i class="fa-solid fa-shield-halved"></i> Active Admin: <?php echo htmlspecialchars($current_admin_user['username'] ?? 'admin'); ?>
                    </span>
                </div>

                <form id="form-general-admin-security" onsubmit="saveGeneralAdminSecurity(event);" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_admin_security">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="remove_avatar" id="gen-admin-remove-avatar" value="0">

                    <!-- Admin Avatar Upload -->
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                            <div id="gen-admin-avatar-box" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid var(--adm-gold); overflow: hidden; background: #07150C; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 14px rgba(0,0,0,0.5);">
                                <?php if (!empty($current_admin_user['avatar_url'])): ?>
                                    <img id="gen-admin-avatar-img" src="../<?php echo htmlspecialchars($current_admin_user['avatar_url']); ?>" alt="Admin Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div id="gen-admin-avatar-placeholder" style="font-size: 24px; color: var(--adm-gold); font-weight: 700;">
                                        <?php echo strtoupper(substr($current_admin_user['full_name'] ?? 'A', 0, 1)); ?>
                                    </div>
                                    <img id="gen-admin-avatar-img" src="" alt="Admin Avatar" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <?php endif; ?>
                            </div>

                            <div style="flex: 1; min-width: 240px;">
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 6px; flex-wrap: wrap;">
                                    <label class="adm-btn-action gold" style="cursor: pointer; padding: 7px 14px; font-size: 11.5px; margin: 0;">
                                        <i class="fa-solid fa-camera"></i> Upload Profile Picture
                                        <input type="file" name="avatar_file" id="gen-admin-avatar-file" accept="image/*" style="display: none;" onchange="previewGeneralAdminAvatar(this);">
                                    </label>
                                    <button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); font-size: 11.5px; padding: 7px 12px;" onclick="removeGeneralAdminAvatar();">
                                        <i class="fa-solid fa-trash-can"></i> Remove
                                    </button>
                                </div>
                                <span style="font-size: 11px; color: var(--adm-text-muted);">Displays on top-right admin header and concierge activity feeds.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Info -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">Full Display Name:</label>
                            <input type="text" name="full_name" id="gen-admin-fullname" value="<?php echo htmlspecialchars($current_admin_user['full_name'] ?? 'Administrator'); ?>" required class="adm-form-control" style="font-size: 12.5px;">
                        </div>
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">Username:</label>
                            <input type="text" name="username" id="gen-admin-username" value="<?php echo htmlspecialchars($current_admin_user['username'] ?? 'admin'); ?>" required class="adm-form-control" style="font-size: 12.5px;">
                        </div>
                        <div>
                            <label class="adm-form-label" style="font-size: 11.5px; color: #CBD5E1; margin-bottom: 5px; display: block;">Email Address:</label>
                            <input type="email" name="email" id="gen-admin-email" value="<?php echo htmlspecialchars($current_admin_user['email'] ?? 'admin@foodforest.com'); ?>" required class="adm-form-control" style="font-size: 12.5px;">
                        </div>
                    </div>

                    <!-- Password Change Box -->
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; margin-bottom: 22px;">
                        <h5 style="color: var(--adm-gold); font-size: 12px; text-transform: uppercase; margin: 0 0 12px; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-key"></i> Change Password (Leave blank to keep current)
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                            <div>
                                <label class="adm-form-label" style="font-size: 11px; color: var(--adm-text-muted); margin-bottom: 4px; display: block;">Current Password:</label>
                                <input type="password" name="current_password" id="gen-admin-curr-pwd" placeholder="Enter current password" class="adm-form-control" style="font-size: 12px;">
                            </div>
                            <div>
                                <label class="adm-form-label" style="font-size: 11px; color: var(--adm-text-muted); margin-bottom: 4px; display: block;">New Password:</label>
                                <input type="password" name="new_password" id="gen-admin-new-pwd" placeholder="Min 4 characters" class="adm-form-control" style="font-size: 12px;">
                            </div>
                            <div>
                                <label class="adm-form-label" style="font-size: 11px; color: var(--adm-text-muted); margin-bottom: 4px; display: block;">Confirm New Password:</label>
                                <input type="password" name="confirm_password" id="gen-admin-conf-pwd" placeholder="Repeat new password" class="adm-form-control" style="font-size: 12px;">
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" id="btn-save-gen-security" class="adm-btn-action gold" style="padding: 10px 26px; font-size: 13px; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> Update Admin Profile &amp; Security
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 7: USER PROFILES & BLUE TICK HUB                      -->
            <!-- ========================================================= -->
            <div id="gen-pane-users" class="adm-gen-tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Guest Accounts &amp; Concierge Verification Hub</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">Manage registered clients, grant Blue Tick verification badges, write VIP concierge notes, reset passwords, or enable/disable accounts.</p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="adm-badge" style="background: rgba(56, 189, 248, 0.15); color: #38BDF8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 11px;">
                            <i class="fa-solid fa-circle-check"></i> <span id="gen-verified-users-count"><?php echo count(array_filter($all_registered_users, function($u) { return !empty($u['is_verified']); })); ?></span> Verified
                        </span>
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 11px;">
                            <i class="fa-solid fa-users"></i> <span id="gen-total-users-count"><?php echo count($all_registered_users); ?></span> Accounts
                        </span>
                    </div>
                </div>

                <!-- Real-Time User Search Toolbar -->
                <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <div style="position: relative; flex: 1; min-width: 260px;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--adm-gold); font-size: 13px;"></i>
                        <input type="text" id="gen-user-search-input" placeholder="Search by mobile number, guest name, or email address..." oninput="filterGeneralUsersList(this.value);" style="background: rgba(0,0,0,0.4); border: 1px solid var(--adm-gold-border); border-radius: 20px; padding: 9px 16px 9px 38px; font-size: 13px; color: #FFF; width: 100%; outline: none;">
                    </div>
                    <span style="font-size: 11.5px; color: var(--adm-text-muted);">
                        <i class="fa-solid fa-phone" style="color: var(--adm-gold); margin-right: 4px;"></i> Search live by phone number
                    </span>
                </div>

                <!-- Users Cards List Container -->
                <div id="gen-users-cards-container" style="display: flex; flex-direction: column; gap: 14px;">
                    <?php if (empty($all_registered_users)): ?>
                        <div style="text-align: center; padding: 40px 20px; color: var(--adm-text-muted);">
                            <i class="fa-solid fa-users-slash" style="font-size: 36px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            No registered client profiles found in database yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_registered_users as $u):
                            $u_id = (int)$u['id'];
                            $u_name = $u['full_name'] ?? 'Guest';
                            $u_phone = $u['phone'] ?? '';
                            $u_email = $u['email'] ?? '';
                            $u_verified = (!empty($u['is_verified']) && $u['is_verified'] == 1);
                            $u_status = (!isset($u['status']) || $u['status'] == 1);
                            $u_notes = $u['admin_notes'] ?? '';
                            $u_created = !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : '—';
                        ?>
                        <div class="gen-user-item-card" id="gen-user-card-<?php echo $u_id; ?>" data-search="<?php echo htmlspecialchars(strtolower($u_name . ' ' . $u_phone . ' ' . $u_email)); ?>" style="background: rgba(0,0,0,0.3); border: 1.5px solid <?php echo $u_verified ? 'rgba(56, 189, 248, 0.45)' : 'rgba(255,255,255,0.08)'; ?>; border-radius: 14px; padding: 18px 20px; transition: border-color 0.2s;">
                            <!-- Row 1: Profile Details & Actions -->
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 14px;">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <!-- Avatar -->
                                    <div style="width: 46px; height: 46px; border-radius: 50%; background: #081a0e; border: 1.5px solid <?php echo $u_verified ? '#38BDF8' : 'var(--adm-gold)'; ?>; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; color: #FFF; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.4);">
                                        <?php if (!empty($u['avatar_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($u['avatar_url']); ?>" alt="<?php echo htmlspecialchars($u_name); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($u_name, 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <span style="font-size: 14.5px; font-weight: 700; color: #FFFFFF;" id="gen-user-name-<?php echo $u_id; ?>">
                                                <?php echo htmlspecialchars($u_name); ?>
                                            </span>

                                            <!-- Glowing Blue Tick Badge -->
                                            <span id="gen-user-badge-verified-<?php echo $u_id; ?>" class="adm-badge" style="<?php echo $u_verified ? 'background: rgba(56, 189, 248, 0.2); color: #38BDF8; border: 1px solid rgba(56, 189, 248, 0.45); box-shadow: 0 0 10px rgba(56, 189, 248, 0.35);' : 'display: none;'; ?> font-size: 10px; padding: 2px 8px; font-weight: 700; border-radius: 10px;">
                                                <i class="fa-solid fa-circle-check"></i> Verified Guest
                                            </span>

                                            <!-- Status Badge -->
                                            <span id="gen-user-badge-status-<?php echo $u_id; ?>" class="adm-badge" style="<?php echo $u_status ? 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);' : 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);'; ?> font-size: 10px; padding: 2px 7px;">
                                                <?php echo $u_status ? 'Active Account' : 'Account Disabled'; ?>
                                            </span>
                                        </div>

                                        <div style="display: flex; align-items: center; gap: 14px; font-size: 11.5px; color: var(--adm-text-secondary); margin-top: 4px; flex-wrap: wrap;">
                                            <span style="color: var(--adm-gold); font-weight: 600;"><i class="fa-solid fa-phone"></i> <span id="gen-user-phone-<?php echo $u_id; ?>"><?php echo htmlspecialchars($u_phone ?: 'No Phone'); ?></span></span>
                                            <span><i class="fa-solid fa-envelope"></i> <span id="gen-user-email-<?php echo $u_id; ?>"><?php echo htmlspecialchars($u_email ?: 'No Email'); ?></span></span>
                                            <span style="color: var(--adm-text-muted);"><i class="fa-solid fa-calendar"></i> Joined <?php echo $u_created; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons Toolbar -->
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <!-- Toggle Verification Blue Tick -->
                                    <button type="button" class="adm-btn-action" id="gen-btn-verify-<?php echo $u_id; ?>" style="<?php echo $u_verified ? 'background: rgba(56, 189, 248, 0.2); color: #38BDF8; border: 1px solid rgba(56, 189, 248, 0.4);' : 'background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15);'; ?> font-size: 11.5px; padding: 6px 12px;" onclick="toggleUserVerification(<?php echo $u_id; ?>);" title="Toggle Blue Tick Verification Badge">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span><?php echo $u_verified ? 'Verified (Blue Tick)' : 'Verify Profile'; ?></span>
                                    </button>

                                    <!-- Toggle Enable/Disable -->
                                    <button type="button" class="adm-btn-action" id="gen-btn-status-<?php echo $u_id; ?>" style="<?php echo $u_status ? 'background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);' : 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);'; ?> font-size: 11.5px; padding: 6px 12px;" onclick="toggleUserStatus(<?php echo $u_id; ?>);" title="Enable or disable user account access">
                                        <i class="fa-solid <?php echo $u_status ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                        <span><?php echo $u_status ? 'Disable' : 'Enable'; ?></span>
                                    </button>

                                    <!-- Reset Password -->
                                    <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15); font-size: 11.5px; padding: 6px 11px;" onclick="promptResetUserPassword(<?php echo $u_id; ?>, '<?php echo addslashes($u_name); ?>');" title="Reset this user's password">
                                        <i class="fa-solid fa-key"></i> Reset Pwd
                                    </button>

                                    <!-- Edit User Details -->
                                    <button type="button" class="adm-btn-action" style="background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15); font-size: 11.5px; padding: 6px 11px;" onclick="promptEditUserProfile(<?php echo $u_id; ?>, '<?php echo addslashes($u_name); ?>', '<?php echo addslashes($u_email); ?>', '<?php echo addslashes($u_phone); ?>');" title="Edit name, phone and email">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>

                                    <!-- Delete User -->
                                    <button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); font-size: 11.5px; padding: 6px 10px;" onclick="confirmDeleteUserProfile(<?php echo $u_id; ?>, '<?php echo addslashes($u_name); ?>');" title="Delete account permanently">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Row 2: Concierge Feedback Notes & Preferences -->
                            <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); border-radius: 10px; padding: 12px 14px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <label style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-clipboard-user"></i> Internal Guest Feedback &amp; Concierge Notes:
                                    </label>
                                    <span style="font-size: 10.5px; color: var(--adm-text-muted);">
                                        <i class="fa-solid fa-bell" style="color: #38BDF8; margin-right: 3px;"></i> Automatically highlighted on their next booking!
                                    </span>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: flex-start;">
                                    <textarea id="gen-user-notes-<?php echo $u_id; ?>" rows="2" placeholder="e.g. VIP guest, anniversary stay, prefers treehouse balcony with sunrise view, strict vegan diet, likes extra quilts..." class="adm-form-control" style="font-size: 12px; line-height: 1.4; resize: vertical;"><?php echo htmlspecialchars($u_notes); ?></textarea>
                                    <button type="button" class="adm-btn-action gold" style="padding: 8px 14px; font-size: 11.5px; font-weight: 700; flex-shrink: 0;" onclick="saveUserFeedbackNote(<?php echo $u_id; ?>, this);">
                                        <i class="fa-solid fa-floppy-disk"></i> Save Note
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 8: ROOM AUDIT CHECKLIST                               -->
            <!-- ========================================================= -->
            <div id="gen-pane-audit" class="adm-gen-tab-pane" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h4 style="color: #FFF; font-size: 16px; margin: 0; font-family: var(--adm-font-title);">Room Inspection &amp; Check-Out Audit Checklist</h4>
                        <p style="color: var(--adm-text-secondary); font-size: 12.5px; margin: 3px 0 0;">These items are inspected by housekeeping staff during guest checkout room audits to record returned property and detect damages.</p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 11.5px; padding: 6px 12px;" onclick="resetAuditChecklistDefaults();">
                            <i class="fa-solid fa-rotate-left"></i> Reset Factory 14 Defaults
                        </button>
                        <button type="button" class="adm-btn-action gold" style="padding: 6px 14px; font-size: 11.5px; font-weight: 700;" onclick="addAuditChecklistItem();">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>

                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;" id="gen-audit-checklist-table">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.5); border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--adm-gold); font-size: 11px; text-transform: uppercase;">
                                <th style="padding: 12px 14px; width: 40px; text-align: center;">#</th>
                                <th style="padding: 12px 14px;">Inventory Item / Appliance Name</th>
                                <th style="padding: 12px 14px; width: 110px; text-align: center;">Standard Qty</th>
                                <th style="padding: 12px 14px;">Placement / Audit Notes</th>
                                <th style="padding: 12px 14px; width: 60px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="gen-audit-checklist-tbody">
                            <?php foreach ($room_audit_checklist as $idx => $it):
                                $it_name = $it['item'] ?? ($it['name'] ?? '');
                                $it_qty = (int)($it['qty'] ?? 1);
                                $it_notes = $it['notes'] ?? ($it['category'] ?? '');
                            ?>
                            <tr class="gen-audit-row" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 10px 14px; text-align: center; color: var(--adm-text-muted);" class="gen-audit-idx">
                                    <?php echo $idx + 1; ?>
                                </td>
                                <td style="padding: 10px 14px;">
                                    <input type="text" class="adm-form-control gen-audit-item-name" value="<?php echo htmlspecialchars($it_name); ?>" placeholder="Item name" style="font-size: 12px; padding: 6px 10px;">
                                </td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <input type="number" min="1" max="99" class="adm-form-control gen-audit-item-qty" value="<?php echo $it_qty; ?>" style="font-size: 12px; padding: 6px 8px; text-align: center;">
                                </td>
                                <td style="padding: 10px 14px;">
                                    <input type="text" class="adm-form-control gen-audit-item-notes" value="<?php echo htmlspecialchars($it_notes); ?>" placeholder="Verification notes" style="font-size: 12px; padding: 6px 10px;">
                                </td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); padding: 5px 9px; font-size: 11px;" onclick="deleteAuditChecklistItem(this);" title="Remove item">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-size: 11.5px; color: var(--adm-text-muted);">
                        <i class="fa-solid fa-circle-info" style="color: var(--adm-gold); margin-right: 4px;"></i>
                        These items automatically populate in the Checkout Room Audit modal in Reservations &amp; Billing.
                    </span>
                    <button type="button" id="btn-save-gen-audit" class="adm-btn-action gold" style="padding: 10px 26px; font-size: 13px; font-weight: 700;" onclick="saveAuditChecklistAll();">
                        <i class="fa-solid fa-floppy-disk"></i> Save Checklist Changes
                    </button>
                </div>
            </div>

        </div> <!-- End #general-settings-content-area -->

    </div> <!-- End #modal-general-settings-card -->
</div> <!-- End #modal-general-settings -->

<script>
function filterSettingsCards(query) {
    var q = (query || '').toLowerCase().trim();
    var cards = document.querySelectorAll('.adm-settings-cards-grid .adm-setting-card-btn');
    cards.forEach(function(card) {
        var text = (card.getAttribute('data-title') || '') + ' ' + card.innerText.toLowerCase();
        if (!q || text.indexOf(q) !== -1) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

/* =========================================================================
   Master General Settings Hub Modal & Controller Functions
   ========================================================================= */
function openGeneralSettingsModal(initialTab) {
    var modal = document.getElementById('modal-general-settings');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        switchGeneralTab(initialTab || 'branding');
    }
}

function closeGeneralSettingsModal() {
    var modal = document.getElementById('modal-general-settings');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function switchGeneralTab(tabKey) {
    var btns = document.querySelectorAll('#general-settings-tabs-bar .adm-gen-tab-btn');
    btns.forEach(function(b) {
        if (b.getAttribute('data-tab') === tabKey) {
            b.classList.add('active');
            b.style.border = '1px solid var(--adm-gold)';
            b.style.background = 'rgba(197, 160, 89, 0.22)';
            b.style.color = '#FFFFFF';
        } else {
            b.classList.remove('active');
            b.style.border = '1px solid rgba(255,255,255,0.1)';
            b.style.background = 'transparent';
            b.style.color = 'var(--adm-text-secondary)';
        }
    });

    var panes = document.querySelectorAll('#general-settings-content-area .adm-gen-tab-pane');
    panes.forEach(function(p) {
        p.style.display = 'none';
    });

    var targetPane = document.getElementById('gen-pane-' + tabKey);
    if (targetPane) {
        targetPane.style.display = 'block';
    }
}

// 1. Branding & Logo Handlers
function previewGeneralLogo(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('gen-logo-preview-img');
            var placeholder = document.getElementById('gen-logo-placeholder');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            if (placeholder) {
                placeholder.style.display = 'none';
            }
            var rem = document.getElementById('gen-branding-remove-logo');
            if (rem) rem.value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeGeneralLogo() {
    var rem = document.getElementById('gen-branding-remove-logo');
    if (rem) rem.value = '1';
    var fileIn = document.getElementById('gen-logo-file-input');
    if (fileIn) fileIn.value = '';
    var img = document.getElementById('gen-logo-preview-img');
    if (img) img.style.display = 'none';
    var placeholder = document.getElementById('gen-logo-placeholder');
    if (placeholder) placeholder.style.display = 'block';
    showSettingsToast('Logo marked for removal. Click "Save Resort Branding" to apply.');
}

function saveGeneralBranding(e) {
    if (e && e.preventDefault) e.preventDefault();
    var form = document.getElementById('form-general-branding');
    var btn = document.getElementById('btn-save-gen-branding');
    if (btn) btn.disabled = true;

    var fd = new FormData(form);
    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Resort branding and policy updated everywhere!');
            if (data.site_logo) {
                var hdrLogo = document.querySelector('.adm-header-logo img, .adm-brand-logo');
                if (hdrLogo) hdrLogo.src = '../' + data.site_logo;
            }
        } else {
            alert(data.message || 'Error updating branding.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

// 2. GST Simulation & Save
function updateGenGstSimulator() {
    var rFood = parseFloat(document.getElementById('gen-gst-rate-food').value) || 0;
    var rCottage = parseFloat(document.getElementById('gen-gst-rate-cottage').value) || 0;
    var rOther = parseFloat(document.getElementById('gen-gst-rate-other').value) || 0;

    var taxCottage = 10000 * (rCottage / 100);
    var taxFood = 2500 * (rFood / 100);
    var taxOther = 1000 * (rOther / 100);
    var totalTax = taxCottage + taxFood + taxOther;

    var simC = document.getElementById('sim-tax-cottage');
    if (simC) simC.textContent = '₹' + taxCottage.toFixed(2) + ' (' + rCottage + '%)';
    var simF = document.getElementById('sim-tax-food');
    if (simF) simF.textContent = '₹' + taxFood.toFixed(2) + ' (' + rFood + '%)';
    var simO = document.getElementById('sim-tax-other');
    if (simO) simO.textContent = '₹' + taxOther.toFixed(2) + ' (' + rOther + '%)';
    var simT = document.getElementById('sim-tax-total');
    if (simT) simT.textContent = '₹' + totalTax.toFixed(2);
}

function saveGeneralGst(e) {
    if (e && e.preventDefault) e.preventDefault();
    var form = document.getElementById('form-general-gst');
    var btn = document.getElementById('btn-save-gen-gst');
    if (btn) btn.disabled = true;

    var fd = new FormData(form);
    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'GST configuration saved successfully!');
            if (data.rates) {
                var hF = document.getElementById('hero-gst-food');
                if (hF) hF.textContent = data.rates.food + '%';
                var hC = document.getElementById('hero-gst-cottage');
                if (hC) hC.textContent = data.rates.cottage + '%';
                var hO = document.getElementById('hero-gst-other');
                if (hO) hO.textContent = data.rates.other + '%';
            }
        } else {
            alert(data.message || 'Error saving GST settings.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

// 4. Live Preview Preferences
function setAllCardPreviews(enabled) {
    var chks = document.querySelectorAll('.gen-card-pv-check');
    chks.forEach(function(c) { c.checked = enabled; });
}

function saveGeneralPreview(e) {
    if (e && e.preventDefault) e.preventDefault();
    var btn = document.getElementById('btn-save-gen-preview');
    if (btn) btn.disabled = true;

    var fd = new FormData();
    fd.append('action', 'save_live_preview_settings');
    fd.append('ajax', '1');
    var globalToggle = document.getElementById('gen-preview-global-toggle');
    fd.append('live_preview_enabled', (globalToggle && globalToggle.checked) ? '1' : '0');

    var cardsConfig = {};
    var chks = document.querySelectorAll('.gen-card-pv-check');
    chks.forEach(function(c) {
        var name = c.getAttribute('name').replace('cards_config[', '').replace(']', '');
        cardsConfig[name] = c.checked ? '1' : '0';
    });
    fd.append('cards_config', JSON.stringify(cardsConfig));

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Live preview preferences saved!');
        } else {
            alert(data.message || 'Error saving live preview settings.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

// 5. Session Inactivity Timings
function saveGeneralSessions(e) {
    if (e && e.preventDefault) e.preventDefault();
    var form = document.getElementById('form-general-sessions');
    var btn = document.getElementById('btn-save-gen-sessions');
    if (btn) btn.disabled = true;

    var fd = new FormData(form);
    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Session inactivity timings saved successfully!');
        } else {
            alert(data.message || 'Error saving session timings.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

// 6. Admin Security & Profile
function previewGeneralAdminAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('gen-admin-avatar-img');
            var placeholder = document.getElementById('gen-admin-avatar-placeholder');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            if (placeholder) {
                placeholder.style.display = 'none';
            }
            var rem = document.getElementById('gen-admin-remove-avatar');
            if (rem) rem.value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeGeneralAdminAvatar() {
    var rem = document.getElementById('gen-admin-remove-avatar');
    if (rem) rem.value = '1';
    var fileIn = document.getElementById('gen-admin-avatar-file');
    if (fileIn) fileIn.value = '';
    var img = document.getElementById('gen-admin-avatar-img');
    if (img) img.style.display = 'none';
    var placeholder = document.getElementById('gen-admin-avatar-placeholder');
    if (placeholder) placeholder.style.display = 'block';
    showSettingsToast('Profile photo removed from draft. Click "Update Admin Profile" to apply.');
}

function saveGeneralAdminSecurity(e) {
    if (e && e.preventDefault) e.preventDefault();
    var form = document.getElementById('form-general-admin-security');
    var btn = document.getElementById('btn-save-gen-security');

    var newPwd = document.getElementById('gen-admin-new-pwd').value;
    var confPwd = document.getElementById('gen-admin-conf-pwd').value;
    if (newPwd && newPwd !== confPwd) {
        alert('New password and confirmation do not match.');
        return;
    }

    if (btn) btn.disabled = true;
    var fd = new FormData(form);

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Admin profile and security credentials updated!');
            document.getElementById('gen-admin-curr-pwd').value = '';
            document.getElementById('gen-admin-new-pwd').value = '';
            document.getElementById('gen-admin-conf-pwd').value = '';
            var admNameEl = document.querySelector('.adm-user-name, .adm-user-info strong');
            if (admNameEl && data.full_name) admNameEl.textContent = data.full_name;
        } else {
            alert(data.message || 'Error updating administrator profile.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

// 7. User Profiles Hub & Blue Tick
function filterGeneralUsersList(query) {
    var q = (query || '').toLowerCase().trim();
    var cards = document.querySelectorAll('#gen-users-cards-container .gen-user-item-card');
    cards.forEach(function(c) {
        var text = (c.getAttribute('data-search') || '').toLowerCase();
        if (!q || text.indexOf(q) !== -1) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}

function toggleUserVerification(userId) {
    var fd = new FormData();
    fd.append('action', 'manage_general_users');
    fd.append('subaction', 'toggle_verify');
    fd.append('user_id', userId);

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var isVer = (data.is_verified === 1 || data.is_verified === '1' || data.is_verified === true);
            var badge = document.getElementById('gen-user-badge-verified-' + userId);
            var btn = document.getElementById('gen-btn-verify-' + userId);
            var card = document.getElementById('gen-user-card-' + userId);

            if (badge) {
                badge.style.display = isVer ? 'inline-flex' : 'none';
                if (isVer) {
                    badge.style.background = 'rgba(56, 189, 248, 0.2)';
                    badge.style.color = '#38BDF8';
                    badge.style.border = '1px solid rgba(56, 189, 248, 0.45)';
                    badge.style.boxShadow = '0 0 10px rgba(56, 189, 248, 0.35)';
                    badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verified Guest';
                }
            }

            if (btn) {
                btn.style.background = isVer ? 'rgba(56, 189, 248, 0.2)' : 'rgba(255,255,255,0.06)';
                btn.style.color = isVer ? '#38BDF8' : 'var(--adm-text-secondary)';
                btn.style.borderColor = isVer ? 'rgba(56, 189, 248, 0.4)' : 'rgba(255,255,255,0.15)';
                var span = btn.querySelector('span');
                if (span) span.textContent = isVer ? 'Verified (Blue Tick)' : 'Verify Profile';
            }

            if (card) {
                card.style.borderColor = isVer ? 'rgba(56, 189, 248, 0.45)' : 'rgba(255,255,255,0.08)';
            }

            var countEl = document.getElementById('gen-verified-users-count');
            if (countEl) {
                var cur = parseInt(countEl.textContent) || 0;
                countEl.textContent = isVer ? (cur + 1) : Math.max(0, cur - 1);
            }

            showSettingsToast(data.message);
        } else {
            alert(data.message || 'Error toggling verification.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    });
}

function toggleUserStatus(userId) {
    var fd = new FormData();
    fd.append('action', 'manage_general_users');
    fd.append('subaction', 'toggle_status');
    fd.append('user_id', userId);

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var isActive = (data.status === 1 || data.status === '1' || data.status === true);
            var badge = document.getElementById('gen-user-badge-status-' + userId);
            var btn = document.getElementById('gen-btn-status-' + userId);

            if (badge) {
                badge.style.background = isActive ? 'rgba(46, 204, 113, 0.15)' : 'rgba(239, 68, 68, 0.15)';
                badge.style.color = isActive ? '#2ecc71' : '#f87171';
                badge.style.border = isActive ? '1px solid rgba(46, 204, 113, 0.3)' : '1px solid rgba(239, 68, 68, 0.3)';
                badge.textContent = isActive ? 'Active Account' : 'Account Disabled';
            }

            if (btn) {
                btn.style.background = isActive ? 'rgba(239, 68, 68, 0.12)' : 'rgba(46, 204, 113, 0.15)';
                btn.style.color = isActive ? '#f87171' : '#2ecc71';
                btn.style.borderColor = isActive ? 'rgba(239, 68, 68, 0.3)' : 'rgba(46, 204, 113, 0.3)';
                var ico = btn.querySelector('i');
                if (ico) ico.className = 'fa-solid ' + (isActive ? 'fa-user-slash' : 'fa-user-check');
                var span = btn.querySelector('span');
                if (span) span.textContent = isActive ? 'Disable' : 'Enable';
            }

            showSettingsToast(data.message);
        } else {
            alert(data.message || 'Error updating user status.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    });
}

function saveUserFeedbackNote(userId, btn) {
    var ta = document.getElementById('gen-user-notes-' + userId);
    var notes = ta ? ta.value : '';

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving';
    }

    var fd = new FormData();
    fd.append('action', 'manage_general_users');
    fd.append('subaction', 'save_notes');
    fd.append('user_id', userId);
    fd.append('admin_notes', notes);

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Concierge guest notes saved! Will be noticed on next booking.');
        } else {
            alert(data.message || 'Error saving guest notes.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    })
    .finally(function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Note';
        }
    });
}

function promptResetUserPassword(userId, userName) {
    var newPwd = prompt('Enter a new password for ' + userName + ' (min 4 characters):');
    if (!newPwd || newPwd.trim() === '') return;
    if (newPwd.length < 4) {
        alert('Password must be at least 4 characters long.');
        return;
    }

    var fd = new FormData();
    fd.append('action', 'manage_general_users');
    fd.append('subaction', 'reset_password');
    fd.append('user_id', userId);
    fd.append('new_password', newPwd);

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Password reset successfully for ' + userName);
        } else {
            alert(data.message || 'Error resetting password.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    });
}

function promptEditUserProfile(userId, fullName, email, phone) {
    var newName = prompt('Update Full Name:', fullName);
    if (newName === null) return;
    var newEmail = prompt('Update Email Address:', email);
    if (newEmail === null) return;
    var newPhone = prompt('Update Mobile Phone Number:', phone);
    if (newPhone === null) return;

    var fd = new FormData();
    fd.append('action', 'manage_general_users');
    fd.append('subaction', 'edit_profile');
    fd.append('user_id', userId);
    fd.append('full_name', newName.trim());
    fd.append('email', newEmail.trim());
    fd.append('phone', newPhone.trim());

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Profile updated successfully.');
            var elN = document.getElementById('gen-user-name-' + userId);
            if (elN) elN.textContent = newName;
            var elE = document.getElementById('gen-user-email-' + userId);
            if (elE) elE.textContent = newEmail;
            var elP = document.getElementById('gen-user-phone-' + userId);
            if (elP) elP.textContent = newPhone;
            var card = document.getElementById('gen-user-card-' + userId);
            if (card) {
                card.setAttribute('data-search', (newName + ' ' + newPhone + ' ' + newEmail).toLowerCase());
            }
        } else {
            alert(data.message || 'Error updating profile.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    });
}

function confirmDeleteUserProfile(userId, userName) {
    if (!confirm('Are you sure you want to permanently delete profile for "' + userName + '"?\nThis action cannot be undone.')) {
        return;
    }

    var fd = new FormData();
    fd.append('action', 'manage_general_users');
    fd.append('subaction', 'delete_user');
    fd.append('user_id', userId);

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'User profile deleted permanently.');
            var card = document.getElementById('gen-user-card-' + userId);
            if (card) card.remove();
            var totalEl = document.getElementById('gen-total-users-count');
            if (totalEl) {
                var cur = parseInt(totalEl.textContent) || 0;
                totalEl.textContent = Math.max(0, cur - 1);
            }
        } else {
            alert(data.message || 'Error deleting user.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    });
}

// 8. Room Audit Checklist Handlers
function renumberAuditChecklistRows() {
    var rows = document.querySelectorAll('#gen-audit-checklist-tbody .gen-audit-row');
    rows.forEach(function(r, idx) {
        var num = r.querySelector('.gen-audit-idx');
        if (num) num.textContent = idx + 1;
    });
}

function addAuditChecklistItem() {
    var tbody = document.getElementById('gen-audit-checklist-tbody');
    if (!tbody) return;
    var rowCount = tbody.querySelectorAll('.gen-audit-row').length + 1;

    var tr = document.createElement('tr');
    tr.className = 'gen-audit-row';
    tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
    tr.innerHTML = '<td style="padding: 10px 14px; text-align: center; color: var(--adm-text-muted);" class="gen-audit-idx">' + rowCount + '</td>' +
                   '<td style="padding: 10px 14px;"><input type="text" class="adm-form-control gen-audit-item-name" value="" placeholder="e.g. Electric Kettle / Blanket" style="font-size: 12px; padding: 6px 10px;"></td>' +
                   '<td style="padding: 10px 14px; text-align: center;"><input type="number" min="1" max="99" class="adm-form-control gen-audit-item-qty" value="1" style="font-size: 12px; padding: 6px 8px; text-align: center;"></td>' +
                   '<td style="padding: 10px 14px;"><input type="text" class="adm-form-control gen-audit-item-notes" value="" placeholder="e.g. In kitchen cabinet" style="font-size: 12px; padding: 6px 10px;"></td>' +
                   '<td style="padding: 10px 14px; text-align: center;"><button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); padding: 5px 9px; font-size: 11px;" onclick="deleteAuditChecklistItem(this);" title="Remove item"><i class="fa-solid fa-trash-can"></i></button></td>';

    tbody.appendChild(tr);
    var input = tr.querySelector('.gen-audit-item-name');
    if (input) input.focus();
}

function deleteAuditChecklistItem(btn) {
    var tr = btn.closest('.gen-audit-row');
    if (tr) {
        tr.remove();
        renumberAuditChecklistRows();
    }
}

function resetAuditChecklistDefaults() {
    if (!confirm('Reset room audit checklist to factory default 14 items? Custom added items will be replaced.')) {
        return;
    }

    var fd = new FormData();
    fd.append('action', 'manage_room_audit_checklist');
    fd.append('subaction', 'reset_defaults');
    fd.append('ajax', '1');

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.items) {
            var tbody = document.getElementById('gen-audit-checklist-tbody');
            if (tbody) {
                tbody.innerHTML = '';
                data.items.forEach(function(it, idx) {
                    var tr = document.createElement('tr');
                    tr.className = 'gen-audit-row';
                    tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
                    tr.innerHTML = '<td style="padding: 10px 14px; text-align: center; color: var(--adm-text-muted);" class="gen-audit-idx">' + (idx + 1) + '</td>' +
                                   '<td style="padding: 10px 14px;"><input type="text" class="adm-form-control gen-audit-item-name" value="' + (it.item || '').replace(/"/g, '&quot;') + '" style="font-size: 12px; padding: 6px 10px;"></td>' +
                                   '<td style="padding: 10px 14px; text-align: center;"><input type="number" min="1" max="99" class="adm-form-control gen-audit-item-qty" value="' + (it.qty || 1) + '" style="font-size: 12px; padding: 6px 8px; text-align: center;"></td>' +
                                   '<td style="padding: 10px 14px;"><input type="text" class="adm-form-control gen-audit-item-notes" value="' + (it.notes || '').replace(/"/g, '&quot;') + '" style="font-size: 12px; padding: 6px 10px;"></td>' +
                                   '<td style="padding: 10px 14px; text-align: center;"><button type="button" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); padding: 5px 9px; font-size: 11px;" onclick="deleteAuditChecklistItem(this);"><i class="fa-solid fa-trash-can"></i></button></td>';
                    tbody.appendChild(tr);
                });
            }
            showSettingsToast(data.message || 'Checklist reset to factory 14 items.');
        } else {
            alert(data.message || 'Error resetting checklist.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    });
}

function saveAuditChecklistAll() {
    var btn = document.getElementById('btn-save-gen-audit');
    if (btn) btn.disabled = true;

    var rows = document.querySelectorAll('#gen-audit-checklist-tbody .gen-audit-row');
    var items = [];
    rows.forEach(function(r) {
        var nameIn = r.querySelector('.gen-audit-item-name');
        var qtyIn = r.querySelector('.gen-audit-item-qty');
        var notesIn = r.querySelector('.gen-audit-item-notes');

        var nameVal = nameIn ? nameIn.value.trim() : '';
        if (nameVal !== '') {
            items.push({
                item: nameVal,
                qty: qtyIn ? parseInt(qtyIn.value) || 1 : 1,
                notes: notesIn ? notesIn.value.trim() : ''
            });
        }
    });

    var fd = new FormData();
    fd.append('action', 'manage_room_audit_checklist');
    fd.append('subaction', 'save_all');
    fd.append('ajax', '1');
    fd.append('items', JSON.stringify(items));

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Room inspection checklist items updated successfully!');
        } else {
            alert(data.message || 'Error saving checklist.');
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Server communication error.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

function openFoodOrderingModal() {
    openGeneralSettingsModal('food');
}

function closeFoodOrderingModal() {
    closeGeneralSettingsModal();
}

function toggleFoodOrderingStatus(targetVal) {
    var btnModal = document.getElementById('modal-toggle-ordering-btn');
    if (btnModal) btnModal.disabled = true;

    var fd = new FormData();
    fd.append('action', 'toggle_food_ordering');
    fd.append('enabled', targetVal);
    fd.append('ajax', '1');

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var isEnabled = data.enabled;
            var nextVal = isEnabled ? '0' : '1';

            // 1. Update Modal Elements
            var modalBox = document.getElementById('modal-food-ordering-card');
            if (modalBox) {
                modalBox.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.45)' : 'rgba(239, 68, 68, 0.45)';
            }
            var modalIcon = document.getElementById('modal-ordering-icon');
            if (modalIcon) {
                modalIcon.className = 'adm-setting-card-icon ' + (isEnabled ? 'emerald' : 'rose');
                modalIcon.innerHTML = '<i class="fa-solid ' + (isEnabled ? 'fa-utensils' : 'fa-ban') + '"></i>';
            }
            var modalBadge = document.getElementById('modal-ordering-status-badge');
            if (modalBadge) {
                modalBadge.style.background = isEnabled ? 'rgba(46, 204, 113, 0.2)' : 'rgba(239, 68, 68, 0.2)';
                modalBadge.style.color = isEnabled ? '#2ecc71' : '#f87171';
                modalBadge.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.4)' : 'rgba(239, 68, 68, 0.4)';
                modalBadge.innerHTML = isEnabled ? '<i class="fa-solid fa-circle-check"></i> ENABLED & ACCEPTING ORDERS' : '<i class="fa-solid fa-circle-pause"></i> DISABLED / MENU HIDDEN';
            }
            if (btnModal) {
                btnModal.className = 'adm-btn-action ' + (isEnabled ? 'rose' : 'emerald');
                btnModal.setAttribute('onclick', 'toggleFoodOrderingStatus(' + nextVal + ');');
                var modalText = document.getElementById('modal-toggle-ordering-text');
                if (modalText) modalText.textContent = isEnabled ? 'Disable Food Ordering' : 'Enable Food Ordering';
                var modalIco = btnModal.querySelector('i');
                if (modalIco) modalIco.className = 'fa-solid ' + (isEnabled ? 'fa-toggle-off' : 'fa-toggle-on');
            }

            // 2. Update Card 06 status badge in Grid
            var card06Badge = document.getElementById('card06-status-badge');
            if (card06Badge) {
                card06Badge.style.background = isEnabled ? 'rgba(46, 204, 113, 0.15)' : 'rgba(239, 68, 68, 0.15)';
                card06Badge.style.color = isEnabled ? '#2ecc71' : '#f87171';
                card06Badge.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.3)' : 'rgba(239, 68, 68, 0.3)';
                card06Badge.textContent = isEnabled ? 'Ordering Active' : 'Ordering Paused';
            }

            // 4. Update General Settings Modal Food Tab
            var genFoodCard = document.getElementById('gen-food-toggle-card');
            if (genFoodCard) {
                genFoodCard.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.45)' : 'rgba(239, 68, 68, 0.45)';
            }
            var genFoodIcon = document.getElementById('gen-food-ordering-icon');
            if (genFoodIcon) {
                genFoodIcon.className = 'adm-setting-card-icon ' + (isEnabled ? 'emerald' : 'rose');
                genFoodIcon.innerHTML = '<i class="fa-solid ' + (isEnabled ? 'fa-utensils' : 'fa-ban') + '"></i>';
            }
            var genFoodBadge = document.getElementById('gen-food-ordering-badge');
            if (genFoodBadge) {
                genFoodBadge.style.background = isEnabled ? 'rgba(46, 204, 113, 0.2)' : 'rgba(239, 68, 68, 0.2)';
                genFoodBadge.style.color = isEnabled ? '#2ecc71' : '#f87171';
                genFoodBadge.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.4)' : 'rgba(239, 68, 68, 0.4)';
                genFoodBadge.innerHTML = isEnabled ? '<i class="fa-solid fa-circle-check"></i> ACTIVE & ACCEPTING ORDERS' : '<i class="fa-solid fa-circle-pause"></i> DISABLED / MENU HIDDEN';
            }
            var genFoodDesc = document.getElementById('gen-food-ordering-desc');
            if (genFoodDesc) {
                genFoodDesc.textContent = isEnabled ? 'In-house guests can place room service meal orders directly from the guest portal.' : 'Food ordering is currently paused. Guests cannot submit new food orders.';
            }
            var btnGenFood = document.getElementById('btn-toggle-gen-food');
            if (btnGenFood) {
                btnGenFood.className = 'adm-btn-action ' + (isEnabled ? 'rose' : 'emerald');
                btnGenFood.setAttribute('onclick', "toggleFoodOrderingStatus('" + (isEnabled ? '0' : '1') + "');");
                var lbl = document.getElementById('gen-food-btn-label');
                if (lbl) lbl.textContent = isEnabled ? 'Disable Food Ordering' : 'Enable Food Ordering';
                var ico = btnGenFood.querySelector('i');
                if (ico) ico.className = 'fa-solid ' + (isEnabled ? 'fa-toggle-off' : 'fa-toggle-on');
            }

            // Show Toast Alert
            var toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '24px';
            toast.style.right = '24px';
            toast.style.background = isEnabled ? 'rgba(16, 185, 129, 0.95)' : 'rgba(239, 68, 68, 0.95)';
            toast.style.color = '#FFFFFF';
            toast.style.padding = '12px 20px';
            toast.style.borderRadius = '8px';
            toast.style.fontWeight = '700';
            toast.style.fontSize = '13px';
            toast.style.boxShadow = '0 6px 20px rgba(0,0,0,0.35)';
            toast.style.zIndex = '999999';
            toast.innerHTML = (isEnabled ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-pause"></i> ') + data.message;
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 3500);
        } else {
            alert('Failed to update food ordering status.');
        }
    })
    .catch(function(err) {
        alert('Communication error updating status.');
    })
    .finally(function() {
        if (btnModal) btnModal.disabled = false;
    });
}

function openGstQuickModal() {
    openGeneralSettingsModal('gst');
}

function closeGstQuickModal() {
    closeGeneralSettingsModal();
}

function saveGstQuickSettings(e) {
    if (e) e.preventDefault();
    var btn = document.getElementById('btn-save-quick-gst');
    if (btn) btn.disabled = true;

    var fd = new FormData();
    fd.append('action', 'update_gst_settings');
    fd.append('ajax', '1');
    fd.append('gst_number', document.getElementById('modal_gst_number').value);
    fd.append('gst_legal_name', document.getElementById('modal_gst_legal_name').value);
    fd.append('gst_rate_food', document.getElementById('modal_gst_rate_food').value);
    fd.append('gst_rate_cottage', document.getElementById('modal_gst_rate_cottage').value);
    fd.append('gst_rate_other', document.getElementById('modal_gst_rate_other').value);

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            // Update top operations banner pills
            var elFood = document.getElementById('hero-gst-food');
            if (elFood) elFood.textContent = data.rates.food + '%';
            var elCottage = document.getElementById('hero-gst-cottage');
            if (elCottage) elCottage.textContent = data.rates.cottage + '%';
            var elOther = document.getElementById('hero-gst-other');
            if (elOther) elOther.textContent = data.rates.other + '%';
            var elGstNum = document.getElementById('top-gst-number-badge');
            if (elGstNum) elGstNum.textContent = 'GSTIN: ' + data.gst_number;

            closeGstQuickModal();

            // Show Toast Alert
            var toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '24px';
            toast.style.right = '24px';
            toast.style.background = 'rgba(16, 185, 129, 0.95)';
            toast.style.color = '#FFFFFF';
            toast.style.padding = '12px 20px';
            toast.style.borderRadius = '8px';
            toast.style.fontWeight = '700';
            toast.style.fontSize = '13px';
            toast.style.boxShadow = '0 6px 20px rgba(0,0,0,0.35)';
            toast.style.zIndex = '99999';
            toast.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message;
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 3500);
        } else {
            alert(data.message || 'Failed to update GST settings.');
        }
    })
    .catch(function(err) {
        alert('Communication error updating GST settings.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

function showSettingsToast(msg) {
    var toast = document.createElement('div');
    toast.style.position = 'fixed';
    toast.style.bottom = '24px';
    toast.style.right = '24px';
    toast.style.background = 'rgba(16, 185, 129, 0.95)';
    toast.style.color = '#FFFFFF';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '8px';
    toast.style.fontWeight = '700';
    toast.style.fontSize = '13px';
    toast.style.boxShadow = '0 6px 20px rgba(0,0,0,0.35)';
    toast.style.zIndex = '999999';
    toast.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + msg;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3500);
}

/* =========================================================================
   Card 23 • Navigation Menu Modal & Live Preview Handlers
   ========================================================================= */
function openNavMenuModal() {
    var modal = document.getElementById('modal-nav-menu');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        updateMenuLivePreview();
    }
}

function closeNavMenuModal() {
    var modal = document.getElementById('modal-nav-menu');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function updateMenuLivePreview() {
    for (var i = 1; i <= 10; i++) {
        var labelInput = document.getElementById('nav-item-label-' + i);
        var iconInput = document.getElementById('nav-item-icon-' + i);
        var highlightInput = document.getElementById('nav-item-highlight-' + i);
        var titleBadge = document.getElementById('row-title-badge-' + i);

        var prevContainer = document.getElementById('preview-item-' + i);
        var prevText = document.getElementById('preview-text-' + i);
        var prevIcon = document.getElementById('preview-icon-' + i);
        var iconBox = document.getElementById('icon-preview-box-' + i);

        if (!labelInput || !prevContainer) continue;

        var labelVal = labelInput.value.trim() || ('Item #' + i);
        var iconVal = iconInput ? iconInput.value.trim() : '';
        var isGold = highlightInput ? highlightInput.checked : false;

        if (titleBadge) titleBadge.textContent = labelVal;

        if (iconBox) {
            if (iconVal) {
                iconBox.innerHTML = '<i class="' + iconVal + '"></i>';
                iconBox.style.color = isGold ? '#C5A059' : '#FFF';
            } else {
                iconBox.innerHTML = '<span style="opacity: 0.3; font-size: 10px;">—</span>';
            }
        }

        if (prevText) prevText.textContent = labelVal;
        prevContainer.style.color = isGold ? '#C5A059' : '#DDE5E0';

        if (prevIcon) {
            if (iconVal) {
                prevIcon.className = iconVal;
                prevIcon.style.display = 'inline-block';
                prevIcon.style.color = isGold ? '#C5A059' : '#8FA89B';
            } else {
                prevIcon.style.display = 'none';
            }
        }
    }
}

function saveNavMenu(e) {
    if (e) e.preventDefault();
    var form = document.getElementById('nav-menu-form');
    if (!form) return;

    var btn = document.getElementById('btn-save-nav-menu');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving Menu...</span>';
    }

    var fd = new FormData(form);
    fd.append('action', 'update_navigation_menu');
    fd.append('ajax', '1');

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showSettingsToast(data.message || 'Navigation menu saved successfully!');
            closeNavMenuModal();
        } else {
            alert(data.message || 'Error updating navigation menu.');
        }
    })
    .catch(function(err) {
        alert('Server communication error saving navigation menu.');
    })
    .finally(function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>Save Navigation Menu</span>';
        }
    });
}

function resetNavMenuDefaults() {
    if (!confirm('Are you sure you want to reset all 10 navigation menu items back to factory defaults? Custom titles and links will be restored.')) {
        return;
    }

    var fd = new FormData();
    fd.append('action', 'update_navigation_menu');
    fd.append('reset_defaults', '1');
    fd.append('ajax', '1');

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.items) {
            data.items.forEach(function(item) {
                var id = item.id;
                var elLabel = document.getElementById('nav-item-label-' + id);
                var elUrl = document.getElementById('nav-item-url-' + id);
                var elIcon = document.getElementById('nav-item-icon-' + id);
                var elHighlight = document.getElementById('nav-item-highlight-' + id);
                var elDesktop = document.getElementById('nav-item-desktop-' + id);

                if (elLabel) elLabel.value = item.label;
                if (elUrl) elUrl.value = item.url;
                if (elIcon) elIcon.value = item.icon || '';
                if (elHighlight) elHighlight.checked = !!item.highlight;
                if (elDesktop) elDesktop.checked = !!item.show_in_desktop;
            });
            updateMenuLivePreview();
            showSettingsToast(data.message || 'Navigation menu reset to original defaults.');
        } else {
            alert(data.message || 'Failed to reset navigation menu.');
        }
    })
    .catch(function(err) {
        alert('Communication error resetting navigation menu.');
    });
}

/* =========================================================================
   360° Walkthrough Modal & Status Handlers
   ========================================================================= */
function openWalkthroughModal() {
    var modal = document.getElementById('modal-walkthrough-360');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeWalkthroughModal() {
    var modal = document.getElementById('modal-walkthrough-360');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function toggleWalkthroughStatus(targetVal) {
    var btn = document.getElementById('modal-toggle-walkthrough-btn');
    if (btn) btn.disabled = true;

    var fd = new FormData();
    fd.append('action', 'toggle_walkthrough_360');
    fd.append('enabled', targetVal);
    fd.append('ajax', '1');

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var isEnabled = data.enabled;
            var modalIcon = document.getElementById('modal-walkthrough-icon');
            var modalBadge = document.getElementById('modal-walkthrough-status-badge');
            var modalBtnToggle = document.getElementById('modal-toggle-walkthrough-btn');
            var modalTextToggle = document.getElementById('modal-toggle-walkthrough-text');

            var cardIcon = document.getElementById('card16-icon');
            var cardBadge = document.getElementById('card16-status-badge');
            var cardNum = document.querySelector('#adm-walkthrough-card-btn .adm-setting-card-num');

            if (isEnabled) {
                if (modalIcon) {
                    modalIcon.className = 'adm-setting-card-icon emerald';
                    modalIcon.style.background = 'rgba(197, 160, 89, 0.18)';
                    modalIcon.style.color = 'var(--adm-gold)';
                    modalIcon.style.borderColor = 'rgba(197, 160, 89, 0.4)';
                }
                if (modalBadge) {
                    modalBadge.className = 'adm-badge';
                    modalBadge.style.cssText = 'background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 10.5px; padding: 2px 9px; font-weight: 700; border-radius: 6px;';
                    modalBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> LIVE ON HOMEPAGE';
                }
                if (modalBtnToggle) {
                    modalBtnToggle.className = 'adm-btn-action rose';
                    modalBtnToggle.onclick = function() { toggleWalkthroughStatus('0'); };
                    var bi = modalBtnToggle.querySelector('i');
                    if (bi) bi.className = 'fa-solid fa-toggle-off';
                }
                if (modalTextToggle) modalTextToggle.textContent = 'Disable 360 Tour';

                if (cardIcon) {
                    cardIcon.className = 'adm-setting-card-icon emerald';
                }
                if (cardBadge) {
                    cardBadge.style.cssText = 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px; padding: 1px 6px;';
                    cardBadge.textContent = 'Live / On';
                }
                if (cardNum) cardNum.style.color = '#2ecc71';
            } else {
                if (modalIcon) {
                    modalIcon.className = 'adm-setting-card-icon rose';
                    modalIcon.style.background = 'rgba(239, 68, 68, 0.15)';
                    modalIcon.style.color = '#f87171';
                    modalIcon.style.borderColor = 'rgba(239, 68, 68, 0.35)';
                }
                if (modalBadge) {
                    modalBadge.className = 'adm-badge';
                    modalBadge.style.cssText = 'background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 10.5px; padding: 2px 9px; font-weight: 700; border-radius: 6px;';
                    modalBadge.innerHTML = '<i class="fa-solid fa-circle-pause"></i> DISABLED / HIDDEN';
                }
                if (modalBtnToggle) {
                    modalBtnToggle.className = 'adm-btn-action emerald';
                    modalBtnToggle.onclick = function() { toggleWalkthroughStatus('1'); };
                    var bi = modalBtnToggle.querySelector('i');
                    if (bi) bi.className = 'fa-solid fa-toggle-on';
                }
                if (modalTextToggle) modalTextToggle.textContent = 'Enable 360 Tour';

                if (cardIcon) {
                    cardIcon.className = 'adm-setting-card-icon rose';
                }
                if (cardBadge) {
                    cardBadge.style.cssText = 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 9.5px; padding: 1px 6px;';
                    cardBadge.textContent = 'Hidden / Off';
                }
                if (cardNum) cardNum.style.color = '#f87171';
            }

            showSettingsToast(data.message || 'Status updated.');
        } else {
            alert(data.message || 'Failed to update 360 Walkthrough status.');
        }
    })
    .catch(function(err) {
        alert('Communication error updating status.');
    })
    .finally(function() {
        if (btn) btn.disabled = false;
    });
}

function onWalkthroughCompressToggle(isChecked) {
    var cropperToggle = document.getElementById('cropper-auto-compress-toggle');
    if (cropperToggle) cropperToggle.checked = isChecked;
}

function updateWalkthroughRoom(stayKey, selectEl) {
    var roomId = selectEl.value;
    var fd = new FormData();
    fd.append('action', 'update_walkthrough_rooms');
    fd.append('ajax', '1');
    if (stayKey === 'woodhouse') {
        fd.append('walkthrough_woodhouse_room_id', roomId);
    } else {
        fd.append('walkthrough_mudhouse_room_id', roomId);
    }
    fetch('settings.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        showSettingsToast('Linked ' + (stayKey === 'woodhouse' ? 'Woodhouse' : 'Mudhouse') + ' room updated.');
    });
}

var activeWalkthroughCropContext = null;
var activeCropperInstance = null;

function triggerWalkthroughUpload(stayKey, typeKey) {
    var sel = document.getElementById('walkthrough_' + stayKey + '_room_select');
    var roomId = sel ? sel.value : 0;
    activeWalkthroughCropContext = {
        stayKey: stayKey,
        typeKey: typeKey,
        roomId: roomId,
        prevId: 'prev_' + stayKey + '_' + typeKey
    };
    var fileInput = document.getElementById('walkthrough-file-input');
    if (fileInput) {
        fileInput.value = '';
        fileInput.click();
    }
}

function onWalkthroughFileSelected(input) {
    if (!input.files || !input.files[0] || !activeWalkthroughCropContext) return;
    var file = input.files[0];
    activeWalkthroughCropContext.filename = file.name;
    var reader = new FileReader();
    reader.onload = function(e) {
        initWalkthroughCropperWithUrl(e.target.result, activeWalkthroughCropContext);
    };
    reader.readAsDataURL(file);
}

function cropExistingWalkthrough(stayKey, typeKey) {
    var prevImg = document.getElementById('prev_' + stayKey + '_' + typeKey);
    if (!prevImg || !prevImg.src) {
        alert('No photo found to crop.');
        return;
    }
    var sel = document.getElementById('walkthrough_' + stayKey + '_room_select');
    var roomId = sel ? sel.value : 0;
    activeWalkthroughCropContext = {
        stayKey: stayKey,
        typeKey: typeKey,
        roomId: roomId,
        prevId: 'prev_' + stayKey + '_' + typeKey,
        filename: (typeKey === 'pano' ? 'interior_360_pano.jpg' : 'exterior_cover_16x9.jpg')
    };
    initWalkthroughCropperWithUrl(prevImg.src, activeWalkthroughCropContext);
}

function initWalkthroughCropperWithUrl(imgUrl, ctx) {
    activeWalkthroughCropContext = ctx;
    var modal = document.getElementById('walkthrough-crop-modal');
    var targetImg = document.getElementById('cropper-target-img');
    if (!modal || !targetImg) return;

    var titleEl = document.getElementById('cropper-modal-title');
    var descEl = document.getElementById('cropper-modal-desc');
    var isPano = (ctx.typeKey === 'pano');
    if (titleEl) {
        titleEl.textContent = isPano ? 'CROP 360° INTERIOR PANORAMA (' + (ctx.stayKey.toUpperCase()) + ')' : 'CROP 16:9 EXTERIOR STAGE COVER (' + (ctx.stayKey.toUpperCase()) + ')';
    }
    if (descEl) {
        descEl.textContent = isPano ? 'Preserve top ceiling and floor. Standard 2:1 ratio recommended for equirectangular 360° WebGL spheres.' : 'Frame and position your photo for the 16:9 widescreen stage.';
    }

    if (activeCropperInstance) {
        activeCropperInstance.destroy();
        activeCropperInstance = null;
    }

    targetImg.src = imgUrl;
    modal.style.display = 'flex';

    var wtToggle = document.getElementById('walkthrough-auto-compress-toggle');
    var cropperToggle = document.getElementById('cropper-auto-compress-toggle');
    if (wtToggle && cropperToggle) {
        cropperToggle.checked = wtToggle.checked;
    }

    var defaultRatio = isPano ? (2 / 1) : (16 / 9);

    targetImg.onload = function() {
        if (typeof Cropper === 'undefined') {
            console.warn('Cropper.js not loaded');
            return;
        }
        if (activeCropperInstance) activeCropperInstance.destroy();

        activeCropperInstance = new Cropper(targetImg, {
            aspectRatio: defaultRatio,
            viewMode: 1,
            autoCropArea: 0.95,
            responsive: true,
            background: false,
            zoomable: true,
            movable: true,
            rotatable: true,
            scalable: false,
            crop: function(e) {
                var dims = document.getElementById('cropper-dims-indicator');
                if (dims) {
                    var w = Math.round(e.detail.width);
                    var h = Math.round(e.detail.height);
                    var ratio = (w / h).toFixed(2);
                    dims.innerText = w + ' × ' + h + ' px (' + (ratio === '2.00' ? '2:1 Panorama' : (ratio === '1.78' ? '16:9 Widescreen' : ratio + ':1')) + ')';
                }
            }
        });

        var btns = document.querySelectorAll('#cropper-ratio-buttons .adm-crop-ratio-btn');
        btns.forEach(function(b) {
            var r = parseFloat(b.getAttribute('data-ratio'));
            if (isPano && r === 2) b.classList.add('active');
            else if (!isPano && Math.abs(r - 1.777) < 0.05) b.classList.add('active');
            else b.classList.remove('active');
        });
    };
}

function setCropperRatio(ratio, btn) {
    if (activeCropperInstance) {
        activeCropperInstance.setAspectRatio(ratio);
    }
    document.querySelectorAll('#cropper-ratio-buttons .adm-crop-ratio-btn').forEach(function(b) {
        b.classList.remove('active');
    });
    if (btn) btn.classList.add('active');
}

function closeWalkthroughCropper() {
    var modal = document.getElementById('walkthrough-crop-modal');
    if (modal) modal.style.display = 'none';
    if (activeCropperInstance) {
        activeCropperInstance.destroy();
        activeCropperInstance = null;
    }
    activeWalkthroughCropContext = null;
}

function confirmWalkthroughCrop() {
    if (!activeCropperInstance || !activeWalkthroughCropContext) {
        closeWalkthroughCropper();
        return;
    }

    var btnConfirm = document.getElementById('btn-confirm-crop');
    if (btnConfirm) {
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading Cropped Photo...';
    }

    var isPano = (activeWalkthroughCropContext.typeKey === 'pano');
    var maxW = isPano ? 4096 : 2560;
    var maxH = isPano ? 2048 : 1440;

    var canvas = activeCropperInstance.getCroppedCanvas({
        maxWidth: maxW,
        maxHeight: maxH,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    if (!canvas) {
        alert('Could not render cropped frame. Please try again.');
        if (btnConfirm) { btnConfirm.disabled = false; btnConfirm.innerHTML = '<i class="fa-solid fa-check"></i> Apply Crop &amp; Upload Photo'; }
        return;
    }

    var base64Data = canvas.toDataURL('image/jpeg', 0.92);

    var fd = new FormData();
    fd.append('stay_key', activeWalkthroughCropContext.stayKey);
    fd.append('type_key', activeWalkthroughCropContext.typeKey);
    fd.append('room_id', activeWalkthroughCropContext.roomId);
    fd.append('image_data', base64Data);

    var autoCompressToggle = document.getElementById('cropper-auto-compress-toggle') || document.getElementById('walkthrough-auto-compress-toggle');
    var shouldCompress = (autoCompressToggle && autoCompressToggle.checked) ? '1' : '0';
    fd.append('auto_compress', shouldCompress);

    fetch('api/upload_walkthrough_photo.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var prevEl = document.getElementById(activeWalkthroughCropContext.prevId);
            if (prevEl) {
                prevEl.src = canvas.toDataURL('image/jpeg', 0.9);
            }
            closeWalkthroughCropper();
            showSettingsToast(data.message || 'Photo successfully cropped and updated!');
        } else {
            alert(data.message || 'Failed to upload photo.');
        }
    })
    .catch(function(err) {
        alert('Error uploading cropped photo to server.');
    })
    .finally(function() {
        if (btnConfirm) {
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = '<i class="fa-solid fa-check"></i> Apply Crop &amp; Upload Photo';
        }
    });
}

// Close modals on outside backdrop click or ESC key
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_menu') === '1') {
        openNavMenuModal();
    }
    var openGen = urlParams.get('open_general');
    if (openGen) {
        openGeneralSettingsModal(openGen);
    }

    var wtModal = document.getElementById('modal-walkthrough-360');
    if (wtModal) {
        wtModal.addEventListener('click', function(e) {
            if (e.target === wtModal) {
                closeWalkthroughModal();
            }
        });
    }

    var navModal = document.getElementById('modal-nav-menu');
    if (navModal) {
        navModal.addEventListener('click', function(e) {
            if (e.target === navModal) {
                closeNavMenuModal();
            }
        });
    }

    var genModal = document.getElementById('modal-general-settings');
    if (genModal) {
        genModal.addEventListener('click', function(e) {
            if (e.target === genModal) {
                closeGeneralSettingsModal();
            }
        });
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var genModal = document.getElementById('modal-general-settings');
        if (genModal && genModal.style.display === 'flex') {
            closeGeneralSettingsModal();
            return;
        }
        var cropModal = document.getElementById('walkthrough-crop-modal');
        if (cropModal && cropModal.style.display === 'flex') {
            closeWalkthroughCropper();
            return;
        }
        var navModal = document.getElementById('modal-nav-menu');
        if (navModal && navModal.style.display === 'flex') {
            closeNavMenuModal();
            return;
        }
        var wtModal = document.getElementById('modal-walkthrough-360');
        if (wtModal && wtModal.style.display === 'flex') {
            closeWalkthroughModal();
            return;
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
