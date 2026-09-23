<?php
// =========================================================================
// Food Forest Sanctuary — Automated Channel Sync Webhook / Cron Endpoint
// Endpoint: GET/POST /api/ical_sync.php?key={optional_secret}
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../admin/includes/ical_sync.php';

try {
    $pdo = get_db();
    ensure_ical_and_channel_schema($pdo);

    $feed_id = isset($_GET['feed_id']) ? (int)$_GET['feed_id'] : (isset($_POST['feed_id']) ? (int)$_POST['feed_id'] : 0);

    if ($feed_id > 0) {
        $result = sync_single_feed($pdo, $feed_id);
    } else {
        $result = sync_all_ical_feeds($pdo);
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'iCal Sync Exception: ' . $e->getMessage()
    ]);
}
