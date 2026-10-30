<?php
// =========================================================================
// Food Forest Sanctuary — High-Speed Parallel 2-Way iCalendar Sync Engine
// File: admin/includes/ical_sync.php
// Handles RFC 5545 parser, parallel multi-cURL HTTPS fetchers, database sync,
// automatic background scheduling, and full cancellation reconciliation.
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
                if (empty($current['dtend'])) {
                    $d = new DateTime($current['dtstart']);
                    $d->modify('+1 day');
                    $current['dtend'] = $d->format('Y-m-d');
                }

                if (empty($current['uid'])) {
                    $current['uid'] = 'auto-' . md5($current['dtstart'] . '-' . $current['dtend'] . '-' . $current['summary']);
                }

                if ($current['dtend'] > $current['dtstart']) {
                    $events[] = $current;
                }
            }
            $in_event = false;
            $current = [];
            continue;
        }

        if (!$in_event) continue;

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
 * Convert iCal date string (20260920, 20260920T140000Z, etc.) into 'YYYY-MM-DD'
 */
function parse_ical_date_string($str) {
    $str = trim($str);
    if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $str, $m)) {
        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }
    $ts = strtotime($str);
    if ($ts) {
        return date('Y-m-d', $ts);
    }
    return '';
}

/**
 * Configure a cURL handle for fast, reliable OTA iCal feed fetching
 */
function build_ical_curl_handle($url) {
    $url = trim((string)$url);
    if (stripos($url, 'webcal://') === 0) {
        $url = 'https://' . substr($url, 9);
    }
    // Add cache-buster separator only if safe (some signed S3/OTA URLs forbid extra params, so use HTTP headers instead)
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_ENCODING       => '', // Accept gzip/deflate automatically for faster download
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; FoodForest-ChannelSync/2.5; +https://foodforestkanthalloor.com)',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/calendar, text/plain, */*;q=0.8',
            'Cache-Control: no-cache, no-store, max-age=0',
            'Pragma: no-cache'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    return $ch;
}

/**
 * Fetch single calendar feed contents
 */
function fetch_ical_feed_url($url) {
    $url = trim((string)$url);
    if (empty($url)) return false;

    $ch = build_ical_curl_handle($url);
    $content = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($content === false || ($http_code !== 200 && $http_code !== 304)) {
        throw new Exception("HTTP Error: " . ($err ?: "Status Code " . $http_code));
    }

    return $content;
}

/**
 * Process raw .ics text for a single feed row and reconcile bookings in DB
 */
function apply_ical_feed_payload(PDO $pdo, array $feed, $raw_ics) {
    if (empty($raw_ics) || stripos($raw_ics, 'BEGIN:VCALENDAR') === false) {
        throw new Exception("Invalid or empty iCalendar (.ics) stream received from channel.");
    }

    $events = parse_ical_content($raw_ics);
    $channel_name = trim($feed['channel_name'] ?: 'OTA Sync');
    $room_slug = $feed['room_slug'];

    $synced_uids = [];
    $active_count = 0;

    foreach ($events as $ev) {
        if ($ev['status'] === 'CANCELLED') continue;

        $uid = $ev['uid'];
        $cin = $ev['dtstart'];
        $cout = $ev['dtend'];

        if (empty($cin) || empty($cout) || $cin >= $cout) continue;

        // Ignore our own exported UIDs if an OTA accidentally echoed them back
        if (strpos($uid, 'FF-') === 0 && (strpos($uid, '@foodforest.in') !== false || strpos($uid, '@foodforestkanthalloor.com') !== false)) {
            continue;
        }

        $synced_uids[] = $uid;
        $active_count++;

        $d1 = new DateTime($cin);
        $d2 = new DateTime($cout);
        $nights = max(1, $d1->diff($d2)->days);

        $summary_title = !empty($ev['summary']) ? $ev['summary'] : "{$channel_name} Booking";
        $guest_name = "{$channel_name} Guest ({$summary_title})";
        $notes = "Automated Calendar Sync from {$channel_name}.\nExternal UID: {$uid}\n" . ($ev['description'] ?? '');

        $check_stmt = $pdo->prepare("SELECT id, status, checkin_date, checkout_date FROM bookings WHERE external_uid = ? AND villa_type = ?");
        $check_stmt->execute([$uid, $room_slug]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $upd = $pdo->prepare("UPDATE bookings SET 
                checkin_date = ?, 
                checkout_date = ?, 
                nights = ?, 
                status = IF(status IN ('inhouse', 'completed'), status, 'confirmed'),
                booking_source = ?,
                special_notes = ? 
                WHERE id = ?");
            $upd->execute([$cin, $cout, $nights, $channel_name, $notes, $existing['id']]);
        } else {
            $ref = 'OTA-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $channel_name), 0, 3)) . '-' . date('Ymd') . '-' . substr(md5($uid), 0, 5);
            $ins = $pdo->prepare("INSERT INTO bookings 
                (reference_code, villa_type, booking_source, external_uid, guest_name, guest_phone, guest_email, guests_count, checkin_date, checkout_date, nights, special_notes, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, 'OTA-Channel', 'ota@foodforest.in', 2, ?, ?, ?, ?, 0.00, 'confirmed')");
            $ins->execute([$ref, $room_slug, $channel_name, $uid, $guest_name, $cin, $cout, $nights, $notes]);
        }
    }

    // Reconcile cancellations: cancel any future OTA reservation for this room+channel missing from the live feed
    if (!empty($synced_uids)) {
        $in_placeholders = implode(',', array_fill(0, count($synced_uids), '?'));
        $cancel_params = array_merge([$room_slug, $channel_name], $synced_uids);
        $cancel_stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' 
            WHERE villa_type = ? AND booking_source = ? AND external_uid IS NOT NULL 
              AND checkout_date >= CURDATE() AND status NOT IN ('inhouse', 'completed', 'cancelled')
              AND external_uid NOT IN ($in_placeholders)");
        $cancel_stmt->execute($cancel_params);
    } else {
        // Feed is valid VCALENDAR with 0 active events -> cancel any upcoming external bookings from this channel
        $cancel_stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' 
            WHERE villa_type = ? AND booking_source = ? AND external_uid IS NOT NULL 
              AND checkout_date >= CURDATE() AND status NOT IN ('inhouse', 'completed', 'cancelled')");
        $cancel_stmt->execute([$room_slug, $channel_name]);
    }

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
        return apply_ical_feed_payload($pdo, $feed, $raw_ics);
    } catch (Exception $e) {
        $pdo->prepare("UPDATE room_ical_feeds SET last_synced_at = NOW(), sync_status = 'error', sync_error = ? WHERE id = ?")
            ->execute([$e->getMessage(), $feed['id']]);

        return [
            'success' => false,
            'message' => "Sync error for {$feed['channel_name']}: " . $e->getMessage()
        ];
    }
}

/**
 * Synchronize all active iCal feeds IN PARALLEL (curl_multi) for ~10x faster sync
 */
function sync_all_ical_feeds($pdo) {
    $start_time = microtime(true);
    ensure_ical_and_channel_schema($pdo);

    try {
        $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('ical_last_auto_sync', ?)")
            ->execute([(string)time()]);
    } catch (Exception $e) {}

    $feeds = $pdo->query("SELECT * FROM room_ical_feeds WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($feeds)) {
        return [
            'success' => true,
            'total_feeds' => 0,
            'successful_feeds' => 0,
            'total_events_synced' => 0,
            'elapsed_ms' => 0,
            'details' => []
        ];
    }

    $results = [];
    $total_events = 0;
    $success_count = 0;

    // Fetch all OTA feeds concurrently via curl_multi so 10+ channels complete in ~1-2s instead of 20s+
    if (function_exists('curl_multi_init')) {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($feeds as $idx => $f) {
            if (empty($f['feed_url'])) continue;
            $ch = build_ical_curl_handle($f['feed_url']);
            curl_multi_add_handle($mh, $ch);
            $handles[$idx] = $ch;
        }

        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 0.5);
            }
        } while ($running > 0 && $status == CURLM_OK);

        foreach ($feeds as $idx => $f) {
            if (!isset($handles[$idx])) {
                $res = ['success' => false, 'message' => 'Feed URL is empty.'];
            } else {
                $ch = $handles[$idx];
                $content = curl_multi_getcontent($ch);
                $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);

                try {
                    if ($content === false || ($http_code !== 200 && $http_code !== 304)) {
                        throw new Exception("HTTP Error: " . ($err ?: "Status Code " . $http_code));
                    }
                    $res = apply_ical_feed_payload($pdo, $f, $content);
                } catch (Exception $e) {
                    $pdo->prepare("UPDATE room_ical_feeds SET last_synced_at = NOW(), sync_status = 'error', sync_error = ? WHERE id = ?")
                        ->execute([$e->getMessage(), $f['id']]);
                    $res = [
                        'success' => false,
                        'message' => "Sync error for {$f['channel_name']}: " . $e->getMessage()
                    ];
                }
            }

            $results[] = [
                'feed_id' => (int)$f['id'],
                'channel' => $f['channel_name'],
                'room'    => $f['room_slug'],
                'result'  => $res
            ];
            if (!empty($res['success'])) {
                $success_count++;
                $total_events += ($res['events_count'] ?? 0);
            }
        }

        curl_multi_close($mh);
    } else {
        // Fallback sequential mode
        foreach ($feeds as $f) {
            $res = sync_single_feed($pdo, $f['id']);
            $results[] = [
                'feed_id' => (int)$f['id'],
                'channel' => $f['channel_name'],
                'room'    => $f['room_slug'],
                'result'  => $res
            ];
            if (!empty($res['success'])) {
                $success_count++;
                $total_events += ($res['events_count'] ?? 0);
            }
        }
    }

    $elapsed_ms = (int)round((microtime(true) - $start_time) * 1000);

    return [
        'success'             => true,
        'total_feeds'         => count($feeds),
        'successful_feeds'    => $success_count,
        'total_events_synced' => $total_events,
        'elapsed_ms'          => $elapsed_ms,
        'details'             => $results
    ];
}

/**
 * Returns true if automatic OTA background sync is due (default every 10 minutes = 600s)
 */
function is_ical_auto_sync_due($interval_seconds = 600) {
    $last = (int)get_setting('ical_last_auto_sync', '0');
    return (time() - $last) >= $interval_seconds;
}
