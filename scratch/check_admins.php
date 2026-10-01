<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query('SELECT password_hash FROM admins WHERE id = 1');
$hash = $stmt->fetchColumn();
echo "Hash: " . $hash . "\n";
foreach (['admin', 'admin123', 'password', 'foodforest', 'foodforest2026', 'admin@123'] as $pwd) {
    if (password_verify($pwd, $hash) || $pwd === $hash) {
        echo "MATCH FOUND: $pwd\n";
    }
}
