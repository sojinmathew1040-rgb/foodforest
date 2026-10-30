<?php
ob_start();
// =========================================================================
// Food Forest Sanctuary — RFC 5545 iCalendar (.ics) Feed Exporter
// Endpoints:
//   GET/HEAD /api/ical_export.php?room={slug}&channel={optional_ota}
//   GET/HEAD /ical/{slug}.ics
// Compatible with Goibibo/MakeMyTrip (InGo-MMT), Booking.com, Airbnb, Agoda
// =========================================================================

require_once __DIR__ . '/../admin/includes/db.php';

// Helper: Escape & sanitize text per RFC 5545 §3.3.11 (ASCII-safe for Java ical4j / InGo-MMT)
function ical_escape_text($str) {
    $str = str_replace(['—', '–', '•'], ['-', '-', '-'], (string)$str);
    $str = preg_replace('/[\x00-\x1F\x7F]/', ' ', $str);
    $str = str_replace(['\\', ';', ','], ['\\\\', '\;', '\,'], $str);
    return trim($str);
}

// Helper: Fold lines > 73 octets per RFC 5545 §3.1
function ical_fold_line($line) {
    if (strlen($line) <= 73) {
        return $line . "\r\n";
    }
    $out = '';
    while (strlen($line) > 73) {
        $out .= substr($line, 0, 73) . "\r\n ";
        $line = substr($line, 73);
    }
    return $out . $line . "\r\n";
}

$room_slug = trim($_GET['room'] ?? ($_GET['slug'] ?? 'all'));
// Strip trailing .ics if passed in room param
$room_slug = preg_replace('/\.ics$/i', '', $room_slug);

$req_channel = strtolower(trim($_GET['channel'] ?? ($_GET['for'] ?? '')));
if ($req_channel === '') {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'goibibo') !== false || strpos($ua, 'makemytrip') !== false || strpos($ua, 'ingommt') !== false || strpos($ua, 'ingo') !== false) {
        $req_channel = 'goibibo';
    } elseif (strpos($ua, 'booking.com') !== false || strpos($ua, 'booking') !== false) {
        $req_channel = 'booking.com';
    } elseif (strpos($ua, 'airbnb') !== false) {
        $req_channel = 'airbnb';
    } elseif (strpos($ua, 'agoda') !== false) {
        $req_channel = 'agoda';
    }
}

$pdo = get_db();
ensure_ical_and_channel_schema($pdo);

// Find room info & maintenance status
$room_title = 'All Chalets';
$room_closed = false;
if ($room_slug !== 'all') {
    $r_stmt = $pdo->prepare("SELECT title, is_available, availability_status FROM rooms WHERE slug = ?");
    $r_stmt->execute([$room_slug]);
    $found_room = $r_stmt->fetch(PDO::FETCH_ASSOC);
    if ($found_room) {
        $room_title = $found_room['title'] ?: $room_slug;
        $avail_status = $found_room['availability_status'] ?? (!empty($found_room['is_available']) ? 'available' : 'maintenance');
        if ($avail_status !== 'available' || empty($found_room['is_available'])) {
            $room_closed = true;
        }
    }
}

// Fetch active bookings (including multi-chalet comma-separated reservations, excluding cancelled/rejected/waitlist)
$sql = "SELECT id, reference_code, villa_type, guest_name, checkin_date, checkout_date, status, booking_source, external_uid, created_at
        FROM bookings
        WHERE status NOT IN ('cancelled', 'rejected', 'waitlist')";

$params = [];
if ($room_slug !== 'all') {
    $sql .= " AND (villa_type = ? OR FIND_IN_SET(?, REPLACE(villa_type, ' ', '')))";
    $params[] = $room_slug;
    $params[] = $room_slug;
}

// Prevent circular OTA echo loops: do not echo an OTA's own imported bookings back to that same OTA
if ($req_channel !== '') {
    if (in_array($req_channel, ['goibibo', 'makemytrip', 'ingommt', 'mmt'], true)) {
        $sql .= " AND LOWER(COALESCE(booking_source, '')) NOT IN ('goibibo', 'makemytrip', 'ingommt', 'mmt')";
    } elseif (in_array($req_channel, ['booking', 'booking.com'], true)) {
        $sql .= " AND LOWER(COALESCE(booking_source, '')) NOT IN ('booking.com', 'booking')";
    } elseif ($req_channel === 'airbnb') {
        $sql .= " AND LOWER(COALESCE(booking_source, '')) != 'airbnb'";
    } elseif ($req_channel === 'agoda') {
        $sql .= " AND LOWER(COALESCE(booking_source, '')) != 'agoda'";
    }
}

$sql .= " ORDER BY checkin_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cal_name = 'Food Forest Sanctuary - ' . $room_title;
$now_utc = gmdate('Ymd\THis\Z');

$ics = '';
$ics .= "BEGIN:VCALENDAR\r\n";
$ics .= "VERSION:2.0\r\n";
$ics .= "PRODID:-//Food Forest Sanctuary Kanthalloor//ChannelSync 2.5//EN\r\n";
$ics .= "CALSCALE:GREGORIAN\r\n";
$ics .= "METHOD:PUBLISH\r\n";
$ics .= ical_fold_line("X-WR-CALNAME:" . ical_escape_text($cal_name));
$ics .= "X-WR-TIMEZONE:Asia/Kolkata\r\n";
$ics .= "REFRESH-INTERVAL;VALUE=DURATION:PT10M\r\n";
$ics .= "X-PUBLISHED-TTL:PT10M\r\n";

// Standard VTIMEZONE block for strict RFC 5545 / InGo-MMT / ical4j validators
$ics .= "BEGIN:VTIMEZONE\r\n";
$ics .= "TZID:Asia/Kolkata\r\n";
$ics .= "X-LIC-LOCATION:Asia/Kolkata\r\n";
$ics .= "BEGIN:STANDARD\r\n";
$ics .= "TZOFFSETFROM:+0530\r\n";
$ics .= "TZOFFSETTO:+0530\r\n";
$ics .= "TZNAME:IST\r\n";
$ics .= "DTSTART:19700101T000000\r\n";
$ics .= "END:STANDARD\r\n";
$ics .= "END:VTIMEZONE\r\n";

// If the room is marked Closed / Under Maintenance in CMS, block upcoming 180 days on OTAs
if ($room_closed && $room_slug !== 'all') {
    $m_start = gmdate('Ymd');
    $m_end = gmdate('Ymd', strtotime('+180 days'));
    $m_uid = 'FF-MAINT-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $room_slug) . '@foodforestkanthalloor.com';
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= ical_fold_line("UID:" . $m_uid);
    $ics .= "DTSTAMP:" . $now_utc . "\r\n";
    $ics .= "DTSTART;VALUE=DATE:" . $m_start . "\r\n";
    $ics .= "DTEND;VALUE=DATE:" . $m_end . "\r\n";
    $ics .= "SUMMARY:Closed - Sanctuary Maintenance\r\n";
    $ics .= "STATUS:CONFIRMED\r\n";
    $ics .= "TRANSP:OPAQUE\r\n";
    $ics .= "END:VEVENT\r\n";
}

foreach ($bookings as $b) {
    $cin_ts = strtotime($b['checkin_date']);
    $cout_ts = strtotime($b['checkout_date']);
    if (!$cin_ts || !$cout_ts || $cout_ts <= $cin_ts) {
        continue;
    }
    $cin = date('Ymd', $cin_ts);
    $cout = date('Ymd', $cout_ts);
    $safe_ref = preg_replace('/[^a-zA-Z0-9]/', '', (string)$b['reference_code']);
    $uid = 'FF-' . (int)$b['id'] . '-' . $cin . '-' . $safe_ref . '@foodforestkanthalloor.com';
    $created_ts = !empty($b['created_at']) ? gmdate('Ymd\THis\Z', strtotime($b['created_at'])) : $now_utc;

    $source_label = (!empty($b['booking_source']) && $b['booking_source'] !== 'direct_website')
        ? strtoupper(preg_replace('/[^a-zA-Z0-9 ._-]/', '', $b['booking_source']))
        : 'DIRECT';
    $summary = 'CLOSED - Not Available (' . $source_label . ')';
    $description = 'Chalet: ' . $room_slug . ' | Ref: ' . ($b['reference_code'] ?: ('#' . $b['id']));

    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= ical_fold_line("UID:" . $uid);
    $ics .= "DTSTAMP:" . $now_utc . "\r\n";
    $ics .= "CREATED:" . $created_ts . "\r\n";
    $ics .= "LAST-MODIFIED:" . $now_utc . "\r\n";
    $ics .= "DTSTART;VALUE=DATE:" . $cin . "\r\n";
    $ics .= "DTEND;VALUE=DATE:" . $cout . "\r\n";
    $ics .= ical_fold_line("SUMMARY:" . ical_escape_text($summary));
    $ics .= ical_fold_line("DESCRIPTION:" . ical_escape_text($description));
    $ics .= "SEQUENCE:0\r\n";
    $ics .= "STATUS:CONFIRMED\r\n";
    $ics .= "TRANSP:OPAQUE\r\n";
    $ics .= "END:VEVENT\r\n";
}

$ics .= "END:VCALENDAR\r\n";

while (ob_get_level() > 0) {
    @ob_end_clean();
}

// Clean any duplicate headers injected upstream so strict Java/OTA HTTP parsers succeed
if (!headers_sent()) {
    @header_remove('Cache-Control');
    @header_remove('Pragma');
    @header_remove('Expires');
    @header_remove('X-Powered-By');

    $safe_file = preg_replace('/[^a-z0-9_-]/i', '', $room_slug) ?: 'all';
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="foodforest-' . $safe_file . '.ics"');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Accel-Expires: 0');
    header('X-LiteSpeed-Cache-Control: no-cache, no-store');
    header('CDN-Cache-Control: no-store');
    header('Surrogate-Control: no-store');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Content-Length: ' . strlen($ics));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit;
}

echo $ics;
