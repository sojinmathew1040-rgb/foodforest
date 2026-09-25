<?php
// =========================================================================
// Food Forest Sanctuary — Real-Time Date & Property Availability Check API
// Endpoint: GET / POST /api/check_availability.php
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/includes/db.php';

$input = $_REQUEST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $input = json_decode($raw, true) ?: [];
    }
}

$checkin = trim($input['checkin'] ?? ($input['checkin_date'] ?? ''));
$checkout = trim($input['checkout'] ?? ($input['checkout_date'] ?? ''));
$requested_villa = trim($input['villa'] ?? ($input['room_slug'] ?? ($input['villa_type'] ?? '')));

if (empty($checkin) || empty($checkout)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Check-in and Check-out dates are required.'
    ]);
    exit;
}

try {
    $d1 = new DateTime($checkin);
    $d2 = new DateTime($checkout);
    
    if ($d1 >= $d2) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Check-out date must be after check-in date.'
        ]);
        exit;
    }
    
    $nights = max(1, $d1->diff($d2)->days);
    $pdo = get_db();
    
    $res = get_all_rooms_availability_for_dates($pdo, $checkin, $checkout);
    
    if (!$res || empty($res['success'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $res['message'] ?? 'Could not calculate room availability.'
        ]);
        exit;
    }
    
    $is_requested_available = true;
    $requested_message = '';
    $requested_room_title = '';
    
    if (!empty($requested_villa)) {
        $slug = strtolower($requested_villa);
        $matched_status = null;
        if (isset($res['rooms_status'][$slug])) {
            $matched_status = $res['rooms_status'][$slug];
        } else {
            foreach ($res['rooms_status'] as $rs_slug => $rs_data) {
                if (strpos($slug, $rs_slug) !== false || strpos($rs_slug, $slug) !== false ||
                    (strpos($slug, 'treehouse') !== false && strpos($rs_slug, 'treehouse') !== false) ||
                    (strpos($slug, 'mudhouse') !== false && strpos($rs_slug, 'mudhouse') !== false) ||
                    (strpos($slug, 'woodhouse') !== false && strpos($rs_slug, 'woodhouse') !== false)) {
                    $matched_status = $rs_data;
                    break;
                }
            }
        }
        
        if ($matched_status) {
            $is_requested_available = $matched_status['available'];
            $requested_message = $matched_status['message'];
            $requested_room_title = $matched_status['title'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'nights' => $nights,
        'checkin_formatted' => $d1->format('d M Y'),
        'checkout_formatted' => $d2->format('d M Y'),
        'requested_villa' => $requested_villa,
        'requested_room_title' => $requested_room_title,
        'is_available' => $is_requested_available,
        'message' => $requested_message,
        'rooms_status' => $res['rooms_status'],
        'spots_status' => $res['spots_status'],
        'summary' => $res['summary'],
        'overlapping_bookings' => array_map(function($b) {
            return [
                'reference_code' => $b['reference_code'],
                'villa_type' => $b['villa_type'],
                'checkin_date' => $b['checkin_date'],
                'checkout_date' => $b['checkout_date'],
                'booking_source' => $b['booking_source']
            ];
        }, $res['overlapping_bookings'] ?? [])
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
