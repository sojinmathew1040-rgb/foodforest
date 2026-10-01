<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query("DESCRIBE bookings");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
