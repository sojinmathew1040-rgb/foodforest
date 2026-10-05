<?php
require_once __DIR__ . '/../admin/includes/db.php';

// Check if administrator is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_admin = (!empty($_SESSION['admin_logged_in']) || !empty($_SESSION['admin_id']));

// Fetch section settings
$menu_badge = get_setting('menu_badge', 'ESTATE GASTRONOMY & ORGANIC DINING');
$menu_title = get_setting('menu_title', 'The Forest Hearth & Living Menu');
$menu_desc = get_setting('menu_desc', 'Food at Food Forest is a ritual. Cooked in indigenous clay pots over aromatic wood hearths, every meal is prepared with ingredients harvested minutes prior from our own organic soil.');
$menu_show_images = get_setting('menu_show_images', '1');
$menu_show_highlights = get_setting('menu_show_highlights', '1');
$currency = get_setting('currency_symbol', '₹');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');

// Category metadata definitions
$categories_meta = [
    'breakfast' => [
        'title' => 'Morning in the Orchards',
        'short_title' => 'Breakfast',
        'icon' => 'fa-solid fa-mug-saucer',
        'time' => get_setting('menu_time_breakfast', '09:00 AM — 10:00 AM'),
        'desc' => get_setting('menu_desc_breakfast', 'Morning Breakfast Service • Fresh Dosas, Idlis, Puttu, Kadala & Fresh Brews')
    ],
    'lunch' => [
        'title' => 'Claypot Hearth Feast',
        'short_title' => 'Lunch',
        'icon' => 'fa-solid fa-bowl-rice',
        'time' => get_setting('menu_time_lunch', '12:30 PM — 02:30 PM'),
        'desc' => get_setting('menu_desc_lunch', 'Lunch Service • Veg Meals, Biriyani, Fried Rice & Fresh Curries')
    ],
    'snacks' => [
        'title' => 'Plantation Tea Ritual & Snacks',
        'short_title' => 'Evening Specials',
        'icon' => 'fa-solid fa-cookie-bite',
        'time' => get_setting('menu_time_snacks', '04:30 PM — 06:30 PM'),
        'desc' => get_setting('menu_desc_snacks', 'Evening Specials • Mountain teas, filter coffees, crispy banana fry & hot fritters')
    ],
    'dinner' => [
        'title' => 'Twilight Hearth & Grills',
        'short_title' => 'Dinner',
        'icon' => 'fa-solid fa-fire-burner',
        'time' => get_setting('menu_time_dinner', '07:00 PM — 09:00 PM'),
        'desc' => get_setting('menu_desc_dinner', 'Dinner Service • Chapathi, BBQ Chicken, Paneer Butter Masala & Curries')
    ],
    'millet' => [
        'title' => 'Wholesome Ancient Millets',
        'short_title' => 'Millet Specials',
        'icon' => 'fa-solid fa-seedling',
        'time' => 'Available on Order',
        'desc' => 'Nutrient-rich ancient grains: millet dosas, biriyani, pulav & comfort kanji'
    ],
    'curries' => [
        'title' => 'Estate Curries & Sides',
        'short_title' => 'Curries & Sides',
        'icon' => 'fa-solid fa-utensils',
        'time' => 'Lunch & Dinner',
        'desc' => 'Nadan chicken, broiler, mutton, beef fry, fresh fish & vegetarian curries'
    ],
    'juices' => [
        'title' => 'Cold-Pressed Orchard Juices',
        'short_title' => 'Healthy Juices',
        'icon' => 'fa-solid fa-glass-water',
        'time' => '08:00 AM — 07:00 PM',
        'desc' => 'Pure sugarcane with strawberry, passion fruit, guava & fresh watermelon'
    ]
];

// Fetch active food items
$all_menu_items = get_food_menu_items(null, true);

// Group items by category
$items_by_category = [
    'breakfast' => [],
    'lunch' => [],
    'snacks' => [],
    'dinner' => [],
    'millet' => [],
    'curries' => [],
    'juices' => []
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
<section id="dining" class="dining-section section-padding <?php echo ($menu_show_images === '0' ? 'menu-no-images' : ''); ?> <?php echo ($menu_show_highlights === '0' ? 'menu-no-highlights' : ''); ?>" data-default-images="<?php echo htmlspecialchars($menu_show_images); ?>" data-default-highlights="<?php echo htmlspecialchars($menu_show_highlights); ?>">
    <div class="container">
        
        <?php if (basename($_SERVER['PHP_SELF']) != 'menu.php'): ?>
        <!-- Editorial Section Header -->
        <div class="dining-section-header text-center">
            <span class="section-label"><?php echo htmlspecialchars($menu_badge); ?></span>
            <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars($menu_title); ?></h3>
            <p class="dining-section-desc font-sans" style="color: var(--text-light); max-width: 760px; margin: 16px auto 0; line-height: 1.8;">
                <?php echo htmlspecialchars($menu_desc); ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <!-- Common ON/OFF Controls Toolbar for Photos, Highlights & Details (Admin Only) -->
        <div class="dining-images-toggle-wrapper">
            <div class="dining-admin-badge-indicator font-sans">
                <i class="fa-solid fa-user-shield"></i>
                <span>Admin Controls</span>
            </div>
            <div class="dining-controls-bar">
                <!-- Common Button 1: Dish Item Photos ON/OFF -->
                <button type="button" 
                        id="dining-images-toggle-btn" 
                        class="dining-images-toggle-btn dining-toggle-pill <?php echo ($menu_show_images === '1' ? 'is-on' : 'is-off'); ?>" 
                        aria-pressed="<?php echo ($menu_show_images === '1' ? 'true' : 'false'); ?>"
                        title="Click to toggle dish photos ON or OFF">
                    <span class="toggle-icon"><i class="fa-solid fa-camera"></i></span>
                    <span class="toggle-label font-sans">Dish Photos:</span>
                    <span class="dining-toggle-switch-track">
                        <span class="dining-toggle-switch-thumb"></span>
                    </span>
                    <span class="toggle-status-badge font-sans">
                        <?php echo ($menu_show_images === '1' ? 'ON' : 'OFF'); ?>
                    </span>
                </button>

                <!-- Common Button 2: Culinary Highlights Carousel ON/OFF -->
                <button type="button" 
                        id="dining-highlights-toggle-btn" 
                        class="dining-images-toggle-btn dining-toggle-pill dining-hl-toggle-trigger <?php echo ($menu_show_highlights === '1' ? 'is-on' : 'is-off'); ?>" 
                        aria-pressed="<?php echo ($menu_show_highlights === '1' ? 'true' : 'false'); ?>"
                        title="Click to toggle culinary highlights carousel ON or OFF">
                    <span class="toggle-icon"><i class="fa-solid fa-camera-retro"></i></span>
                    <span class="toggle-label font-sans">Highlights:</span>
                    <span class="dining-toggle-switch-track">
                        <span class="dining-toggle-switch-thumb"></span>
                    </span>
                    <span class="toggle-status-badge font-sans">
                        <?php echo ($menu_show_highlights === '1' ? 'ON' : 'OFF'); ?>
                    </span>
                </button>

                <!-- Common Button 3: Details View (Expand All / Collapse All) -->
                <button type="button" 
                        id="dining-expand-all-btn" 
                        class="dining-images-toggle-btn dining-toggle-pill is-off" 
                        title="Expand or collapse details for all dishes">
                    <span class="toggle-icon"><i class="fa-solid fa-list-ul"></i></span>
                    <span class="toggle-label font-sans">Details:</span>
                    <span class="toggle-status-badge font-sans" id="dining-expand-badge">SHOW ALL</span>
                </button>
            </div>

            <div class="dining-toggle-hints-group font-sans">
                <span class="dining-toggle-hint" id="dining-toggle-hint">
                    <?php if ($menu_show_images === '1'): ?>
                        <i class="fa-solid fa-circle-check"></i> Dish photos ON
                    <?php else: ?>
                        <i class="fa-solid fa-circle-info"></i> Text-only menu (photos hidden)
                    <?php endif; ?>
                </span>
                <span class="dining-hint-divider">•</span>
                <span class="dining-toggle-hint" id="dining-hl-toggle-hint">
                    <?php if ($menu_show_highlights === '1'): ?>
                        <i class="fa-solid fa-circle-check"></i> Highlights ON
                    <?php else: ?>
                        <i class="fa-solid fa-circle-info"></i> Highlights hidden
                    <?php endif; ?>
                </span>
                <span class="dining-hint-divider">•</span>
                <span class="dining-toggle-hint" id="dining-expand-hint">
                    <i class="fa-solid fa-hand-pointer"></i> Tap any dish to view details
                </span>
            </div>
        </div>
        <?php endif; ?>

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
                                    <?php if ($is_admin): ?>
                                    <!-- Common Highlights ON/OFF Toggle Button (Admin Only) -->
                                    <button type="button" 
                                            class="dining-common-hl-toggle-btn dining-hl-toggle-trigger <?php echo ($menu_show_highlights === '1' ? 'is-on' : 'is-off'); ?>" 
                                            aria-pressed="<?php echo ($menu_show_highlights === '1' ? 'true' : 'false'); ?>"
                                            title="Toggle culinary highlights carousel ON or OFF across all categories">
                                        <span class="hl-toggle-icon"><i class="fa-solid fa-camera-retro"></i></span>
                                        <span class="hl-toggle-text font-sans">Highlights:</span>
                                        <span class="hl-toggle-status-pill font-sans"><?php echo ($menu_show_highlights === '1' ? 'ON' : 'OFF'); ?></span>
                                    </button>
                                    <?php endif; ?>
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

                            <?php if ($is_admin): ?>
                            <!-- Minimized State Banner when Highlights are toggled OFF (Admin Only) -->
                            <div class="dining-slider-collapsed-banner">
                                <div class="dining-slider-collapsed-left font-sans">
                                    <i class="fa-solid fa-camera-retro"></i>
                                    <span><strong><?php echo htmlspecialchars($meta['short_title']); ?> Highlights</strong> are hidden</span>
                                </div>
                                <button type="button" class="dining-slider-restore-btn dining-hl-toggle-trigger font-sans" title="Turn Highlights back ON">
                                    <i class="fa-solid fa-eye"></i> Show Highlights (Turn ON)
                                </button>
                            </div>
                            <?php endif; ?>
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
                                <div class="dining-dish-card scroll-reveal" data-dish-id="<?php echo (int)($item['id'] ?? $idx); ?>">
                                    <!-- Name and Rate Only Header (Clickable Accordion Trigger) -->
                                    <div class="dining-dish-header" role="button" tabindex="0" aria-expanded="false" title="Click to view details and ingredients">
                                        <div class="dining-dish-header-left">
                                            <span class="dining-dietary-badge <?php echo $is_veg ? 'veg' : 'non-veg'; ?>" title="<?php echo $is_veg ? 'Pure Vegetarian' : 'Non-Vegetarian'; ?>">
                                                <span class="dietary-circle"></span>
                                            </span>
                                            <div class="dining-dish-title-group">
                                                <h4 class="dining-dish-heading font-serif"><?php echo htmlspecialchars($item['heading']); ?></h4>
                                                <?php if (!empty($item['badge'])): ?>
                                                    <span class="dining-badge-pill font-sans"><?php echo htmlspecialchars($item['badge']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="dining-dish-header-right">
                                            <div class="dining-dish-rate font-sans">
                                                <span class="price-val"><?php echo htmlspecialchars($price_display); ?></span>
                                                <?php if (!empty($item['price_note'])): ?>
                                                    <span class="price-note"><?php echo htmlspecialchars($item['price_note']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="dining-dish-expand-pill font-sans">
                                                <span class="expand-text">Details</span>
                                                <i class="fa-solid fa-chevron-down expand-chevron"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Expandable Details: Subtitle, Description, Inclusions & Photo -->
                                    <div class="dining-dish-expandable-content">
                                        <div class="dining-dish-details-inner">
                                            <?php if (!empty($item['image_url'])): ?>
                                                <div class="dining-dish-media">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['heading']); ?>" 
                                                         class="dining-dish-img" 
                                                         loading="lazy"
                                                         onerror="this.src='assets/images/treehouse_exterior.png'">
                                                </div>
                                            <?php endif; ?>

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
                    requestAnimationFrame(function() {
                        layoutActiveMasonry(false);
                    });
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

    // 3. Menu Images ON/OFF Common Toggle Controller
    var imgToggleBtn = document.getElementById('dining-images-toggle-btn');
    var diningSection = document.getElementById('dining');
    var toggleHint = document.getElementById('dining-toggle-hint');

    function updateMenuImagesUI(showImages) {
        if (!diningSection || !imgToggleBtn) return;
        var badge = imgToggleBtn.querySelector('.toggle-status-badge');
        if (showImages) {
            diningSection.classList.remove('menu-no-images');
            imgToggleBtn.classList.remove('is-off');
            imgToggleBtn.classList.add('is-on');
            imgToggleBtn.setAttribute('aria-pressed', 'true');
            if (badge) badge.textContent = 'ON';
            if (toggleHint) {
                toggleHint.innerHTML = '<i class="fa-solid fa-circle-check"></i> Photos enabled • Click to display text-only menu';
            }
        } else {
            diningSection.classList.add('menu-no-images');
            imgToggleBtn.classList.remove('is-on');
            imgToggleBtn.classList.add('is-off');
            imgToggleBtn.setAttribute('aria-pressed', 'false');
            if (badge) badge.textContent = 'OFF';
            if (toggleHint) {
                toggleHint.innerHTML = '<i class="fa-solid fa-circle-info"></i> Photos hidden • Displaying clean text menu with prices';
            }
        }
        layoutActiveMasonry(false);
    }

    // Initialize state: prioritize saved visitor preference in localStorage ONLY for logged in admins
    var isAdminUser = <?php echo $is_admin ? 'true' : 'false'; ?>;
    if (isAdminUser) {
        var storedPref = localStorage.getItem('ff_menu_show_images');
        if (storedPref !== null) {
            updateMenuImagesUI(storedPref === '1');
        }
    }

    if (imgToggleBtn) {
        imgToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var currentlyShowing = !diningSection.classList.contains('menu-no-images');
            var nextState = !currentlyShowing;
            updateMenuImagesUI(nextState);
            localStorage.setItem('ff_menu_show_images', nextState ? '1' : '0');
        });
    }

    // 4. Menu Highlights ON/OFF Common Toggle Controller
    var hlToggleBtn = document.getElementById('dining-highlights-toggle-btn');
    var hlToggleHint = document.getElementById('dining-hl-toggle-hint');

    function updateMenuHighlightsUI(showHighlights) {
        if (!diningSection) return;

        if (showHighlights) {
            diningSection.classList.remove('menu-no-highlights');
            if (hlToggleBtn) {
                hlToggleBtn.classList.remove('is-off');
                hlToggleBtn.classList.add('is-on');
                hlToggleBtn.setAttribute('aria-pressed', 'true');
                var badge = hlToggleBtn.querySelector('.toggle-status-badge');
                if (badge) badge.textContent = 'ON';
            }
            if (hlToggleHint) {
                hlToggleHint.innerHTML = '<i class="fa-solid fa-circle-check"></i> Highlights ON';
            }
            document.querySelectorAll('.dining-common-hl-toggle-btn').forEach(function(btn) {
                btn.classList.remove('is-off');
                btn.classList.add('is-on');
                btn.setAttribute('aria-pressed', 'true');
                var pill = btn.querySelector('.hl-toggle-status-pill');
                if (pill) pill.textContent = 'ON';
            });
        } else {
            diningSection.classList.add('menu-no-highlights');
            if (hlToggleBtn) {
                hlToggleBtn.classList.remove('is-on');
                hlToggleBtn.classList.add('is-off');
                hlToggleBtn.setAttribute('aria-pressed', 'false');
                var badge = hlToggleBtn.querySelector('.toggle-status-badge');
                if (badge) badge.textContent = 'OFF';
            }
            if (hlToggleHint) {
                hlToggleHint.innerHTML = '<i class="fa-solid fa-circle-info"></i> Highlights hidden';
            }
            document.querySelectorAll('.dining-common-hl-toggle-btn').forEach(function(btn) {
                btn.classList.remove('is-on');
                btn.classList.add('is-off');
                btn.setAttribute('aria-pressed', 'false');
                var pill = btn.querySelector('.hl-toggle-status-pill');
                if (pill) pill.textContent = 'OFF';
            });
        }
        layoutActiveMasonry(false);
    }

    if (isAdminUser) {
        var storedHlPref = localStorage.getItem('ff_menu_show_highlights');
        if (storedHlPref !== null) {
            updateMenuHighlightsUI(storedHlPref === '1');
        }
    }

    // Bind click events on all highlight toggle triggers
    document.querySelectorAll('.dining-hl-toggle-trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            var currentlyShowingHl = !diningSection.classList.contains('menu-no-highlights');
            var nextHlState = !currentlyShowingHl;
            updateMenuHighlightsUI(nextHlState);
            localStorage.setItem('ff_menu_show_highlights', nextHlState ? '1' : '0');
        });
    });

    // 5. Dynamic Animated Masonry Layout Engine
    function layoutMasonryGrid(grid, forceNoTransition) {
        if (!grid) return;
        var parentPane = grid.closest('.dining-category-pane');
        if (parentPane && !parentPane.classList.contains('active')) return;

        var cards = Array.from(grid.querySelectorAll('.dining-dish-card'));
        if (!cards.length) return;

        var containerWidth = grid.offsetWidth;
        if (containerWidth <= 0) return;

        if (forceNoTransition) {
            grid.classList.add('no-transition');
        }

        grid.classList.add('masonry-active');

        var gap = 20;
        var colCount = 1;
        if (containerWidth >= 1050) {
            colCount = 3;
        } else if (containerWidth >= 680) {
            colCount = 2;
        } else {
            colCount = 1;
        }

        var colWidth = (containerWidth - (colCount - 1) * gap) / colCount;
        var colHeights = new Array(colCount).fill(0);

        cards.forEach(function(card) {
            card.style.width = Math.floor(colWidth) + 'px';

            // Find column with minimum height to fill spaces dynamically
            var minCol = 0;
            var minH = colHeights[0];
            for (var c = 1; c < colCount; c++) {
                if (colHeights[c] < minH) {
                    minH = colHeights[c];
                    minCol = c;
                }
            }

            var posX = Math.round(minCol * (colWidth + gap));
            var posY = Math.round(colHeights[minCol]);

            card.style.transform = 'translate3d(' + posX + 'px, ' + posY + 'px, 0)';
            colHeights[minCol] += card.offsetHeight + gap;
        });

        var maxH = Math.max.apply(Math, colHeights);
        if (maxH > 0) maxH -= gap;
        grid.style.height = Math.max(maxH, 80) + 'px';

        if (forceNoTransition) {
            requestAnimationFrame(function() {
                setTimeout(function() {
                    grid.classList.remove('no-transition');
                }, 50);
            });
        }
    }

    function layoutActiveMasonry(forceNoTransition) {
        var activePane = document.querySelector('.dining-category-pane.active');
        if (activePane) {
            var grid = activePane.querySelector('.dining-items-grid');
            if (grid) layoutMasonryGrid(grid, forceNoTransition);
        }
    }

    // 6. Expandable Dish Cards (Show Name & Rate Only, Expand for Details)
    var dishCards = document.querySelectorAll('.dining-dish-card');
    
    dishCards.forEach(function(card) {
        var header = card.querySelector('.dining-dish-header');
        if (!header) return;

        function toggleCard() {
            var isExpanded = card.classList.contains('is-expanded');
            if (isExpanded) {
                card.classList.remove('is-expanded');
                header.setAttribute('aria-expanded', 'false');
                var text = header.querySelector('.expand-text');
                if (text) text.textContent = 'Details';
            } else {
                card.classList.add('is-expanded');
                header.setAttribute('aria-expanded', 'true');
                var text = header.querySelector('.expand-text');
                if (text) text.textContent = 'Close';
            }
            var grid = card.closest('.dining-items-grid');
            if (grid) {
                layoutMasonryGrid(grid, false);
            }
        }

        header.addEventListener('click', function(e) {
            e.preventDefault();
            toggleCard();
        });

        header.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleCard();
            }
        });
    });

    // 7. Expand All / Collapse All Button Controller
    var expandAllBtn = document.getElementById('dining-expand-all-btn');
    var expandBadge = document.getElementById('dining-expand-badge');
    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var activePane = document.querySelector('.dining-category-pane.active') || document;
            var activeCards = activePane.querySelectorAll('.dining-dish-card');
            var anyCollapsed = false;
            activeCards.forEach(function(c) {
                if (!c.classList.contains('is-expanded')) anyCollapsed = true;
            });

            activeCards.forEach(function(c) {
                var hdr = c.querySelector('.dining-dish-header');
                var txt = c.querySelector('.expand-text');
                if (anyCollapsed) {
                    c.classList.add('is-expanded');
                    if (hdr) hdr.setAttribute('aria-expanded', 'true');
                    if (txt) txt.textContent = 'Close';
                } else {
                    c.classList.remove('is-expanded');
                    if (hdr) hdr.setAttribute('aria-expanded', 'false');
                    if (txt) txt.textContent = 'Details';
                }
            });

            if (expandBadge) {
                expandBadge.textContent = anyCollapsed ? 'HIDE ALL' : 'SHOW ALL';
            }
            if (anyCollapsed) {
                expandAllBtn.classList.add('is-on');
                expandAllBtn.classList.remove('is-off');
            } else {
                expandAllBtn.classList.remove('is-on');
                expandAllBtn.classList.add('is-off');
            }

            var activeGrid = activePane.querySelector('.dining-items-grid');
            if (activeGrid) {
                layoutMasonryGrid(activeGrid, false);
            }
        });
    }

    // 8. Re-layout Masonry on dynamic events (Initial Load, Image Loads, Window Resizing)
    layoutActiveMasonry(true);

    window.addEventListener('load', function() {
        layoutActiveMasonry(true);
    });

    var resizeTimer = null;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            layoutActiveMasonry(true);
        }, 100);
    });

    document.querySelectorAll('.dining-dish-card img').forEach(function(img) {
        if (!img.complete) {
            img.addEventListener('load', function() {
                var grid = img.closest('.dining-items-grid');
                if (grid) layoutMasonryGrid(grid, false);
            });
        }
    });
});
</script>

<style>
/* Common Menu Images ON/OFF Button Styling */
.dining-images-toggle-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin: 22px auto 0;
    width: fit-content;
}

.dining-admin-badge-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #935B28;
    background: rgba(197, 160, 89, 0.15);
    border: 1px solid rgba(197, 160, 89, 0.35);
    padding: 3px 12px;
    border-radius: 20px;
    margin-bottom: 2px;
}

.dining-images-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #FFFFFF;
    border: 1.5px solid rgba(197, 160, 89, 0.45);
    border-radius: 40px;
    padding: 6px 14px 6px 16px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(11, 24, 16, 0.05);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
    outline: none;
}

.dining-images-toggle-btn:hover {
    border-color: var(--accent-gold);
    box-shadow: 0 6px 20px rgba(197, 160, 89, 0.2);
    transform: translateY(-2px);
}

.dining-images-toggle-btn .toggle-icon {
    font-size: 15px;
    color: var(--accent-gold);
}

.dining-images-toggle-btn .toggle-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--accent-green);
    letter-spacing: 0.3px;
}

.dining-toggle-switch-track {
    width: 42px;
    height: 22px;
    background: #CBD5E1;
    border-radius: 20px;
    position: relative;
    transition: background 0.3s ease;
    display: inline-block;
}

.dining-toggle-switch-thumb {
    width: 18px;
    height: 18px;
    background: #FFFFFF;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ON State */
.dining-images-toggle-btn.is-on {
    border-color: #10B981;
    background: #F0FDF4;
}

.dining-images-toggle-btn.is-on .dining-toggle-switch-track {
    background: #10B981;
}

.dining-images-toggle-btn.is-on .dining-toggle-switch-thumb {
    transform: translateX(20px);
}

.dining-images-toggle-btn.is-on .toggle-status-badge {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #86EFAC;
}

/* OFF State */
.dining-images-toggle-btn.is-off {
    border-color: #CBD5E1;
    background: #F8FAFC;
}

.dining-images-toggle-btn.is-off .dining-toggle-switch-track {
    background: #94A3B8;
}

.dining-images-toggle-btn.is-off .dining-toggle-switch-thumb {
    transform: translateX(0);
}

.dining-images-toggle-btn.is-off .toggle-status-badge {
    background: #E2E8F0;
    color: #475569;
    border: 1px solid #CBD5E1;
}

.dining-images-toggle-btn .toggle-status-badge {
    font-size: 11px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 12px;
    letter-spacing: 0.5px;
    min-width: 34px;
    text-align: center;
}

.dining-toggle-hint {
    font-size: 11.5px;
    color: var(--text-light);
    display: flex;
    align-items: center;
    gap: 6px;
}

.dining-toggle-hint i {
    color: var(--accent-gold);
}

/* Expandable Dish Card Styling */
.dining-dish-card {
    background: #FFFFFF;
    border: 1px solid rgba(197, 160, 89, 0.28);
    border-left: 4px solid var(--accent-gold);
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(11, 24, 16, 0.04);
    transition: transform 0.45s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.3s ease, border-color 0.3s ease;
    overflow: hidden;
    position: relative;
    align-self: start;
}

.dining-dish-card:hover {
    border-left-color: var(--accent-green);
    box-shadow: 0 10px 28px rgba(11, 24, 16, 0.12);
    z-index: 6;
}

.dining-dish-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    cursor: pointer;
    user-select: none;
    gap: 16px;
    transition: background 0.2s ease;
}

.dining-dish-header:hover {
    background: rgba(248, 246, 240, 0.75);
}

.dining-dish-header:focus-visible {
    outline: 2px solid var(--accent-gold);
    outline-offset: -2px;
}

.dining-dish-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.dining-dish-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    min-width: 0;
}

.dining-dish-heading {
    margin: 0;
    font-size: 1.25rem;
    color: var(--accent-green);
    font-weight: 600;
    line-height: 1.25;
}

.dining-badge-pill {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    background: rgba(197, 160, 89, 0.15);
    color: #935B28;
    border: 1px solid rgba(197, 160, 89, 0.35);
    padding: 2px 8px;
    border-radius: 12px;
    white-space: nowrap;
}

.dining-dish-header-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
}

.dining-dish-rate {
    text-align: right;
    min-width: 60px;
}

.dining-dish-rate .price-val {
    font-size: 1.35rem;
    font-weight: 700;
    color: #935B28;
    display: block;
    line-height: 1;
}

.dining-dish-rate .price-note {
    font-size: 0.72rem;
    color: var(--text-muted);
    display: block;
    margin-top: 3px;
    white-space: nowrap;
}

.dining-dish-expand-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    color: var(--accent-green);
    background: rgba(28, 56, 38, 0.06);
    border: 1px solid rgba(28, 56, 38, 0.15);
    padding: 5px 11px;
    border-radius: 20px;
    transition: all 0.25s ease;
}

.dining-dish-expand-pill .expand-chevron {
    font-size: 10px;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.dining-dish-card:hover .dining-dish-expand-pill {
    background: var(--accent-gold);
    color: #FFFFFF;
    border-color: var(--accent-gold);
}

/* Expanded State */
.dining-dish-card.is-expanded {
    border-left-color: #10B981;
    box-shadow: 0 10px 28px rgba(11, 24, 16, 0.08);
}

.dining-dish-card.is-expanded .dining-dish-header {
    background: rgba(240, 253, 244, 0.4);
    border-bottom: 1px dashed rgba(197, 160, 89, 0.3);
}

.dining-dish-card.is-expanded .expand-chevron {
    transform: rotate(180deg);
}

.dining-dish-card.is-expanded .dining-dish-expand-pill {
    background: #DCFCE7;
    color: #15803D;
    border-color: #86EFAC;
}

/* Expandable Content Container */
.dining-dish-expandable-content {
    display: none;
    animation: diningFadeIn 0.3s ease;
}

.dining-dish-card.is-expanded .dining-dish-expandable-content {
    display: block;
}

@keyframes diningFadeIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

.dining-dish-details-inner {
    padding: 20px 22px 22px;
}

.dining-dish-details-inner .dining-dish-media {
    margin-bottom: 16px;
    border-radius: 10px;
    overflow: hidden;
    max-height: 220px;
}

.dining-dish-details-inner .dining-dish-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
}

.dining-dish-details-inner .dining-dish-sub {
    color: #935B28;
    font-weight: 600;
    font-size: 0.95rem;
    margin: 0 0 10px;
    line-height: 1.45;
}

.dining-dish-details-inner .dining-dish-description {
    color: var(--text-light);
    font-size: 0.9rem;
    line-height: 1.65;
    margin: 0 0 16px;
}

.dining-dish-details-inner .dining-inclusions-box {
    background: #FDFBF7;
    border: 1px dashed rgba(197, 160, 89, 0.45);
    border-radius: 10px;
    padding: 14px 16px;
    margin-top: 12px;
}

.dining-section.menu-no-images .dining-dish-media {
    display: none !important;
}

.dining-section.menu-no-images .dining-slider-section {
    display: none !important;
}

.dining-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    align-items: start;
    position: relative;
}

/* Dynamic Animated Masonry Arrangement */
.dining-items-grid.masonry-active {
    display: block;
    position: relative;
    width: 100%;
    transition: height 0.45s cubic-bezier(0.25, 1, 0.5, 1);
}

.dining-items-grid.masonry-active .dining-dish-card {
    position: absolute;
    top: 0;
    left: 0;
    margin: 0;
    box-sizing: border-box;
    will-change: transform;
    transition: transform 0.45s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.3s ease, border-color 0.3s ease;
}

.dining-items-grid.no-transition .dining-dish-card,
.dining-items-grid.no-transition {
    transition: none !important;
}

@media (max-width: 600px) {
    .dining-items-grid {
        grid-template-columns: 1fr;
    }
    .dining-dish-header {
        padding: 14px 16px;
        gap: 10px;
    }
    .dining-dish-heading {
        font-size: 1.15rem;
    }
    .dining-dish-rate .price-val {
        font-size: 1.18rem;
    }
    .dining-dish-expand-pill .expand-text {
        display: none;
    }
    .dining-dish-expand-pill {
        padding: 6px 8px;
    }
    .dining-dish-details-inner {
        padding: 16px;
    }
}

/* Highlights Section & Controls Styling */
.dining-controls-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
}

.dining-toggle-hints-group {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 6px;
}

.dining-hint-divider {
    color: rgba(197, 160, 89, 0.5);
    font-size: 10px;
}

/* When Highlights are toggled OFF */
.dining-section.menu-no-highlights .dining-slider-section {
    display: none !important;
}

.dining-common-hl-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #FFFFFF;
    border: 1px solid rgba(197, 160, 89, 0.4);
    border-radius: 20px;
    padding: 4px 10px;
    cursor: pointer;
    font-size: 11.5px;
    color: var(--accent-green);
    transition: all 0.25s ease;
    outline: none;
}

.dining-common-hl-toggle-btn:hover {
    border-color: var(--accent-gold);
    transform: translateY(-1px);
}

.dining-common-hl-toggle-btn.is-on {
    border-color: #10B981;
    background: #F0FDF4;
}

.dining-common-hl-toggle-btn.is-on .hl-toggle-status-pill {
    background: #DCFCE7;
    color: #15803D;
}

.dining-common-hl-toggle-btn.is-off {
    border-color: #CBD5E1;
    background: #F8FAFC;
}

.dining-common-hl-toggle-btn.is-off .hl-toggle-status-pill {
    background: #E2E8F0;
    color: #475569;
}

.hl-toggle-status-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
}

.dining-slider-collapsed-banner {
    display: none;
}
</style>
