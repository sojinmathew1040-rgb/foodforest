<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

// 1. Mark table as initialized
$pdo->exec("REPLACE INTO settings (setting_key, setting_value) VALUES ('sanctuary_spots_table_initialized', '1')");

// 2. Delete / Truncate all records from sanctuary_spots
$pdo->exec("DELETE FROM `sanctuary_spots`");

// 3. Clean all route pathways from referencing old spots
$route_json = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'sanctuary_map_route_data'")->fetchColumn();
if (!empty($route_json)) {
    $cfg = json_decode($route_json, true);
    if (is_array($cfg) && !empty($cfg['routes'])) {
        foreach ($cfg['routes'] as &$r) {
            if (!empty($r['nodes']) && is_array($r['nodes'])) {
                $r['nodes'] = array_values(array_filter($r['nodes'], function($n) {
                    return !in_array($n['type'] ?? '', ['spot', 'spot_id', 'spot_num']);
                }));
            }
        }
        $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('sanctuary_map_route_data', ?)")
            ->execute([json_encode($cfg)]);
    }
}

$count = $pdo->query("SELECT COUNT(*) FROM `sanctuary_spots`")->fetchColumn();
echo "SUCCESS: All spots deleted. Remaining spots count: " . $count . "\n";
