<?php
require_once __DIR__ . '/../admin/includes/db.php';

$gst_rate = get_setting('gst_rate_percentage', 'not_found');
echo "GST Rate Setting: {$gst_rate}%\n";

$pdo = get_db();
$stmt = $pdo->query("SELECT id, reference_code, guest_name, billing_type, gst_number, gst_percentage, gst_amount, total_amount FROM bookings ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent Bookings:\n";
foreach ($rows as $r) {
    $details = get_booking_billing_details($pdo, $r['id']);
    $p = $details['parsed'];
    echo "ID: {$r['id']} | Ref: {$r['reference_code']} | Raw Type: {$r['billing_type']} | Evaluated: {$p['billing_type']} | GST%: {$p['gst_percentage']}% | Taxable: {$p['taxable_subtotal']} | Tax: {$p['tax_amount']} | Total: {$p['net_total']}\n";
}
