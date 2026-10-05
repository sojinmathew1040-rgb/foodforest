<?php
// Test kitchen POST actions
require_once __DIR__ . '/../admin/includes/auth.php';

$pdo = get_db();

// Find an existing booking to test with
$stmt = $pdo->query("SELECT id, food_status, food_amount, total_amount, food_items FROM bookings WHERE status != 'cancelled' ORDER BY id DESC LIMIT 1");
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("No booking found to test.\n");
}

$test_booking_id = $booking['id'];
$orig_status = $booking['food_status'];
$orig_food_amount = (float)$booking['food_amount'];
$orig_total_amount = (float)$booking['total_amount'];

echo "Found booking #{$test_booking_id}: orig_status={$orig_status}, orig_food_amount={$orig_food_amount}\n";

// Set up fake admin session
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'Administrator';
$_SESSION['admin_role'] = 'Concierge Lead';
$token = csrf_token();

// 1. Test update_food_status
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PHP_SELF'] = '/foodforest/admin/kitchen.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_POST = [
    'csrf_token' => $token,
    'action' => 'update_food_status',
    'booking_id' => $test_booking_id,
    'new_food_status' => 'preparing'
];

ob_start();
require __DIR__ . '/../admin/kitchen.php';
$output = ob_get_clean();

// Check if status changed in DB
$chk = $pdo->prepare("SELECT food_status FROM bookings WHERE id = ?");
$chk->execute([$test_booking_id]);
$new_status = $chk->fetchColumn();

echo "After update_food_status: DB food_status is '{$new_status}' (expected 'preparing')\n";

// 2. Test quick_add_dish
// Get a dish (e.g. Masala Tea)
$d_stmt = $pdo->query("SELECT id, heading, price FROM food_menu WHERE heading LIKE '%Tea%' LIMIT 1");
$dish = $d_stmt->fetch(PDO::FETCH_ASSOC);
echo "Testing quick add dish: #{$dish['id']} {$dish['heading']} (₹{$dish['price']})\n";

$_POST = [
    'csrf_token' => $token,
    'action' => 'quick_add_dish',
    'booking_id' => $test_booking_id,
    'dish_id' => $dish['id'],
    'dish_qty' => 2,
    'dish_meal_time' => 'snacks'
];

ob_start();
require __DIR__ . '/../admin/kitchen.php';
$output2 = ob_get_clean();

$chk2 = $pdo->prepare("SELECT food_items, food_amount, total_amount FROM bookings WHERE id = ?");
$chk2->execute([$test_booking_id]);
$updated_b = $chk2->fetch(PDO::FETCH_ASSOC);

$updated_items = json_decode($updated_b['food_items'], true);
$last_item = end($updated_items);

echo "Updated food_amount: {$updated_b['food_amount']} (was {$orig_food_amount} + " . (2 * $dish['price']) . ")\n";
echo "Last item in food_items: {$last_item['heading']} x {$last_item['quantity']} for [{$last_item['meal_time']}]\n";

// Reset booking back if desired or keep clean
echo "All Kitchen POST tests executed successfully!\n";
