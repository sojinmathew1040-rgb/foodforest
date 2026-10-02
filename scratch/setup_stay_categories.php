<?php
require __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

// 1. Initialize stay_categories_json in settings table
$default_categories = [
    'woodhouse' => [
        'key' => 'woodhouse',
        'label' => 'Wood House',
        'icon' => '🪵',
        'description' => 'Alpine Timber & Mountain Log Chalet'
    ],
    'mudhouse' => [
        'key' => 'mudhouse',
        'label' => 'Mud House',
        'icon' => '🌿',
        'description' => 'Handcrafted Earthen & Terracotta Sanctuary'
    ]
];

$json = json_encode($default_categories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('stay_categories_json', ?)");
$stmt->execute([$json]);
echo "✓ Saved stay_categories_json into settings.\n";

// 2. Update existing rooms where stay_type = 'treehouse' to 'woodhouse'
$stmt2 = $pdo->prepare("UPDATE rooms SET stay_type = 'woodhouse' WHERE stay_type = 'treehouse' OR stay_type IS NULL OR stay_type = ''");
$stmt2->execute();
echo "✓ Updated rooms stay_type: " . $stmt2->rowCount() . " row(s) updated.\n";

// 3. Verify all rooms
echo "\n=== RENDERED OPTIONS (selected=woodhouse) ===\n";
echo render_stay_category_options('woodhouse');

echo "\n=== RENDERED OPTIONS (selected=mudhouse) ===\n";
echo render_stay_category_options('mudhouse');

echo "\n=== GET STAY CATEGORIES ===\n";
print_r(get_stay_categories());

