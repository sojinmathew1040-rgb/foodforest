<!-- Sanctuary Visual Gallery & Chronicle Section (Homepage Preview) -->
<section id="gallery" class="gallery-section section-padding">
    <!-- Anchor alias for legacy dining links -->
    <div id="dining" style="position: relative; top: -80px; visibility: hidden;"></div>

    <div class="container">
        
        <!-- Header with Top-Right Action Button -->
        <div class="gallery-header-bar scroll-reveal">
            <div class="gallery-header-text">
                <span class="section-label"><?php echo htmlspecialchars(get_setting('gallery_badge', 'A Visual Chronicle')); ?></span>
                <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars(get_setting('gallery_title', 'Curated Sanctuary Collections')); ?></h3>
                <p class="font-sans" style="color: var(--text-light); max-width: 650px; margin-top: 10px; line-height: 1.7;">
                    <?php echo htmlspecialchars(get_setting('gallery_desc', 'Explore our living photographic archives. Click on any collection to open the high-resolution showcase with interactive zoom, photo exploration, and full album views.')); ?>
                </p>
            </div>
            <div class="gallery-header-action">
                <a href="gallery.php" class="btn-luxury-solid btn-gallery-all magnetic" data-strength="10">
                    <span>View All Collections</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>

        <!-- 4x2 Uniform Collection Grid (Dynamic from Database) -->
        <div class="gallery-quad-grid" id="gallery-grid">
            <?php
            require_once __DIR__ . '/../admin/includes/db.php';
            $collections = get_gallery_collections(8);
            if (!empty($collections)):
            ?>
            <script>
                window.sanctuaryGalleryCollections = <?php echo json_encode(array_values($collections), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
            </script>
            <?php
                foreach ($collections as $idx => $col):
                    $delay = ($idx % 4) * 0.08;
                    $photo_count = count($col['photos'] ?? []);
            ?>
                <!-- Dynamic Collection Card <?php echo $idx + 1; ?> -->
                <div class="gallery-card is-collection scroll-reveal" 
                     data-index="<?php echo $idx; ?>" 
                     style="transition-delay: <?php echo $delay; ?>s;"
                     data-collection="<?php echo htmlspecialchars(json_encode($col), ENT_QUOTES, 'UTF-8'); ?>"
                     data-title="<?php echo htmlspecialchars($col['title']); ?>"
                     data-caption="<?php echo htmlspecialchars($col['description']); ?>"
                     data-tag="<?php echo htmlspecialchars($col['tag'] ?: $col['category']); ?>"
                     onclick="if(window.openGalleryCollection){window.openGalleryCollection(<?php echo $idx; ?>);}">
                    
                    <div class="gallery-card-inner">
                        <!-- Stacked Album Effect Layer -->
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
            <?php 
                endforeach;
            endif; 
            ?>
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
                <!-- Navigation Prev -->
                <button type="button" class="lb-nav-btn lb-prev-btn" id="lb-prev-btn" aria-label="Previous photo" title="Previous (Left Arrow)">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <!-- Interactive Viewport Canvas -->
                <div class="lightbox-viewport" id="lb-viewport">
                    <div class="lightbox-canvas" id="lb-canvas">
                        <img src="" alt="" class="lightbox-active-img" id="lb-active-img" draggable="false">
                    </div>
                    <!-- Zoom Hint Pill (fades out) -->
                    <div class="lightbox-zoom-hint font-sans" id="lb-zoom-hint">
                        <i class="fa-solid fa-hand-pointer"></i> Double-click or scroll wheel to zoom · Drag to pan
                    </div>
                </div>

                <!-- Navigation Next -->
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

                <!-- Scrollable Thumbnails Strip for Current Collection -->
                <div class="lightbox-thumbnails-wrapper">
                    <div class="lightbox-thumbnails-strip" id="lb-thumbnails-strip">
                        <!-- Thumbnails populated dynamically by JavaScript -->
                    </div>
                </div>
            </div>

        </div>
    </div>

</section>
