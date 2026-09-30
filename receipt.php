<?php
// =========================================================================
// Food Forest Sanctuary — Luxury Reservation Receipt & Printable PDF
// =========================================================================

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/client_auth.php';

client_session_start();

$ref_code = strtoupper(trim($_GET['ref'] ?? ''));
$passcode = trim($_GET['passcode'] ?? '');

if (empty($ref_code)) {
    die("<div style='font-family:serif;padding:60px;text-align:center;color:#101F15;'><h2>Reservation Reference Required</h2><p>Please provide a valid reservation reference code.</p><p><a href='index.php'>Return to Sanctuary Home</a></p></div>");
}

$booking = get_booking_by_ref($ref_code);

if (!$booking) {
    die("<div style='font-family:serif;padding:60px;text-align:center;color:#101F15;'><h2>Reservation Not Found</h2><p>No reservation matching reference <strong>" . htmlspecialchars($ref_code) . "</strong> exists.</p><p><a href='index.php'>Return to Sanctuary Home</a></p></div>");
}

// Access Verification:
// Allowed if:
// 1. Logged in as admin
// 2. Logged in as user and matches booking.user_id
// 3. Active guest session matches booking.reference_code
// 4. Passcode matches guest_access_token or guest phone
$is_admin = !empty($_SESSION['admin_logged_in']);
$is_owner_user = is_client_user_logged_in() && ((int)$_SESSION['ff_client_user_id'] === (int)$booking['user_id']);
$is_owner_guest = is_guest_booking_session_active() && ($_SESSION['ff_guest_booking_ref'] === $booking['reference_code']);
$is_valid_passcode = !empty($passcode) && (!empty($booking['guest_access_token']) && strcasecmp($booking['guest_access_token'], $passcode) === 0);

if (!$is_admin && !$is_owner_user && !$is_owner_guest && !$is_valid_passcode) {
    // If not verified, redirect to guest portal with ref code prefilled
    header("Location: guest_portal.php?ref=" . urlencode($ref_code) . "&msg=auth_required");
    exit;
}

// Check 30-day auto-destruct expiration for guest bookings
$is_expired = false;
if (!empty($booking['is_guest']) && !empty($booking['expires_at'])) {
    if (strtotime($booking['expires_at']) < time() && !$is_admin) {
        $is_expired = true;
    }
}

if ($is_expired) {
    die("<div style='font-family:sans-serif;max-width:540px;margin:80px auto;padding:40px;background:#fff8f6;border:1px solid #fecaca;border-radius:12px;text-align:center;'>
        <h3 style='color:#991b1b;font-family:serif;font-size:24px;margin-top:0;'>30-Day Guest Pass Expired</h3>
        <p style='color:#4b5563;font-size:15px;line-height:1.6;'>This temporary guest reservation receipt ($ref_code) has passed its 30-day active window. If you wish to retrieve historical stay records, please contact our estate concierge.</p>
        <div style='margin-top:24px;'>
            <a href='https://wa.me/919234567890' style='display:inline-block;padding:10px 20px;background:#101F15;color:#EAEFED;text-decoration:none;border-radius:6px;font-size:14px;'>Contact Master Concierge</a>
            <a href='guest_portal.php' style='display:inline-block;margin-left:10px;padding:10px 20px;background:#EAEFED;color:#101F15;text-decoration:none;border-radius:6px;font-size:14px;'>Guest Portal</a>
        </div>
    </div>");
}

$concierge_phone = get_setting('concierge_phone', '+91 923 456 7890');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');
$currency = get_setting('currency_symbol', '₹');
$villa_title = $booking['room_title'] ?? ($booking['villa_type'] === 'treehouse' ? 'Luxury Canopy Treehouse' : 'Traditional Earthen Mudhouse');
$villa_image = $booking['room_image'] ?? ($booking['villa_type'] === 'treehouse' ? 'assets/images/treehouse_exterior.png' : 'assets/images/mudhouse_exterior.png');

$food_items = $booking['food_items_list'] ?? [];
$food_amount = (float)($booking['food_amount'] ?? 0.00);
$room_amount = (float)($booking['room_amount'] ?? ($booking['total_amount'] - $food_amount));
$total_amount = (float)$booking['total_amount'];

// Billing Type & GST Breakdown
$billing_type = !empty($booking['billing_type']) ? strtolower(trim($booking['billing_type'])) : 'estimate';
$is_gst_bill = ($billing_type === 'gst');
$guest_gstin = $booking['gst_number'] ?? '';
$billing_name = $booking['billing_name'] ?? '';
$billing_address = $booking['billing_address'] ?? '';
$gst_percentage = (float)($booking['gst_percentage'] ?? 0.00);
$gst_amount = (float)($booking['gst_amount'] ?? 0.00);
$taxable_subtotal = $room_amount + $food_amount;

if ($is_gst_bill && $gst_percentage <= 0) {
    $gst_percentage = (float)get_setting('gst_rate_percentage', '12');
}
if ($is_gst_bill && $gst_amount <= 0) {
    $gst_amount = round($taxable_subtotal * ($gst_percentage / 100), 2);
}

// Group food items by category
$grouped_food = [];
foreach ($food_items as $fi) {
    $cat = strtolower(trim($fi['category'] ?? 'general'));
    if (!isset($grouped_food[$cat])) {
        $grouped_food[$cat] = [];
    }
    $grouped_food[$cat][] = $fi;
}

$meal_rituals = [
    'breakfast' => [
        'name' => 'Breakfast Ritual',
        'icon' => 'fa-solid fa-mug-saucer',
        'time' => '07:30 AM — 10:00 AM',
        'items' => $grouped_food['breakfast'] ?? [],
        'default_title' => 'Chef\'s Organic Orchard Breakfast',
        'is_complimentary' => true,
    ],
    'lunch' => [
        'name' => 'Lunch Ritual',
        'icon' => 'fa-solid fa-bowl-rice',
        'time' => '12:30 PM — 02:30 PM',
        'items' => $grouped_food['lunch'] ?? [],
        'default_title' => 'Woodfire Claypot Harvest Lunch',
        'is_complimentary' => false,
    ],
    'snacks' => [
        'name' => 'Evening Snacks',
        'icon' => 'fa-solid fa-cookie-bite',
        'time' => '04:30 PM — 06:30 PM',
        'items' => $grouped_food['snacks'] ?? [],
        'default_title' => 'Plantation Tea & Hearth Delicacies',
        'is_complimentary' => false,
    ],
    'dinner' => [
        'name' => 'Dinner Ritual',
        'icon' => 'fa-solid fa-fire-burner',
        'time' => '07:30 PM — 10:00 PM',
        'items' => $grouped_food['dinner'] ?? [],
        'default_title' => 'Twilight Campfire & Hearth Feast',
        'is_complimentary' => false,
    ],
];

// Parse Add-ons / Experiences
$addons_text = trim($booking['addons'] ?? '');
$parsed_addons = [];
if (!empty($addons_text) && strtolower($addons_text) !== 'none') {
    $addon_parts = preg_split('/,(?![^(]*\))/', $addons_text);
    foreach ($addon_parts as $ap) {
        $ap = trim($ap);
        if (!empty($ap)) {
            $icon = 'fa-solid fa-sparkles';
            $ap_lower = strtolower($ap);
            if (strpos($ap_lower, 'dinner') !== false || strpos($ap_lower, 'candle') !== false) {
                $icon = 'fa-solid fa-wine-glass';
            } elseif (strpos($ap_lower, 'pottery') !== false || strpos($ap_lower, 'clay') !== false) {
                $icon = 'fa-solid fa-hands-holding-circle';
            } elseif (strpos($ap_lower, 'trek') !== false || strpos($ap_lower, 'trail') !== false || strpos($ap_lower, 'peak') !== false) {
                $icon = 'fa-solid fa-person-hiking';
            }
            $parsed_addons[] = [
                'title' => $ap,
                'icon' => $icon
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Receipt — <?php echo htmlspecialchars($booking['reference_code']); ?> | Food Forest Sanctuary</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #101F15;
            --primary-light: #1A2E22;
            --accent: #C5A059;
            --accent-soft: #dfc289;
            --accent-terra: #C26D4D;
            --bg-cream: #F9F8F6;
            --card-bg: #FFFFFF;
            --text-dark: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F0F2EF;
            color: var(--text-dark);
            line-height: 1.6;
            padding: 40px 20px;
        }

        .font-serif {
            font-family: 'Cormorant Garamond', Georgia, serif;
        }

        .font-sans {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Top Actions Bar */
        .receipt-action-bar {
            max-width: 900px;
            margin: 0 auto 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-receipt {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-print {
            background-color: var(--primary);
            color: #FFFFFF;
        }

        .btn-print:hover {
            background-color: var(--accent);
            color: var(--primary);
        }

        .btn-secondary {
            background-color: #FFFFFF;
            color: var(--primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #F3F4F6;
        }

        .btn-whatsapp {
            background-color: #25D366;
            color: #FFFFFF;
        }

        .btn-whatsapp:hover {
            background-color: #1EBE5D;
        }

        /* Main Luxury Paper Container */
        .receipt-sheet {
            max-width: 900px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.06);
            overflow: hidden;
            position: relative;
        }

        .receipt-sheet::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #101F15, #C5A059, #C26D4D, #101F15);
        }

        /* Header */
        .receipt-header {
            padding: 40px 48px 30px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
        }

        .receipt-brand-logo .brand-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .receipt-brand-logo .brand-sub {
            font-size: 11px;
            letter-spacing: 3px;
            color: var(--accent-terra);
            display: block;
            margin-top: 2px;
            font-weight: 600;
        }

        .receipt-brand-address {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 8px;
            line-height: 1.5;
        }

        .receipt-meta-box {
            text-align: right;
        }

        .receipt-status-badge {
            display: inline-block;
            padding: 5px 14px;
            background-color: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .receipt-ref-code {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 1px;
        }

        .receipt-date-line {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Body Sections */
        .receipt-body {
            padding: 36px 48px;
        }

        /* Villa Showcase Card */
        .villa-showcase-box {
            display: grid;
            grid-template-columns: 200px 1fr;
            background: #F9FAF8;
            border: 1px solid #E5E9E4;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 32px;
        }

        .villa-thumb {
            width: 100%;
            height: 100%;
            min-height: 140px;
            object-fit: cover;
        }

        .villa-info {
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .villa-stay-pill {
            display: inline-block;
            align-self: flex-start;
            padding: 3px 10px;
            background: rgba(197, 160, 89, 0.15);
            color: #936814;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .villa-name {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            line-height: 1.2;
        }

        .villa-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 10px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .villa-specs span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Key Grid: Guest & Stay Details */
        .receipt-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 32px;
        }

        .details-col-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13.5px;
            border-bottom: 1px dashed #F0F2EF;
        }

        .detail-row .label {
            color: var(--text-muted);
        }

        .detail-row .value {
            font-weight: 600;
            color: var(--text-dark);
            text-align: right;
        }

        /* Gastronomy Section Table & Ritual Cards */
        .section-header-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .category-pill-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            background: rgba(16, 31, 21, 0.08);
            color: var(--primary);
        }

        .gastronomy-rituals-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        @media (max-width: 640px) {
            .gastronomy-rituals-grid {
                grid-template-columns: 1fr;
            }
        }

        .meal-ritual-card {
            background: #FAFCFA;
            border: 1.5px solid #E2ECE5;
            border-radius: 8px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .meal-ritual-card:hover {
            border-color: #CBDCD0;
            box-shadow: 0 2px 8px rgba(16, 31, 21, 0.04);
        }

        .meal-ritual-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #D6E4DB;
        }

        .meal-ritual-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .meal-ritual-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-included {
            background: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }

        .badge-onsite {
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
        }

        .badge-ordered {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .meal-dishes-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .meal-dish-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 13px;
            line-height: 1.35;
        }

        .meal-dish-name {
            color: #1F2937;
            font-weight: 600;
            flex: 1;
        }

        .meal-dish-rate {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
            white-space: nowrap;
            margin-left: 12px;
        }

        /* Experiences Section */
        .experiences-section {
            margin-bottom: 28px;
        }

        .experiences-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .experience-card {
            background: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .experience-card-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .experience-icon {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: rgba(197, 160, 89, 0.15);
            color: #8C6615;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .experience-info strong {
            font-size: 13.5px;
            color: var(--primary);
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .experience-info span {
            font-size: 11.5px;
            color: var(--text-muted);
            display: block;
            margin-top: 1px;
        }

        .experience-rate-tag {
            text-align: right;
            flex-shrink: 0;
        }

        .experience-rate-tag .rate-val {
            font-size: 13px;
            font-weight: 700;
            color: #0E7490;
            display: block;
        }

        .experience-rate-tag .rate-sub {
            font-size: 10.5px;
            color: #64748B;
            display: block;
        }

        .no-exp-card {
            background: #F8FAF9;
            border: 1px dashed #CBD5E1;
            border-radius: 8px;
            padding: 12px 18px;
            font-size: 12.5px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Financial Summary Box */
        .receipt-finance-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 28px;
            margin-bottom: 32px;
        }

        .guest-credentials-card {
            background: #FDFCF7;
            border: 1px solid #EFE8D3;
            border-radius: 8px;
            padding: 18px 22px;
        }

        .credentials-header {
            font-size: 13px;
            font-weight: 700;
            color: #8C6615;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .credentials-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .passcode-pill {
            font-family: monospace;
            background: #8C6615;
            color: #FFFFFF;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .credentials-note {
            font-size: 11px;
            color: #78716C;
            margin-top: 10px;
            line-height: 1.4;
        }

        .charges-table {
            width: 100%;
            border-collapse: collapse;
        }

        .charges-table td {
            padding: 8px 0;
            font-size: 13.5px;
            border-bottom: 1px solid #F3F4F6;
        }

        .charges-table .total-row td {
            padding-top: 14px;
            border-bottom: none;
            border-top: 2px solid var(--primary);
        }

        .grand-total-val {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
        }

        /* House Rules & Policies */
        .receipt-footer-policies {
            background: #FAFAFA;
            border-top: 1px solid var(--border-color);
            padding: 24px 48px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .policies-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 8px;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #FFFFFF !important;
                padding: 0 !important;
                color: #000000 !important;
            }

            .receipt-action-bar {
                display: none !important;
            }

            .receipt-sheet {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
            }

            .receipt-header {
                padding: 20px 0 !important;
            }

            .receipt-body {
                padding: 20px 0 !important;
            }

            .receipt-footer-policies {
                padding: 20px 0 !important;
            }

            .villa-showcase-box {
                border: 1px solid #CCC !important;
                background: #FFF !important;
            }
        }

        @media (max-width: 768px) {
            .receipt-header, .receipt-body, .receipt-footer-policies {
                padding: 24px 20px;
            }
            .villa-showcase-box {
                grid-template-columns: 1fr;
            }
            .villa-thumb {
                height: 160px;
            }
            .receipt-details-grid, .receipt-finance-grid, .policies-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Actions Control Bar -->
    <div class="receipt-action-bar">
        <a href="guest_portal.php" class="btn-receipt btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Guest Portal</span>
        </a>
        <div style="display: flex; gap: 10px;">
            <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Concierge,%20I%20have%20an%20inquiry%20regarding%20my%20reservation%20<?php echo urlencode($booking['reference_code']); ?>." target="_blank" class="btn-receipt btn-whatsapp">
                <i class="fa-brands fa-whatsapp"></i>
                <span>Concierge Chat</span>
            </a>
            <button type="button" onclick="window.print()" class="btn-receipt btn-print">
                <i class="fa-solid fa-print"></i>
                <span>Download / Print PDF</span>
            </button>
        </div>
    </div>

    <!-- The Luxury Receipt Sheet -->
    <div class="receipt-sheet">
        
        <!-- Header -->
        <header class="receipt-header">
            <div>
                <div class="receipt-brand-logo">
                    <span class="brand-title font-serif">FOOD FOREST</span>
                    <span class="brand-sub font-sans">KANTHALLOOR • ECO SANCTUARY</span>
                </div>
                <p class="receipt-brand-address font-sans">
                    Kanthalloor High Range • 1,600m Elevation<br>
                    Marayoor Valley, Idukki District, Kerala — 685620<br>
                    Phone: <?php echo htmlspecialchars($concierge_phone); ?>
                </p>
            </div>
            <div class="receipt-meta-box">
                <?php if ($is_gst_bill): ?>
                    <span class="receipt-status-badge font-sans" style="background: rgba(2, 132, 199, 0.12); color: #0284C7; border: 1px solid rgba(2, 132, 199, 0.3);">
                        <i class="fa-solid fa-file-invoice-dollar"></i> TAX INVOICE (GST)
                    </span>
                <?php else: ?>
                    <span class="receipt-status-badge font-sans">
                        <i class="fa-solid fa-circle-check"></i> ESTIMATE STAY FOLIO
                    </span>
                <?php endif; ?>
                <div class="receipt-ref-code font-serif"><?php echo htmlspecialchars($booking['reference_code']); ?></div>
                <div class="receipt-date-line font-sans">
                    Issued: <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?>
                </div>
            </div>
        </header>

        <!-- Body -->
        <div class="receipt-body">

            <!-- Selected Villa Showcase Card -->
            <div class="villa-showcase-box">
                <img src="<?php echo htmlspecialchars($villa_image); ?>" 
                     alt="<?php echo htmlspecialchars($villa_title); ?>" 
                     class="villa-thumb"
                     onerror="this.src='assets/images/treehouse_exterior.png'">
                <div class="villa-info">
                    <span class="villa-stay-pill font-sans">
                        <i class="fa-solid fa-tree"></i> <?php echo htmlspecialchars(ucfirst($booking['room_stay_type'] ?? 'Sanctuary Stay')); ?>
                    </span>
                    <h2 class="villa-name font-serif"><?php echo htmlspecialchars($villa_title); ?></h2>
                    <div class="villa-specs font-sans">
                        <span><i class="fa-regular fa-moon"></i> <?php echo (int)$booking['nights']; ?> Night<?php echo $booking['nights'] > 1 ? 's' : ''; ?></span>
                        <span><i class="fa-solid fa-users"></i> <?php echo (int)$booking['adults_count']; ?> Adults<?php echo ((int)$booking['kids_count'] > 0) ? ', ' . (int)$booking['kids_count'] . ' Kids' : ''; ?></span>
                        <?php if (!empty($booking['room_elevation'])): ?>
                            <span><i class="fa-solid fa-mountain"></i> <?php echo htmlspecialchars($booking['room_elevation']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Guest & Stay Itinerary Grid -->
            <div class="receipt-details-grid">
                <!-- Stay Itinerary -->
                <div>
                    <h3 class="details-col-title font-serif"><i class="fa-regular fa-calendar-days"></i> Stay Schedule</h3>
                    <div class="detail-row">
                        <span class="label">Check-In Date:</span>
                        <span class="value"><?php echo date('D, d M Y', strtotime($booking['checkin_date'])); ?> (02:00 PM)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Check-Out Date:</span>
                        <span class="value"><?php echo date('D, d M Y', strtotime($booking['checkout_date'])); ?> (11:00 AM)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Total Duration:</span>
                        <span class="value"><?php echo (int)$booking['nights']; ?> Night(s)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Total Guests:</span>
                        <span class="value"><?php echo (int)$booking['guests_count']; ?> Person(s) (<?php echo (int)$booking['adults_count']; ?> Adults, <?php echo (int)$booking['kids_count']; ?> Children)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Folio Type:</span>
                        <span class="value" style="font-weight: 700; color: <?php echo $is_gst_bill ? '#0284C7' : '#101F15'; ?>;">
                            <?php echo $is_gst_bill ? "Official Tax Invoice (GST {$gst_percentage}%)" : 'Estimate Bill (Standard Folio)'; ?>
                        </span>
                    </div>
                </div>

                <!-- Guest Particulars -->
                <div>
                    <h3 class="details-col-title font-serif"><i class="fa-regular fa-user"></i> Guest Particulars</h3>
                    <div class="detail-row">
                        <span class="label">Primary Guest:</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">WhatsApp / Phone:</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Email Address:</span>
                        <span class="value"><?php echo htmlspecialchars($booking['guest_email'] ?: 'On File'); ?></span>
                    </div>
                    <?php if ($is_gst_bill): ?>
                        <?php if (!empty($guest_gstin)): ?>
                            <div class="detail-row">
                                <span class="label">Guest GSTIN:</span>
                                <span class="value" style="font-family: monospace; font-weight: bold; color: #0284C7;"><?php echo htmlspecialchars($guest_gstin); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($billing_name)): ?>
                            <div class="detail-row">
                                <span class="label">Billing Name:</span>
                                <span class="value"><?php echo htmlspecialchars($billing_name); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($billing_address)): ?>
                            <div class="detail-row">
                                <span class="label">Billing Address:</span>
                                <span class="value"><?php echo htmlspecialchars($billing_address); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($booking['special_notes'])): ?>
                        <div class="detail-row">
                            <span class="label">Preferences / Notes:</span>
                            <span class="value"><?php echo htmlspecialchars($booking['special_notes']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
              <!-- Curated Gastronomy (Food Menu) Section -->
            <div style="margin-bottom: 24px;">
                <div class="section-header-title font-serif">
                    <span><i class="fa-solid fa-utensils"></i> Curated Estate Gastronomy</span>
                    <?php if ($food_amount > 0): ?>
                        <span class="category-pill-tag" style="background: rgba(197, 160, 89, 0.2); color: #8C6615;">
                            <?php echo count($food_items); ?> Dish Set<?php echo count($food_items) > 1 ? 's' : ''; ?> (Advance Pre-Selected)
                        </span>
                    <?php else: ?>
                        <span class="category-pill-tag" style="background: rgba(16, 185, 129, 0.12); color: #065F46;">
                            Farm-To-Table Dining Plan
                        </span>
                    <?php endif; ?>
                </div>

                <div class="gastronomy-rituals-grid font-sans">
                    <?php foreach ($meal_rituals as $r_key => $ritual): 
                        $has_items = !empty($ritual['items']);
                    ?>
                        <div class="meal-ritual-card">
                            <div>
                                <div class="meal-ritual-header">
                                    <div class="meal-ritual-title">
                                        <i class="<?php echo $ritual['icon']; ?>" style="color: var(--accent);"></i>
                                        <span><?php echo htmlspecialchars($ritual['name']); ?></span>
                                    </div>
                                    <?php if ($ritual['is_complimentary']): ?>
                                        <span class="meal-ritual-badge badge-included">
                                            <i class="fa-solid fa-gift"></i> Complimentary
                                        </span>
                                    <?php elseif ($has_items): ?>
                                        <span class="meal-ritual-badge badge-ordered">
                                            Pre-Selected
                                        </span>
                                    <?php else: ?>
                                        <span class="meal-ritual-badge badge-onsite">
                                            On-Site Choice
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <ul class="meal-dishes-list">
                                    <?php if ($has_items): ?>
                                        <?php foreach ($ritual['items'] as $item): 
                                            $i_qty = (int)($item['quantity'] ?? 1);
                                            $i_price = (float)($item['price'] ?? 0);
                                            $i_subtotal = (float)($item['subtotal'] ?? ($i_price * $i_qty));
                                        ?>
                                            <li class="meal-dish-item">
                                                <div class="meal-dish-name">
                                                    <strong><?php echo htmlspecialchars($item['heading']); ?></strong>
                                                    <?php if ($i_qty > 1): ?>
                                                        <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(<?php echo $i_qty; ?> Sets)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="meal-dish-rate">
                                                    <?php if ($ritual['is_complimentary'] || $i_price == 0): ?>
                                                        <span style="color: #059669; font-weight: 700;">Included (₹0.00)</span>
                                                    <?php else: ?>
                                                        <span><?php echo $currency . number_format($i_subtotal, 2); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="meal-dish-item">
                                            <div class="meal-dish-name" style="color: #4B5563;">
                                                <span><?php echo htmlspecialchars($ritual['default_title']); ?></span>
                                            </div>
                                            <div class="meal-dish-rate">
                                                <?php if ($ritual['is_complimentary']): ?>
                                                    <span style="color: #059669; font-weight: 700;">Included (₹0.00)</span>
                                                <?php else: ?>
                                                    <span style="color: #64748B; font-weight: 600; font-size: 11.5px;">Payable On-Site (₹0.00)</span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Selected Signature Experiences & Curated Add-ons Section -->
            <div class="experiences-section font-sans">
                <div class="section-header-title font-serif">
                    <span><i class="fa-solid fa-sparkles" style="color: var(--accent);"></i> Selected Signature Experiences</span>
                    <?php if (!empty($parsed_addons)): ?>
                        <span class="category-pill-tag" style="background: rgba(14, 116, 144, 0.12); color: #0E7490;">
                            <?php echo count($parsed_addons); ?> Experience<?php echo count($parsed_addons) > 1 ? 's' : ''; ?> Chosen
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($parsed_addons)): ?>
                    <div class="experiences-grid">
                        <?php foreach ($parsed_addons as $addon): ?>
                            <div class="experience-card">
                                <div class="experience-card-left">
                                    <div class="experience-icon">
                                        <i class="<?php echo $addon['icon']; ?>"></i>
                                    </div>
                                    <div class="experience-info">
                                        <strong><?php echo htmlspecialchars($addon['title']); ?></strong>
                                        <span>Direct on-site settlement to artisan/guide</span>
                                    </div>
                                </div>
                                <div class="experience-rate-tag">
                                    <span class="rate-val">₹0.00</span>
                                    <span class="rate-sub">Payable On-Site</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-exp-card">
                        <i class="fa-solid fa-person-hiking" style="color: var(--accent); font-size: 16px;"></i>
                        <span>No extra signature experiences pre-booked. Orchard candlelight dining, pottery workshops, and sunrise high-peak valley trails can be booked on arrival.</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Financial Summary & Guest Access Box -->
            <div class="receipt-finance-grid">
                
                <!-- Left: Guest Access Passcode Box (if guest pass) -->
                <div>
                    <?php if (!empty($booking['is_guest'])): ?>
                        <div class="guest-credentials-card font-sans">
                            <div class="credentials-header">
                                <i class="fa-solid fa-key"></i>
                                <span>30-Day Guest Portal Pass</span>
                            </div>
                            <div class="credentials-row">
                                <span style="color: #6B7280;">Reservation ID:</span>
                                <strong><?php echo htmlspecialchars($booking['reference_code']); ?></strong>
                            </div>
                            <div class="credentials-row">
                                <span style="color: #6B7280;">Access Passcode:</span>
                                <span class="passcode-pill"><?php echo htmlspecialchars($booking['guest_access_token'] ?: 'Direct'); ?></span>
                            </div>
                            <?php if (!empty($booking['expires_at'])): ?>
                                <div class="credentials-row">
                                    <span style="color: #6B7280;">Valid Until:</span>
                                    <span style="color: #B45309; font-weight: 600;"><?php echo date('d M Y', strtotime($booking['expires_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <p class="credentials-note">
                                <i class="fa-solid fa-clock-rotate-left"></i> Valid for 30 days. Log in at <strong>guest_portal.php</strong> anytime to re-access this receipt or convert to a permanent member account.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="guest-credentials-card font-sans" style="background: #F0FDF4; border-color: #BBF7D0;">
                            <div class="credentials-header" style="color: #15803D;">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>Permanent Sanctuary Account</span>
                            </div>
                            <p style="font-size: 13px; color: #166534; line-height: 1.5;">
                                This booking is permanently linked to your personal guest account. All your reservation history and receipts are securely archived indefinitely.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Financial Itemization -->
                <div>
                    <table class="charges-table font-sans">
                        <tbody>
                            <tr>
                                <td style="color: var(--text-muted);">Villa Tariff (<?php echo (int)$booking['nights']; ?> Night<?php echo $booking['nights'] > 1 ? 's' : ''; ?>):</td>
                                <td style="text-align: right; font-weight: 600;"><?php echo $currency . number_format($room_amount, 2); ?></td>
                            </tr>
                            <?php if (!empty($parsed_addons)): ?>
                                <tr>
                                    <td style="color: var(--text-muted);">Signature Experiences (<?php echo count($parsed_addons); ?>):</td>
                                    <td style="text-align: right; font-weight: 600; color: #0E7490;">Payable On-Site (₹0.00)</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td style="color: var(--text-muted);">Estate Gastronomy Total:</td>Gastronomy Total:</td>
                                <td style="text-align: right; font-weight: 600;"><?php echo $currency . number_format($food_amount, 2); ?></td>
                            </tr>
                            <?php if ($is_gst_bill): ?>
                                <tr>
                                    <td style="color: #475569; font-weight: 600; border-top: 1px dashed #E2E8F0; padding-top: 6px;">Taxable Subtotal:</td>
                                    <td style="text-align: right; font-weight: 600; border-top: 1px dashed #E2E8F0; padding-top: 6px;"><?php echo $currency . number_format($taxable_subtotal, 2); ?></td>
                                </tr>
                                <tr>
                                    <td style="color: #0284C7; font-size: 13px;">
                                        CGST (<?php echo number_format($gst_percentage / 2, 2); ?>%):
                                    </td>
                                    <td style="text-align: right; color: #0284C7; font-weight: 600;">
                                        +<?php echo $currency . number_format($gst_amount / 2, 2); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="color: #0284C7; font-size: 13px;">
                                        SGST (<?php echo number_format($gst_percentage / 2, 2); ?>%):
                                    </td>
                                    <td style="text-align: right; color: #0284C7; font-weight: 600;">
                                        +<?php echo $currency . number_format($gst_amount / 2, 2); ?>
                                    </td>
                                </tr>
                                <tr class="total-row">
                                    <td class="font-serif" style="font-size: 18px; font-weight: 700; color: var(--primary);">Grand Total (GST Incl.):</td>
                                    <td class="font-serif grand-total-val"><?php echo $currency . number_format($total_amount, 2); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td style="color: var(--text-muted);">Estate Taxes & Levies:</td>
                                    <td style="text-align: right; color: #10B981; font-weight: 600;">Inclusive</td>
                                </tr>
                                <tr class="total-row">
                                    <td class="font-serif" style="font-size: 18px; font-weight: 700; color: var(--primary);">Estimated Total:</td>
                                    <td class="font-serif grand-total-val"><?php echo $currency . number_format($total_amount, 2); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

        <!-- Policies & Etiquette Footer -->
        <footer class="receipt-footer-policies font-sans">
            <strong>Sanctuary House Etiquette & Concierge Information:</strong>
            <div class="policies-grid">
                <div>
                    • <strong>Check-In</strong>: 02:00 PM | <strong>Check-Out</strong>: 11:00 AM<br>
                    • <strong>Location</strong>: Deep inside Kanthalloor fruit orchards (GPS coordinates shared on WhatsApp)<br>
                    • <strong>Plastic Free</strong>: Single-use plastics are prohibited within the sanctuary
                </div>
                <div>
                    • <strong>Cancellation</strong>: Free date adjustments up to 72 hours prior to arrival<br>
                    • <strong>Concierge Desk</strong>: Available daily 08:00 AM – 09:00 PM<br>
                    • <strong>Direct Hotline</strong>: <?php echo htmlspecialchars($concierge_phone); ?>
                </div>
            </div>
            <p style="text-align: center; margin-top: 20px; font-size: 11px; color: #9CA3AF;">
                © 2026 Food Forest Sanctuary Kanthalloor • An unhurried ecological retreat rooted in earth, reverence & time.
            </p>
        </footer>

    </div>

</body>
</html>
