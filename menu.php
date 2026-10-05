<?php
// =========================================================================
// Food Forest Sanctuary — Our Menu (Dedicated Gastronomy Page)
// =========================================================================

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Dedicated Our Menu Page Hero Header -->
<div class="page-hero-header">
    <div class="container">
        <div class="page-hero-content text-center scroll-reveal">
            <span class="section-label">ORGANIC HEIRLOOM CUISINE</span>
            <h1 class="page-hero-title font-serif split-text" style="color: var(--accent-green);">Our Menu</h1>
            <p class="font-sans page-hero-desc" style="color: var(--text-light); max-width: 720px; margin: 15px auto 0; line-height: 1.8;">
                Every dish is prepared fresh on-site in traditional earthen clay pots and wood hearths. Prepared using organic ingredients plucked directly from our high-altitude orchards and local gardens. Outside food is strictly not allowed.
            </p>
            <div class="gallery-hero-meta font-sans">
                <span><i class="fa-solid fa-seedling"></i> 100% Organic Soil</span>
                <span><i class="fa-solid fa-fire-burner"></i> Woodfire Earthen Hearth</span>
                <span><i class="fa-solid fa-droplet"></i> Natural Spring Water</span>
                <span><i class="fa-solid fa-ban"></i> Outside Food Not Allowed</span>
            </div>
        </div>
    </div>
</div>

<?php
// Load Dynamic Food Menu Component
require_once __DIR__ . '/components/dining.php';

// Load Footer
require_once __DIR__ . '/includes/footer.php';
?>
