<?php
// =========================================================================
// Food Forest Sanctuary — Public Reservation Submission API
// Endpoint: POST /api/book.php
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Support both JSON payload and regular application/x-www-form-urlencoded
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$guest_name = trim($input['name'] ?? ($input['guest_name'] ?? ''));
$guest_phone = trim($input['phone'] ?? ($input['guest_phone'] ?? ''));
$guest_email = trim($input['email'] ?? ($input['guest_email'] ?? ''));
$villa_type = trim($input['villa'] ?? ($input['villa_type'] ?? 'treehouse'));
$guests_count = (int)($input['guests'] ?? ($input['guests_count'] ?? 2));
$checkin_date = trim($input['checkin'] ?? ($input['checkin_date'] ?? ''));
$checkout_date = trim($input['checkout'] ?? ($input['checkout_date'] ?? ''));
$special_notes = trim($input['notes'] ?? ($input['special_notes'] ?? ''));
$addons_input = $input['addons'] ?? '';

if (is_array($addons_input)) {
    $addons = implode(', ', $addons_input);
} else {
    $addons = trim((string)$addons_input);
}

if (empty($guest_name) || empty($guest_phone) || empty($checkin_date) || empty($checkout_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide full name, contact number, and stay dates.']);
    exit;
}

try {
    $pdo = get_db();

    // Calculate nights
    $cin = new DateTime($checkin_date);
    $cout = new DateTime($checkout_date);
    $nights = max(1, $cin->diff($cout)->days);

    // Retrieve active room rate
    $room_stmt = $pdo->prepare("SELECT rate_per_night, title FROM rooms WHERE slug = ?");
    $room_stmt->execute([$villa_type]);
    $room = $room_stmt->fetch();
    $rate_per_night = $room ? (float)$room['rate_per_night'] : ($villa_type === 'treehouse' ? 14500 : 11500);
    $villa_title = $room ? $room['title'] : ($villa_type === 'treehouse' ? 'Luxury Canopy Treehouse' : 'Traditional Earthen Mudhouse');

    // Calculate total amount
    $base_total = $rate_per_night * $nights;
    $addons_total = 0;
    if (stripos($addons, 'dinner') !== false) $addons_total += 3000;
    if (stripos($addons, 'pottery') !== false) $addons_total += 1500;
    if (stripos($addons, 'trek') !== false) $addons_total += 2000;

    $total_amount = $base_total + $addons_total;
    if (!empty($input['total_amount'])) {
        $total_amount = (float)$input['total_amount'];
    }

    // Generate unique reference code
    $ref_code = 'FF-' . rand(2000, 9999);

    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            reference_code, villa_type, guest_name, guest_phone, guest_email,
            guests_count, checkin_date, checkout_date, nights, addons, special_notes,
            total_amount, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $ref_code,
        $villa_type,
        $guest_name,
        $guest_phone,
        $guest_email,
        $guests_count,
        $checkin_date,
        $checkout_date,
        $nights,
        $addons,
        $special_notes,
        $total_amount
    ]);

    // Fetch concierge WhatsApp
    $whatsapp_num = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'concierge_whatsapp'")->fetchColumn() ?: '919234567890';

    echo json_encode([
        'success' => true,
        'reference_code' => $ref_code,
        'guest_name' => $guest_name,
        'villa_title' => $villa_title,
        'checkin_date' => $checkin_date,
        'checkout_date' => $checkout_date,
        'nights' => $nights,
        'total_amount' => $total_amount,
        'concierge_whatsapp' => $whatsapp_num,
        'message' => "Your reservation request ($ref_code) has been submitted! Our Master Concierge will confirm shortly."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to record reservation. Please contact concierge directly.',
        'error' => $e->getMessage()
    ]);
}
