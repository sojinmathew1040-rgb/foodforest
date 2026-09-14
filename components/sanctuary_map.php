<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
ensure_sanctuary_spots_table_exists($pdo);
$sanctuary_spots = get_all_sanctuary_spots(true);
$sec_label = get_setting('sanctuary_section_label', 'The Living Landscape');
$sec_title = get_setting('sanctuary_section_title', 'An Untamed Sanctuary');

// Calculate dynamic SVG route trail connecting spots in numerical sequence (1 -> 2 -> 3 -> 4...)
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
            // Gentle organic mountain trail curvature
            $cx = $mx - ($dy * 0.12);
            $cy = $my + ($dx * 0.12);
            $route_d .= " Q " . round($cx, 1) . "," . round($cy, 1) . " " . round($p1['x'], 1) . "," . round($p1['y'], 1);
        }
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

                        <!-- Connected Mountain Route Trail (1 -> 2 -> 3 -> 4...) -->
                        <?php if (!empty($route_d)): ?>
                            <path class="sanctuary-trail-aura" d="<?php echo $route_d; ?>" 
                                  fill="none" stroke="rgba(197, 160, 89, 0.2)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#map-glow)" />
                            <path class="sanctuary-route-trail" d="<?php echo $route_d; ?>" 
                                  fill="none" stroke="#C5A059" stroke-width="2.2" stroke-dasharray="6,6" stroke-linecap="round" stroke-linejoin="round" />
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
                        <?php foreach ($sanctuary_spots as $idx => $sp): ?>
                            <div class="sanctuary-pin <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                 data-zone="<?php echo (int)$sp['id']; ?>" 
                                 data-spot-num="<?php echo (int)$sp['spot_number']; ?>"
                                 style="top: <?php echo (float)$sp['y_coord']; ?>%; left: <?php echo (float)$sp['x_coord']; ?>%;">
                                <div class="pin-beacon"></div>
                                <div class="pin-marker">
                                    <span class="pin-index"><?php echo sprintf('%02d', $sp['spot_number']); ?></span>
                                </div>
                                <div class="pin-tooltip">
                                    <span class="pin-title"><?php echo htmlspecialchars($sp['title']); ?></span>
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
                            <span class="inspector-zone-num" id="ins-zone-num">ROUTE SPOT <?php echo $first_spot ? sprintf('%02d', $first_spot['spot_number']) : '01'; ?></span>
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
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <span id="ins-photo-indicator">1 / <?php echo !empty($first_spot['photos_list']) ? count($first_spot['photos_list']) : 1; ?></span>
                        </div>
                    </div>

                    <!-- Zone Title & Description -->
                    <div class="inspector-body" style="padding-top: 10px;">
                        <h4 class="inspector-title font-serif" id="ins-title" style="margin-bottom: 8px; font-size: 1.18rem;"><?php echo $first_spot ? htmlspecialchars($first_spot['title']) : 'Farmhouse Kitchen & Organic Dining'; ?></h4>
                        <p class="inspector-desc font-sans" id="ins-desc" style="margin-bottom: 0; line-height: 1.6; font-size: 0.84rem;">
                            <?php echo $first_spot ? htmlspecialchars($first_spot['description']) : 'Central hearth serving organic farm-to-table meals.'; ?>
                        </p>
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
