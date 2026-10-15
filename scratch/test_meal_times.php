<?php
require_once __DIR__ . '/../admin/includes/db.php';

$pdo = get_db();

echo "=== TESTING MEAL TIME DISPATCH & BATCH PREP SUMMARY ===\n";

// 1. Check an existing active booking or find one
$stmt = $pdo->query("SELECT id, reference_code, villa_type, food_items FROM bookings ORDER BY id DESC LIMIT 1");
$sample_booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sample_booking) {
    echo "No bookings found in DB.\n";
    exit;
}

echo "Found sample booking ID: {$sample_booking['id']} ({$sample_booking['reference_code']})\n";

// 2. Test normalization logic for different inputs
$test_cases = [
    ['raw' => 'dinner', 'cat' => 'juices', 'expected' => 'dinner'],
    ['raw' => 'lunch', 'cat' => 'juices', 'expected' => 'lunch'],
    ['raw' => 'breakfast', 'cat' => 'curries', 'expected' => 'breakfast'],
    ['raw' => 'snacks', 'cat' => 'millet', 'expected' => 'snacks'],
    ['raw' => '', 'cat' => 'juices', 'global' => 'dinner', 'expected' => 'dinner'],
    ['raw' => '', 'cat' => 'breakfast', 'global' => 'dinner', 'expected' => 'breakfast'],
    ['raw' => 'dinner', 'cat' => 'breakfast', 'global' => 'dinner', 'expected' => 'dinner']
];

foreach ($test_cases as $tc) {
    $raw_meal = $tc['raw'];
    $cat = $tc['cat'];
    $global = $tc['global'] ?? 'dinner';

    $chosen_meal = !empty($raw_meal) ? $raw_meal : (!empty($global) ? $global : $cat);
    $meal_time = strtolower($chosen_meal);
    if (strpos($meal_time, 'snack') !== false || strpos($meal_time, 'tea') !== false || strpos($meal_time, 'evening') !== false) {
        $meal_time = 'snacks';
    } elseif (strpos($meal_time, 'break') !== false || strpos($meal_time, 'morn') !== false) {
        $meal_time = 'breakfast';
    } elseif (strpos($meal_time, 'din') !== false || strpos($meal_time, 'night') !== false) {
        $meal_time = 'dinner';
    } elseif (strpos($meal_time, 'lunch') !== false || strpos($meal_time, 'noon') !== false) {
        $meal_time = 'lunch';
    } else {
        $meal_time = $global;
    }
    if (!in_array($meal_time, ['breakfast', 'lunch', 'snacks', 'dinner'])) {
        $meal_time = $global;
    }

    $pass = ($meal_time === $tc['expected']);
    echo ($pass ? "PASS: " : "FAIL: ") . "raw='{$raw_meal}', cat='{$cat}', global='{$global}' => got '{$meal_time}', expected '{$tc['expected']}'\n";
}

echo "=== TEST COMPLETED ===\n";
