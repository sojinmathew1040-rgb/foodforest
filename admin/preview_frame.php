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
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">

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

        .preview-highlight-pulse {
            animation: previewGlow 1.2s ease-out;
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
        $hero_bg_image = $s['hero_bg_image'] ?? '../assets/images/01 (25).jpeg';
        if (!empty($hero_bg_image) && strpos($hero_bg_image, 'http') !== 0 && strpos($hero_bg_image, '../') !== 0 && strpos($hero_bg_image, '/') !== 0) {
            $hero_bg_image = '../' . $hero_bg_image;
        }
        ?>
        <section id="hero" class="hero-section" style="min-height: 85vh; display: flex; align-items: center; justify-content: center; position: relative; padding: 60px 20px;">
            <div class="hero-bg-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 1;">
                <img id="pv-hero-bg" src="<?php echo htmlspecialchars($hero_bg_image); ?>" alt="Hero Backdrop" class="hero-bg-img loaded" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.65);" onerror="this.src='../assets/images/treehouse_exterior.png'">
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
        <!-- CARD 05: PHILOSOPHY & ETHOS PREVIEW -->
        <?php
        $welcome_badge = $s['welcome_badge'] ?? 'SANCTUARY ETHOS';
        $welcome_title = $s['welcome_title'] ?? 'Rooted in Earth. Nurtured by Harmony.';
        $welcome_paragraph = $s['welcome_paragraph'] ?? 'Food Forest Sanctuary is born from a reverence for natural ecosystems. Here, living soil meets vernacular cob architecture and ancestral permaculture wisdom.';
        $welcome_image = $s['welcome_image'] ?? '../assets/images/portrait_farm.jpg';
        if (!empty($welcome_image) && strpos($welcome_image, 'http') !== 0 && strpos($welcome_image, '../') !== 0 && strpos($welcome_image, '/') !== 0) {
            $welcome_image = '../' . $welcome_image;
        }
        ?>
        <section style="padding: 50px 24px; max-width: 1000px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
                <div>
                    <span id="pv-wel-badge" style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase; display: block; margin-bottom: 12px;"><?php echo htmlspecialchars($welcome_badge); ?></span>
                    <h2 id="pv-wel-title" style="font-family: var(--font-serif); font-size: 32px; color: #FFFFFF; font-weight: 400; line-height: 1.3; margin: 0 0 20px;"><?php echo htmlspecialchars($welcome_title); ?></h2>
                    <p id="pv-wel-desc" style="font-size: 14px; line-height: 1.8; color: #CBD5E1; margin: 0 0 20px;"><?php echo nl2br(htmlspecialchars($welcome_paragraph)); ?></p>
                </div>
                <div style="position: relative;">
                    <img id="pv-wel-img" src="<?php echo htmlspecialchars($welcome_image); ?>" alt="Sanctuary Portrait" style="width: 100%; height: 360px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(197, 160, 89, 0.3); box-shadow: 0 16px 40px rgba(0,0,0,0.5);" onerror="this.src='../assets/images/treehouse_exterior.png'">
                </div>
            </div>
        </section>

    <?php elseif ($section === 'why'): ?>
        <!-- CARD 06: WHY FOOD FOREST PREVIEW -->
        <?php
        $why_badge = $s['why_badge'] ?? 'LIVING ARCHITECTURE';
        $why_title = $s['why_title'] ?? 'Why Choose an Earthen Mudhouse & Forest Farmstay?';
        $why_desc = $s['why_desc'] ?? 'Hand-sculpted with cob clay, straw, and stone, our mudhouses breathe naturally with the mountain air, staying cool under the afternoon sun and warm during frosty nights.';
        $why_image = $s['why_image'] ?? '../assets/images/cob_mudhouse.jpg';
        if (!empty($why_image) && strpos($why_image, 'http') !== 0 && strpos($why_image, '../') !== 0 && strpos($why_image, '/') !== 0) {
            $why_image = '../' . $why_image;
        }
        ?>
        <section style="padding: 50px 24px; max-width: 1000px; margin: 0 auto;">
            <div style="text-align: center; max-width: 700px; margin: 0 auto 36px;">
                <span id="pv-why-badge" style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;"><?php echo htmlspecialchars($why_badge); ?></span>
                <h2 id="pv-why-title" style="font-family: var(--font-serif); font-size: 30px; color: #FFFFFF; font-weight: 400; margin: 8px 0 14px;"><?php echo htmlspecialchars($why_title); ?></h2>
                <p id="pv-why-desc" style="font-size: 14px; color: #CBD5E1; line-height: 1.7;"><?php echo htmlspecialchars($why_desc); ?></p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div style="background: rgba(16, 31, 21, 0.8); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 12px; padding: 22px;">
                    <div style="color: #2ecc71; font-size: 22px; margin-bottom: 12px;"><i class="fa-solid fa-leaf"></i></div>
                    <h4 style="font-family: var(--font-display); color: #FFF; font-size: 14px; margin-bottom: 6px;">Living Organic Soil</h4>
                    <p style="font-size: 12px; color: #839788;">Zero chemicals or pesticides across all orchard groves.</p>
                </div>
                <div style="background: rgba(16, 31, 21, 0.8); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 12px; padding: 22px;">
                    <div style="color: #C5A059; font-size: 22px; margin-bottom: 12px;"><i class="fa-solid fa-house-chimney"></i></div>
                    <h4 style="font-family: var(--font-display); color: #FFF; font-size: 14px; margin-bottom: 6px;">Cob Mud Architecture</h4>
                    <p style="font-size: 12px; color: #839788;">Breathable earthen thermal mass built with natural clay.</p>
                </div>
                <div style="background: rgba(16, 31, 21, 0.8); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 12px; padding: 22px;">
                    <div style="color: #e74c3c; font-size: 22px; margin-bottom: 12px;"><i class="fa-solid fa-fire"></i></div>
                    <h4 style="font-family: var(--font-display); color: #FFF; font-size: 14px; margin-bottom: 6px;">Slow Woodfire Hearth</h4>
                    <p style="font-size: 12px; color: #839788;">Clay hearth dining infused with wild mountain herbs.</p>
                </div>
            </div>
        </section>

    <?php elseif ($section === 'experiences'): ?>
        <!-- CARD 07: CURATED EXPERIENCES PREVIEW -->
        <?php
        $exps = $pdo->query("SELECT * FROM experiences ORDER BY display_order ASC, id ASC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <section style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;">IMMERSIVE RITUALS</span>
                <h2 style="font-family: var(--font-serif); font-size: 30px; color: #FFFFFF; font-weight: 400; margin: 6px 0;">Curated Sanctuary Experiences</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                <?php foreach ($exps as $e): 
                    $img = $e['image_url'] ?? '';
                    if (!empty($img) && strpos($img, 'http') !== 0 && strpos($img, '../') !== 0 && strpos($img, '/') !== 0) $img = '../' . $img;
                ?>
                <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 12px; overflow: hidden;">
                    <div style="height: 140px; position: relative;">
                        <img src="<?php echo htmlspecialchars($img); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                        <span style="position: absolute; top: 10px; left: 10px; background: rgba(11,24,16,0.85); color: #C5A059; font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700;">
                            <?php echo htmlspecialchars($e['badge'] ?? 'RITUAL'); ?>
                        </span>
                    </div>
                    <div style="padding: 16px;">
                        <h4 style="font-family: var(--font-display); font-size: 14px; color: #FFF; margin: 0 0 6px;"><?php echo htmlspecialchars($e['title']); ?></h4>
                        <p style="font-size: 12px; color: #839788; margin: 0 0 10px; line-height: 1.5;"><?php echo htmlspecialchars($e['tagline'] ?? ''); ?></p>
                        <span style="font-size: 11px; color: #C5A059;"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($e['timing'] ?? ''); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php elseif ($section === 'menu'): ?>
        <!-- CARD 08: FOOD MENU PREVIEW -->
        <?php
        $menus = $pdo->query("SELECT * FROM food_menu ORDER BY id ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <section style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;">ORGANIC GASTRONOMY</span>
                <h2 style="font-family: var(--font-serif); font-size: 28px; color: #FFFFFF; font-weight: 400; margin: 6px 0;">Living Farm-to-Table Menu</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <?php foreach ($menus as $m): 
                    $m_img = $m['image_url'] ?? '';
                    if (!empty($m_img) && strpos($m_img, 'http') !== 0 && strpos($m_img, '../') !== 0 && strpos($m_img, '/') !== 0) $m_img = '../' . $m_img;
                ?>
                <div style="background: rgba(16, 31, 21, 0.85); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 14px; display: flex; gap: 14px; align-items: center;">
                    <?php if (!empty($m_img)): ?>
                        <img src="<?php echo htmlspecialchars($m_img); ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="font-family: var(--font-display); font-size: 13.5px; color: #FFF; margin: 0;"><?php echo htmlspecialchars($m['heading'] ?? ($m['item_name'] ?? 'Dish')); ?></h4>
                            <strong style="color: #C5A059; font-size: 13px;"><?php echo htmlspecialchars($currency_symbol . number_format((float)($m['price'] ?? 0))); ?></strong>
                        </div>
                        <p style="font-size: 11.5px; color: #839788; margin: 4px 0 0; line-height: 1.4;"><?php echo htmlspecialchars($m['description'] ?? ($m['subtitle'] ?? '')); ?></p>
                        <span style="font-size: 10px; color: #2ecc71; text-transform: uppercase; font-weight: 600; margin-top: 4px; display: inline-block;"><?php echo htmlspecialchars($m['category'] ?? 'Dining'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php elseif ($section === 'seasons'): ?>
        <!-- CARD 09: SEASONS PREVIEW -->
        <?php
        $seasons = $pdo->query("SELECT * FROM seasons ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <section style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;">MICROCLIMATE CYCLES</span>
                <h2 style="font-family: var(--font-serif); font-size: 28px; color: #FFFFFF; font-weight: 400; margin: 6px 0;">4 Seasons of Kanthalloor</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <?php foreach ($seasons as $sn): 
                    $sn_img = $sn['image_url'] ?? '';
                    if (!empty($sn_img) && strpos($sn_img, 'http') !== 0 && strpos($sn_img, '../') !== 0 && strpos($sn_img, '/') !== 0) $sn_img = '../' . $sn_img;
                    $sn_title = $sn['title'] ?? ($sn['name'] ?? 'Season');
                ?>
                <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 12px; overflow: hidden;">
                    <div style="height: 110px;">
                        <img src="<?php echo htmlspecialchars($sn_img); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                    </div>
                    <div style="padding: 14px;">
                        <span style="font-size: 10px; color: #C5A059; font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($sn['months'] ?? ''); ?></span>
                        <h4 style="font-family: var(--font-display); font-size: 14px; color: #FFF; margin: 2px 0 6px;"><?php echo htmlspecialchars($sn_title); ?></h4>
                        <span style="font-size: 11px; color: #2ecc71; display: block;"><i class="fa-solid fa-temperature-half"></i> <?php echo htmlspecialchars($sn['temperature'] ?? '18°C - 24°C'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php elseif ($section === 'sanctuary_map'): ?>
        <!-- CARD 10: SANCTUARY ESTATE MAP PREVIEW -->
        <section style="padding: 30px 20px; max-width: 900px; margin: 0 auto; text-align: center;">
            <div style="margin-bottom: 20px;">
                <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;">MOUNTAIN TRAIL & MAP</span>
                <h2 style="font-family: var(--font-serif); font-size: 26px; color: #FFFFFF; font-weight: 400; margin: 6px 0;">Sanctuary Landscape Topography</h2>
            </div>
            <div style="position: relative; border-radius: 14px; overflow: hidden; border: 1px solid rgba(197, 160, 89, 0.3);">
                <img src="../assets/images/map_bg.jpg" style="width: 100%; height: 320px; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(11,24,16,0.4); display: flex; align-items: center; justify-content: center;">
                    <div style="background: rgba(16, 31, 21, 0.9); padding: 16px 24px; border-radius: 30px; border: 1px solid #C5A059; color: #FFF; font-size: 13px; font-weight: 600;">
                        <i class="fa-solid fa-map-pin" style="color: #2ecc71; margin-right: 8px;"></i> Interactive 4-Point Trail Route Map Active
                    </div>
                </div>
            </div>
        </section>

    <?php elseif ($section === 'rooms'): ?>
        <!-- CARD 11: VILLAS & RATES PREVIEW -->
        <?php
        $rooms = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <section style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;">PRIVATE ACCOMMODATIONS</span>
                <h2 style="font-family: var(--font-serif); font-size: 28px; color: #FFFFFF; font-weight: 400; margin: 6px 0;">Villas & Earthen Cottages</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px;">
                <?php foreach ($rooms as $rm): 
                    $rm_img = $rm['image_url'] ?? '';
                    if (!empty($rm_img) && strpos($rm_img, 'http') !== 0 && strpos($rm_img, '../') !== 0 && strpos($rm_img, '/') !== 0) $rm_img = '../' . $rm_img;
                    $rm_title = $rm['title'] ?? ($rm['name'] ?? 'Sanctuary Suite');
                    $rm_rate = (float)($rm['rate_per_night'] ?? ($rm['price_per_night'] ?? 14500));
                    $is_duplex = (($rm['structure_type'] ?? '') === 'duplex_hut');
                    $single_rate = (float)($rm['single_room_rate'] ?? $rm_rate);
                ?>
                <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 12px; overflow: hidden;">
                    <div style="height: 140px; position: relative;">
                        <img src="<?php echo htmlspecialchars($rm_img); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                        <span style="position: absolute; top: 10px; right: 10px; background: rgba(11,24,16,0.85); color: #C5A059; font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: 700;">
                            <?php echo htmlspecialchars($currency_symbol . number_format($rm_rate)); ?> / night
                        </span>
                        <?php if ($is_duplex): ?>
                            <span style="position: absolute; bottom: 8px; left: 8px; background: rgba(86, 194, 201, 0.9); color: #08120B; font-size: 10px; padding: 2px 7px; border-radius: 4px; font-weight: 800;">
                                <i class="fa-solid fa-layer-group"></i> DUPLEX (2 SUITES)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 16px;">
                        <h4 style="font-family: var(--font-display); font-size: 14px; color: #FFF; margin: 0 0 6px;"><?php echo htmlspecialchars($rm_title); ?></h4>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; color: #839788;">
                            <span><i class="fa-solid fa-user-group"></i> Base <?php echo (int)($rm['base_guests'] ?? 2); ?> • Max <?php echo (int)($rm['max_guests'] ?? 4); ?></span>
                            <?php if ($is_duplex): ?>
                                <span style="color: #56c2c9; font-size: 11px;">Single: ₹<?php echo number_format($single_rate); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php elseif ($section === 'gallery'): ?>
        <!-- CARD 12: GALLERY PREVIEW -->
        <?php
        $gallery = $pdo->query("SELECT * FROM gallery ORDER BY id ASC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <section style="padding: 30px 20px; max-width: 1000px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 20px;">
                <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;">VISUAL DIARY</span>
                <h2 style="font-family: var(--font-serif); font-size: 26px; color: #FFFFFF; font-weight: 400; margin: 4px 0;">Photo Chronicle of Sanctuary</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                <?php foreach ($gallery as $g): 
                    $g_img = $g['image_url'] ?? '';
                    if (!empty($g_img) && strpos($g_img, 'http') !== 0 && strpos($g_img, '../') !== 0 && strpos($g_img, '/') !== 0) $g_img = '../' . $g_img;
                ?>
                <div style="height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid rgba(197, 160, 89, 0.2);">
                    <img src="<?php echo htmlspecialchars($g_img); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php elseif ($section === 'testimonials'): ?>
        <!-- CARD 13: TESTIMONIALS PREVIEW -->
        <?php
        $reviews = $pdo->query("SELECT * FROM testimonials ORDER BY id ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <section style="padding: 40px 20px; max-width: 900px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="font-size: 11px; font-weight: 700; color: #C5A059; letter-spacing: 2px; text-transform: uppercase;">TRAVELER REFLECTIONS</span>
                <h2 style="font-family: var(--font-serif); font-size: 26px; color: #FFFFFF; font-weight: 400; margin: 6px 0;">Guest Voices & High Praise</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                <?php foreach ($reviews as $rev): ?>
                <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 12px; padding: 20px;">
                    <div style="color: #F59E0B; font-size: 13px; margin-bottom: 10px;">
                        <?php for ($i=0; $i<(int)($rev['rating'] ?? 5); $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                    </div>
                    <p style="font-size: 13px; color: #CBD5E1; font-style: italic; line-height: 1.6; margin: 0 0 14px;">"<?php echo htmlspecialchars($rev['review_text']); ?>"</p>
                    <strong style="color: #FFF; font-size: 13px; display: block;"><?php echo htmlspecialchars($rev['guest_name']); ?></strong>
                    <span style="font-size: 11px; color: #839788;"><?php echo htmlspecialchars($rev['stay_type'] ?? 'Verified Guest'); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

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
                <h3 style="font-family: var(--font-display); color: #FFF; font-size: 18px; margin: 0 0 6px;">Concierge Authentication Layer</h3>
                <p style="font-size: 12px; color: #839788; margin: 0 0 16px;">Bcrypt-hashed master password protection active.</p>
                <div style="background: rgba(11,24,16,0.8); padding: 10px; border-radius: 6px; font-size: 12px; color: #2ecc71;">
                    <i class="fa-solid fa-lock"></i> Session Authenticated & Encrypted
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
    <?php endif; ?>

</div>

<!-- Real-Time Two-Way Synchronization Script -->
<script>
window.addEventListener('message', function(event) {
    if (!event.data) return;
    var data = event.data;

    // Direct text element update with smooth highlight
    if (data.type === 'update_text' && data.selector) {
        var el = document.querySelector(data.selector);
        if (el) {
            el.textContent = data.value;
            el.classList.add('preview-highlight-pulse');
            setTimeout(function() { el.classList.remove('preview-highlight-pulse'); }, 1200);
        }
    }

    // Direct image update
    if (data.type === 'update_image' && data.selector && data.src) {
        var img = document.querySelector(data.selector);
        if (img) {
            img.src = data.src;
            img.classList.add('preview-highlight-pulse');
            setTimeout(function() { img.classList.remove('preview-highlight-pulse'); }, 1200);
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
            if (el) el.src = f.hero_bg_image;
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
    }
});
</script>

</body>
</html>
