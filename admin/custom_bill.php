<?php
// =========================================================================
// Food Forest Sanctuary — Bespoke Custom Tax Invoice & Bill Generator
// =========================================================================

$page_title = 'Custom Bill Generator';
$page_subtitle = 'Generate, customize, and print bespoke customer tax invoices and bills';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
ensure_custom_invoices_table_exists($pdo);

$alert_message = '';
$alert_type = 'success';

$edit_id = (int)($_GET['id'] ?? 0);
$clone_id = (int)($_GET['clone'] ?? 0);
$existing_invoice = null;

if ($edit_id > 0) {
    $existing_invoice = get_custom_invoice($pdo, $edit_id);
    if (!$existing_invoice) {
        $alert_message = "Invoice #{$edit_id} not found.";
        $alert_type = 'error';
        $edit_id = 0;
    }
} elseif ($clone_id > 0) {
    $existing_invoice = get_custom_invoice($pdo, $clone_id);
    if ($existing_invoice) {
        $existing_invoice['id'] = 0;
        $existing_invoice['invoice_no'] = get_next_custom_invoice_number($pdo);
        $existing_invoice['invoice_date'] = date('Y-m-d H:i:s');
    }
}

// Handle Form Submission (Save & Optionally Print)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed. Please refresh and try again.';
        $alert_type = 'error';
    } else {
        $invoice_id = (int)($_POST['invoice_id'] ?? 0);
        $invoice_no = trim($_POST['invoice_no'] ?? '');
        if (empty($invoice_no)) {
            $invoice_no = get_next_custom_invoice_number($pdo);
        }

        $inv_date_raw = trim($_POST['invoice_date'] ?? '');
        $invoice_date = !empty($inv_date_raw) ? date('Y-m-d H:i:s', strtotime($inv_date_raw)) : date('Y-m-d H:i:s');

        // Customer Details
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $customer_address = trim($_POST['customer_address'] ?? '');

        // B2B GST Details
        $is_gst_bill = !empty($_POST['is_gst_bill']) ? 1 : 0;
        $has_b2b_gst = !empty($_POST['has_b2b_gst']) ? 1 : 0;
        $gstin = $has_b2b_gst ? strtoupper(trim($_POST['gstin'] ?? '')) : '';
        $business_name = $has_b2b_gst ? trim($_POST['business_name'] ?? '') : '';
        $gst_address = $has_b2b_gst ? trim($_POST['gst_address'] ?? '') : '';
        $gst_type = in_array($_POST['gst_type'] ?? '', ['intra_state', 'inter_state']) ? $_POST['gst_type'] : 'intra_state';

        // Process Dynamic Line Items
        $items = [];
        $subtotal = 0.00;

        if (!empty($_POST['item_desc']) && is_array($_POST['item_desc'])) {
            foreach ($_POST['item_desc'] as $idx => $desc) {
                $desc = trim($desc);
                if (empty($desc)) continue;

                $sac = trim($_POST['item_sac'][$idx] ?? '');
                $qty = max(0.01, (float)($_POST['item_qty'][$idx] ?? 1));
                $rate = max(0, (float)($_POST['item_rate'][$idx] ?? 0));
                $line_total = round($qty * $rate, 2);
                $subtotal += $line_total;

                $items[] = [
                    'description' => $desc,
                    'sac_code' => $sac,
                    'quantity' => $qty,
                    'unit_price' => $rate,
                    'total' => $line_total
                ];
            }
        }

        if (empty($customer_name)) {
            $alert_message = 'Please enter the Customer / Guest Name.';
            $alert_type = 'error';
        } elseif (empty($items)) {
            $alert_message = 'Please add at least one line item with description and amount.';
            $alert_type = 'error';
        } else {
            // Financials Calculation
            $discount_amount = max(0, (float)($_POST['discount_amount'] ?? 0));
            $taxable_amount = max(0, $subtotal - $discount_amount);

            $gst_percentage = max(0, (float)($_POST['gst_percentage'] ?? 5.00));
            $tax_amount = round($taxable_amount * ($gst_percentage / 100), 2);

            if ($gst_type === 'intra_state') {
                $cgst_amount = round($tax_amount / 2, 2);
                $sgst_amount = round($tax_amount - $cgst_amount, 2);
                $igst_amount = 0.00;
            } else {
                $cgst_amount = 0.00;
                $sgst_amount = 0.00;
                $igst_amount = $tax_amount;
            }

            $grand_total = round($taxable_amount + $tax_amount, 2);
            $advance_paid = max(0, (float)($_POST['advance_paid'] ?? $grand_total));
            $balance_due = max(0, $grand_total - $advance_paid);

            $payment_method = trim($_POST['payment_method'] ?? 'cash');
            $payment_status = trim($_POST['payment_status'] ?? 'paid');
            if ($balance_due <= 0 && $grand_total > 0) {
                $payment_status = 'paid';
            } elseif ($advance_paid > 0 && $balance_due > 0) {
                $payment_status = 'partial';
            } elseif ($advance_paid <= 0) {
                $payment_status = 'unpaid';
            }

            $notes = trim($_POST['notes'] ?? '');
            $show_bank_details = !empty($_POST['show_bank_details']) ? 1 : 0;
            $show_qr_code = !empty($_POST['show_qr_code']) ? 1 : 0;
            $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);

            if ($invoice_id > 0) {
                // Update Existing Invoice
                $stmt = $pdo->prepare("UPDATE custom_invoices SET 
                    invoice_no = ?, invoice_date = ?, customer_name = ?, customer_phone = ?, customer_email = ?, customer_address = ?,
                    is_gst_bill = ?, gstin = ?, business_name = ?, gst_address = ?, items_json = ?,
                    subtotal = ?, discount_amount = ?, taxable_amount = ?, gst_percentage = ?, gst_type = ?,
                    cgst_amount = ?, sgst_amount = ?, igst_amount = ?, tax_amount = ?, grand_total = ?,
                    advance_paid = ?, balance_due = ?, payment_method = ?, payment_status = ?, notes = ?,
                    show_bank_details = ?, show_qr_code = ?
                    WHERE id = ?");
                $stmt->execute([
                    $invoice_no, $invoice_date, $customer_name, $customer_phone, $customer_email, $customer_address,
                    $is_gst_bill, $gstin, $business_name, $gst_address, $items_json,
                    $subtotal, $discount_amount, $taxable_amount, $gst_percentage, $gst_type,
                    $cgst_amount, $sgst_amount, $igst_amount, $tax_amount, $grand_total,
                    $advance_paid, $balance_due, $payment_method, $payment_status, $notes,
                    $show_bank_details, $show_qr_code,
                    $invoice_id
                ]);
                $saved_id = $invoice_id;
            } else {
                // Insert New Custom Invoice
                $stmt = $pdo->prepare("INSERT INTO custom_invoices (
                    invoice_no, invoice_date, customer_name, customer_phone, customer_email, customer_address,
                    is_gst_bill, gstin, business_name, gst_address, items_json,
                    subtotal, discount_amount, taxable_amount, gst_percentage, gst_type,
                    cgst_amount, sgst_amount, igst_amount, tax_amount, grand_total,
                    advance_paid, balance_due, payment_method, payment_status, notes,
                    show_bank_details, show_qr_code
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $invoice_no, $invoice_date, $customer_name, $customer_phone, $customer_email, $customer_address,
                    $is_gst_bill, $gstin, $business_name, $gst_address, $items_json,
                    $subtotal, $discount_amount, $taxable_amount, $gst_percentage, $gst_type,
                    $cgst_amount, $sgst_amount, $igst_amount, $tax_amount, $grand_total,
                    $advance_paid, $balance_due, $payment_method, $payment_status, $notes,
                    $show_bank_details, $show_qr_code
                ]);
                $saved_id = (int)$pdo->lastInsertId();
            }

            $submit_action = $_POST['submit_action'] ?? 'save';
            if ($submit_action === 'save_and_print') {
                echo "<script>window.open('print_custom_bill.php?id=" . $saved_id . "&auto_print=1', '_blank'); window.location.href='billing.php?tab=custom_invoices&msg=saved';</script>";
                exit;
            } else {
                header("Location: billing.php?tab=custom_invoices&msg=saved");
                exit;
            }
        }
    }
}

// Prepare Form Initial Values
$val_id = $existing_invoice['id'] ?? 0;
$val_inv_no = $existing_invoice['invoice_no'] ?? get_next_custom_invoice_number($pdo);
$val_date = !empty($existing_invoice['invoice_date']) ? date('Y-m-d\TH:i', strtotime($existing_invoice['invoice_date'])) : date('Y-m-d\TH:i');
$val_cust_name = $existing_invoice['customer_name'] ?? '';
$val_cust_phone = $existing_invoice['customer_phone'] ?? '';
$val_cust_email = $existing_invoice['customer_email'] ?? '';
$val_cust_address = $existing_invoice['customer_address'] ?? '';
$val_is_gst = isset($existing_invoice['is_gst_bill']) ? (int)$existing_invoice['is_gst_bill'] : 1;
$val_has_b2b = !empty($existing_invoice['gstin']) || !empty($existing_invoice['business_name']);
$val_gstin = $existing_invoice['gstin'] ?? '';
$val_biz_name = $existing_invoice['business_name'] ?? '';
$val_gst_address = $existing_invoice['gst_address'] ?? '';
$val_gst_type = $existing_invoice['gst_type'] ?? 'intra_state';
$val_gst_pct = isset($existing_invoice['gst_percentage']) ? (float)$existing_invoice['gst_percentage'] : (float)get_setting('gst_rate_percentage', 5.00);
$val_discount = isset($existing_invoice['discount_amount']) ? (float)$existing_invoice['discount_amount'] : 0.00;
$val_advance = isset($existing_invoice['advance_paid']) ? (float)$existing_invoice['advance_paid'] : 0.00;
$val_method = $existing_invoice['payment_method'] ?? 'cash';
$val_status = $existing_invoice['payment_status'] ?? 'paid';
$val_notes = $existing_invoice['notes'] ?? 'Thank you for choosing Food Forest Sanctuary, Kanthalloor.';
$val_show_bank = isset($existing_invoice['show_bank_details']) ? (int)$existing_invoice['show_bank_details'] : 1;
$val_show_qr = isset($existing_invoice['show_qr_code']) ? (int)$existing_invoice['show_qr_code'] : 1;
$val_items = $existing_invoice['items'] ?? [];

if (empty($val_items)) {
    $val_items = [
        ['description' => '', 'sac_code' => '996331', 'quantity' => 1, 'unit_price' => 0, 'total' => 0]
    ];
}

$currency = get_setting('currency_symbol', '₹');
?>

<div class="adm-content-wrapper">

    <!-- Header Alert Notification -->
    <?php if (!empty($alert_message)): ?>
        <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 24px;">
            <i class="fa-solid <?php echo ($alert_type === 'success') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
            <span><?php echo htmlspecialchars($alert_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb & Top Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 14px;">
        <div>
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--adm-gold); font-weight: 700; margin-bottom: 4px;">
                <a href="billing.php" style="color: var(--adm-gold); text-decoration: none;">Billing &amp; Invoices Hub</a> / <span>Custom Bill Generator</span>
            </div>
            <h1 style="font-family: var(--adm-font-title); font-size: 22px; color: #FFFFFF; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-file-circle-plus" style="color: var(--adm-gold);"></i>
                <span><?php echo ($val_id > 0) ? 'Edit Custom Tax Invoice #' . htmlspecialchars($val_inv_no) : 'Bespoke Custom Bill &amp; Tax Invoice'; ?></span>
            </h1>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="billing.php?tab=custom_invoices" class="adm-btn-action" style="padding: 8px 16px; font-size: 12.5px; background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); border: 1px solid rgba(255,255,255,0.15); text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Invoices Hub</span>
            </a>
            <?php if ($val_id > 0): ?>
                <a href="print_custom_bill.php?id=<?php echo $val_id; ?>" target="_blank" class="adm-btn-action gold" style="padding: 8px 16px; font-size: 12.5px; text-decoration: none;">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Folio Now</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Form -->
    <form action="custom_bill.php<?php echo ($val_id > 0) ? '?id=' . $val_id : ''; ?>" method="POST" id="customBillForm">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="invoice_id" value="<?php echo $val_id; ?>">
        <input type="hidden" name="submit_action" id="submitActionInput" value="save">

        <div style="display: grid; grid-template-columns: 2.1fr 1fr; gap: 24px; align-items: start;">
            
            <!-- LEFT MAIN COLUMN -->
            <div style="display: flex; flex-direction: column; gap: 22px;">

                <!-- CARD 1: Customer & GST Billing Details -->
                <div class="adm-card" style="background: rgba(11, 25, 16, 0.95); border: 1px solid rgba(197, 160, 89, 0.28); border-radius: 12px; overflow: hidden;">
                    <div style="padding: 16px 20px; background: rgba(0,0,0,0.35); border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="adm-setting-card-icon gold" style="width: 32px; height: 32px; font-size: 13px;">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div>
                                <h3 style="font-family: var(--adm-font-title); font-size: 14.5px; color: #FFFFFF; margin: 0;">1. Customer &amp; Billing Particulars</h3>
                                <p style="font-size: 11px; color: var(--adm-text-secondary); margin: 2px 0 0;">Enter guest particulars, contact info &amp; optional B2B GST tax credentials</p>
                            </div>
                        </div>
                        <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); font-size: 10.5px;">GUEST / BUYER</span>
                    </div>

                    <div style="padding: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 14px;">
                            <div class="adm-form-group">
                                <label class="adm-form-label" style="font-size: 11.5px; font-weight: 700;">Customer / Guest Full Name <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="customer_name" class="adm-form-control" value="<?php echo htmlspecialchars($val_cust_name); ?>" placeholder="e.g. John Doe / Family Walk-in" required style="font-weight: 600;">
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-form-label" style="font-size: 11.5px; font-weight: 700;">Mobile Phone / WhatsApp</label>
                                <input type="text" name="customer_phone" class="adm-form-control" value="<?php echo htmlspecialchars($val_cust_phone); ?>" placeholder="e.g. +91 9876543210">
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-form-label" style="font-size: 11.5px; font-weight: 700;">Email Address</label>
                                <input type="email" name="customer_email" class="adm-form-control" value="<?php echo htmlspecialchars($val_cust_email); ?>" placeholder="e.g. guest@example.com">
                            </div>
                        </div>

                        <div class="adm-form-group" style="margin-bottom: 16px;">
                            <label class="adm-form-label" style="font-size: 11.5px; font-weight: 700;">Customer General Street / City Address</label>
                            <input type="text" name="customer_address" class="adm-form-control" value="<?php echo htmlspecialchars($val_cust_address); ?>" placeholder="e.g. Cochin, Kerala / Bangalore, Karnataka">
                        </div>

                        <!-- B2B GST Registered Toggle Box -->
                        <div style="background: rgba(6, 17, 10, 0.85); border: 1px solid rgba(46, 204, 113, 0.3); border-radius: 8px; padding: 14px; margin-top: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; color: #FFFFFF; font-weight: 700; font-size: 12.5px;">
                                    <input type="checkbox" name="has_b2b_gst" id="toggle_b2b_gst" value="1" <?php echo $val_has_b2b ? 'checked' : ''; ?> onchange="toggleB2BGstSection(this.checked);" style="width: 17px; height: 17px; accent-color: #2ecc71;">
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-building" style="color: #2ecc71;"></i>
                                        <span>Add Separate Customer GST Details (B2B Tax Invoice)</span>
                                    </span>
                                </label>
                                <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; font-size: 10px;">OFFICIAL GSTIN</span>
                            </div>

                            <!-- Expandable GST Section -->
                            <div id="b2b_gst_container" style="display: <?php echo $val_has_b2b ? 'block' : 'none'; ?>; margin-top: 14px; padding-top: 14px; border-top: 1px dashed rgba(255,255,255,0.1);">
                                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 14px; margin-bottom: 12px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700; color: #2ecc71;">
                                            <i class="fa-solid fa-id-card"></i> Buyer GSTIN / UIN
                                        </label>
                                        <input type="text" name="gstin" id="input_gstin" class="adm-form-control" value="<?php echo htmlspecialchars($val_gstin); ?>" placeholder="e.g. 32AAECF1234M1Z5" maxlength="15" style="text-transform: uppercase; font-family: monospace; font-weight: 700; letter-spacing: 1px;" oninput="this.value = this.value.toUpperCase(); detectGstState(this.value);">
                                        <small style="color: var(--adm-text-secondary); font-size: 10px;">15-digit alphanumeric GSTIN</small>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700; color: #2ecc71;">
                                            <i class="fa-solid fa-briefcase"></i> Registered Company / Trade Name
                                        </label>
                                        <input type="text" name="business_name" class="adm-form-control" value="<?php echo htmlspecialchars($val_biz_name); ?>" placeholder="e.g. Malabar Spice Trails Pvt Ltd">
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px;">
                                    <div class="adm-form-group">
                                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700; color: #2ecc71;">
                                            <i class="fa-solid fa-location-dot"></i> Registered GST Billing Address
                                        </label>
                                        <textarea name="gst_address" rows="2" class="adm-form-control" style="font-size: 12px;" placeholder="Complete registered GST address with pin code..."><?php echo htmlspecialchars($val_gst_address); ?></textarea>
                                    </div>
                                    <div class="adm-form-group">
                                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700; color: #2ecc71;">
                                            <i class="fa-solid fa-scale-balanced"></i> Place of Supply / Tax Mode
                                        </label>
                                        <select name="gst_type" id="select_gst_type" class="adm-form-control" onchange="calculateCustomBill();">
                                            <option value="intra_state" <?php echo ($val_gst_type === 'intra_state') ? 'selected' : ''; ?>>Intra-State: Kerala (CGST + SGST)</option>
                                            <option value="inter_state" <?php echo ($val_gst_type === 'inter_state') ? 'selected' : ''; ?>>Inter-State: Outside Kerala (IGST)</option>
                                        </select>
                                        <small id="gst_mode_hint" style="color: #38bdf8; font-size: 10px; display: block; margin-top: 3px;">Auto-splits 50% CGST + 50% SGST</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: Dynamic Line Items Table (Unit × Amount Calculation) -->
                <div class="adm-card" style="background: rgba(11, 25, 16, 0.95); border: 1px solid rgba(197, 160, 89, 0.28); border-radius: 12px; overflow: hidden;">
                    <div style="padding: 16px 20px; background: rgba(0,0,0,0.35); border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="adm-setting-card-icon gold" style="width: 32px; height: 32px; font-size: 13px;">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div>
                                <h3 style="font-family: var(--adm-font-title); font-size: 14.5px; color: #FFFFFF; margin: 0;">2. Goods &amp; Services Line Items</h3>
                                <p style="font-size: 11px; color: var(--adm-text-secondary); margin: 2px 0 0;">Enter description, unit quantity &amp; amount per unit (Quantity × Rate = Total)</p>
                            </div>
                        </div>
                        <button type="button" class="adm-btn-action gold" style="padding: 6px 14px; font-size: 11.5px;" onclick="addLineItemRow();">
                            <i class="fa-solid fa-plus-circle"></i> + Add Line Item
                        </button>
                    </div>

                    <div style="padding: 18px 20px;">
                        <!-- Quick Add Preset Chips -->
                        <div style="margin-bottom: 16px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); padding: 10px 14px; border-radius: 8px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; display: block; margin-bottom: 6px;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> 1-Click Quick Add Presets:
                            </span>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="quickAddPreset('Special Dining & Gourmet Meals Package', '996331', 1, 1500);">
                                    🍽️ Dining Package (₹1,500)
                                </button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="quickAddPreset('Evening Campfire & Mountain Barbecue', '996331', 1, 2500);">
                                    🔥 Campfire &amp; BBQ (₹2,500)
                                </button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="quickAddPreset('Guided Fruit Orchard & Plantation Safari', '999692', 1, 1200);">
                                    🌿 Plantation Trek (₹1,200)
                                </button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="quickAddPreset('High Canopy Luxury Treehouse Stay (1 Night)', '996311', 1, 14500);">
                                    🏡 Treehouse Stay (₹14,500)
                                </button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="quickAddPreset('Earthen Mudhouse Suite Stay (1 Night)', '996311', 1, 11500);">
                                    🛖 Mudhouse Stay (₹11,500)
                                </button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="quickAddPreset('Airport / Railway Station Transfer (Private Cab)', '996412', 1, 3500);">
                                    🚗 Cab Transfer (₹3,500)
                                </button>
                                <button type="button" class="adm-btn-action" style="padding: 4px 10px; font-size: 11px;" onclick="quickAddPreset('Farm-Fresh Organic Harvest & Jam Box', '080810', 1, 850);">
                                    🧺 Organic Farm Produce (₹850)
                                </button>
                            </div>
                        </div>

                        <!-- Repeater Table -->
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: separate; border-spacing: 0 8px;" id="itemsTable">
                                <thead>
                                    <tr style="color: var(--adm-text-secondary); font-size: 11px; text-transform: uppercase; font-weight: 700; text-align: left;">
                                        <th style="padding: 0 6px; width: 34px;">#</th>
                                        <th style="padding: 0 6px;">Item Description / Service Details *</th>
                                        <th style="padding: 0 6px; width: 110px;">SAC / HSN</th>
                                        <th style="padding: 0 6px; width: 85px; text-align: center;">Qty (Units)</th>
                                        <th style="padding: 0 6px; width: 125px; text-align: right;">Unit Rate (₹)</th>
                                        <th style="padding: 0 6px; width: 135px; text-align: right;">Line Total (₹)</th>
                                        <th style="padding: 0 6px; width: 40px; text-align: center;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <?php foreach ($val_items as $idx => $it): 
                                        $row_desc = $it['description'] ?? '';
                                        $row_sac = $it['sac_code'] ?? '996331';
                                        $row_qty = (float)($it['quantity'] ?? 1);
                                        $row_rate = (float)($it['unit_price'] ?? 0);
                                        $row_total = round($row_qty * $row_rate, 2);
                                        $row_num = $idx + 1;
                                    ?>
                                        <tr class="item-row" data-row-idx="<?php echo $idx; ?>">
                                            <td style="padding: 6px; vertical-align: middle;">
                                                <span class="row-num-badge" style="background: rgba(197, 160, 89, 0.2); color: var(--adm-gold); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                                    <?php echo $row_num; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 6px;">
                                                <input type="text" name="item_desc[]" class="adm-form-control item-desc-input" value="<?php echo htmlspecialchars($row_desc); ?>" placeholder="e.g. Kerala Traditional Sadhya / Dinner Package" required style="font-size: 12.5px;">
                                            </td>
                                            <td style="padding: 6px;">
                                                <input type="text" name="item_sac[]" class="adm-form-control" value="<?php echo htmlspecialchars($row_sac); ?>" placeholder="996331" style="font-size: 12px; font-family: monospace;">
                                            </td>
                                            <td style="padding: 6px;">
                                                <input type="number" step="any" min="0.01" name="item_qty[]" class="adm-form-control item-qty-input" value="<?php echo $row_qty; ?>" required style="text-align: center; font-weight: 700;" oninput="calculateCustomBill();">
                                            </td>
                                            <td style="padding: 6px;">
                                                <input type="number" step="0.5" min="0" name="item_rate[]" class="adm-form-control item-rate-input" value="<?php echo $row_rate; ?>" required style="text-align: right; font-weight: 700; color: #2ecc71;" oninput="calculateCustomBill();">
                                            </td>
                                            <td style="padding: 6px;">
                                                <input type="text" class="adm-form-control item-total-disp" value="<?php echo number_format($row_total, 2, '.', ''); ?>" readonly style="text-align: right; font-weight: 800; font-family: monospace; background: rgba(0,0,0,0.4); color: #FFFFFF;">
                                            </td>
                                            <td style="padding: 6px; text-align: center; vertical-align: middle;">
                                                <button type="button" class="adm-btn-action" style="padding: 6px 10px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.35);" onclick="removeLineItemRow(this);" title="Delete Line Item">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 12px; border-top: 1px dashed rgba(255,255,255,0.08);">
                            <button type="button" class="adm-btn-action" style="padding: 6px 14px; font-size: 11.5px; background: rgba(255,255,255,0.05); color: #FFF; border: 1px solid rgba(255,255,255,0.15);" onclick="addLineItemRow();">
                                <i class="fa-solid fa-plus"></i> Add Another Row
                            </button>
                            <div style="font-size: 12px; color: var(--adm-text-secondary);">
                                Total Items Count: <strong id="items_count_badge" style="color: var(--adm-gold);"><?php echo count($val_items); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: Notes & Folio Terms -->
                <div class="adm-card" style="background: rgba(11, 25, 16, 0.95); border: 1px solid rgba(197, 160, 89, 0.28); border-radius: 12px; overflow: hidden;">
                    <div style="padding: 14px 20px; background: rgba(0,0,0,0.35); border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 13px; font-weight: 700; color: #FFFFFF;">
                            <i class="fa-solid fa-pencil" style="color: var(--adm-gold); margin-right: 6px;"></i> Invoice Remarks &amp; Guest Terms
                        </span>
                    </div>
                    <div style="padding: 16px 20px;">
                        <textarea name="notes" rows="2" class="adm-form-control" style="font-size: 12px; resize: vertical;" placeholder="Special billing remarks, transaction references, or terms printed at the bottom of the folio..."><?php echo htmlspecialchars($val_notes); ?></textarea>
                    </div>
                </div>

            </div>

            <!-- RIGHT STICKY SUMMARY COLUMN -->
            <div style="display: flex; flex-direction: column; gap: 20px; position: sticky; top: 90px;">
                
                <!-- INVOICE META CARD -->
                <div class="adm-card" style="background: rgba(11, 25, 16, 0.95); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 12px; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                        <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 0.8px;">
                            <i class="fa-solid fa-receipt"></i> Invoice Settings
                        </span>
                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; font-size: 9.5px;">LIVE CALCULATOR</span>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 12px;">
                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700;">Invoice Reference No.</label>
                        <input type="text" name="invoice_no" class="adm-form-control" value="<?php echo htmlspecialchars($val_inv_no); ?>" required style="font-family: monospace; font-weight: 700; letter-spacing: 0.5px;">
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 12px;">
                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700;">Invoice Issue Date &amp; Time</label>
                        <input type="datetime-local" name="invoice_date" class="adm-form-control" value="<?php echo $val_date; ?>" required style="font-size: 12px;">
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 12px;">
                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700;">Settlement Mode</label>
                        <select name="payment_method" class="adm-form-control" style="font-size: 12px;">
                            <option value="cash" <?php echo ($val_method === 'cash') ? 'selected' : ''; ?>>Cash Payment</option>
                            <option value="upi" <?php echo ($val_method === 'upi') ? 'selected' : ''; ?>>UPI / GPay / PhonePe</option>
                            <option value="card" <?php echo ($val_method === 'card') ? 'selected' : ''; ?>>Credit / Debit Card</option>
                            <option value="bank_transfer" <?php echo ($val_method === 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer (IMPS/NEFT)</option>
                            <option value="net_banking" <?php echo ($val_method === 'net_banking') ? 'selected' : ''; ?>>Net Banking</option>
                            <option value="cheque" <?php echo ($val_method === 'cheque') ? 'selected' : ''; ?>>Cheque</option>
                            <option value="credit" <?php echo ($val_method === 'credit') ? 'selected' : ''; ?>>Corporate Credit / Ledger</option>
                        </select>
                    </div>

                    <div class="adm-form-group" style="margin-bottom: 14px;">
                        <label class="adm-form-label" style="font-size: 11px; font-weight: 700;">Payment Status</label>
                        <select name="payment_status" id="select_payment_status" class="adm-form-control" style="font-size: 12px;">
                            <option value="paid" <?php echo ($val_status === 'paid') ? 'selected' : ''; ?>>Paid (Fully Settled)</option>
                            <option value="partial" <?php echo ($val_status === 'partial') ? 'selected' : ''; ?>>Partially Paid</option>
                            <option value="unpaid" <?php echo ($val_status === 'unpaid') ? 'selected' : ''; ?>>Unpaid (Pending Settlement)</option>
                        </select>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; background: rgba(0,0,0,0.3); padding: 10px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06);">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 11px; color: #FFF; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="show_bank_details" value="1" <?php echo $val_show_bank ? 'checked' : ''; ?> style="accent-color: var(--adm-gold);">
                            <span>Print Estate Bank Transfer Details</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 11px; color: #FFF; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="show_qr_code" value="1" <?php echo $val_show_qr ? 'checked' : ''; ?> style="accent-color: var(--adm-gold);">
                            <span>Print Estate UPI QR Code</span>
                        </label>
                    </div>
                </div>

                <!-- FINANCIALS & TAX CALCULATIONS CARD -->
                <div class="adm-card" style="background: rgba(7, 18, 11, 0.98); border: 2px solid var(--adm-gold); border-radius: 12px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(197, 160, 89, 0.3); padding-bottom: 10px;">
                        <h4 style="font-family: var(--adm-font-title); font-size: 15px; color: var(--adm-gold); margin: 0;">FINANCIAL BREAKDOWN</h4>
                        <span class="adm-badge" style="background: rgba(197, 160, 89, 0.2); color: var(--adm-gold); font-size: 10px; font-weight: 800;">INR <?php echo $currency; ?></span>
                    </div>

                    <!-- Gross Subtotal -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px 0; font-size: 12.5px;">
                        <span style="color: var(--adm-text-secondary);">Items Subtotal:</span>
                        <strong id="disp_subtotal" style="font-family: monospace; font-size: 13.5px; color: #FFFFFF;">₹0.00</strong>
                    </div>

                    <!-- Concession / Discount -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 12.5px;">
                        <span style="color: var(--adm-text-secondary);">Discount / Concession:</span>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: #ef4444; font-weight: 700;">-₹</span>
                            <input type="number" step="1" min="0" name="discount_amount" id="input_discount" value="<?php echo $val_discount; ?>" class="adm-form-control" style="width: 100px; padding: 4px 8px; text-align: right; font-weight: 700; color: #ef4444; font-size: 12px;" oninput="calculateCustomBill();">
                        </div>
                    </div>

                    <!-- Taxable Subtotal -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 12.5px; border-top: 1px dashed rgba(255,255,255,0.08); margin-top: 4px;">
                        <span style="color: #38bdf8; font-weight: 700;">Taxable Value:</span>
                        <strong id="disp_taxable" style="font-family: monospace; font-size: 14px; color: #38bdf8;">₹0.00</strong>
                    </div>

                    <!-- GST Configuration Section (Typeable Rate + Presets) -->
                    <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 12px; margin: 12px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase;">
                                <i class="fa-solid fa-percent"></i> GST Tax Rate (%):
                            </span>
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <input type="number" step="0.5" min="0" max="100" name="gst_percentage" id="input_gst_pct" value="<?php echo $val_gst_pct; ?>" class="adm-form-control" style="width: 70px; padding: 4px 6px; text-align: center; font-weight: 800; color: #2ecc71; font-size: 12px;" oninput="calculateCustomBill();">
                                <span style="font-weight: 700; color: #2ecc71;">%</span>
                            </div>
                        </div>

                        <!-- Quick Tax Chips -->
                        <div style="display: flex; gap: 4px; justify-content: space-between; margin-bottom: 8px;">
                            <button type="button" class="adm-crop-ratio-btn" style="flex: 1; padding: 4px 2px; font-size: 10px; justify-content: center;" onclick="setGstRate(0);">0%</button>
                            <button type="button" class="adm-crop-ratio-btn active" style="flex: 1; padding: 4px 2px; font-size: 10px; justify-content: center;" onclick="setGstRate(5);">5%</button>
                            <button type="button" class="adm-crop-ratio-btn" style="flex: 1; padding: 4px 2px; font-size: 10px; justify-content: center;" onclick="setGstRate(12);">12%</button>
                            <button type="button" class="adm-crop-ratio-btn" style="flex: 1; padding: 4px 2px; font-size: 10px; justify-content: center;" onclick="setGstRate(18);">18%</button>
                            <button type="button" class="adm-crop-ratio-btn" style="flex: 1; padding: 4px 2px; font-size: 10px; justify-content: center;" onclick="setGstRate(28);">28%</button>
                        </div>

                        <!-- Tax Breakdown Display -->
                        <div id="tax_breakdown_display" style="font-size: 11px; color: var(--adm-text-secondary); line-height: 1.5; border-top: 1px dashed rgba(255,255,255,0.08); padding-top: 6px;">
                            <div style="display: flex; justify-content: space-between;" id="row_cgst">
                                <span>CGST (<span id="disp_cgst_pct">2.5</span>%):</span>
                                <span id="disp_cgst_amt" style="font-family: monospace; font-weight: 600; color: #FFF;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;" id="row_sgst">
                                <span>SGST (<span id="disp_sgst_pct">2.5</span>%):</span>
                                <span id="disp_sgst_amt" style="font-family: monospace; font-weight: 600; color: #FFF;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; display: none;" id="row_igst">
                                <span>IGST (<span id="disp_igst_pct">5</span>%):</span>
                                <span id="disp_igst_amt" style="font-family: monospace; font-weight: 600; color: #FFF;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed rgba(255,255,255,0.08); padding-top: 4px; margin-top: 4px; font-weight: 700; color: #2ecc71;">
                                <span>Total GST:</span>
                                <span id="disp_total_gst" style="font-family: monospace;">₹0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- GRAND TOTAL -->
                    <div style="background: rgba(197, 160, 89, 0.15); border: 1px solid var(--adm-gold); border-radius: 8px; padding: 12px 14px; margin: 10px 0;">
                        <span style="font-size: 10.5px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;">
                            INVOICE GRAND TOTAL
                        </span>
                        <div id="disp_grand_total" style="font-family: var(--adm-font-title); font-size: 24px; font-weight: 700; color: #FFFFFF; letter-spacing: 0.5px;">
                            ₹0.00
                        </div>
                    </div>

                    <!-- Advance Paid Input -->
                    <div style="margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 11px; font-weight: 700; color: #FFF;">Amount Paid / Received:</span>
                            <button type="button" class="adm-btn-action" style="padding: 2px 8px; font-size: 9.5px; background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);" onclick="copyFullToAdvance();">
                                Pay In Full
                            </button>
                        </div>
                        <input type="number" step="any" min="0" name="advance_paid" id="input_advance" value="<?php echo $val_advance; ?>" class="adm-form-control" style="font-weight: 800; font-size: 13.5px; text-align: right; color: #2ecc71;" oninput="calculateCustomBill();">
                    </div>

                    <!-- Balance Due -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-radius: 6px; margin-top: 10px; background: rgba(0,0,0,0.4);" id="balance_due_box">
                        <span style="font-size: 11.5px; font-weight: 700; color: var(--adm-text-secondary);">Balance Due:</span>
                        <strong id="disp_balance_due" style="font-family: monospace; font-size: 15px; color: #2ecc71;">₹0.00</strong>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 18px;">
                        <button type="button" class="adm-btn-action gold" style="padding: 12px; font-size: 13.5px; font-weight: 700; width: 100%; justify-content: center; box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);" onclick="submitCustomBill('save_and_print');">
                            <i class="fa-solid fa-print"></i>
                            <span>SAVE &amp; PRINT INVOICE</span>
                        </button>

                        <button type="button" class="adm-btn-action" style="padding: 10px; font-size: 12.5px; font-weight: 600; width: 100%; justify-content: center; background: rgba(255,255,255,0.06); color: #FFF; border: 1px solid rgba(255,255,255,0.2);" onclick="submitCustomBill('save');">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Invoice Record Only</span>
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </form>

</div>

<script>
// State State Code Map for Indian GSTIN (first 2 digits)
var GST_STATE_MAP = {
    '32': 'Kerala', '33': 'Tamil Nadu', '29': 'Karnataka', '36': 'Telangana', '37': 'Andhra Pradesh',
    '27': 'Maharashtra', '07': 'Delhi', '09': 'Uttar Pradesh', '19': 'West Bengal', '24': 'Gujarat'
};

function detectGstState(gstin) {
    if (!gstin || gstin.length < 2) return;
    var stateCode = gstin.substring(0, 2);
    var typeSelect = document.getElementById('select_gst_type');
    var hint = document.getElementById('gst_mode_hint');
    if (stateCode === '32') {
        typeSelect.value = 'intra_state';
        if (hint) hint.innerText = 'Detected: Kerala GSTIN (32) → CGST + SGST Split';
    } else if (GST_STATE_MAP[stateCode]) {
        typeSelect.value = 'inter_state';
        if (hint) hint.innerText = 'Detected: ' + GST_STATE_MAP[stateCode] + ' (' + stateCode + ') → IGST Applicable';
    }
    calculateCustomBill();
}

function toggleB2BGstSection(checked) {
    var c = document.getElementById('b2b_gst_container');
    if (c) c.style.display = checked ? 'block' : 'none';
}

function setGstRate(pct) {
    var input = document.getElementById('input_gst_pct');
    if (input) {
        input.value = pct;
        calculateCustomBill();
    }
    document.querySelectorAll('.adm-crop-ratio-btn').forEach(function(btn) {
        if (parseFloat(btn.innerText) === pct) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function addLineItemRow(desc, sac, qty, rate) {
    desc = desc || '';
    sac = sac || '996331';
    qty = (qty !== undefined) ? qty : 1;
    rate = (rate !== undefined) ? rate : 0;
    var total = (qty * rate).toFixed(2);

    var tbody = document.getElementById('itemsTableBody');
    var rowIdx = tbody.querySelectorAll('.item-row').length;
    var rowNum = rowIdx + 1;

    var tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.rowIdx = rowIdx;
    tr.innerHTML = `
        <td style="padding: 6px; vertical-align: middle;">
            <span class="row-num-badge" style="background: rgba(197, 160, 89, 0.2); color: var(--adm-gold); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                ${rowNum}
            </span>
        </td>
        <td style="padding: 6px;">
            <input type="text" name="item_desc[]" class="adm-form-control item-desc-input" value="${escapeHtml(desc)}" placeholder="Item description / service particulars" required style="font-size: 12.5px;">
        </td>
        <td style="padding: 6px;">
            <input type="text" name="item_sac[]" class="adm-form-control" value="${escapeHtml(sac)}" placeholder="996331" style="font-size: 12px; font-family: monospace;">
        </td>
        <td style="padding: 6px;">
            <input type="number" step="any" min="0.01" name="item_qty[]" class="adm-form-control item-qty-input" value="${qty}" required style="text-align: center; font-weight: 700;" oninput="calculateCustomBill();">
        </td>
        <td style="padding: 6px;">
            <input type="number" step="0.5" min="0" name="item_rate[]" class="adm-form-control item-rate-input" value="${rate}" required style="text-align: right; font-weight: 700; color: #2ecc71;" oninput="calculateCustomBill();">
        </td>
        <td style="padding: 6px;">
            <input type="text" class="adm-form-control item-total-disp" value="${total}" readonly style="text-align: right; font-weight: 800; font-family: monospace; background: rgba(0,0,0,0.4); color: #FFFFFF;">
        </td>
        <td style="padding: 6px; text-align: center; vertical-align: middle;">
            <button type="button" class="adm-btn-action" style="padding: 6px 10px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.35);" onclick="removeLineItemRow(this);" title="Delete Line Item">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);

    var countBadge = document.getElementById('items_count_badge');
    if (countBadge) countBadge.innerText = tbody.querySelectorAll('.item-row').length;

    calculateCustomBill();
    tr.querySelector('.item-desc-input').focus();
}

function quickAddPreset(desc, sac, qty, rate) {
    addLineItemRow(desc, sac, qty, rate);
}

function removeLineItemRow(btn) {
    var tbody = document.getElementById('itemsTableBody');
    var rows = tbody.querySelectorAll('.item-row');
    if (rows.length <= 1) {
        alert('Invoice must have at least one line item.');
        return;
    }
    btn.closest('tr').remove();

    // Renumber rows
    tbody.querySelectorAll('.item-row').forEach(function(row, idx) {
        var badge = row.querySelector('.row-num-badge');
        if (badge) badge.innerText = idx + 1;
    });

    var countBadge = document.getElementById('items_count_badge');
    if (countBadge) countBadge.innerText = tbody.querySelectorAll('.item-row').length;

    calculateCustomBill();
}

function escapeHtml(text) {
    return (text || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function calculateCustomBill() {
    var subtotal = 0;
    var rows = document.querySelectorAll('#itemsTableBody .item-row');

    rows.forEach(function(row) {
        var qtyInput = row.querySelector('.item-qty-input');
        var rateInput = row.querySelector('.item-rate-input');
        var totalDisp = row.querySelector('.item-total-disp');

        var q = parseFloat(qtyInput.value) || 0;
        var r = parseFloat(rateInput.value) || 0;
        var lineTot = q * r;

        if (totalDisp) totalDisp.value = lineTot.toFixed(2);
        subtotal += lineTot;
    });

    // Subtotal
    document.getElementById('disp_subtotal').innerText = '₹' + formatMoney(subtotal);

    // Discount
    var discInput = document.getElementById('input_discount');
    var discount = parseFloat(discInput.value) || 0;
    var taxable = Math.max(0, subtotal - discount);
    document.getElementById('disp_taxable').innerText = '₹' + formatMoney(taxable);

    // GST
    var gstPctInput = document.getElementById('input_gst_pct');
    var gstPct = parseFloat(gstPctInput.value) || 0;
    var taxAmount = taxable * (gstPct / 100);

    var gstTypeSelect = document.getElementById('select_gst_type');
    var isInterState = gstTypeSelect && (gstTypeSelect.value === 'inter_state');

    var rowCgst = document.getElementById('row_cgst');
    var rowSgst = document.getElementById('row_sgst');
    var rowIgst = document.getElementById('row_igst');

    if (isInterState) {
        if (rowCgst) rowCgst.style.display = 'none';
        if (rowSgst) rowSgst.style.display = 'none';
        if (rowIgst) {
            rowIgst.style.display = 'flex';
            document.getElementById('disp_igst_pct').innerText = gstPct.toFixed(1);
            document.getElementById('disp_igst_amt').innerText = '₹' + formatMoney(taxAmount);
        }
    } else {
        if (rowIgst) rowIgst.style.display = 'none';
        var halfPct = gstPct / 2;
        var halfTax = taxAmount / 2;
        if (rowCgst) {
            rowCgst.style.display = 'flex';
            document.getElementById('disp_cgst_pct').innerText = halfPct.toFixed(1);
            document.getElementById('disp_cgst_amt').innerText = '₹' + formatMoney(halfTax);
        }
        if (rowSgst) {
            rowSgst.style.display = 'flex';
            document.getElementById('disp_sgst_pct').innerText = halfPct.toFixed(1);
            document.getElementById('disp_sgst_amt').innerText = '₹' + formatMoney(halfTax);
        }
    }

    document.getElementById('disp_total_gst').innerText = '₹' + formatMoney(taxAmount);

    // Grand Total
    var grandTotal = taxable + taxAmount;
    document.getElementById('disp_grand_total').innerText = '₹' + formatMoney(grandTotal);

    // Advance & Balance
    var advInput = document.getElementById('input_advance');
    var advance = parseFloat(advInput.value);
    if (isNaN(advance)) advance = 0;

    var balance = Math.max(0, grandTotal - advance);
    var balDisp = document.getElementById('disp_balance_due');
    balDisp.innerText = '₹' + formatMoney(balance);

    var statusSelect = document.getElementById('select_payment_status');
    if (balance <= 0.01 && grandTotal > 0) {
        balDisp.style.color = '#2ecc71';
        if (statusSelect) statusSelect.value = 'paid';
    } else if (advance > 0 && balance > 0.01) {
        balDisp.style.color = '#f59e0b';
        if (statusSelect) statusSelect.value = 'partial';
    } else {
        balDisp.style.color = '#ef4444';
        if (statusSelect) statusSelect.value = 'unpaid';
    }
}

function copyFullToAdvance() {
    var rows = document.querySelectorAll('#itemsTableBody .item-row');
    var subtotal = 0;
    rows.forEach(function(row) {
        var q = parseFloat(row.querySelector('.item-qty-input').value) || 0;
        var r = parseFloat(row.querySelector('.item-rate-input').value) || 0;
        subtotal += q * r;
    });
    var discount = parseFloat(document.getElementById('input_discount').value) || 0;
    var taxable = Math.max(0, subtotal - discount);
    var gstPct = parseFloat(document.getElementById('input_gst_pct').value) || 0;
    var grandTotal = taxable + (taxable * (gstPct / 100));

    var advInput = document.getElementById('input_advance');
    if (advInput) {
        advInput.value = grandTotal.toFixed(2);
        calculateCustomBill();
    }
}

function formatMoney(amount) {
    return Number(amount || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function submitCustomBill(action) {
    document.getElementById('submitActionInput').value = action;
    var form = document.getElementById('customBillForm');
    if (form.reportValidity()) {
        form.submit();
    }
}

// Initial calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateCustomBill();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
