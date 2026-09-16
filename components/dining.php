<?php
require_once __DIR__ . '/../admin/includes/db.php';

// Fetch section settings
$menu_badge = get_setting('menu_badge', 'ESTATE GASTRONOMY & ORGANIC DINING');
$menu_title = get_setting('menu_title', 'The Forest Hearth & Living Menu');
$menu_desc = get_setting('menu_desc', 'Food at Food Forest is a ritual. Cooked in indigenous clay pots over aromatic wood hearths, every meal is prepared with ingredients harvested minutes prior from our own organic soil.');
$currency = get_setting('currency_symbol', '₹');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');

// Category metadata definitions
$categories_meta = [
    'breakfast' => [
        'title' => 'Morning in the Orchards',
        'short_title' => 'Breakfast',
        'icon' => 'fa-solid fa-mug-saucer',
        'time' => get_setting('menu_time_breakfast', '07:30 AM — 10:00 AM'),
        'desc' => get_setting('menu_desc_breakfast', 'Morning in the Orchards • Fresh farm juices, lacy hoppers & stone-ground breakfast sets')
    ],
    'lunch' => [
        'title' => 'Claypot Hearth Feast',
        'short_title' => 'Lunch',
        'icon' => 'fa-solid fa-bowl-rice',
        'time' => get_setting('menu_time_lunch', '12:30 PM — 02:30 PM'),
        'desc' => get_setting('menu_desc_lunch', 'Claypot Hearth Feast • Heirloom red rice, seasonal thorans & traditional banana-leaf sadya')
    ],
    'snacks' => [
        'title' => 'Plantation Tea Ritual',
        'short_title' => 'Evening Snacks',
        'icon' => 'fa-solid fa-cookie-bite',
        'time' => get_setting('menu_time_snacks', '04:30 PM — 06:30 PM'),
        'desc' => get_setting('menu_desc_snacks', 'Plantation Tea Ritual • Steaming Marayoor cardamom chai, hot banana fritters & steamed ela ada')
    ],
    'dinner' => [
        'title' => 'Twilight Campfire Dining',
        'short_title' => 'Dinner',
        'icon' => 'fa-solid fa-fire-burner',
        'time' => get_setting('menu_time_dinner', '07:30 PM — 10:00 PM'),
        'desc' => get_setting('menu_desc_dinner', 'Twilight Campfire Dining • Slow-simmered stews, charcoal grills & jaggery desserts by the embers')
    ]
];

// Fetch active food items
$all_menu_items = get_food_menu_items(null, true);

// Group items by category
$items_by_category = [
    'breakfast' => [],
    'lunch' => [],
    'snacks' => [],
    'dinner' => []
];

foreach ($all_menu_items as $item) {
    $cat = strtolower(trim($item['category']));
    if (isset($items_by_category[$cat])) {
        $items_by_category[$cat][] = $item;
    } else {
        $items_by_category['breakfast'][] = $item;
    }
}
?>

<!-- The Food Forest Hearth & Living Menu (Gastronomy) Section -->
<section id="dining" class="dining-section section-padding">
    <div class="container">
        
        <!-- Editorial Section Header -->
        <div class="dining-section-header text-center">
            <span class="section-label"><?php echo htmlspecialchars($menu_badge); ?></span>
            <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars($menu_title); ?></h3>
            <p class="dining-section-desc font-sans" style="color: var(--text-light); max-width: 760px; margin: 16px auto 0; line-height: 1.8;">
                <?php echo htmlspecialchars($menu_desc); ?>
            </p>
        </div>

        <!-- Dining Category Navigation Tabs -->
        <div class="dining-tabs-nav-wrapper">
            <div class="dining-tabs-nav font-sans" role="tablist">
                <?php 
                $first_cat = true;
                foreach ($categories_meta as $cat_key => $meta): 
                    $item_count = count($items_by_category[$cat_key]);
                ?>
                    <button type="button" 
                            class="dining-tab-btn <?php echo $first_cat ? 'active' : ''; ?>" 
                            data-category="<?php echo $cat_key; ?>"
                            role="tab"
                            aria-selected="<?php echo $first_cat ? 'true' : 'false'; ?>">
                        <span class="dining-tab-icon"><i class="<?php echo htmlspecialchars($meta['icon']); ?>"></i></span>
                        <span class="dining-tab-label-group">
                            <span class="dining-tab-name font-serif"><?php echo htmlspecialchars($meta['short_title']); ?></span>
                            <span class="dining-tab-timing font-sans"><?php echo htmlspecialchars($meta['time']); ?></span>
                        </span>
                        <span class="dining-tab-count-pill"><?php echo $item_count; ?></span>
                    </button>
                <?php 
                    $first_cat = false;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- Dining Categories Content Panes -->
        <div class="dining-panes-container">
            <?php 
            $first_pane = true;
            foreach ($categories_meta as $cat_key => $meta): 
                $cat_items = $items_by_category[$cat_key];
                
                // Collect all slide photos for this category's sliding showcase
                $category_slider_images = [];
                foreach ($cat_items as $ci) {
                    if (!empty($ci['gallery_list'])) {
                        foreach ($ci['gallery_list'] as $gimg) {
                            if (!in_array($gimg, $category_slider_images)) {
                                $category_slider_images[] = [
                                    'url' => $gimg,
                                    'title' => $ci['heading'],
                                    'subtitle' => $ci['subtitle'] ?? ''
                                ];
                            }
                        }
                    } elseif (!empty($ci['image_url']) && !in_array($ci['image_url'], $category_slider_images)) {
                        $category_slider_images[] = [
                            'url' => $ci['image_url'],
                            'title' => $ci['heading'],
                            'subtitle' => $ci['subtitle'] ?? ''
                        ];
                    }
                }
            ?>
                <div class="dining-category-pane <?php echo $first_pane ? 'active' : ''; ?>" 
                     id="dining-pane-<?php echo $cat_key; ?>"
                     data-pane="<?php echo $cat_key; ?>">

                    <!-- Category Banner Narrative -->
                    <div class="dining-category-banner">
                        <div class="dining-cat-banner-left">
                            <div class="dining-cat-timing-badge font-sans">
                                <i class="fa-regular fa-clock"></i>
                                <span><?php echo htmlspecialchars($meta['time']); ?></span>
                            </div>
                            <h4 class="dining-cat-heading font-serif"><?php echo htmlspecialchars($meta['title']); ?></h4>
                            <p class="dining-cat-subtext font-sans"><?php echo htmlspecialchars($meta['desc']); ?></p>
                        </div>
                        <div class="dining-cat-banner-right">
                            <span class="dining-farm-tag font-sans"><i class="fa-solid fa-seedling"></i> 100% Organic & Chemical-Free</span>
                        </div>
                    </div>

                    <!-- Compact Sliding Image Carousel / Showcase -->
                    <?php if (!empty($category_slider_images)): ?>
                        <div class="dining-slider-section">
                            <div class="dining-slider-header">
                                <span class="dining-slider-title font-sans">
                                    <i class="fa-solid fa-camera-retro"></i>
                                    <span><?php echo htmlspecialchars($meta['short_title']); ?> Culinary Highlights</span>
                                </span>
                                <div class="dining-slider-controls">
                                    <button type="button" class="dining-slider-arrow prev" data-slider-target="slider-<?php echo $cat_key; ?>" aria-label="Previous Slide">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <button type="button" class="dining-slider-arrow next" data-slider-target="slider-<?php echo $cat_key; ?>" aria-label="Next Slide">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="dining-carousel-container" id="slider-<?php echo $cat_key; ?>">
                                <div class="dining-carousel-track">
                                    <?php foreach ($category_slider_images as $s_idx => $s_item): ?>
                                        <div class="dining-carousel-slide">
                                            <div class="dining-slide-card">
                                                <img src="<?php echo htmlspecialchars($s_item['url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($s_item['title']); ?>" 
                                                     class="dining-slide-img" 
                                                     loading="lazy"
                                                     onerror="this.src='assets/images/treehouse_exterior.png'">
                                                <div class="dining-slide-overlay">
                                                    <span class="dining-slide-tag font-sans"><?php echo htmlspecialchars($meta['short_title']); ?> SPECIAL</span>
                                                    <h5 class="dining-slide-name font-serif"><?php echo htmlspecialchars($s_item['title']); ?></h5>
                                                    <?php if (!empty($s_item['subtitle'])): ?>
                                                        <p class="dining-slide-sub font-sans"><?php echo htmlspecialchars($s_item['subtitle']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="dining-carousel-dots">
                                    <?php foreach ($category_slider_images as $s_idx => $s_item): ?>
                                        <span class="dining-dot <?php echo $s_idx === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo $s_idx; ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Menu Items Grid for this Category -->
                    <div class="dining-items-grid">
                        <?php if (empty($cat_items)): ?>
                            <div class="dining-no-items text-center">
                                <p class="font-sans" style="color: var(--text-light);">Dishes for this ritual are prepared fresh daily upon request. Speak with our concierge for today's specials.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cat_items as $idx => $item): 
                                $is_veg = ($item['dietary_type'] ?? 'veg') === 'veg';
                                $price_display = floatval($item['price']) > 0 ? $currency . number_format($item['price'], 0) : 'Complimentary';
                            ?>
                                <div class="dining-dish-card scroll-reveal">
                                    <div class="dining-dish-media">
                                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'assets/images/treehouse_exterior.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['heading']); ?>" 
                                             class="dining-dish-img" 
                                             loading="lazy"
                                             onerror="this.src='assets/images/treehouse_exterior.png'">
                                        
                                        <div class="dining-dish-badges-overlay">
                                            <span class="dining-dietary-badge <?php echo $is_veg ? 'veg' : 'non-veg'; ?>" title="<?php echo $is_veg ? 'Pure Vegetarian' : 'Non-Vegetarian'; ?>">
                                                <span class="dietary-circle"></span>
                                            </span>
                                            <?php if (!empty($item['badge'])): ?>
                                                <span class="dining-badge-pill font-sans"><?php echo htmlspecialchars($item['badge']); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="dining-dish-price-tag font-sans">
                                            <span class="price-val"><?php echo htmlspecialchars($price_display); ?></span>
                                            <?php if (!empty($item['price_note'])): ?>
                                                <span class="price-note"><?php echo htmlspecialchars($item['price_note']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="dining-dish-body">
                                        <div class="dining-dish-title-row">
                                            <h4 class="dining-dish-heading font-serif"><?php echo htmlspecialchars($item['heading']); ?></h4>
                                        </div>
                                        
                                        <?php if (!empty($item['subtitle'])): ?>
                                            <p class="dining-dish-sub font-sans"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                                        <?php endif; ?>

                                        <?php if (!empty($item['description'])): ?>
                                            <p class="dining-dish-description font-sans"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <?php endif; ?>

                                        <!-- Inclusions Section (List & Items) -->
                                        <?php if (!empty($item['inclusions_list'])): ?>
                                            <div class="dining-inclusions-box">
                                                <div class="inclusions-label font-sans">
                                                    <i class="fa-solid fa-list-check"></i>
                                                    <span>Included in this Set:</span>
                                                </div>
                                                <div class="inclusions-pills-list">
                                                    <?php foreach ($item['inclusions_list'] as $inc): ?>
                                                        <span class="inclusion-pill font-sans">
                                                            <i class="fa-solid fa-circle-check"></i>
                                                            <span><?php echo htmlspecialchars($inc); ?></span>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            <?php 
                $first_pane = false;
            endforeach; 
            ?>
        </div>

    </div>
</section>

<!-- Interactive Dining Tab Switcher & Image Slider Controller -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Dining Category Tabs Switcher
    var tabButtons = document.querySelectorAll('.dining-tab-btn');
    var tabPanes = document.querySelectorAll('.dining-category-pane');

    tabButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var targetCat = this.getAttribute('data-category');
            if (!targetCat) return;

            tabButtons.forEach(function(b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            tabPanes.forEach(function(pane) {
                if (pane.getAttribute('data-pane') === targetCat) {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });
        });
    });

    // 2. Sliding Image Carousels Controller
    function initCategorySliders() {
        var carousels = document.querySelectorAll('.dining-carousel-container');
        carousels.forEach(function(carousel) {
            var track = carousel.querySelector('.dining-carousel-track');
            var slides = carousel.querySelectorAll('.dining-carousel-slide');
            var dots = carousel.querySelectorAll('.dining-dot');
            if (!track || slides.length <= 1) return;

            var currentIdx = 0;
            var totalSlides = slides.length;
            var autoInterval = null;

            function updateCarousel(idx) {
                if (idx < 0) idx = totalSlides - 1;
                if (idx >= totalSlides) idx = 0;
                currentIdx = idx;

                // Scroll smoothly to slide
                var targetSlide = slides[currentIdx];
                if (targetSlide) {
                    var slideWidth = targetSlide.offsetWidth;
                    var scrollPos = targetSlide.offsetLeft - track.offsetLeft;
                    track.scrollTo({ left: scrollPos, behavior: 'smooth' });
                }

                // Update dots
                dots.forEach(function(dot, dIdx) {
                    if (dIdx === currentIdx) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }

            // Dot clicks
            dots.forEach(function(dot) {
                dot.addEventListener('click', function() {
                    var dIdx = parseInt(this.getAttribute('data-slide-index'), 10);
                    if (!isNaN(dIdx)) {
                        updateCarousel(dIdx);
                    }
                });
            });

            // Arrow buttons
            var parentSection = carousel.closest('.dining-slider-section');
            if (parentSection) {
                var prevBtn = parentSection.querySelector('.dining-slider-arrow.prev');
                var nextBtn = parentSection.querySelector('.dining-slider-arrow.next');
                if (prevBtn) {
                    prevBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        updateCarousel(currentIdx - 1);
                    });
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        updateCarousel(currentIdx + 1);
                    });
                }
            }

            // Auto-advance every 5 seconds, paused on hover
            function startAuto() {
                if (autoInterval) clearInterval(autoInterval);
                autoInterval = setInterval(function() {
                    updateCarousel(currentIdx + 1);
                }, 5000);
            }
            function stopAuto() {
                if (autoInterval) clearInterval(autoInterval);
            }

            carousel.addEventListener('mouseenter', stopAuto);
            carousel.addEventListener('mouseleave', startAuto);
            carousel.addEventListener('touchstart', stopAuto, { passive: true });

            startAuto();
        });
    }

    initCategorySliders();
});
</script>
