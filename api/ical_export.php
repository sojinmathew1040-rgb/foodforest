<?php
// =========================================================================
// Food Forest Sanctuary — iCalendar (.ics) Feed Exporter
// Endpoint: GET /api/ical_export.php?room={slug}&key={optional_token}
// Compatible with MakeMyTrip InGoMMT, Airbnb, Booking.com, Agoda, Google Calendar
// =========================================================================

require_once __DIR__ . '/../admin/includes/db.php';

$room_slug = trim($_GET['room'] ?? ($_GET['slug'] ?? 'all'));
$pdo = get_db();
ensure_ical_and_channel_schema($pdo);

// Find room info
$room_title = 'All Chalets';
if ($room_slug !== 'all') {
    $r_stmt = $pdo->prepare("SELECT title FROM rooms WHERE slug = ?");
    $r_stmt->execute([$room_slug]);
    $found_title = $r_stmt->fetchColumn();
    if ($found_title) {
        $room_title = $found_title;
    }
}

// Fetch active bookings
$sql = "SELECT id, reference_code, villa_type, guest_name, checkin_date, checkout_date, status, booking_source, created_at 
        FROM bookings 
        WHERE status NOT IN ('cancelled', 'rejected')";

$params = [];
if ($room_slug !== 'all') {
    $sql .= " AND villa_type = ?";
    $params[] = $room_slug;
}

$sql .= " ORDER BY checkin_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output RFC 5545 iCalendar stream
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="foodforest-' . preg_replace('/[^a-z0-9_-]/i', '', $room_slug) . '.ics"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$cal_name = 'Food Forest Sanctuary — ' . $room_title;
$now_utc = gmdate('Ymd\THis\Z');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//Food Forest Sanctuary Kanthalloor//Reservations//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:" . $cal_name . "\r\n";
echo "X-WR-TIMEZONE:Asia/Kolkata\r\n";
echo "X-PUBLISHED-TTL:PT15M\r\n";

foreach ($bookings as $b) {
    $cin = str_replace('-', '', $b['checkin_date']);
    $cout = str_replace('-', '', $b['checkout_date']);
    $uid = 'FF-' . $b['id'] . '-' . $cin . '-' . preg_replace('/[^a-zA-Z0-9]/', '', $b['reference_code']) . '@foodforest.in';
    $created_ts = !empty($b['created_at']) ? gmdate('Ymd\THis\Z', strtotime($b['created_at'])) : $now_utc;
    
    // Privacy compliant summary for OTAs (MakeMyTrip, Airbnb, Booking.com)
    $source_label = ($b['booking_source'] && $b['booking_source'] !== 'direct_website') ? strtoupper($b['booking_source']) : 'DIRECT';
    $summary = 'Reserved — Food Forest (' . $source_label . ')';
    $description = 'Chalet Stay: ' . $b['villa_type'] . ' | Ref: ' . $b['reference_code'];

    echo "BEGIN:VEVENT\r\n";
    echo "UID:" . $uid . "\r\n";
    echo "DTSTAMP:" . $now_utc . "\r\n";
    echo "CREATED:" . $created_ts . "\r\n";
    echo "LAST-MODIFIED:" . $now_utc . "\r\n";
    echo "DTSTART;VALUE=DATE:" . $cin . "\r\n";
    echo "DTEND;VALUE=DATE:" . $cout . "\r\n";
    echo "SUMMARY:" . $summary . "\r\n";
    echo "DESCRIPTION:" . $description . "\r\n";
    echo "STATUS:CONFIRMED\r\n";
    echo "TRANSP:OPAQUE\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
