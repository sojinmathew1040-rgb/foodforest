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
$sanctuary_spots = get_all_sanctuary_spots(true);
$currency = get_setting('currency_symbol', '₹');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');

// Pre-selected villa from URL query
$preselect_slug = trim($_GET['villa'] ?? '');

// Calculate dynamic SVG route trail
$route_d = '';
if (!empty($sanctuary_spots)) {
    $pts = [];
    foreach ($sanctuary_spots as $sp) {
        $pts[] = [
            'x' => ($sp['x_coord'] / 100.0) * 800,
            'y' => ($sp['y_coord'] / 100.0) * 520
        ];
    }
    if (count($pts) > 1) {
        $route_d = "M " . round($pts[0]['x'], 1) . "," . round($pts[0]['y'], 1);
        for ($i = 0; $i < count($pts) - 1; $i++) {
            $p0 = $pts[$i];
            $p1 = $pts[$i + 1];
            $mx = ($p0['x'] + $p1['x']) / 2;
            $my = ($p0['y'] + $p1['y']) / 2;
            $dx = $p1['x'] - $p0['x'];
            $dy = $p1['y'] - $p0['y'];
            $cx = $mx - ($dy * 0.12);
            $cy = $my + ($dx * 0.12);
            $route_d .= " Q " . round($cx, 1) . "," . round($cy, 1) . " " . round($p1['x'], 1) . "," . round($p1['y'], 1);
        }
    }
}

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
                            <input type="number" id="book-adults" class="ctrl-step-val font-sans" value="2" min="1" max="10" readonly>
                            <button type="button" class="ctrl-step-btn btn-plus" data-target="book-adults">+</button>
                        </div>
                    </div>

                    <!-- Children Counter -->
                    <div class="ctrl-field">
                        <label class="ctrl-label font-sans"><i class="fa-solid fa-child"></i> Children <small style="font-size: 10px; color: var(--accent-gold);">(5–11y)</small></label>
                        <div class="ctrl-stepper">
                            <button type="button" class="ctrl-step-btn btn-minus" data-target="book-kids">−</button>
                            <input type="number" id="book-kids" class="ctrl-step-val font-sans" value="0" min="0" max="6" readonly>
                            <button type="button" class="ctrl-step-btn btn-plus" data-target="book-kids">+</button>
                        </div>
                    </div>

                    <!-- View Switcher Tabs -->
                    <div class="ctrl-field view-switcher-field">
                        <label class="ctrl-label font-sans"><i class="fa-solid fa-sliders"></i> Layout View</label>
                        <div class="view-toggle-group">
                            <button type="button" class="view-toggle-btn active" id="view-btn-map" data-view="map">
                                <i class="fa-solid fa-map"></i> <span>Estate Map</span>
                            </button>
                            <button type="button" class="view-toggle-btn" id="view-btn-grid" data-view="grid">
                                <i class="fa-solid fa-grip"></i> <span>Chalet Grid</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Stay Concept Filter Chips -->
                <div class="booking-filter-chips-row">
                    <span class="chips-label font-sans"><i class="fa-solid fa-filter"></i> Filter Stays:</span>
                    <button type="button" class="chip-btn active" data-stay-filter="all">All Chalets</button>
                    <button type="button" class="chip-btn" data-stay-filter="treehouse">🌲 Canopy Treehouse</button>
                    <button type="button" class="chip-btn" data-stay-filter="mudhouse">🌿 Earthen Mudhouse</button>
                    <button type="button" class="chip-btn" data-stay-filter="woodhouse">🪵 Alpine Woodhouse</button>
                    <button type="button" class="chip-btn" data-stay-filter="duplex">🏰 Duplex Suites</button>
                    <button type="button" class="chip-btn" data-stay-filter="single">🏡 Single Cottages</button>
                </div>
            </div>

            <!-- BookMyShow-Style Interactive Map Legend -->
            <div class="bms-map-legend font-sans">
                <div class="legend-item"><span class="legend-badge badge-single"><i class="fa-solid fa-house-chimney"></i></span> Single Cottage / Room</div>
                <div class="legend-item"><span class="legend-badge badge-duplex"><i class="fa-solid fa-layer-group"></i></span> Duplex Chalet (2 Suites)</div>
                <div class="legend-item"><span class="legend-badge badge-fast-filling"></span> Fast Filling (1 Left)</div>
                <div class="legend-item"><span class="legend-badge badge-facility"><i class="fa-solid fa-utensils"></i></span> Estate Facilities</div>
                <div class="legend-item"><span class="legend-badge badge-selected"><i class="fa-solid fa-check"></i></span> Selected Chalet</div>
            </div>
        </div>
    </section>

    <!-- Main View Content Area (Map View & Grid View) -->
    <section class="booking-view-container">
        <div class="container">
            
            <!-- 1. MAP VIEW LAYOUT -->
            <div class="booking-map-layout" id="booking-map-layout">
                
                <div class="bms-map-canvas-card">
                    <!-- Topographic SVG Background Canvas -->
                    <svg class="bms-topo-svg" viewBox="0 0 800 520" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
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

                        <!-- Meandering River Stream -->
                        <path d="M 120,-20 C 140,80 190,140 240,210 C 290,280 340,310 410,380 C 470,440 520,480 580,540" 
                              stroke="url(#bms-stream-grad)" stroke-width="5" fill="none" stroke-linecap="round" filter="url(#bms-glow)" />
                        <path d="M 390,260 C 430,280 470,320 480,350" 
                              stroke="url(#bms-stream-grad)" stroke-width="2.5" fill="none" stroke-linecap="round" opacity="0.8" />

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

                        <!-- Route Trail Line -->
                        <?php if (!empty($route_d)): ?>
                            <path d="<?php echo $route_d; ?>" fill="none" stroke="rgba(197, 160, 89, 0.25)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#bms-glow)" />
                            <path d="<?php echo $route_d; ?>" fill="none" stroke="#C5A059" stroke-width="2.2" stroke-dasharray="6,6" stroke-linecap="round" stroke-linejoin="round" />
                        <?php endif; ?>
                    </svg>

                    <!-- Compass & Brook Badges -->
                    <div class="map-compass font-serif">
                        <span class="compass-n">N</span>
                        <div class="compass-pointer"></div>
                        <span class="compass-coords">KANTHALLOOR · 1,600M</span>
                    </div>
                    <div class="map-stream-badge font-sans">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12c3-3 6-3 9 0s6 3 9 0"/><path d="M2 18c3-3 6-3 9 0s6 3 9 0"/></svg>
                        Perennial Brook
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
                            $status = ($idx === 1 || $idx === 2) ? 'available' : ($idx === 3 ? 'fast_filling' : 'available');
                            
                            // Map stay types
                            $stay_cat = 'treehouse';
                            if (stripos($sp['title'], 'mudhouse') !== false) $stay_cat = 'mudhouse';
                            if (stripos($sp['title'], 'woodhouse') !== false) $stay_cat = 'woodhouse';

                            $y_pos = (float)$sp['y_coord'];
                            $x_pos = (float)$sp['x_coord'];
                            $pos_classes = [];
                            if ($y_pos < 36.0) {
                                $pos_classes[] = 'pos-bottom';
                            }
                            if ($x_pos < 22.0) {
                                $pos_classes[] = 'pos-left';
                            } elseif ($x_pos > 78.0) {
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
                                 style="top: <?php echo $y_pos; ?>%; left: <?php echo $x_pos; ?>%;">
                                
                                <div class="node-halo"></div>
                                <div class="node-box">
                                    <?php if ($is_duplex): ?>
                                        <div class="node-icon icon-duplex" title="Duplex Chalet (2 Suites)"><i class="fa-solid fa-layer-group"></i></div>
                                        <div class="node-code font-serif"><?php echo sprintf('%02d', $sp['spot_number']); ?></div>
                                        <span class="node-duplex-pill">2-Suite</span>
                                        <span class="node-status-dot status-dot-<?php echo $status; ?>"></span>
                                    <?php elseif ($is_stay): ?>
                                        <div class="node-icon icon-single" title="Single Cottage"><i class="fa-solid fa-house-chimney"></i></div>
                                        <div class="node-code font-serif"><?php echo sprintf('%02d', $sp['spot_number']); ?></div>
                                        <span class="node-status-dot status-dot-<?php echo $status; ?>"></span>
                                    <?php else: ?>
                                        <div class="node-icon-facility">
                                            <?php if ($sp['category'] === 'dining'): ?>
                                                <i class="fa-solid fa-utensils"></i>
                                            <?php elseif ($sp['category'] === 'amenities'): ?>
                                                <i class="fa-solid fa-water"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-tree"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="node-code font-serif"><?php echo sprintf('%02d', $sp['spot_number']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Floating Mini Badge -->
                                <div class="node-hover-card font-sans">
                                    <span class="nhc-type"><?php echo $is_duplex ? '🏰 DUPLEX RESIDENCE (2 SUITES)' : ($is_stay ? '🏡 SINGLE COTTAGE' : '🌿 ESTATE HUB'); ?></span>
                                    <span class="nhc-title"><?php echo htmlspecialchars($sp['title']); ?></span>
                                    <?php if ($is_stay): ?>
                                        <span class="nhc-rate">From ₹<?php echo number_format($rate, 0, '.', ','); ?>/night</span>
                                        <span class="nhc-status status-label-<?php echo $status; ?>">
                                            <i class="fa-solid fa-circle-check"></i> <?php echo ($status === 'fast_filling') ? 'Fast Filling' : 'Available for Dates'; ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="nhc-cta">Click to View Details & Rates &rarr;</span>
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

                        <div class="sac-gallery-wrap">
                            <img src="assets/images/01 (25).jpeg" alt="Chalet" id="sac-main-img" class="sac-img">
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

                            <!-- Dynamic Tier / Duplex Option Switcher (if duplex) -->
                            <div class="sac-tier-box" id="sac-tier-box" style="display: none;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                                    <label class="tier-box-label font-sans" style="margin-bottom: 0;"><i class="fa-solid fa-layer-group"></i> Configuration & Rate Option:</label>
                                    <button type="button" class="btn-open-duplex-guide font-sans" onclick="openDuplexExplainer();" style="background: transparent; border: none; color: #56c2c9; font-size: 11px; cursor: pointer; text-decoration: underline; display: flex; align-items: center; gap: 4px; padding: 0;">
                                        <i class="fa-solid fa-circle-question"></i> View Duplex Architecture Guide
                                    </button>
                                </div>
                                <div class="tier-options-grid">
                                    <label class="tier-option-label" id="tier-label-full">
                                        <input type="radio" name="sac_tier_choice" value="full" checked>
                                        <div class="tier-option-content">
                                            <span class="toc-name font-sans">Full Duplex Suite</span>
                                            <span class="toc-rate font-serif" id="toc-rate-full">₹24,000/nt</span>
                                            <small class="toc-note font-sans">Entire 2-Floor Residence (Base 4 Guests)</small>
                                        </div>
                                    </label>
                                    <label class="tier-option-label" id="tier-label-single">
                                        <input type="radio" name="sac_tier_choice" value="single_room">
                                        <div class="tier-option-content">
                                            <span class="toc-name font-sans">Single Master Room</span>
                                            <span class="toc-rate font-serif" id="toc-rate-single">₹14,500/nt</span>
                                            <small class="toc-note font-sans">1 Master Room in Chalet (Base 2 Guests)</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Amenities Summary Pills -->
                            <div class="sac-amenities-row font-sans">
                                <span><i class="fa-solid fa-utensils"></i> All Farm Meals Included</span>
                                <span><i class="fa-solid fa-wifi"></i> Forest Wi-Fi</span>
                                <span><i class="fa-solid fa-mug-hot"></i> Organic Tea Ritual</span>
                            </div>

                            <!-- Pricing Calculation Box -->
                            <div class="sac-price-breakdown font-sans">
                                <div class="price-row">
                                    <span id="sac-price-label">Room Rate (1 Night):</span>
                                    <strong id="sac-base-price">₹14,500</strong>
                                </div>
                                <div class="price-row" id="sac-extra-row" style="display: none;">
                                    <span>Extra Guests:</span>
                                    <span id="sac-extra-price">+₹0</span>
                                </div>
                                <div class="price-row total-row">
                                    <span>Total Net Amount:</span>
                                    <strong class="total-val font-serif" id="sac-total-price">₹14,500</strong>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="sac-actions">
                                <button type="button" class="btn-primary sac-book-btn font-sans" id="btn-sac-open-checkout">
                                    <span>Proceed to Reserve</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                                <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Concierge,%20I%20am%20interested%20in%20booking%20at%20Food%20Forest." target="_blank" class="btn-outline sac-wa-btn font-sans">
                                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Concierge
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 2. GRID VIEW LAYOUT (List Cards Alternative) -->
            <div class="booking-grid-layout" id="booking-grid-layout" style="display: none;">
                <div class="chalets-cards-grid">
                    <?php 
                    foreach ($rooms as $rm):
                        $is_dup = ($rm['structure_type'] === 'duplex_hut');
                        $r_rate = (float)$rm['rate_per_night'];
                        $single_rate = (float)($rm['single_room_rate'] ?? $r_rate);
                    ?>
                        <div class="chalet-card font-sans" data-villa-slug="<?php echo htmlspecialchars($rm['slug']); ?>" data-stay-cat="<?php echo htmlspecialchars($rm['stay_type'] ?? 'treehouse'); ?>" data-structure="<?php echo htmlspecialchars($rm['structure_type'] ?? 'single_hut'); ?>" data-is-duplex="<?php echo $is_dup ? '1' : '0'; ?>">
                            <div class="chalet-card-media">
                                <img src="<?php echo htmlspecialchars($rm['image_url']); ?>" alt="<?php echo htmlspecialchars($rm['title']); ?>" onerror="this.src='assets/images/01 (25).jpeg';">
                                <span class="chalet-badge-pill"><?php echo htmlspecialchars($rm['elevation']); ?></span>
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
                                </div>

                                <div class="chalet-actions-row">
                                    <button type="button" class="btn-primary select-from-grid-btn font-sans" data-slug="<?php echo htmlspecialchars($rm['slug']); ?>">
                                        <i class="fa-solid fa-calendar-check"></i> Book Chalet
                                    </button>
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
</script>
<script src="assets/js/booking_controller.js?v=<?php echo time(); ?>"></script>

<?php
// Load Existing Booking Modal (serves as rich step-by-step confirmation checkout)
require_once __DIR__ . '/components/booking_modal.php';

// Load Footer
require_once __DIR__ . '/includes/footer.php';
?>
