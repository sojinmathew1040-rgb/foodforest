<?php
// =========================================================================
// Food Forest Sanctuary — Interactive Estate Booking Page (BookMyShow Style)
// =========================================================================

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/client_auth.php';

$pdo = get_db();
ensure_rooms_pricing_columns($pdo);
ensure_sanctuary_spots_table_exists($pdo);
ensure_users_and_guest_columns($pdo);

$rooms = get_all_rooms(false);
$map_data = get_sanctuary_map_data($pdo, true);
$sanctuary_spots = $map_data['spots'];
$map_routes = $map_data['routes'];
$map_entrance = $map_data['entrance'];
$map_exit = $map_data['exit'];
$map_waypoints = $map_data['waypoints'];

$currency = get_setting('currency_symbol', '₹');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');

// Pre-selected villa from URL query
$preselect_slug = trim($_GET['villa'] ?? '');

// Load Header
require_once __DIR__ . '/includes/header.php';
?>

<main class="booking-page-main">
    
    <!-- Hero Banner & Filter Console -->
    <section class="booking-hero-section">
        <div class="container">
            <div class="booking-hero-header text-center">
                <span class="section-label font-sans"><i class="fa-solid fa-map-location-dot"></i> INTERACTIVE ESTATE RESERVATIONS</span>
                <h1 class="booking-page-title font-serif">Select Your Sanctuary Chalet</h1>
                <p class="booking-page-subtitle font-sans">
                    Explore our high-altitude organic estate. Pick your cottage directly on the interactive topographic map, check real-time availability, and customize your stay.
                </p>
            </div>

            <!-- Booking Filter Bar (Dates, Guests, Categories) -->
            <div class="booking-controls-card">
                <div class="booking-controls-grid">
                    
                    <!-- Check-in Date -->
                    <div class="ctrl-field">
                        <label for="book-checkin" class="ctrl-label font-sans"><i class="fa-regular fa-calendar"></i> Check-In</label>
                        <input type="date" id="book-checkin" class="ctrl-input font-sans" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Check-out Date -->
                    <div class="ctrl-field">
                        <label for="book-checkout" class="ctrl-label font-sans"><i class="fa-regular fa-calendar-check"></i> Check-Out</label>
                        <input type="date" id="book-checkout" class="ctrl-input font-sans" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>

                    <!-- Adults Counter -->
                    <div class="ctrl-field">
                        <label class="ctrl-label font-sans"><i class="fa-solid fa-user"></i> Adults</label>
                        <div class="ctrl-stepper">
                            <button type="button" class="ctrl-step-btn btn-minus" data-target="book-adults">−</button>
                            <input type="number" id="book-adults" class="ctrl-step-val font-sans" value="2" min="1" max="30" readonly>
                            <button type="button" class="ctrl-step-btn btn-plus" data-target="book-adults">+</button>
                        </div>
                    </div>

                    <!-- Children Counter -->
                    <div class="ctrl-field">
                        <label class="ctrl-label font-sans"><i class="fa-solid fa-child"></i> Children <small style="font-size: 10px; color: #4ADE80; font-weight: 700;">(Below 10 Free)</small></label>
                        <div class="ctrl-stepper">
                            <button type="button" class="ctrl-step-btn btn-minus" data-target="book-kids">−</button>
                            <input type="number" id="book-kids" class="ctrl-step-val font-sans" value="0" min="0" max="15" readonly>
                            <button type="button" class="ctrl-step-btn btn-plus" data-target="book-kids">+</button>
                        </div>
                    </div>

                    <!-- Action: Check Availability CTA Button -->
                    <div class="ctrl-field btn-check-avail-field">
                        <label class="ctrl-label font-sans"><i class="fa-solid fa-magnifying-glass"></i> Availability</label>
                        <button type="button" class="btn-check-availability font-sans" id="btn-check-live-availability" title="Refresh Real-Time Chalet Availability">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Check Availability</span>
                        </button>
                    </div>

                    <!-- View Switcher Tabs -->
                    <div class="ctrl-field view-switcher-field">
                        <label class="ctrl-label font-sans"><i class="fa-solid fa-sliders"></i> Layout View</label>
                        <div class="view-toggle-group">
                            <button type="button" class="view-toggle-btn active" id="view-btn-grid" data-view="grid">
                                <i class="fa-solid fa-grip"></i> <span>Chalet Grid</span>
                            </button>
                            <button type="button" class="view-toggle-btn" id="view-btn-map" data-view="map">
                                <i class="fa-solid fa-map"></i> <span>Estate Map</span>
                            </button>
                        </div>
                    </div>

                </div>

                <?php
                // Dynamic Stay Category Definitions & Active Types Extraction
                $category_definitions = get_stay_categories();

                $active_stay_types = [];
                $has_duplex = false;
                $has_single = false;

                foreach ($rooms as $rm) {
                    if (!empty($rm['stay_type'])) {
                        $st = strtolower(trim($rm['stay_type']));
                        if (!in_array($st, $active_stay_types)) {
                            $active_stay_types[] = $st;
                        }
                    }
                    $struct = $rm['structure_type'] ?? 'single_hut';
                    if ($struct === 'duplex_hut') {
                        $has_duplex = true;
                    }
                    if ($struct === 'single_hut') {
                        $has_single = true;
                    }
                }
                ?>

                <!-- Dynamic Stay Concept Filter Chips -->
                <div class="booking-filter-chips-row">
                    <span class="chips-label font-sans"><i class="fa-solid fa-filter"></i> Filter Stays:</span>
                    <button type="button" class="chip-btn active" data-stay-filter="all">All Chalets</button>
                    <?php 
                    foreach ($active_stay_types as $st): 
                        $meta = $category_definitions[$st] ?? [
                            'label' => ucwords(str_replace(['_', '-'], ' ', $st)),
                            'icon' => '🏡'
                        ];
                    ?>
                        <button type="button" class="chip-btn" data-stay-filter="<?php echo htmlspecialchars($st); ?>">
                            <?php echo $meta['icon']; ?> <?php echo htmlspecialchars($meta['label']); ?>
                        </button>
                    <?php endforeach; ?>
                    <?php if ($has_duplex): ?>
                        <button type="button" class="chip-btn" data-stay-filter="duplex">🏰 Duplex Suites</button>
                    <?php endif; ?>
                    <?php if ($has_single && $has_duplex): ?>
                        <button type="button" class="chip-btn" data-stay-filter="single">🏡 Single Cottages</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Interactive Map Legend & Category Quick-Filter Bar (shown when Map view is toggled) -->
            <div class="bms-map-legend font-sans" id="bms-map-legend" style="display: none;">
                <button type="button" class="legend-filter-btn active font-sans" data-legend-filter="all" title="Show all estate spots">
                    <span class="legend-badge badge-all"><i class="fa-solid fa-border-all"></i></span>
                    <span>All Spots</span>
                </button>
                <button type="button" class="legend-filter-btn font-sans" data-legend-filter="available" title="Filter to available chalets for dates">
                    <span class="legend-badge badge-available"></span>
                    <strong style="color: #16A34A;">Available</strong>
                </button>
                <button type="button" class="legend-filter-btn font-sans" data-legend-filter="booked" title="Filter to booked chalets">
                    <span class="legend-badge badge-booked"></span>
                    <strong style="color: #DC2626;">Booked / Reserved</strong>
                </button>
                <?php if ($has_single): ?>
                <button type="button" class="legend-filter-btn font-sans" data-legend-filter="single" title="Filter to single cottages">
                    <span class="legend-badge badge-single"><i class="fa-solid fa-house-chimney"></i></span>
                    <span>Single Cottage</span>
                </button>
                <?php endif; ?>
                <?php if ($has_duplex): ?>
                <button type="button" class="legend-filter-btn font-sans" data-legend-filter="duplex" title="Filter to 2-room duplex chalets">
                    <span class="legend-badge badge-duplex"><i class="fa-solid fa-layer-group"></i></span>
                    <span>Duplex Chalet (2 Suites)</span>
                </button>
                <?php endif; ?>
                <button type="button" class="legend-filter-btn font-sans" data-legend-filter="facilities" title="Filter to dining, plunge pool & estate amenities">
                    <span class="legend-badge badge-facility"><i class="fa-solid fa-utensils"></i></span>
                    <span>Estate Facilities</span>
                </button>
            </div>

            <!-- Live Stay Date & Availability Summary Bar -->
            <div id="bms-live-date-status-bar" class="bms-live-date-bar font-sans" style="background: #10261A; border: 1.5px solid rgba(197, 160, 89, 0.45); border-radius: 10px; padding: 12px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.12);">
                <div style="display: flex; align-items: center; gap: 12px; color: #EAEFED; font-size: 13.5px; flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: rgba(197, 160, 89, 0.22); color: var(--accent-gold); font-size: 13px;">
                        <i class="fa-solid fa-calendar-days"></i>
                    </span>
                    <span>Live Estate Availability for: <strong id="bms-live-dates-txt" style="color: var(--accent-gold); letter-spacing: 0.3px;">Loading stay dates...</strong></span>
                    <button type="button" id="btn-restore-recommendation" class="btn-restore-rec font-sans" style="display: none; background: rgba(22, 163, 74, 0.2); border: 1px solid #16A34A; color: #4ADE80; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> <span>View Group Suggestions</span>
                    </button>
                </div>
                <div id="bms-live-counts-wrap" style="display: flex; align-items: center; gap: 16px; font-size: 13px;">
                    <span style="color: #4ADE80; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-circle-check"></i> <span id="bms-count-avail">Available</span></span>
                    <span style="color: #F87171; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-ban"></i> <span id="bms-count-booked">Reserved</span></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main View Content Area (Map View & Grid View) -->
    <section class="booking-view-container">
        <div class="container">

            <!-- Group Recommendation Drawer Style Fallback -->
            <style>
            .bms-group-rec-drawer {
                background: linear-gradient(135deg, rgba(16, 38, 26, 0.98), rgba(11, 26, 18, 0.99)) !important;
                border: 1.5px solid rgba(197, 160, 89, 0.55) !important;
                border-radius: 14px !important;
                padding: 22px 24px !important;
                margin-bottom: 24px !important;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35), 0 0 20px rgba(197, 160, 89, 0.15) !important;
                position: relative !important;
                overflow: hidden !important;
                color: #FFFFFF !important;
            }
            .bms-group-rec-drawer::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 3px;
                background: linear-gradient(90deg, #10B981, #C5A059, #38BDF8, #10B981);
                background-size: 200% 100%;
                animation: shimmerRecGradient 4s linear infinite;
            }
            @keyframes shimmerRecGradient {
                0% { background-position: 100% 0; }
                100% { background-position: -100% 0; }
            }
            .bms-rec-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 16px;
                margin-bottom: 18px;
                border-bottom: 1px solid rgba(197, 160, 89, 0.2);
                padding-bottom: 14px;
            }
            .bms-rec-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: rgba(197, 160, 89, 0.18);
                border: 1px solid rgba(197, 160, 89, 0.45);
                color: #C5A059;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.8px;
                text-transform: uppercase;
                padding: 4px 10px;
                border-radius: 20px;
                margin-bottom: 6px;
            }
            .bms-rec-title {
                color: #F8FAF8;
                font-size: 1.45rem;
                margin: 4px 0 6px;
                font-weight: 700;
                font-family: var(--font-primary, serif);
            }
            .bms-rec-subtitle {
                color: #C2D1C9;
                font-size: 13.5px;
                margin: 0;
                line-height: 1.5;
            }
            .bms-rec-subtitle strong {
                color: #C5A059;
            }
            .btn-rec-action-dismiss {
                background: linear-gradient(135deg, rgba(197, 160, 89, 0.22) 0%, rgba(197, 160, 89, 0.10) 100%);
                border: 1.5px solid #C5A059;
                color: #FFFFFF;
                font-size: 13px;
                font-weight: 700;
                letter-spacing: 0.2px;
                padding: 9px 18px;
                border-radius: 50px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 9px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4), 0 0 16px rgba(197, 160, 89, 0.3);
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .btn-rec-action-dismiss i {
                color: #C5A059;
                font-size: 14px;
                transition: transform 0.25s ease, color 0.25s ease;
            }
            .btn-rec-action-dismiss:hover {
                background: #C5A059;
                border-color: #D4AF37;
                color: #0A1C12;
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(197, 160, 89, 0.55);
            }
            .btn-rec-action-dismiss:hover i {
                color: #0A1C12;
                transform: scale(1.15);
            }
            .btn-rec-action-dismiss:active {
                transform: translateY(0);
            }
            .bms-rec-options-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 16px;
                margin-bottom: 18px;
            }
            .rec-option-card {
                background: rgba(17, 35, 24, 0.9);
                border: 1.5px solid rgba(197, 160, 89, 0.3);
                border-radius: 12px;
                padding: 16px;
                cursor: pointer;
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                box-sizing: border-box;
            }
            .rec-option-card:hover {
                border-color: #C5A059;
                background: rgba(25, 48, 34, 0.98);
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
            }
            .rec-option-card.is-active-rec {
                border: 2px solid #4ADE80 !important;
                background: linear-gradient(145deg, rgba(22, 54, 36, 0.98), rgba(16, 40, 28, 0.98)) !important;
                box-shadow: 0 0 20px rgba(74, 222, 128, 0.3), 0 4px 14px rgba(0,0,0,0.4) !important;
            }
            .rec-option-card.is-active-rec::after {
                content: 'ACTIVE SPOTLIGHT';
                position: absolute;
                top: -10px;
                right: 14px;
                background: #16A34A;
                color: #FFFFFF;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.6px;
                padding: 2px 8px;
                border-radius: 10px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            }
            .rec-card-top-tag {
                font-size: 11px;
                font-weight: 700;
                color: #C5A059;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 6px;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .rec-card-title {
                color: #F8FAF8;
                font-size: 15px;
                font-weight: 700;
                margin: 0 0 8px;
                line-height: 1.35;
            }
            .rec-card-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 10px;
            }
            .rec-chalet-chip {
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.15);
                color: #E2E8F0;
                font-size: 11px;
                font-weight: 600;
                padding: 3px 8px;
                border-radius: 6px;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .rec-chalet-chip.duplex-chip {
                background: rgba(197, 160, 89, 0.15);
                border-color: rgba(197, 160, 89, 0.4);
                color: #F3E5AB;
            }
            .rec-card-meta {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding-top: 10px;
                margin-top: 6px;
            }
            .rec-card-cap {
                font-size: 12px;
                color: #CBD5E1;
            }
            .rec-card-cap strong {
                color: #4ADE80;
            }
            .rec-card-price {
                text-align: right;
            }
            .rec-card-price-num {
                font-size: 17px;
                font-weight: 800;
                color: #C5A059;
                display: block;
                line-height: 1.1;
                font-family: var(--font-secondary, sans-serif);
            }
            .rec-card-price-period {
                font-size: 10.5px;
                color: #94A3B8;
            }
            .bms-rec-active-bar {
                background: rgba(12, 27, 18, 0.95);
                border: 1px solid rgba(74, 222, 128, 0.4);
                border-radius: 10px;
                padding: 12px 18px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
            }
            .bms-rec-active-summary {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
                font-size: 13px;
                color: #E2E8F0;
            }
            .rec-active-label {
                color: #4ADE80;
                font-weight: 700;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .rec-active-meta {
                background: rgba(255, 255, 255, 0.08);
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 11.5px;
                color: #94A3B8;
            }
            .btn-rec-book-combo {
                background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
                color: #FFFFFF;
                border: none;
                font-size: 13.5px;
                font-weight: 700;
                padding: 10px 22px;
                border-radius: 8px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
                transition: all 0.2s ease;
            }
            .btn-rec-book-combo:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 18px rgba(34, 197, 94, 0.5);
            }
            </style>

            <!-- Smart Auto-Suggestion / Recommendation Drawer for Large Groups & Multi-Cottage Stays -->
            <div id="bms-group-recommendation-panel" class="bms-group-rec-drawer font-sans" style="display: none;">
                <div class="bms-rec-header">
                    <div class="bms-rec-header-left">
                        <span class="bms-rec-badge font-sans">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> SMART GROUP AUTO-SUGGESTION
                        </span>
                        <h3 class="bms-rec-title font-serif">Curated Chalet Combinations for Your Group</h3>
                        <p class="bms-rec-subtitle font-sans" id="bms-rec-subtitle-txt">
                            To comfortably accommodate your group of <strong>14 Guests</strong> together, we recommend adjacent clustered chalets on the estate. Non-suggested properties are dimmed on the map.
                        </p>
                    </div>
                    <div class="bms-rec-header-actions">
                        <button type="button" id="btn-rec-dismiss-toggle" class="btn-rec-action-dismiss font-sans" title="Show all properties on the map and choose rooms manually">
                            <i class="fa-solid fa-eye"></i> <span>Show All Chalets (Dismiss Suggestion)</span>
                        </button>
                    </div>
                </div>

                <!-- Combination Option Cards Carousel/Grid -->
                <div class="bms-rec-options-grid" id="bms-rec-options-grid">
                    <!-- Populated dynamically by booking_controller.js -->
                </div>

                <!-- Active Option Floating Control Bar -->
                <div class="bms-rec-active-bar" id="bms-rec-active-bar">
                    <div class="bms-rec-active-summary">
                        <span class="rec-active-label"><i class="fa-solid fa-crosshairs"></i> Currently Spotlighting on Map:</span>
                        <strong id="bms-rec-active-title" style="color: var(--accent-gold);">Option 1: 2 Adjacent Duplex Chalets</strong>
                        <span id="bms-rec-active-meta" class="rec-active-meta">16 Guests Max • 4 Master Suites • Upper Ridge</span>
                    </div>
                    <div class="bms-rec-active-ctas">
                        <button type="button" id="btn-rec-book-active" class="btn-rec-book-combo font-sans">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span id="btn-rec-book-txt">Book Recommended Combination (2 Chalets)</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- 1. MAP VIEW LAYOUT (Secondary View) -->
            <div class="booking-map-layout" id="booking-map-layout" style="display: none;">
                
                <div class="bms-map-canvas-card">
                    <!-- Topographic SVG Background Canvas -->
                    <svg class="bms-topo-svg" viewBox="0 0 800 520" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <radialGradient id="bms-forest-glow" cx="50%" cy="50%" r="70%">
                                <stop offset="0%" stop-color="#193322" stop-opacity="0.98" />
                                <stop offset="60%" stop-color="#112318" stop-opacity="0.99" />
                                <stop offset="100%" stop-color="#0a160f" stop-opacity="1" />
                            </radialGradient>
                            <linearGradient id="bms-stream-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#64dfdf" stop-opacity="0.85" />
                                <stop offset="50%" stop-color="#48bfe3" stop-opacity="0.9" />
                                <stop offset="100%" stop-color="#1e6091" stop-opacity="0.85" />
                            </linearGradient>
                            <filter id="bms-glow" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur stdDeviation="3" result="blur" />
                                <feComposite in="SourceGraphic" in2="blur" operator="over" />
                            </filter>
                        </defs>

                        <!-- Base Map Terrain -->
                        <rect width="800" height="520" fill="url(#bms-forest-glow)" />

                        <!-- Topographic Contour Lines -->
                        <g class="bms-contours" stroke="rgba(197, 160, 89, 0.18)" fill="none" stroke-width="1.2">
                            <path d="M -20,440 Q 150,480 320,430 T 650,470 T 820,420" />
                            <path d="M -20,380 Q 180,410 340,360 T 670,390 T 820,350" stroke="rgba(197, 160, 89, 0.24)" />
                            <path d="M -20,320 Q 160,350 350,300 T 630,320 T 820,290" />
                            <path d="M -20,260 Q 200,290 400,240 T 650,260 T 820,220" stroke="rgba(197, 160, 89, 0.3)" />
                            <path d="M -20,200 Q 180,220 420,170 T 680,190 T 820,150" stroke="rgba(197, 160, 89, 0.45)" stroke-width="1.8" />
                            <path d="M -20,140 Q 220,170 450,110 T 700,130 T 820,90" />
                            <path d="M -20,80 Q 240,110 470,60 T 720,80 T 820,30" stroke="rgba(197, 160, 89, 0.24)" />
                            <path d="M 280,-20 Q 420,70 560,-20" stroke="rgba(197, 160, 89, 0.38)" />
                            <path d="M 330,-20 Q 430,45 520,-20" stroke="rgba(197, 160, 89, 0.5)" stroke-width="1.5" />
                        </g>

                        <!-- Luxury Navigational Compass Rose -->
                        <g class="bms-topo-compass" transform="translate(710, 68)" pointer-events="none">
                            <circle cx="0" cy="0" r="28" fill="rgba(8, 20, 14, 0.85)" stroke="rgba(197, 160, 89, 0.5)" stroke-width="1.2" filter="url(#bms-glow)" />
                            <circle cx="0" cy="0" r="23" fill="none" stroke="rgba(197, 160, 89, 0.35)" stroke-width="0.8" stroke-dasharray="2,2" />
                            <!-- 8 Compass Star Points -->
                            <!-- North Point (Gold Primary Needle) -->
                            <polygon points="0,-23 5,-4 0,-1" fill="#D4AF37" />
                            <polygon points="0,-23 -5,-4 0,-1" fill="#FFF2B2" />
                            <!-- South Point -->
                            <polygon points="0,23 5,4 0,1" fill="rgba(197, 160, 89, 0.45)" />
                            <polygon points="0,23 -5,4 0,1" fill="rgba(197, 160, 89, 0.25)" />
                            <!-- East Point -->
                            <polygon points="23,0 4,5 1,0" fill="rgba(197, 160, 89, 0.45)" />
                            <polygon points="23,0 4,-5 1,0" fill="rgba(197, 160, 89, 0.25)" />
                            <!-- West Point -->
                            <polygon points="-23,0 -4,5 -1,0" fill="rgba(197, 160, 89, 0.45)" />
                            <polygon points="-23,0 -4,-5 -1,0" fill="rgba(197, 160, 89, 0.25)" />
                            <!-- Diagonal Points -->
                            <polygon points="12,-12 3,-3 0,0" fill="rgba(197, 160, 89, 0.3)" />
                            <polygon points="-12,-12 -3,-3 0,0" fill="rgba(197, 160, 89, 0.3)" />
                            <polygon points="12,12 3,3 0,0" fill="rgba(197, 160, 89, 0.2)" />
                            <polygon points="-12,12 -3,3 0,0" fill="rgba(197, 160, 89, 0.2)" />
                            <!-- Center Pivot Core -->
                            <circle cx="0" cy="0" r="4" fill="#0c1d14" stroke="#D4AF37" stroke-width="1.5" />
                            <circle cx="0" cy="0" r="1.8" fill="#FFF2B2" />
                            <!-- Direction Letters -->
                            <text x="0" y="-30" text-anchor="middle" fill="#D4AF37" font-family="'Cinzel', Georgia, serif" font-size="10" font-weight="bold" letter-spacing="1">N</text>
                            <text x="0" y="38" text-anchor="middle" fill="rgba(197, 160, 89, 0.65)" font-family="'Cinzel', Georgia, serif" font-size="7" font-weight="bold">S</text>
                            <text x="35" y="3" text-anchor="middle" fill="rgba(197, 160, 89, 0.65)" font-family="'Cinzel', Georgia, serif" font-size="7" font-weight="bold">E</text>
                            <text x="-35" y="3" text-anchor="middle" fill="rgba(197, 160, 89, 0.65)" font-family="'Cinzel', Georgia, serif" font-size="7" font-weight="bold">W</text>
                        </g>

                        <!-- Decorative Forest Clusters -->
                        <g fill="rgba(64, 115, 84, 0.4)">
                            <circle cx="110" cy="180" r="14" /><circle cx="130" cy="190" r="11" /><circle cx="95" cy="195" r="9" />
                            <circle cx="670" cy="240" r="16" /><circle cx="690" cy="255" r="12" /><circle cx="650" cy="260" r="10" />
                            <circle cx="210" cy="420" r="15" /><circle cx="230" cy="435" r="11" />
                            <circle cx="610" cy="90" r="14" /><circle cx="630" cy="105" r="10" />
                        </g>

                        <!-- Contour Elevation Labels -->
                        <text x="685" y="185" fill="rgba(197, 160, 89, 0.6)" font-size="10" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,600M MSL</text>
                        <text x="685" y="125" fill="rgba(197, 160, 89, 0.45)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,620M MSL</text>
                        <text x="685" y="385" fill="rgba(197, 160, 89, 0.45)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,580M MSL</text>

                        <!-- Dynamic Custom Routes / Walking Trails -->
                        <?php if (!empty($map_routes)): ?>
                            <?php foreach ($map_routes as $r): ?>
                                <?php if (!empty($r['svg_d'])): 
                                    $dash = ($r['stroke_type'] === 'solid') ? 'none' : (($r['stroke_type'] === 'dotted') ? '3,4' : '9,6');
                                    $w = floatval($r['line_width'] ?? 3.2);
                                    $col = $r['color'] ?? '#D4AF37';
                                ?>
                                    <!-- Trail Glowing Aura -->
                                    <path d="<?php echo $r['svg_d']; ?>" 
                                          fill="none" stroke="<?php echo $col; ?>" stroke-opacity="0.3" stroke-width="<?php echo $w * 2.8; ?>" stroke-linecap="round" stroke-linejoin="round" filter="url(#bms-glow)" />
                                    <!-- Paved Golden Trail Line -->
                                    <path d="<?php echo $r['svg_d']; ?>" 
                                          fill="none" stroke="<?php echo $col; ?>" stroke-width="<?php echo $w; ?>" stroke-dasharray="<?php echo $dash; ?>" stroke-linecap="round" stroke-linejoin="round" />
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Main Entrance Landmark Gate -->
                        <?php if (!empty($map_entrance['enabled'])): ?>
                            <g transform="translate(<?php echo $map_entrance['svg_x']; ?>, <?php echo $map_entrance['svg_y']; ?>)">
                                <circle cx="0" cy="0" r="7" fill="#112318" stroke="#D4AF37" stroke-width="2.2" filter="url(#bms-glow)" />
                                <circle cx="0" cy="0" r="2.8" fill="#64dfdf" />
                                <text x="14" y="3.5" fill="#D4AF37" font-size="9" font-family="'Cinzel', Georgia, serif" font-weight="bold" letter-spacing="1"><?php echo e($map_entrance['label']); ?></text>
                            </g>
                        <?php endif; ?>

                        <!-- Estate Exit Landmark Gate -->
                        <?php if (!empty($map_exit['enabled'])): ?>
                            <g transform="translate(<?php echo $map_exit['svg_x']; ?>, <?php echo $map_exit['svg_y']; ?>)">
                                <circle cx="0" cy="0" r="7" fill="#112318" stroke="#E67E22" stroke-width="2.2" filter="url(#bms-glow)" />
                                <circle cx="0" cy="0" r="2.8" fill="#E67E22" />
                                <text x="14" y="3.5" fill="#E67E22" font-size="9" font-family="'Cinzel', Georgia, serif" font-weight="bold" letter-spacing="1"><?php echo e($map_exit['label']); ?></text>
                            </g>
                        <?php endif; ?>

                        <!-- Custom Waypoints (Road geometry is rendered above, waypoint pins hidden on frontend) -->
                    </svg>

                    <!-- Luxury Compass Rose -->
                    <div class="map-compass font-serif">
                        <span class="compass-n">N</span>
                        <div class="compass-pointer"></div>
                        <span class="compass-coords">KANTHALLOOR · 1,600M</span>
                    </div>

                    <!-- Interactive Chalet & Facility Spot Elements (BookMyShow Style) -->
                    <div class="bms-chalets-container" id="bms-chalets-container">
                        <?php 
                        foreach ($sanctuary_spots as $idx => $sp):
                            $is_stay = !empty($sp['is_stay']) || ($sp['category'] ?? '') === 'stays';
                            $slug = $sp['linked_room_slug'] ?? 'treehouse';
                            $rate = (float)($sp['room_rate'] ?? $sp['stay_price'] ?? 14500);
                            $struct = $sp['structure_type'] ?? 'single_hut';
                            $is_duplex = ($is_stay && ($struct === 'duplex_hut' || stripos($sp['title'], 'duplex') !== false));
                            
                            // Check linked room's base availability status
                            $linked_rm_status = 'available';
                            if ($is_stay) {
                                foreach ($rooms as $rm_obj) {
                                    $rm_slug = strtolower($rm_obj['slug']);
                                    $sp_slug = strtolower($slug);
                                    if ($rm_slug === $sp_slug || strpos($rm_slug, $sp_slug) !== false || strpos($sp_slug, $rm_slug) !== false) {
                                        $linked_rm_status = $rm_obj['availability_status'] ?? (!empty($rm_obj['is_available']) ? 'available' : 'maintenance');
                                        break;
                                    }
                                }
                            }
                            
                            if ($is_stay && $linked_rm_status !== 'available') {
                                $status = $linked_rm_status;
                            } else {
                                $status = ($idx === 1 || $idx === 2) ? 'available' : ($idx === 3 ? 'fast_filling' : 'available');
                            }
                            
                            $pin_col = !empty($sp['pin_color']) ? $sp['pin_color'] : ($is_stay ? ($is_duplex ? '#06B6D4' : '#10B981') : '#F59E0B');
                            if ($status === 'closed_renovation') $pin_col = '#F59E0B';
                            elseif ($status === 'opening_soon') $pin_col = '#38BDF8';
                            elseif ($status === 'maintenance') $pin_col = '#EF4444';

                            $custom_icon = !empty($sp['icon_class']) ? $sp['icon_class'] : ($is_duplex ? 'fa-solid fa-layer-group' : ($is_stay ? 'fa-solid fa-house-chimney' : ($sp['category'] === 'dining' ? 'fa-solid fa-utensils' : 'fa-solid fa-tree')));

                            // Map stay types
                            $stay_cat = 'treehouse';
                            if (stripos($sp['title'], 'mudhouse') !== false) $stay_cat = 'mudhouse';
                            if (stripos($sp['title'], 'woodhouse') !== false) $stay_cat = 'woodhouse';

                            $y_pos = (float)$sp['y_coord'];
                            $x_pos = (float)$sp['x_coord'];
                            $pos_classes = [];
                            if ($y_pos <= 45.0) {
                                $pos_classes[] = 'pos-bottom';
                            }
                            if ($x_pos <= 25.0) {
                                $pos_classes[] = 'pos-left';
                            } elseif ($x_pos >= 75.0) {
                                $pos_classes[] = 'pos-right';
                            }
                            $pos_class_str = implode(' ', $pos_classes);
                        ?>
                            <div class="bms-chalet-node <?php echo $is_stay ? ($is_duplex ? 'node-stay node-duplex' : 'node-stay node-single') : 'node-facility'; ?> status-<?php echo $status; ?> <?php echo $pos_class_str; ?> <?php echo ($preselect_slug === $slug && $is_stay) ? 'is-selected' : ''; ?>"
                                 id="chalet-node-<?php echo (int)$sp['id']; ?>"
                                 data-spot-id="<?php echo (int)$sp['id']; ?>"
                                 data-slug="<?php echo htmlspecialchars($slug); ?>"
                                 data-is-stay="<?php echo $is_stay ? '1' : '0'; ?>"
                                 data-is-duplex="<?php echo $is_duplex ? '1' : '0'; ?>"
                                 data-stay-cat="<?php echo htmlspecialchars($stay_cat); ?>"
                                 data-structure="<?php echo htmlspecialchars($struct); ?>"
                                 data-title="<?php echo htmlspecialchars($sp['title']); ?>"
                                 data-rate="<?php echo $rate; ?>"
                                 data-single-rate="<?php echo (float)($sp['single_room_rate'] ?? $rate); ?>"
                                 data-status="<?php echo $status; ?>"
                                 data-avail-status="<?php echo htmlspecialchars($linked_rm_status); ?>"
                                 style="top: <?php echo $y_pos; ?>%; left: <?php echo $x_pos; ?>%; --node-accent: <?php echo $pin_col; ?>;">
                                
                                <div class="node-halo" style="background: <?php echo $pin_col; ?>; opacity: 0.35;"></div>
                                <div class="node-box" style="border-color: <?php echo $pin_col; ?>; box-shadow: 0 0 14px <?php echo $pin_col; ?>55;">
                                    <div class="node-icon" style="color: <?php echo $pin_col; ?>;" title="<?php echo htmlspecialchars($sp['title']); ?>">
                                        <i class="<?php echo htmlspecialchars($custom_icon); ?>"></i>
                                    </div>
                                    <div class="node-code font-serif"><?php echo sprintf('%02d', $sp['spot_number']); ?></div>
                                    <?php if ($is_duplex): ?>
                                        <span class="node-duplex-pill" style="background: <?php echo $pin_col; ?>; color: #fff;">2S</span>
                                    <?php endif; ?>
                                    <?php if ($is_stay): ?>
                                        <?php if ($status === 'closed_renovation'): ?>
                                            <span class="node-status-dot" style="background: #F59E0B; box-shadow: 0 0 8px #F59E0B;" title="Closed for Renovation"></span>
                                        <?php elseif ($status === 'opening_soon'): ?>
                                            <span class="node-status-dot" style="background: #38BDF8; box-shadow: 0 0 8px #38BDF8;" title="Opening Soon"></span>
                                        <?php elseif ($status === 'maintenance'): ?>
                                            <span class="node-status-dot" style="background: #EF4444; box-shadow: 0 0 8px #EF4444;" title="Offline / Maintenance"></span>
                                        <?php else: ?>
                                            <span class="node-status-dot status-dot-<?php echo $status; ?>"></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Floating Mini Badge -->
                                <div class="node-hover-card font-sans">
                                    <span class="nhc-type"><?php echo $is_duplex ? '🏰 DUPLEX RESIDENCE (2 SUITES)' : ($is_stay ? '🏡 SINGLE COTTAGE' : '🌿 ESTATE HUB'); ?></span>
                                    <span class="nhc-title"><?php echo htmlspecialchars($sp['title']); ?></span>
                                    <?php if ($is_stay): ?>
                                        <span class="nhc-rate">From ₹<?php echo number_format($rate, 0, '.', ','); ?>/night</span>
                                        <?php if ($status === 'closed_renovation'): ?>
                                            <span class="nhc-status status-label-renovation" style="color: #F59E0B;">
                                                <i class="fa-solid fa-person-digging"></i> Closed for Renovation
                                            </span>
                                        <?php elseif ($status === 'opening_soon'): ?>
                                            <span class="nhc-status status-label-opening-soon" style="color: #38BDF8;">
                                                <i class="fa-solid fa-sparkles"></i> Opening Soon
                                            </span>
                                        <?php elseif ($status === 'maintenance'): ?>
                                            <span class="nhc-status status-label-booked" style="color: #EF4444;">
                                                <i class="fa-solid fa-wrench"></i> Offline / Maintenance
                                            </span>
                                        <?php else: ?>
                                            <span class="nhc-status status-label-<?php echo $status; ?>">
                                                <i class="fa-solid fa-circle-check"></i> <?php echo ($status === 'fast_filling') ? 'Fast Filling' : 'Available for Dates'; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span class="nhc-cta"><?php echo ($status === 'closed_renovation' || $status === 'opening_soon' || $status === 'maintenance') ? 'Click to View Status Notice &rarr;' : 'Click to View Details & Rates &rarr;'; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Side: Quick Property Inspector & Booking Sidebar -->
                <div class="bms-sidebar-inspector" id="bms-sidebar-inspector">
                    <div class="sidebar-empty-state" id="sidebar-empty-state" style="display: none;">
                        <i class="fa-solid fa-compass-drafting" style="font-size: 36px; color: var(--accent-gold); margin-bottom: 12px;"></i>
                        <h4 class="font-serif">Select a Chalet on the Map</h4>
                        <p class="font-sans">Click on any cottage pin across the estate map to view photos, live rates, and configuration options.</p>
                    </div>

                    <div class="sidebar-active-card" id="sidebar-active-card">
                        <div class="sac-badge-row">
                            <span class="sac-type-pill font-sans" id="sac-type-pill"><i class="fa-solid fa-house-chimney"></i> BOOKABLE CHALET</span>
                            <span class="sac-avail-pill font-sans" id="sac-avail-pill"><i class="fa-solid fa-circle"></i> Available</span>
                        </div>

                        <div class="sac-gallery-wrap" style="position: relative;">
                            <img src="assets/images/01 (25).jpeg" alt="Chalet" id="sac-main-img" class="sac-img">
                            <div class="sac-photo-caption font-sans" id="sac-photo-caption" style="display: none; position: absolute; bottom: 8px; left: 10px; max-width: calc(100% - 110px); background: rgba(7, 16, 11, 0.82); backdrop-filter: blur(4px); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(197, 160, 89, 0.35); font-size: 11px; color: #FFFFFF; z-index: 5; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; pointer-events: none;"></div>
                            <div class="sac-gallery-nav">
                                <button type="button" class="sac-nav-btn" id="sac-gal-prev"><i class="fa-solid fa-chevron-left"></i></button>
                                <span class="sac-gal-indicator font-sans" id="sac-gal-indicator">1 / 3</span>
                                <button type="button" class="sac-nav-btn" id="sac-gal-next"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>

                        <div class="sac-details">
                            <h3 class="sac-title font-serif" id="sac-title">High-Altitude Canopy Treehouse</h3>
                            <p class="sac-desc font-sans" id="sac-desc">
                                Elevated living among towering mountain trees with panoramic glass valley windows and private balcony.
                            </p>

                            <!-- Live Reservation Conflict Warning in Sidebar -->
                            <div id="sac-booked-warning" class="sac-booked-warning-box font-sans" style="display: none;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <div>
                                    <strong style="color: #991B1B; font-size: 13.5px; display: block;">Chalet Already Reserved</strong>
                                    <p id="sac-booked-warning-msg" style="margin: 3px 0 6px; font-size: 12px; color: #B91C1C; line-height: 1.45;">
                                        We apologize, but this property has already been reserved for your selected stay dates (via Direct Website, MakeMyTrip, or Airbnb).
                                    </p>
                                    <span style="font-size: 11.5px; color: #7F1D1D; font-weight: 600;">Please select alternative dates above or pick another available chalet on the map.</span>
                                </div>
                            </div>

                            <!-- Dynamic Tier / Duplex Option Switcher (if duplex) -->
                            <div class="sac-tier-box" id="sac-tier-box" style="display: none;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                                    <label class="tier-box-label font-sans" style="margin-bottom: 0;"><i class="fa-solid fa-layer-group"></i> Configuration &amp; Rate Option:</label>
                                    <button type="button" class="btn-open-duplex-guide font-sans" onclick="openDuplexExplainer();" style="background: transparent; border: none; color: #56c2c9; font-size: 11px; cursor: pointer; text-decoration: underline; display: flex; align-items: center; gap: 4px; padding: 0;">
                                        <i class="fa-solid fa-circle-question"></i> View Duplex Architecture Guide
                                    </button>
                                </div>
                                <div class="tier-options-grid">
                                    <label class="tier-option-label" id="tier-label-full">
                                        <input type="radio" name="sac_tier_choice" value="full" checked>
                                        <div class="tier-option-content">
                                            <span class="toc-name font-sans">Full Duplex Suite</span>
                                            <span class="toc-rate font-serif" id="toc-rate-full">₹8,000/nt</span>
                                            <small class="toc-note font-sans">Entire 2-Floor Residence (Base 4 Guests)</small>
                                        </div>
                                    </label>
                                    <label class="tier-option-label" id="tier-label-single">
                                        <input type="radio" name="sac_tier_choice" value="single_room">
                                        <div class="tier-option-content">
                                            <span class="toc-name font-sans">Single Master Room</span>
                                            <span class="toc-rate font-serif" id="toc-rate-single">₹4,000/nt</span>
                                            <small class="toc-note font-sans">1 Master Room in Chalet (Base 2 Guests)</small>
                                        </div>
                                    </label>
                                </div>

                                <!-- Duplex Wing Selector (Left Suite vs Right Suite) -->
                                <div id="sac-duplex-wing-box" class="sac-duplex-wing-container font-sans" style="display: none;">
                                    <div class="sac-wing-header">
                                        <label class="sac-wing-title">
                                            <i class="fa-solid fa-door-open"></i> Choose Suite Wing:
                                        </label>
                                        <span id="sac-wing-status-hint" class="sac-wing-status-badge">Both Wings Available</span>
                                    </div>
                                    <div class="duplex-wing-selector-grid">
                                        <label class="duplex-wing-card is-selected" id="sac-wing-card-left" title="Click to select Left Suite (Wing A)">
                                            <input type="radio" name="sac_duplex_wing" value="left" checked class="sac-wing-radio-native">
                                            <div class="wing-card-top">
                                                <div class="wing-card-title-group">
                                                    <span class="wing-radio-indicator"><i class="fa-solid fa-check"></i></span>
                                                    <span class="wing-title-text"><i class="fa-solid fa-door-open" style="color: #059669;"></i> Left (Wing A)</span>
                                                </div>
                                                <div class="wing-badge-group">
                                                    <span class="wing-selected-pill">SELECTED</span>
                                                    <span class="wing-badge available" id="sac-wing-badge-left">Available</span>
                                                </div>
                                            </div>
                                            <p class="wing-desc-text">Garden-facing master suite with private balcony</p>
                                        </label>
                                        <label class="duplex-wing-card" id="sac-wing-card-right" title="Click to select Right Suite (Wing B)">
                                            <input type="radio" name="sac_duplex_wing" value="right" class="sac-wing-radio-native">
                                            <div class="wing-card-top">
                                                <div class="wing-card-title-group">
                                                    <span class="wing-radio-indicator"><i class="fa-solid fa-check"></i></span>
                                                    <span class="wing-title-text"><i class="fa-solid fa-door-open" style="color: #2563EB;"></i> Right (Wing B)</span>
                                                </div>
                                                <div class="wing-badge-group">
                                                    <span class="wing-selected-pill">SELECTED</span>
                                                    <span class="wing-badge available" id="sac-wing-badge-right">Available</span>
                                                </div>
                                            </div>
                                            <p class="wing-desc-text">Valley view master suite with sunset deck</p>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Amenities Summary Pills -->
                            <div class="sac-amenities-row font-sans" id="sac-amenities-row">
                                <span><i class="fa-solid fa-utensils"></i> All Farm Meals Included</span>
                                <span><i class="fa-solid fa-wifi"></i> Forest Wi-Fi</span>
                                <span><i class="fa-solid fa-mug-hot"></i> Organic Tea Ritual</span>
                                <span><i class="fa-solid fa-square-parking"></i> Free Parking</span>
                            </div>

                            <!-- Transparent Rate Policy Card (Stays Only) -->
                            <div class="sac-rates-policy font-sans" id="sac-rates-policy" style="background: rgba(197, 160, 89, 0.12); border: 1px dashed rgba(197, 160, 89, 0.5); border-radius: 8px; padding: 10px 14px; margin: 12px 0; font-size: 11.5px; line-height: 1.5;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; color: #8F6B2A; font-weight: 700;">
                                    <span style="display: flex; align-items: center; gap: 6px;"><i class="fa-solid fa-shield-halved" style="color: var(--accent-gold);"></i> All-Inclusive Sanctuary Tariff</span>
                                    <button type="button" onclick="openAmenitiesGuide();" style="background: none; border: none; color: #1E6B52; font-size: 11px; cursor: pointer; text-decoration: underline; font-weight: 600; padding: 0;">
                                        <i class="fa-solid fa-circle-info"></i> All 8 Amenities
                                    </button>
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px;">
                                    <span style="color: #1E3324;"><i class="fa-solid fa-user-plus" style="color: #9A7B38; margin-right: 4px;"></i> Extra Person: <strong style="color: #0E1C13;">+₹750/nt</strong> (All Meals Incl.)</span>
                                    <span style="color: #166534; font-weight: 700;"><i class="fa-solid fa-child" style="color: #166534; margin-right: 4px;"></i> Kids below 10 years: <strong>Free</strong></span>
                                </div>
                            </div>

                            <!-- Pricing Calculation Box (Stays Only) -->
                            <div class="sac-price-breakdown font-sans" id="sac-price-breakdown">
                                <div class="price-row">
                                    <span id="sac-price-label">Room Rate (1 Night):</span>
                                    <strong id="sac-base-price">₹5,000</strong>
                                </div>
                                <div class="price-row" id="sac-extra-row" style="display: none;">
                                    <span>Extra Guests:</span>
                                    <span id="sac-extra-price">+₹0</span>
                                </div>
                                <div class="price-row total-row">
                                    <span>Total Net Amount:</span>
                                    <strong class="total-val font-serif" id="sac-total-price">₹5,000</strong>
                                </div>
                            </div>

                            <!-- Facility Experience & Information Card (Non-Stay Amenities / Facilities) -->
                            <div class="sac-facility-card font-sans" id="sac-facility-card" style="display: none;">
                                <div class="sfc-header">
                                    <span class="sfc-badge"><i class="fa-solid fa-sparkles"></i> Complimentary Feature</span>
                                    <button type="button" onclick="openAmenitiesGuide();" class="sfc-all-link">
                                        <i class="fa-solid fa-circle-info"></i> All 8 Amenities
                                    </button>
                                </div>
                                <p class="sfc-desc" id="sfc-desc">
                                    This facility is an open sanctuary amenity accessible to all guests staying at our cottages. No separate reservation required.
                                </p>
                                <div class="sfc-highlights" id="sfc-highlights">
                                    <span><i class="fa-regular fa-clock"></i> Open Daily for Guests</span>
                                    <span><i class="fa-solid fa-users"></i> Resident Guests Only</span>
                                    <span><i class="fa-solid fa-gift"></i> Complimentary In-Stay Access</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="sac-actions">
                                <!-- Proceed to Reserve: Only shown for Cottages/Stays -->
                                <button type="button" class="btn-primary sac-book-btn font-sans" id="btn-sac-open-checkout">
                                    <span>Proceed to Reserve</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>

                                <!-- Check Info / More Info: Shown ONLY for Facilities/Amenities (Pool, Campfire, Dining, etc.) -->
                                <button type="button" class="btn-primary sac-info-btn font-sans" id="btn-sac-check-info" style="display: none;">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>More Info</span>
                                </button>

                                <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Concierge,%20I%20am%20interested%20in%20booking%20at%20Food%20Forest." target="_blank" class="btn-outline sac-wa-btn font-sans" id="sac-wa-btn">
                                    <i class="fa-brands fa-whatsapp"></i> <span id="sac-wa-text">WhatsApp Concierge</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 2. GRID VIEW LAYOUT (Primary / Default View) -->
            <div class="booking-grid-layout" id="booking-grid-layout" style="display: block;">
                <div class="chalets-cards-grid">
                    <?php 
                    foreach ($rooms as $rm):
                        $is_dup = ($rm['structure_type'] === 'duplex_hut');
                        $r_rate = (float)$rm['rate_per_night'];
                        $single_rate = (float)($rm['single_room_rate'] ?? $r_rate);
                        $avail_status = $rm['availability_status'] ?? (!empty($rm['is_available']) ? 'available' : 'maintenance');
                        $is_renovation = ($avail_status === 'closed_renovation');
                        $is_opening_soon = ($avail_status === 'opening_soon');
                        $is_maintenance = ($avail_status === 'maintenance' || empty($rm['is_available']));
                        
                        $card_class = '';
                        if ($is_renovation) $card_class = 'is-renovation-card';
                        elseif ($is_opening_soon) $card_class = 'is-opening-soon-card';
                        elseif ($is_maintenance) $card_class = 'is-maintenance-card';
                    ?>
                        <div class="chalet-card font-sans <?php echo $card_class; ?>" data-villa-slug="<?php echo htmlspecialchars($rm['slug']); ?>" data-stay-cat="<?php echo htmlspecialchars($rm['stay_type'] ?? 'treehouse'); ?>" data-structure="<?php echo htmlspecialchars($rm['structure_type'] ?? 'single_hut'); ?>" data-is-duplex="<?php echo $is_dup ? '1' : '0'; ?>" data-avail-status="<?php echo htmlspecialchars($avail_status); ?>">
                            <div class="chalet-card-media" style="position: relative;">
                                <img src="<?php echo htmlspecialchars($rm['image_url']); ?>" alt="<?php echo htmlspecialchars($rm['title']); ?>" onerror="this.src='assets/images/01 (25).jpeg';">
                                <span class="chalet-badge-pill"><?php echo htmlspecialchars($rm['elevation']); ?></span>
                                
                                <?php if ($is_renovation): ?>
                                    <span class="chalet-status-pill status-renovation font-sans" style="position: absolute; top: 10px; left: 10px; background: rgba(217, 119, 6, 0.95); color: #FFFFFF; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); backdrop-filter: blur(4px); display: inline-flex; align-items: center; gap: 5px; z-index: 5;">
                                        <i class="fa-solid fa-person-digging"></i> Closed for Renovation
                                    </span>
                                <?php elseif ($is_opening_soon): ?>
                                    <span class="chalet-status-pill status-opening-soon font-sans" style="position: absolute; top: 10px; left: 10px; background: rgba(2, 132, 199, 0.95); color: #FFFFFF; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); backdrop-filter: blur(4px); display: inline-flex; align-items: center; gap: 5px; z-index: 5;">
                                        <i class="fa-solid fa-sparkles"></i> Opening Soon
                                    </span>
                                <?php elseif ($is_maintenance): ?>
                                    <span class="chalet-status-pill status-maintenance font-sans" style="position: absolute; top: 10px; left: 10px; background: rgba(220, 38, 38, 0.95); color: #FFFFFF; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); backdrop-filter: blur(4px); display: inline-flex; align-items: center; gap: 5px; z-index: 5;">
                                        <i class="fa-solid fa-wrench"></i> Offline for Maintenance
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($rm['photos_list']) && count($rm['photos_list']) > 1): ?>
                                    <span class="chalet-photos-count-badge font-sans" style="position: absolute; bottom: 8px; right: 10px; background: rgba(7, 16, 11, 0.78); color: #FFFFFF; font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; backdrop-filter: blur(4px); border: 1px solid rgba(197, 160, 89, 0.4); display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fa-solid fa-images" style="color: var(--accent-gold);"></i> <?php echo count($rm['photos_list']); ?> Photos
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="chalet-card-body">
                                <div class="chalet-header-row">
                                    <h3 class="chalet-title font-serif"><?php echo htmlspecialchars($rm['title']); ?></h3>
                                    <div class="chalet-price-tag font-serif">
                                        <span class="currency">₹</span><?php echo number_format($r_rate, 0, '.', ','); ?>
                                        <small class="per-night font-sans">/night</small>
                                    </div>
                                </div>

                                <p class="chalet-desc font-sans"><?php echo htmlspecialchars($rm['description']); ?></p>

                                <?php if ($is_dup): ?>
                                    <div class="chalet-duplex-badge font-sans">
                                        <i class="fa-solid fa-layer-group"></i> Duplex Chalet: Single Room available at ₹<?php echo number_format($single_rate, 0, '.', ','); ?>/nt
                                    </div>
                                <?php endif; ?>

                                <div class="chalet-features-row font-sans">
                                    <span><i class="fa-solid fa-users"></i> Base <?php echo (int)($rm['base_guests'] ?? 2); ?> (Max <?php echo (int)($rm['max_guests'] ?? 4); ?>)</span>
                                    <span><i class="fa-solid fa-utensils"></i> All Meals Included</span>
                                    <span><i class="fa-solid fa-wifi"></i> Forest Wi-Fi</span>
                                </div>

                                <div class="chalet-pricing-policy font-sans" style="font-size: 11px; color: #2D4234; margin-top: 6px; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed rgba(28, 56, 38, 0.16); padding-top: 6px;">
                                    <span>Extra Person: <strong style="color: #9A7B38;">+₹<?php echo number_format((float)($rm['extra_guest_rate'] ?? 750), 0); ?>/nt</strong> • Kids below 10: <strong style="color: #047857;">Free</strong></span>
                                    <button type="button" onclick="openAmenitiesGuide();" style="background: none; border: none; color: #1E6B52; font-size: 11px; cursor: pointer; text-decoration: underline; font-weight: 600;">
                                        8 Amenities
                                    </button>
                                </div>

                                <div class="chalet-actions-row" style="margin-top: 10px;">
                                    <?php if ($is_renovation): ?>
                                        <button type="button" class="btn-primary select-from-grid-btn font-sans btn-disabled status-btn-renovation" data-slug="<?php echo htmlspecialchars($rm['slug']); ?>" disabled style="background: rgba(217, 119, 6, 0.25); color: #F59E0B; border: 1px solid rgba(217, 119, 6, 0.5); cursor: not-allowed; width: 100%; justify-content: center;">
                                            <i class="fa-solid fa-person-digging"></i> Closed for Renovation
                                        </button>
                                        <p style="font-size: 11.5px; color: #D97706; margin: 6px 0 0; text-align: center; font-weight: 500;">
                                            Undergoing architectural upgrade. Bookings currently closed.
                                        </p>
                                    <?php elseif ($is_opening_soon): ?>
                                        <button type="button" class="btn-primary select-from-grid-btn font-sans btn-disabled status-btn-opening" data-slug="<?php echo htmlspecialchars($rm['slug']); ?>" disabled style="background: rgba(2, 132, 199, 0.25); color: #38BDF8; border: 1px solid rgba(2, 132, 199, 0.5); cursor: not-allowed; width: 100%; justify-content: center;">
                                            <i class="fa-solid fa-sparkles"></i> Opening Soon
                                        </button>
                                        <p style="font-size: 11.5px; color: #0284C7; margin: 6px 0 0; text-align: center; font-weight: 500;">
                                            New sanctuary dwelling unveiling soon. Reservations opening shortly.
                                        </p>
                                    <?php elseif ($is_maintenance): ?>
                                        <button type="button" class="btn-primary select-from-grid-btn font-sans btn-disabled" data-slug="<?php echo htmlspecialchars($rm['slug']); ?>" disabled style="background: rgba(239, 68, 68, 0.2); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.4); cursor: not-allowed; width: 100%; justify-content: center;">
                                            <i class="fa-solid fa-ban"></i> Offline for Maintenance
                                        </button>
                                        <p style="font-size: 11.5px; color: #DC2626; margin: 6px 0 0; text-align: center; font-weight: 500;">
                                            Temporarily offline for scheduled estate maintenance.
                                        </p>
                                    <?php else: ?>
                                        <button type="button" class="btn-primary select-from-grid-btn font-sans" data-slug="<?php echo htmlspecialchars($rm['slug']); ?>">
                                            <i class="fa-solid fa-calendar-check"></i> Book Chalet
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </section>

</main>

<!-- Injected Data for JS Interactive Controller -->
<script>
window.bookingSanctuarySpots = <?php echo json_encode($sanctuary_spots); ?>;
window.bookingRooms = <?php echo json_encode($rooms); ?>;
window.preselectVillaSlug = <?php echo json_encode($preselect_slug); ?>;
window.conciergeWhatsApp = <?php echo json_encode($concierge_wa); ?>;
</script>
<script src="assets/js/booking_controller.js?v=<?php echo time(); ?>"></script>

<!-- Sanctuary Amenities Quick Modal (Popup inside Booking Page) -->
<div id="amenities-guide-modal" class="booking-modal-overlay" style="display: none; z-index: 99999;">
    <div class="booking-modal-backdrop" onclick="closeAmenitiesGuide();"></div>
    <div class="booking-modal-container" style="max-width: 850px; background: #0E2016; border: 1.5px solid rgba(197, 160, 89, 0.45); border-radius: 16px; padding: 28px; color: #FFFFFF;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(197, 160, 89, 0.25); padding-bottom: 14px; margin-bottom: 20px;">
            <div>
                <span class="font-sans" style="font-size: 11px; font-weight: 700; color: var(--accent-gold); letter-spacing: 1.5px; text-transform: uppercase;">
                    <i class="fa-solid fa-sparkles"></i> MAKEMYTRIP VERIFIED AMENITIES &amp; TARIFFS
                </span>
                <h3 class="font-serif" style="font-size: 1.8rem; margin: 4px 0 0; color: #FFFFFF;">Food Forest Sanctuary Facilities</h3>
            </div>
            <button type="button" onclick="closeAmenitiesGuide();" style="background: rgba(255,255,255,0.1); border: none; color: #FFFFFF; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="amenities-modal-grid font-sans" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; max-height: 60vh; overflow-y: auto; padding-right: 6px;">
            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-hotel"></i> 1. Basic Facilities</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>High-Speed Optical Forest Wi-Fi</li>
                    <li>Free Private Self-Parking &amp; Valet Area</li>
                    <li>24/7 Power Backup (Eco Solar &amp; Inverter)</li>
                    <li>Daily Housekeeping &amp; Turn-down Service</li>
                    <li>Luggage Storage &amp; Porter Assistance</li>
                    <li>RO Purified Natural Spring Drinking Water</li>
                </ul>
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-bell-concierge"></i> 2. Staff &amp; Key Services</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>24/7 Dedicated Concierge &amp; Caretaker</li>
                    <li>Doctor on Call &amp; Medical First-Aid Desk</li>
                    <li>Multilingual Staff (Malayalam, English, Tamil, Hindi)</li>
                    <li>Sightseeing, Jeep Safari &amp; Taxi Desk</li>
                    <li>Express Contactless Check-In / Check-Out</li>
                </ul>
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-spa"></i> 3. Health &amp; Wellness</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>Sunrise Mountain Yoga &amp; Meditation Decks</li>
                    <li>Organic Herbal Steam Inhalation Ritual</li>
                    <li>Zero-Pollution Pure Mountain Air (1,600m MSL)</li>
                    <li>Shinrin-Yoku Forest Bathing Guided Walk</li>
                    <li>River Brook Hydro-Reflexology Pebbled Trail</li>
                </ul>
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-bed"></i> 4. Room Amenities</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>180° Panoramic Mountain &amp; Mist Valley Views</li>
                    <li>Private Cantilevered Timber Sit-Out Balcony</li>
                    <li>Handcrafted Teak &amp; Earthen Cob Architecture</li>
                    <li>100% Breathable Organic Cotton Linen &amp; Quilts</li>
                    <li>Electric Kettle with Heirloom Herbal Tea &amp; Coffee Kit</li>
                </ul>
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-utensils"></i> 5. Food &amp; Drink (Gastronomy)</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li><strong>All 4 Farm Meals Included</strong> in Base Tariff</li>
                    <li>Woodfire Hearth Dining Pavilion &amp; Orchard Gazebo</li>
                    <li>100% Farm-to-Table Organic Kerala Feasts</li>
                    <li>High-Range Evening Plantation Chai Ritual</li>
                    <li>Campfire Barbecue on Request</li>
                </ul>
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-shower"></i> 6. Bathroom &amp; Hygiene</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>24h Eco Solar &amp; Wood-Fired Hot Water</li>
                    <li>Private En-Suite Natural River Stone Bathroom</li>
                    <li>Handcrafted Botanical Herbal Toiletries</li>
                    <li>Western Ceramic WC &amp; Health Jet</li>
                    <li>Plush Organic Cotton Towels &amp; Mats</li>
                </ul>
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-shield-halved"></i> 7. Safety &amp; Security</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>Gated 10-Acre Perimeter with Solar Fencing</li>
                    <li>24/7 Resident Caretaker &amp; Night Security Patrol</li>
                    <li>Fire Extinguishers &amp; Solar Emergency Lighting</li>
                    <li>Illuminated Stone Pathways with Guiding Lanterns</li>
                </ul>
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px;">
                <h4 style="color: var(--accent-gold); font-size: 13.5px; margin-bottom: 8px;"><i class="fa-solid fa-tree"></i> 8. Common Area &amp; Grounds</h4>
                <ul style="font-size: 12px; color: #D3E0D8; line-height: 1.6; padding-left: 18px; margin: 0;">
                    <li>10-Acre Certified Organic Apple &amp; Orange Orchards</li>
                    <li>Natural Mountain Brook &amp; Wooden Footbridge</li>
                    <li>Twilight Campfire Glade &amp; Stargazing Firepit</li>
                    <li>Living Forest Lounge &amp; Botanical Library</li>
                </ul>
            </div>
        </div>

        <div style="margin-top: 18px; display: flex; justify-content: flex-end;">
            <button type="button" class="btn-primary font-sans" onclick="closeAmenitiesGuide();" style="padding: 10px 24px;">
                <span>Understood &amp; Close</span>
            </button>
        </div>
    </div>
</div>

<script>
function openAmenitiesGuide() {
    var m = document.getElementById('amenities-guide-modal');
    if (m) {
        m.style.display = 'flex';
        m.style.opacity = '1';
        m.style.pointerEvents = 'auto';
        m.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}
function closeAmenitiesGuide() {
    var m = document.getElementById('amenities-guide-modal');
    if (m) {
        m.classList.remove('active');
        m.style.display = 'none';
        m.style.opacity = '0';
        m.style.pointerEvents = 'none';
        document.body.style.overflow = '';
    }
}
window.openAmenitiesGuide = openAmenitiesGuide;
window.closeAmenitiesGuide = closeAmenitiesGuide;
</script>

<!-- Sanctuary Facility & Amenity Detail Modal (More Info / Check Info Popup) -->
<div id="facility-info-modal" class="booking-modal-overlay" style="display: none; z-index: 99999;" aria-hidden="true" role="dialog">
    <div class="booking-modal-backdrop" onclick="closeFacilityInfoModal();"></div>
    <div class="booking-modal-container font-sans" style="max-width: 680px; background: #0E2016; border: 1.5px solid rgba(197, 160, 89, 0.45); border-radius: 16px; padding: 0; color: #FFFFFF; overflow: hidden; display: flex; flex-direction: column;">
        
        <!-- Header Image & Hero Overlay -->
        <div style="position: relative; width: 100%; height: 240px; overflow: hidden; background: #07130C;">
            <img id="fim-img" src="assets/images/01 (10).jpeg" alt="Facility Image" style="width: 100%; height: 100%; object-fit: cover;">
            <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(7,19,12,0.2) 0%, rgba(7,19,12,0.85) 100%);"></div>
            
            <button type="button" onclick="closeFacilityInfoModal();" style="position: absolute; top: 14px; right: 14px; background: rgba(0,0,0,0.55); border: 1px solid rgba(255,255,255,0.2); width: 34px; height: 34px; border-radius: 50%; color: #FFFFFF; cursor: pointer; display: flex; align-items: center; justify-content: center;" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div style="position: absolute; bottom: 16px; left: 20px; right: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                    <span id="fim-badge" style="background: rgba(86, 194, 201, 0.25); border: 1px solid rgba(86, 194, 201, 0.6); color: #56C2C9; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;">
                        <i class="fa-solid fa-water"></i> ESTATE AMENITY
                    </span>
                    <span id="fim-spot-num" style="background: rgba(197, 160, 89, 0.2); border: 1px solid rgba(197, 160, 89, 0.5); color: var(--accent-gold); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;">
                        SPOT 15
                    </span>
                </div>
                <h3 class="font-serif" id="fim-title" style="font-size: 1.55rem; color: #FFFFFF; margin: 0; text-shadow: 0 2px 8px rgba(0,0,0,0.6);">Natural Plunge Pool &amp; Spring Bath</h3>
            </div>
        </div>

        <!-- Body Details -->
        <div style="padding: 20px; max-height: calc(85vh - 240px); overflow-y: auto;">
            
            <!-- Quick Feature Badges -->
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.06); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 6px; padding: 5px 10px; font-size: 11.5px; color: #E2E8F0;">
                    <i class="fa-solid fa-mountain" style="color: var(--accent-gold);"></i>
                    <span id="fim-elevation">1,600M Elevation</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.06); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 6px; padding: 5px 10px; font-size: 11.5px; color: #E2E8F0;">
                    <i class="fa-solid fa-wind" style="color: #56C2C9;"></i>
                    <span id="fim-climate">18°C Alpine Breeze</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 6px; padding: 5px 10px; font-size: 11.5px; color: #4ADE80; font-weight: 600;">
                    <i class="fa-solid fa-gift"></i>
                    <span>Included in Cottage Stays</span>
                </div>
            </div>

            <!-- Description -->
            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px;">
                <h5 style="color: var(--accent-gold); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px;">Experience Details</h5>
                <p id="fim-desc" style="color: #D3E0D8; font-size: 13px; line-height: 1.6; margin: 0;">
                    Fresh crystal-clear natural mountain spring water pool.
                </p>
            </div>

            <!-- Privilege & Highlights Grid -->
            <div style="margin-bottom: 16px;">
                <h5 style="color: var(--accent-gold); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 8px;">Facility Inclusions</h5>
                <div id="fim-highlights-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                    <!-- Inserted dynamically -->
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 14px;">
                <button type="button" class="btn-primary" onclick="selectFirstAvailableCottage(); closeFacilityInfoModal();" style="flex: 1; min-width: 180px; justify-content: center; padding: 10px; font-size: 13px;">
                    <i class="fa-solid fa-house-chimney"></i> <span>Reserve Cottage to Enjoy</span>
                </button>
                <a href="#" id="fim-wa-btn" target="_blank" class="btn-outline" style="border-color: #25D366; color: #25D366; padding: 10px 14px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                    <i class="fa-brands fa-whatsapp"></i> <span>Ask Concierge</span>
                </a>
                <button type="button" class="btn-outline" onclick="closeFacilityInfoModal(); openAmenitiesGuide();" style="border-color: rgba(197, 160, 89, 0.4); color: var(--accent-gold); padding: 10px 12px; font-size: 13px;">
                    <span>All Amenities</span>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
function openFacilityInfoModal(spot) {
    if (!spot) return;
    var m = document.getElementById('facility-info-modal');
    if (!m) return;

    var titleEl = document.getElementById('fim-title');
    var descEl = document.getElementById('fim-desc');
    var badgeEl = document.getElementById('fim-badge');
    var spotNumEl = document.getElementById('fim-spot-num');
    var imgEl = document.getElementById('fim-img');
    var elevEl = document.getElementById('fim-elevation');
    var climEl = document.getElementById('fim-climate');
    var gridEl = document.getElementById('fim-highlights-grid');
    var waBtn = document.getElementById('fim-wa-btn');

    if (titleEl) titleEl.innerText = spot.title || 'Estate Facility';
    if (descEl) descEl.innerText = spot.description || 'Complimentary estate facility exclusively included for all resident sanctuary guests.';
    if (spotNumEl) spotNumEl.innerText = 'SPOT ' + String(spot.spot_number || spot.id || '01').padStart(2, '0');
    if (elevEl) elevEl.innerText = spot.elevation || '1,600M Elevation';
    if (climEl) climEl.innerText = spot.temperature || '18°C Alpine Breeze';

    var isDining = (spot.category === 'dining' || (spot.title && spot.title.toLowerCase().includes('dining')));
    if (badgeEl) {
        badgeEl.innerHTML = isDining ? '<i class="fa-solid fa-utensils"></i> FARM DINING' : '<i class="fa-solid fa-water"></i> ESTATE AMENITY';
    }

    // Set contextual image
    var photoSrc = '';
    if (spot.photos_list && spot.photos_list.length > 0 && spot.photos_list[0]) {
        photoSrc = spot.photos_list[0];
    } else if (spot.image_url) {
        photoSrc = spot.image_url;
    } else if (isDining) {
        photoSrc = 'assets/images/food_kerala_sadya.jpg';
    } else if (spot.title && spot.title.toLowerCase().includes('pool')) {
        photoSrc = 'assets/images/01 (10).jpeg';
    } else if (spot.title && spot.title.toLowerCase().includes('campfire')) {
        photoSrc = 'assets/images/01 (26).jpeg';
    } else {
        photoSrc = 'assets/images/01 (25).jpeg';
    }
    if (imgEl) imgEl.src = photoSrc;

    // Highlights grid
    if (gridEl) {
        var tLower = (spot.title || '').toLowerCase();
        if (tLower.includes('pool')) {
            gridEl.innerHTML = `
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86,194,201,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-droplet" style="color: #0EA5E9; margin-right: 6px;"></i> Pure Natural Spring Aquifer Water</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86,194,201,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-leaf" style="color: #10B981; margin-right: 6px;"></i> 100% Chlorine &amp; Chemical Free</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86,194,201,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-clock" style="color: var(--accent-gold); margin-right: 6px;"></i> Open Daily 07:00 AM – 06:00 PM</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86,194,201,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-users" style="color: #56C2C9; margin-right: 6px;"></i> Free for All In-House Residents</div>
            `;
        } else if (tLower.includes('campfire') || tLower.includes('recreation')) {
            gridEl.innerHTML = `
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-fire" style="color: #F59E0B; margin-right: 6px;"></i> Twilight Bonfire &amp; Acoustic Music</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-star" style="color: var(--accent-gold); margin-right: 6px;"></i> High-Altitude Night Stargazing Lawn</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-mug-hot" style="color: #F59E0B; margin-right: 6px;"></i> Evening Plantation Tea &amp; Snacks</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-users" style="color: #10B981; margin-right: 6px;"></i> Free for All In-House Residents</div>
            `;
        } else if (isDining) {
            gridEl.innerHTML = `
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-utensils" style="color: #F59E0B; margin-right: 6px;"></i> All 4 Farm Meals Included with Stays</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-fire-burner" style="color: #F59E0B; margin-right: 6px;"></i> Traditional Claypot Woodfire Cooking</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-seedling" style="color: #10B981; margin-right: 6px;"></i> 100% Soil-to-Plate Organic Produce</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-shield-halved" style="color: var(--accent-gold); margin-right: 6px;"></i> Pure Vegetarian &amp; Non-Veg Sections</div>
            `;
        } else {
            gridEl.innerHTML = `
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(197,160,89,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-sparkles" style="color: var(--accent-gold); margin-right: 6px;"></i> Sanctuary Amenity for All Guests</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(197,160,89,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-tree" style="color: #10B981; margin-right: 6px;"></i> Organic High-Range Nature Surrounds</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(197,160,89,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-clock" style="color: #56C2C9; margin-right: 6px;"></i> Accessible Throughout Your Stay</div>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(197,160,89,0.25); border-radius: 6px; padding: 8px 10px; font-size: 12px; color: #D3E0D8;"><i class="fa-solid fa-wifi" style="color: var(--accent-gold); margin-right: 6px;"></i> Wi-Fi &amp; Concierge Service Available</div>
            `;
        }
    }

    if (waBtn) {
        var waNum = window.conciergeWhatsApp || '919234567890';
        waBtn.href = 'https://wa.me/' + waNum + '?text=' + encodeURIComponent('Hello Concierge, I would like to know more about the ' + (spot.title || 'facilities') + ' at Food Forest.');
    }

    m.style.display = 'flex';
    m.style.opacity = '1';
    m.style.pointerEvents = 'auto';
    m.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeFacilityInfoModal() {
    var m = document.getElementById('facility-info-modal');
    if (m) {
        m.classList.remove('active');
        m.style.display = 'none';
        m.style.opacity = '0';
        m.style.pointerEvents = 'none';
        document.body.style.overflow = '';
    }
}

function selectFirstAvailableCottage() {
    var spots = window.bookingSanctuarySpots || [];
    var stay = spots.find(function(s) {
        return (s.is_stay == 1 || s.is_stay === '1' || s.category === 'stays');
    });
    if (stay) {
        var node = document.getElementById('chalet-node-' + stay.id);
        if (node) {
            node.click();
        } else if (typeof selectChalet === 'function') {
            selectChalet(stay.id, true);
        }
    }
}

window.openFacilityInfoModal = openFacilityInfoModal;
window.closeFacilityInfoModal = closeFacilityInfoModal;
window.selectFirstAvailableCottage = selectFirstAvailableCottage;
</script>

<?php
// Load Existing Booking Modal (serves as rich step-by-step confirmation checkout)
require_once __DIR__ . '/components/booking_modal.php';

// Load Footer
require_once __DIR__ . '/includes/footer.php';
?>
