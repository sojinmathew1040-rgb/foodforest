<?php
// Test booking API with real food items & meal slot selections
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

// Fetch sample dishes from food_menu
$dishes = [];
$dish_queries = [
    ['Puttu', 'breakfast', 2],
    ['Kadala Curry', 'breakfast', 2],
    ['Chicken Biriyani', 'lunch', 2],
    ['Banana Fry', 'snacks', 4],
    ['Masala Tea', 'snacks', 4],
    ['Chapathi', 'dinner', 6],
    ['Nadan Chicken Curry', 'dinner', 1],
    ['Passion Fruit', 'lunch', 2]
];

$food_items = [];
foreach ($dish_queries as [$name, $slot, $qty]) {
    $stmt = $pdo->prepare("SELECT * FROM food_menu WHERE heading LIKE ? LIMIT 1");
    $stmt->execute(['%' . $name . '%']);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($d) {
        $food_items[] = [
            'id' => (int)$d['id'],
            'category' => $d['category'],
            'category_title' => ucfirst($d['category']),
            'heading' => $d['heading'],
            'subtitle' => $d['subtitle'] ?? '',
            'price' => (float)$d['price'],
            'quantity' => $qty,
            'subtotal' => $qty * (float)$d['price'],
            'meal_time' => $slot,
            'dietary_type' => $d['dietary_type'] ?? 'veg'
        ];
    }
}

echo "Compiled " . count($food_items) . " food items for test booking:\n";
foreach ($food_items as $fi) {
    echo " - [{$fi['meal_time']}] {$fi['heading']} x {$fi['quantity']} @ ₹{$fi['price']} = ₹{$fi['subtotal']}\n";
}

// Get an available room
$r_stmt = $pdo->query("SELECT slug, title, structure_type FROM rooms WHERE is_available = 1 LIMIT 1");
$room = $r_stmt->fetch(PDO::FETCH_ASSOC);
$room_slug = $room['slug'] ?? 'mud-chalet-01';

$payload = [
    'step' => 'finalize',
    'guest_name' => 'Dr. Thomas Kurian',
    'guest_email' => 'thomas.kurian@example.com',
    'guest_phone' => '+91 98470 12345',
    'villa_type' => $room_slug,
    'checkin' => date('Y-m-d'),
    'checkout' => date('Y-m-d', strtotime('+1 day')),
    'adults' => 2,
    'kids' => 1,
    'special_notes' => 'Please prepare spicy curries and non-sugar tea for elderly guest.',
    'food_skipped' => 0,
    'food_items' => json_encode($food_items),
    'id_proof_type' => 'Aadhaar Card',
    'id_proof_number' => '9876-5432-1098'
];

$test_img = realpath(__DIR__ . '/../assets/images/food_puttu_kadala.jpg');
$payload['id_proof_file'] = new CURLFile($test_img, 'image/jpeg', 'aadhaar_card.jpg');

$ch = curl_init('http://localhost/foodforest/api/book.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nHTTP Code: {$http_code}\n";
echo "Response:\n{$response}\n";

$res_json = json_decode($response, true);
if (!empty($res_json['booking_id'])) {
    $b_id = $res_json['booking_id'];
    echo "\nSuccessfully created Booking #{$b_id}!\n";

    // Verify booking in DB
    $v_stmt = $pdo->prepare("SELECT id, reference_code, food_status, food_amount, food_items FROM bookings WHERE id = ?");
    $v_stmt->execute([$b_id]);
    $b = $v_stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB food_status: {$b['food_status']}\n";
    echo "DB food_amount: ₹{$b['food_amount']}\n";

    $db_items = json_decode($b['food_items'], true);
    echo "DB food_items count: " . count($db_items) . "\n";
    foreach ($db_items as $di) {
        echo "   -> [{$di['meal_time']}] {$di['heading']} x {$di['quantity']}\n";
    }
}
