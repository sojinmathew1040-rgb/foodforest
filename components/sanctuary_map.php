<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
ensure_sanctuary_spots_table_exists($pdo);
$sanctuary_spots = get_all_sanctuary_spots(true);
$sec_label = get_setting('sanctuary_section_label', 'The Living Landscape');
$sec_title = get_setting('sanctuary_section_title', 'An Untamed Sanctuary');

// Circular/Elliptical Main Estate Loop Road & Dynamic Cottage Sub-Branches
$loop_cx = 400;
$loop_cy = 258;
$loop_rx = 240;
$loop_ry = 162;

// Main Estate Loop Road SVG Path (Smooth closed circuit)
$main_loop_d = "M 390,420 " .
               "C 280,420 160,340 160,260 " .
               "C 160,180 280,95 400,95 " .
               "C 520,95 640,180 640,260 " .
               "C 640,340 520,420 390,420 Z";

// Main Entrance South Avenue Drive
$entrance_drive_d = "M 390,496 L 390,420";

// Loop Junction Nodes (Paved waypoints around the ring road)
$loop_waypoint_nodes = [
    ['x' => 390, 'y' => 420, 'label' => 'South Gate Junction'],
    ['x' => 220, 'y' => 375, 'label' => 'Farmstead Turn'],
    ['x' => 160, 'y' => 260, 'label' => 'West Ridge Way'],
    ['x' => 220, 'y' => 145, 'label' => 'High Vista Junction'],
    ['x' => 400, 'y' => 95,  'label' => 'North Alpine Peak'],
    ['x' => 580, 'y' => 145, 'label' => 'East Brook Terraces'],
    ['x' => 640, 'y' => 260, 'label' => 'Perennial Brook Bridge'],
    ['x' => 580, 'y' => 375, 'label' => 'Southeast Orchard Loop']
];

if (!function_exists('calculate_loop_junction')) {
    function calculate_loop_junction($spot_x, $spot_y, $cx = 400, $cy = 258, $rx = 240, $ry = 162) {
        $angle = atan2($spot_y - $cy, $spot_x - $cx);
        $jx = $cx + $rx * cos($angle);
        $jy = $cy + $ry * sin($angle);
        return ['x' => round($jx, 1), 'y' => round($jy, 1), 'angle' => $angle];
    }
}

$branch_paths = [];
$junction_nodes = [];
if (!empty($sanctuary_spots)) {
    foreach ($sanctuary_spots as $sp) {
        $sx = ($sp['x_coord'] / 100.0) * 800;
        $sy = ($sp['y_coord'] / 100.0) * 520;
        $junc = calculate_loop_junction($sx, $sy, $loop_cx, $loop_cy, $loop_rx, $loop_ry);
        $mid_x = ($junc['x'] + $sx) / 2;
        $mid_y = ($junc['y'] + $sy) / 2;
        $offset_x = ($sy - $junc['y']) * 0.18;
        $offset_y = -($sx - $junc['x']) * 0.18;
        $ctrl_x = $mid_x + $offset_x;
        $ctrl_y = $mid_y + $offset_y;

        $branch_paths[] = [
            'd' => "M " . $junc['x'] . "," . $junc['y'] . " Q " . round($ctrl_x, 1) . "," . round($ctrl_y, 1) . " " . round($sx, 1) . "," . round($sy, 1),
            'spot_id' => $sp['id'],
            'spot_title' => $sp['title']
        ];
        $junction_nodes[] = $junc;
    }
}

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
                    <i class="fa-solid fa-water"></i> 🌊 Brook & Glades
                </button>
                <a href="booking.php" class="sanctuary-filter-btn map-bookmyshow-link" title="Open Interactive Estate Booking Page">
                    <i class="fa-solid fa-calendar-check"></i> Book Chalets Online <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; margin-left: 3px;"></i>
                </a>
            </div>
        </div>


        <!-- Interactive Estate Map & Exploration Console -->
        <div class="sanctuary-explorer-wrapper scroll-reveal">

            <!-- Two-Column Interactive Console -->
            <div class="sanctuary-console-grid">
                
                <!-- Left: Topographic Map Canvas -->
                <div class="sanctuary-map-board" id="sanctuary-map-board">
                    <!-- Topographic Background SVG -->
                    <svg class="topo-svg-canvas" viewBox="0 0 800 520" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
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

                        <!-- Natural Mountain Brook / River Stream (meandering) -->
                        <path class="topo-stream" d="M 120,-20 C 140,80 190,140 240,210 C 290,280 340,310 410,380 C 470,440 520,480 580,540" 
                              stroke="url(#stream-gradient)" stroke-width="4.5" fill="none" stroke-linecap="round" filter="url(#map-glow)" />
                        <!-- Tributary Stream -->
                        <path class="topo-stream-sub" d="M 390,260 C 430,280 470,320 480,350" 
                              stroke="url(#stream-gradient)" stroke-width="2.2" fill="none" stroke-linecap="round" opacity="0.75" />

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

                        <!-- 2. Main Circular / Elliptical Estate Loop Promenade Road (Permanently Visible) -->
                        <!-- Entrance Driveway -->
                        <path d="<?php echo $entrance_drive_d; ?>" fill="none" stroke="rgba(197, 160, 89, 0.28)" stroke-width="8" stroke-linecap="round" filter="url(#map-glow)" />
                        <path d="<?php echo $entrance_drive_d; ?>" fill="none" stroke="#D4AF37" stroke-width="3" stroke-dasharray="6,4" stroke-linecap="round" />

                        <!-- Circular Ring Road Aura & Paved Trail -->
                        <path class="sanctuary-spine-aura" d="<?php echo $main_loop_d; ?>" 
                              fill="none" stroke="rgba(197, 160, 89, 0.28)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" filter="url(#map-glow)" />
                        <path class="sanctuary-spine-trail" d="<?php echo $main_loop_d; ?>" 
                              fill="none" stroke="#D4AF37" stroke-width="3.2" stroke-dasharray="10,6" stroke-linecap="round" stroke-linejoin="round" />

                        <!-- Entrance Gate Landmark -->
                        <g transform="translate(390, 492)">
                            <circle cx="0" cy="0" r="6" fill="#14281c" stroke="#D4AF37" stroke-width="2" filter="url(#map-glow)" />
                            <circle cx="0" cy="0" r="2.5" fill="#56C2C9" />
                            <text x="14" y="3" fill="#D4AF37" font-size="9" font-family="'Cinzel', Georgia, serif" font-weight="bold" letter-spacing="1">MAIN ENTRANCE</text>
                        </g>

                        <!-- Loop Road Waypoints / Junction Nodes -->
                        <?php foreach ($loop_waypoint_nodes as $lwn): ?>
                            <circle cx="<?php echo $lwn['x']; ?>" cy="<?php echo $lwn['y']; ?>" r="4.5" fill="#14281c" stroke="#D4AF37" stroke-width="1.8" filter="url(#map-glow)" />
                            <circle cx="<?php echo $lwn['x']; ?>" cy="<?php echo $lwn['y']; ?>" r="1.8" fill="#56C2C9" />
                        <?php endforeach; ?>

                        <!-- 3. Sub-Branch Pathways to Cottages & Spots -->
                        <?php if (!empty($branch_paths)): ?>
                            <?php foreach ($branch_paths as $bp): ?>
                                <path class="sanctuary-branch-trail-aura" d="<?php echo $bp['d']; ?>" 
                                      fill="none" stroke="rgba(86, 194, 201, 0.25)" stroke-width="5" stroke-linecap="round" filter="url(#map-glow)" />
                                <path class="sanctuary-branch-trail" data-branch-spot="<?php echo $bp['spot_id']; ?>" d="<?php echo $bp['d']; ?>" 
                                      fill="none" stroke="#C5A059" stroke-width="2" stroke-dasharray="4,4" stroke-linecap="round" opacity="0.9" />
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </svg>

                    <!-- Luxury Compass Rose -->
                    <div class="map-compass font-serif">
                        <span class="compass-n">N</span>
                        <div class="compass-pointer"></div>
                        <span class="compass-coords">KANTHALLOOR · 1,600M</span>
                    </div>

                    <!-- Water Brook Tag -->
                    <div class="map-stream-badge font-sans">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12c3-3 6-3 9 0s6 3 9 0"/><path d="M2 18c3-3 6-3 9 0s6 3 9 0"/></svg>
                        Perennial Brook
                    </div>

                    <!-- Dynamic Hotspot Pins on the Map -->
                    <div class="sanctuary-pins-container">
                        <?php foreach ($sanctuary_spots as $idx => $sp): 
                            $is_stay = !empty($sp['is_stay']) || $sp['category'] === 'stays';
                            $struct = $sp['structure_type'] ?? 'single_hut';
                            $is_duplex = ($is_stay && ($struct === 'duplex_hut' || stripos($sp['title'], 'duplex') !== false));

                            if ($is_duplex) {
                                $stay_class = 'is-stay-pin is-duplex-pin';
                                $stay_icon = '<i class="fa-solid fa-layer-group"></i>';
                                $stay_tag = '🏰 DUPLEX CHALET (2 SUITES)';
                            } elseif ($is_stay) {
                                $stay_class = 'is-stay-pin is-single-pin';
                                $stay_icon = '<i class="fa-solid fa-house-chimney"></i>';
                                $stay_tag = '🏡 SINGLE COTTAGE';
                            } else {
                                $stay_class = 'is-facility-pin';
                                $stay_tag = '🌿 ESTATE HUB';
                                if ($sp['category'] === 'dining') {
                                    $stay_icon = '<i class="fa-solid fa-utensils"></i>';
                                } elseif ($sp['category'] === 'amenities') {
                                    $stay_icon = '<i class="fa-solid fa-water"></i>';
                                } else {
                                    $stay_icon = '<i class="fa-solid fa-tree"></i>';
                                }
                            }
                        ?>
                            <div class="sanctuary-pin <?php echo $idx === 0 ? 'active' : ''; ?> <?php echo $stay_class; ?>" 
                                 data-zone="<?php echo (int)$sp['id']; ?>" 
                                 data-spot-num="<?php echo (int)$sp['spot_number']; ?>"
                                 data-category="<?php echo htmlspecialchars($sp['category'] ?? 'nature'); ?>"
                                 data-is-stay="<?php echo $is_stay ? '1' : '0'; ?>"
                                 data-is-duplex="<?php echo $is_duplex ? '1' : '0'; ?>"
                                 data-structure="<?php echo htmlspecialchars($struct); ?>"
                                 style="top: <?php echo (float)$sp['y_coord']; ?>%; left: <?php echo (float)$sp['x_coord']; ?>%;">
                                <div class="pin-beacon"></div>
                                <div class="pin-marker">
                                    <span class="pin-stay-icon"><?php echo $stay_icon; ?></span>
                                    <span class="pin-index"><?php echo sprintf('%02d', $sp['spot_number']); ?></span>
                                    <?php if ($is_duplex): ?>
                                        <span class="pin-duplex-indicator" title="2-Suite Duplex">2S</span>
                                    <?php endif; ?>
                                </div>
                                <div class="pin-tooltip">
                                    <span class="pin-stay-tag"><?php echo $stay_tag; ?></span>
                                    <span class="pin-title"><?php echo htmlspecialchars($sp['title']); ?></span>
                                    <?php if ($is_stay && !empty($sp['room_rate'])): ?>
                                        <span class="pin-price font-sans">From ₹<?php echo number_format($sp['room_rate'], 0, '.', ','); ?>/nt</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: Dynamic Interactive Zone Inspector Card -->
                <div class="sanctuary-inspector-card" id="sanctuary-inspector">
                    
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
