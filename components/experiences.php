<?php
require_once __DIR__ . '/../admin/includes/db.php';
$exp_badge = get_setting('experiences_badge', 'Curated Journeys');
$exp_title = get_setting('experiences_title', 'Rituals of the High Range');
$exp_desc = get_setting('experiences_desc', 'Connect deeply with the pulse of Kanthalloor. Each experience is handcrafted to immerse you in vernacular craftsmanship, mountain wilderness, and restorative tranquility.');
$experiences_list = get_experiences();
?>
<!-- Curated Experiences Section -->
<section id="experiences" class="experiences-section section-padding">
    <div class="container">
        
        <div class="experiences-header text-center">
            <span class="section-label"><?php echo htmlspecialchars($exp_badge); ?></span>
            <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars($exp_title); ?></h3>
            <p class="experiences-desc font-sans" style="color: var(--text-light); max-width: 750px; margin: 15px auto 0;">
                <?php echo htmlspecialchars($exp_desc); ?>
            </p>
        </div>
        
        <div class="experiences-grid">
            <?php if (!empty($experiences_list)): ?>
                <?php foreach ($experiences_list as $idx => $exp): 
                    $delay = ($idx % 4) * 0.1;
                ?>
                    <!-- Dynamic Experience Card -->
                    <div class="experience-card scroll-reveal" style="transition-delay: <?php echo $delay; ?>s;">
                        <div class="experience-img-wrapper">
                            <img src="<?php echo htmlspecialchars($exp['image_url']); ?>" alt="<?php echo htmlspecialchars($exp['title']); ?>" class="experience-img" onerror="this.src='assets/images/treehouse_exterior.png'">
                            <div class="experience-badges">
                                <span class="exp-badge font-sans"><?php echo htmlspecialchars($exp['badge']); ?></span>
                                <span class="exp-timing font-sans"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($exp['timing']); ?></span>
                            </div>
                        </div>
                        <div class="experience-info">
                            <h4 class="experience-card-title font-serif"><?php echo htmlspecialchars($exp['title']); ?></h4>
                            <p class="experience-card-desc font-sans">
                                <?php echo htmlspecialchars($exp['description']); ?>
                            </p>
                            <button type="button" class="experience-arrow font-sans open-booking-modal-btn">
                                <span>Book Sanctuary Experience</span> <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</section>
