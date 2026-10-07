<?php
require_once __DIR__ . '/../admin/includes/db.php';
$b = get_booking_by_ref('FF-2057');
echo "Booking FF-2057:\n";
echo "Food amount: {$b['food_amount']}\n";
echo "Food items count: " . count($b['food_items_list']) . "\n";
foreach ($b['food_items_list'] as $f) {
    echo "  * {$f['heading']} [{$f['category']}] x {$f['quantity']} @ ₹{$f['price']} = ₹{$f['subtotal']} (meal_time: " . ($f['meal_time'] ?? 'N/A') . ")\n";
}
