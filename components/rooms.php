<?php
require_once __DIR__ . '/../admin/includes/db.php';
$rooms_list = get_all_rooms();
$treehouse = null;
$mudhouse = null;

foreach ($rooms_list as $r) {
    $stay_t = $r['stay_type'] ?? '';
    $slug = $r['slug'] ?? '';
    $title = $r['title'] ?? '';

    if ($stay_t === 'treehouse' || stripos($slug, 'tree') !== false || stripos($title, 'tree') !== false) {
        if (!$treehouse) $treehouse = $r;
    }
    if ($stay_t === 'mudhouse' || stripos($slug, 'mud') !== false || stripos($title, 'mud') !== false) {
        if (!$mudhouse) $mudhouse = $r;
    }
}

// Fallbacks if one stay type exists in DB
if (!$treehouse && !empty($rooms_list)) {
    foreach ($rooms_list as $r) {
        if (($r['stay_type'] ?? '') !== 'mudhouse') { $treehouse = $r; break; }
    }
}
if (!$mudhouse && !empty($rooms_list)) {
    foreach ($rooms_list as $r) {
        if (($r['stay_type'] ?? '') === 'mudhouse') { $mudhouse = $r; break; }
    }
    if (!$mudhouse) $mudhouse = $rooms_list[0];
}

// Ultimate fallbacks if DB is empty
if (!$treehouse) {
    $treehouse = [
        'title' => 'The Canopy Treehouse',
        'elevation' => '30FT ELEVATION',
        'rate_per_night' => 14500,
        'description' => 'Suspended 30 feet above the forest floor within ancient trees. Crafted with wild teak timber, an open cantilevered deck, and expansive curved glass with panoramic mist views.',
        'image_url' => 'assets/images/treehouse_exterior.png',
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg'
    ];
}
if (!$mudhouse) {
    $mudhouse = [
        'title' => 'The Earthen Mudhouse',
        'elevation' => 'COB HERITAGE',
        'rate_per_night' => 11500,
        'description' => 'Handcrafted from organic mountain clay, river stone, and local terracotta. Thermal earthen walls breathe with the mountain mist, staying naturally insulated day and night.',
        'image_url' => 'assets/images/mudhouse_exterior.png',
        'interior_360_url' => 'assets/images/mudhouse_360_pano.jpg'
    ];
}

// Determine default active stay
$default_stay = 'treehouse';
$has_db_treehouse = false;
$has_db_mudhouse = false;
foreach ($rooms_list as $r) {
    if (($r['stay_type'] ?? '') === 'treehouse' || stripos($r['slug'] ?? '', 'tree') !== false) $has_db_treehouse = true;
    if (($r['stay_type'] ?? '') === 'mudhouse' || stripos($r['slug'] ?? '', 'mud') !== false) $has_db_mudhouse = true;
}
if ($has_db_mudhouse && !$has_db_treehouse) {
    $default_stay = 'mudhouse';
}
$active_initial_stay = ($default_stay === 'mudhouse') ? $mudhouse : $treehouse;

// Fetch dynamic 360 tour stages & milestones from DB (or defaults)
$treehouse_tour = get_room_tour_stages($treehouse);
$mudhouse_tour = get_room_tour_stages($mudhouse);
$active_tour_stages = ($default_stay === 'mudhouse') ? $mudhouse_tour : $treehouse_tour;
?>
<!-- Fullscreen 3D Walkthrough: Canopy Treehouse & Earthen Mudhouse -->
<section id="rooms-experience" class="rooms-3d-fullscreen-section">
    <!-- WebGL Three.js Canvas -->
    <canvas id="rooms-webgl-canvas"></canvas>

    <!-- Subtle Cinematic Vignette -->
    <div class="tour-vignette"></div>

    <!-- Tour Overlay Content -->
    <div class="tour-overlay-container">
        <!-- Floating Stay Concept Selector Tabs (Treehouse vs Mudhouse) -->
        <div class="stay-selector-wrapper">
            <div class="stay-concept-tabs font-serif">
                <button type="button" class="stay-tab-btn <?php echo $default_stay === 'treehouse' ? 'active' : ''; ?>" data-stay="treehouse" id="tab-stay-treehouse">
                    <span class="stay-tab-pill font-sans"><i class="fa-solid fa-tree"></i> <?php echo htmlspecialchars(strtoupper($treehouse['elevation'] ?? '30FT ELEVATION')); ?></span>
                    <span class="stay-tab-name"><?php echo htmlspecialchars($treehouse['title']); ?></span>
                </button>
                <button type="button" class="stay-tab-btn <?php echo $default_stay === 'mudhouse' ? 'active' : ''; ?>" data-stay="mudhouse" id="tab-stay-mudhouse">
                    <span class="stay-tab-pill font-sans"><i class="fa-solid fa-house-chimney"></i> <?php echo htmlspecialchars(strtoupper($mudhouse['elevation'] ?? 'COB HERITAGE')); ?></span>
                    <span class="stay-tab-name"><?php echo htmlspecialchars($mudhouse['title']); ?></span>
                </button>
            </div>
        </div>

        <!-- Persistent Top Header -->
        <div class="treehouse-tour-header">
            <div class="tour-badge-row" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="tour-sub font-sans" id="tour-concept-badge"><?php echo htmlspecialchars($active_tour_stages['badge'] ?? 'FOOD FOREST IMMERSIVE ARCHITECTURAL TOUR'); ?></span>
                    <span class="gimbal-live-pill font-sans"><span class="rec-dot"></span> 360° CINEMATIC WALKTHROUGH</span>
                </div>
                <button type="button" class="tour-amenities-explore-btn font-sans" onclick="openRoomAmenitiesModal(window.currentActiveTourStay || '<?php echo $default_stay; ?>');" title="View all verified inclusions and facilities for this chalet" style="background: rgba(197, 160, 89, 0.18); border: 1px solid rgba(197, 160, 89, 0.45); color: #F5E8C7; padding: 6px 13px; border-radius: 20px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                    <i class="fa-solid fa-sparkles" style="color: #C5A059;"></i>
                    <span>Chalet Inclusions & Amenities</span>
                </button>
            </div>
            <h2 class="tour-title font-serif" id="tour-main-title"><?php echo htmlspecialchars($active_initial_stay['title']); ?></h2>
            <p class="tour-subtitle font-sans" id="tour-main-subtitle"><?php echo htmlspecialchars($active_tour_stages['subtitle'] ?? 'Scroll down to fly from the misty forest canopy directly inside the 360° suite.'); ?></p>
        </div>

        <!-- Mobile Touch 360 Drag Hint -->
        <div class="mobile-tour-hint font-sans" id="mobile-tour-hint">
            <span class="hint-icon"><i class="fa-solid fa-arrows-up-down-left-right"></i></span>
            <span>Drag around to explore 360°</span>
        </div>

        <!-- Stage 1: Exterior Front View (Visible initially) -->
        <div class="tour-card-floating stage-exterior is-visible" id="tour-stage-1">
            <span class="stage-pill font-sans" id="stage1-pill"><?php echo $active_tour_stages['stages'][0]['pill'] ?? ('<i class="fa-solid ' . ($default_stay === 'mudhouse' ? 'fa-house-chimney' : 'fa-tree') . '"></i> ' . htmlspecialchars(strtoupper($active_initial_stay['elevation'] ?? '1,600M ELEVATION'))); ?></span>
            <h3 class="stage-heading font-serif" id="stage1-heading"><?php echo htmlspecialchars($active_tour_stages['stages'][0]['heading'] ?? 'Front Exterior & Sanctuary Architecture'); ?></h3>
            <p class="stage-text font-sans" id="stage1-text">
                <?php echo htmlspecialchars($active_tour_stages['stages'][0]['text'] ?? $active_initial_stay['description']); ?>
            </p>
            <div style="margin-top: 10px; margin-bottom: 8px;">
                <button type="button" class="font-sans" onclick="openRoomAmenitiesModal(window.currentActiveTourStay || '<?php echo $default_stay; ?>');" style="background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.35); color: #F5E8C7; padding: 6px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-sparkles" style="color: #C5A059;"></i>
                    <span>View Room Inclusions (Wi-Fi, Meals, En-Suite)</span>
                </button>
            </div>
            <div class="tour-scroll-guide font-sans">
                <div class="mouse-scroll-icon"><span class="wheel-dot"></span></div>
                <span>Scroll down to step inside</span>
            </div>
        </div>

        <!-- Stage 2: Inside - Panoramic Bay Window / Garden Glasswork -->
        <div class="tour-card-floating stage-center" id="tour-stage-2">
            <span class="stage-pill font-sans" id="stage2-pill"><?php echo $active_tour_stages['stages'][1]['pill'] ?? '<i class="fa-solid fa-mountain-sun"></i> 01 • 180° VALLEY GLASSWORK'; ?></span>
            <h3 class="stage-heading font-serif" id="stage2-heading"><?php echo htmlspecialchars($active_tour_stages['stages'][1]['heading'] ?? 'Floor-to-Ceiling Curved Bay Window'); ?></h3>
            <p class="stage-text font-sans" id="stage2-text">
                <?php echo htmlspecialchars($active_tour_stages['stages'][1]['text'] ?? 'An expansive architectural curved window framing floating clouds, high-altitude tea valleys, and morning mountain mist.'); ?>
            </p>
        </div>

        <!-- Stage 3: Inside - Canopy Deck / Orchard Veranda -->
        <div class="tour-card-floating stage-right" id="tour-stage-3">
            <span class="stage-pill font-sans" id="stage3-pill"><?php echo $active_tour_stages['stages'][2]['pill'] ?? '<i class="fa-solid fa-wind"></i> 02 • MISTY CANOPY DECK'; ?></span>
            <h3 class="stage-heading font-serif" id="stage3-heading"><?php echo htmlspecialchars($active_tour_stages['stages'][2]['heading'] ?? 'Private Cantilevered Timber Balcony'); ?></h3>
            <p class="stage-text font-sans" id="stage3-text">
                <?php echo htmlspecialchars($active_tour_stages['stages'][2]['text'] ?? 'Step directly outside into the clouds. An open timber deck perched 30 feet high in ancient trees for birdsong and organic mountain tea.'); ?>
            </p>
        </div>

        <!-- Stage 4: Inside - Bed Suite / Cob Daybed Alcove -->
        <div class="tour-card-floating stage-left" id="tour-stage-4">
            <span class="stage-pill font-sans" id="stage4-pill"><?php echo $active_tour_stages['stages'][3]['pill'] ?? '<i class="fa-solid fa-bed"></i> 03 • WILD TEAK BED SUITE'; ?></span>
            <h3 class="stage-heading font-serif" id="stage4-heading"><?php echo htmlspecialchars($active_tour_stages['stages'][3]['heading'] ?? 'Handcrafted Artisan King Bed'); ?></h3>
            <p class="stage-text font-sans" id="stage4-text">
                <?php echo htmlspecialchars($active_tour_stages['stages'][3]['text'] ?? 'Hand-hewn from natural wild teak, dressed in 100% breathable organic linen, accompanied by handcrafted bedside lanterns and radial wooden ceiling beams.'); ?>
            </p>
        </div>

        <!-- Stage 5: Inside - Hearth & Lounge -->
        <div class="tour-card-floating stage-right" id="tour-stage-5">
            <span class="stage-pill font-sans" id="stage5-pill"><?php echo $active_tour_stages['stages'][4]['pill'] ?? '<i class="fa-solid fa-fire"></i> 04 • THE FOREST HEARTH'; ?></span>
            <h3 class="stage-heading font-serif" id="stage5-heading"><?php echo htmlspecialchars($active_tour_stages['stages'][4]['heading'] ?? 'Hand-Cut Stone Fireplace & Lounge'); ?></h3>
            <p class="stage-text font-sans" id="stage5-text">
                <?php echo htmlspecialchars($active_tour_stages['stages'][4]['text'] ?? 'Warm authentic stone fireplace with crackling hearth wood, curved luxury sofa, and library nook to relax on crisp mountain evenings.'); ?>
            </p>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                <button type="button" class="btn-primary tour-cta-btn open-booking-modal-btn font-sans" id="tour-cta-btn" data-villa="<?php echo $default_stay; ?>" style="flex: 1; min-width: 170px;">
                    <span id="tour-cta-label">Reserve <?php echo htmlspecialchars($active_initial_stay['title']); ?></span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
                <button type="button" class="font-sans" onclick="openRoomAmenitiesModal(window.currentActiveTourStay || '<?php echo $default_stay; ?>');" title="View all verified inclusions and facilities for this chalet" style="background: rgba(197, 160, 89, 0.18); border: 1px solid rgba(197, 160, 89, 0.45); color: #F5E8C7; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-sparkles" style="color: #C5A059;"></i>
                    <span>Inclusions</span>
                </button>
            </div>
        </div>

        <!-- Mobile Stage Stepper Bar (visible on mobile only) -->
        <div class="mobile-tour-stepper font-sans" id="mobile-tour-stepper">
            <button type="button" class="mobile-step-arrow" id="mobile-tour-prev" aria-label="Previous Stage">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="mobile-step-dots" id="mobile-step-dots">
                <span class="mobile-dot active" data-step="1"></span>
                <span class="mobile-dot" data-step="2"></span>
                <span class="mobile-dot" data-step="3"></span>
                <span class="mobile-dot" data-step="4"></span>
                <span class="mobile-dot" data-step="5"></span>
            </div>
            <span class="mobile-step-name font-sans" id="mobile-step-label"><?php echo htmlspecialchars($active_tour_stages['progressLabels'][0] ?? 'Exterior Sanctuary'); ?></span>
            <button type="button" class="mobile-step-arrow" id="mobile-tour-next" aria-label="Next Stage">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Bottom Tour Progress Indicator (5 Milestones) -->
        <div class="treehouse-tour-progress font-sans">
            <div class="tour-step-item active" id="prog-step-1">
                <span class="step-badge">01</span>
                <span class="step-label" id="prog-label-1"><?php echo htmlspecialchars($active_tour_stages['progressLabels'][0] ?? 'Exterior'); ?></span>
            </div>
            <div class="tour-step-divider"></div>
            <div class="tour-step-item" id="prog-step-2">
                <span class="step-badge">02</span>
                <span class="step-label" id="prog-label-2"><?php echo htmlspecialchars($active_tour_stages['progressLabels'][1] ?? 'Panoramic Bay'); ?></span>
            </div>
            <div class="tour-step-divider"></div>
            <div class="tour-step-item" id="prog-step-3">
                <span class="step-badge">03</span>
                <span class="step-label" id="prog-label-3"><?php echo htmlspecialchars($active_tour_stages['progressLabels'][2] ?? 'Forest Deck'); ?></span>
            </div>
            <div class="tour-step-divider"></div>
            <div class="tour-step-item" id="prog-step-4">
                <span class="step-badge">04</span>
                <span class="step-label" id="prog-label-4"><?php echo htmlspecialchars($active_tour_stages['progressLabels'][3] ?? 'Master Suite'); ?></span>
            </div>
            <div class="tour-step-divider"></div>
            <div class="tour-step-item" id="prog-step-5">
                <span class="step-badge">05</span>
                <span class="step-label" id="prog-label-5"><?php echo htmlspecialchars($active_tour_stages['progressLabels'][4] ?? 'Stone Hearth'); ?></span>
            </div>
        </div>
    </div>

    <!-- Mobile Responsive Fallback Layout -->
    <div class="tour-mobile-fallback" id="webgl-fallback-container">
        <div class="container">
            <div class="text-center" style="margin-bottom: 40px;">
                <span class="section-label">Food Forest Signature Stays</span>
                <h3 class="section-title font-serif" style="color: var(--accent-green);">Architectural Sanctuary Stays</h3>
                <p class="font-sans" style="color: var(--text-light); max-width: 650px; margin: 12px auto 0;">
                    Experience luxury elevated 30 feet in the forest canopy or grounded in organic earthen cob.
                </p>
            </div>

            <!-- Card 1: Treehouse -->
            <div class="mobile-tour-card">
                <div class="mobile-tour-img-wrap">
                    <img src="<?php echo htmlspecialchars(($treehouse['image_url'] ?? '') ?: 'assets/images/treehouse_exterior.png'); ?>" alt="<?php echo htmlspecialchars($treehouse['title']); ?>" class="mobile-tour-img" onerror="this.src='assets/images/treehouse_exterior.png'">
                    <span class="mobile-tour-badge"><?php echo htmlspecialchars($treehouse['title']); ?></span>
                </div>
                <div class="mobile-tour-content">
                    <h4 class="font-serif"><?php echo htmlspecialchars($treehouse['title']); ?></h4>
                    <p class="font-sans">
                        <?php echo htmlspecialchars($treehouse['description']); ?>
                    </p>
                    <div style="display: flex; gap: 8px; margin-top: 15px;">
                        <button type="button" class="btn-primary open-booking-modal-btn font-sans" data-villa="treehouse" style="flex: 1;">
                            <span>Reserve <?php echo htmlspecialchars($treehouse['title']); ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="button" class="font-sans" onclick="openRoomAmenitiesModal('treehouse')" style="background: rgba(197, 160, 89, 0.18); border: 1px solid rgba(197, 160, 89, 0.45); color: #F5E8C7; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; white-space: nowrap;">
                            <i class="fa-solid fa-sparkles" style="color: #C5A059;"></i> Inclusions
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card 2: Mudhouse -->
            <div class="mobile-tour-card" style="margin-top: 24px;">
                <div class="mobile-tour-img-wrap">
                    <img src="<?php echo htmlspecialchars(($mudhouse['image_url'] ?? '') ?: 'assets/images/mudhouse_exterior.png'); ?>" alt="<?php echo htmlspecialchars($mudhouse['title']); ?>" class="mobile-tour-img" onerror="this.src='assets/images/mudhouse_exterior.png'">
                    <span class="mobile-tour-badge"><?php echo htmlspecialchars($mudhouse['title']); ?></span>
                </div>
                <div class="mobile-tour-content">
                    <h4 class="font-serif"><?php echo htmlspecialchars($mudhouse['title']); ?></h4>
                    <p class="font-sans">
                        <?php echo htmlspecialchars($mudhouse['description']); ?>
                    </p>
                    <div style="display: flex; gap: 8px; margin-top: 15px;">
                        <button type="button" class="btn-primary open-booking-modal-btn font-sans" data-villa="mudhouse" style="flex: 1;">
                            <span>Reserve <?php echo htmlspecialchars($mudhouse['title']); ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="button" class="font-sans" onclick="openRoomAmenitiesModal('mudhouse')" style="background: rgba(197, 160, 89, 0.18); border: 1px solid rgba(197, 160, 89, 0.45); color: #F5E8C7; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; white-space: nowrap;">
                            <i class="fa-solid fa-sparkles" style="color: #C5A059;"></i> Inclusions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    window.ESTATE_DYNAMIC_STAY = {
        defaultStay: <?php echo json_encode($default_stay); ?>,
        treehouse: {
            title: <?php echo json_encode($treehouse['title']); ?>,
            badge: <?php echo json_encode($treehouse_tour['badge'] ?? ('FOOD FOREST • ' . strtoupper($treehouse['elevation'] ?? '30FT ELEVATION'))); ?>,
            subtitle: <?php echo json_encode($treehouse_tour['subtitle'] ?? ('Scroll down to fly inside the 360° ' . ($treehouse['title'] ?? 'suite') . '.')); ?>,
            ctaLabel: <?php echo json_encode('Reserve ' . ($treehouse['title'] ?? 'Canopy Treehouse')); ?>,
            ctaVilla: 'treehouse',
            stages: <?php echo json_encode($treehouse_tour['stages']); ?>,
            progressLabels: <?php echo json_encode($treehouse_tour['progressLabels']); ?>,
            <?php if (!empty($treehouse['image_url'])): ?>
            exteriorImg: <?php echo json_encode($treehouse['image_url']); ?>,
            <?php endif; ?>
            <?php if (!empty($treehouse['interior_360_url'])): ?>
            interiorImg: <?php echo json_encode($treehouse['interior_360_url']); ?>,
            <?php endif; ?>
        },
        mudhouse: {
            title: <?php echo json_encode($mudhouse['title']); ?>,
            badge: <?php echo json_encode($mudhouse_tour['badge'] ?? ('FOOD FOREST • ' . strtoupper($mudhouse['elevation'] ?? 'COB HERITAGE'))); ?>,
            subtitle: <?php echo json_encode($mudhouse_tour['subtitle'] ?? ('Scroll down to fly inside the 360° ' . ($mudhouse['title'] ?? 'suite') . '.')); ?>,
            ctaLabel: <?php echo json_encode('Reserve ' . ($mudhouse['title'] ?? 'Earthen Mudhouse')); ?>,
            ctaVilla: 'mudhouse',
            stages: <?php echo json_encode($mudhouse_tour['stages']); ?>,
            progressLabels: <?php echo json_encode($mudhouse_tour['progressLabels']); ?>,
            <?php if (!empty($mudhouse['image_url'])): ?>
            exteriorImg: <?php echo json_encode($mudhouse['image_url']); ?>,
            <?php endif; ?>
            <?php if (!empty($mudhouse['interior_360_url'])): ?>
            interiorImg: <?php echo json_encode($mudhouse['interior_360_url']); ?>,
            <?php endif; ?>
        }
    };
    </script>
</section>