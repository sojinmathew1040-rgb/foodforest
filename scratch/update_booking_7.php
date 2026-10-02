<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

$food = [
    [
        'heading' => 'Candlelight Orchard Dinner',
        'category' => 'dinner',
        'category_title' => 'Curated Dining Experience',
        'quantity' => 1,
        'price' => 3000.00,
        'subtotal' => 3000.00,
        'served' => true,
        'is_substituted' => false,
        'original_dish' => '',
        'special_notes' => 'Sugarless food for one person'
    ]
];

$stmt = $pdo->prepare("UPDATE bookings SET food_items = ?, food_amount = 3000.00, food_status = 'selected', activities_json = '[]', total_amount = room_amount + 3000.00 WHERE id = 7");
$stmt->execute([json_encode($food, JSON_UNESCAPED_UNICODE)]);
echo "Updated Booking 7 successfully!\n";

$check = $pdo->query("SELECT id, guest_name, food_items, food_amount, food_status, activities_json, total_amount FROM bookings WHERE id = 7")->fetch(PDO::FETCH_ASSOC);
print_r($check);
