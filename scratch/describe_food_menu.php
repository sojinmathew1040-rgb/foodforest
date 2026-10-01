<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query("DESCRIBE food_menu");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
