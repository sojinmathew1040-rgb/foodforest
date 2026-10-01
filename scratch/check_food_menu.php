<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query("SELECT id, name, category, price FROM food_menu");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total food_menu items: " . count($items) . "\n";
foreach ($items as $it) {
    if (empty($it['category'])) {
        echo "WARNING: Empty category for item " . $it['id'] . " (" . $it['name'] . ")\n";
    }
}
print_r($items);
