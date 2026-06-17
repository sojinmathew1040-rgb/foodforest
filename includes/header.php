<?php
// Self-installer setup to copy assets from brain folder to local assets
$source_dir = 'C:/Users/sojin/.gemini/antigravity-ide/brain/076a2ed0-05e5-40cb-a619-0fc1201e9758/';
$dest_dir = dirname(__DIR__) . '/assets/images/';

if (!is_dir($dest_dir)) {
    mkdir($dest_dir, 0777, true);
}

$images = [
    'treehouse_exterior.png' => 'treehouse_exterior_1781169354966.png',
    'treehouse_interior.png' => 'treehouse_interior_1781169369356.png',
    'mudhouse_exterior.png' => 'mudhouse_exterior_1781169386119.png',
    'mudhouse_interior.png' => 'mudhouse_interior_1781169402031.png',
];

foreach ($images as $dest_name => $source_name) {
    $src_path = $source_dir . $source_name;
    $dst_path = $dest_dir . $dest_name;
    if (file_exists($src_path) && !file_exists($dst_path)) {
        copy($src_path, $dst_path);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Forest — Premium Cinematic Eco-Retreat | Mudhouse Kanthalloor</title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="Immerse yourself in nature at Food Forest, a premium eco-retreat in Kanthalloor, Kerala. Experience sustainable mudhouses, luxury treehouses, organic dining, and absolute peace.">
    <meta name="keywords"
        content="eco retreat Kanthalloor, Mudhouse Kanthalloor, luxury treehouse Kerala, organic farm stay, Kerala tourism, premium resort Kanthalloor">
    <meta name="author" content="Food Forest">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Food Forest — Premium Cinematic Eco-Retreat | Mudhouse Kanthalloor">
    <meta property="og:description"
        content="A rustic luxury eco-retreat in the misty hills of Kanthalloor, Kerala. Rediscover yourself close to nature.">
    <meta property="og:image" content="assets/images/01 (25).jpeg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Food Forest — Premium Cinematic Eco-Retreat | Mudhouse Kanthalloor">
    <meta property="twitter:description"
        content="A rustic luxury eco-retreat in the misty hills of Kanthalloor, Kerala. Rediscover yourself close to nature.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=La+Belle+Aurore&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Local Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- CDNs (Loaded early for scripting dependencies) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
</head>

<body>



    <!-- Mouse Glow Follower -->
    <div id="mouse-glow"></div>
    <div id="custom-cursor">
        <div class="cursor-dot"></div>
        <div class="cursor-ring"></div>
        <span class="cursor-text">DRAG</span>
    </div>

    <!-- Header Navigation -->
    <header class="main-header">
        <div class="header-container">
            <a href="#" class="logo font-serif magnetic" data-strength="15">
                FOOD FOREST
                <span class="logo-sub">KANTHALLOOR</span>
            </a>

            <nav class="nav-links font-sans">
                <a href="#welcome" class="nav-item magnetic" data-strength="10">About Us</a>
                <a href="#rooms-experience" class="nav-item magnetic" data-strength="10">Stay</a>
                <a href="#experiences" class="nav-item magnetic" data-strength="10">Experiences</a>
                <a href="#dining" class="nav-item magnetic" data-strength="10">Taste</a>
                <a href="#blog" class="nav-item magnetic" data-strength="10">Journal</a>
            </nav>

            <div class="header-actions">
                <a href="#contact" class="btn-book-now font-sans magnetic" data-strength="15">
                    <span>BOOK YOUR ESCAPE</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <button class="mobile-nav-toggle" aria-label="Toggle Navigation">
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu font-serif">
        <div class="mobile-menu-links">
            <a href="#welcome" class="mobile-link">About Us</a>
            <a href="#rooms-experience" class="mobile-link">Stay</a>
            <a href="#experiences" class="mobile-link">Experiences</a>
            <a href="#dining" class="mobile-link">Taste</a>
            <a href="#blog" class="mobile-link">Journal</a>
            <a href="#contact" class="mobile-link btn-mobile-book">Book Now</a>
        </div>
    </div>

    <!-- Scroll Wrapper for Lenis -->
    <div class="smooth-scroll-wrapper">