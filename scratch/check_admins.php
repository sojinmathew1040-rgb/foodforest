<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$a = $pdo->query("SELECT * FROM admins LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "Username: " . $a['username'] . "\n";
echo "Password hash: " . $a['password_hash'] . "\n";
foreach (['admin', 'admin123', 'admin@123', 'foodforest', 'password'] as $pw) {
    if (password_verify($pw, $a['password_hash']) || $pw === $a['password_hash']) {
        echo "MATCH FOUND: Password is " . $pw . "\n";
    }
}
