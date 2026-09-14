<!-- Sanctuary Visual Gallery & Chronicle Section (Homepage Preview) -->
<section id="gallery" class="gallery-section section-padding">
    <!-- Anchor alias for legacy dining links -->
    <div id="dining" style="position: relative; top: -80px; visibility: hidden;"></div>

    <div class="container">
        
        <!-- Header with Top-Right Action Button -->
        <div class="gallery-header-bar scroll-reveal">
            <div class="gallery-header-text">
                <span class="section-label"><?php echo htmlspecialchars(get_setting('gallery_badge', 'A Visual Chronicle')); ?></span>
                <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars(get_setting('gallery_title', 'Glimpses of the Sanctuary')); ?></h3>
                <p class="font-sans" style="color: var(--text-light); max-width: 650px; margin-top: 10px; line-height: 1.7;">
                    <?php echo htmlspecialchars(get_setting('gallery_desc', 'A living photographic archive of slow living, morning fog across the high ranges, and handcrafted earthen architecture.')); ?>
                </p>
            </div>
            <div class="gallery-header-action">
                <a href="gallery.php" class="btn-luxury-solid btn-gallery-all magnetic" data-strength="10">
                    <span>View Full Gallery</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>

        <!-- 4x2 Uniform Photo Grid (Dynamic from Database) -->
        <div class="gallery-quad-grid" id="gallery-grid">
            <?php
            require_once __DIR__ . '/../admin/includes/db.php';
            $gallery_photos = get_gallery_items(8);
            if (!empty($gallery_photos)):
                foreach ($gallery_photos as $idx => $photo):
                    $delay = ($idx % 4) * 0.08;
            ?>
                <!-- Dynamic Photo Card <?php echo $idx + 1; ?> -->
                <div class="gallery-card scroll-reveal" data-index="<?php echo $idx; ?>" style="transition-delay: <?php echo $delay; ?>s;"
                     data-title="<?php echo htmlspecialchars($photo['title']); ?>"
                     data-caption="<?php echo htmlspecialchars($photo['caption']); ?>"
                     data-tag="<?php echo htmlspecialchars($photo['tag'] ?: $photo['category']); ?>">
                    <div class="gallery-card-inner">
                        <img src="<?php echo htmlspecialchars($photo['image_url']); ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" class="gallery-img" loading="lazy" onerror="this.src='assets/images/treehouse_exterior.png'">
                        <div class="gallery-overlay">
                            <div class="gallery-overlay-top">
                                <span class="gallery-tag font-sans"><?php echo htmlspecialchars($photo['tag'] ?: $photo['category']); ?></span>
                                <span class="gallery-zoom-btn" aria-label="Expand image">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                </span>
                            </div>
                            <div class="gallery-overlay-bottom">
                                <h5 class="gallery-title font-serif"><?php echo htmlspecialchars($photo['title']); ?></h5>
                                <span class="gallery-meta font-sans"><?php echo htmlspecialchars($photo['category']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach;
            endif; 
            ?>
        </div>

    </div>

    <!-- Luxury Lightbox Modal -->
    <div class="luxury-lightbox" id="luxury-lightbox" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="lightbox-backdrop"></div>
        <div class="lightbox-content-wrapper">
            
            <!-- Top Controls -->
            <div class="lightbox-top-bar">
                <div class="lightbox-counter font-serif">
                    <span id="lb-curr-index">01</span> / <span id="lb-total-count">08</span>
                </div>
                <div class="lightbox-tag font-sans" id="lb-tag">CANOPY DWELLING</div>
                <button type="button" class="lightbox-close-btn" id="lb-close-btn" aria-label="Close Lightbox">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Main Stage with Image & Nav Arrows -->
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

            <!-- Bottom Caption Panel -->
            <div class="lightbox-bottom-bar">
                <div class="lightbox-caption-box">
                    <h4 class="lightbox-title font-serif" id="lb-title"></h4>
                    <p class="lightbox-desc font-sans" id="lb-caption"></p>
                </div>
            </div>

        </div>
    </div>

</section>
