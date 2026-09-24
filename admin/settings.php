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
            <input type="text" id="adm-card-filter-input" placeholder="Search 17 sections..." onkeyup="filterSettingsCards(this.value);" style="background: var(--adm-bg-surface); border: 1px solid var(--adm-gold-border); border-radius: 20px; padding: 7px 14px 7px 32px; font-size: 12px; color: #FFF; outline: none; width: 200px;">
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

<!-- ================================================================= -->
<!-- 16 FRONTEND CONFIGURATION CARDS GRID                              -->
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
            <span class="adm-setting-card-num">CARD 06 • GASTRONOMY</span>
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
