<?php
require __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$rooms = $pdo->query("SELECT slug, title FROM rooms")->fetchAll(PDO::FETCH_KEY_PAIR);
print_r($rooms);
