<?php
// =========================================================================
// Food Forest Sanctuary — Automated Channel Sync Webhook / Cron / Auto-Trigger
// Endpoint: GET/POST /api/ical_sync.php?auto={0|1}&feed_id={optional}
// =========================================================================

@ignore_user_abort(true);
@set_time_limit(60);

if (!headers_sent()) {
    @header_remove('Cache-Control');
    @header_remove('Pragma');
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Accel-Expires: 0');
    header('X-LiteSpeed-Cache-Control: no-cache, no-store');
}

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../admin/includes/ical_sync.php';

try {
    $pdo = get_db();
    ensure_ical_and_channel_schema($pdo);

    $is_auto = !empty($_GET['auto']) || !empty($_POST['auto']);
    if ($is_auto) {
        $last_sync = (int)get_setting('ical_last_auto_sync', '0', true);
        if ((time() - $last_sync) < 480) {
            echo json_encode([
                'success' => true,
                'skipped' => true,
                'message' => 'Auto-sync skipped (synced ' . (time() - $last_sync) . 's ago).'
            ]);
            exit;
        }
        // Immediately stamp ical_last_auto_sync to prevent concurrent stampede
        $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('ical_last_auto_sync', ?)")
            ->execute([(string)time()]);
    }

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
