<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
ensure_users_and_guest_columns($pdo);
ensure_default_settings($pdo);

$cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
echo "UPDATED USERS COLUMNS:\n";
foreach ($cols as $c) {
    echo $c['Field'] . " (" . $c['Type'] . ")\n";
}
echo "Migration successful!\n";
