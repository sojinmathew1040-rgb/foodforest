<?php
require __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$cols = $pdo->query("SHOW COLUMNS FROM food_menu")->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in food_menu: " . implode(', ', $cols) . "\n";
