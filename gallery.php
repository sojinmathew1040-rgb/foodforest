<?php
// Load Site Header
require_once 'includes/header.php';
require_once 'admin/includes/db.php';

$collections = get_gallery_collections();
$total_photos = 0;
foreach ($collections as $c) {
    $total_photos += count($c['photos'] ?? []);
}
?>

<!-- Dedicated Gallery Hero Header -->
<div class="page-hero-header">
    <div class="container">
        <div class="page-hero-content text-center scroll-reveal">
            <span class="section-label">A Visual Chronicle</span>
            <h1 class="page-hero-title font-serif split-text" style="color: var(--accent-green);">The Sanctuary Collections</h1>
            <p class="font-sans page-hero-desc" style="color: var(--text-light); max-width: 700px; margin: 15px auto 0;">
                A comprehensive photographic archive capturing misty high-altitude dawns, handcrafted cob mudhouses, organic heirloom harvests, and the timeless rhythms of Kanthalloor. Click any collection to browse individual high-resolution photos with interactive zoom.
            </p>
            <div class="gallery-hero-meta font-sans">
                <span><i class="fa-solid fa-location-dot"></i> Kanthalloor, Kerala</span>
                <span><i class="fa-solid fa-mountain"></i> 1,600M Elevation</span>
                <span><i class="fa-solid fa-layer-group"></i> <?php echo count($collections); ?> Curated Collections (<?php echo $total_photos; ?> Photos)</span>
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
                    <span>All Collections</span>
                    <span class="filter-count"><?php echo count($collections); ?></span>
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
            <?php if (!empty($collections)): ?>
                <script>
                    window.sanctuaryGalleryCollections = <?php echo json_encode(array_values($collections), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
                </script>
                <?php foreach ($collections as $idx => $col): 
                    $cat_slug = $col['category_slug'] ?? 'landscape';
                    $delay = ($idx % 4) * 0.08;
                    $photo_count = count($col['photos'] ?? []);
                ?>
                    <div class="gallery-card is-collection scroll-reveal" 
                         data-category="<?php echo htmlspecialchars($cat_slug); ?>" 
                         data-index="<?php echo $idx; ?>" 
                         style="transition-delay: <?php echo $delay; ?>s;"
                         data-collection="<?php echo htmlspecialchars(json_encode($col), ENT_QUOTES, 'UTF-8'); ?>"
                         data-title="<?php echo htmlspecialchars($col['title']); ?>"
                         data-caption="<?php echo htmlspecialchars($col['description']); ?>"
                         data-tag="<?php echo htmlspecialchars($col['tag'] ?: $col['category']); ?>"
                         onclick="if(window.openGalleryCollection){window.openGalleryCollection(<?php echo $idx; ?>);}">
                        
                        <div class="gallery-card-inner">
                            <div class="gallery-card-stack-layer"></div>
                            <img src="<?php echo htmlspecialchars($col['cover_image']); ?>" alt="<?php echo htmlspecialchars($col['title']); ?>" class="gallery-img" loading="lazy" onerror="this.src='assets/images/treehouse_exterior.png'">
                            
                            <div class="gallery-overlay">
                                <div class="gallery-overlay-top">
                                    <span class="gallery-tag font-sans"><?php echo htmlspecialchars($col['tag'] ?: $col['category']); ?></span>
                                    <span class="gallery-album-badge font-sans">
                                        <i class="fa-solid fa-layer-group"></i> <?php echo $photo_count; ?> Photos
                                    </span>
                                </div>
                                <div class="gallery-overlay-bottom">
                                    <h5 class="gallery-title font-serif"><?php echo htmlspecialchars($col['title']); ?></h5>
                                    <div class="gallery-card-bottom-row">
                                        <span class="gallery-meta font-sans"><?php echo htmlspecialchars($col['category']); ?></span>
                                        <span class="gallery-view-collection font-sans"><i class="fa-solid fa-expand"></i> Open Album &rarr;</span>
                                    </div>
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

    <!-- Luxury Multi-Photo Collection Lightbox & Zoom Modal -->
    <div class="luxury-lightbox" id="luxury-lightbox" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="lightbox-backdrop" id="lb-backdrop"></div>
        <div class="lightbox-content-wrapper">
            
            <!-- Top Controls Bar -->
            <div class="lightbox-top-bar">
                <div class="lightbox-top-info">
                    <span class="lightbox-tag font-sans" id="lb-tag">CANOPY DWELLING</span>
                    <h3 class="lightbox-collection-name font-serif" id="lb-collection-title">Canopy Treehouse Collection</h3>
                </div>

                <div class="lightbox-top-actions">
                    <!-- Photo Counter -->
                    <div class="lightbox-counter font-serif">
                        <span id="lb-curr-index">01</span> / <span id="lb-total-count">06</span>
                    </div>

                    <!-- Zoom Controls -->
                    <div class="lightbox-zoom-toolbar font-sans">
                        <button type="button" class="lb-tool-btn" id="lb-zoom-out" title="Zoom Out (-)">
                            <i class="fa-solid fa-magnifying-glass-minus"></i>
                        </button>
                        <button type="button" class="lb-tool-btn lb-zoom-level-btn" id="lb-zoom-reset" title="Reset Zoom (100%)">
                            <span id="lb-zoom-level-text">100%</span>
                        </button>
                        <button type="button" class="lb-tool-btn" id="lb-zoom-in" title="Zoom In (+)">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" class="lb-tool-btn" id="lb-fullscreen-btn" title="Toggle Fullscreen">
                            <i class="fa-solid fa-expand" id="lb-fs-icon"></i>
                        </button>
                    </div>

                    <!-- Close Button -->
                    <button type="button" class="lightbox-close-btn" id="lb-close-btn" aria-label="Close Lightbox" title="Close (Esc)">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Main Interactive Zoom & Pan Stage -->
            <div class="lightbox-stage" id="lb-stage">
                <button type="button" class="lb-nav-btn lb-prev-btn" id="lb-prev-btn" aria-label="Previous photo" title="Previous (Left Arrow)">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <div class="lightbox-viewport" id="lb-viewport">
                    <div class="lightbox-canvas" id="lb-canvas">
                        <img src="" alt="" class="lightbox-active-img" id="lb-active-img" draggable="false">
                    </div>
                    <div class="lightbox-zoom-hint font-sans" id="lb-zoom-hint">
                        <i class="fa-solid fa-hand-pointer"></i> Double-click or scroll wheel to zoom · Drag to pan
                    </div>
                </div>

                <button type="button" class="lb-nav-btn lb-next-btn" id="lb-next-btn" aria-label="Next photo" title="Next (Right Arrow)">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <!-- Bottom Caption & Interactive Thumbnail Bar -->
            <div class="lightbox-bottom-bar">
                <div class="lightbox-caption-box">
                    <h4 class="lightbox-title font-serif" id="lb-title">Photo Title</h4>
                    <p class="lightbox-desc font-sans" id="lb-caption">Photo Description</p>
                </div>

                <div class="lightbox-thumbnails-wrapper">
                    <div class="lightbox-thumbnails-strip" id="lb-thumbnails-strip">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>

        </div>
    </div>

</section>

<?php
// Load Site Footer
require_once 'includes/footer.php';
?>
