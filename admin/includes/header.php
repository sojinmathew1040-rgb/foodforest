<?php
// =========================================================================
// Food Forest Sanctuary — Admin Header Component
// =========================================================================
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/upload.php';
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

    <!-- Global Image Upload Preview & Tab Switcher Functions -->
    <script>
    function previewUploadImage(input, previewImgId, infoBadgeId) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            if (!file.type.match('image.*')) {
                alert('Please select an image file (JPG, PNG, WEBP, GIF, SVG).');
                input.value = '';
                return;
            }
            var previewImg = document.getElementById(previewImgId);
            if (previewImg) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
            if (infoBadgeId) {
                var badge = document.getElementById(infoBadgeId);
                if (badge) {
                    var sizeKb = Math.round(file.size / 1024);
                    var sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB';
                    badge.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> ' + file.name + ' <span style="opacity:0.7;margin-left:4px;">(' + sizeStr + ')</span>';
                    badge.style.display = 'inline-flex';
                }
            }
        }
    }
    window.previewUploadImage = previewUploadImage;

    function admin_img_src(path, fallback) {
        if (!fallback) fallback = '../assets/images/treehouse_exterior.png';
        if (!path || !path.trim()) return fallback;
        path = path.trim();
        if (/^https?:\/\//i.test(path) || /^data:/i.test(path)) return path;
        if (path.indexOf('../') === 0) return path;
        return '../' + path.replace(/^\/+/, '');
    }
    window.admin_img_src = admin_img_src;

    function toggleAddNewDrawer(drawerId) {
        var el = document.getElementById(drawerId);
        if (!el) return;
        var isHidden = (el.style.display === 'none' || getComputedStyle(el).display === 'none');
        if (isHidden) {
            el.style.display = 'block';
            setTimeout(function() {
                try {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } catch(e){}
                var firstInput = el.querySelector('input[type="text"], textarea');
                if (firstInput) firstInput.focus();
            }, 50);
        } else {
            el.style.display = 'none';
        }
    }
    function toggle360Mode(showId, hideId) {
        var showEl = document.getElementById(showId);
        var hideEl = document.getElementById(hideId);
        if (showEl) showEl.style.display = 'block';
        if (hideEl) hideEl.style.display = 'none';
    }
    window.toggle360Mode = toggle360Mode;

    function updateStitchBadge(input, badgeId) {
        var badge = document.getElementById(badgeId);
        if (!badge) return;
        if (input.files && input.files[0]) {
            var f = input.files[0];
            badge.innerHTML = '<i class="fa-solid fa-check"></i> ' + f.name.substring(0, 14) + '...';
            badge.style.display = 'block';
        }
    }
    window.updateStitchBadge = updateStitchBadge;

    function setPinFromClick(event, containerId, inputXId, inputYId, markerId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        var rect = container.getBoundingClientRect();
        var x = event.clientX - rect.left;
        var y = event.clientY - rect.top;
        var xPercent = Math.max(2, Math.min(98, (x / rect.width) * 100));
        var yPercent = Math.max(2, Math.min(98, (y / rect.height) * 100));
        
        xPercent = Math.round(xPercent * 10) / 10;
        yPercent = Math.round(yPercent * 10) / 10;
        
        var inputX = document.getElementById(inputXId);
        var inputY = document.getElementById(inputYId);
        var marker = document.getElementById(markerId);
        
        if (inputX) inputX.value = xPercent;
        if (inputY) inputY.value = yPercent;
        if (marker) {
            marker.style.left = xPercent + '%';
            marker.style.top = yPercent + '%';
        }
    }
    window.setPinFromClick = setPinFromClick;

    function updatePinFromInput(inputXId, inputYId, markerId) {
        var inputX = document.getElementById(inputXId);
        var inputY = document.getElementById(inputYId);
        var marker = document.getElementById(markerId);
        if (!marker) return;
        var x = inputX ? parseFloat(inputX.value) || 50 : 50;
        var y = inputY ? parseFloat(inputY.value) || 50 : 50;
        marker.style.left = Math.max(2, Math.min(98, x)) + '%';
        marker.style.top = Math.max(2, Math.min(98, y)) + '%';
    }
    window.updatePinFromInput = updatePinFromInput;

    /* =========================================================================
       Sanctuary Master Map Studio: Mouse & Touch Drag-and-Drop Waypoint Engine
       ========================================================================= */
    function initAdminMasterMapStudio() {
        var canvas = document.getElementById('admin-master-map-canvas');
        if (!canvas) return;

        var activePin = null;
        var activeIdx = null;
        var feedbackPill = document.getElementById('admin-map-drag-feedback');
        var feedbackText = document.getElementById('admin-map-drag-text');
        var auraPath = document.getElementById('admin-master-trail-aura');
        var linePath = document.getElementById('admin-master-trail-line');

        function updateAdminTrail() {
            var pins = Array.from(document.querySelectorAll('.admin-master-pin'));
            if (pins.length < 2) {
                if (auraPath) auraPath.setAttribute('d', '');
                if (linePath) linePath.setAttribute('d', '');
                return;
            }

            // Sort pins by numerical spot number sequence (1 -> 2 -> 3...)
            pins.sort(function(a, b) {
                var na = parseInt(a.getAttribute('data-spot-num'), 10) || 0;
                var nb = parseInt(b.getAttribute('data-spot-num'), 10) || 0;
                return na - nb;
            });

            var pts = pins.map(function(pin) {
                var x = parseFloat(pin.style.left) || 0;
                var y = parseFloat(pin.style.top) || 0;
                return {
                    x: (x / 100.0) * 800,
                    y: (y / 100.0) * 520
                };
            });

            var d = "M " + pts[0].x.toFixed(1) + "," + pts[0].y.toFixed(1);
            for (var i = 0; i < pts.length - 1; i++) {
                var p0 = pts[i];
                var p1 = pts[i + 1];
                var mx = (p0.x + p1.x) / 2;
                var my = (p0.y + p1.y) / 2;
                var dx = p1.x - p0.x;
                var dy = p1.y - p0.y;
                var cx = mx - (dy * 0.12);
                var cy = my + (dx * 0.12);
                d += " Q " + cx.toFixed(1) + "," + cy.toFixed(1) + " " + p1.x.toFixed(1) + "," + p1.y.toFixed(1);
            }

            if (auraPath) auraPath.setAttribute('d', d);
            if (linePath) linePath.setAttribute('d', d);
        }
        window.updateAdminRouteTrail = updateAdminTrail;

        function onPointerDown(e) {
            var pin = e.target.closest('.admin-master-pin');
            if (!pin) return;

            e.preventDefault();
            activePin = pin;
            activeIdx = pin.getAttribute('data-idx');
            activePin.classList.add('is-dragging');

            if (feedbackPill && feedbackText) {
                feedbackPill.style.display = 'inline-flex';
                var sNum = pin.getAttribute('data-spot-num');
                feedbackText.textContent = 'Dragging Waypoint #' + sNum;
            }

            document.addEventListener('mousemove', onPointerMove);
            document.addEventListener('mouseup', onPointerUp);
            document.addEventListener('touchmove', onPointerMove, { passive: false });
            document.addEventListener('touchend', onPointerUp);
        }

        function onPointerMove(e) {
            if (!activePin) return;
            if (e.cancelable) e.preventDefault();

            var clientX = e.clientX;
            var clientY = e.clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }

            var rect = canvas.getBoundingClientRect();
            var relX = clientX - rect.left;
            var relY = clientY - rect.top;

            // Clamping within comfortable margin (2% to 98%)
            var pctX = Math.max(2, Math.min(98, (relX / rect.width) * 100));
            var pctY = Math.max(2, Math.min(98, (relY / rect.height) * 100));

            pctX = Math.round(pctX * 10) / 10;
            pctY = Math.round(pctY * 10) / 10;

            activePin.style.left = pctX + '%';
            activePin.style.top = pctY + '%';

            // Sync hidden inputs in the edit form
            var inputX = document.getElementById('spot_x_' + activeIdx);
            var inputY = document.getElementById('spot_y_' + activeIdx);
            if (inputX) inputX.value = pctX;
            if (inputY) inputY.value = pctY;

            // Sync pin tooltip coordinate text
            var pinCoords = document.getElementById('pin-coords-text-' + activeIdx);
            if (pinCoords) {
                pinCoords.textContent = 'X:' + pctX + '% Y:' + pctY + '%';
            }

            // Sync card badge text
            var cardBadge = document.getElementById('card-coord-badge-' + activeIdx);
            if (cardBadge) {
                cardBadge.textContent = 'X: ' + pctX + '% | Y: ' + pctY + '%';
            }

            // Sync HUD live feedback badge
            if (feedbackText) {
                var sNum = activePin.getAttribute('data-spot-num');
                feedbackText.textContent = 'Spot #' + sNum + ' (X: ' + pctX + '%, Y: ' + pctY + '%)';
            }

            // Live redraw route trail
            updateAdminTrail();
        }

        function onPointerUp() {
            if (!activePin) return;
            activePin.classList.remove('is-dragging');
            activePin = null;
            activeIdx = null;

            if (feedbackPill) {
                setTimeout(function() {
                    if (!activePin) feedbackPill.style.display = 'none';
                }, 1200);
            }

            document.removeEventListener('mousemove', onPointerMove);
            document.removeEventListener('mouseup', onPointerUp);
            document.removeEventListener('touchmove', onPointerMove);
            document.removeEventListener('touchend', onPointerUp);
        }

        // Attach listeners to canvas
        canvas.removeEventListener('mousedown', onPointerDown);
        canvas.addEventListener('mousedown', onPointerDown);
        canvas.removeEventListener('touchstart', onPointerDown);
        canvas.addEventListener('touchstart', onPointerDown, { passive: false });

        window.focusPinOnMasterMap = function(idx) {
            var pin = document.getElementById('master-pin-' + idx);
            if (!pin) return;

            var studio = document.querySelector('.admin-map-studio-card');
            if (studio) {
                try {
                    studio.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } catch(e) {}
            }

            pin.classList.remove('is-highlighted');
            void pin.offsetWidth;
            pin.classList.add('is-highlighted');

            setTimeout(function() {
                pin.classList.remove('is-highlighted');
            }, 3600);
        };

        window.syncSpotNumToPin = function(idx, val) {
            var pin = document.getElementById('master-pin-' + idx);
            if (!pin) return;
            val = parseInt(val, 10) || 1;
            pin.setAttribute('data-spot-num', val);
            var core = pin.querySelector('.admin-pin-core span');
            if (core) core.textContent = (val < 10 ? '0' : '') + val;
            var lblStrong = pin.querySelector('.admin-pin-label strong');
            var curTitle = pin.getAttribute('data-title') || '';
            if (lblStrong) lblStrong.textContent = '#' + (val < 10 ? '0' : '') + val + ' ' + curTitle.substring(0, 15);
            updateAdminTrail();
        };

        window.syncSpotTitleToPin = function(idx, val) {
            var pin = document.getElementById('master-pin-' + idx);
            if (!pin) return;
            pin.setAttribute('data-title', val);
            var num = parseInt(pin.getAttribute('data-spot-num'), 10) || 1;
            var lblStrong = pin.querySelector('.admin-pin-label strong');
            if (lblStrong) lblStrong.textContent = '#' + (num < 10 ? '0' : '') + num + ' ' + val.substring(0, 15);
        };
    }
    window.initAdminMasterMapStudio = initAdminMasterMapStudio;

    // Auto initialize on DOM readiness
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminMasterMapStudio);
    } else {
        setTimeout(initAdminMasterMapStudio, 50);
    }

    function removeSpotPhotoThumbnail(btn) {
        if (!btn) return;
        var item = btn.closest('.adm-spot-photo-thumb');
        if (item) {
            item.style.opacity = '0';
            item.style.transform = 'scale(0.8)';
            setTimeout(function() {
                item.remove();
            }, 150);
        }
    }
    window.removeSpotPhotoThumbnail = removeSpotPhotoThumbnail;

    function previewMultiSpotUpload(input, previewContainerId) {
        var container = document.getElementById(previewContainerId);
        if (!container || !input || !input.files) return;
        container.innerHTML = '';
        var count = input.files.length;
        if (count === 0) return;
        
        var info = document.createElement('div');
        info.style.cssText = 'font-size: 11px; color: #2ecc71; margin-bottom: 6px; font-weight: 600;';
        info.textContent = '✓ ' + count + ' new photo' + (count > 1 ? 's' : '') + ' selected';
        container.appendChild(info);

        var grid = document.createElement('div');
        grid.style.cssText = 'display: flex; gap: 8px; flex-wrap: wrap; align-items: center;';
        
        Array.from(input.files).forEach(function(file) {
            if (file.type && file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var thumb = document.createElement('div');
                    thumb.style.cssText = 'width: 55px; height: 55px; border-radius: 6px; overflow: hidden; border: 1px solid rgba(46, 204, 113, 0.4); position: relative;';
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                    thumb.appendChild(img);
                    grid.appendChild(thumb);
                };
                reader.readAsDataURL(file);
            }
        });
        container.appendChild(grid);
    }
    window.previewMultiSpotUpload = previewMultiSpotUpload;

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

            if (tabKey === 'sanctuary_map' && typeof window.initAdminMasterMapStudio === 'function') {
                setTimeout(window.initAdminMasterMapStudio, 80);
            }
        }

        // 3. Keep hidden active_tab fields synchronized across forms
        document.querySelectorAll('input[name="active_tab"]').forEach(function(input) {
            input.value = tabKey;
        });

        // 4. Update banner indicator text if present
        var tabTitleMap = {
            'climate': 'CARD 01 • CLIMATE TICKER & SANCTUARY ACCOLADES',
            'hero': 'CARD 02 • HERO MARQUEE & VISUAL BACKDROP',
            'philosophy': 'CARD 03 • SANCTUARY PHILOSOPHY & WELCOME MANIFESTO',
            'rooms': 'CARD 04 • VILLAS & COTTAGES (DYNAMIC TARIFFS & SPECS)',
            'experiences': 'CARD 05 • CURATED EXPERIENCES & RITUALS (DYNAMIC CMS)',
            'menu': 'CARD 06 • FOOD MENU & LIVING GASTRONOMY HUB (DYNAMIC CMS)',
            'why': 'CARD 07 • WHY FOOD FOREST? (LIVING SOIL & COB ARCHITECTURE)',
            'sanctuary_map': 'CARD 08 • SANCTUARY ESTATE MAP & MOUNTAIN ROUTE TRAILS',
            'seasons': 'CARD 09 • SEASONS OF KANTHALLOOR (DYNAMIC CMS)',
            'gallery': 'CARD 10 • VISUAL DIARY (8 PHOTO CHRONICLE)',
            'testimonials': 'CARD 11 • GUEST REFLECTIONS (TESTIMONIALS & REVIEWS)',
            'whatsapp': 'CARD 12 • WHATSAPP CONCIERGE & COMMUNICATION CHANNELS',
            'estate': 'CARD 13 • ESTATE BRANDING & OPERATIONAL IDENTITY',
            'protection': 'CARD 14 • WEBSITE CONTENT & IMAGE SHIELD',
            'security': 'CARD 15 • ADMINISTRATOR SECURITY & ACCESS KEY',
            'backup': 'CARD 16 • MYSQL DATABASE BACKUP & RESTORE'
        };
        var activeLabel = document.getElementById('active-tab-label');
        if (activeLabel && tabTitleMap[tabKey]) {
            activeLabel.textContent = tabTitleMap[tabKey];
        }

        // 5. Update Quick Jump Dropdown if present
        var jumpSelect = document.getElementById('adm-section-jump-select');
        if (jumpSelect) {
            jumpSelect.value = tabKey;
        }

        // 6. Update Live Website Anchor Link
        var anchorMap = {
            'estate': '../index.php',
            'whatsapp': '../index.php#whatsapp',
            'hero': '../index.php#hero',
            'climate': '../index.php#climate',
            'philosophy': '../index.php#welcome',
            'why': '../index.php#why-mudhouse',
            'experiences': '../index.php#experiences',
            'menu': '../index.php#dining',
            'seasons': '../index.php#seasons',
            'sanctuary_map': '../index.php#sanctuary-map',
            'rooms': '../index.php#villas',
            'gallery': '../index.php#gallery',
            'testimonials': '../index.php#reviews',
            'protection': '../index.php',
            'security': 'settings.php?tab=security',
            'backup': 'settings.php?tab=backup'
        };
        var liveAnchorLink = document.getElementById('adm-btn-live-anchor');
        if (liveAnchorLink && anchorMap[tabKey]) {
            liveAnchorLink.href = anchorMap[tabKey];
        }

        // 7. Update Live Preview Frame iframe source
        var pvIframe = document.getElementById('adm-live-preview-iframe');
        if (pvIframe) {
            pvIframe.src = 'preview_frame.php?section=' + tabKey;
        }

        // 8. Update browser URL parameter without reloading
        if (window.history && window.history.replaceState) {
            var url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    }
    window.switchSettingsTab = switchSettingsTab;
    </script>
    <script src="assets/js/live_preview.js?v=<?php echo time(); ?>" defer></script>
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
            </div>
        </header>

        <!-- Main Content Area Begins -->
        <main class="adm-content-body">
