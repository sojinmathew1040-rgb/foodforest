<?php
// =========================================================================
// Food Forest Sanctuary — Luxury Guest Folio & Tax Invoice (Print Engine)
// =========================================================================

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin_auth();

$pdo = get_db();
$ref_code = trim($_GET['ref'] ?? '');
$booking_id = (int)($_GET['id'] ?? 0);

if (empty($ref_code) && empty($booking_id)) {
    die("<div style='font-family:sans-serif;padding:50px;text-align:center;color:#101F15;'><h2>Reference or Booking ID Required</h2><p><a href='billing.php' style='color:#C5A059;'>Return to Billing Hub</a></p></div>");
}

$identifier = !empty($ref_code) ? $ref_code : $booking_id;
$booking = get_booking_billing_details($pdo, $identifier);

if (!$booking) {
    die("<div style='font-family:sans-serif;padding:50px;text-align:center;color:#101F15;'><h2>Reservation Record Not Found</h2><p>Could not locate billing data for <strong>" . htmlspecialchars($identifier) . "</strong>.</p><p><a href='billing.php' style='color:#C5A059;'>Return to Billing Hub</a></p></div>");
}

$p = $booking['parsed'];
$concierge_phone = get_setting('concierge_phone', '+91 923 456 7890');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');
$currency = get_setting('currency_symbol', '₹');
$gst_number = get_setting('gst_number', '32AAECF1234M1Z5');
$upi_id = get_setting('upi_id', 'foodforest@upi');
$bank_details = get_setting('bank_details', 'State Bank of India • A/C: 40982314981 • IFSC: SBIN0070123');

// Invoice Serial
$invoice_no = 'FF-INV-' . date('Ym', strtotime($booking['created_at'])) . '-' . str_pad((string)$booking['id'], 4, '0', STR_PAD_LEFT);
$bill_date = date('d M Y, h:i A');

// Generate WhatsApp message text
$guest_clean_phone = preg_replace('/[^0-9]/', '', $booking['guest_phone']);
$wa_msg = "🌿 *FOOD FOREST SANCTUARY — GUEST FOLIO & INVOICE*\n";
$wa_msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
$wa_msg .= "• *Invoice No*: {$invoice_no}\n";
$wa_msg .= "• *Booking Ref*: #{$booking['reference_code']}\n";
$wa_msg .= "• *Guest*: {$booking['guest_name']}\n";
$wa_msg .= "• *Property*: {$booking['room_title']}\n";
$wa_msg .= "• *Stay*: " . date('d M Y', strtotime($booking['checkin_date'])) . " to " . date('d M Y', strtotime($booking['checkout_date'])) . " ({$p['nights']} Night" . ($p['nights'] > 1 ? 's' : '') . ")\n";
$wa_msg .= "• *Occupancy*: {$p['adults_count']} Adults" . ($p['kids_count'] > 0 ? ", {$p['kids_count']} Kids" : '') . "\n";
$wa_msg .= "─────────────────────\n";
$wa_msg .= "• Room Tariff: {$currency}" . number_format($p['room_amount'], 2) . "\n";
if ($p['food_total'] > 0) {
    $wa_msg .= "• Gastronomy / Meals: {$currency}" . number_format($p['food_total'], 2) . "\n";
}
if ($p['activities_total'] > 0) {
    $wa_msg .= "• Sanctuary Experiences: {$currency}" . number_format($p['activities_total'], 2) . "\n";
}
if ($p['custom_total'] > 0 || $p['extra_charges'] > 0) {
    $wa_msg .= "• Extra Services / Amenities: {$currency}" . number_format($p['custom_total'] + $p['extra_charges'], 2) . "\n";
}
if ($p['discount_amount'] > 0) {
    $wa_msg .= "• Concession / Discount: -{$currency}" . number_format($p['discount_amount'], 2) . "\n";
}
$wa_msg .= "─────────────────────\n";
$wa_msg .= "*GRAND TOTAL*: {$currency}" . number_format($p['net_total'], 2) . "\n";
$wa_msg .= "• Advance Paid: {$currency}" . number_format($p['advance_paid'], 2) . "\n";
$wa_msg .= "*BALANCE DUE*: {$currency}" . number_format($p['balance_due'], 2) . "\n";
$wa_msg .= "• Status: " . strtoupper($p['payment_status']) . "\n";
$wa_msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
$wa_msg .= "Thank you for staying at Food Forest Sanctuary, Kanthalloor!";
$wa_url = "https://wa.me/" . (str_starts_with($guest_clean_phone, '91') ? $guest_clean_phone : ('91' . $guest_clean_phone)) . "?text=" . urlencode($wa_msg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Folio & Invoice #<?php echo htmlspecialchars($booking['reference_code']); ?> — Food Forest Sanctuary</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #101F15;
            --primary-dark: #09130D;
            --accent: #C5A059;
            --accent-soft: #DFC289;
            --accent-terra: #C26D4D;
            --bg-paper: #FFFFFF;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --border-light: #E2E8F0;
            --border-dark: #CBD5E1;
            --emerald: #059669;
            --amber: #D97706;
            --red: #DC2626;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ECEFEA;
            color: var(--text-dark);
            line-height: 1.5;
            padding: 30px 15px 60px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Non-Print Control Toolbar */
        .bill-toolbar {
            max-width: 900px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: #101F15;
            padding: 14px 22px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border: 1px solid rgba(197, 160, 89, 0.3);
        }

        .bill-toolbar-title {
            color: #FFFFFF;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bill-toolbar-title i {
            color: var(--accent);
        }

        .bill-btn-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .bill-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-gold {
            background: linear-gradient(135deg, #ECC880 0%, #C5A059 50%, #9D7B37 100%);
            color: #101F15;
            box-shadow: 0 2px 8px rgba(197,160,89,0.3);
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, #F5DC9B 0%, #D8B26E 50%, #B59048 100%);
            transform: translateY(-1px);
        }

        .btn-wa {
            background-color: #25D366;
            color: #FFFFFF;
        }

        .btn-wa:hover {
            background-color: #1EBE5D;
        }

        .btn-back {
            background: rgba(255,255,255,0.12);
            color: #FFFFFF;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Printable A4 Paper Container */
        .bill-sheet {
            max-width: 900px;
            margin: 0 auto;
            background: var(--bg-paper);
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid #E2E8F0;
            overflow: hidden;
            position: relative;
        }

        /* Luxury Header Ribbon */
        .bill-sheet::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #101F15 0%, #C5A059 35%, #C26D4D 70%, #101F15 100%);
        }

        .bill-header {
            padding: 36px 44px 24px;
            border-bottom: 2px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
        }

        .brand-emblem-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .brand-emblem {
            width: 36px;
            height: 36px;
            background: #101F15;
            color: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            border: 1px solid var(--accent);
        }

        .brand-name {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 2px;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 2.5px;
            color: var(--accent-terra);
            text-transform: uppercase;
            display: block;
        }

        .brand-address {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-top: 6px;
        }

        .invoice-badge-box {
            text-align: right;
        }

        .invoice-type-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }

        .status-pill {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-paid {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .status-partial {
            background: #FFFBEB;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .status-unpaid {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .status-inhouse {
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }

        .invoice-meta-table {
            margin-top: 10px;
            font-size: 12.5px;
            color: var(--text-muted);
            margin-left: auto;
        }

        .invoice-meta-table td {
            padding: 2px 4px;
        }

        .invoice-meta-table .meta-val {
            font-weight: 700;
            color: var(--text-dark);
            font-family: monospace;
            font-size: 13px;
        }

        /* Details Grid: Guest & Stay Itinerary */
        .bill-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 24px 44px;
            background: #F8FAF9;
            border-bottom: 1px solid var(--border-light);
        }

        .details-panel-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 4px;
        }

        .details-panel-title i {
            color: var(--accent);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 13px;
        }

        .detail-row .label {
            color: var(--text-muted);
        }

        .detail-row .val {
            font-weight: 600;
            color: var(--text-dark);
            text-align: right;
        }

        /* Itemized Billing Sections */
        .bill-body {
            padding: 28px 44px;
        }

        .section-heading {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.5px;
            margin: 20px 0 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #CBD5E1;
            padding-bottom: 6px;
        }

        .section-heading:first-child {
            margin-top: 0;
        }

        .section-heading .sec-tag {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 11px;
            font-weight: 700;
            color: #8C6615;
            background: rgba(197, 160, 89, 0.15);
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .bill-table th {
            background-color: #F1F5F3;
            color: var(--primary);
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        .bill-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: top;
        }

        .bill-table tr:last-child td {
            border-bottom: 1px solid #E2E8F0;
        }

        .table-dish-title strong {
            display: block;
            font-size: 13.5px;
            color: var(--primary);
        }

        .table-dish-sub {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .table-inclusions {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }

        .inc-pill {
            font-size: 10.5px;
            background: #F8FAFC;
            color: #475569;
            padding: 1px 6px;
            border-radius: 3px;
            border: 1px solid #E2E8F0;
        }

        /* Financial Calculation Footer Summary */
        .bill-footer-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 28px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px solid var(--border-light);
        }

        .payment-instructions-card {
            background: #F8FAF9;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 16px 20px;
            font-size: 12.5px;
        }

        .payment-card-title {
            font-weight: 700;
            color: var(--primary);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .payment-card-title i {
            color: var(--accent);
        }

        .charges-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .charges-summary-table td {
            padding: 6px 0;
            border-bottom: 1px dashed #E2E8F0;
        }

        .charges-summary-table .amount-cell {
            text-align: right;
            font-weight: 600;
            font-family: monospace;
            font-size: 13.5px;
        }

        .grand-total-row td {
            padding-top: 10px;
            border-bottom: none;
            border-top: 2px solid var(--primary);
        }

        .grand-total-label {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .grand-total-val {
            font-family: 'Cinzel', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
        }

        .balance-due-row {
            background: #FEF2F2;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 8px;
            border: 1px solid #FECACA;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .balance-due-row.settled {
            background: #ECFDF5;
            border-color: #A7F3D0;
        }

        .balance-due-label {
            font-weight: 700;
            font-size: 13px;
            color: #991B1B;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .balance-due-row.settled .balance-due-label {
            color: #065F46;
        }

        .balance-due-val {
            font-family: 'Cinzel', serif;
            font-size: 18px;
            font-weight: 700;
            color: #991B1B;
        }

        .balance-due-row.settled .balance-due-val {
            color: #065F46;
        }

        /* Signatures & Bottom Policies */
        .bill-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 36px;
            padding-top: 24px;
            border-top: 1px solid var(--border-light);
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            width: 80%;
            margin: 40px auto 6px;
            border-top: 1px solid #94A3B8;
        }

        .sig-label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .bill-footer-notes {
            background: #F8FAF9;
            border-top: 1px solid var(--border-light);
            padding: 20px 44px;
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.5;
            text-align: center;
        }

        /* PRINT MEDIA QUERIES (OPTIMIZED FOR A4) */
        @media print {
            body {
                background: #FFFFFF !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
            }

            .bill-toolbar {
                display: none !important;
            }

            .bill-sheet {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }

            .bill-header, .bill-body, .bill-footer-notes {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .bill-details-grid {
                padding-left: 20px !important;
                padding-right: 20px !important;
                background: #FAFAFA !important;
            }

            .payment-instructions-card {
                background: #FAFAFA !important;
            }
        }

        @media (max-width: 768px) {
            .bill-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 24px 20px;
            }
            .invoice-badge-box {
                text-align: left;
            }
            .invoice-meta-table {
                margin-left: 0;
            }
            .bill-details-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            .bill-body {
                padding: 20px;
            }
            .bill-footer-grid {
                grid-template-columns: 1fr;
            }
            .bill-signatures {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Non-Print Toolbar -->
    <div class="bill-toolbar">
        <div class="bill-toolbar-title">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Guest Folio & Tax Invoice Preview — #<?php echo htmlspecialchars($booking['reference_code']); ?></span>
        </div>
        <div class="bill-btn-group">
            <a href="billing.php" class="bill-btn btn-back">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Billing Hub</span>
            </a>
            <?php if (!empty($booking['guest_phone'])): ?>
                <a href="<?php echo $wa_url; ?>" target="_blank" class="bill-btn btn-wa" title="Send itemized folio via WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>Share on WhatsApp</span>
                </a>
            <?php endif; ?>
            <button type="button" onclick="window.print()" class="bill-btn btn-gold" title="Print or save as PDF">
                <i class="fa-solid fa-print"></i>
                <span>Print Folio (A4)</span>
            </button>
        </div>
    </div>

    <!-- Printable A4 Folio Sheet -->
    <div class="bill-sheet">
        
        <!-- Header -->
        <header class="bill-header">
            <div>
                <div class="brand-emblem-wrap">
                    <div class="brand-emblem">
                        <i class="fa-solid fa-seedling"></i>
                    </div>
                    <div>
                        <div class="brand-name">FOOD FOREST</div>
                        <span class="brand-sub">KANTHALLOOR • HIGH-RANGE SANCTUARY</span>
                    </div>
                </div>
                <div class="brand-address">
                    Kanthalloor High Range • 1,600m Elevation<br>
                    Marayoor Valley, Idukki District, Kerala — 685620<br>
                    Concierge Hotline: <?php echo htmlspecialchars($concierge_phone); ?> | Email: concierge@foodforestkanthalloor.com<br>
                    <strong>GSTIN / Tax ID:</strong> <?php echo htmlspecialchars($gst_number); ?>
                </div>
            </div>

            <div class="invoice-badge-box">
                <div class="invoice-type-title">GUEST FOLIO & INVOICE</div>
                <div style="margin-bottom: 6px;">
                    <?php if ($p['balance_due'] <= 0): ?>
                        <span class="status-pill status-paid"><i class="fa-solid fa-circle-check"></i> FULLY SETTLED</span>
                    <?php elseif ($p['advance_paid'] > 0): ?>
                        <span class="status-pill status-partial"><i class="fa-solid fa-circle-half-stroke"></i> PARTIALLY PAID</span>
                    <?php else: ?>
                        <span class="status-pill status-unpaid"><i class="fa-solid fa-circle-exclamation"></i> PAYMENT PENDING</span>
                    <?php endif; ?>
                    <span class="status-pill status-inhouse" style="margin-left: 4px;"><?php echo strtoupper(htmlspecialchars($booking['status'])); ?></span>
                </div>
                <table class="invoice-meta-table">
                    <tr>
                        <td>Invoice No:</td>
                        <td class="meta-val"><?php echo htmlspecialchars($invoice_no); ?></td>
                    </tr>
                    <tr>
                        <td>Booking Ref:</td>
                        <td class="meta-val">#<?php echo htmlspecialchars($booking['reference_code']); ?></td>
                    </tr>
                    <tr>
                        <td>Issued Date:</td>
                        <td><?php echo $bill_date; ?></td>
                    </tr>
                </table>
            </div>
        </header>

        <!-- Guest & Itinerary Grid -->
        <div class="bill-details-grid">
            <!-- Guest Particulars -->
            <div>
                <h3 class="details-panel-title"><i class="fa-solid fa-user-check"></i> Guest Particulars</h3>
                <div class="detail-row">
                    <span class="label">Guest Name:</span>
                    <span class="val"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Phone / WhatsApp:</span>
                    <span class="val"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Email Address:</span>
                    <span class="val"><?php echo htmlspecialchars($booking['guest_email'] ?: 'On File'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Account Status:</span>
                    <span class="val"><?php echo !empty($booking['is_guest']) ? 'Guest Pass (#Token ' . htmlspecialchars($booking['guest_access_token'] ?: 'Direct') . ')' : 'Registered Sanctuary Member'; ?></span>
                </div>
            </div>

            <!-- Stay Itinerary & Property Info -->
            <div>
                <h3 class="details-panel-title"><i class="fa-solid fa-calendar-check"></i> Stay Itinerary & Villa</h3>
                <div class="detail-row">
                    <span class="label">Sanctuary Villa:</span>
                    <span class="val"><?php echo htmlspecialchars($booking['room_title']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Check-In:</span>
                    <span class="val"><?php echo date('D, d M Y', strtotime($booking['checkin_date'])); ?> (02:00 PM)</span>
                </div>
                <div class="detail-row">
                    <span class="label">Check-Out:</span>
                    <span class="val"><?php echo date('D, d M Y', strtotime($booking['checkout_date'])); ?> (11:00 AM)</span>
                </div>
                <div class="detail-row">
                    <span class="label">Total Duration & Pax:</span>
                    <span class="val"><?php echo $p['nights']; ?> Night(s) • <?php echo $p['adults_count']; ?> Adults<?php echo $p['kids_count'] > 0 ? ', ' . $p['kids_count'] . ' Kids' : ''; ?> (<?php echo (int)$booking['guests_count']; ?> Pax)</span>
                </div>
            </div>
        </div>

        <!-- Itemized Body Sections -->
        <div class="bill-body">

            <!-- 1. Accommodation / Room Tariff -->
            <div class="section-heading">
                <span>1. Accommodation & Villa Tariff</span>
                <span class="sec-tag"><?php echo $p['nights']; ?> Night<?php echo $p['nights'] > 1 ? 's' : ''; ?> Stay</span>
            </div>
            <table class="bill-table">
                <thead>
                    <tr>
                        <th>Particulars / Description</th>
                        <th style="text-align: center;">Occupancy / Specs</th>
                        <th style="text-align: right;">Nightly Rate</th>
                        <th style="text-align: center;">Nights</th>
                        <th style="text-align: right;">Amount (<?php echo $currency; ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($booking['room_title']); ?></strong>
                            <div class="table-dish-sub"><?php echo htmlspecialchars($booking['room_elevation'] ?? 'Kanthalloor Fruit Forest'); ?> • <?php echo htmlspecialchars(ucfirst($booking['room_stay_type'] ?? 'Sanctuary Stay')); ?></div>
                        </td>
                        <td style="text-align: center; color: var(--text-muted);">
                            <?php echo $p['adults_count']; ?> Adults<?php echo $p['kids_count'] > 0 ? ', ' . $p['kids_count'] . ' Kids' : ''; ?>
                        </td>
                        <td style="text-align: right; color: var(--text-muted);">
                            <?php echo $currency . number_format($p['rate_per_night'], 2); ?>
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            <?php echo $p['nights']; ?>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--primary);">
                            <?php echo $currency . number_format($p['room_base_total'], 2); ?>
                        </td>
                    </tr>
                    <?php if ($p['extra_guest_total'] > 0): ?>
                        <tr>
                            <td>
                                <strong>Extra Guest / Child Occupancy Tariff</strong>
                                <div class="table-dish-sub">
                                    <?php echo $p['extra_adults'] > 0 ? $p['extra_adults'] . ' Extra Adult(s)' : ''; ?>
                                    <?php echo ($p['extra_adults'] > 0 && $p['extra_kids'] > 0) ? ' & ' : ''; ?>
                                    <?php echo $p['extra_kids'] > 0 ? $p['extra_kids'] . ' Extra Child(ren)' : ''; ?>
                                </div>
                            </td>
                            <td style="text-align: center; color: var(--text-muted);">-</td>
                            <td style="text-align: right; color: var(--text-muted);">-</td>
                            <td style="text-align: center; font-weight: 600;"><?php echo $p['nights']; ?></td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                <?php echo $currency . number_format($p['extra_guest_total'], 2); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- 2. What They Ate (Curated Gastronomy) -->
            <div class="section-heading">
                <span>2. Gastronomy & Curated Estate Meals</span>
                <span class="sec-tag"><?php echo count($p['food_items']); ?> Set(s) Ordered</span>
            </div>
            <?php if (!empty($p['food_items'])): ?>
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Meal Ritual / Dish Set</th>
                            <th>Category</th>
                            <th style="text-align: center;">Sets / Qty</th>
                            <th style="text-align: right;">Unit Price</th>
                            <th style="text-align: right;">Amount (<?php echo $currency; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($p['food_items'] as $fi): ?>
                            <tr>
                                <td class="table-dish-title">
                                    <strong><?php echo htmlspecialchars($fi['heading'] ?? 'Custom Meal Set'); ?></strong>
                                    <?php if (!empty($fi['subtitle'])): ?>
                                        <div class="table-dish-sub"><?php echo htmlspecialchars($fi['subtitle']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($fi['inclusions']) && is_array($fi['inclusions'])): ?>
                                        <div class="table-inclusions">
                                            <?php foreach ($fi['inclusions'] as $inc): ?>
                                                <span class="inc-pill">✓ <?php echo htmlspecialchars($inc); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--primary); background: #F1F5F9; padding: 2px 7px; border-radius: 4px;">
                                        <?php echo htmlspecialchars($fi['category'] ?? 'Dining'); ?>
                                    </span>
                                </td>
                                <td style="text-align: center; font-weight: 600;">
                                    <?php echo (int)($fi['quantity'] ?? 1); ?>
                                </td>
                                <td style="text-align: right; color: var(--text-muted);">
                                    <?php echo $currency . number_format((float)($fi['price'] ?? 0), 2); ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                    <?php echo $currency . number_format((float)($fi['subtotal'] ?? (($fi['price'] ?? 0) * ($fi['quantity'] ?? 1))), 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="background: #F8FAF9; border: 1px dashed #CBD5E1; padding: 12px 18px; border-radius: 6px; font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px;">
                    <i class="fa-solid fa-utensils" style="color: var(--accent); margin-right: 6px;"></i>
                    No pre-booked dining sets on record. Meals ordered on arrival are settled per daily concierge ticket.
                </div>
            <?php endif; ?>

            <!-- 3. What They Did (Sanctuary Experiences & Activities) -->
            <div class="section-heading">
                <span>3. Sanctuary Experiences & Curated Activities</span>
                <span class="sec-tag"><?php echo count($p['activities']); ?> Activity(s)</span>
            </div>
            <?php if (!empty($p['activities'])): ?>
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Experience / Activity Title</th>
                            <th>Timing / Details</th>
                            <th style="text-align: center;">Pax / Qty</th>
                            <th style="text-align: right;">Unit Rate</th>
                            <th style="text-align: right;">Amount (<?php echo $currency; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($p['activities'] as $act): ?>
                            <tr>
                                <td class="table-dish-title">
                                    <strong><i class="fa-solid fa-feather" style="color: var(--accent); font-size: 11px; margin-right: 4px;"></i> <?php echo htmlspecialchars($act['title'] ?? 'Sanctuary Experience'); ?></strong>
                                </td>
                                <td style="color: var(--text-muted); font-size: 12px;">
                                    <?php echo htmlspecialchars($act['timing'] ?? 'Curated Schedule'); ?>
                                </td>
                                <td style="text-align: center; font-weight: 600;">
                                    <?php echo (int)($act['quantity'] ?? 1); ?>
                                </td>
                                <td style="text-align: right; color: var(--text-muted);">
                                    <?php echo ((float)($act['price'] ?? 0) > 0) ? $currency . number_format((float)$act['price'], 2) : '<span style="color: #059669; font-weight: 600;">Included</span>'; ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                    <?php echo ((float)($act['subtotal'] ?? 0) > 0) ? $currency . number_format((float)$act['subtotal'], 2) : '<span style="color: #059669; font-weight: 600;">₹0.00</span>'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="background: #F8FAF9; border: 1px dashed #CBD5E1; padding: 12px 18px; border-radius: 6px; font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px;">
                    <i class="fa-solid fa-compass" style="color: var(--accent); margin-right: 6px;"></i>
                    Standard complimentary estate activities included in stay (Orchard Walks, Bird Watching & Morning Sunrise Rituals).
                </div>
            <?php endif; ?>

            <!-- 4. Additional Services / Custom Items (if any) -->
            <?php if (!empty($p['custom_items']) || $p['extra_charges'] > 0): ?>
                <div class="section-heading">
                    <span>4. Extra Bespoke Services & Incidentals</span>
                    <span class="sec-tag">Custom Charges</span>
                </div>
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Service Description</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Rate</th>
                            <th style="text-align: right;">Amount (<?php echo $currency; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($p['custom_items'] as $ci): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ci['title'] ?? 'Custom Service'); ?></strong></td>
                                <td style="text-align: center; font-weight: 600;"><?php echo (int)($ci['quantity'] ?? 1); ?></td>
                                <td style="text-align: right; color: var(--text-muted);"><?php echo $currency . number_format((float)($ci['price'] ?? 0), 2); ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);"><?php echo $currency . number_format((float)($ci['subtotal'] ?? 0), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($p['extra_charges'] > 0 && empty($p['custom_items'])): ?>
                            <tr>
                                <td><strong>Additional Concierge Services & Incidental Charges</strong></td>
                                <td style="text-align: center; font-weight: 600;">1</td>
                                <td style="text-align: right; color: var(--text-muted);"><?php echo $currency . number_format($p['extra_charges'], 2); ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);"><?php echo $currency . number_format($p['extra_charges'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Financial Calculation & Bank Instructions -->
            <div class="bill-footer-grid">
                
                <!-- Left: Settlement Notes & UPI / Bank -->
                <div>
                    <div class="payment-instructions-card">
                        <div class="payment-card-title">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>Settlement & Bank Details</span>
                        </div>
                        <div style="margin-bottom: 6px;">
                            <strong>UPI VPA ID:</strong> <span style="font-family: monospace; font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($upi_id); ?></span>
                        </div>
                        <div style="margin-bottom: 6px;">
                            <strong>Bank Transfer:</strong> <?php echo htmlspecialchars($bank_details); ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 11.5px; margin-top: 8px;">
                            <i class="fa-solid fa-receipt"></i> Official receipts are generated digitally. For invoices with company GST number, please notify the concierge prior to checkout.
                        </div>
                    </div>
                </div>

                <!-- Right: Financial Itemization Totals -->
                <div>
                    <table class="charges-summary-table">
                        <tr>
                            <td style="color: var(--text-muted);">Villa Tariff Total:</td>
                            <td class="amount-cell"><?php echo $currency . number_format($p['room_amount'], 2); ?></td>
                        </tr>
                        <?php if ($p['food_total'] > 0): ?>
                            <tr>
                                <td style="color: var(--text-muted);">Gastronomy & Meals Subtotal:</td>
                                <td class="amount-cell"><?php echo $currency . number_format($p['food_total'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($p['activities_total'] > 0): ?>
                            <tr>
                                <td style="color: var(--text-muted);">Sanctuary Experiences Subtotal:</td>
                                <td class="amount-cell"><?php echo $currency . number_format($p['activities_total'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($p['custom_total'] > 0 || $p['extra_charges'] > 0): ?>
                            <tr>
                                <td style="color: var(--text-muted);">Extra Services & Charges:</td>
                                <td class="amount-cell"><?php echo $currency . number_format($p['custom_total'] + $p['extra_charges'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($p['discount_amount'] > 0): ?>
                            <tr>
                                <td style="color: #DC2626;">Concession / Courtesy Discount:</td>
                                <td class="amount-cell" style="color: #DC2626;">-<?php echo $currency . number_format($p['discount_amount'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="color: var(--text-muted);">Taxes & Ecological Levies:</td>
                            <td class="amount-cell" style="color: #059669;">
                                <?php echo $p['tax_amount'] > 0 ? $currency . number_format($p['tax_amount'], 2) : 'Inclusive'; ?>
                            </td>
                        </tr>
                        <tr class="grand-total-row">
                            <td class="grand-total-label">Grand Total:</td>
                            <td class="grand-total-val"><?php echo $currency . number_format($p['net_total'], 2); ?></td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted); padding-top: 8px;">Advance / Deposit Paid:</td>
                            <td class="amount-cell" style="padding-top: 8px; color: #059669;">
                                <?php echo $currency . number_format($p['advance_paid'], 2); ?>
                                <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">(<?php echo htmlspecialchars(ucfirst($p['payment_method'])); ?>)</span>
                            </td>
                        </tr>
                    </table>

                    <!-- Balance Due Highlight Banner -->
                    <div class="balance-due-row <?php echo ($p['balance_due'] <= 0) ? 'settled' : ''; ?>">
                        <span class="balance-due-label">
                            <i class="fa-solid <?php echo ($p['balance_due'] <= 0) ? 'fa-circle-check' : 'fa-hand-holding-dollar'; ?>"></i>
                            <?php echo ($p['balance_due'] <= 0) ? 'Balance Settled (Nil)' : 'Net Balance Payable:'; ?>
                        </span>
                        <span class="balance-due-val">
                            <?php echo $currency . number_format($p['balance_due'], 2); ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Signatures Section -->
            <div class="bill-signatures">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Guest Signature & Date</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">For Food Forest Sanctuary (Authorized Signatory)</div>
                </div>
            </div>

        </div>

        <!-- Footer Etiquette & Policy -->
        <footer class="bill-footer-notes">
            <p><strong>Food Forest Sanctuary Kanthalloor</strong> • An organic, high-altitude regenerative sanctuary rooted in earth, reverence and unhurried time.</p>
            <p style="margin-top: 4px; font-size: 11px; color: #94A3B8;">
                Check-Out Time: 11:00 AM • This is a computer-generated luxury guest invoice and stay folio.
            </p>
        </footer>

    </div>

</body>
</html>
