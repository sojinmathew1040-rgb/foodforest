<?php
// Load Site Header
require_once 'includes/header.php';
require_once 'admin/includes/db.php';

$gallery_photos = get_gallery_items();
?>

<!-- Dedicated Gallery Hero Header -->
<div class="page-hero-header">
    <div class="container">
        <div class="page-hero-content text-center scroll-reveal">
            <span class="section-label">A Visual Chronicle</span>
            <h1 class="page-hero-title font-serif split-text" style="color: var(--accent-green);">The Sanctuary Archive</h1>
            <p class="font-sans page-hero-desc" style="color: var(--text-light); max-width: 700px; margin: 15px auto 0;">
                A comprehensive photographic collection capturing misty high-altitude dawns, handcrafted cob mudhouses, organic heirloom harvests, and the timeless rhythms of Kanthalloor.
            </p>
            <div class="gallery-hero-meta font-sans">
                <span><i class="fa-solid fa-location-dot"></i> Kanthalloor, Kerala</span>
                <span><i class="fa-solid fa-mountain"></i> 1,600M Elevation</span>
                <span><i class="fa-solid fa-camera"></i> Full Archive (<?php echo count($gallery_photos); ?> Photos)</span>
            </div>
        </div>
    </div>
</div>

<!-- Standalone Gallery Main Section -->
<section class="gallery-page-section section-padding-bottom">
    <div class="container">
        
        <!-- Category Filter Tabs -->
        <div class="gallery-filter-wrapper scroll-reveal">
            <div class="gallery-filter-tabs" role="tablist">
                <button type="button" class="gallery-filter-btn active" data-filter="all">
                    <span>All Moments</span>
                    <span class="filter-count"><?php echo count($gallery_photos); ?></span>
                </button>
                <button type="button" class="gallery-filter-btn" data-filter="dwellings">
                    <span>Dwellings & Stays</span>
                </button>
                <button type="button" class="gallery-filter-btn" data-filter="landscape">
                    <span>Misty Ridges & Waters</span>
                </button>
                <button type="button" class="gallery-filter-btn" data-filter="orchards">
                    <span>Orchards & Terroir</span>
                </button>
                <button type="button" class="gallery-filter-btn" data-filter="gastronomy">
                    <span>Earthen Gastronomy</span>
                </button>
            </div>
        </div>

        <!-- Full Archive Grid (Dynamic from Database) -->
        <div class="gallery-quad-grid gallery-page-grid" id="gallery-grid">
            <?php if (!empty($gallery_photos)): ?>
                <?php foreach ($gallery_photos as $idx => $photo): 
                    $cat_slug = 'landscape';
                    $cat_lower = strtolower($photo['category']);
                    if (strpos($cat_lower, 'villa') !== false || strpos($cat_lower, 'dwelling') !== false || strpos($cat_lower, 'handcrafted') !== false) {
                        $cat_slug = 'dwellings';
                    } elseif (strpos($cat_lower, 'orchard') !== false || strpos($cat_lower, 'harvest') !== false) {
                        $cat_slug = 'orchards';
                    } elseif (strpos($cat_lower, 'gastro') !== false || strpos($cat_lower, 'food') !== false) {
                        $cat_slug = 'gastronomy';
                    }
                    $delay = ($idx % 4) * 0.08;
                ?>
                    <div class="gallery-card scroll-reveal" data-category="<?php echo $cat_slug; ?>" data-index="<?php echo $idx; ?>" style="transition-delay: <?php echo $delay; ?>s;"
                         data-title="<?php echo htmlspecialchars($photo['title']); ?>"
                         data-caption="<?php echo htmlspecialchars($photo['caption']); ?>"
                         data-tag="<?php echo htmlspecialchars($photo['tag'] ?: $photo['category']); ?>">
                        <div class="gallery-card-inner">
                            <img src="<?php echo htmlspecialchars($photo['image_url']); ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" class="gallery-img" loading="lazy" onerror="this.src='assets/images/treehouse_exterior.png'">
                            <div class="gallery-overlay">
                                <div class="gallery-overlay-top">
                                    <span class="gallery-tag font-sans"><?php echo htmlspecialchars($photo['tag'] ?: $photo['category']); ?></span>
                                    <span class="gallery-zoom-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></span>
                                </div>
                                <div class="gallery-overlay-bottom">
                                    <h5 class="gallery-title font-serif"><?php echo htmlspecialchars($photo['title']); ?></h5>
                                    <span class="gallery-meta font-sans"><?php echo htmlspecialchars($photo['category']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Back to Sanctuary CTA -->
        <div class="text-center" style="margin-top: 60px;">
            <a href="index.php" class="btn-luxury-outline magnetic" data-strength="15">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transform: rotate(180deg);"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                <span>Return to Sanctuary Experience</span>
            </a>
        </div>

    </div>

    <!-- Luxury Lightbox Modal -->
    <div class="luxury-lightbox" id="luxury-lightbox" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="lightbox-backdrop"></div>
        <div class="lightbox-content-wrapper">
            <div class="lightbox-top-bar">
                <div class="lightbox-counter font-serif">
                    <span id="lb-curr-index">01</span> / <span id="lb-total-count"><?php echo count($gallery_photos); ?></span>
                </div>
                <div class="lightbox-tag font-sans" id="lb-tag">SANCTUARY</div>
                <button type="button" class="lightbox-close-btn" id="lb-close-btn" aria-label="Close Lightbox">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="lightbox-stage">
                <button type="button" class="lb-nav-btn lb-prev-btn" id="lb-prev-btn" aria-label="Previous image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <div class="lightbox-img-holder">
                    <img src="" alt="" class="lightbox-active-img" id="lb-active-img">
                </div>
                <button type="button" class="lb-nav-btn lb-next-btn" id="lb-next-btn" aria-label="Next image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
            <div class="lightbox-bottom-bar">
                <div class="lightbox-caption-box">
                    <h4 class="lightbox-title font-serif" id="lb-title"></h4>
                    <p class="lightbox-desc font-sans" id="lb-caption"></p>
                </div>
            </div>
        </div>
    </div>

</section>

<?php
// Load Site Footer
require_once 'includes/footer.php';
?>
