<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$count = $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'pending'")->fetchColumn();
$sources = $pdo->query("SELECT source, status, COUNT(*) as c FROM testimonials GROUP BY source, status")->fetchAll(PDO::FETCH_ASSOC);

echo "Total reviews in DB: $count\n";
echo "Pending reviews: $pending\n";
print_r($sources);
