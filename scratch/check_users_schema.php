<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
echo "COLUMNS IN USERS:\n";
foreach ($cols as $c) {
    echo $c['Field'] . " (" . $c['Type'] . ")\n";
}
$cnt = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "TOTAL USERS: $cnt\n";
$users = $pdo->query('SELECT id, full_name, email, phone FROM users LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
