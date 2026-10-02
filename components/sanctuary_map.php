<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
ensure_sanctuary_spots_table_exists($pdo);

$map_data = get_sanctuary_map_data($pdo, true);
$sanctuary_spots = $map_data['spots'];
$map_routes = $map_data['routes'];
$map_entrance = $map_data['entrance'];
$map_exit = $map_data['exit'];
$map_waypoints = $map_data['waypoints'];

$sec_label = get_setting('sanctuary_section_label', 'The Living Landscape');
$sec_title = get_setting('sanctuary_section_title', 'An Untamed Sanctuary');

$first_spot = !empty($sanctuary_spots) ? $sanctuary_spots[0] : null;
$spots_count = count($sanctuary_spots);
?>
<!-- Sanctuary Estate & Environmental Harmony Section -->
<section id="sanctuary" class="sanctuary-section">
    <div class="container">
        
        <!-- Header -->
        <div class="sanctuary-header text-center">
            <span class="section-label"><?php echo e($sec_label); ?></span>
            <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo e($sec_title); ?></h3>
            <p class="sanctuary-subtitle font-sans" style="max-width: 680px; margin: 10px auto 24px; color: var(--text-light); font-size: 0.95rem;">
                Explore our terraced mountain estate. Distinct <strong style="color: var(--accent-gold);">Stay Accommodations</strong> (Treehouses, Mudhouses & Woodhouses) are nestled among orchards, natural streams, and communal dining hearths.
            </p>

            <!-- Map Filter Chips (Stays vs Facilities) -->
            <div class="sanctuary-filter-bar">
                <button type="button" class="sanctuary-filter-btn active" data-map-filter="all">
                    <i class="fa-solid fa-compass"></i> All Locations (<?php echo $spots_count; ?>)
                </button>
                <button type="button" class="sanctuary-filter-btn filter-single-btn" data-map-filter="single">
                    <i class="fa-solid fa-house-chimney"></i> 🏡 Single Cottages
                </button>
                <button type="button" class="sanctuary-filter-btn filter-duplex-btn" data-map-filter="duplex" style="color: #56C2C9; border-color: rgba(86, 194, 201, 0.4);">
                    <i class="fa-solid fa-layer-group"></i> 🏰 Duplex Chalets
                </button>
                <button type="button" class="sanctuary-filter-btn" data-map-filter="dining">
                    <i class="fa-solid fa-utensils"></i> 🍲 Farm Dining
                </button>
                <button type="button" class="sanctuary-filter-btn" data-map-filter="amenities">
                    <i class="fa-solid fa-water"></i> 🌊 Farm Streams & Glades
                </button>
                <a href="booking.php" class="sanctuary-filter-btn map-bookmyshow-link" title="Open Interactive Estate Booking Page">
                    <i class="fa-solid fa-calendar-check"></i> Book Chalets Online <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; margin-left: 3px;"></i>
                </a>
            </div>
        </div>


        <!-- Interactive Estate Map & Exploration Console -->
        <div class="sanctuary-explorer-wrapper scroll-reveal">

            <!-- Two-Column Interactive Console -->
            <div class="sanctuary-console-grid booking-map-layout">
                
                <!-- Left: Topographic Map Canvas -->
                <div class="sanctuary-map-board bms-map-canvas-card" id="sanctuary-map-board">
                    <!-- Topographic Background SVG -->
                    <svg class="topo-svg-canvas" viewBox="0 0 800 520" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <radialGradient id="topo-forest-glow" cx="50%" cy="50%" r="65%">
                                <stop offset="0%" stop-color="#1c3826" stop-opacity="0.95" />
                                <stop offset="60%" stop-color="#14281c" stop-opacity="0.98" />
                                <stop offset="100%" stop-color="#0c1912" stop-opacity="1" />
                            </radialGradient>
                            <linearGradient id="stream-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#56c2c9" stop-opacity="0.8" />
                                <stop offset="50%" stop-color="#38a3a5" stop-opacity="0.9" />
                                <stop offset="100%" stop-color="#22577a" stop-opacity="0.8" />
                            </linearGradient>
                            <filter id="map-glow" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur stdDeviation="3" result="blur" />
                                <feComposite in="SourceGraphic" in2="blur" operator="over" />
                            </filter>
                        </defs>

                        <!-- Base Map Terrain -->
                        <rect width="800" height="520" fill="url(#topo-forest-glow)" />

                        <!-- Topographic Contour Lines -->
                        <g class="topo-contours" stroke="rgba(197, 160, 89, 0.16)" fill="none" stroke-width="1.2">
                            <!-- 1,560m contour -->
                            <path d="M -20,440 Q 150,480 320,430 T 650,470 T 820,420" />
                            <path d="M -20,380 Q 180,410 340,360 T 670,390 T 820,350" stroke="rgba(197, 160, 89, 0.22)" />
                            <!-- 1,580m contour -->
                            <path d="M -20,320 Q 160,350 350,300 T 630,320 T 820,290" />
                            <path d="M -20,260 Q 200,290 400,240 T 650,260 T 820,220" stroke="rgba(197, 160, 89, 0.28)" />
                            <!-- 1,600m contour (Master index) -->
                            <path d="M -20,200 Q 180,220 420,170 T 680,190 T 820,150" stroke="rgba(197, 160, 89, 0.4)" stroke-width="1.8" />
                            <!-- 1,620m contour -->
                            <path d="M -20,140 Q 220,170 450,110 T 700,130 T 820,90" />
                            <path d="M -20,80 Q 240,110 470,60 T 720,80 T 820,30" stroke="rgba(197, 160, 89, 0.22)" />
                            <!-- 1,640m peak contour -->
                            <path d="M 280,-20 Q 420,70 560,-20" stroke="rgba(197, 160, 89, 0.35)" />
                            <path d="M 330,-20 Q 430,45 520,-20" stroke="rgba(197, 160, 89, 0.45)" stroke-width="1.5" />
                        </g>

                        <!-- Luxury Navigational Compass Rose -->
                        <g class="sanctuary-topo-compass" transform="translate(710, 68)" pointer-events="none">
                            <circle cx="0" cy="0" r="28" fill="rgba(8, 20, 14, 0.85)" stroke="rgba(197, 160, 89, 0.5)" stroke-width="1.2" filter="url(#map-glow)" />
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

                        <!-- Decorative Shola Tree Clusters -->
                        <g class="topo-trees" fill="rgba(64, 115, 84, 0.35)">
                            <circle cx="110" cy="180" r="14" />
                            <circle cx="130" cy="190" r="11" />
                            <circle cx="95" cy="195" r="9" />
                            
                            <circle cx="670" cy="240" r="16" />
                            <circle cx="690" cy="255" r="12" />
                            <circle cx="650" cy="260" r="10" />

                            <circle cx="210" cy="420" r="15" />
                            <circle cx="230" cy="435" r="11" />

                            <circle cx="610" cy="90" r="14" />
                            <circle cx="630" cy="105" r="10" />
                        </g>

                        <!-- Contour Elevation Labels -->
                        <text x="685" y="185" fill="rgba(197, 160, 89, 0.55)" font-size="10" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,600M MSL</text>
                        <text x="685" y="125" fill="rgba(197, 160, 89, 0.4)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,620M MSL</text>
                        <text x="685" y="385" fill="rgba(197, 160, 89, 0.4)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,580M MSL</text>

                        <!-- 1. Estate Perimeter Survey Boundary -->
                        <rect x="36" y="24" width="728" height="472" rx="16" 
                              fill="none" stroke="rgba(197, 160, 89, 0.35)" stroke-width="1.2" stroke-dasharray="10,6" />
                        
                        <!-- Survey Corner Coordinate Marks -->
                        <g font-family="'Cinzel', Georgia, serif" font-size="8.5" fill="rgba(197, 160, 89, 0.55)" letter-spacing="1">
                            <text x="50" y="44">+ 10°14'22"N · 77°11'45"E</text>
                            <text x="640" y="44" text-anchor="end">+ 1,640M HIGH RANGE</text>
                            <text x="50" y="484">ESTATE PERIMETER · 12 ACRES</text>
                            <text x="640" y="484" text-anchor="end">PRIVATE SANCTUARY RESERVE</text>
                        </g>

                        <!-- 2. Dynamic Custom Routes / Walking Trails (Calculated from Admin Pathways) -->
                        <?php if (!empty($map_routes)): ?>
                            <?php foreach ($map_routes as $r): ?>
                                <?php if (!empty($r['svg_d'])): 
                                    $dash = ($r['stroke_type'] === 'solid') ? 'none' : (($r['stroke_type'] === 'dotted') ? '3,4' : '9,6');
                                    $w = floatval($r['line_width'] ?? 3.2);
                                    $col = $r['color'] ?? '#D4AF37';
                                ?>
                                    <!-- Trail Glowing Aura -->
                                    <path class="sanctuary-spine-aura" d="<?php echo $r['svg_d']; ?>" 
                                          fill="none" stroke="<?php echo $col; ?>" stroke-opacity="0.32" stroke-width="<?php echo $w * 2.8; ?>" stroke-linecap="round" stroke-linejoin="round" filter="url(#map-glow)" />
                                    <!-- Paved Golden Trail Line -->
                                    <path class="sanctuary-spine-trail" d="<?php echo $r['svg_d']; ?>" 
                                          fill="none" stroke="<?php echo $col; ?>" stroke-width="<?php echo $w; ?>" stroke-dasharray="<?php echo $dash; ?>" stroke-linecap="round" stroke-linejoin="round" />
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- 3. Main Entrance Landmark Gate -->
                        <?php if (!empty($map_entrance['enabled'])): ?>
                            <g transform="translate(<?php echo $map_entrance['svg_x']; ?>, <?php echo $map_entrance['svg_y']; ?>)">
                                <circle cx="0" cy="0" r="7" fill="#14281c" stroke="#D4AF37" stroke-width="2.2" filter="url(#map-glow)" />
                                <circle cx="0" cy="0" r="2.8" fill="#56C2C9" />
                                <text x="14" y="3.5" fill="#D4AF37" font-size="9" font-family="'Cinzel', Georgia, serif" font-weight="bold" letter-spacing="1"><?php echo e($map_entrance['label']); ?></text>
                            </g>
                        <?php endif; ?>

                        <!-- 4. Estate Exit Landmark Gate (if enabled) -->
                        <?php if (!empty($map_exit['enabled'])): ?>
                            <g transform="translate(<?php echo $map_exit['svg_x']; ?>, <?php echo $map_exit['svg_y']; ?>)">
                                <circle cx="0" cy="0" r="7" fill="#14281c" stroke="#E67E22" stroke-width="2.2" filter="url(#map-glow)" />
                                <circle cx="0" cy="0" r="2.8" fill="#E67E22" />
                                <text x="14" y="3.5" fill="#E67E22" font-size="9" font-family="'Cinzel', Georgia, serif" font-weight="bold" letter-spacing="1"><?php echo e($map_exit['label']); ?></text>
                            </g>
                        <?php endif; ?>

                        <!-- 5. Custom Waypoints (Road geometry is rendered above, waypoint pins hidden on frontend) -->
                    </svg>

                    <!-- Luxury Compass Rose -->
                    <div class="map-compass font-serif">
                        <span class="compass-n">N</span>
                        <div class="compass-pointer"></div>
                        <span class="compass-coords">KANTHALLOOR · 1,600M</span>
                    </div>

                    <!-- Dynamic Hotspot Pins on the Map -->
                    <div class="sanctuary-pins-container bms-chalets-container" id="bms-chalets-container">
                        <?php foreach ($sanctuary_spots as $idx => $sp): 
                            $is_stay = !empty($sp['is_stay']) || ($sp['category'] ?? '') === 'stays';
                            $slug = $sp['linked_room_slug'] ?? 'treehouse';
                            $rate = (float)($sp['room_rate'] ?? $sp['stay_price'] ?? 0);
                            $struct = $sp['structure_type'] ?? 'single_hut';
                            $is_duplex = ($is_stay && ($struct === 'duplex_hut' || stripos($sp['title'], 'duplex') !== false));
                            $pin_col = !empty($sp['pin_color']) ? $sp['pin_color'] : ($is_stay ? ($is_duplex ? '#06B6D4' : '#10B981') : '#F59E0B');
                            $custom_icon = !empty($sp['icon_class']) ? $sp['icon_class'] : ($is_duplex ? 'fa-solid fa-layer-group' : ($is_stay ? 'fa-solid fa-house-chimney' : ($sp['category'] === 'dining' ? 'fa-solid fa-utensils' : 'fa-solid fa-tree')));

                            if ($is_duplex) {
                                $stay_tag = '🏰 DUPLEX CHALET (2 SUITES)';
                            } elseif ($is_stay) {
                                $stay_tag = '🏡 SINGLE COTTAGE';
                            } else {
                                $stay_tag = '🌿 ESTATE HUB';
                            }
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
                            <div class="sanctuary-pin bms-chalet-node <?php echo $is_stay ? ($is_duplex ? 'node-stay node-duplex' : 'node-stay node-single') : 'node-facility'; ?> status-available <?php echo $pos_class_str; ?> <?php echo $idx === 0 ? 'active is-selected' : ''; ?>" 
                                 id="chalet-node-<?php echo (int)$sp['id']; ?>"
                                 data-zone="<?php echo (int)$sp['id']; ?>" 
                                 data-spot-id="<?php echo (int)$sp['id']; ?>"
                                 data-spot-num="<?php echo (int)$sp['spot_number']; ?>"
                                 data-category="<?php echo htmlspecialchars($sp['category'] ?? 'nature'); ?>"
                                 data-is-stay="<?php echo $is_stay ? '1' : '0'; ?>"
                                 data-is-duplex="<?php echo $is_duplex ? '1' : '0'; ?>"
                                 data-structure="<?php echo htmlspecialchars($struct); ?>"
                                 data-slug="<?php echo htmlspecialchars($slug); ?>"
                                 data-rate="<?php echo $rate; ?>"
                                 data-status="available"
                                 style="top: <?php echo $y_pos; ?>%; left: <?php echo $x_pos; ?>%; --pin-accent: <?php echo $pin_col; ?>; --node-accent: <?php echo $pin_col; ?>;">
                                
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
                                        <span class="node-status-dot status-dot-available"></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Floating Mini Badge -->
                                <div class="node-hover-card font-sans">
                                    <span class="nhc-type"><?php echo $stay_tag; ?></span>
                                    <span class="nhc-title"><?php echo htmlspecialchars($sp['title']); ?></span>
                                    <?php if ($is_stay && !empty($sp['room_rate'])): ?>
                                        <span class="nhc-rate">From ₹<?php echo number_format($sp['room_rate'], 0, '.', ','); ?>/night</span>
                                        <span class="nhc-status status-label-available">
                                            <i class="fa-solid fa-circle-check"></i> Available for Reservation
                                        </span>
                                    <?php endif; ?>
                                    <span class="nhc-cta">Click Spot on Map to Inspect &rarr;</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: Dynamic Interactive Zone Inspector Card -->
                <div class="sanctuary-inspector-card sidebar-active-card" id="sanctuary-inspector">
                    
                    <!-- Zone Card Header -->
                    <div class="inspector-header">
                        <div class="inspector-zone-pill">
                            <span class="inspector-zone-num" id="ins-zone-num">SPOT <?php echo $first_spot ? sprintf('%02d', $first_spot['spot_number']) : '01'; ?></span>
                            <span class="inspector-zone-type" id="ins-zone-type" style="margin-left: 6px;"><?php echo (!empty($first_spot['is_stay']) || ($first_spot['category'] ?? '') === 'stays') ? '🏡 BOOKABLE STAY' : '🌿 ESTATE FACILITY'; ?></span>
                        </div>
                        <div class="inspector-nav-arrows">
                            <button class="ins-arrow-btn" id="ins-prev-btn" aria-label="Previous Spot" title="Previous Spot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <span class="ins-fraction font-serif"><span id="ins-curr-idx">1</span> / <?php echo max(1, $spots_count); ?></span>
                            <button class="ins-arrow-btn" id="ins-next-btn" aria-label="Next Spot" title="Next Spot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Multi-Photo Showcase Carousel -->
                    <div class="inspector-media-wrap" id="ins-media-wrap" style="position: relative; overflow: hidden; border-radius: 6px; aspect-ratio: 16/10;">
                        <img src="<?php echo $first_spot ? htmlspecialchars($first_spot['image_url']) : 'assets/images/01 (10).jpeg'; ?>" alt="<?php echo $first_spot ? htmlspecialchars($first_spot['title']) : 'Spot'; ?>" id="ins-img" class="inspector-img" onerror="this.src='assets/images/01 (1).jpeg';">
                        <div class="inspector-img-overlay"></div>

                        <!-- Photo Navigation Arrows -->
                        <button type="button" class="ins-photo-nav-btn ins-photo-prev" id="ins-photo-prev-btn" aria-label="Previous Photo" title="Previous Photo">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <button type="button" class="ins-photo-nav-btn ins-photo-next" id="ins-photo-next-btn" aria-label="Next Photo" title="Next Photo">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>

                        <!-- Photo Counter Badge -->
                        <div class="ins-photo-badge" id="ins-photo-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <span id="ins-photo-indicator">1 / <?php echo !empty($first_spot['photos_list']) ? count($first_spot['photos_list']) : 1; ?></span>
                        </div>
                    </div>

                    <!-- Zone Title & Description -->
                    <div class="inspector-body" style="padding-top: 10px;">
                        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px;">
                            <h4 class="inspector-title font-serif" id="ins-title" style="margin-bottom: 6px; font-size: 1.18rem;"><?php echo $first_spot ? htmlspecialchars($first_spot['title']) : 'Farmhouse Kitchen & Organic Dining'; ?></h4>
                            <span id="ins-price-badge" class="font-sans" style="font-size: 0.8rem; font-weight: 700; color: var(--accent-gold); white-space: nowrap;"><?php echo (!empty($first_spot['room_rate'])) ? '₹' . number_format($first_spot['room_rate'], 0, '.', ',') . '/nt' : ''; ?></span>
                        </div>
                        <p class="inspector-desc font-sans" id="ins-desc" style="margin-bottom: 12px; line-height: 1.6; font-size: 0.84rem;">
                            <?php echo $first_spot ? htmlspecialchars($first_spot['description']) : 'Central hearth serving organic farm-to-table meals.'; ?>
                        </p>

                        <!-- Dynamic CTA Button (Reserve Stay or Explore) -->
                        <div class="inspector-actions" style="margin-top: auto; display: flex; gap: 8px;">
                            <a href="booking.php" id="ins-cta-btn" class="btn-primary font-sans" style="flex: 1; text-align: center; padding: 9px 14px; font-size: 0.82rem; text-decoration: none; justify-content: center; display: inline-flex; align-items: center; gap: 6px;">
                                <span id="ins-cta-text">Book Online</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <button type="button" id="ins-quick-book-btn" class="btn-outline font-sans open-booking-modal-btn" data-villa="treehouse" style="padding: 9px 12px; font-size: 0.82rem; border-color: rgba(197, 160, 89, 0.4); color: var(--accent-green);" title="Quick Reserve Modal">
                                <i class="fa-solid fa-bolt"></i> Quick Book
                            </button>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</section>

<!-- Inject Dynamic Sanctuary Spots Data for JavaScript Controller -->
<script>
window.sanctuarySpotsData = <?php echo json_encode($sanctuary_spots); ?>;
</script>
