<?php
require_once __DIR__ . '/../admin/includes/db.php';
$why_badge = get_setting('why_badge', 'Why Food Forest?');
$why_title = get_setting('why_title', 'An Authentic Farmstay Sanctuary');
$why_desc = get_setting('why_desc', 'At Food Forest Kanthalloor, every design detail is curated to offer comfort while leaving zero ecological footprint. As an organic farmstay in the high-altitude hills of Kerala, we offer two distinct living experiences: soaring high into the canopy in our Luxury Treehouse, or grounded in the ancient thermal cool of our Traditional Earthen Mudhouse.');
$why_image = get_setting('why_image', 'assets/images/01 (26).jpeg');
?>
<!-- Why Food Forest Section -->
<section id="why-mudhouse" class="why-mudhouse-section">
    <div class="container why-grid">
        
        <div class="why-visual-side parallax-wrap scroll-reveal">
            <img src="<?php echo htmlspecialchars($why_image); ?>" alt="Food Forest Kanthalloor Farmstay" class="why-img" data-speed="0.1" onerror="this.src='assets/images/treehouse_exterior.png'">
        </div>
        
        <div class="why-text-side">
            <div class="why-intro">
                <span class="section-label" style="color: var(--accent-terracotta);"><?php echo htmlspecialchars($why_badge); ?></span>
                <h3 class="section-title font-serif split-text"><?php echo htmlspecialchars($why_title); ?></h3>
                <p class="why-intro-desc font-sans">
                    <?php echo nl2br(htmlspecialchars($why_desc)); ?>
                </p>
            </div>
            
            <div class="why-features-list">
                
                <div class="why-feature scroll-reveal">
                    <div class="why-feature-icon"><i class="fa-solid fa-tree"></i></div>
                    <h4 class="why-feature-title font-serif"><?php echo htmlspecialchars(get_setting('why_feat1_title', 'Canopy & Mud Living')); ?></h4>
                    <p class="why-feature-desc font-sans"><?php echo htmlspecialchars(get_setting('why_feat1_desc', 'Choose between elevated treehouses nestled 30ft in ancient branches or traditional clay cob mudhouses with thermal regulation.')); ?></p>
                </div>
                
                <div class="why-feature scroll-reveal" style="transition-delay: 0.1s;">
                    <div class="why-feature-icon"><i class="fa-solid fa-seedling"></i></div>
                    <h4 class="why-feature-title font-serif"><?php echo htmlspecialchars(get_setting('why_feat2_title', '100% Organic Farmstay')); ?></h4>
                    <p class="why-feature-desc font-sans"><?php echo htmlspecialchars(get_setting('why_feat2_desc', 'Live right inside chemical-free apple, plum, and tree tomato orchards. Every meal is harvested fresh from our fertile soil.')); ?></p>
                </div>
                
                <div class="why-feature scroll-reveal" style="transition-delay: 0.2s;">
                    <div class="why-feature-icon"><i class="fa-solid fa-bowl-food"></i></div>
                    <h4 class="why-feature-title font-serif"><?php echo htmlspecialchars(get_setting('why_feat3_title', 'Claypot Hearth Cuisine')); ?></h4>
                    <p class="why-feature-desc font-sans"><?php echo htmlspecialchars(get_setting('why_feat3_desc', 'Authentic Kerala slow cooking in earthenware over teak wood fires, flavored with indigenous Marayoor forest spices.')); ?></p>
                </div>
                
                <div class="why-feature scroll-reveal" style="transition-delay: 0.3s;">
                    <div class="why-feature-icon"><i class="fa-solid fa-mountain"></i></div>
                    <h4 class="why-feature-title font-serif"><?php echo htmlspecialchars(get_setting('why_feat4_title', 'High-Range Serenity')); ?></h4>
                    <p class="why-feature-desc font-sans"><?php echo htmlspecialchars(get_setting('why_feat4_desc', 'Perched at 1,600 meters in Kanthalloor. Wake up to heavy mountain fog, native birdsong, and total acoustic tranquility.')); ?></p>
                </div>
                
            </div>
        </div>
        
    </div>
</section>
