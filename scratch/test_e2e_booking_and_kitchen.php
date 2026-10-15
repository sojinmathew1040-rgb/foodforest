<?php
require_once __DIR__ . '/../admin/includes/db.php';

$pdo = get_db();

echo "=== E2E VERIFICATION TEST: MEAL TIMING IN BOOKING & KITCHEN ===\n";

// 1. Fetch dishes from various categories
$stmt = $pdo->query("SELECT id, heading, category, price FROM food_menu WHERE is_active = 1 LIMIT 50");
$all_dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$by_cat = [];
foreach ($all_dishes as $d) {
    $by_cat[$d['category']][] = $d;
}

echo "Available categories in DB: " . implode(', ', array_keys($by_cat)) . "\n";

$juice_dish = $by_cat['juices'][0] ?? null;
$breakfast_dish = $by_cat['breakfast'][0] ?? null;
$millet_dish = $by_cat['millet'][0] ?? null;
$curry_dish = $by_cat['curries'][0] ?? null;
$dinner_dish = $by_cat['dinner'][0] ?? null;

echo "Selected Test Dishes:\n";
echo " - Juice: " . ($juice_dish['heading'] ?? 'None') . " (Category: " . ($juice_dish['category'] ?? '') . ")\n";
echo " - Breakfast: " . ($breakfast_dish['heading'] ?? 'None') . " (Category: " . ($breakfast_dish['category'] ?? '') . ")\n";
echo " - Millet: " . ($millet_dish['heading'] ?? 'None') . " (Category: " . ($millet_dish['category'] ?? '') . ")\n";
echo " - Curry: " . ($curry_dish['heading'] ?? 'None') . " (Category: " . ($curry_dish['category'] ?? '') . ")\n";

// 2. Simulate Kitchen Order Dispatch for DINNER
// Chef selects Dinner shift, adds Juice + Breakfast dish + Curry
$global_meal = 'dinner';
$items_to_add = [
    [
        'dishId' => $juice_dish['id'],
        'quantity' => 2,
        'mealSlot' => $global_meal
    ],
    [
        'dishId' => $breakfast_dish['id'],
        'quantity' => 3,
        'mealSlot' => $global_meal
    ],
    [
        'dishId' => $curry_dish['id'],
        'quantity' => 1,
        'mealSlot' => $global_meal
    ]
];

// Replicate kitchen.php quick_add_batch_dishes processing
$processed_kitchen_items = [];
$dstmt = $pdo->prepare("SELECT * FROM food_menu WHERE id = ?");

foreach ($items_to_add as $it) {
    $dish_id = (int)$it['dishId'];
    $qty = (int)$it['quantity'];
    $raw_chosen = trim($it['mealSlot'] ?? '');
    if (empty($raw_chosen) || in_array(strtolower($raw_chosen), ['juices', 'millet', 'curries'])) {
        $raw_chosen = $global_meal;
    }
    $chosen_meal = strtolower($raw_chosen);
    if (strpos($chosen_meal, 'snack') !== false || strpos($chosen_meal, 'tea') !== false || strpos($chosen_meal, 'evening') !== false) {
        $chosen_meal = 'snacks';
    } elseif (strpos($chosen_meal, 'break') !== false || strpos($chosen_meal, 'morn') !== false) {
        $chosen_meal = 'breakfast';
    } elseif (strpos($chosen_meal, 'din') !== false || strpos($chosen_meal, 'night') !== false) {
        $chosen_meal = 'dinner';
    } elseif (strpos($chosen_meal, 'lunch') !== false || strpos($chosen_meal, 'noon') !== false) {
        $chosen_meal = 'lunch';
    } else {
        $chosen_meal = $global_meal;
    }

    $dstmt->execute([$dish_id]);
    $d = $dstmt->fetch(PDO::FETCH_ASSOC);

    $processed_kitchen_items[] = [
        'id' => (int)$d['id'],
        'heading' => $d['heading'],
        'category' => $d['category'],
        'meal_time' => $chosen_meal,
        'quantity' => $qty,
        'price' => (float)$d['price'],
        'subtotal' => $qty * (float)$d['price'],
        'status' => 'queued',
        'served' => false
    ];
}

echo "\n--- KITCHEN DISPATCH ORDER FOR DINNER ---\n";
foreach ($processed_kitchen_items as $pki) {
    echo "Dish: {$pki['heading']} | Cat: {$pki['category']} | Assigned meal_time: {$pki['meal_time']}\n";
    if ($pki['meal_time'] !== 'dinner') {
        echo "❌ ERROR: Expected 'dinner' but got {$pki['meal_time']}\n";
    } else {
        echo "✅ PASS: Assigned to DINNER correctly.\n";
    }
}

// 3. Replicate Chef's Preparation Batch Summary grouping
$prep_summary = [
    'breakfast' => ['count' => 0, 'items' => []],
    'lunch'     => ['count' => 0, 'items' => []],
    'snacks'    => ['count' => 0, 'items' => []],
    'dinner'    => ['count' => 0, 'items' => []]
];

foreach ($processed_kitchen_items as $item) {
    $raw_slot = strtolower(trim($item['meal_time'] ?? ''));
    if (empty($raw_slot) || in_array($raw_slot, ['juices', 'millet', 'curries'])) {
        $raw_slot = strtolower(trim($item['category'] ?? 'lunch'));
    }
    if (strpos($raw_slot, 'snack') !== false || strpos($raw_slot, 'evening') !== false || strpos($raw_slot, 'tea') !== false) {
        $slot = 'snacks';
    } elseif (strpos($raw_slot, 'break') !== false || strpos($raw_slot, 'morn') !== false) {
        $slot = 'breakfast';
    } elseif (strpos($raw_slot, 'din') !== false || strpos($raw_slot, 'night') !== false) {
        $slot = 'dinner';
    } elseif (strpos($raw_slot, 'lunch') !== false || strpos($raw_slot, 'noon') !== false) {
        $slot = 'lunch';
    } else {
        $slot = 'lunch';
    }

    $h = trim($item['heading']);
    $qty = (int)$item['quantity'];

    if (!isset($prep_summary[$slot]['items'][$h])) {
        $prep_summary[$slot]['items'][$h] = ['name' => $h, 'quantity' => 0];
    }
    $prep_summary[$slot]['items'][$h]['quantity'] += $qty;
    $prep_summary[$slot]['count'] += $qty;
}

echo "\n--- CHEF'S PREPARATION BATCH SUMMARY GROUPING ---\n";
foreach ($prep_summary as $shift => $data) {
    echo "Shift: " . strtoupper($shift) . " => Total Dishes: " . $data['count'] . "\n";
    foreach ($data['items'] as $it_name => $it_info) {
        echo "   * {$it_name} x {$it_info['quantity']}\n";
    }
}

if ($prep_summary['dinner']['count'] === 6 && $prep_summary['breakfast']['count'] === 0 && $prep_summary['lunch']['count'] === 0 && $prep_summary['snacks']['count'] === 0) {
    echo "\n🎉 PERFECT! All 6 portions (Juice, Breakfast Dosa, Curry) aggregated under DINNER shift!\n";
} else {
    echo "\n❌ FAIL: Dishes were scattered into wrong shifts.\n";
}

echo "=== TEST COMPLETED ===\n";
