<?php
// =========================================================================
// Food Forest Sanctuary — Admin Header Component
// =========================================================================
require_once __DIR__ . '/auth.php';
require_admin_auth();

$admin = current_admin();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' — ' : ''; ?>Estate Concierge Admin | Food Forest Kanthalloor</title>

    <!-- Google Fonts: Cinzel, Cormorant Garamond, Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Dedicated Separate Admin Stylesheet -->
    <link rel="stylesheet" href="assets/css/admin.css?v=<?php echo time(); ?>">

    <!-- Global Tab Switcher Function (Guaranteed Available Immediately) -->
    <script>
    function switchSettingsTab(tabKey, cardEl, event, doScroll) {
        if (event) {
            if (typeof event.preventDefault === 'function') event.preventDefault();
            if (typeof event.stopPropagation === 'function') event.stopPropagation();
        }
        if (!tabKey) return;
        if (typeof doScroll === 'undefined') doScroll = true;
        
        // 1. Highlight active card in grid
        document.querySelectorAll('.adm-setting-card-btn').forEach(function(btn) {
            btn.classList.remove('is-active');
            var badge = btn.querySelector('.active-badge');
            if (badge) badge.style.display = 'none';
        });
        var activeBtn = cardEl || document.getElementById('card-' + tabKey) || document.querySelector(".adm-setting-card-btn[data-tab='" + tabKey + "']");
        if (activeBtn) {
            activeBtn.classList.add('is-active');
            var badge = activeBtn.querySelector('.active-badge');
            if (badge) badge.style.display = 'block';
        }

        // 2. Hide all panes and display target active pane directly below cards
        document.querySelectorAll('.adm-settings-tab-pane').forEach(function(pane) {
            pane.classList.remove('is-active');
            pane.style.display = 'none';
        });
        
        var targetPane = document.getElementById('pane-' + tabKey);
        if (targetPane) {
            targetPane.classList.add('is-active');
            targetPane.style.display = 'block';

            // Smoothly bring the active editing form comfortably into view
            if (doScroll) {
                setTimeout(function() {
                    try {
                        targetPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch(err) {
                        var rect = targetPane.getBoundingClientRect();
                        var topPos = window.pageYOffset + rect.top - 85;
                        window.scrollTo({ top: Math.max(0, topPos), behavior: 'smooth' });
                    }
                }, 40);
            }
        }

        // 3. Keep hidden active_tab fields synchronized across forms
        document.querySelectorAll('input[name="active_tab"]').forEach(function(input) {
            input.value = tabKey;
        });

        // 4. Update banner indicator text if present
        var tabTitleMap = {
            'estate': 'CARD 01 • ESTATE BRANDING & OPERATIONAL IDENTITY',
            'whatsapp': 'CARD 02 • WHATSAPP CONCIERGE & COMMUNICATION CHANNELS',
            'hero': 'CARD 03 • HERO MARQUEE & VISUAL BACKDROP',
            'climate': 'CARD 04 • CLIMATE TICKER & SANCTUARY ACCOLADES',
            'philosophy': 'CARD 05 • SANCTUARY PHILOSOPHY & WELCOME MANIFESTO',
            'why': 'CARD 06 • WHY FOOD FOREST? (LIVING SOIL & COB ARCHITECTURE)',
            'experiences': 'CARD 07 • CURATED EXPERIENCES & RITUALS (DYNAMIC CMS)',
            'seasons': 'CARD 08 • SEASONS OF KANTHALLOOR (DYNAMIC CMS)',
            'rooms': 'CARD 09 • VILLAS & COTTAGES (DYNAMIC TARIFFS & SPECS)',
            'gallery': 'CARD 10 • VISUAL DIARY (8 PHOTO CHRONICLE)',
            'testimonials': 'CARD 11 • GUEST REFLECTIONS (TESTIMONIALS & REVIEWS)',
            'protection': 'CARD 12 • WEBSITE CONTENT & IMAGE SHIELD',
            'security': 'CARD 13 • ADMINISTRATOR SECURITY & ACCESS KEY',
            'backup': 'CARD 14 • MYSQL DATABASE BACKUP & RESTORE'
        };
        var activeLabel = document.getElementById('active-tab-label');
        if (activeLabel && tabTitleMap[tabKey]) {
            activeLabel.textContent = tabTitleMap[tabKey];
        }

        // 5. Update browser URL parameter without reloading
        if (window.history && window.history.replaceState) {
            var url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    }
    window.switchSettingsTab = switchSettingsTab;
    </script>
</head>
<body class="adm-body">

<div class="adm-app-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="adm-main-viewport">
        <!-- Persistent Top Header Bar -->
        <header class="adm-topbar">
            <div class="adm-topbar-left">
                <button type="button" class="adm-mobile-toggle" id="adm-mobile-toggle" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="adm-page-heading">
                    <h1 class="adm-page-title"><?php echo isset($page_title) ? e($page_title) : 'Sanctuary Overview'; ?></h1>
                    <span class="adm-page-sub"><?php echo isset($page_subtitle) ? e($page_subtitle) : 'Kanthalloor High Range • 1,600m Elevation'; ?></span>
                </div>
            </div>

            <div class="adm-topbar-right">
                <div class="adm-estate-live-badge">
                    <span class="adm-pulse-dot"></span>
                    <span>Sanctuary Operational</span>
                </div>

                <a href="../index.php" target="_blank" class="adm-btn-site-preview" title="Open Public Website">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Live Sanctuary</span>
                </a>
            </div>
        </header>

        <!-- Main Content Area Begins -->
        <main class="adm-content-body">
