<?php
require_once __DIR__ . '/../admin/includes/db.php';

$hero_eyebrow = get_setting('hero_eyebrow', 'KANTHALLOOR, KERALA • PRIVATE ECO-SANCTUARY');
$hero_title = get_setting('hero_title', 'Where Earth Breathes & Time Stands Still.');
$hero_desc = get_setting('hero_desc', 'Tucked deep in the misty hills and organic orchards of Kanthalloor. Experience private earthen mudhouses, soaring canopy treehouses, and nourishing farm gastronomy cooked slowly over wood fires.');
$hero_bg_image = get_setting('hero_bg_image', 'assets/images/01 (25).jpeg');
$hero_rooms = get_all_rooms(true);
?>
<!-- Ultra-Luxury Hero Section -->
<section id="hero" class="hero-section">
    <!-- Hero Background Visual -->
    <div class="hero-bg-container" data-speed="-0.15">
        <img src="<?php echo htmlspecialchars($hero_bg_image); ?>" alt="Food Forest Kanthalloor Eco-Retreat" class="hero-bg-img" onerror="this.src='assets/images/treehouse_exterior.png'">
        <div class="hero-overlay"></div>
    </div>
    
    <!-- Hero Main Editorial Content -->
    <div class="hero-content">
        <!-- Refined Luxury Eyebrow Pill -->
        <div class="hero-eyebrow-pill font-sans">
            <span class="hero-pill-bullet"></span>
            <span class="hero-pill-text"><?php echo htmlspecialchars($hero_eyebrow); ?></span>
        </div>
        
        <div class="hero-title-wrapper">
            <h1 class="hero-title font-serif split-text">
                <?php echo nl2br(htmlspecialchars($hero_title)); ?>
            </h1>
        </div>

        <p class="hero-desc font-sans">
            <?php echo htmlspecialchars($hero_desc); ?>
        </p>
    </div>

    <!-- Floating Interactive Availability Booking Bar -->
    <div class="hero-booking-bar-wrapper">
        <div class="hero-booking-bar font-sans">
            <!-- Field 1: Check-in -->
            <div class="booking-bar-field">
                <span class="field-label font-sans"><i class="fa-regular fa-calendar"></i> Check-In</span>
                <input type="date" id="hero-checkin" class="bar-input font-sans" aria-label="Check in Date">
            </div>

            <div class="booking-bar-divider"></div>

            <!-- Field 2: Check-out -->
            <div class="booking-bar-field">
                <span class="field-label font-sans"><i class="fa-regular fa-calendar-check"></i> Check-Out</span>
                <input type="date" id="hero-checkout" class="bar-input font-sans" aria-label="Check out Date">
            </div>

            <div class="booking-bar-divider"></div>

            <!-- Field 3: Guests -->
            <div class="booking-bar-field">
                <span class="field-label font-sans"><i class="fa-solid fa-user-group"></i> Guests</span>
                <select id="hero-guests" class="bar-select font-sans" aria-label="Select Guests">
                    <option value="1">1 Guest</option>
                    <option value="2" selected>2 Guests</option>
                    <option value="3">3 Guests</option>
                    <option value="4">4 Guests</option>
                </select>
            </div>

            <div class="booking-bar-divider"></div>

            <!-- Field 4: Villa Choice -->
            <div class="booking-bar-field">
                <span class="field-label font-sans"><i class="fa-solid fa-house-chimney"></i> Villa Stay</span>
                <select id="hero-villa" class="bar-select font-sans" aria-label="Select Villa">
                    <?php if (!empty($hero_rooms)): ?>
                        <?php foreach ($hero_rooms as $hr): ?>
                            <option value="<?php echo htmlspecialchars($hr['slug']); ?>" data-price="<?php echo htmlspecialchars($hr['rate_per_night']); ?>">
                                <?php echo htmlspecialchars($hr['title']); ?> (₹<?php echo number_format($hr['rate_per_night'], 0, '.', ','); ?>/nt)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="treehouse" data-price="14500">Canopy Treehouse (₹14,500/nt)</option>
                        <option value="mudhouse" data-price="11500">Earthen Mudhouse (₹11,500/nt)</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Action Button -->
            <div class="booking-bar-action">
                <button type="button" id="btn-hero-check-availability" class="btn-primary hero-bar-submit font-sans magnetic" data-strength="15">
                    <span>Check Availability</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Scroll Mouse Indicator -->
    <a href="#welcome" class="hero-scroll-indicator magnetic" data-strength="10">
        <span>Scroll To Explore</span>
        <div class="scroll-mouse">
            <div class="scroll-wheel"></div>
        </div>
    </a>
</section>
