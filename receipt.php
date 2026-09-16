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

// Group food items by category
$grouped_food = [];
foreach ($food_items as $fi) {
    $cat = strtolower(trim($fi['category'] ?? 'general'));
    if (!isset($grouped_food[$cat])) {
        $grouped_food[$cat] = [];
    }
    $grouped_food[$cat][] = $fi;
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

        /* Gastronomy Section Table */
        .section-header-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .food-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }

        .food-table th {
            background-color: #F8FAF9;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: left;
            padding: 10px 14px;
            border-bottom: 2px solid var(--border-color);
        }

        .food-table td {
            padding: 12px 14px;
            font-size: 13.5px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        .dish-title-cell strong {
            display: block;
            font-size: 14px;
            color: var(--primary);
        }

        .dish-inclusions-snippet {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .dish-inc-tag {
            font-size: 11px;
            background: #F3F4F6;
            color: #4B5563;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .category-pill-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            background: rgba(16, 31, 21, 0.08);
            color: var(--primary);
        }

        .food-skipped-box {
            background: #FBFBFA;
            border: 1px dashed #D1D5DB;
            border-radius: 8px;
            padding: 16px 20px;
            font-size: 13.5px;
            color: var(--text-muted);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
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
                <span class="receipt-status-badge font-sans">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars(strtoupper($booking['status'] ?? 'CONFIRMED')); ?>
                </span>
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
                    <?php if (!empty($booking['special_notes'])): ?>
                        <div class="detail-row">
                            <span class="label">Preferences / Notes:</span>
                            <span class="value"><?php echo htmlspecialchars($booking['special_notes']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Curated Gastronomy (Food Menu) Section -->
            <div>
                <div class="section-header-title font-serif">
                    <span><i class="fa-solid fa-utensils"></i> Curated Estate Gastronomy</span>
                    <?php if ($food_amount > 0): ?>
                        <span class="category-pill-tag" style="background: rgba(197, 160, 89, 0.2); color: #8C6615;">
                            <?php echo count($food_items); ?> Dish Set<?php echo count($food_items) > 1 ? 's' : ''; ?> Selected
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($food_items)): ?>
                    <table class="food-table font-sans">
                        <thead>
                            <tr>
                                <th>Meal Ritual</th>
                                <th>Dish / Set Selection</th>
                                <th style="text-align: center;">Sets</th>
                                <th style="text-align: right;">Unit Price</th>
                                <th style="text-align: right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($food_items as $fi): ?>
                                <tr>
                                    <td>
                                        <span class="category-pill-tag">
                                            <?php echo htmlspecialchars(strtoupper($fi['category'] ?? 'Meal')); ?>
                                        </span>
                                    </td>
                                    <td class="dish-title-cell">
                                        <strong><?php echo htmlspecialchars($fi['heading']); ?></strong>
                                        <?php if (!empty($fi['subtitle'])): ?>
                                            <span style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($fi['subtitle']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($fi['inclusions'])): ?>
                                            <div class="dish-inclusions-snippet">
                                                <?php foreach ($fi['inclusions'] as $inc): ?>
                                                    <span class="dish-inc-tag">✓ <?php echo htmlspecialchars($inc); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 600;">
                                        <?php echo (int)($fi['quantity'] ?? 1); ?>
                                    </td>
                                    <td style="text-align: right; color: var(--text-muted);">
                                        <?php echo $currency . number_format((float)$fi['price'], 2); ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: var(--primary);">
                                        <?php echo $currency . number_format((float)($fi['subtotal'] ?? ($fi['price'] * $fi['quantity'])), 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="food-skipped-box font-sans">
                        <i class="fa-solid fa-seedling" style="color: var(--accent); font-size: 20px;"></i>
                        <div>
                            <strong>Gastronomy Pre-selection Skipped</strong>
                            <p style="font-size: 12px; margin-top: 2px;">You opted to select meals on arrival. Wholesome organic farm meals will be harvested and cooked fresh over wood hearths according to your daily choice.</p>
                        </div>
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
                            <?php if (!empty($booking['addons'])): ?>
                                <tr>
                                    <td style="color: var(--text-muted);">Signature Experiences:</td>
                                    <td style="text-align: right; font-weight: 600;"><?php echo htmlspecialchars($booking['addons']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td style="color: var(--text-muted);">Estate Gastronomy Total:</td>
                                <td style="text-align: right; font-weight: 600;"><?php echo $currency . number_format($food_amount, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="color: var(--text-muted);">Estate Taxes & Ecological Levies:</td>
                                <td style="text-align: right; color: #10B981; font-weight: 600;">Inclusive</td>
                            </tr>
                            <tr class="total-row">
                                <td class="font-serif" style="font-size: 18px; font-weight: 700; color: var(--primary);">Estimated Total:</td>
                                <td class="font-serif grand-total-val"><?php echo $currency . number_format($total_amount, 2); ?></td>
                            </tr>
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
