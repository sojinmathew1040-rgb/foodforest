<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query('SELECT id, category, heading, price, default_meal_time FROM food_menu ORDER BY id ASC');
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | Cat: {$r['category']} | Name: {$r['heading']} | Price: {$r['price']} | Meal: {$r['default_meal_time']}\n";
}
