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
        @file_put_contents(__DIR__ . '/../db/foodforest.sql', $sql_dump);
        @file_put_contents(__DIR__ . '/../backup/foodforest_backup.sql', $sql_dump);
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
?>

<!-- Subheader Status & Search / Quick Action Toolbar -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px; padding: 4px 2px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span class="adm-pulse-dot" style="background:#2ecc71; box-shadow: 0 0 10px #2ecc71;"></span>
        <div>
            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; letter-spacing: 0.3px; display: block;">
                Frontend Configuration Sections
            </span>
            <span style="font-size: 12px; color: var(--adm-text-muted);">
                Click any section card below to open its dedicated 2-column editor with live user-side preview:
            </span>
        </div>
    </div>
    
    <div style="display: flex; align-items: center; gap: 10px;">
        <div style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--adm-text-muted); font-size: 12px;"></i>
            <input type="text" id="adm-card-filter-input" placeholder="Search 22 sections..." onkeyup="filterSettingsCards(this.value);" style="background: var(--adm-bg-surface); border: 1px solid var(--adm-gold-border); border-radius: 20px; padding: 7px 14px 7px 32px; font-size: 12px; color: #FFF; outline: none; width: 200px;">
        </div>

        <a href="../index.php" target="_blank" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;" title="Open Public Website">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Live Website</span>
        </a>
        <a href="settings.php?action=download_backup" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;" title="Export MySQL Database Dump">
            <i class="fa-solid fa-cloud-arrow-down"></i>
            <span>Download SQL</span>
        </a>
    </div>
</div>

<!-- Guest In-Cottage Food Ordering Operations Card -->
<div class="adm-card" id="adm-food-ordering-card" style="margin-bottom: 20px; padding: 20px 24px; background: linear-gradient(135deg, rgba(16, 31, 21, 0.94) 0%, rgba(9, 20, 14, 0.98) 100%); border: 1.5px solid <?php echo $food_ordering_enabled ? 'rgba(46, 204, 113, 0.45)' : 'rgba(239, 68, 68, 0.45)'; ?>; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.25); transition: border-color 0.3s ease;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div class="adm-setting-card-icon <?php echo $food_ordering_enabled ? 'emerald' : 'rose'; ?>" id="top-ordering-icon" style="width: 44px; height: 44px; font-size: 18px; border-radius: 10px;">
                <i class="fa-solid <?php echo $food_ordering_enabled ? 'fa-utensils' : 'fa-ban'; ?>"></i>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                    <h3 style="font-family: var(--adm-font-title); font-size: 15px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">
                        GUEST IN-COTTAGE FOOD ORDERING
                    </h3>
                    <span id="top-ordering-status-badge" class="adm-badge" style="<?php echo $food_ordering_enabled ? 'background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4);' : 'background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);'; ?> font-size: 11px; padding: 3px 10px; font-weight: 700; border-radius: 6px;">
                        <?php echo $food_ordering_enabled ? '<i class="fa-solid fa-circle-check"></i> ENABLED &amp; ACCEPTING ORDERS' : '<i class="fa-solid fa-circle-pause"></i> DISABLED / MENU HIDDEN'; ?>
                    </span>
                </div>
                <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0; max-width: 720px; line-height: 1.45;">
                    Control food ordering on the guest portal. When <strong>Enabled</strong>, resident guests can browse the farm menu and dispatch meal orders to the kitchen. When <strong>Disabled</strong>, the menu option is completely hidden from the user login.
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" id="top-toggle-ordering-btn" onclick="toggleFoodOrderingStatus(<?php echo $food_ordering_enabled ? '0' : '1'; ?>);" class="adm-btn-action <?php echo $food_ordering_enabled ? 'rose' : 'emerald'; ?>" style="padding: 9px 18px; font-size: 12.5px; font-weight: 700; border-radius: 8px;">
                <i class="fa-solid <?php echo $food_ordering_enabled ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                <span id="top-toggle-ordering-text"><?php echo $food_ordering_enabled ? 'Disable Food Ordering' : 'Enable Food Ordering'; ?></span>
            </button>

            <a href="edit_section.php?section=menu" class="adm-btn-action outline" style="padding: 8px 14px; font-size: 12px;" title="Manage Menu Dishes">
                <i class="fa-solid fa-utensils"></i>
                <span>Menu CMS</span>
            </a>

            <a href="kitchen.php" class="adm-btn-action gold" style="padding: 8px 14px; font-size: 12px;" title="Open Kitchen Orders Screen">
                <i class="fa-solid fa-kitchen-set"></i>
                <span>Kitchen Screen</span>
            </a>
        </div>
    </div>
</div>

<!-- Estate GST & Taxation Regime Architecture Operations Card -->
<div class="adm-card" id="adm-gst-operations-card" style="margin-bottom: 22px; padding: 20px 24px; background: linear-gradient(135deg, rgba(16, 31, 21, 0.94) 0%, rgba(13, 24, 18, 0.98) 100%); border: 1.5px solid rgba(197, 160, 89, 0.4); border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px; min-width: 0; flex: 1;">
            <div class="adm-setting-card-icon gold" style="width: 44px; height: 44px; font-size: 18px; border-radius: 10px; flex-shrink: 0; background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.35);">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div style="min-width: 0;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                    <h3 style="font-family: var(--adm-font-title); font-size: 15px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">
                        ESTATE GST &amp; TAXATION REGIMES
                    </h3>
                    <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.35); font-size: 10.5px; padding: 2px 8px; font-weight: 700; border-radius: 6px; font-family: monospace;" id="top-gst-number-badge">
                        GSTIN: <?php echo htmlspecialchars($gst_number); ?>
                    </span>
                    <span class="adm-badge confirmed" style="font-size: 10px; padding: 2px 8px;">
                        <i class="fa-solid fa-check"></i> Multi-Tier Bills Active
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                    <!-- 1. Food Rate Pill -->
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); padding: 4px 10px; border-radius: 6px; font-size: 11.5px;">
                        <span style="color: #F59E0B;">🍲 Food / Dining:</span>
                        <strong style="color: #FFFFFF; font-family: monospace; font-size: 12.5px;" id="hero-gst-food"><?php echo $gst_rate_food; ?>%</strong>
                        <span style="color: #94A3B8; font-size: 10px;">(SAC 996331)</span>
                    </div>

                    <!-- 2. Cottage Stay Rate Pill -->
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(56, 189, 248, 0.12); border: 1px solid rgba(56, 189, 248, 0.3); padding: 4px 10px; border-radius: 6px; font-size: 11.5px;">
                        <span style="color: #38BDF8;">🏡 Cottage Stay:</span>
                        <strong style="color: #FFFFFF; font-family: monospace; font-size: 12.5px;" id="hero-gst-cottage"><?php echo $gst_rate_cottage; ?>%</strong>
                        <span style="color: #94A3B8; font-size: 10px;">(SAC 996311)</span>
                    </div>

                    <!-- 3. Other Expenses Rate Pill -->
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.3); padding: 4px 10px; border-radius: 6px; font-size: 11.5px;">
                        <span style="color: #C084FC;">✨ Other / Services:</span>
                        <strong style="color: #FFFFFF; font-family: monospace; font-size: 12.5px;" id="hero-gst-other"><?php echo $gst_rate_other; ?>%</strong>
                        <span style="color: #94A3B8; font-size: 10px;">(SAC 999799)</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" onclick="openGstQuickModal();" class="adm-btn-action gold" style="padding: 9px 16px; font-size: 12px; font-weight: 700; border-radius: 8px;">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Quick Edit Rates</span>
            </button>

            <a href="edit_section.php?section=gst" class="adm-btn-action outline" style="padding: 9px 14px; font-size: 12px;" title="Open Detailed GST Section Editor">
                <i class="fa-solid fa-sliders"></i>
                <span>GST Section CMS</span>
            </a>

            <a href="billing.php" class="adm-btn-action" style="padding: 9px 14px; font-size: 12px; background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15);" title="Open Billing Folio Hub">
                <i class="fa-solid fa-receipt"></i>
                <span>Billing Folios</span>
            </a>
        </div>
    </div>
</div>

<!-- ================================================================= -->
<!-- 360° IMMERSIVE DYNAMIC WALKTHROUGH TOUR OPERATIONS CARD           -->
<!-- ================================================================= -->
<div class="adm-card" id="adm-walkthrough-card" style="margin-bottom: 24px; padding: 22px 26px; background: linear-gradient(135deg, rgba(16, 31, 21, 0.96) 0%, rgba(9, 20, 14, 0.98) 100%); border: 1.5px solid <?php echo $walkthrough_360_enabled ? 'rgba(197, 160, 89, 0.5)' : 'rgba(239, 68, 68, 0.45)'; ?>; border-radius: 14px; box-shadow: 0 6px 24px rgba(0,0,0,0.3); transition: border-color 0.3s ease;">
    <!-- Top Bar: Header & Controls -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.08);">
        <div style="display: flex; align-items: center; gap: 16px; min-width: 0; flex: 1;">
            <div class="adm-setting-card-icon <?php echo $walkthrough_360_enabled ? 'gold' : 'rose'; ?>" id="top-walkthrough-icon" style="width: 46px; height: 46px; font-size: 20px; border-radius: 10px; flex-shrink: 0; background: <?php echo $walkthrough_360_enabled ? 'rgba(197, 160, 89, 0.18)' : 'rgba(239, 68, 68, 0.15)'; ?>; color: <?php echo $walkthrough_360_enabled ? 'var(--adm-gold)' : '#f87171'; ?>; border: 1px solid <?php echo $walkthrough_360_enabled ? 'rgba(197, 160, 89, 0.4)' : 'rgba(239, 68, 68, 0.35)'; ?>;">
                <i class="fa-solid fa-arrows-spin"></i>
            </div>
            <div style="min-width: 0;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                    <h3 style="font-family: var(--adm-font-title); font-size: 16px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">
                        360° IMMERSIVE DYNAMIC WALKTHROUGH TOUR
                    </h3>
                    <span id="top-walkthrough-status-badge" class="adm-badge" style="<?php echo $walkthrough_360_enabled ? 'background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4);' : 'background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);'; ?> font-size: 11px; padding: 3px 10px; font-weight: 700; border-radius: 6px;">
                        <?php echo $walkthrough_360_enabled ? '<i class="fa-solid fa-circle-check"></i> ENABLED &amp; LIVE ON HOMEPAGE' : '<i class="fa-solid fa-circle-pause"></i> DISABLED / SECTION HIDDEN'; ?>
                    </span>
                </div>
                <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0; max-width: 780px; line-height: 1.45;">
                    Control the interactive 3D WebGL tour section on the public homepage. When <strong>Enabled</strong>, guests can explore 360° panoramas of the <strong>Woodhouse &amp; Mudhouse</strong>. When <strong>Disabled</strong>, the entire section is omitted from the website.
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" id="top-toggle-walkthrough-btn" onclick="toggleWalkthroughStatus(<?php echo $walkthrough_360_enabled ? '0' : '1'; ?>);" class="adm-btn-action <?php echo $walkthrough_360_enabled ? 'rose' : 'emerald'; ?>" style="padding: 9px 18px; font-size: 12.5px; font-weight: 700; border-radius: 8px;">
                <i class="fa-solid <?php echo $walkthrough_360_enabled ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                <span id="top-toggle-walkthrough-text"><?php echo $walkthrough_360_enabled ? 'Disable 360 Tour' : 'Enable 360 Tour'; ?></span>
            </button>

            <a href="../index.php#rooms-experience" target="_blank" class="adm-btn-action outline" style="padding: 8px 14px; font-size: 12px;" title="Preview 360 Tour on Live Website">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Live Preview</span>
            </a>

            <button type="button" onclick="const p = document.getElementById('walkthrough-operations-panel'); p.style.display = (p.style.display === 'none' ? 'grid' : 'none');" class="adm-btn-action" style="padding: 8px 14px; font-size: 12px; background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15);" title="Collapse / Expand 360 Photo Management">
                <i class="fa-solid fa-sliders"></i>
                <span>Manage Photos</span>
            </button>
        </div>
    </div>

    <!-- Main Dual Column Photo & Panorama Management Panel -->
    <div id="walkthrough-operations-panel" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-top: 18px;">
        
        <!-- ========================================================= -->
        <!-- STAY 1: WOODHOUSE (Timber Treehouse / Alpine Chalet)      -->
        <!-- ========================================================= -->
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

        <!-- ========================================================= -->
        <!-- STAY 2: MUDHOUSE (Handcrafted Earthen Cob Suite)          -->
        <!-- ========================================================= -->
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

<!-- Hidden File Input for Walkthrough Uploads -->
<input type="file" id="walkthrough-file-input" style="display: none;" accept="image/*" onchange="onWalkthroughFileSelected(this);">

<!-- ================================================================= -->
<!-- 22 FRONTEND CONFIGURATION CARDS GRID                              -->
<!-- ================================================================= -->
<div class="adm-settings-cards-grid" id="adm-cards-top-grid">

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

    <!-- Card 13: Estate & Identity -->
    <a href="edit_section.php?section=estate" class="adm-setting-card-btn" data-title="estate branding identity name currency checkin" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-leaf"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 13 • IDENTITY</span>
            <h4>Estate & Identity</h4>
            <p>Branding, check-in/out & currency</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 14: Content & Image Protection -->
    <a href="edit_section.php?section=protection" class="adm-setting-card-btn" data-title="content protection shield security devtools anti copy image shield" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 14 • PROTECTION</span>
            <h4>Content Protection</h4>
            <p>Anti-copy & DevTools shield</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 15: Security & Password -->
    <a href="edit_section.php?section=security" class="adm-setting-card-btn" data-title="security password access credentials master key admin" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon indigo"><i class="fa-solid fa-key"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 15 • ACCESS</span>
            <h4>Security & Password</h4>
            <p>Admin credentials & password</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 16: Backup Database -->
    <a href="edit_section.php?section=backup" class="adm-setting-card-btn" data-title="backup mysql database sql dump export tables restore" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon gold"><i class="fa-solid fa-database"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 16 • SQL BACKUP</span>
            <h4>MySQL Database Backup</h4>
            <p>1-click phpMyAdmin SQL dump</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 17: Bank Details & UPI Payment QR -->
    <a href="edit_section.php?section=bank" class="adm-setting-card-btn" data-title="bank payment upi qr code account ifsc branch billing transfer folio invoice" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon emerald"><i class="fa-solid fa-building-columns"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 17 • BANK & UPI QR</span>
            <h4>Bank Details & UPI QR</h4>
            <p>A/C number, IFSC, QR upload & bill print</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 18: Footer & Eco Trust Pillars -->
    <a href="edit_section.php?section=footer" class="adm-setting-card-btn" data-title="footer badges sustainability brand pillars legal copyright sanctuary gazette contact concierge navigation links" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon gold"><i class="fa-solid fa-seedling"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num">CARD 18 • FOOTER</span>
            <h4>Footer & Eco Trust Pillars</h4>
            <p>Trust badges, navigation, bio, gazette & legal</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 19: Media & Image Optimization (Auto-Compress & Resize) -->
    <a href="edit_section.php?section=media" class="adm-setting-card-btn" data-title="media image photo auto compress resize optimization webp automatic quality dimensions batch sandbox" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon cyan" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8; border-color: rgba(14, 165, 233, 0.35);"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
        <div class="adm-setting-card-content">
            <span class="adm-setting-card-num" style="color: #38bdf8;">CARD 19 • MEDIA OPTIMIZER</span>
            <h4>Image Compression &amp; Resize</h4>
            <p>Auto-compress, WebP &amp; resize high-res uploads</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 20: In-Cottage Food Ordering Toggle -->
    <div class="adm-setting-card-btn" data-title="food ordering guest dining enable disable toggle menu status kitchen in-cottage" style="display:flex; cursor: default;">
        <div class="adm-setting-card-icon <?php echo $food_ordering_enabled ? 'emerald' : 'rose'; ?>" id="card20-icon">
            <i class="fa-solid <?php echo $food_ordering_enabled ? 'fa-utensils' : 'fa-ban'; ?>"></i>
        </div>
        <div class="adm-setting-card-content" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="adm-setting-card-num" style="color: <?php echo $food_ordering_enabled ? '#2ecc71' : '#f87171'; ?>;">CARD 20 • FOOD ORDERING</span>
                <span id="card20-status-badge" class="adm-badge" style="<?php echo $food_ordering_enabled ? 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);' : 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);'; ?> font-size: 9.5px; padding: 1px 6px;">
                    <?php echo $food_ordering_enabled ? 'Active / On' : 'Disabled / Off'; ?>
                </span>
            </div>
            <h4>Guest Food Ordering</h4>
            <p>Toggle ordering access on guest portal</p>
            <div style="margin-top: 10px;">
                <button type="button" onclick="toggleFoodOrderingStatus(<?php echo $food_ordering_enabled ? '0' : '1'; ?>);" id="card20-toggle-btn" class="adm-btn-action <?php echo $food_ordering_enabled ? 'rose' : 'emerald'; ?>" style="padding: 6px 12px; font-size: 11.5px; width: 100%; justify-content: center;">
                    <i class="fa-solid <?php echo $food_ordering_enabled ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                    <span id="card20-toggle-text"><?php echo $food_ordering_enabled ? 'Disable Food Ordering' : 'Enable Food Ordering'; ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Card 21: Multi-Tier GST Tax Rates & Invoicing -->
    <a href="edit_section.php?section=gst" class="adm-setting-card-btn" data-title="gst tax rates food dining cottage room accommodation other expenses percentage invoice sac hsn" style="text-decoration:none; color:inherit; display:flex;">
        <div class="adm-setting-card-icon gold" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.35);"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="adm-setting-card-content" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="adm-setting-card-num" style="color: var(--adm-gold);">CARD 21 • GST &amp; TAXATION</span>
                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); font-size: 9.5px; padding: 1px 6px;">
                    Multi-Tier
                </span>
            </div>
            <h4>GST Rates &amp; Invoicing</h4>
            <p>Separate tax % for Cottages, Food &amp; Other services</p>
            <div style="margin-top: 8px; display: flex; gap: 6px; font-size: 10.5px;">
                <span style="color: #F59E0B; font-weight: 600;">Food: <?php echo $gst_rate_food; ?>%</span> • 
                <span style="color: #38BDF8; font-weight: 600;">Stay: <?php echo $gst_rate_cottage; ?>%</span> • 
                <span style="color: #C084FC; font-weight: 600;">Other: <?php echo $gst_rate_other; ?>%</span>
            </div>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Open Section Editor <i class="fa-solid fa-arrow-right"></i>
            </span>
        </div>
    </a>

    <!-- Card 22: 360° Dynamic Walkthrough & Panoramas -->
    <div class="adm-setting-card-btn" data-title="360 dynamic walkthrough virtual tour webgl panorama woodhouse treehouse mudhouse interactive framing" style="display:flex; cursor: pointer;" onclick="scrollToWalkthroughCard();">
        <div class="adm-setting-card-icon <?php echo $walkthrough_360_enabled ? 'emerald' : 'rose'; ?>" id="card22-icon" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.35);">
            <i class="fa-solid fa-arrows-spin"></i>
        </div>
        <div class="adm-setting-card-content" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="adm-setting-card-num" style="color: var(--adm-gold);">CARD 22 • 360° WALKTHROUGH</span>
                <span id="card22-status-badge" class="adm-badge" style="<?php echo $walkthrough_360_enabled ? 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);' : 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);'; ?> font-size: 9.5px; padding: 1px 6px;">
                    <?php echo $walkthrough_360_enabled ? 'Live / On' : 'Hidden / Off'; ?>
                </span>
            </div>
            <h4>360° Walkthrough Tour</h4>
            <p>Woodhouse &amp; Mudhouse 360° panoramas &amp; crop framing</p>
            <span style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                Manage 360° Tours <i class="fa-solid fa-arrow-up"></i>
            </span>
        </div>
    </div>

</div>

<!-- Modal: Quick Edit GST Rates -->
<div id="modal-quick-gst" style="display: none; position: fixed; inset: 0; background: rgba(5, 12, 8, 0.82); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(5px); padding: 20px;">
    <div style="background: linear-gradient(145deg, #101F15 0%, #0B170F 100%); border: 1.5px solid var(--adm-gold-border); border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid var(--adm-border);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="adm-setting-card-icon gold" style="width: 36px; height: 36px; font-size: 15px;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div>
                    <h3 style="font-family: var(--adm-font-title); font-size: 15px; color: #FFF; margin: 0; letter-spacing: 0.5px;">Configure GST Rates</h3>
                    <p style="font-size: 11.5px; color: var(--adm-text-secondary); margin: 2px 0 0;">Multi-tier tax percentage configuration for bills</p>
                </div>
            </div>
            <button type="button" onclick="closeGstQuickModal();" style="background: none; border: none; color: #FFF; font-size: 18px; cursor: pointer; opacity: 0.7;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="quick-gst-form" onsubmit="saveGstQuickSettings(event);" style="padding: 22px 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                <div class="adm-form-group" style="margin-bottom: 0;">
                    <label class="adm-form-label" style="font-size: 11.5px;">GSTIN / Tax ID</label>
                    <input type="text" id="modal_gst_number" name="gst_number" value="<?php echo htmlspecialchars($gst_number); ?>" class="adm-form-control" style="font-family: monospace; text-transform: uppercase; font-size: 12.5px;">
                </div>
                <div class="adm-form-group" style="margin-bottom: 0;">
                    <label class="adm-form-label" style="font-size: 11.5px;">Legal Business Name</label>
                    <input type="text" id="modal_gst_legal_name" name="gst_legal_name" value="<?php echo htmlspecialchars($gst_legal_name); ?>" class="adm-form-control" style="font-size: 12.5px;">
                </div>
            </div>

            <div style="border-top: 1px dashed rgba(197, 160, 89, 0.25); padding-top: 14px; margin-bottom: 14px;">
                <label style="font-size: 11px; color: var(--adm-gold); font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 10px;">Separate Multi-Tier Tax Percentages</label>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 8px; padding: 10px;">
                        <label style="font-size: 11px; color: #F59E0B; font-weight: 700; display: block; margin-bottom: 4px;">🍲 Food GST %</label>
                        <input type="number" step="0.01" min="0" max="100" id="modal_gst_rate_food" name="gst_rate_food" value="<?php echo $gst_rate_food; ?>" class="adm-form-control" style="font-weight: 700; font-size: 14px; text-align: center;" required>
                        <span style="font-size: 10px; color: var(--adm-text-muted); display: block; margin-top: 4px; text-align: center;">Dining / Orders</span>
                    </div>

                    <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 8px; padding: 10px;">
                        <label style="font-size: 11px; color: #38BDF8; font-weight: 700; display: block; margin-bottom: 4px;">🏡 Cottage GST %</label>
                        <input type="number" step="0.01" min="0" max="100" id="modal_gst_rate_cottage" name="gst_rate_cottage" value="<?php echo $gst_rate_cottage; ?>" class="adm-form-control" style="font-weight: 700; font-size: 14px; text-align: center;" required>
                        <span style="font-size: 10px; color: var(--adm-text-muted); display: block; margin-top: 4px; text-align: center;">Villas &amp; Stay</span>
                    </div>

                    <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 8px; padding: 10px;">
                        <label style="font-size: 11px; color: #C084FC; font-weight: 700; display: block; margin-bottom: 4px;">✨ Other GST %</label>
                        <input type="number" step="0.01" min="0" max="100" id="modal_gst_rate_other" name="gst_rate_other" value="<?php echo $gst_rate_other; ?>" class="adm-form-control" style="font-weight: 700; font-size: 14px; text-align: center;" required>
                        <span style="font-size: 10px; color: var(--adm-text-muted); display: block; margin-top: 4px; text-align: center;">Tours &amp; Extras</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;">
                <button type="button" onclick="closeGstQuickModal();" class="adm-btn-action outline" style="padding: 8px 16px; font-size: 12px;">Cancel</button>
                <button type="submit" id="btn-save-quick-gst" class="adm-btn-action gold" style="padding: 8px 20px; font-size: 12px; font-weight: 700;">
                    <i class="fa-solid fa-check"></i> Save GST Rates
                </button>
            </div>
        </form>
    </div>
</div>

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

        <div class="adm-cropper-modal-footer">
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

function toggleFoodOrderingStatus(targetVal) {
    var btnTop = document.getElementById('top-toggle-ordering-btn');
    var btnCard20 = document.getElementById('card20-toggle-btn');
    if (btnTop) btnTop.disabled = true;
    if (btnCard20) btnCard20.disabled = true;

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

            // 1. Update Top Banner Card
            var cardBox = document.getElementById('adm-food-ordering-card');
            if (cardBox) {
                cardBox.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.45)' : 'rgba(239, 68, 68, 0.45)';
            }
            var topIcon = document.getElementById('top-ordering-icon');
            if (topIcon) {
                topIcon.className = 'adm-setting-card-icon ' + (isEnabled ? 'emerald' : 'rose');
                topIcon.innerHTML = '<i class="fa-solid ' + (isEnabled ? 'fa-utensils' : 'fa-ban') + '"></i>';
            }
            var topBadge = document.getElementById('top-ordering-status-badge');
            if (topBadge) {
                topBadge.style.background = isEnabled ? 'rgba(46, 204, 113, 0.2)' : 'rgba(239, 68, 68, 0.2)';
                topBadge.style.color = isEnabled ? '#2ecc71' : '#f87171';
                topBadge.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.4)' : 'rgba(239, 68, 68, 0.4)';
                topBadge.innerHTML = isEnabled ? '<i class="fa-solid fa-circle-check"></i> ENABLED & ACCEPTING ORDERS' : '<i class="fa-solid fa-circle-pause"></i> DISABLED / MENU HIDDEN';
            }
            if (btnTop) {
                btnTop.className = 'adm-btn-action ' + (isEnabled ? 'rose' : 'emerald');
                btnTop.setAttribute('onclick', 'toggleFoodOrderingStatus(' + nextVal + ');');
                var topText = document.getElementById('top-toggle-ordering-text');
                if (topText) topText.textContent = isEnabled ? 'Disable Food Ordering' : 'Enable Food Ordering';
                var topIco = btnTop.querySelector('i');
                if (topIco) topIco.className = 'fa-solid ' + (isEnabled ? 'fa-toggle-off' : 'fa-toggle-on');
            }

            // 2. Update Card 06 status badge
            var card06Badge = document.getElementById('card06-status-badge');
            if (card06Badge) {
                card06Badge.style.background = isEnabled ? 'rgba(46, 204, 113, 0.15)' : 'rgba(239, 68, 68, 0.15)';
                card06Badge.style.color = isEnabled ? '#2ecc71' : '#f87171';
                card06Badge.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.3)' : 'rgba(239, 68, 68, 0.3)';
                card06Badge.textContent = isEnabled ? 'Ordering Active' : 'Ordering Paused';
            }

            // 3. Update Card 20 in grid
            var card20Icon = document.getElementById('card20-icon');
            if (card20Icon) {
                card20Icon.className = 'adm-setting-card-icon ' + (isEnabled ? 'emerald' : 'rose');
                card20Icon.innerHTML = '<i class="fa-solid ' + (isEnabled ? 'fa-utensils' : 'fa-ban') + '"></i>';
            }
            var card20Badge = document.getElementById('card20-status-badge');
            if (card20Badge) {
                card20Badge.style.background = isEnabled ? 'rgba(46, 204, 113, 0.15)' : 'rgba(239, 68, 68, 0.15)';
                card20Badge.style.color = isEnabled ? '#2ecc71' : '#f87171';
                card20Badge.style.borderColor = isEnabled ? 'rgba(46, 204, 113, 0.3)' : 'rgba(239, 68, 68, 0.3)';
                card20Badge.textContent = isEnabled ? 'Active / On' : 'Disabled / Off';
            }
            if (btnCard20) {
                btnCard20.className = 'adm-btn-action ' + (isEnabled ? 'rose' : 'emerald');
                btnCard20.setAttribute('onclick', 'toggleFoodOrderingStatus(' + nextVal + ');');
                var card20Text = document.getElementById('card20-toggle-text');
                if (card20Text) card20Text.textContent = isEnabled ? 'Disable Food Ordering' : 'Enable Food Ordering';
                var card20Ico = btnCard20.querySelector('i');
                if (card20Ico) card20Ico.className = 'fa-solid ' + (isEnabled ? 'fa-toggle-off' : 'fa-toggle-on');
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
            toast.style.zIndex = '99999';
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
        if (btnTop) btnTop.disabled = false;
        if (btnCard20) btnCard20.disabled = false;
    });
}

function openGstQuickModal() {
    var modal = document.getElementById('modal-quick-gst');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeGstQuickModal() {
    var modal = document.getElementById('modal-quick-gst');
    if (modal) {
        modal.style.display = 'none';
    }
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
   360° Walkthrough Operations Handlers & Cropper.js Integration
   ========================================================================= */
function scrollToWalkthroughCard() {
    var card = document.getElementById('adm-walkthrough-card');
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.style.transition = 'box-shadow 0.4s ease, border-color 0.4s ease';
        card.style.borderColor = 'var(--adm-gold)';
        card.style.boxShadow = '0 0 30px rgba(197, 160, 89, 0.4)';
        setTimeout(function() {
            card.style.boxShadow = '0 6px 24px rgba(0,0,0,0.3)';
        }, 1800);
    }
}

function toggleWalkthroughStatus(targetVal) {
    var btnTop = document.getElementById('top-toggle-walkthrough-btn');
    if (btnTop) btnTop.disabled = true;

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
            var badgeTop = document.getElementById('top-walkthrough-status-badge');
            var iconTop = document.getElementById('top-walkthrough-icon');
            var textTop = document.getElementById('top-toggle-walkthrough-text');
            var cardTop = document.getElementById('adm-walkthrough-card');

            var card22Icon = document.getElementById('card22-icon');
            var card22Badge = document.getElementById('card22-status-badge');

            if (isEnabled) {
                if (badgeTop) {
                    badgeTop.className = 'adm-badge';
                    badgeTop.style.cssText = 'background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); font-size: 11px; padding: 3px 10px; font-weight: 700; border-radius: 6px;';
                    badgeTop.innerHTML = '<i class="fa-solid fa-circle-check"></i> ENABLED &amp; LIVE ON HOMEPAGE';
                }
                if (iconTop) {
                    iconTop.className = 'adm-setting-card-icon gold';
                    iconTop.style.background = 'rgba(197, 160, 89, 0.18)';
                    iconTop.style.color = 'var(--adm-gold)';
                    iconTop.style.borderColor = 'rgba(197, 160, 89, 0.4)';
                }
                if (cardTop) cardTop.style.borderColor = 'rgba(197, 160, 89, 0.5)';
                if (btnTop) {
                    btnTop.className = 'adm-btn-action rose';
                    btnTop.onclick = function() { toggleWalkthroughStatus('0'); };
                    var bi = btnTop.querySelector('i');
                    if (bi) bi.className = 'fa-solid fa-toggle-off';
                }
                if (textTop) textTop.textContent = 'Disable 360 Tour';

                if (card22Icon) card22Icon.className = 'adm-setting-card-icon emerald';
                if (card22Badge) {
                    card22Badge.style.cssText = 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px; padding: 1px 6px;';
                    card22Badge.textContent = 'Live / On';
                }
            } else {
                if (badgeTop) {
                    badgeTop.className = 'adm-badge';
                    badgeTop.style.cssText = 'background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 11px; padding: 3px 10px; font-weight: 700; border-radius: 6px;';
                    badgeTop.innerHTML = '<i class="fa-solid fa-circle-pause"></i> DISABLED / SECTION HIDDEN';
                }
                if (iconTop) {
                    iconTop.className = 'adm-setting-card-icon rose';
                    iconTop.style.background = 'rgba(239, 68, 68, 0.15)';
                    iconTop.style.color = '#f87171';
                    iconTop.style.borderColor = 'rgba(239, 68, 68, 0.35)';
                }
                if (cardTop) cardTop.style.borderColor = 'rgba(239, 68, 68, 0.45)';
                if (btnTop) {
                    btnTop.className = 'adm-btn-action emerald';
                    btnTop.onclick = function() { toggleWalkthroughStatus('1'); };
                    var bi = btnTop.querySelector('i');
                    if (bi) bi.className = 'fa-solid fa-toggle-on';
                }
                if (textTop) textTop.textContent = 'Enable 360 Tour';

                if (card22Icon) card22Icon.className = 'adm-setting-card-icon rose';
                if (card22Badge) {
                    card22Badge.style.cssText = 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 9.5px; padding: 1px 6px;';
                    card22Badge.textContent = 'Hidden / Off';
                }
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
        if (btnTop) btnTop.disabled = false;
    });
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
