<?php
require __DIR__ . '/../admin/includes/db.php';

$pdo = get_db();
echo "=== ROOMS ===\n";
$stmt = $pdo->query("SELECT id, slug, title, stay_type, structure_type FROM rooms");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID {$r['id']}: [{$r['slug']}] '{$r['title']}' -> stay_type='{$r['stay_type']}', structure='{$r['structure_type']}'\n";
}

echo "\n=== TABLES ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\n=== SETTINGS TABLE DESCRIBE ===\n";
print_r($pdo->query("DESCRIBE settings")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== SETTINGS CONTENT ===\n";
print_r($pdo->query("SELECT * FROM settings LIMIT 20")->fetchAll(PDO::FETCH_ASSOC));
