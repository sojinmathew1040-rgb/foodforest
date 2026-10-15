<?php
require_once __DIR__ . '/../admin/includes/db.php';

$pdo = get_db();

echo "=== VERIFYING API/BOOK.PHP MEAL TIMING ===\n";

// Create test booking payload
$post_data = [
    'name' => 'Automated MealTime Test Guest',
    'phone' => '+91 99999 88888',
    'email' => 'mealtest@foodforest.test',
    'city_state' => 'Kochi, Kerala',
    'villa' => 'treehouse',
    'adults' => 2,
    'kids' => 0,
    'checkin' => '2027-04-10',
    'checkout' => '2027-04-11',
    'target_meal_time' => 'dinner',
    'food_skipped' => '0',
    'complimentary_breakfast' => '1',
    'food_items' => json_encode([
        [
            'id' => 0,
            'category' => 'breakfast',
            'category_title' => 'Complimentary Estate Breakfast',
            'heading' => "Complimentary Sanctuary Breakfast (Chef's Fixed Daily Spread)",
            'meal_time' => 'breakfast',
            'price' => 0,
            'quantity' => 2,
            'is_complimentary' => true
        ],
        [
            'id' => 1,
            'category' => 'breakfast',
            'category_title' => 'Breakfast',
            'heading' => 'Thattu Dosa (Set – 3 Nos)',
            'meal_time' => 'dinner',
            'price' => 120,
            'quantity' => 2,
            'is_complimentary' => false
        ],
        [
            'id' => 15,
            'category' => 'juices',
            'category_title' => 'Juices',
            'heading' => 'Fresh Sugarcane Juice',
            'meal_time' => 'dinner',
            'price' => 80,
            'quantity' => 2,
            'is_complimentary' => false
        ]
    ])
];

$dummy_file = 'C:/Users/Dijo J Perumaly/.gemini/antigravity-ide/brain/2b87bc28-7df7-45e3-a87d-189aa78bb228/.user_uploaded/media_1791981870115.png';
$post_data['id_proof_file'] = new CURLFile($dummy_file, 'image/png', 'test_id.png');

// Execute via curl or local HTTP request to http://localhost/foodforest/api/book.php
$ch = curl_init('http://localhost/foodforest/api/book.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";

$res = json_decode($response, true);
if ($res && !empty($res['reference_code'])) {
    $ref = $res['reference_code'];
    $bstmt = $pdo->prepare("SELECT id, reference_code, food_items FROM bookings WHERE reference_code = ?");
    $bstmt->execute([$ref]);
    $bk = $bstmt->fetch(PDO::FETCH_ASSOC);

    $items = json_decode($bk['food_items'], true);
    echo "\nSaved Booking #{$bk['id']} ({$bk['reference_code']}) food items:\n";
    foreach ($items as $itm) {
        echo " - {$itm['heading']} [Category: {$itm['category']}] => MEAL_TIME: {$itm['meal_time']}\n";
    }

    // Clean up test booking to keep database pristine
    $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$bk['id']]);
    echo "\nCleaned up test booking #{$bk['id']}.\n";
} else {
    echo "Could not parse response or booking failed.\n";
}
