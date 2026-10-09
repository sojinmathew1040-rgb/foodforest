<?php
// Comprehensive verification test for custom billing
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_logged_in'] = true;

require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

echo "=== 1. TEST DATABASE DATA ===\n";
$stmt = $pdo->query("SELECT * FROM custom_invoices ORDER BY id DESC LIMIT 1");
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inv) {
    die("No custom invoice found in DB!\n");
}

echo "Found Invoice: #{$inv['invoice_no']} for {$inv['customer_name']}\n";
echo "Subtotal: {$inv['subtotal']} | Taxable: {$inv['taxable_amount']} | Tax: {$inv['tax_amount']} | Grand: {$inv['grand_total']}\n";
echo "GST %: {$inv['gst_percentage']}% ({$inv['gst_type']})\n";

echo "\n=== 2. TEST BILLING HUB (CUSTOM INVOICES TAB) ===\n";
$_GET['tab'] = 'custom_invoices';
ob_start();
include __DIR__ . '/../admin/billing.php';
$hub_html = ob_get_clean();

echo "Billing Hub rendered: " . strlen($hub_html) . " bytes\n";
$has_inv_no = strpos($hub_html, $inv['invoice_no']) !== false;
$has_cust_name = strpos($hub_html, $inv['customer_name']) !== false;
$has_print_btn = strpos($hub_html, 'print_custom_bill.php?id=' . $inv['id']) !== false;
$has_tab = strpos($hub_html, 'tab=custom_invoices') !== false;

echo "Has Invoice No in table: " . ($has_inv_no ? "PASS" : "FAIL") . "\n";
echo "Has Customer Name in table: " . ($has_cust_name ? "PASS" : "FAIL") . "\n";
echo "Has Print Link: " . ($has_print_btn ? "PASS" : "FAIL") . "\n";
echo "Has Custom Invoices tab: " . ($has_tab ? "PASS" : "FAIL") . "\n";

echo "\n=== 3. TEST CUSTOM BILL GENERATOR PAGE (CREATE / EDIT) ===\n";
$_GET = ['id' => $inv['id']];
ob_start();
include __DIR__ . '/../admin/custom_bill.php';
$edit_html = ob_get_clean();

echo "Custom Bill Edit page rendered: " . strlen($edit_html) . " bytes\n";
$has_edit_title = strpos($edit_html, 'Edit Custom Tax Invoice') !== false;
$has_input_rate = strpos($edit_html, 'item_rate[]') !== false;
$has_input_gst = strpos($edit_html, 'id="input_gst_pct"') !== false;

echo "Has Edit Title: " . ($has_edit_title ? "PASS" : "FAIL") . "\n";
echo "Has item_rate field: " . ($has_input_rate ? "PASS" : "FAIL") . "\n";
echo "Has gst_percentage input (input_gst_pct): " . ($has_input_gst ? "PASS" : "FAIL") . "\n";

echo "\n=== 4. TEST PRINT CUSTOM BILL (ADMIN VIEW) ===\n";
$_GET = ['id' => $inv['id']];
ob_start();
include __DIR__ . '/../admin/print_custom_bill.php';
$print_html = ob_get_clean();

echo "Print custom bill page rendered: " . strlen($print_html) . " bytes\n";
$has_print_sheet = strpos($print_html, 'id="bill-print-sheet"') !== false;
$has_gst_table = strpos($print_html, 'Taxable Value:') !== false;
$has_cgst_sgst = strpos($print_html, 'CGST') !== false;
$has_qr = strpos($print_html, 'id="bill-qr-card"') !== false;
$has_bank = strpos($print_html, 'id="bill-bank-card"') !== false;

echo "Has printable sheet: " . ($has_print_sheet ? "PASS" : "FAIL") . "\n";
echo "Has GST breakdown: " . ($has_gst_table ? "PASS" : "FAIL") . "\n";
echo "Has CGST/SGST: " . ($has_cgst_sgst ? "PASS" : "FAIL") . "\n";
echo "Has QR Code toggle block: " . ($has_qr ? "PASS" : "FAIL") . "\n";
echo "Has Bank Account block: " . ($has_bank ? "PASS" : "FAIL") . "\n";

echo "\n=== 5. TEST GUEST PUBLIC VIEW WITH TOKEN (NO ADMIN SESSION) ===\n";
unset($_SESSION['admin_id'], $_SESSION['admin_logged_in'], $_SESSION['admin_username']);
$sec_token = substr(hash('sha256', (string)$inv['invoice_no'] . 'ff_sanctuary_folio_secret'), 0, 16);
$_GET = ['id' => $inv['id'], 'token' => $sec_token];

ob_start();
include __DIR__ . '/../admin/print_custom_bill.php';
$guest_print_html = ob_get_clean();

echo "Guest print bill page rendered: " . strlen($guest_print_html) . " bytes\n";
$has_guest_content = strpos($guest_print_html, $inv['invoice_no']) !== false;
echo "Guest access with token valid: " . ($has_guest_content ? "PASS" : "FAIL") . "\n";

echo "\nALL TESTS COMPLETED!\n";
