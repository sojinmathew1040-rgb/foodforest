<?php
// =========================================================================
// Food Forest Sanctuary — Interactive Chalet Photo Lightbox Modal
// Allows guests to view and browse full multi-photo galleries for chalets,
// including Left Suite (Wing A), Right Suite (Wing B), and Exterior views.
// =========================================================================

if (!isset($rooms) && !isset($rooms_list)) {
    require_once __DIR__ . '/../admin/includes/db.php';
    $gallery_modal_rooms = get_all_rooms();
} else {
    $gallery_modal_rooms = $rooms ?? ($rooms_list ?? []);
}

// Prepare comprehensive galleries data dictionary
$clb_galleries = [];
foreach ($gallery_modal_rooms as $rm) {
    $slug = $rm['slug'] ?? '';
    if (!$slug) continue;

    $is_duplex = ($rm['structure_type'] ?? '') === 'duplex_hut';
    
    // General / Exterior
    $gen_photos = [];
    if (!empty($rm['photos_meta_list'])) {
        foreach ($rm['photos_meta_list'] as $p) {
            $u = is_array($p) ? ($p['url'] ?? '') : (string)$p;
            if ($u && $u !== '[object Object]') {
                $gen_photos[] = [
                    'url' => $u,
                    'title' => is_array($p) ? ($p['title'] ?? '') : '',
                    'desc' => is_array($p) ? ($p['description'] ?? '') : '',
                    'wing' => 'general',
                    'wing_label' => 'Exterior & Grounds'
                ];
            }
        }
    } elseif (!empty($rm['photos_list'])) {
        foreach ($rm['photos_list'] as $u) {
            if ($u && $u !== '[object Object]') {
                $gen_photos[] = [
                    'url' => (string)$u,
                    'title' => 'Exterior & Surroundings',
                    'desc' => '',
                    'wing' => 'general',
                    'wing_label' => 'Exterior & Grounds'
                ];
            }
        }
    }

    // Left Suite (Wing A)
    $left_photos = [];
    if (!empty($rm['photos_left_meta_list'])) {
        foreach ($rm['photos_left_meta_list'] as $p) {
            $u = is_array($p) ? ($p['url'] ?? '') : (string)$p;
            if ($u && $u !== '[object Object]') {
                $left_photos[] = [
                    'url' => $u,
                    'title' => is_array($p) ? ($p['title'] ?? '') : '',
                    'desc' => is_array($p) ? ($p['description'] ?? '') : '',
                    'wing' => 'left',
                    'wing_label' => 'Wing A • Left Suite'
                ];
            }
        }
    } elseif (!empty($rm['photos_left_list'])) {
        foreach ($rm['photos_left_list'] as $u) {
            if ($u && $u !== '[object Object]') {
                $left_photos[] = [
                    'url' => (string)$u,
                    'title' => 'Left Suite Interior',
                    'desc' => '',
                    'wing' => 'left',
                    'wing_label' => 'Wing A • Left Suite'
                ];
            }
        }
    }

    // Right Suite (Wing B)
    $right_photos = [];
    if (!empty($rm['photos_right_meta_list'])) {
        foreach ($rm['photos_right_meta_list'] as $p) {
            $u = is_array($p) ? ($p['url'] ?? '') : (string)$p;
            if ($u && $u !== '[object Object]') {
                $right_photos[] = [
                    'url' => $u,
                    'title' => is_array($p) ? ($p['title'] ?? '') : '',
                    'desc' => is_array($p) ? ($p['description'] ?? '') : '',
                    'wing' => 'right',
                    'wing_label' => 'Wing B • Right Suite'
                ];
            }
        }
    } elseif (!empty($rm['photos_right_list'])) {
        foreach ($rm['photos_right_list'] as $u) {
            if ($u && $u !== '[object Object]') {
                $right_photos[] = [
                    'url' => (string)$u,
                    'title' => 'Right Suite Interior',
                    'desc' => '',
                    'wing' => 'right',
                    'wing_label' => 'Wing B • Right Suite'
                ];
            }
        }
    }

    // Fallback to image_url if completely empty
    if (empty($gen_photos) && empty($left_photos) && empty($right_photos) && !empty($rm['image_url'])) {
        $gen_photos[] = [
            'url' => $rm['image_url'],
            'title' => $rm['title'] ?? 'Chalet Showcase',
            'desc' => '',
            'wing' => 'general',
            'wing_label' => 'Primary Showcase'
        ];
    }

    // Combined all photos list
    $all_photos = array_merge($gen_photos, $left_photos, $right_photos);

    $clb_galleries[$slug] = [
        'slug' => $slug,
        'title' => $rm['title'] ?? 'Chalet',
        'elevation' => $rm['elevation'] ?? '',
        'stay_type' => $rm['stay_type'] ?? 'treehouse',
        'structure_type' => $rm['structure_type'] ?? 'single_hut',
        'is_duplex' => $is_duplex,
        'rate' => (float)($rm['rate_per_night'] ?? 0),
        'single_rate' => (float)($rm['single_room_rate'] ?? ($rm['rate_per_night'] ?? 0)),
        'description' => $rm['description'] ?? '',
        'photos_all' => $all_photos,
        'photos_general' => $gen_photos,
        'photos_left' => $left_photos,
        'photos_right' => $right_photos
    ];
}
?>

<!-- =========================================================================
     CHALET PHOTO LIGHTBOX MODAL MARKUP
     ========================================================================= -->
<div id="chalet-photo-lightbox-modal" class="chalet-lightbox-overlay" style="display: none;" role="dialog" aria-modal="true" aria-label="Chalet Photo Gallery Lightbox">
    <div class="chalet-lightbox-dialog font-sans">
        
        <!-- Lightbox Header Bar -->
        <div class="clb-header">
            <div class="clb-header-left">
                <div class="clb-title-group">
                    <span id="clb-type-badge" class="clb-badge-pill">🏡 SINGLE COTTAGE</span>
                    <span id="clb-elevation-badge" class="clb-badge-elevation">1,600M ELEVATION</span>
                </div>
                <h3 id="clb-chalet-title" class="clb-title font-serif">High-Altitude Canopy Treehouse</h3>
            </div>

            <!-- Duplex Wing Filter Tabs -->
            <div id="clb-wing-tabs" class="clb-wing-tabs-bar" style="display: none;">
                <button type="button" class="clb-tab-btn active" data-clb-wing="all" onclick="filterChaletLightboxWing('all', this);">
                    <i class="fa-solid fa-border-all"></i> All Photos (<span id="clb-tab-count-all">0</span>)
                </button>
                <button type="button" class="clb-tab-btn tab-left-btn" data-clb-wing="left" onclick="filterChaletLightboxWing('left', this);">
                    <i class="fa-solid fa-door-open"></i> Wing A / Left (<span id="clb-tab-count-left">0</span>)
                </button>
                <button type="button" class="clb-tab-btn tab-right-btn" data-clb-wing="right" onclick="filterChaletLightboxWing('right', this);">
                    <i class="fa-solid fa-door-open"></i> Wing B / Right (<span id="clb-tab-count-right">0</span>)
                </button>
                <button type="button" class="clb-tab-btn tab-gen-btn" data-clb-wing="general" onclick="filterChaletLightboxWing('general', this);">
                    <i class="fa-solid fa-mountain-sun"></i> Grounds &amp; Exterior (<span id="clb-tab-count-gen">0</span>)
                </button>
            </div>

            <!-- Action Controls: Direct Book & Close -->
            <div class="clb-header-actions">
                <a id="clb-book-direct-btn" href="booking.php" class="clb-btn-book font-sans" title="Reserve this chalet online">
                    <i class="fa-solid fa-calendar-check"></i> <span>Reserve Chalet</span>
                </a>
                <button type="button" class="clb-btn-close" onclick="closeChaletGalleryModal();" aria-label="Close Lightbox" title="Close Lightbox (Esc)">
                    ✕
                </button>
            </div>
        </div>

        <!-- Lightbox Main Stage -->
        <div class="clb-stage-container">
            <div class="clb-stage-media-wrap">
                <img id="clb-main-image" src="" alt="Chalet Showcase" class="clb-main-img" onerror="this.src='assets/images/01 (25).jpeg';">
                
                <!-- Floating Nav Arrows -->
                <button type="button" id="clb-prev-btn" class="clb-nav-arrow clb-arrow-prev" onclick="stepChaletLightbox(-1);" aria-label="Previous Photo" title="Previous Photo (Left Arrow)">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" id="clb-next-btn" class="clb-nav-arrow clb-arrow-next" onclick="stepChaletLightbox(1);" aria-label="Next Photo" title="Next Photo (Right Arrow)">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <!-- Floating Counter Badge -->
                <div class="clb-photo-counter font-sans">
                    <i class="fa-solid fa-camera"></i> <span id="clb-counter">1 / 1</span>
                </div>

                <!-- Floating Wing Tag Badge -->
                <div id="clb-floating-wing-tag" class="clb-floating-wing-tag font-sans" style="display: none;">
                    Wing A • Left Suite
                </div>

                <!-- Floating Caption Banner -->
                <div id="clb-caption-box" class="clb-caption-banner font-sans" style="display: none;">
                    <div id="clb-caption-title" class="clb-caption-title"></div>
                    <div id="clb-caption-desc" class="clb-caption-desc"></div>
                </div>
            </div>
        </div>

        <!-- Lightbox Bottom Thumbnails Strip -->
        <div class="clb-thumbs-bar">
            <div id="clb-thumbs-scroller" class="clb-thumbs-scroller">
                <!-- Injected dynamically via JS -->
            </div>
        </div>

    </div>
</div>

<style>
/* =========================================================================
   LUXURY DARK CHALET LIGHTBOX STYLING
   ========================================================================= */
.chalet-lightbox-overlay {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: rgba(3, 10, 6, 0.94);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.chalet-lightbox-overlay.is-open {
    opacity: 1;
    pointer-events: auto;
}
.chalet-card-media:hover .chalet-media-hover-overlay {
    opacity: 1 !important;
}
.chalet-lightbox-dialog {
    width: 100%;
    max-width: 1180px;
    height: 94vh;
    max-height: 860px;
    background: #07150C;
    border: 1.5px solid rgba(197, 160, 89, 0.45);
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8), 0 0 35px rgba(197, 160, 89, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: scale(0.97);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.chalet-lightbox-overlay.is-open .chalet-lightbox-dialog {
    transform: scale(1);
}

/* Header */
.clb-header {
    background: rgba(11, 26, 17, 0.95);
    border-bottom: 1px solid rgba(197, 160, 89, 0.25);
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.clb-header-left {
    min-width: 0;
}
.clb-title-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 3px;
}
.clb-badge-pill {
    background: rgba(46, 204, 113, 0.18);
    color: #2ecc71;
    border: 1px solid rgba(46, 204, 113, 0.4);
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    text-transform: uppercase;
}
.clb-badge-elevation {
    font-size: 10px;
    color: var(--accent-gold, #C5A059);
    font-weight: 700;
    background: rgba(197, 160, 89, 0.15);
    border: 1px solid rgba(197, 160, 89, 0.3);
    padding: 2px 7px;
    border-radius: 6px;
}
.clb-title {
    color: #FFFFFF;
    font-size: 1.25rem;
    margin: 0;
    line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

/* Duplex Wing Filter Tabs */
.clb-wing-tabs-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(0, 0, 0, 0.4);
    padding: 4px;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.clb-tab-btn {
    background: transparent;
    border: none;
    color: #A1B5A9;
    font-size: 11.5px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 18px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.clb-tab-btn:hover {
    color: #FFFFFF;
    background: rgba(255, 255, 255, 0.08);
}
.clb-tab-btn.active {
    background: var(--accent-gold, #C5A059);
    color: #07100B;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(197, 160, 89, 0.35);
}
.clb-tab-btn.tab-left-btn.active {
    background: #4ADE80;
    color: #07100B;
    box-shadow: 0 2px 8px rgba(74, 222, 128, 0.4);
}
.clb-tab-btn.tab-right-btn.active {
    background: #60A5FA;
    color: #07100B;
    box-shadow: 0 2px 8px rgba(96, 165, 250, 0.4);
}

/* Action Controls */
.clb-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.clb-btn-book {
    background: linear-gradient(135deg, #10B981, #059669);
    color: #FFFFFF;
    font-size: 12px;
    font-weight: 700;
    padding: 7px 16px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 3px 10px rgba(16, 185, 129, 0.35);
    transition: transform 0.2s ease;
}
.clb-btn-book:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(16, 185, 129, 0.5);
}
.clb-btn-close {
    background: rgba(255, 255, 255, 0.08);
    color: #FFFFFF;
    border: 1px solid rgba(255, 255, 255, 0.2);
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.clb-btn-close:hover {
    background: rgba(239, 68, 68, 0.25);
    color: #EF4444;
    border-color: #EF4444;
    transform: rotate(90deg);
}

/* Stage */
.clb-stage-container {
    flex: 1;
    position: relative;
    background: #030805;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 0;
}
.clb-stage-media-wrap {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.clb-main-img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    transition: opacity 0.2s ease, transform 0.25s ease;
    user-select: none;
}
.clb-main-img.fade {
    opacity: 0.15;
    transform: scale(0.985);
}

/* Floating Navigation Arrows */
.clb-nav-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(7, 20, 13, 0.75);
    color: #FFFFFF;
    border: 1px solid rgba(197, 160, 89, 0.4);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    cursor: pointer;
    backdrop-filter: blur(6px);
    transition: all 0.2s ease;
    z-index: 10;
}
.clb-nav-arrow:hover {
    background: var(--accent-gold, #C5A059);
    color: #07100B;
    border-color: var(--accent-gold, #C5A059);
    transform: translateY(-50%) scale(1.08);
    box-shadow: 0 4px 14px rgba(197, 160, 89, 0.4);
}
.clb-arrow-prev { left: 16px; }
.clb-arrow-next { right: 16px; }

/* Counter & Tag */
.clb-photo-counter {
    position: absolute;
    top: 14px;
    left: 16px;
    background: rgba(7, 16, 11, 0.85);
    color: #FFFFFF;
    border: 1px solid rgba(197, 160, 89, 0.4);
    backdrop-filter: blur(4px);
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    z-index: 8;
}
.clb-photo-counter i {
    color: var(--accent-gold, #C5A059);
}
.clb-floating-wing-tag {
    position: absolute;
    top: 14px;
    right: 16px;
    background: rgba(7, 16, 11, 0.85);
    color: #FFFFFF;
    border: 1px solid rgba(74, 222, 128, 0.4);
    backdrop-filter: blur(4px);
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    z-index: 8;
}

/* Caption Banner */
.clb-caption-banner {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(3, 10, 6, 0.95) 0%, rgba(3, 10, 6, 0.7) 70%, transparent 100%);
    padding: 30px 24px 14px;
    color: #FFFFFF;
    z-index: 8;
    pointer-events: none;
}
.clb-caption-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--accent-gold, #C5A059);
    margin-bottom: 2px;
}
.clb-caption-desc {
    font-size: 12px;
    color: #CBD5E1;
    line-height: 1.4;
    max-width: 820px;
}

/* Bottom Thumbnails Strip */
.clb-thumbs-bar {
    background: rgba(6, 17, 10, 0.96);
    border-top: 1px solid rgba(197, 160, 89, 0.2);
    padding: 10px 16px;
    flex-shrink: 0;
}
.clb-thumbs-scroller {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: thin;
    scrollbar-color: var(--accent-gold, #C5A059) rgba(0,0,0,0.5);
    padding-bottom: 4px;
}
.clb-thumbs-scroller::-webkit-scrollbar {
    height: 5px;
}
.clb-thumbs-scroller::-webkit-scrollbar-thumb {
    background: var(--accent-gold, #C5A059);
    border-radius: 4px;
}
.clb-thumb-item {
    position: relative;
    width: 68px;
    height: 50px;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    flex-shrink: 0;
    border: 2px solid transparent;
    opacity: 0.55;
    transition: all 0.2s ease;
    background: #000;
}
.clb-thumb-item:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}
.clb-thumb-item.is-active {
    opacity: 1;
    border-color: var(--accent-gold, #C5A059);
    box-shadow: 0 0 10px rgba(197, 160, 89, 0.5);
}
.clb-thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.clb-thumb-wing-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    border: 1px solid #000;
}
.clb-thumb-wing-dot.left { background: #4ADE80; }
.clb-thumb-wing-dot.right { background: #60A5FA; }
.clb-thumb-wing-dot.general { background: var(--accent-gold, #C5A059); }

/* Responsive adjustments */
@media (max-width: 768px) {
    .chalet-lightbox-dialog {
        height: 100vh;
        max-height: none;
        border-radius: 0;
        border: none;
    }
    .clb-header {
        padding: 10px 14px;
    }
    .clb-title {
        font-size: 1.1rem;
    }
    .clb-wing-tabs-bar {
        order: 3;
        width: 100%;
        overflow-x: auto;
    }
    .clb-nav-arrow {
        width: 36px;
        height: 36px;
        font-size: 13px;
    }
    .clb-arrow-prev { left: 8px; }
    .clb-arrow-next { right: 8px; }
}
</style>

<!-- =========================================================================
     CHALET PHOTO LIGHTBOX MODAL JAVASCRIPT CONTROLLER
     ========================================================================= -->
<script>
window.chaletGalleriesData = window.chaletGalleriesData || <?php echo json_encode($clb_galleries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

(function() {
    let currentChaletSlug = '';
    let currentWingFilter = 'all';
    let currentFilteredPhotos = [];
    let currentPhotoIdx = 0;

    const modal = document.getElementById('chalet-photo-lightbox-modal');
    const mainImg = document.getElementById('clb-main-image');
    const titleEl = document.getElementById('clb-chalet-title');
    const typeBadge = document.getElementById('clb-type-badge');
    const elevationBadge = document.getElementById('clb-elevation-badge');
    const wingTabsBar = document.getElementById('clb-wing-tabs');
    const bookDirectBtn = document.getElementById('clb-book-direct-btn');
    const counterEl = document.getElementById('clb-counter');
    const wingTagEl = document.getElementById('clb-floating-wing-tag');
    const captionBox = document.getElementById('clb-caption-box');
    const captionTitle = document.getElementById('clb-caption-title');
    const captionDesc = document.getElementById('clb-caption-desc');
    const thumbsScroller = document.getElementById('clb-thumbs-scroller');

    window.openChaletGalleryModal = function(slug, preferredWing) {
        if (!slug || !window.chaletGalleriesData) return;
        const data = window.chaletGalleriesData[slug];
        if (!data) return;

        currentChaletSlug = slug;
        currentWingFilter = preferredWing || 'all';

        // Set Chalet Details
        if (titleEl) titleEl.innerText = data.title;
        if (typeBadge) {
            typeBadge.innerText = data.is_duplex ? '🏰 DUPLEX CHALET' : '🏡 SINGLE COTTAGE';
            typeBadge.style.background = data.is_duplex ? 'rgba(168, 85, 247, 0.2)' : 'rgba(46, 204, 113, 0.18)';
            typeBadge.style.color = data.is_duplex ? '#c084fc' : '#2ecc71';
            typeBadge.style.borderColor = data.is_duplex ? 'rgba(168, 85, 247, 0.4)' : 'rgba(46, 204, 113, 0.4)';
        }
        if (elevationBadge) {
            elevationBadge.innerText = data.elevation ? data.elevation.toUpperCase() : '1,600M ELEVATION';
        }
        if (bookDirectBtn) {
            bookDirectBtn.href = `booking.php?villa=${encodeURIComponent(slug)}`;
        }

        // Configure Wing Tabs if Duplex
        if (data.is_duplex) {
            if (wingTabsBar) {
                wingTabsBar.style.display = 'inline-flex';
                const countAll = (data.photos_all || []).length;
                const countLeft = (data.photos_left || []).length;
                const countRight = (data.photos_right || []).length;
                const countGen = (data.photos_general || []).length;

                const cAll = document.getElementById('clb-tab-count-all');
                const cLeft = document.getElementById('clb-tab-count-left');
                const cRight = document.getElementById('clb-tab-count-right');
                const cGen = document.getElementById('clb-tab-count-gen');
                if (cAll) cAll.innerText = countAll;
                if (cLeft) cLeft.innerText = countLeft;
                if (cRight) cRight.innerText = countRight;
                if (cGen) cGen.innerText = countGen;
            }
        } else {
            if (wingTabsBar) wingTabsBar.style.display = 'none';
        }

        // Apply Tab Active State
        const tabs = modal.querySelectorAll('.clb-tab-btn');
        tabs.forEach(t => {
            if (t.dataset.clbWing === currentWingFilter) t.classList.add('active');
            else t.classList.remove('active');
        });

        rebuildFilteredPhotoList();
        currentPhotoIdx = 0;
        renderActivePhoto();
        renderThumbnails();

        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => { modal.classList.add('is-open'); }, 10);
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeChaletGalleryModal = function() {
        if (!modal) return;
        modal.classList.remove('is-open');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 280);
    };

    window.filterChaletLightboxWing = function(wing, btn) {
        currentWingFilter = wing || 'all';
        const tabs = modal.querySelectorAll('.clb-tab-btn');
        tabs.forEach(t => t.classList.remove('active'));
        if (btn) btn.classList.add('active');

        rebuildFilteredPhotoList();
        currentPhotoIdx = 0;
        renderActivePhoto();
        renderThumbnails();
    };

    function rebuildFilteredPhotoList() {
        const data = window.chaletGalleriesData[currentChaletSlug];
        if (!data) {
            currentFilteredPhotos = [];
            return;
        }

        if (currentWingFilter === 'left') {
            currentFilteredPhotos = data.photos_left && data.photos_left.length > 0 ? data.photos_left : data.photos_all;
        } else if (currentWingFilter === 'right') {
            currentFilteredPhotos = data.photos_right && data.photos_right.length > 0 ? data.photos_right : data.photos_all;
        } else if (currentWingFilter === 'general') {
            currentFilteredPhotos = data.photos_general && data.photos_general.length > 0 ? data.photos_general : data.photos_all;
        } else {
            currentFilteredPhotos = data.photos_all || [];
        }

        if (currentFilteredPhotos.length === 0 && data.photos_all && data.photos_all.length > 0) {
            currentFilteredPhotos = data.photos_all;
        }
    }

    function renderActivePhoto() {
        if (!currentFilteredPhotos || currentFilteredPhotos.length === 0) return;
        if (currentPhotoIdx >= currentFilteredPhotos.length) currentPhotoIdx = 0;
        if (currentPhotoIdx < 0) currentPhotoIdx = currentFilteredPhotos.length - 1;

        const photo = currentFilteredPhotos[currentPhotoIdx];
        if (!photo) return;

        let photoUrl = typeof photo === 'object' ? (photo.url || '') : String(photo);
        if (!photoUrl || photoUrl === '[object Object]') {
            photoUrl = 'assets/images/01 (25).jpeg';
        }

        if (mainImg) {
            mainImg.classList.add('fade');
            setTimeout(() => {
                mainImg.src = photoUrl;
                mainImg.alt = photo.title || 'Chalet Photo';
                mainImg.classList.remove('fade');
            }, 100);
        }

        if (counterEl) {
            counterEl.innerText = `${currentPhotoIdx + 1} / ${currentFilteredPhotos.length}`;
        }

        // Floating Wing Badge
        if (wingTagEl) {
            if (photo.wing === 'left') {
                wingTagEl.innerText = '🌿 Wing A • Left Suite';
                wingTagEl.style.color = '#4ADE80';
                wingTagEl.style.borderColor = 'rgba(74, 222, 128, 0.4)';
                wingTagEl.style.display = 'inline-block';
            } else if (photo.wing === 'right') {
                wingTagEl.innerText = '🌲 Wing B • Right Suite';
                wingTagEl.style.color = '#60A5FA';
                wingTagEl.style.borderColor = 'rgba(96, 165, 250, 0.4)';
                wingTagEl.style.display = 'inline-block';
            } else if (photo.wing === 'general') {
                wingTagEl.innerText = '🏡 Grounds & Exterior';
                wingTagEl.style.color = 'var(--accent-gold, #C5A059)';
                wingTagEl.style.borderColor = 'rgba(197, 160, 89, 0.4)';
                wingTagEl.style.display = 'inline-block';
            } else {
                wingTagEl.style.display = 'none';
            }
        }

        // Caption box
        if (captionBox && captionTitle && captionDesc) {
            const hasTitle = photo.title && photo.title.trim() !== '';
            const hasDesc = photo.desc && photo.desc.trim() !== '';
            if (hasTitle || hasDesc) {
                captionTitle.innerText = photo.title || '';
                captionDesc.innerText = photo.desc || '';
                captionBox.style.display = 'block';
            } else {
                captionBox.style.display = 'none';
            }
        }

        // Update active thumb
        if (thumbsScroller) {
            const thumbs = thumbsScroller.querySelectorAll('.clb-thumb-item');
            thumbs.forEach((th, i) => {
                if (i === currentPhotoIdx) {
                    th.classList.add('is-active');
                    th.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    th.classList.remove('is-active');
                }
            });
        }
    }

    function renderThumbnails() {
        if (!thumbsScroller) return;
        thumbsScroller.innerHTML = '';
        currentFilteredPhotos.forEach((photo, idx) => {
            const th = document.createElement('div');
            th.className = 'clb-thumb-item' + (idx === currentPhotoIdx ? ' is-active' : '');
            th.title = photo.title || `Photo ${idx + 1}`;
            
            let photoUrl = typeof photo === 'object' ? (photo.url || '') : String(photo);
            if (!photoUrl || photoUrl === '[object Object]') photoUrl = 'assets/images/01 (25).jpeg';

            const dotClass = photo.wing === 'left' ? 'left' : (photo.wing === 'right' ? 'right' : 'general');
            th.innerHTML = `
                <img src="${photoUrl}" alt="Thumbnail" onerror="this.src='assets/images/01 (25).jpeg';">
                <span class="clb-thumb-wing-dot ${dotClass}"></span>
            `;
            th.addEventListener('click', () => {
                currentPhotoIdx = idx;
                renderActivePhoto();
            });
            thumbsScroller.appendChild(th);
        });
    }

    window.stepChaletLightbox = function(step) {
        if (!currentFilteredPhotos || currentFilteredPhotos.length <= 1) return;
        currentPhotoIdx = (currentPhotoIdx + step + currentFilteredPhotos.length) % currentFilteredPhotos.length;
        renderActivePhoto();
    };

    // Keyboard Navigation
    document.addEventListener('keydown', function(e) {
        if (!modal || modal.style.display === 'none' || !modal.classList.contains('is-open')) return;
        if (e.key === 'Escape') {
            closeChaletGalleryModal();
        } else if (e.key === 'ArrowLeft') {
            stepChaletLightbox(-1);
        } else if (e.key === 'ArrowRight') {
            stepChaletLightbox(1);
        }
    });

    // Close when clicking dark backdrop outside modal dialog
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeChaletGalleryModal();
            }
        });
    }
})();
</script>
