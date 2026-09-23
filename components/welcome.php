<?php
require_once __DIR__ . '/../admin/includes/db.php';

$welcome_badge = get_setting('welcome_badge', 'THE SANCTUARY PHILOSOPHY');
$welcome_title = get_setting('welcome_title', 'Rooted in Earth, Reverence & Time');
$welcome_paragraph = get_setting('welcome_paragraph', 'Food Forest is not merely a getaway; it is a conscious return to living in harmony with nature. Tucked into the mist-veiled terraced hills of Kanthalloor, Kerala, our estate was conceived as a living ecosystem where luxury means silence, pure mountain spring water, and unhurried peace.');
$welcome_image = get_setting('welcome_image', 'assets/images/01 (7).jpeg');
?>
<!-- Welcome & Philosophy Section -->
<section id="welcome" class="welcome-section">
    <div class="container welcome-grid">
        <div class="welcome-text-side">
            <div class="welcome-badge-header">
                <span class="section-label"><?php echo htmlspecialchars($welcome_badge); ?></span>
            </div>
            <h2 class="section-title font-serif split-text"><?php echo htmlspecialchars($welcome_title); ?></h2>
            <p class="welcome-paragraph font-sans">
                <?php echo htmlspecialchars($welcome_paragraph); ?>
            </p>
            
            <div class="welcome-details">
                <div class="detail-card scroll-reveal">
                    <div class="detail-icon"><i class="fa-solid fa-leaf"></i></div>
                    <div>
                        <h4 class="detail-title font-serif"><?php echo htmlspecialchars(get_setting('welcome_feat1_title', 'Ecological Vernacular')); ?></h4>
                        <p class="detail-desc font-sans"><?php echo htmlspecialchars(get_setting('welcome_feat1_desc', 'Earthen clay, raw stone, reclaimed teak, and zero plastic across the retreat.')); ?></p>
                    </div>
                </div>
                <div class="detail-card scroll-reveal" style="transition-delay: 0.1s;">
                    <div class="detail-icon"><i class="fa-solid fa-seedling"></i></div>
                    <div>
                        <h4 class="detail-title font-serif"><?php echo htmlspecialchars(get_setting('welcome_feat2_title', 'Pure Farm-to-Table')); ?></h4>
                        <p class="detail-desc font-sans"><?php echo htmlspecialchars(get_setting('welcome_feat2_desc', 'Organic chemical-free orchards. Meals harvested minutes before cooking over earthen wood fires.')); ?></p>
                    </div>
                </div>
                <div class="detail-card scroll-reveal" style="transition-delay: 0.2s;">
                    <div class="detail-icon"><i class="fa-solid fa-wind"></i></div>
                    <div>
                        <h4 class="detail-title font-serif"><?php echo htmlspecialchars(get_setting('welcome_feat3_title', 'High-Range Climate')); ?></h4>
                        <p class="detail-desc font-sans"><?php echo htmlspecialchars(get_setting('welcome_feat3_desc', 'Situated at 1,600m altitude. Chilly night mists, crisp mountain breeze, and clear skies.')); ?></p>
                    </div>
                </div>
                <div class="detail-card scroll-reveal" style="transition-delay: 0.3s;">
                    <div class="detail-icon"><i class="fa-solid fa-shield-heart"></i></div>
                    <div>
                        <h4 class="detail-title font-serif"><?php echo htmlspecialchars(get_setting('welcome_feat4_title', 'Intimate & Private')); ?></h4>
                        <p class="detail-desc font-sans"><?php echo htmlspecialchars(get_setting('welcome_feat4_desc', 'Exclusive living stay concepts nestled among organic orchards to guarantee absolute privacy and silence.')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="welcome-visual-side parallax-wrap">
            <div class="luxury-image-frame">
                <img src="<?php echo htmlspecialchars($welcome_image); ?>" alt="Sanctuary Estate in Kanthalloor" class="welcome-img" data-speed="0.08" onerror="this.src='assets/images/mudhouse_exterior.png'">
                <div class="image-corner-ornament top-left"></div>
                <div class="image-corner-ornament bottom-right"></div>
            </div>
        </div>
    </div>
</section>
