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
    <div class="luxury-lightbox" id="luxury-lightbox" role="dialog" aria-modal="true" aria-hidden="true" style="display: none;">
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
                    <!-- Zoom Hint Pill -->
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
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Self-Contained Standalone Lightbox & Filter Controller -->
    <script>
    (function() {
        var currentCollection = null;
        var currentPhotos = [];
        var currentPhotoIdx = 0;
        var scale = 1.0;
        var translateX = 0;
        var translateY = 0;
        var isDragging = false;
        var dragStartX = 0, dragStartY = 0, initialTranslateX = 0, initialTranslateY = 0;
        var hintTimeout = null;

        function getLightboxElements() {
            var lb = document.getElementById('luxury-lightbox');
            if (lb && lb.parentElement !== document.body) {
                document.body.appendChild(lb);
            }
            return {
                lb: lb,
                backdrop: document.getElementById('lb-backdrop'),
                closeBtn: document.getElementById('lb-close-btn'),
                tag: document.getElementById('lb-tag'),
                colTitle: document.getElementById('lb-collection-title'),
                currIdx: document.getElementById('lb-curr-index'),
                totalCount: document.getElementById('lb-total-count'),
                title: document.getElementById('lb-title'),
                caption: document.getElementById('lb-caption'),
                activeImg: document.getElementById('lb-active-img'),
                canvas: document.getElementById('lb-canvas'),
                viewport: document.getElementById('lb-viewport'),
                prevBtn: document.getElementById('lb-prev-btn'),
                nextBtn: document.getElementById('lb-next-btn'),
                thumbStrip: document.getElementById('lb-thumbnails-strip'),
                zoomIn: document.getElementById('lb-zoom-in'),
                zoomOut: document.getElementById('lb-zoom-out'),
                zoomReset: document.getElementById('lb-zoom-reset'),
                zoomLevelText: document.getElementById('lb-zoom-level-text'),
                fsBtn: document.getElementById('lb-fullscreen-btn'),
                fsIcon: document.getElementById('lb-fs-icon'),
                hint: document.getElementById('lb-zoom-hint')
            };
        }

        function updateTransform(smooth) {
            var el = getLightboxElements();
            if (!el.canvas) return;
            el.canvas.style.transition = smooth ? 'transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1)' : 'none';
            el.canvas.style.transform = 'translate3d(' + translateX + 'px, ' + translateY + 'px, 0) scale(' + scale + ')';
            if (el.zoomLevelText) el.zoomLevelText.innerText = Math.round(scale * 100) + '%';
            if (el.viewport) {
                if (scale > 1.05) {
                    el.viewport.classList.add('is-zoomed');
                    el.viewport.style.cursor = isDragging ? 'grabbing' : 'grab';
                } else {
                    el.viewport.classList.remove('is-zoomed');
                    el.viewport.style.cursor = 'default';
                }
            }
        }

        function resetZoom() {
            scale = 1.0;
            translateX = 0;
            translateY = 0;
            updateTransform(true);
        }

        function zoomIn(step) {
            step = step || 0.4;
            scale = Math.min(3.5, scale + step);
            updateTransform(true);
        }

        function zoomOut(step) {
            step = step || 0.4;
            scale = Math.max(1.0, scale - step);
            if (scale <= 1.05) {
                scale = 1.0;
                translateX = 0;
                translateY = 0;
            }
            updateTransform(true);
        }

        function renderPhoto(idx, smooth) {
            if (!currentPhotos || currentPhotos.length === 0) return;
            if (idx < 0) idx = currentPhotos.length - 1;
            if (idx >= currentPhotos.length) idx = 0;
            currentPhotoIdx = idx;
            var photo = currentPhotos[currentPhotoIdx];
            var el = getLightboxElements();

            resetZoom();

            if (el.activeImg) {
                if (smooth) {
                    el.activeImg.classList.add('fade-out');
                    setTimeout(function() {
                        el.activeImg.src = photo.src;
                        el.activeImg.alt = photo.title || 'Sanctuary Photo';
                        el.activeImg.classList.remove('fade-out');
                    }, 80);
                } else {
                    el.activeImg.src = photo.src;
                    el.activeImg.alt = photo.title || 'Sanctuary Photo';
                    el.activeImg.classList.remove('fade-out');
                }
            }

            if (el.currIdx) el.currIdx.innerText = String(currentPhotoIdx + 1).padStart(2, '0');
            if (el.totalCount) el.totalCount.innerText = String(currentPhotos.length).padStart(2, '0');
            if (el.title) el.title.innerText = photo.title || currentCollection.title;
            if (el.caption) el.caption.innerText = photo.caption || currentCollection.description || '';

            if (el.thumbStrip) {
                var thumbs = el.thumbStrip.querySelectorAll('.lb-thumb');
                thumbs.forEach(function(t, tIdx) {
                    if (tIdx === currentPhotoIdx) {
                        t.classList.add('active');
                        t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    } else {
                        t.classList.remove('active');
                    }
                });
            }
        }

        function openCollection(colData, startIdx) {
            if (!colData) return;
            currentCollection = colData;
            currentPhotos = (colData.photos && colData.photos.length > 0) ? colData.photos : [{
                src: colData.cover_image || colData.image_url || 'assets/images/treehouse_exterior.png',
                title: colData.title || 'Sanctuary Photo',
                caption: colData.description || colData.caption || ''
            }];

            var el = getLightboxElements();
            if (!el.lb) return;

            if (el.tag) el.tag.innerText = currentCollection.tag || currentCollection.category || 'SANCTUARY';
            if (el.colTitle) el.colTitle.innerText = currentCollection.title || 'Sanctuary Showcase';

            if (el.thumbStrip) {
                el.thumbStrip.innerHTML = '';
                currentPhotos.forEach(function(p, pIdx) {
                    var thumb = document.createElement('div');
                    thumb.className = 'lb-thumb' + (pIdx === (startIdx || 0) ? ' active' : '');
                    thumb.setAttribute('data-photo-idx', pIdx);
                    thumb.setAttribute('title', p.title || ('Photo ' + (pIdx + 1)));
                    thumb.innerHTML = '<img src="' + p.src + '" alt="' + (p.title || 'Photo') + '" loading="lazy">' +
                                      '<span class="lb-thumb-num font-sans">' + (pIdx + 1) + '</span>';
                    thumb.addEventListener('click', function(e) {
                        e.stopPropagation();
                        renderPhoto(pIdx, true);
                    });
                    el.thumbStrip.appendChild(thumb);
                });
            }

            renderPhoto(startIdx || 0, false);

            el.lb.style.display = 'flex';
            setTimeout(function() {
                el.lb.classList.add('active');
                el.lb.setAttribute('aria-hidden', 'false');
            }, 10);
            document.body.style.overflow = 'hidden';

            if (el.hint) {
                el.hint.style.opacity = '1';
                clearTimeout(hintTimeout);
                hintTimeout = setTimeout(function() {
                    if (el.hint) el.hint.style.opacity = '0';
                }, 3500);
            }
        }

        function closeLightbox() {
            var el = getLightboxElements();
            if (!el.lb) return;
            el.lb.classList.remove('active');
            el.lb.setAttribute('aria-hidden', 'true');
            setTimeout(function() {
                if (!el.lb.classList.contains('active')) {
                    el.lb.style.display = 'none';
                }
            }, 300);
            document.body.style.overflow = '';
            resetZoom();
            if (document.fullscreenElement) {
                document.exitFullscreen ? document.exitFullscreen().catch(function(){}) : null;
            }
        }

        // Global functions
        window.openGalleryCollection = function(idx) {
            if (typeof idx === 'object' && idx !== null) {
                openCollection(idx, 0);
                return;
            }
            if (window.sanctuaryGalleryCollections && window.sanctuaryGalleryCollections[idx]) {
                openCollection(window.sanctuaryGalleryCollections[idx], 0);
                return;
            }
            var allCards = document.querySelectorAll('.gallery-card');
            if (allCards[idx]) {
                var colAttr = allCards[idx].getAttribute('data-collection');
                if (colAttr) {
                    try {
                        openCollection(JSON.parse(colAttr), 0);
                        return;
                    } catch (e) {}
                }
            }
        };
        window.closeGalleryLightbox = closeLightbox;

        // Wire event listeners
        function attachListeners() {
            var el = getLightboxElements();
            if (el.closeBtn) el.closeBtn.onclick = closeLightbox;
            if (el.backdrop) el.backdrop.onclick = closeLightbox;

            if (el.nextBtn) el.nextBtn.onclick = function(e) {
                e.stopPropagation();
                renderPhoto(currentPhotoIdx + 1, true);
            };
            if (el.prevBtn) el.prevBtn.onclick = function(e) {
                e.stopPropagation();
                renderPhoto(currentPhotoIdx - 1, true);
            };

            if (el.zoomIn) el.zoomIn.onclick = function(e) { e.stopPropagation(); zoomIn(0.4); };
            if (el.zoomOut) el.zoomOut.onclick = function(e) { e.stopPropagation(); zoomOut(0.4); };
            if (el.zoomReset) el.zoomReset.onclick = function(e) { e.stopPropagation(); resetZoom(); };

            if (el.fsBtn) {
                el.fsBtn.onclick = function(e) {
                    e.stopPropagation();
                    if (!document.fullscreenElement) {
                        el.lb.requestFullscreen ? el.lb.requestFullscreen().catch(function(){}) : null;
                        if (el.fsIcon) el.fsIcon.className = 'fa-solid fa-compress';
                    } else {
                        document.exitFullscreen ? document.exitFullscreen().catch(function(){}) : null;
                        if (el.fsIcon) el.fsIcon.className = 'fa-solid fa-expand';
                    }
                };
            }

            if (el.viewport) {
                el.viewport.ondblclick = function(e) {
                    e.preventDefault();
                    if (scale > 1.2) resetZoom();
                    else { scale = 2.2; updateTransform(true); }
                };

                el.viewport.onwheel = function(e) {
                    e.preventDefault();
                    if (e.deltaY < 0) zoomIn(0.25);
                    else zoomOut(0.25);
                };

                el.viewport.onmousedown = function(e) {
                    if (scale <= 1.05) return;
                    isDragging = true;
                    dragStartX = e.clientX;
                    dragStartY = e.clientY;
                    initialTranslateX = translateX;
                    initialTranslateY = translateY;
                };

                window.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;
                    var dx = e.clientX - dragStartX;
                    var dy = e.clientY - dragStartY;
                    translateX = initialTranslateX + dx;
                    translateY = initialTranslateY + dy;
                    updateTransform(false);
                });

                window.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        updateTransform(true);
                    }
                });
            }

            window.addEventListener('keydown', function(e) {
                var el = getLightboxElements();
                if (!el.lb || !el.lb.classList.contains('active')) return;
                if (e.key === 'Escape') closeLightbox();
                else if (e.key === 'ArrowRight') renderPhoto(currentPhotoIdx + 1, true);
                else if (e.key === 'ArrowLeft') renderPhoto(currentPhotoIdx - 1, true);
                else if (e.key === '+' || e.key === '=') zoomIn(0.4);
                else if (e.key === '-' || e.key === '_') zoomOut(0.4);
                else if (e.key === '0') resetZoom();
            });

            // Global delegation for gallery card clicks
            document.addEventListener('click', function(e) {
                var card = e.target.closest('.gallery-card');
                if (card) {
                    e.preventDefault();
                    var idxStr = card.getAttribute('data-index');
                    var idx = idxStr !== null ? parseInt(idxStr, 10) : -1;
                    if (idx >= 0 && window.sanctuaryGalleryCollections && window.sanctuaryGalleryCollections[idx]) {
                        openCollection(window.sanctuaryGalleryCollections[idx], 0);
                        return;
                    }
                    var colAttr = card.getAttribute('data-collection');
                    if (colAttr) {
                        try {
                            openCollection(JSON.parse(colAttr), 0);
                            return;
                        } catch (err) {}
                    }
                }
            });

            // Category Filter Tabs
            var filterBtns = document.querySelectorAll('.gallery-filter-btn');
            var galleryGrid = document.getElementById('gallery-grid');
            if (filterBtns.length > 0 && galleryGrid) {
                filterBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        filterBtns.forEach(function(b) { b.classList.remove('active'); });
                        btn.classList.add('active');

                        var filter = (btn.getAttribute('data-filter') || 'all').toLowerCase();
                        var allCards = galleryGrid.querySelectorAll('.gallery-card');

                        allCards.forEach(function(card) {
                            var cardCat = (card.getAttribute('data-category') || '').toLowerCase();
                            var match = (filter === 'all' || cardCat === filter || cardCat.indexOf(filter) !== -1 || filter.indexOf(cardCat) !== -1);
                            if (match) {
                                card.style.display = 'block';
                                setTimeout(function() {
                                    card.style.opacity = '1';
                                    card.style.transform = 'translateY(0)';
                                }, 30);
                            } else {
                                card.style.opacity = '0';
                                card.style.transform = 'translateY(15px)';
                                setTimeout(function() {
                                    card.style.display = 'none';
                                }, 220);
                            }
                        });
                    });
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', attachListeners);
        } else {
            attachListeners();
        }
    })();
    </script>

</section>

<?php
// Load Site Footer
require_once 'includes/footer.php';
?>
