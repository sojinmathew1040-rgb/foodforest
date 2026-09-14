<!-- Guest Stories (Testimonials) Section -->
<section id="testimonials" class="testimonials-section section-padding">
    <div class="container">
        
        <!-- Header with Navigation Controls -->
        <div class="testimonials-header-bar scroll-reveal">
            <div class="testimonials-header-text">
                <span class="section-label"><?php echo htmlspecialchars(get_setting('testimonials_badge', 'Guest Stories & Chronicles')); ?></span>
                <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars(get_setting('testimonials_title', 'Moments Cherished, Memories Shared')); ?></h3>
                <p class="testimonials-desc font-sans" style="color: var(--text-light); max-width: 680px; margin-top: 10px;">
                    <?php echo htmlspecialchars(get_setting('testimonials_desc', 'Unfiltered impressions from travelers who have slept beneath our high-range canopies and lived in our handcrafted earthen mudhouses.')); ?>
                </p>
            </div>
            <!-- Slider Control Buttons -->
            <div class="testimonials-slider-controls">
                <button type="button" class="btn-testimonial-nav prev magnetic" data-strength="10" id="btn-prev-testimonial" aria-label="Scroll Left">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button type="button" class="btn-testimonial-nav next magnetic" data-strength="10" id="btn-next-testimonial" aria-label="Scroll Right">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>

        <!-- Scrollable Testimonials Carousel (Mouse drag, Touch swipe, Auto-scroll) -->
        <div class="testimonials-carousel-container" id="testimonials-carousel-container">
            <div class="testimonials-track" id="testimonials-track">
                <?php
                require_once __DIR__ . '/../admin/includes/db.php';
                $testimonials_list = get_testimonials();
                if (!empty($testimonials_list)):
                    foreach ($testimonials_list as $t):
                ?>
                    <!-- Dynamic Testimonial Card -->
                    <div class="testimonial-card">
                        <div class="testimonial-card-top">
                            <div class="testimonial-stars">
                                <?php for ($i = 0; $i < (int)$t['stars']; $i++): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="stay-badge font-sans"><?php echo htmlspecialchars($t['stay_badge']); ?></span>
                        </div>
                        <p class="testimonial-quote font-serif">
                            "<?php echo htmlspecialchars($t['quote']); ?>"
                        </p>
                        <div class="testimonial-author-row">
                            <div class="author-avatar font-serif"><?php echo htmlspecialchars($t['initials'] ?: 'FF'); ?></div>
                            <div class="author-info">
                                <span class="author-name font-sans"><?php echo htmlspecialchars($t['guest_name']); ?></span>
                                <span class="author-location font-sans"><?php echo htmlspecialchars($t['guest_location']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>

    </div>
</section>
