<?php
require_once __DIR__ . '/../admin/includes/db.php';
$seasons_badge = get_setting('seasons_badge', 'Every Season has a Story');
$seasons_title = get_setting('seasons_title', "Nature's Changing Canvas");
$seasons_desc = get_setting('seasons_desc', 'Kanthalloor shifts beautifully throughout the year. Each season paints our organic forest retreat in a completely distinct set of colors.');
$seasons = get_all_seasons(true);
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
            <?php if (!empty($seasons)): ?>
                <?php foreach ($seasons as $idx => $season): 
                    $delay = ($idx % 4) * 0.1;
                ?>
                    <div class="season-card scroll-reveal" style="transition-delay: <?php echo $delay; ?>s;">
                        <div class="season-img-container">
                            <img src="<?php echo htmlspecialchars($season['image_url']); ?>" alt="<?php echo htmlspecialchars($season['title']); ?>" class="season-img" onerror="this.src='assets/images/treehouse_exterior.png'">
                        </div>
                        <div class="season-overlay"></div>
                        <div class="season-content">
                            <span class="season-months font-sans"><?php echo htmlspecialchars($season['months']); ?></span>
                            <h4 class="season-name font-serif"><?php echo htmlspecialchars($season['title']); ?></h4>
                            <p class="season-desc font-sans">
                                <?php echo htmlspecialchars($season['description']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</section>
