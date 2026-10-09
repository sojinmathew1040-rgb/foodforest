<?php
// =========================================================================
// Food Forest Sanctuary — Luxury Custom Tax Invoice (Print Engine)
// =========================================================================

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pdo = get_db();
ensure_custom_invoices_table_exists($pdo);

$invoice_id = (int)($_GET['id'] ?? ($_GET['invoice_id'] ?? ($_GET['custom_id'] ?? 0)));
$invoice_ref = trim($_GET['ref'] ?? ($_GET['invoice_no'] ?? ($_GET['custom_ref'] ?? '')));
$token = trim($_GET['token'] ?? '');

if (empty($invoice_id) && empty($invoice_ref)) {
    die("<div style='font-family:sans-serif;padding:50px;text-align:center;color:#101F15;'><h2>Invoice ID or Reference Number Required</h2><p><a href='billing.php' style='color:#C5A059;'>Return to Billing Hub</a></p></div>");
}

$identifier = !empty($invoice_id) ? $invoice_id : $invoice_ref;
$invoice = get_custom_invoice($pdo, $identifier);

if (!$invoice) {
    die("<div style='font-family:sans-serif;padding:50px;text-align:center;color:#101F15;'><h2>Custom Invoice Not Found</h2><p>Could not locate billing data for <strong>" . htmlspecialchars($identifier) . "</strong>.</p><p><a href='billing.php' style='color:#C5A059;'>Return to Billing Hub</a></p></div>");
}

// Authentication verification: Admin session OR valid guest hash token
$is_admin = !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in']);
$expected_token = substr(hash('sha256', (string)$invoice['invoice_no'] . 'ff_sanctuary_folio_secret'), 0, 16);
$is_valid_guest = (!empty($token) && hash_equals($expected_token, $token));

if (!$is_admin && !$is_valid_guest) {
    require_admin_auth();
}

$concierge_phone = get_setting('concierge_phone', '+91 923 456 7890');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');
$currency = get_setting('currency_symbol', '₹');
$estate_gstin = get_setting('gst_number', '32AAECF1234M1Z5');
$bank_account_holder = get_setting('bank_account_holder', 'Food Forest Eco Sanctuary');
$bank_name = get_setting('bank_name', 'State Bank of India');
$bank_branch = get_setting('bank_branch', 'Munnar / Kanthalloor Branch');
$bank_account_number = get_setting('bank_account_number', '40982314981');
$bank_ifsc = get_setting('bank_ifsc', 'SBIN0070123');
$bank_account_type = get_setting('bank_account_type', 'Current Account');
$bank_upi_id = get_setting('bank_upi_id', 'foodforest@upi');
$bank_qr_image = get_setting('bank_qr_image', 'assets/images/foodforest_upi_qr.svg');
$bill_footer_notes = get_setting('bill_footer_notes', 'All payments via UPI, IMPS, or NEFT must be confirmed with transaction ID. For official GST tax queries, notify concierge.');

$show_bank = isset($_GET['show_bank']) ? ($_GET['show_bank'] == '1') : (!empty($invoice['show_bank_details']));
$show_qr = isset($_GET['show_qr']) ? ($_GET['show_qr'] == '1') : (!empty($invoice['show_qr_code']));
$auto_print = isset($_GET['auto_print']) && $_GET['auto_print'] == '1';

$is_b2b = !empty($invoice['gstin']) || !empty($invoice['business_name']);
$is_gst = !empty($invoice['is_gst_bill']);
$bill_date = !empty($invoice['invoice_date']) ? date('d M Y, h:i A', strtotime($invoice['invoice_date'])) : date('d M Y, h:i A', strtotime($invoice['created_at']));

$bill_title_text = $is_b2b ? "TAX INVOICE (B2B REGISTERED)" : "TAX INVOICE — BESPOKE BILL";
$bill_category_badge = $is_b2b ? "🏢 B2B CORPORATE TAX INVOICE" : "🧾 CUSTOM TAX INVOICE";

// Generate Public Digital Folio URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$public_folio_url = $protocol . $host . $base_dir . '/print_custom_bill.php?id=' . $invoice['id'] . '&token=' . $expected_token;

// WhatsApp Message
$guest_clean_phone = preg_replace('/[^0-9]/', '', $invoice['customer_phone']);
$wa_phone_clean = str_starts_with($guest_clean_phone, '91') ? $guest_clean_phone : ('91' . $guest_clean_phone);

$wa_msg = "🌿 *FOOD FOREST SANCTUARY — {$bill_title_text}*\n";
$wa_msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
$wa_msg .= "• *Invoice No*: {$invoice['invoice_no']}\n";
$wa_msg .= "• *Customer*: {$invoice['customer_name']}\n";
if ($is_b2b && !empty($invoice['business_name'])) {
    $wa_msg .= "• *Billed Entity*: {$invoice['business_name']}\n";
}
if ($is_b2b && !empty($invoice['gstin'])) {
    $wa_msg .= "• *Buyer GSTIN*: {$invoice['gstin']}\n";
}
$wa_msg .= "• *Date*: {$bill_date}\n";
$wa_msg .= "─────────────────────\n";
foreach ($invoice['items'] as $it) {
    $it_desc = $it['description'] ?? ($it['desc'] ?? 'Item');
    $it_qty = (float)($it['quantity'] ?? ($it['qty'] ?? 1));
    $it_rate = (float)($it['unit_price'] ?? ($it['rate'] ?? 0));
    $it_tot = (float)($it['total'] ?? ($it_qty * $it_rate));
    $wa_msg .= "• {$it_desc}: {$currency}" . number_format($it_tot, 2) . " ({$it_qty} @ {$currency}" . number_format($it_rate, 2) . ")\n";
}
if ((float)$invoice['discount_amount'] > 0) {
    $wa_msg .= "• Concession / Discount: -{$currency}" . number_format($invoice['discount_amount'], 2) . "\n";
}
if ($is_gst && (float)$invoice['tax_amount'] > 0) {
    $wa_msg .= "• GST ({$invoice['gst_percentage']}%): +{$currency}" . number_format($invoice['tax_amount'], 2) . "\n";
}
$wa_msg .= "─────────────────────\n";
$wa_msg .= "*GRAND TOTAL*: {$currency}" . number_format($invoice['grand_total'], 2) . "\n";
$wa_msg .= "• Paid / Advance: {$currency}" . number_format($invoice['advance_paid'], 2) . "\n";
$wa_msg .= "*BALANCE DUE*: {$currency}" . number_format($invoice['balance_due'], 2) . "\n";
$wa_msg .= "• Status: " . strtoupper($invoice['payment_status']) . "\n";
$wa_msg .= "─────────────────────\n";
$wa_msg .= "📄 *Official PDF & Digital Invoice*:\n{$public_folio_url}\n";
$wa_msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
$wa_msg .= "Thank you for visiting Food Forest Sanctuary, Kanthalloor!";

$pdf_file_name = 'FoodForest_Invoice_' . $invoice['invoice_no'] . '.pdf';
$wa_url = "https://wa.me/" . $wa_phone_clean . "?text=" . urlencode($wa_msg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($bill_title_text); ?> #<?php echo htmlspecialchars($invoice['invoice_no']); ?> — Food Forest Sanctuary</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Client-Side HTML to PDF Engine -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
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

        .btn-pdf {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 50%, #991B1B 100%);
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35);
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, #F87171 0%, #EF4444 50%, #B91C1C 100%);
            transform: translateY(-1px);
        }

        .btn-back {
            background: rgba(255,255,255,0.12);
            color: #FFFFFF;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Printable A4 Sheet */
        .bill-sheet {
            max-width: 900px;
            margin: 0 auto;
            background: var(--bg-paper);
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid var(--border-dark);
            position: relative;
            overflow: hidden;
        }

        .bill-sheet::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #101F15 0%, #C5A059 50%, #101F15 100%);
        }

        /* Sheet Header */
        .bill-header {
            padding: 24px 32px 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1.5px solid var(--border-light);
            gap: 20px;
        }

        .brand-emblem-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .brand-emblem {
            width: 32px;
            height: 32px;
            background: #101F15;
            color: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            border: 1px solid var(--accent);
        }

        .brand-name {
            font-family: 'Cinzel', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 1.5px;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--accent-terra);
            text-transform: uppercase;
            display: block;
        }

        .brand-address {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.4;
            margin-top: 4px;
        }

        .invoice-badge-box {
            text-align: right;
        }

        .invoice-type-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.1;
            margin-bottom: 4px;
            letter-spacing: 0.8px;
        }

        .status-pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
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

        .invoice-meta-table {
            margin-top: 6px;
            font-size: 11.5px;
            color: var(--text-muted);
            margin-left: auto;
        }

        .invoice-meta-table td {
            padding: 1.5px 3px;
        }

        .invoice-meta-table .meta-val {
            font-weight: 700;
            color: var(--text-dark);
            font-family: monospace;
            font-size: 12px;
        }

        /* Details Grid: Customer & B2B Particulars */
        .bill-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 14px 32px;
            background: #F8FAF9;
            border-bottom: 1px solid var(--border-light);
        }

        .details-panel-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 3px;
        }

        .details-panel-title i {
            color: var(--accent);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 2.5px 0;
            font-size: 11.5px;
        }

        .detail-row .label {
            color: var(--text-muted);
            white-space: nowrap;
        }

        .detail-row .val {
            font-weight: 600;
            color: var(--text-dark);
            text-align: right;
            padding-left: 8px;
        }

        /* Itemized Body */
        .bill-body {
            padding: 16px 32px;
        }

        .section-heading {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.5px;
            margin: 0 0 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #CBD5E1;
            padding-bottom: 4px;
        }

        .section-heading .sec-tag {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 10px;
            font-weight: 700;
            color: #8C6615;
            background: rgba(197, 160, 89, 0.15);
            padding: 1px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 11.5px;
        }

        .bill-table th {
            background-color: #F1F5F3;
            color: var(--primary);
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        .bill-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: top;
        }

        .bill-table tr:last-child td {
            border-bottom: 1px solid #E2E8F0;
        }

        /* Financial Calculation Footer Summary */
        .bill-footer-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 20px;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1.5px solid var(--border-light);
        }

        .payment-instructions-card {
            background: #F8FAF9;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 11px;
        }

        .payment-card-title {
            font-weight: 700;
            color: var(--primary);
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 4px;
        }

        .payment-card-title i {
            color: var(--accent);
        }

        .payment-card-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            align-items: start;
        }

        .payment-card-grid.full-bank {
            grid-template-columns: 1fr;
        }

        .qr-box {
            text-align: center;
            background: #FFF;
            padding: 6px;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
        }

        .qr-box img {
            width: 78px;
            height: 78px;
            display: block;
            margin: 0 auto;
        }

        .qr-caption {
            font-size: 8.5px;
            font-weight: 700;
            color: var(--text-dark);
            margin-top: 2px;
        }

        .bank-info-table {
            width: 100%;
            font-size: 10.5px;
            line-height: 1.35;
        }

        .bank-info-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        .bank-info-table .b-lbl {
            color: var(--text-muted);
            width: 80px;
            font-weight: 500;
        }

        .bank-info-table .b-val {
            color: var(--text-dark);
            font-weight: 600;
        }

        .bank-info-table .b-val.mono {
            font-family: monospace;
            font-size: 11px;
            font-weight: 700;
            color: var(--primary);
        }

        .charges-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }

        .charges-summary-table td {
            padding: 3.5px 0;
            border-bottom: 1px dashed #E2E8F0;
        }

        .charges-summary-table .amount-cell {
            text-align: right;
            font-weight: 600;
            font-family: monospace;
            font-size: 12px;
        }

        .grand-total-row td {
            padding-top: 6px;
            padding-bottom: 4px;
            border-bottom: none;
            border-top: 1.5px solid var(--primary);
        }

        .grand-total-label {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
        }

        .grand-total-val {
            font-family: 'Cinzel', serif;
            font-size: 17px;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
        }

        .balance-due-row {
            background: #FEF2F2;
            padding: 5px 10px;
            border-radius: 5px;
            margin-top: 6px;
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
            font-size: 11px;
            color: #991B1B;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .balance-due-row.settled .balance-due-label {
            color: #065F46;
        }

        .balance-due-val {
            font-family: 'Cinzel', serif;
            font-size: 15px;
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
            gap: 24px;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid var(--border-light);
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            width: 70%;
            margin: 20px auto 4px;
            border-top: 1px solid #94A3B8;
        }

        .sig-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .bill-footer-notes {
            background: transparent;
            border-top: 1px solid var(--border-light);
            padding: 8px 32px;
            font-size: 10px;
            color: var(--text-muted);
            text-align: center;
        }

        @page {
            size: A4 portrait;
            margin: 8mm 8mm 8mm 8mm;
        }

        /* PRINT MEDIA QUERIES (OPTIMIZED FOR A4) */
        @media print {
            html, body {
                background: #FFFFFF !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bill-toolbar {
                display: none !important;
            }

            .bill-sheet {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }

            .bill-sheet::before {
                height: 4px !important;
            }

            .bill-header {
                padding: 12px 16px 8px !important;
            }

            .bill-details-grid {
                padding: 8px 16px !important;
            }

            .bill-body {
                padding: 10px 16px !important;
            }

            .bill-footer-grid {
                margin-top: 10px !important;
                padding-top: 8px !important;
            }

            .bill-signatures {
                margin-top: 10px !important;
            }

            .bill-footer-notes {
                padding: 6px 16px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Non-Print Toolbar -->
    <div class="bill-toolbar">
        <div class="bill-toolbar-title">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Bespoke Custom Invoice — #<?php echo htmlspecialchars($invoice['invoice_no']); ?></span>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.08); padding: 5px 12px; border-radius: 6px; border: 1px solid rgba(197,160,89,0.35);">
                <label style="color:#FFF; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; cursor:pointer;" title="Toggle Bank Account details on printable bill">
                    <input type="checkbox" id="toolbar_toggle_bank" <?php echo $show_bank ? 'checked' : ''; ?> onchange="toggleBillElement('bill-bank-card', this.checked);" style="accent-color: #C5A059; width: 15px; height: 15px;">
                    <span>Bank Details</span>
                </label>
                <label style="color:#FFF; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; cursor:pointer;" title="Toggle UPI Payment QR Code on printable bill">
                    <input type="checkbox" id="toolbar_toggle_qr" <?php echo $show_qr ? 'checked' : ''; ?> onchange="toggleBillElement('bill-qr-card', this.checked);" style="accent-color: #C5A059; width: 15px; height: 15px;">
                    <span>UPI QR Code</span>
                </label>
            </div>

            <button type="button" onclick="copyBillBankDetails();" class="bill-btn" style="background: rgba(197,160,89,0.18); color: #DFC289; border: 1px solid rgba(197,160,89,0.4);" title="Copy formatted bank details for guest WhatsApp">
                <i class="fa-solid fa-copy"></i>
                <span id="btn-copy-bank-label">Copy Bank Info</span>
            </button>
        </div>

        <div class="bill-btn-group">
            <?php if ($is_admin): ?>
                <a href="custom_bill.php?id=<?php echo $invoice['id']; ?>" class="bill-btn btn-back" title="Edit this custom invoice">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit Bill</span>
                </a>
                <a href="billing.php?tab=custom_invoices" class="bill-btn btn-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Invoices Hub</span>
                </a>
            <?php endif; ?>
            <?php if (!empty($invoice['customer_phone'])): ?>
                <a href="<?php echo htmlspecialchars($wa_url); ?>" target="_blank" class="bill-btn btn-wa" id="btn-share-wa" title="Share invoice link directly to customer WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>Share on WhatsApp</span>
                </a>
            <?php endif; ?>
            <button type="button" onclick="downloadBillAsPdf();" class="bill-btn btn-pdf" id="btn-download-pdf" title="Download official PDF file to device">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Download PDF</span>
            </button>
            <button type="button" onclick="window.print()" class="bill-btn btn-gold" title="Print or save as A4 PDF">
                <i class="fa-solid fa-print"></i>
                <span>Print Folio (A4)</span>
            </button>
        </div>
    </div>

    <!-- Printable A4 Folio Sheet -->
    <div class="bill-sheet" id="bill-print-sheet">
        
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
                    <strong>GSTIN / Tax ID:</strong> <?php echo htmlspecialchars($estate_gstin); ?>
                </div>
            </div>

            <div class="invoice-badge-box">
                <div style="margin-bottom: 5px;">
                    <span style="display: inline-block; padding: 3px 9px; border-radius: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; background: <?php echo $is_b2b ? '#EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE;' : '#ECFDF5; color: #065F46; border: 1px solid #A7F3D0;'; ?>">
                        <?php echo $bill_category_badge; ?>
                    </span>
                </div>
                <div class="invoice-type-title">
                    <i class="fa-solid fa-file-invoice" style="margin-right: 4px; color: var(--accent);"></i>
                    <?php echo htmlspecialchars($bill_title_text); ?>
                </div>
                <div style="margin-bottom: 6px;">
                    <?php if ((float)$invoice['balance_due'] <= 0): ?>
                        <span class="status-pill status-paid"><i class="fa-solid fa-circle-check"></i> FULLY SETTLED</span>
                    <?php elseif ((float)$invoice['advance_paid'] > 0): ?>
                        <span class="status-pill status-partial"><i class="fa-solid fa-circle-half-stroke"></i> PARTIALLY PAID</span>
                    <?php else: ?>
                        <span class="status-pill status-unpaid"><i class="fa-solid fa-circle-exclamation"></i> PAYMENT PENDING</span>
                    <?php endif; ?>
                </div>
                <table class="invoice-meta-table">
                    <tr>
                        <td>Invoice No:</td>
                        <td class="meta-val"><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                    </tr>
                    <tr>
                        <td>Billing Type:</td>
                        <td style="font-weight: 700; color: #059669;">
                            <?php 
                            $disp_rate = (float)($invoice['gst_percentage'] ?? 0) . '%';
                            echo $is_b2b ? 'B2B GST Tax Invoice (' . $disp_rate . ')' : 'GST Tax Invoice (' . $disp_rate . ')';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Payment Mode:</td>
                        <td style="font-weight: 600; text-transform: uppercase;">
                            <?php echo htmlspecialchars($invoice['payment_method']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Issued Date:</td>
                        <td><?php echo $bill_date; ?></td>
                    </tr>
                </table>
            </div>
        </header>

        <!-- Customer & B2B Billing Grid -->
        <div class="bill-details-grid">
            <!-- Customer Particulars -->
            <div>
                <h3 class="details-panel-title"><i class="fa-solid fa-user-check"></i> Customer Particulars</h3>
                <div class="detail-row">
                    <span class="label">Customer / Guest:</span>
                    <span class="val" style="font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
                </div>
                <?php if (!empty($invoice['customer_phone'])): ?>
                    <div class="detail-row">
                        <span class="label">Phone / WhatsApp:</span>
                        <span class="val"><?php echo htmlspecialchars($invoice['customer_phone']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($invoice['customer_email'])): ?>
                    <div class="detail-row">
                        <span class="label">Email Address:</span>
                        <span class="val"><?php echo htmlspecialchars($invoice['customer_email']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($invoice['customer_address'])): ?>
                    <div class="detail-row">
                        <span class="label">Address / Location:</span>
                        <span class="val" style="font-size: 11px;"><?php echo htmlspecialchars($invoice['customer_address']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- B2B Entity Particulars (If B2B) -->
            <div>
                <h3 class="details-panel-title">
                    <i class="fa-solid <?php echo $is_b2b ? 'fa-building' : 'fa-circle-info'; ?>"></i> 
                    <?php echo $is_b2b ? 'B2B GST Billing Details' : 'Supply & Settlement Note'; ?>
                </h3>
                <?php if ($is_b2b): ?>
                    <?php if (!empty($invoice['business_name'])): ?>
                        <div class="detail-row">
                            <span class="label">Registered Entity:</span>
                            <span class="val" style="font-weight: 700; color: #065F46;"><?php echo htmlspecialchars($invoice['business_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($invoice['gstin'])): ?>
                        <div class="detail-row">
                            <span class="label">Buyer GSTIN:</span>
                            <span class="val" style="font-weight: 700; font-family: monospace; letter-spacing: 0.5px; color: #065F46;"><?php echo htmlspecialchars($invoice['gstin']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($invoice['gst_address'])): ?>
                        <div class="detail-row">
                            <span class="label">GST Address:</span>
                            <span class="val" style="font-size: 11px;"><?php echo nl2br(htmlspecialchars($invoice['gst_address'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="label">Tax Place of Supply:</span>
                        <span class="val"><?php echo ($invoice['gst_type'] === 'inter_state') ? 'Inter-State (IGST Applicable)' : 'Intra-State: Kerala (CGST + SGST)'; ?></span>
                    </div>
                <?php else: ?>
                    <div class="detail-row">
                        <span class="label">Place of Supply:</span>
                        <span class="val">Kerala, State Code: 32</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Invoice Nature:</span>
                        <span class="val">Direct Sanctuary Services / Retail Folio</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Payment Status:</span>
                        <span class="val" style="font-weight: 700; color: <?php echo ((float)$invoice['balance_due'] <= 0) ? '#065F46;' : '#991B1B;'; ?>">
                            <?php echo strtoupper(htmlspecialchars($invoice['payment_status'])); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Itemized Body Sections -->
        <div class="bill-body">
            <div class="section-heading">
                <span>Itemized Goods &amp; Sanctuary Services</span>
                <span class="sec-tag"><?php echo count($invoice['items']); ?> Line Item<?php echo count($invoice['items']) > 1 ? 's' : ''; ?></span>
            </div>

            <table class="bill-table">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;">#</th>
                        <th>Particulars / Description</th>
                        <th style="width: 100px; text-align: center;">SAC / HSN</th>
                        <th style="width: 80px; text-align: center;">Qty (Units)</th>
                        <th style="width: 120px; text-align: right;">Unit Rate (<?php echo $currency; ?>)</th>
                        <th style="width: 130px; text-align: right;">Total Amount (<?php echo $currency; ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['items'] as $it_idx => $item): 
                        $i_num = $it_idx + 1;
                        $i_desc = $item['description'] ?? ($item['desc'] ?? 'Sanctuary Service');
                        $i_sac = $item['sac_code'] ?? ($item['sac'] ?? '996331');
                        $i_qty = (float)($item['quantity'] ?? ($item['qty'] ?? 1));
                        $i_rate = (float)($item['unit_price'] ?? ($item['rate'] ?? 0));
                        $i_tot = (float)($item['total'] ?? ($i_qty * $i_rate));
                    ?>
                        <tr>
                            <td style="text-align: center; color: var(--text-muted); font-size: 11px;">
                                <?php echo $i_num; ?>
                            </td>
                            <td>
                                <strong style="color: var(--primary); font-size: 12px;"><?php echo htmlspecialchars($i_desc); ?></strong>
                            </td>
                            <td style="text-align: center; font-family: monospace; color: var(--text-muted); font-size: 11px;">
                                <?php echo htmlspecialchars($i_sac ?: '—'); ?>
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                <?php echo $i_qty; ?>
                            </td>
                            <td style="text-align: right; color: var(--text-muted);">
                                <?php echo $currency . number_format($i_rate, 2); ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                <?php echo $currency . number_format($i_tot, 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Financial Calculation Footer Summary -->
            <div class="bill-footer-grid">
                
                <!-- Left: Payment Instructions & Bank/QR -->
                <div class="payment-instructions-card" id="bill-bank-card" style="display: <?php echo $show_bank ? 'block' : 'none'; ?>;">
                    <div class="payment-card-title">
                        <span><i class="fa-solid fa-building-columns"></i> Official Bank Settlement Details</span>
                        <span style="font-size: 9.5px; color: var(--text-muted); font-weight: 500;">Direct Deposit / IMPS</span>
                    </div>

                    <div class="payment-card-grid <?php echo !$show_qr ? 'full-bank' : ''; ?>">
                        <?php if ($show_qr): ?>
                            <div class="qr-box" id="bill-qr-card">
                                <img src="../<?php echo htmlspecialchars($bank_qr_image); ?>" alt="Sanctuary UPI QR" onerror="this.src='../assets/images/foodforest_upi_qr.svg';">
                                <div class="qr-caption">SCAN TO PAY UPI</div>
                            </div>
                        <?php endif; ?>

                        <table class="bank-info-table">
                            <tr>
                                <td class="b-lbl">A/C Holder:</td>
                                <td class="b-val"><?php echo htmlspecialchars($bank_account_holder); ?></td>
                            </tr>
                            <tr>
                                <td class="b-lbl">Bank Name:</td>
                                <td class="b-val"><?php echo htmlspecialchars($bank_name); ?> (<?php echo htmlspecialchars($bank_branch); ?>)</td>
                            </tr>
                            <tr>
                                <td class="b-lbl">Account No:</td>
                                <td class="b-val mono"><?php echo htmlspecialchars($bank_account_number); ?></td>
                            </tr>
                            <tr>
                                <td class="b-lbl">IFSC Code:</td>
                                <td class="b-val mono"><?php echo htmlspecialchars($bank_ifsc); ?></td>
                            </tr>
                            <tr>
                                <td class="b-lbl">UPI ID:</td>
                                <td class="b-val mono" style="color: #059669;"><?php echo htmlspecialchars($bank_upi_id); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Right: Financial Summary Charges -->
                <div>
                    <table class="charges-summary-table">
                        <tr>
                            <td>Gross Subtotal:</td>
                            <td class="amount-cell"><?php echo $currency . number_format($invoice['subtotal'], 2); ?></td>
                        </tr>
                        <?php if ((float)$invoice['discount_amount'] > 0): ?>
                            <tr style="color: var(--red);">
                                <td>Concession / Special Discount:</td>
                                <td class="amount-cell">-<?php echo $currency . number_format($invoice['discount_amount'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if ($is_gst): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--primary);">Taxable Value:</td>
                                <td class="amount-cell" style="font-weight: 700; color: var(--primary);"><?php echo $currency . number_format($invoice['taxable_amount'], 2); ?></td>
                            </tr>

                            <?php if ($invoice['gst_type'] === 'inter_state'): ?>
                                <tr>
                                    <td>Integrated GST (IGST <?php echo (float)$invoice['gst_percentage']; ?>%):</td>
                                    <td class="amount-cell">+<?php echo $currency . number_format($invoice['igst_amount'], 2); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td>Central GST (CGST <?php echo $invoice['cgst_percentage']; ?>%):</td>
                                    <td class="amount-cell">+<?php echo $currency . number_format($invoice['cgst_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>State GST (SGST <?php echo $invoice['sgst_percentage']; ?>%):</td>
                                    <td class="amount-cell">+<?php echo $currency . number_format($invoice['sgst_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Grand Total Row -->
                        <tr class="grand-total-row">
                            <td class="grand-total-label">INVOICE GRAND TOTAL:</td>
                            <td class="grand-total-val"><?php echo $currency . number_format($invoice['grand_total'], 2); ?></td>
                        </tr>

                        <!-- Advance Paid Row -->
                        <tr>
                            <td style="color: var(--emerald); font-weight: 600;">Amount Received / Paid:</td>
                            <td class="amount-cell" style="color: var(--emerald); font-weight: 700;"><?php echo $currency . number_format($invoice['advance_paid'], 2); ?></td>
                        </tr>
                    </table>

                    <!-- Balance Due Highlight Box -->
                    <div class="balance-due-row <?php echo ((float)$invoice['balance_due'] <= 0.01) ? 'settled' : ''; ?>">
                        <span class="balance-due-label">
                            <i class="fa-solid <?php echo ((float)$invoice['balance_due'] <= 0.01) ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>" style="margin-right: 4px;"></i>
                            <?php echo ((float)$invoice['balance_due'] <= 0.01) ? 'SETTLEMENT BALANCE DUE:' : 'NET BALANCE DUE:'; ?>
                        </span>
                        <span class="balance-due-val">
                            <?php echo $currency . number_format($invoice['balance_due'], 2); ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Notes & Terms -->
            <?php if (!empty($invoice['notes'])): ?>
                <div style="margin-top: 14px; background: #F8FAF9; border-left: 3px solid var(--accent); padding: 8px 12px; font-size: 10.5px; color: var(--text-dark); line-height: 1.4;">
                    <strong>Remarks / Terms:</strong> <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?>
                </div>
            <?php endif; ?>

            <!-- Signatures Block -->
            <div class="bill-signatures">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Customer / Authorized Signatory</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">For Food Forest Eco Sanctuary (Concierge Desk)</div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer class="bill-footer-notes">
            <?php echo htmlspecialchars($bill_footer_notes); ?><br>
            Computer-generated tax invoice. Registered at Kanthalloor, Kerala. Thank you for your patronage!
        </footer>

    </div>

    <!-- Script for Dynamic Controls & PDF Generation -->
    <script>
    function toggleBillElement(elementId, isVisible) {
        var el = document.getElementById(elementId);
        if (el) {
            el.style.display = isVisible ? 'block' : 'none';
        }
    }

    function copyBillBankDetails() {
        var bankText = `*FOOD FOREST SANCTUARY — BANK DETAILS*\n` +
            `• Account Holder: <?php echo addslashes($bank_account_holder); ?>\n` +
            `• Bank: <?php echo addslashes($bank_name); ?> (<?php echo addslashes($bank_branch); ?>)\n` +
            `• A/C Number: <?php echo addslashes($bank_account_number); ?>\n` +
            `• IFSC: <?php echo addslashes($bank_ifsc); ?>\n` +
            `• UPI ID: <?php echo addslashes($bank_upi_id); ?>\n` +
            `━━━━━━━━━━━━━━━━━━━━\n` +
            `Invoice Ref: <?php echo addslashes($invoice['invoice_no']); ?> | Grand Total: <?php echo $currency . number_format($invoice['grand_total'], 2); ?>`;

        navigator.clipboard.writeText(bankText).then(function() {
            var lbl = document.getElementById('btn-copy-bank-label');
            if (lbl) {
                var orig = lbl.innerText;
                lbl.innerText = 'Copied to Clipboard!';
                setTimeout(function(){ lbl.innerText = orig; }, 2500);
            }
        }).catch(function(err) {
            alert('Bank info:\nA/C: <?php echo $bank_account_number; ?>\nIFSC: <?php echo $bank_ifsc; ?>\nUPI: <?php echo $bank_upi_id; ?>');
        });
    }

    function downloadBillAsPdf() {
        var element = document.getElementById('bill-print-sheet');
        var opt = {
            margin:       [8, 8, 8, 8],
            filename:     '<?php echo $pdf_file_name; ?>',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        var btn = document.getElementById('btn-download-pdf');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Generating PDF...</span>';
            btn.disabled = true;
        }

        html2pdf().set(opt).from(element).save().then(function() {
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> <span>Download PDF</span>';
                btn.disabled = false;
            }
        }).catch(function(err) {
            console.error('PDF error:', err);
            window.print();
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> <span>Download PDF</span>';
                btn.disabled = false;
            }
        });
    }

    <?php if ($auto_print): ?>
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 500);
    });
    <?php endif; ?>
    </script>
</body>
</html>
