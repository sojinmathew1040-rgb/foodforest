<?php
// =========================================================================
// Food Forest Sanctuary — Live Real-Time Frontend Section Preview Frame
// Renders pixel-perfect frontend components with live synchronization
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';

$pdo = get_db();
$section = $_GET['section'] ?? 'estate';

// Load settings
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$s = [];
while ($row = $settings_stmt->fetch()) {
    $s[$row['setting_key']] = $row['setting_value'];
}

$estate_name = $s['estate_name'] ?? 'Food Forest Kanthalloor';
$estate_tagline = $s['estate_tagline'] ?? 'Eco Sanctuary & Agro Farmstay';
$currency_symbol = $s['currency_symbol'] ?? '₹';
$checkin_time = $s['checkin_time'] ?? '01:00 PM';
$checkout_time = $s['checkout_time'] ?? '11:00 AM';
$concierge_whatsapp = $s['concierge_whatsapp'] ?? '+919447000000';
$concierge_phone = $s['concierge_phone'] ?? '+91 94470 00000';
$concierge_email = $s['concierge_email'] ?? 'concierge@foodforestkanthalloor.com';
$location = $s['location'] ?? 'Kanthalloor High Ranges, Munnar, Kerala';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Preview — <?php echo htmlspecialchars(ucfirst($section)); ?></title>

    <!-- Google Fonts: Cinzel, Cormorant Garamond, Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;800&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=La+Belle+Aurore&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Public Website Stylesheet -->
    <base href="../">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">

    <style>
        /* Preview-specific isolation & helper styles */
        body.preview-mode {
            background-color: var(--forest-deep, #0B1810);
            color: #EAEFED;
            font-family: var(--font-sans, 'Plus Jakarta Sans', sans-serif);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        .preview-wrapper {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            position: relative;
        }

        .scroll-reveal {
            opacity: 1 !important;
            transform: none !important;
            visibility: visible !important;
        }

        .section-padding {
            padding: 30px 0 !important;
        }

        . {
            /* animation disabled */
        }

        @keyframes previewGlow {
            0% { outline: 3px solid #C5A059; box-shadow: 0 0 20px rgba(197, 160, 89, 0.8); }
            100% { outline: 3px solid transparent; box-shadow: none; }
        }

        /* Mock Top Navigation for Estate/Branding Preview */
        .preview-mock-nav {
            background: rgba(11, 24, 16, 0.95);
            border-bottom: 1px solid rgba(197, 160, 89, 0.25);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        .preview-brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .preview-brand-emblem {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ECC880 0%, #C5A059 50%, #9D7B37 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B1810;
            font-size: 20px;
            box-shadow: 0 0 16px rgba(197, 160, 89, 0.4);
        }
        .preview-brand-titles h2 {
            font-family: var(--font-display, 'Cinzel', serif);
            font-size: 18px;
            letter-spacing: 1.5px;
            color: #FFFFFF;
            margin: 0;
            font-weight: 700;
        }
        .preview-brand-titles p {
            font-size: 11px;
            color: #C5A059;
            margin: 2px 0 0;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .preview-policy-card {
            background: rgba(20, 40, 27, 0.8);
            border: 1px solid rgba(197, 160, 89, 0.2);
            border-radius: 12px;
            padding: 24px;
            margin: 30px auto;
            max-width: 600px;
        }

        /* WhatsApp Preview Floating Bar */
        .preview-wa-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
        }
        .preview-wa-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #25D366;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.45);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .preview-wa-btn:hover {
            transform: scale(1.08);
        }
        .preview-wa-bubble {
            background: #101F15;
            color: #FFFFFF;
            border: 1px solid rgba(37, 211, 102, 0.4);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
            font-weight: 500;
        }

        /* Preview Top Weather Ticker */
        .preview-top-ticker {
            background: #08120B;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 10px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            color: #C5A059;
            letter-spacing: 0.5px;
        }

        /* Protection Banner Preview */
        .preview-shield-banner {
            background: linear-gradient(135deg, rgba(16, 31, 21, 0.95), rgba(8, 18, 11, 0.95));
            border: 1px solid #2ecc71;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 40px auto;
            max-width: 500px;
        }

        /* Rooms & 3D Walkthrough Preview Styles */
        .preview-rooms-wrap {
            padding: 24px 20px;
            max-width: 1040px;
            margin: 0 auto;
        }
        .preview-tour-box {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            background: #0A150F;
            border: 1px solid rgba(197, 160, 89, 0.35);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6);
            margin-bottom: 30px;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .preview-tour-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            filter: brightness(0.68);
            transition: opacity 0.4s ease, transform 0.6s ease;
        }
        .preview-tour-vignette {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 30%, rgba(10, 21, 15, 0.6) 75%, rgba(10, 21, 15, 0.92) 100%),
                        linear-gradient(to bottom, rgba(10, 21, 15, 0.85) 0%, transparent 28%, transparent 70%, rgba(10, 21, 15, 0.92) 100%);
            z-index: 2;
            pointer-events: none;
        }
        .preview-tour-top {
            position: relative;
            z-index: 3;
            padding: 20px 22px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }
        .preview-tour-tabs {
            display: inline-flex;
            gap: 6px;
            background: rgba(10, 21, 15, 0.88);
            border: 1px solid rgba(197, 160, 89, 0.35);
            border-radius: 30px;
            padding: 4px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        .preview-tour-tab-btn {
            background: transparent;
            border: 1px solid transparent;
            color: rgba(248, 246, 240, 0.75);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11.5px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: all 0.25s ease;
            font-family: var(--font-sans);
        }
        .preview-tour-tab-btn:hover {
            color: #FFFFFF;
            background: rgba(197, 160, 89, 0.15);
        }
        .preview-tour-tab-btn.active {
            background: linear-gradient(135deg, rgba(197, 160, 89, 0.35), rgba(139, 107, 44, 0.5));
            border-color: #C5A059;
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(197, 160, 89, 0.3);
        }
        .preview-tour-bottom {
            position: relative;
            z-index: 3;
            padding: 20px 22px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }
        .preview-stage-box {
            background: rgba(12, 24, 17, 0.92);
            border: 1px solid rgba(197, 160, 89, 0.4);
            border-radius: 10px;
            padding: 16px 20px;
            max-width: 480px;
            backdrop-filter: blur(16px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.55);
        }
        .preview-rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 14px;
        }
        .preview-chalet-card {
            background: rgba(16, 31, 21, 0.85);
            border: 1px solid rgba(197, 160, 89, 0.25);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.25s, border-color 0.25s;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            display: flex;
            flex-direction: column;
        }
        .preview-chalet-card:hover {
            border-color: #C5A059;
            transform: translateY(-2px);
        }
        .preview-chalet-thumb {
            width: 100%;
            height: 175px;
            object-fit: cover;
            display: block;
        }
        .preview-chalet-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
        }
    </style>
</head>
<body class="preview-mode">

<div class="preview-wrapper" id="preview-wrapper">

    <?php if ($section === 'estate'): ?>
        <!-- CARD 01: ESTATE BRANDING & POLICIES PREVIEW -->
        <div class="preview-mock-nav">
            <div class="preview-brand-logo">
                <div class="preview-brand-emblem"><i class="fa-solid fa-seedling"></i></div>
                <div class="preview-brand-titles">
                    <h2 id="pv-estate-name"><?php echo htmlspecialchars($estate_name); ?></h2>
                    <p id="pv-estate-tagline"><?php echo htmlspecialchars($estate_tagline); ?></p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <span style="font-size: 12px; color: #C5A059; background: rgba(197, 160, 89, 0.15); padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(197, 160, 89, 0.3);">
                    Currency: <strong id="pv-currency" style="font-size: 14px; color: #FFF;"><?php echo htmlspecialchars($currency_symbol); ?></strong>
                </span>
                <span class="btn-luxury" style="padding: 8px 18px; font-size: 12px; border-radius: 6px; background: #C5A059; color: #0B1810; font-weight: 700;">RESERVE</span>
            </div>
        </div>

        <div style="padding: 30px 20px;">
            <div class="preview-policy-card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                    <i class="fa-solid fa-clock" style="color: #C5A059; font-size: 20px;"></i>
                    <h3 style="font-family: var(--font-display); color: #FFF; font-size: 16px; margin: 0;">Reservation Schedule & Currency</h3>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px;">
                    <div style="background: rgba(11, 24, 16, 0.7); padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 11px; color: #839788; text-transform: uppercase; display: block;">Check-in Window</span>
                        <strong id="pv-checkin" style="font-size: 16px; color: #FFF; display: block; margin-top: 4px;"><?php echo htmlspecialchars($checkin_time); ?></strong>
                    </div>
                    <div style="background: rgba(11, 24, 16, 0.7); padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 11px; color: #839788; text-transform: uppercase; display: block;">Check-out Window</span>
                        <strong id="pv-checkout" style="font-size: 16px; color: #FFF; display: block; margin-top: 4px;"><?php echo htmlspecialchars($checkout_time); ?></strong>
                    </div>
                </div>
                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 12px; color: #A0B2A6;">
                    <i class="fa-solid fa-circle-info" style="color: #C5A059; margin-right: 6px;"></i> Rates and confirmation receipts will be billed in <strong id="pv-currency-note" style="color: #C5A059;"><?php echo htmlspecialchars($currency_symbol); ?> (INR/Specified Currency)</strong>.
                </div>
            </div>
        </div>

    <?php elseif ($section === 'whatsapp'): ?>
        <!-- CARD 02: WHATSAPP & CONCIERGE PREVIEW -->
        <div style="padding: 40px 24px; max-width: 600px; margin: 0 auto;">
            <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(37, 211, 102, 0.3); border-radius: 16px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: #25D366; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #FFF;">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div>
                        <span style="font-size: 10px; color: #25D366; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">Direct Line Concierge</span>
                        <h3 style="font-family: var(--font-display); font-size: 18px; color: #FFF; margin: 2px 0 0;">24/7 Estate Communication</h3>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 20px;">
                    <div style="background: rgba(11, 24, 16, 0.8); padding: 14px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 11px; color: #839788; display: block;">WhatsApp Direct Number</span>
                            <strong id="pv-whatsapp-num" style="font-size: 15px; color: #25D366;"><?php echo htmlspecialchars($concierge_whatsapp); ?></strong>
                        </div>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $concierge_whatsapp); ?>" target="_blank" style="padding: 6px 14px; background: #25D366; color: #FFF; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                            <i class="fa-brands fa-whatsapp"></i> Chat Now
                        </a>
                    </div>

                    <div style="background: rgba(11, 24, 16, 0.8); padding: 14px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 11px; color: #839788; display: block;">Telephone Line</span>
                        <strong id="pv-phone-num" style="font-size: 14px; color: #FFF;"><?php echo htmlspecialchars($concierge_phone); ?></strong>
                    </div>

                    <div style="background: rgba(11, 24, 16, 0.8); padding: 14px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 11px; color: #839788; display: block;">Concierge Email</span>
                        <strong id="pv-email-val" style="font-size: 14px; color: #C5A059;"><?php echo htmlspecialchars($concierge_email); ?></strong>
                    </div>

                    <div style="background: rgba(11, 24, 16, 0.8); padding: 14px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 11px; color: #839788; display: block;">Estate Physical Location</span>
                        <strong id="pv-location-val" style="font-size: 13px; color: #FFF;"><?php echo htmlspecialchars($location); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Live Widget -->
        <div class="preview-wa-widget">
            <div class="preview-wa-bubble" id="pv-wa-bubble">Need assistance? Chat with Concierge</div>
            <div class="preview-wa-btn"><i class="fa-brands fa-whatsapp"></i></div>
        </div>

    <?php elseif ($section === 'hero'): ?>
        <!-- CARD 03: HERO MARQUEE & VISUAL PREVIEW -->
        <?php
        $hero_eyebrow = $s['hero_eyebrow'] ?? 'KANTHALLOOR, KERALA • PRIVATE ECO-SANCTUARY';
        $hero_title = $s['hero_title'] ?? 'Where Earth Breathes & Time Stands Still.';
        $hero_desc = $s['hero_desc'] ?? 'Tucked deep in the misty hills and organic orchards of Kanthalloor. Experience private earthen mudhouses, soaring canopy treehouses, and nourishing farm gastronomy cooked slowly over wood fires.';
        $hero_bg_image = $s['hero_bg_image'] ?? 'assets/images/01 (25).jpeg';
        if (!empty($hero_bg_image) && strpos($hero_bg_image, 'http') !== 0 && false && strpos($hero_bg_image, '/') !== 0) {
            
        }
        ?>
        <section id="hero" class="hero-section" style="min-height: 85vh; display: flex; align-items: center; justify-content: center; position: relative; padding: 60px 20px;">
            <div class="hero-bg-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 1;">
                <img id="pv-hero-bg" src="<?php echo htmlspecialchars($hero_bg_image); ?>" alt="Hero Backdrop" class="hero-bg-img loaded" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.65);" onerror="this.src='assets/images/01 (25).jpeg'">
                <div class="hero-overlay" style="position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(180deg, rgba(11,24,16,0.3) 0%, rgba(11,24,16,0.85) 100%);"></div>
            </div>

            <div class="hero-content" style="position: relative; z-index: 2; text-align: center; max-width: 850px; margin: 0 auto; padding: 20px;">
                <div class="hero-eyebrow-pill font-sans" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(197, 160, 89, 0.2); border: 1px solid rgba(197, 160, 89, 0.4); padding: 6px 18px; border-radius: 30px; margin-bottom: 20px;">
                    <span class="hero-pill-bullet" style="width: 6px; height: 6px; border-radius: 50%; background: #C5A059;"></span>
                    <span class="hero-pill-text" id="pv-hero-eyebrow" style="font-size: 11px; letter-spacing: 1.5px; font-weight: 700; color: #FFF; text-transform: uppercase;"><?php echo htmlspecialchars($hero_eyebrow); ?></span>
                </div>

                <div class="hero-title-wrapper" style="margin-bottom: 20px;">
                    <h1 class="hero-title font-serif split-text" id="pv-hero-title" style="font-family: var(--font-serif); font-size: 38px; line-height: 1.25; color: #FFFFFF; font-weight: 400; text-shadow: 0 4px 20px rgba(0,0,0,0.6);">
                        <?php echo nl2br(htmlspecialchars($hero_title)); ?>
                    </h1>
                </div>

                <p class="hero-desc font-sans" id="pv-hero-desc" style="font-size: 15px; line-height: 1.7; color: #CBD5E1; max-width: 680px; margin: 0 auto 30px;">
                    <?php echo htmlspecialchars($hero_desc); ?>
                </p>

                <div style="display: flex; align-items: center; justify-content: center; gap: 16px; flex-wrap: wrap;">
                    <button type="button" class="btn-luxury" style="padding: 14px 32px; background: #C5A059; color: #0B1810; font-weight: 700; border-radius: 30px; border: none; font-size: 13px; letter-spacing: 1px; cursor: pointer; box-shadow: 0 4px 20px rgba(197, 160, 89, 0.4);">
                        EXPLORE VILLAS & COTTAGES
                    </button>
                    <button type="button" class="btn-luxury-outline" style="padding: 14px 28px; background: rgba(16, 31, 21, 0.7); color: #FFF; border: 1px solid rgba(255,255,255,0.3); border-radius: 30px; font-size: 13px; cursor: pointer;">
                        VIEW 360° TOUR
                    </button>
                </div>
            </div>
        </section>

    <?php elseif ($section === 'climate'): ?>
        <!-- CARD 04: CLIMATE & ACCOLADES PREVIEW -->
        <?php
        $top_bar_location = $s['top_bar_location'] ?? 'KANTHALLOOR, MUNNAR HIGH RANGES • 5,100 FT';
        $top_bar_accolade = $s['top_bar_accolade'] ?? 'BEST SUSTAINABLE ECO-RETREAT 2026';
        $hero_tag_climate = $s['hero_tag_climate'] ?? '18°C MIST & MOUNTAIN BREEZE';
        ?>
        <div class="preview-top-ticker">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-location-dot" style="color: #C5A059;"></i>
                <span id="pv-climate-loc"><?php echo htmlspecialchars($top_bar_location); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <span style="color: #2ecc71;" id="pv-climate-weather"><i class="fa-solid fa-cloud-sun"></i> <?php echo htmlspecialchars($hero_tag_climate); ?></span>
                <span style="color: #C5A059;" id="pv-climate-accolade"><i class="fa-solid fa-award"></i> <?php echo htmlspecialchars($top_bar_accolade); ?></span>
            </div>
        </div>

        <div style="padding: 40px 20px; text-align: center; max-width: 650px; margin: 0 auto;">
            <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 16px; padding: 30px;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #C5A059; font-size: 26px;">
                    <i class="fa-solid fa-mountain-sun"></i>
                </div>
                <h3 style="font-family: var(--font-display); color: #FFF; font-size: 20px; margin: 0 0 10px;">High Ranges Microclimate & Accolades</h3>
                <p style="font-size: 13px; color: #CBD5E1; line-height: 1.6;">Displayed permanently in the top header banner and weather widgets across all website pages.</p>
            </div>
        </div>

    <?php elseif ($section === 'philosophy'): ?>
        <!-- CARD 03: PHILOSOPHY & ETHOS PREVIEW -->
        <?php
        $welcome_badge = $s['welcome_badge'] ?? 'THE SANCTUARY PHILOSOPHY';
        $welcome_title = $s['welcome_title'] ?? 'Rooted in Earth, Reverence & Time';
        $welcome_paragraph = $s['welcome_paragraph'] ?? 'Food Forest is not merely a getaway; it is a conscious return to living in harmony with nature. Tucked into the mist-veiled terraced hills of Kanthalloor, Kerala, our estate was conceived as a living ecosystem where luxury means silence, pure mountain spring water, and unhurried peace.';
        $welcome_image = $s['welcome_image'] ?? 'assets/images/01 (7).jpeg';
        if (!empty($welcome_image) && strpos($welcome_image, 'http') !== 0 && false && strpos($welcome_image, '/') !== 0) {
            
        }

        $feat1_t = $s['welcome_feat1_title'] ?? 'Ecological Vernacular';
        $feat1_d = $s['welcome_feat1_desc'] ?? 'Earthen clay, raw stone, reclaimed teak, and zero plastic across the retreat.';
        $feat2_t = $s['welcome_feat2_title'] ?? 'Pure Farm-to-Table';
        $feat2_d = $s['welcome_feat2_desc'] ?? 'Organic chemical-free orchards. Meals harvested minutes before cooking over earthen wood fires.';
        $feat3_t = $s['welcome_feat3_title'] ?? 'High-Range Climate';
        $feat3_d = $s['welcome_feat3_desc'] ?? 'Situated at 1,600m altitude. Chilly night mists, crisp mountain breeze, and clear skies.';
        $feat4_t = $s['welcome_feat4_title'] ?? 'Intimate & Private';
        $feat4_d = $s['welcome_feat4_desc'] ?? 'Exclusive living stay concepts nestled among organic orchards to guarantee absolute privacy and silence.';
        ?>
        <section style="padding: 50px 24px; max-width: 1040px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 40px; align-items: flex-start; margin-bottom: 36px;">
                <div>
                    <span id="pv-wel-badge" style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase; display: block; margin-bottom: 12px;"><?php echo htmlspecialchars($welcome_badge); ?></span>
                    <h2 id="pv-wel-title" style="font-family: var(--font-serif); font-size: 30px; color: #FFFFFF; font-weight: 400; line-height: 1.3; margin: 0 0 18px;"><?php echo htmlspecialchars($welcome_title); ?></h2>
                    <p id="pv-wel-desc" style="font-size: 14px; line-height: 1.8; color: #CBD5E1; margin: 0;"><?php echo nl2br(htmlspecialchars($welcome_paragraph)); ?></p>
                </div>
                <div style="position: relative;">
                    <img id="pv-wel-img" src="<?php echo htmlspecialchars($welcome_image); ?>" alt="Sanctuary Portrait" style="width: 100%; height: 320px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(197, 160, 89, 0.3); box-shadow: 0 16px 40px rgba(0,0,0,0.5);" onerror="this.src='assets/images/mudhouse_exterior.png'">
                </div>
            </div>

            <!-- 4 Philosophy & Ecological Pillars Preview -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 18px; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(46, 204, 113, 0.15); color: #2ecc71; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;"><i class="fa-solid fa-leaf"></i></div>
                    <div>
                        <h4 id="pv-wel-f1-t" style="font-family: var(--font-serif); font-size: 15px; color: #FFF; margin: 0 0 6px; font-weight: 600;"><?php echo htmlspecialchars($feat1_t); ?></h4>
                        <p id="pv-wel-f1-d" style="font-size: 12px; color: #CBD5E1; margin: 0; line-height: 1.6;"><?php echo htmlspecialchars($feat1_d); ?></p>
                    </div>
                </div>

                <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 18px; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); color: #C5A059; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;"><i class="fa-solid fa-seedling"></i></div>
                    <div>
                        <h4 id="pv-wel-f2-t" style="font-family: var(--font-serif); font-size: 15px; color: #FFF; margin: 0 0 6px; font-weight: 600;"><?php echo htmlspecialchars($feat2_t); ?></h4>
                        <p id="pv-wel-f2-d" style="font-size: 12px; color: #CBD5E1; margin: 0; line-height: 1.6;"><?php echo htmlspecialchars($feat2_d); ?></p>
                    </div>
                </div>

                <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 18px; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(56, 189, 248, 0.15); color: #38bdf8; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;"><i class="fa-solid fa-wind"></i></div>
                    <div>
                        <h4 id="pv-wel-f3-t" style="font-family: var(--font-serif); font-size: 15px; color: #FFF; margin: 0 0 6px; font-weight: 600;"><?php echo htmlspecialchars($feat3_t); ?></h4>
                        <p id="pv-wel-f3-d" style="font-size: 12px; color: #CBD5E1; margin: 0; line-height: 1.6;"><?php echo htmlspecialchars($feat3_d); ?></p>
                    </div>
                </div>

                <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 18px; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(244, 63, 94, 0.15); color: #f43f5e; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;"><i class="fa-solid fa-shield-heart"></i></div>
                    <div>
                        <h4 id="pv-wel-f4-t" style="font-family: var(--font-serif); font-size: 15px; color: #FFF; margin: 0 0 6px; font-weight: 600;"><?php echo htmlspecialchars($feat4_t); ?></h4>
                        <p id="pv-wel-f4-d" style="font-size: 12px; color: #CBD5E1; margin: 0; line-height: 1.6;"><?php echo htmlspecialchars($feat4_d); ?></p>
                    </div>
                </div>
            </div>
        </section>

    <?php elseif ($section === 'why'): ?>
        <!-- CARD 07: WHY FOOD FOREST (EXACT PUBLIC FRONTEND COMPONENT) -->
        <div style="padding: 10px 0;">
            <?php include __DIR__ . '/../components/why_mudhouse.php'; ?>
        </div>

    <?php elseif ($section === 'experiences'): ?>
        <!-- CARD 05: CURATED EXPERIENCES (EXACT PUBLIC FRONTEND COMPONENT) -->
        <div style="padding: 10px 0;">
            <?php include __DIR__ . '/../components/experiences.php'; ?>
        </div>

    <?php elseif ($section === 'menu' || $section === 'dining'): ?>
        <!-- CARD 06: LIVING FOOD MENU & DINING (EXACT PUBLIC FRONTEND COMPONENT) -->
        <div style="padding: 10px 0;">
            <?php include __DIR__ . '/../components/dining.php'; ?>
        </div>

    <?php elseif ($section === 'seasons'): ?>
        <!-- CARD 09: SEASONS OF KANTHALLOOR (EXACT PUBLIC FRONTEND COMPONENT) -->
        <div style="padding: 10px 0;">
            <?php include __DIR__ . '/../components/seasons.php'; ?>
        </div>

    <?php elseif ($section === 'sanctuary_map'): ?>
        <!-- CARD 08: SANCTUARY ESTATE MAP (EXACT PUBLIC FRONTEND COMPONENT) -->
        <div style="padding: 10px 0;">
            <?php include __DIR__ . '/../components/sanctuary_map.php'; ?>
        </div>

    <?php elseif ($section === 'rooms'): ?>
        <!-- CARD 04: VILLAS, 3D WALKTHROUGH & ACCOMMODATIONS LIVE PREVIEW -->
        <?php
        $rooms_badge = $s['rooms_badge'] ?? 'Sanctuary Accommodations';
        $rooms_title = $s['rooms_title'] ?? 'The Canopy Treehouse & The Earthen Mudhouse';
        $rooms_desc = $s['rooms_desc'] ?? 'Experience our full-screen 3D architectural walkthrough. Hover and rotate through 360-degree panoramas of our timber treehouse and handcrafted mudhouse.';
        $all_rooms = get_all_rooms();
        
        $th = null;
        $mh = null;
        foreach ($all_rooms as $r) {
            $st = $r['stay_type'] ?? '';
            $sl = $r['slug'] ?? '';
            $ti = $r['title'] ?? '';
            if (!$th && ($st === 'treehouse' || stripos($sl, 'tree') !== false || stripos($ti, 'tree') !== false)) $th = $r;
            if (!$mh && ($st === 'mudhouse' || stripos($sl, 'mud') !== false || stripos($ti, 'mud') !== false)) $mh = $r;
        }
        if (!$th && !empty($all_rooms)) $th = $all_rooms[0];
        if (!$mh && !empty($all_rooms)) $mh = $all_rooms[count($all_rooms)-1];
        
        $def_img_th = (!empty($th['image_url'])) ? (str_starts_with($th['image_url'], 'http') ? $th['image_url'] : $th['image_url']) : 'assets/images/treehouse_exterior.png';
        $def_img_mh = (!empty($mh['image_url'])) ? (str_starts_with($mh['image_url'], 'http') ? $mh['image_url'] : $mh['image_url']) : 'assets/images/mudhouse_exterior.png';
        $active_initial = $mh ?: $th;
        $active_is_mh = ($active_initial === $mh);
        ?>
        <div class="preview-rooms-wrap">
            <!-- Section Header -->
            <div style="text-align: center; margin-bottom: 24px;">
                <span id="pv-rooms-badge" style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase; display: block; margin-bottom: 8px;"><?php echo htmlspecialchars($rooms_badge); ?></span>
                <h2 id="pv-rooms-title" style="font-family: var(--font-serif); font-size: 26px; color: #FFFFFF; font-weight: 400; margin: 0 0 10px;"><?php echo htmlspecialchars($rooms_title); ?></h2>
                <p id="pv-rooms-desc" style="font-size: 13px; line-height: 1.6; color: #CBD5E1; max-width: 650px; margin: 0 auto;"><?php echo htmlspecialchars($rooms_desc); ?></p>
            </div>

            <!-- 3D Walkthrough Interactive Box -->
            <div class="preview-tour-box" id="pv-tour-box">
                <img id="pv-tour-backdrop" src="<?php echo htmlspecialchars($active_is_mh ? $def_img_mh : $def_img_th); ?>" alt="Walkthrough Backdrop" class="preview-tour-bg" onerror="this.src='assets/images/mudhouse_exterior.png'">
                <div class="preview-tour-vignette"></div>

                <!-- Top Controls -->
                <div class="preview-tour-top">
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(197, 160, 89, 0.2); border: 1px solid rgba(197, 160, 89, 0.4); padding: 4px 10px; border-radius: 20px; margin-bottom: 6px;">
                            <span style="width: 5px; height: 5px; border-radius: 50%; background: #2ecc71;"></span>
                            <span style="font-size: 9.5px; font-weight: 700; color: #FFFFFF; letter-spacing: 1px;">360° CINEMATIC WALKTHROUGH</span>
                        </div>
                        <h3 id="pv-active-tour-title" style="font-family: var(--font-serif); font-size: 20px; color: #FFFFFF; margin: 0; font-weight: 400;">
                            <?php echo htmlspecialchars($active_initial['title'] ?? 'The Earthen Mudhouse'); ?>
                        </h3>
                    </div>

                    <!-- Stay Concept Tabs -->
                    <div class="preview-tour-tabs">
                        <button type="button" class="preview-tour-tab-btn <?php echo !$active_is_mh ? 'active' : ''; ?>" id="pv-tab-th" onclick="switchPreviewStay('treehouse');">
                            <span style="font-size: 8.5px; letter-spacing: 1px; color: #C5A059; text-transform: uppercase;"><i class="fa-solid fa-tree"></i> <?php echo htmlspecialchars($th['elevation'] ?? '30FT ELEVATION'); ?></span>
                            <span style="font-size: 11px; font-weight: 600;"><?php echo htmlspecialchars($th['title'] ?? 'The Canopy Treehouse'); ?></span>
                        </button>
                        <button type="button" class="preview-tour-tab-btn <?php echo $active_is_mh ? 'active' : ''; ?>" id="pv-tab-mh" onclick="switchPreviewStay('mudhouse');">
                            <span style="font-size: 8.5px; letter-spacing: 1px; color: #C5A059; text-transform: uppercase;"><i class="fa-solid fa-house-chimney"></i> <?php echo htmlspecialchars($mh['elevation'] ?? '1,600M HIGH RIDGE'); ?></span>
                            <span style="font-size: 11px; font-weight: 600;"><?php echo htmlspecialchars($mh['title'] ?? 'The Earthen Mudhouse'); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Bottom Floating Stage Card -->
                <div class="preview-tour-bottom">
                    <div class="preview-stage-box">
                        <span id="pv-active-tour-elev" style="display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; color: #C5A059; background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.3); padding: 3px 10px; border-radius: 20px; text-transform: uppercase; margin-bottom: 8px;">
                            <i class="fa-solid fa-mountain"></i> <?php echo htmlspecialchars($active_initial['elevation'] ?? '1,600M HIGH RIDGE'); ?>
                        </span>
                        <h4 id="pv-active-stage-title" style="font-family: var(--font-serif); font-size: 16px; color: #FFFFFF; margin: 0 0 6px; font-weight: 600;">Front Exterior & Sanctuary Architecture</h4>
                        <p id="pv-active-tour-desc" style="font-size: 12px; color: #CBD5E1; line-height: 1.6; margin: 0 0 12px;">
                            <?php echo htmlspecialchars($active_initial['description'] ?? $rooms_desc); ?>
                        </p>
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: #C5A059; background: rgba(11,24,16,0.8); border: 1px solid rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 6px;">
                                <i class="fa-solid fa-arrows-up-down-left-right"></i> Drag to rotate 360°
                            </span>
                            <span style="font-size: 11px; color: #2ecc71; font-weight: 600;">
                                <i class="fa-solid fa-circle-check"></i> High-Resolution PANO Active
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn-luxury" style="padding: 8px 18px; font-size: 12px; background: #C5A059; color: #0B1810; font-weight: 700; border-radius: 6px; border: none; cursor: pointer;">
                            <i class="fa-solid fa-calendar-check"></i> RESERVE SUITE
                        </button>
                    </div>
                </div>
            </div>

            <!-- Architectural Suites & Nightly Rates Cards -->
            <div style="margin-top: 36px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-bed" style="color: #C5A059; font-size: 16px;"></i>
                        <h3 style="font-family: var(--font-serif); font-size: 18px; color: #FFFFFF; margin: 0;">Architectural Suites & Nightly Rates (<?php echo count($all_rooms); ?>)</h3>
                    </div>
                    <span style="font-size: 11px; color: #839788;">Live Tariff & Room Inventory</span>
                </div>

                <div class="preview-rooms-grid">
                    <?php foreach ($all_rooms as $r): 
                        $r_img = (!empty($r['image_url'])) ? (str_starts_with($r['image_url'], 'http') ? $r['image_url'] : $r['image_url']) : 'assets/images/mudhouse_exterior.png';
                        $r_st = $r['stay_type'] ?? 'treehouse';
                        $r_struct = $r['structure_type'] ?? 'single_hut';
                    ?>
                        <div class="preview-chalet-card" id="pv-room-card-<?php echo $r['id']; ?>">
                            <div style="position: relative;">
                                <img src="<?php echo htmlspecialchars($r_img); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" class="preview-chalet-thumb" onerror="this.src='assets/images/mudhouse_exterior.png'">
                                <div style="position: absolute; top: 10px; left: 10px; display: flex; gap: 5px; flex-wrap: wrap;">
                                    <span style="font-size: 9.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; <?php echo $r_st === 'mudhouse' ? 'background: rgba(234, 88, 12, 0.85); color: #FFF;' : 'background: rgba(46, 204, 113, 0.85); color: #FFF;'; ?>">
                                        <?php echo $r_st === 'mudhouse' ? '🌿 MUDHOUSE' : '🌲 TREEHOUSE'; ?>
                                    </span>
                                    <span style="font-size: 9.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; <?php echo $r_struct === 'duplex_hut' ? 'background: rgba(168, 85, 247, 0.85); color: #FFF;' : 'background: rgba(59, 130, 246, 0.85); color: #FFF;'; ?>">
                                        <?php echo $r_struct === 'duplex_hut' ? '🏘️ DUPLEX' : '🏡 SINGLE'; ?>
                                    </span>
                                </div>
                                <div style="position: absolute; bottom: 10px; right: 10px; background: rgba(11,24,16,0.9); border: 1px solid rgba(197,160,89,0.5); padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; color: #DFC694;">
                                    ₹<?php echo number_format((float)$r['rate_per_night'], 0); ?>/nt
                                </div>
                            </div>
                            <div class="preview-chalet-body">
                                <div>
                                    <span style="font-size: 10.5px; color: #C5A059; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 2px;">
                                        <?php echo htmlspecialchars($r['elevation'] ?? '1,600m High Ridge'); ?>
                                    </span>
                                    <h4 style="font-family: var(--font-serif); font-size: 16px; color: #FFFFFF; margin: 0 0 6px; font-weight: 600;">
                                        <?php echo htmlspecialchars($r['title'] ?? 'Villa Suite'); ?>
                                    </h4>
                                    <p style="font-size: 11.5px; color: #A0B2A6; line-height: 1.5; margin: 0 0 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($r['description'] ?? ''); ?>
                                    </p>
                                </div>
                                <div style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #839788;">
                                    <span><i class="fa-solid fa-users" style="color: #C5A059;"></i> Up to <?php echo (int)($r['max_guests'] ?? 4); ?> Guests</span>
                                    <span style="color: #2ecc71; font-weight: 600;"><i class="fa-solid fa-check"></i> Available</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        window.previewStayData = {
            treehouse: {
                title: <?php echo json_encode($th['title'] ?? 'The Canopy Treehouse'); ?>,
                elevation: <?php echo json_encode($th['elevation'] ?? '30FT ELEVATION'); ?>,
                image: <?php echo json_encode($def_img_th); ?>,
                desc: <?php echo json_encode($th['description'] ?? 'Suspended 30 feet above the forest floor within ancient trees.'); ?>
            },
            mudhouse: {
                title: <?php echo json_encode($mh['title'] ?? 'The Earthen Mudhouse'); ?>,
                elevation: <?php echo json_encode($mh['elevation'] ?? '1,600M HIGH RIDGE'); ?>,
                image: <?php echo json_encode($def_img_mh); ?>,
                desc: <?php echo json_encode($mh['description'] ?? 'Handcrafted from organic mountain clay, river stone, and local terracotta.'); ?>
            }
        };

        function switchPreviewStay(stayKey) {
            var data = window.previewStayData[stayKey];
            if (!data) return;
            var bg = document.getElementById('pv-tour-backdrop');
            if (bg) {
                bg.style.opacity = '0';
                setTimeout(function() {
                    bg.src = resolvePreviewImgSrc(data.image);
                    bg.style.opacity = '1';
                }, 200);
            }
            var t = document.getElementById('pv-active-tour-title');
            if (t) t.textContent = data.title;
            var e = document.getElementById('pv-active-tour-elev');
            if (e) e.innerHTML = '<i class="fa-solid fa-mountain"></i> ' + data.elevation;
            var d = document.getElementById('pv-active-tour-desc');
            if (d) d.textContent = data.desc;

            var tabTh = document.getElementById('pv-tab-th');
            var tabMh = document.getElementById('pv-tab-mh');
            if (tabTh) tabTh.classList.toggle('active', stayKey === 'treehouse');
            if (tabMh) tabMh.classList.toggle('active', stayKey === 'mudhouse');
        }
        </script>

    <?php elseif ($section === 'gallery'): ?>
        <!-- CARD 10: VISUAL DIARY (EXACT PUBLIC FRONTEND COMPONENT) -->
        <div style="padding: 10px 0;">
            <?php include __DIR__ . '/../components/gallery.php'; ?>
        </div>

    <?php elseif ($section === 'testimonials'): ?>
        <!-- CARD 11: GUEST REFLECTIONS (EXACT PUBLIC FRONTEND COMPONENT) -->
        <div style="padding: 10px 0;">
            <?php include __DIR__ . '/../components/testimonials.php'; ?>
        </div>

    <?php elseif ($section === 'protection'): ?>
        <!-- CARD 14: CONTENT PROTECTION PREVIEW -->
        <div style="padding: 40px 20px;">
            <div class="preview-shield-banner">
                <i class="fa-solid fa-shield-halved" style="font-size: 48px; color: #2ecc71; margin-bottom: 16px; display: block;"></i>
                <h3 style="font-family: var(--font-display); color: #FFF; font-size: 20px; margin: 0 0 8px;">DevTools & Anti-Copy Shield Active</h3>
                <p style="font-size: 13px; color: #CBD5E1; line-height: 1.6; margin: 0;">Right-click context menu, image dragging, F12 inspector tampering, and keyboard shortcuts (Ctrl+S, Ctrl+U, Ctrl+P) are shielded on live public visitor sessions.</p>
            </div>
        </div>

    <?php elseif ($section === 'security'): ?>
        <!-- CARD 15: SECURITY PREVIEW -->
        <div style="padding: 40px 20px; max-width: 500px; margin: 0 auto;">
            <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 14px; padding: 24px; text-align: center;">
                <i class="fa-solid fa-key" style="font-size: 36px; color: #C5A059; margin-bottom: 14px;"></i>
                <p style="font-size: 12px; color: #839788; margin: 0 0 16px;">Direct master access key protection active.</p>
                <div style="background: rgba(11,24,16,0.8); padding: 10px; border-radius: 6px; font-size: 12px; color: #2ecc71;">
                    <i class="fa-solid fa-lock"></i> Master Session Authenticated
                </div>
            </div>
        </div>

    <?php elseif ($section === 'backup'): ?>
        <!-- CARD 16: BACKUP PREVIEW -->
        <?php
        $tables_count = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
        ?>
        <div style="padding: 40px 20px; max-width: 560px; margin: 0 auto;">
            <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 14px; padding: 26px; text-align: center;">
                <i class="fa-solid fa-database" style="font-size: 40px; color: #C5A059; margin-bottom: 14px;"></i>
                <h3 style="font-family: var(--font-display); color: #FFF; font-size: 18px; margin: 0 0 8px;">MySQL Database Health Center</h3>
                <p style="font-size: 12.5px; color: #CBD5E1; margin: 0 0 20px;">Live exportable SQL database with <?php echo $tables_count; ?> active relational tables.</p>
                <a href="settings.php?action=download_backup" target="_parent" class="btn-luxury" style="padding: 10px 24px; background: #C5A059; color: #0B1810; font-weight: 700; border-radius: 6px; text-decoration: none; font-size: 12.5px; display: inline-block;">
                    <i class="fa-solid fa-download"></i> Download Live SQL Dump
                </a>
            </div>
        </div>

    <?php elseif ($section === 'bank'): ?>
        <!-- CARD 17: BANK & UPI PAYMENT QR PREVIEW -->
        <?php
        $b_holder = $s['bank_account_holder'] ?? 'Food Forest Eco Sanctuary';
        $b_name = $s['bank_name'] ?? 'State Bank of India';
        $b_branch = $s['bank_branch'] ?? 'Munnar / Kanthalloor Branch';
        $b_acc = $s['bank_account_number'] ?? '40982314981';
        $b_ifsc = $s['bank_ifsc'] ?? 'SBIN0070123';
        $b_type = $s['bank_account_type'] ?? 'Current Account';
        $b_upi = $s['bank_upi_id'] ?? 'foodforest@upi';
        $b_qr = !empty($s['bank_qr_image']) ? (str_starts_with($s['bank_qr_image'], 'http') || str_starts_with($s['bank_qr_image'], 'assets/') ? '../' . $s['bank_qr_image'] : '../' . $s['bank_qr_image']) : 'assets/images/foodforest_upi_qr.svg';
        $b_gst = $s['gst_number'] ?? '32AAECF1234M1Z5';
        $b_show_bank = ($s['bill_show_bank_details'] ?? '1') === '1';
        $b_show_qr = ($s['bill_show_qr_code'] ?? '1') === '1';
        ?>
        <div style="padding: 30px 16px; max-width: 680px; margin: 0 auto;">
            <div style="background: #FFFFFF; border: 1.5px solid #C5A059; border-radius: 12px; padding: 24px; color: #101F15; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 18px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 1px; text-transform: uppercase;">OFFICIAL SETTLEMENT CHANNELS</span>
                        <h3 style="font-family: 'Cinzel', serif; font-size: 18px; margin: 2px 0 0; color: #101F15;">Food Forest Sanctuary Folio Banking</h3>
                    </div>
                    <span style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                        <i class="fa-solid fa-circle-check"></i> VERIFIED
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; align-items: start;">
                    <!-- QR Code Card -->
                    <div style="background: #F8FAF9; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px; text-align: center;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 8px;">Scan & Pay via UPI</div>
                        <div style="background: #FFF; padding: 10px; border-radius: 8px; display: inline-block; border: 1px solid #CBD5E1; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                            <img src="<?php echo htmlspecialchars($b_qr); ?>" alt="UPI QR" style="width: 140px; height: 140px; object-fit: contain; display: block;" onerror="this.src='assets/images/foodforest_upi_qr.svg';">
                        </div>
                        <div style="font-size: 12px; font-weight: 700; color: #101F15; margin-top: 10px; font-family: monospace; background: #FFF; padding: 4px 8px; border-radius: 4px; border: 1px dashed #CBD5E1;">
                            <?php echo htmlspecialchars($b_upi); ?>
                        </div>
                        <div style="font-size: 10.5px; color: #64748B; margin-top: 6px;">GPay • PhonePe • Paytm • BHIM</div>
                    </div>

                    <!-- Bank Details Table -->
                    <div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 10px;">Direct IMPS / NEFT Transfer</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 6px 0; color: #64748B;">Account Holder:</td>
                                <td style="padding: 6px 0; font-weight: 700; color: #101F15; text-align: right;"><?php echo htmlspecialchars($b_holder); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 6px 0; color: #64748B;">Bank Name:</td>
                                <td style="padding: 6px 0; font-weight: 600; color: #101F15; text-align: right;"><?php echo htmlspecialchars($b_name); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 6px 0; color: #64748B;">Account Number:</td>
                                <td style="padding: 6px 0; font-weight: 700; color: #101F15; font-family: monospace; font-size: 13px; text-align: right;"><?php echo htmlspecialchars($b_acc); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 6px 0; color: #64748B;">IFSC Code:</td>
                                <td style="padding: 6px 0; font-weight: 700; color: #101F15; font-family: monospace; text-align: right;"><?php echo htmlspecialchars($b_ifsc); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 6px 0; color: #64748B;">Account Type:</td>
                                <td style="padding: 6px 0; font-weight: 600; color: #101F15; text-align: right;"><?php echo htmlspecialchars($b_type); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <td style="padding: 6px 0; color: #64748B;">Branch:</td>
                                <td style="padding: 6px 0; font-weight: 600; color: #101F15; text-align: right;"><?php echo htmlspecialchars($b_branch); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: #64748B;">GSTIN / Tax ID:</td>
                                <td style="padding: 6px 0; font-weight: 600; color: #101F15; font-family: monospace; text-align: right;"><?php echo htmlspecialchars($b_gst); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div style="margin-top: 18px; padding-top: 12px; border-top: 1px dashed #CBD5E1; font-size: 11.5px; color: #64748B; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div><i class="fa-solid fa-print"></i> Default on Bills: <strong><?php echo $b_show_bank ? 'Bank Table (ON)' : 'Bank Table (OFF)'; ?></strong> • <strong><?php echo $b_show_qr ? 'QR Code (ON)' : 'QR Code (OFF)'; ?></strong></div>
                    <div style="color: #059669; font-weight: 600;"><i class="fa-solid fa-shield-halved"></i> 100% Secure Sanctuary Gateway</div>
                </div>
            </div>
        </div>

    <?php elseif ($section === 'footer'): ?>
        <!-- CARD 18: FOOTER & ECO TRUST PILLARS LIVE PREVIEW -->
        <div style="width: 100%; background: #07120B; min-height: 100vh;">
            <?php require_once __DIR__ . '/../includes/footer.php'; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Real-Time Two-Way Synchronization Script -->
<script>
function resolvePreviewImgSrc(path) {
    if (!path || !path.trim()) return 'assets/images/01 (25).jpeg';
    path = path.trim();
    if (/^https?:\/\//i.test(path) || /^data:/i.test(path) || /^blob:/i.test(path)) return path;
    // Strip leading ../ or / because <base href="../"> is active in the preview frame
    while (path.indexOf('../') === 0) {
        path = path.substring(3);
    }
    return path.replace(/^\/+/, '');
}

window.addEventListener('message', function(event) {
    if (!event.data) return;
    var data = event.data;

    // Direct text element update with smooth highlight
    if (data.type === 'update_text' && data.selector) {
        var el = document.querySelector(data.selector);
        if (el) {
            el.textContent = data.value;
            el.classList.add('');
            setTimeout(function() { el.classList.remove(''); }, 1200);
        }
    }

    // Direct image update
    if (data.type === 'update_image' && data.selector && data.src) {
        var img = document.querySelector(data.selector);
        if (img) {
            img.src = resolvePreviewImgSrc(data.src);
            img.classList.add('');
            setTimeout(function() { img.classList.remove(''); }, 1200);
        }
    }

    // Full form fields sync
    if (data.type === 'sync_fields' && data.fields) {
        var f = data.fields;
        
        // Estate
        if (f.estate_name) {
            var el = document.getElementById('pv-estate-name');
            if (el) el.textContent = f.estate_name;
        }
        if (f.estate_tagline) {
            var el = document.getElementById('pv-estate-tagline');
            if (el) el.textContent = f.estate_tagline;
        }
        if (f.currency_symbol) {
            var el = document.getElementById('pv-currency');
            if (el) el.textContent = f.currency_symbol;
            var el2 = document.getElementById('pv-currency-note');
            if (el2) el2.textContent = f.currency_symbol + ' (Specified Currency)';
        }
        if (f.checkin_time) {
            var el = document.getElementById('pv-checkin');
            if (el) el.textContent = f.checkin_time;
        }
        if (f.checkout_time) {
            var el = document.getElementById('pv-checkout');
            if (el) el.textContent = f.checkout_time;
        }

        // WhatsApp
        if (f.concierge_whatsapp) {
            var el = document.getElementById('pv-whatsapp-num');
            if (el) el.textContent = f.concierge_whatsapp;
        }
        if (f.concierge_phone) {
            var el = document.getElementById('pv-phone-num');
            if (el) el.textContent = f.concierge_phone;
        }
        if (f.concierge_email) {
            var el = document.getElementById('pv-email-val');
            if (el) el.textContent = f.concierge_email;
        }
        if (f.location) {
            var el = document.getElementById('pv-location-val');
            if (el) el.textContent = f.location;
        }

        // Hero
        if (f.hero_eyebrow) {
            var el = document.getElementById('pv-hero-eyebrow');
            if (el) el.textContent = f.hero_eyebrow;
        }
        if (f.hero_title) {
            var el = document.getElementById('pv-hero-title');
            if (el) el.innerHTML = f.hero_title.replace(/\n/g, '<br>');
        }
        if (f.hero_desc) {
            var el = document.getElementById('pv-hero-desc');
            if (el) el.textContent = f.hero_desc;
        }
        if (f.hero_bg_image) {
            var el = document.getElementById('pv-hero-bg');
            if (el) el.src = resolvePreviewImgSrc(f.hero_bg_image);
        }

        // Climate
        if (f.top_bar_location) {
            var el = document.getElementById('pv-climate-loc');
            if (el) el.textContent = f.top_bar_location;
        }
        if (f.hero_tag_climate) {
            var el = document.getElementById('pv-climate-weather');
            if (el) el.innerHTML = '<i class="fa-solid fa-cloud-sun"></i> ' + f.hero_tag_climate;
        }
        if (f.top_bar_accolade) {
            var el = document.getElementById('pv-climate-accolade');
            if (el) el.innerHTML = '<i class="fa-solid fa-award"></i> ' + f.top_bar_accolade;
        }

        // Philosophy
        if (f.welcome_badge) {
            var el = document.getElementById('pv-wel-badge');
            if (el) el.textContent = f.welcome_badge;
        }
        if (f.welcome_title) {
            var el = document.getElementById('pv-wel-title');
            if (el) el.textContent = f.welcome_title;
        }
        if (f.welcome_paragraph) {
            var el = document.getElementById('pv-wel-desc');
            if (el) el.innerHTML = f.welcome_paragraph.replace(/\n/g, '<br>');
        }
        if (f.welcome_image) {
            var el = document.getElementById('pv-wel-img');
            if (el) el.src = resolvePreviewImgSrc(f.welcome_image);
        }
        if (f.welcome_feat1_title) {
            var el = document.getElementById('pv-wel-f1-t');
            if (el) el.textContent = f.welcome_feat1_title;
        }
        if (f.welcome_feat1_desc) {
            var el = document.getElementById('pv-wel-f1-d');
            if (el) el.textContent = f.welcome_feat1_desc;
        }
        if (f.welcome_feat2_title) {
            var el = document.getElementById('pv-wel-f2-t');
            if (el) el.textContent = f.welcome_feat2_title;
        }
        if (f.welcome_feat2_desc) {
            var el = document.getElementById('pv-wel-f2-d');
            if (el) el.textContent = f.welcome_feat2_desc;
        }
        if (f.welcome_feat3_title) {
            var el = document.getElementById('pv-wel-f3-t');
            if (el) el.textContent = f.welcome_feat3_title;
        }
        if (f.welcome_feat3_desc) {
            var el = document.getElementById('pv-wel-f3-d');
            if (el) el.textContent = f.welcome_feat3_desc;
        }
        if (f.welcome_feat4_title) {
            var el = document.getElementById('pv-wel-f4-t');
            if (el) el.textContent = f.welcome_feat4_title;
        }
        if (f.welcome_feat4_desc) {
            var el = document.getElementById('pv-wel-f4-d');
            if (el) el.textContent = f.welcome_feat4_desc;
        }

        // Why
        if (f.why_badge) {
            var el = document.getElementById('pv-why-badge');
            if (el) el.textContent = f.why_badge;
        }
        if (f.why_title) {
            var el = document.getElementById('pv-why-title');
            if (el) el.textContent = f.why_title;
        }
        if (f.why_desc) {
            var el = document.getElementById('pv-why-desc');
            if (el) el.textContent = f.why_desc;
        }
        if (f.why_image) {
            var el = document.getElementById('pv-why-img');
            if (el) el.src = resolvePreviewImgSrc(f.why_image);
        }

        // Rooms & 3D Walkthrough
        if (f.rooms_badge) {
            var el = document.getElementById('pv-rooms-badge');
            if (el) el.textContent = f.rooms_badge;
        }
        if (f.rooms_title) {
            var el = document.getElementById('pv-rooms-title');
            if (el) el.textContent = f.rooms_title;
        }
        if (f.rooms_desc) {
            var el = document.getElementById('pv-rooms-desc');
            if (el) el.textContent = f.rooms_desc;
            var elTourDesc = document.getElementById('pv-active-tour-desc');
            if (elTourDesc) elTourDesc.textContent = f.rooms_desc;
        }
    }
});
</script>

</body>
</html>
