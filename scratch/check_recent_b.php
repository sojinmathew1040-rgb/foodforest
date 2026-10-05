<?php
require __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query("SELECT id, reference_code, guest_name, villa_type, checkin_date, checkout_date, status, food_status, food_amount, food_items FROM bookings ORDER BY id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Date today is: " . date('Y-m-d') . "\n";
foreach ($rows as $r) {
    echo "#{$r['id']} ({$r['reference_code']}): {$r['guest_name']} | {$r['villa_type']} | {$r['checkin_date']} to {$r['checkout_date']} | Status: {$r['status']} | Food Status: {$r['food_status']} | Food Amt: {$r['food_amount']}\n";
    if (!empty($r['food_items'])) {
        echo "   Food JSON: " . substr($r['food_items'], 0, 100) . "...\n";
    }
}
