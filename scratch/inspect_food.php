<?php
require __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$bookings = $pdo->query("SELECT id, reference_code, guest_name, villa_type, food_items FROM bookings WHERE food_items IS NOT NULL AND food_items != '[]'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($bookings as $b) {
    echo "ID: {$b['id']} | Ref: {$b['reference_code']} | Guest: {$b['guest_name']} | Villa Type: {$b['villa_type']}\n";
    $items = json_decode($b['food_items'], true);
    foreach ($items as $it) {
        echo "   - " . ($it['heading'] ?? '') . " (" . ($it['quantity'] ?? 1) . "x) [" . ($it['meal_time'] ?? $it['category'] ?? '') . "]\n";
    }
}
