<?php
require_once __DIR__ . '/../admin/includes/db.php';
$seasons_badge = get_setting('seasons_badge', 'Every Season has a Story');
$seasons_title = get_setting('seasons_title', "Nature's Changing Canvas");
$seasons_desc = get_setting('seasons_desc', 'Kanthalloor shifts beautifully throughout the year. Each season paints our organic forest retreat in a completely distinct set of colors.');
?>
<!-- Seasons Section -->
<section id="seasons" class="seasons-section section-padding">
    <div class="container">
        
        <div class="seasons-header">
            <span class="section-label"><?php echo htmlspecialchars($seasons_badge); ?></span>
            <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars($seasons_title); ?></h3>
            <p class="seasons-desc font-sans" style="color: var(--text-light);">
                <?php echo htmlspecialchars($seasons_desc); ?>
            </p>
        </div>
        
        <div class="seasons-grid">
            
            <!-- Season 1: Monsoon -->
            <div class="season-card scroll-reveal">
                <div class="season-img-container">
                    <img src="<?php echo htmlspecialchars(get_setting('season_1_image', 'assets/images/01 (9).jpeg')); ?>" alt="Monsoon Season" class="season-img" onerror="this.src='assets/images/01 (9).jpeg'">
                </div>
                <div class="season-overlay"></div>
                <div class="season-content">
                    <span class="season-months font-sans"><?php echo htmlspecialchars(get_setting('season_1_months', 'June - September')); ?></span>
                    <h4 class="season-name font-serif"><?php echo htmlspecialchars(get_setting('season_1_name', 'Monsoon Magic')); ?></h4>
                    <p class="season-desc font-sans">
                        <?php echo htmlspecialchars(get_setting('season_1_desc', 'Lush, deep green landscapes, rising mist, heavy refreshing rainfall, and crisp cold mountain breeze.')); ?>
                    </p>
                </div>
            </div>

            <!-- Season 2: Winter -->
            <div class="season-card scroll-reveal" style="transition-delay: 0.1s;">
                <div class="season-img-container">
                    <img src="<?php echo htmlspecialchars(get_setting('season_2_image', 'assets/images/01 (8).jpeg')); ?>" alt="Winter Season" class="season-img" onerror="this.src='assets/images/01 (8).jpeg'">
                </div>
                <div class="season-overlay"></div>
                <div class="season-content">
                    <span class="season-months font-sans"><?php echo htmlspecialchars(get_setting('season_2_months', 'October - February')); ?></span>
                    <h4 class="season-name font-serif"><?php echo htmlspecialchars(get_setting('season_2_name', 'Cozy Winter')); ?></h4>
                    <p class="season-desc font-sans">
                        <?php echo htmlspecialchars(get_setting('season_2_desc', 'Chilly mist, clear bright blue skies, warm sunlit afternoons, and snug campfire nights under starry skies.')); ?>
                    </p>
                </div>
            </div>

            <!-- Season 3: Harvest Season -->
            <div class="season-card scroll-reveal" style="transition-delay: 0.2s;">
                <div class="season-img-container">
                    <img src="<?php echo htmlspecialchars(get_setting('season_3_image', 'assets/images/01 (19).jpeg')); ?>" alt="Harvest Season" class="season-img" onerror="this.src='assets/images/01 (19).jpeg'">
                </div>
                <div class="season-overlay"></div>
                <div class="season-content">
                    <span class="season-months font-sans"><?php echo htmlspecialchars(get_setting('season_3_months', 'March - May')); ?></span>
                    <h4 class="season-name font-serif"><?php echo htmlspecialchars(get_setting('season_3_name', 'Harvest Season')); ?></h4>
                    <p class="season-desc font-sans">
                        <?php echo htmlspecialchars(get_setting('season_3_desc', 'Fruits and blossoms heavy on the branches. Perfect time to pick apples, plums, peaches, and berries.')); ?>
                    </p>
                </div>
            </div>

            <!-- Season 4: Summer -->
            <div class="season-card scroll-reveal" style="transition-delay: 0.3s;">
                <div class="season-img-container">
                    <img src="<?php echo htmlspecialchars(get_setting('season_4_image', 'assets/images/01 (33).jpeg')); ?>" alt="Summer Season" class="season-img" onerror="this.src='assets/images/01 (33).jpeg'">
                </div>
                <div class="season-overlay"></div>
                <div class="season-content">
                    <span class="season-months font-sans"><?php echo htmlspecialchars(get_setting('season_4_months', 'April - June')); ?></span>
                    <h4 class="season-name font-serif"><?php echo htmlspecialchars(get_setting('season_4_name', 'Cool Summer')); ?></h4>
                    <p class="season-desc font-sans">
                        <?php echo htmlspecialchars(get_setting('season_4_desc', 'Pleasant, breezy weather. Kanthalloor acts as a cool refuge from the sweltering heat of the plains.')); ?>
                    </p>
                </div>
            </div>

        </div>
        
    </div>
</section>
