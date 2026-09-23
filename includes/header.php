<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Forest — Luxury Eco-Farmstay & Sanctuary | Kanthalloor, Kerala</title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="Immerse in unhurried luxury at Food Forest, an exclusive organic farmstay in Kanthalloor, Kerala. Experience our two signature stays: Luxury Canopy Treehouses and Traditional Earthen Mudhouses.">
    <meta name="keywords"
        content="Food Forest Kanthalloor, luxury farmstay Kerala, canopy treehouse Kanthalloor, earthen mudhouse Kerala, organic farmstay Kanthalloor, sustainable retreat Kerala">
    <meta name="author" content="Food Forest Kanthalloor">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Food Forest — Luxury Eco-Farmstay | Kanthalloor, Kerala">
    <meta property="og:description"
        content="An organic farmstay sanctuary in the misty hills of Kanthalloor, Kerala. Experience luxury canopy treehouses and earthen mudhouses.">
    <meta property="og:image" content="assets/images/01 (25).jpeg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Food Forest — Luxury Eco-Farmstay | Kanthalloor">
    <meta property="twitter:description"
        content="An unhurried sanctuary in the misty hills of Kanthalloor, Kerala. Sustainable luxury rooted in earth.">

    <!-- Google Fonts: Cormorant Garamond, Cinzel, Plus Jakarta Sans, La Belle Aurore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,600&family=La+Belle+Aurore&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- FontAwesome 6 for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">

    <!-- Core Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
</head>

<body>

<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/client_auth.php';

$top_location = get_setting('top_bar_location', 'Kanthalloor High Range • 1,600m Elevation • 18°C Misty Mountain Air');
$top_accolade = get_setting('top_bar_accolade', 'Rated 4.98 / 5 • Top Sustainable Sanctuary 2026');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');

$is_logged_client = is_client_user_logged_in();
$is_guest_client = is_guest_booking_session_active();
$client_label = 'Guest Portal';
if ($is_logged_client) {
    $client_user = get_logged_in_client_user();
    $client_label = 'My Stays (' . htmlspecialchars(explode(' ', $client_user['full_name'])[0]) . ')';
} elseif ($is_guest_client) {
    $client_label = 'My Booking';
}
$site_name = get_setting('site_name', 'FOOD FOREST');
$site_tagline = get_setting('site_tagline', 'KANTHALLOOR • ECO SANCTUARY');

// Compute minimum starting room rate dynamically from database
$all_header_rooms = get_all_rooms(true);
$min_start_rate = 11500;
if (!empty($all_header_rooms)) {
    $rates = array_map(function($r) { 
        return min((float)$r['rate_per_night'], (float)($r['single_room_rate'] ?? $r['rate_per_night'])); 
    }, $all_header_rooms);
    $min_start_rate = min($rates);
}
?>
    <!-- Luxury Header Wrapper (Coordinates Top Announcement & Main Navigation) -->
    <div class="site-header-wrapper" id="site-header-wrapper">
        <!-- Top Luxury Announcement & Ambient Audio Bar -->
        <div class="top-announcement-bar" id="top-announcement-bar">
            <div class="top-announcement-container">
                <div class="top-announcement-left">
                    <span class="location-pulse"><i class="fa-solid fa-location-dot"></i></span>
                    <span class="font-sans"><?php echo htmlspecialchars($top_location); ?></span>
                </div>
                <div class="top-announcement-center">
                    <span class="luxury-badge-pill font-sans">
                        <i class="fa-solid fa-award"></i> <?php echo htmlspecialchars($top_accolade); ?>
                    </span>
                </div>
                <div class="top-announcement-right">
                    <!-- Ambient Audio Soundscape Toggle -->
                    <button type="button" id="ambient-audio-toggle" class="ambient-audio-btn font-sans" title="Play Forest Soundscape">
                        <span class="sound-wave-bars">
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                        </span>
                        <span class="sound-text">Forest Ambience: <strong id="sound-status-label">OFF</strong></span>
                    </button>
                    <a href="guest_portal.php" class="top-whatsapp-link font-sans" style="color: #C5A059; border-color: rgba(197, 160, 89, 0.4);">
                        <i class="fa-solid fa-user"></i> <?php echo $client_label; ?>
                    </a>
                    <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Food%20Forest%20Concierge,%20I%20would%20like%20to%20enquire%20about%20a%20luxury%20stay." target="_blank" class="top-whatsapp-link font-sans">
                        <i class="fa-brands fa-whatsapp"></i> Concierge
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Luxury Header -->
        <header class="main-header" id="site-header">
            <div class="header-container">
                <!-- Brand Logo -->
                <a href="index.php" class="logo font-serif magnetic" data-strength="15">
                    <span class="logo-main"><?php echo htmlspecialchars($site_name); ?></span>
                    <span class="logo-sub font-sans"><?php echo htmlspecialchars($site_tagline); ?></span>
                </a>

                <?php
                $is_home = (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == '');
                $nav_prefix = $is_home ? '' : 'index.php';
                ?>
                <!-- Editorial Nav Links -->
                <nav class="nav-links font-sans">
                    <a href="<?php echo $nav_prefix; ?>#welcome" class="nav-item magnetic" data-strength="10">The Sanctuary</a>
                    <a href="<?php echo $nav_prefix; ?>#rooms-experience" class="nav-item magnetic" data-strength="10">Villas & Stays</a>
                    <a href="booking.php" class="nav-item magnetic" data-strength="10" style="color: var(--accent-gold); font-weight: 700;"><i class="fa-solid fa-map-location-dot"></i> Map Booking</a>
                    <a href="<?php echo $nav_prefix; ?>#experiences" class="nav-item magnetic" data-strength="10">Activities</a>
                    <a href="<?php echo $nav_prefix; ?>#dining" class="nav-item magnetic" data-strength="10">Food Menu</a>
                    <a href="<?php echo $nav_prefix; ?>#gallery" class="nav-item magnetic" data-strength="10">Gallery</a>
                    <a href="<?php echo $nav_prefix; ?>#sanctuary" class="nav-item magnetic" data-strength="10">Landscape</a>
                    <a href="<?php echo $nav_prefix; ?>#testimonials" class="nav-item magnetic" data-strength="10">Guest Stories</a>
                </nav>

                <!-- Header Actions -->
                <div class="header-actions">
                    <a href="booking.php" class="btn-book-now font-sans magnetic" data-strength="15" style="text-decoration: none;">
                        <span class="btn-sparkle"><i class="fa-solid fa-sparkles"></i></span>
                        <span>RESERVE STAY</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <button class="mobile-nav-toggle" aria-label="Toggle Navigation">
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </button>
                </div>
            </div>
        </header>
    </div>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu font-serif">
        <div class="mobile-menu-links">
            <a href="<?php echo $nav_prefix; ?>#welcome" class="mobile-link">The Sanctuary</a>
            <a href="<?php echo $nav_prefix; ?>#rooms-experience" class="mobile-link">Villas & Stays</a>
            <a href="booking.php" class="mobile-link" style="color: #C5A059;"><i class="fa-solid fa-map-location-dot"></i> Interactive Map Booking</a>
            <a href="<?php echo $nav_prefix; ?>#experiences" class="mobile-link">Activities</a>
            <a href="<?php echo $nav_prefix; ?>#dining" class="mobile-link">Food Menu</a>
            <a href="<?php echo $nav_prefix; ?>#gallery" class="mobile-link">Gallery</a>
            <a href="<?php echo $nav_prefix; ?>#sanctuary" class="mobile-link">Landscape</a>
            <a href="<?php echo $nav_prefix; ?>#testimonials" class="mobile-link">Guest Stories</a>
            <a href="guest_portal.php" class="mobile-link" style="color: #C5A059;"><i class="fa-solid fa-key"></i> Guest Portal / My Bookings</a>
            <a href="<?php echo $nav_prefix; ?>#contact" class="mobile-link">Contact</a>
            <a href="booking.php" class="mobile-link btn-mobile-book font-sans" style="text-decoration: none; text-align: center;">
                <i class="fa-solid fa-calendar-check"></i> Book Chalet Online
            </a>
        </div>
    </div>

    <!-- Floating Sticky Quick-Booking Pill (appears on scroll) -->
    <div class="sticky-booking-pill font-sans" id="sticky-booking-pill">
        <div class="pill-info">
            <span class="pill-title font-serif"><?php echo htmlspecialchars($site_name); ?></span>
            <span class="pill-rates">From ₹<?php echo number_format($min_start_rate, 0, '.', ','); ?>/night • All Organic Farm Meals Included</span>
        </div>
        <button type="button" class="pill-btn open-booking-modal-btn">
            <span>Check Availability</span>
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>

    <!-- Scroll Wrapper for Lenis -->
    <div class="smooth-scroll-wrapper">