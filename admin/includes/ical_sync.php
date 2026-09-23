<?php
// =========================================================================
// Food Forest Sanctuary — 2-Way iCalendar Synchronization Engine
// File: admin/includes/ical_sync.php
// Handles RFC 5545 parser, HTTPS fetchers, database sync, and cancellation reconciliation.
// =========================================================================

require_once __DIR__ . '/db.php';

/**
 * Parse raw RFC 5545 iCalendar (.ics) string into an array of events
 */
function parse_ical_content($ics_text) {
    $events = [];
    if (empty($ics_text)) {
        return $events;
    }

    // Unfold multi-line headers (RFC 5545: lines starting with space or tab continue previous line)
    $ics_text = preg_replace('/\r\n[ \t]/', '', $ics_text);
    $ics_text = preg_replace('/\n[ \t]/', '', $ics_text);

    // Normalize line breaks
    $lines = explode("\n", str_replace("\r", "", $ics_text));

    $in_event = false;
    $current = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        if (strtoupper($line) === 'BEGIN:VEVENT') {
            $in_event = true;
            $current = [
                'uid' => '',
                'dtstart' => '',
                'dtend' => '',
                'summary' => '',
                'description' => '',
                'status' => 'CONFIRMED'
            ];
            continue;
        }

        if (strtoupper($line) === 'END:VEVENT') {
            if ($in_event && !empty($current['dtstart'])) {
                // If DTEND is missing, default to 1 day after DTSTART
                if (empty($current['dtend'])) {
                    $d = new DateTime($current['dtstart']);
                    $d->modify('+1 day');
                    $current['dtend'] = $d->format('Y-m-d');
                }

                // If UID is missing, generate deterministic hash
                if (empty($current['uid'])) {
                    $current['uid'] = 'auto-' . md5($current['dtstart'] . '-' . $current['dtend'] . '-' . $current['summary']);
                }

                // Skip zero-duration or inverted dates
                if ($current['dtend'] > $current['dtstart']) {
                    $events[] = $current;
                }
            }
            $in_event = false;
            $current = [];
            continue;
        }

        if (!$in_event) continue;

        // Parse key:value or key;params:value
        if (preg_match('/^([^:;]+)(?:;[^:]*)?:(.*)$/', $line, $matches)) {
            $prop = strtoupper(trim($matches[1]));
            $val = trim($matches[2]);

            switch ($prop) {
                case 'UID':
                    $current['uid'] = $val;
                    break;
                case 'DTSTART':
                    $current['dtstart'] = parse_ical_date_string($val);
                    break;
                case 'DTEND':
                    $current['dtend'] = parse_ical_date_string($val);
                    break;
                case 'SUMMARY':
                    $current['summary'] = stripslashes($val);
                    break;
                case 'DESCRIPTION':
                    $current['description'] = stripslashes($val);
                    break;
                case 'STATUS':
                    $current['status'] = strtoupper($val);
                    break;
            }
        }
    }

    return $events;
}

/**
 * Helper to convert iCal date string (20260920, 20260920T140000Z, etc.) into 'YYYY-MM-DD'
 */
function parse_ical_date_string($str) {
    $str = trim($str);
    // Format YYYYMMDD
    if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $str, $m)) {
        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }
    // Fallback through strtotime
    $ts = strtotime($str);
    if ($ts) {
        return date('Y-m-d', $ts);
    }
    return '';
}

/**
 * Fetch calendar feed contents with timeout and custom headers
 */
function fetch_ical_feed_url($url) {
    $url = trim($url);
    if (empty($url)) return false;

    // Webcal to https conversion
    if (stripos($url, 'webcal://') === 0) {
        $url = 'https://' . substr($url, 9);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) FoodForest-CalendarSync/2.0 (Hospitality iCal Exchanger)');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($content === false || ($http_code !== 200 && $http_code !== 304)) {
        throw new Exception("HTTP Error: " . ($err ?: "Status Code " . $http_code));
    }

    return $content;
}

/**
 * Synchronize a single iCal feed record into the bookings database
 */
function sync_single_feed($pdo, $feed_id) {
    ensure_ical_and_channel_schema($pdo);

    $stmt = $pdo->prepare("SELECT * FROM room_ical_feeds WHERE id = ?");
    $stmt->execute([(int)$feed_id]);
    $feed = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$feed) {
        return ['success' => false, 'message' => 'Feed record not found.'];
    }

    if (empty($feed['feed_url'])) {
        return ['success' => false, 'message' => 'Feed URL is empty.'];
    }

    try {
        $raw_ics = fetch_ical_feed_url($feed['feed_url']);
        if (!$raw_ics) {
            throw new Exception("Unable to retrieve calendar feed stream.");
        }

        $events = parse_ical_content($raw_ics);
        $channel_name = trim($feed['channel_name'] ?: 'OTA Sync');
        $room_slug = $feed['room_slug'];

        $synced_uids = [];
        $active_count = 0;

        foreach ($events as $ev) {
            // Ignore CANCELLED events
            if ($ev['status'] === 'CANCELLED') continue;

            $uid = $ev['uid'];
            $cin = $ev['dtstart'];
            $cout = $ev['dtend'];

            if (empty($cin) || empty($cout) || $cin >= $cout) continue;

            $synced_uids[] = $uid;
            $active_count++;

            // Calculate nights
            $d1 = new DateTime($cin);
            $d2 = new DateTime($cout);
            $nights = max(1, $d1->diff($d2)->days);

            // Clean summary name
            $summary_title = !empty($ev['summary']) ? $ev['summary'] : "{$channel_name} Booking";
            $guest_name = "{$channel_name} Guest ({$summary_title})";
            $notes = "Automated Calendar Sync from {$channel_name}.\nExternal UID: {$uid}\n" . ($ev['description'] ?? '');

            // Check if this reservation already exists by external_uid
            $check_stmt = $pdo->prepare("SELECT id, status, checkin_date, checkout_date FROM bookings WHERE external_uid = ? AND villa_type = ?");
            $check_stmt->execute([$uid, $room_slug]);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing record
                $upd = $pdo->prepare("UPDATE bookings SET 
                    checkin_date = ?, 
                    checkout_date = ?, 
                    nights = ?, 
                    status = 'confirmed',
                    booking_source = ?,
                    special_notes = ? 
                    WHERE id = ?");
                $upd->execute([$cin, $cout, $nights, $channel_name, $notes, $existing['id']]);
            } else {
                // Insert new reservation block
                $ref = 'OTA-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $channel_name), 0, 3)) . '-' . date('Ymd') . '-' . substr(md5($uid), 0, 5);
                $ins = $pdo->prepare("INSERT INTO bookings 
                    (reference_code, villa_type, booking_source, external_uid, guest_name, guest_phone, guest_email, guests_count, checkin_date, checkout_date, nights, special_notes, total_amount, status) 
                    VALUES (?, ?, ?, ?, ?, 'OTA-Channel', 'ota@foodforest.in', 2, ?, ?, ?, ?, 0.00, 'confirmed')");
                $ins->execute([$ref, $room_slug, $channel_name, $uid, $guest_name, $cin, $cout, $nights, $notes]);
            }
        }

        // Cancel external reservations that were removed from the feed
        if (!empty($synced_uids)) {
            $in_placeholders = implode(',', array_fill(0, count($synced_uids), '?'));
            $cancel_params = array_merge([$room_slug, $channel_name], $synced_uids);
            $cancel_stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' 
                WHERE villa_type = ? AND booking_source = ? AND external_uid IS NOT NULL AND external_uid NOT IN ($in_placeholders)");
            $cancel_stmt->execute($cancel_params);
        }

        // Update feed sync status
        $feed_upd = $pdo->prepare("UPDATE room_ical_feeds SET 
            last_synced_at = NOW(), 
            sync_status = 'success', 
            sync_error = NULL, 
            sync_events_count = ? 
            WHERE id = ?");
        $feed_upd->execute([$active_count, $feed['id']]);

        return [
            'success' => true,
            'message' => "Successfully synced {$active_count} reservations from {$channel_name}.",
            'events_count' => $active_count
        ];
    } catch (Exception $e) {
        $feed_upd = $pdo->prepare("UPDATE room_ical_feeds SET 
            last_synced_at = NOW(), 
            sync_status = 'error', 
            sync_error = ? 
            WHERE id = ?");
        $feed_upd->execute([$e->getMessage(), $feed['id']]);

        return [
            'success' => false,
            'message' => "Sync error for {$feed['channel_name']}: " . $e->getMessage()
        ];
    }
}

/**
 * Synchronize all active iCal feeds across all chalets
 */
function sync_all_ical_feeds($pdo) {
    ensure_ical_and_channel_schema($pdo);

    $feeds = $pdo->query("SELECT id, channel_name, room_slug FROM room_ical_feeds WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    $results = [];
    $total_events = 0;
    $success_count = 0;

    foreach ($feeds as $f) {
        $res = sync_single_feed($pdo, $f['id']);
        $results[] = [
            'feed_id' => $f['id'],
            'channel' => $f['channel_name'],
            'room' => $f['room_slug'],
            'result' => $res
        ];
        if (!empty($res['success'])) {
            $success_count++;
            $total_events += ($res['events_count'] ?? 0);
        }
    }

    return [
        'success' => true,
        'total_feeds' => count($feeds),
        'successful_feeds' => $success_count,
        'total_events_synced' => $total_events,
        'details' => $results
    ];
}
?>
