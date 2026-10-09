<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%tour%' OR setting_key LIKE '%walkthrough%' OR setting_key LIKE '%360%' OR setting_key LIKE '%room%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . "\n";
}
