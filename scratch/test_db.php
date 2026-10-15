<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query('SELECT slug, title FROM rooms');
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['slug'] . ' => ' . $r['title'] . "\n";
}
