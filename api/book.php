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
$city_state = trim($input['city_state'] ?? ($input['city'] ?? ''));
$id_proof_type = trim($input['id_proof_type'] ?? 'Aadhaar Card');
$id_proof_number = trim($input['id_proof_number'] ?? '');
$country = trim($input['country'] ?? 'India');
$villa_type = trim($input['villa'] ?? ($input['villa_type'] ?? 'treehouse'));

$adults_count = isset($input['adults']) ? max(1, (int)$input['adults']) : (isset($input['adults_count']) ? max(1, (int)$input['adults_count']) : 0);
$kids_count = isset($input['kids']) ? max(0, (int)$input['kids']) : (isset($input['kids_count']) ? max(0, (int)$input['kids_count']) : 0);
if ($adults_count === 0 && $kids_count === 0) {
    $guests_count = max(1, (int)($input['guests'] ?? ($input['guests_count'] ?? 2)));
    $adults_count = $guests_count;
    $kids_count = 0;
} else {
    $guests_count = $adults_count + $kids_count;
}

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
    ensure_rooms_pricing_columns($pdo);

    // Calculate nights
    $cin = new DateTime($checkin_date);
    $cout = new DateTime($checkout_date);
    $nights = max(1, $cin->diff($cout)->days);

    // Dual Adult & Child Occupancy Math:
    // Retrieve active room specifications
    $room_stmt = $pdo->prepare("SELECT rate_per_night, single_room_rate, title, base_guests, max_guests, extra_guest_rate, extra_child_rate, stay_type, structure_type FROM rooms WHERE slug = ?");
    $room_stmt->execute([$villa_type]);
    $room = $room_stmt->fetch(PDO::FETCH_ASSOC);

    // Double-Booking & Multi-Channel Availability Check
    if (!check_room_availability($pdo, $villa_type, $checkin_date, $checkout_date)) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'We apologize, but ' . ($room['title'] ?? 'this chalet') . ' has already been reserved for the selected dates (via Direct Website, MakeMyTrip, or Airbnb). Please select alternative dates.'
        ]);
        exit;
    }

    $booking_tier = trim($input['tier'] ?? ($input['pricing_tier'] ?? 'full'));
    $rate_per_night = $room ? (float)$room['rate_per_night'] : 14500;
    $base_guests = $room ? (int)($room['base_guests'] ?? 2) : 2;

    // If single room option in duplex is selected
    if ($booking_tier === 'single_room' && !empty($room['single_room_rate'])) {
        $rate_per_night = (float)$room['single_room_rate'];
        $base_guests = 2; // Single room accommodates base 2 guests
    }

    $villa_title = $room ? $room['title'] : 'Sanctuary Villa';
    if ($booking_tier === 'single_room' && !empty($room['structure_type']) && $room['structure_type'] === 'duplex_hut') {
        $villa_title .= ' (Single Room)';
    }

    $extra_adult_rate = $room ? (float)($room['extra_guest_rate'] ?? 1500) : 1500;
    $extra_child_rate = $room ? (float)($room['extra_child_rate'] ?? 800) : 800;

    // Dual Adult & Child Occupancy Math:
    // Adults fill the base slots first
    $adults_in_base = min($adults_count, $base_guests);
    $extra_adults = max(0, $adults_count - $adults_in_base);
    $remaining_base_slots = max(0, $base_guests - $adults_in_base);
    $kids_in_base = min($kids_count, $remaining_base_slots);
    $extra_kids = max(0, $kids_count - $kids_in_base);

    $extra_adults_fee = $extra_adults * $extra_adult_rate * $nights;
    $extra_kids_fee = $extra_kids * $extra_child_rate * $nights;
    $extra_guests_fee = $extra_adults_fee + $extra_kids_fee;

    // Calculate room stay total
    $base_total = $rate_per_night * $nights;
    $room_total = $base_total + $extra_guests_fee;

    // Experiences Addons are payable on-site directly to local guides (0 in advance bill)
    $addons_total = 0;

    // Process Food Menu Selections
    $food_items_raw = $input['food_items'] ?? [];
    if (is_string($food_items_raw)) {
        $food_items_array = json_decode($food_items_raw, true) ?: [];
    } else {
        $food_items_array = is_array($food_items_raw) ? $food_items_raw : [];
    }

    $verified_food_items = [];
    $food_total = 0.00;
    $food_status = (!empty($input['food_skipped']) || empty($food_items_array)) ? 'skipped' : 'selected';

    if ($food_status === 'selected') {
        foreach ($food_items_array as $fi) {
            $qty = max(0, (int)($fi['quantity'] ?? ($fi['sets'] ?? 0)));
            $cat = trim($fi['category'] ?? 'general');
            // Breakfast is complimentary (price = 0)
            $price = ($cat === 'breakfast') ? 0.00 : max(0, (float)($fi['price'] ?? 0));
            if ($qty > 0 && !empty($fi['heading'])) {
                $subtotal = $qty * $price;
                $food_total += $subtotal;
                $verified_food_items[] = [
                    'id' => (int)($fi['id'] ?? 0),
                    'category' => $cat,
                    'category_title' => trim($fi['category_title'] ?? ucfirst($cat)),
                    'heading' => trim($fi['heading']),
                    'subtitle' => trim($fi['subtitle'] ?? ''),
                    'price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                    'inclusions' => is_array($fi['inclusions'] ?? null) ? $fi['inclusions'] : []
                ];
            }
        }
        if (empty($verified_food_items)) {
            $food_status = 'skipped';
        }
    }

    // Billing & GST Calculation
    ensure_booking_gst_columns($pdo);

    $billing_type = strtolower(trim($input['billing_type'] ?? 'estimate'));
    if ($billing_type !== 'gst') {
        $billing_type = 'estimate';
    }
    $guest_gst_number = ($billing_type === 'gst') ? strtoupper(trim($input['gst_number'] ?? '')) : null;
    $billing_name = ($billing_type === 'gst') ? trim($input['billing_name'] ?? '') : null;
    $billing_address = ($billing_type === 'gst') ? trim($input['billing_address'] ?? '') : null;

    $subtotal_amount = $room_total + $food_total;
    $system_gst_rate = (float)get_setting('gst_rate_percentage', '12');

    if ($billing_type === 'gst') {
        $gst_percentage = (!empty($input['gst_percentage']) && (float)$input['gst_percentage'] > 0) ? (float)$input['gst_percentage'] : $system_gst_rate;
        $gst_amount = round($subtotal_amount * ($gst_percentage / 100), 2);
        $tax_amount = $gst_amount;
        $total_amount = $subtotal_amount + $gst_amount;
    } else {
        $gst_percentage = 0.00;
        $gst_amount = 0.00;
        $tax_amount = 0.00;
        $total_amount = $subtotal_amount;
    }

    // Client Authentication & User Association
    require_once __DIR__ . '/../includes/client_auth.php';
    ensure_users_and_guest_columns($pdo);

    $user_id = null;
    $is_guest = 1;
    $guest_access_token = null;
    $expires_at = null;

    if (is_client_user_logged_in()) {
        $user_id = (int)$_SESSION['ff_client_user_id'];
        $is_guest = 0;
    } elseif (!empty($input['create_account']) && !empty($input['password'])) {
        // Register permanent account
        $reg = register_client_user($guest_name, $guest_email, $guest_phone, $input['password']);
        if ($reg['success']) {
            $user_id = $reg['user']['id'];
            $is_guest = 0;
            set_client_user_session($reg['user']);
        }
    }

    if ($is_guest) {
        // Generate secure 4-digit passcode & 30-day auto-destruct date
        $guest_access_token = str_pad(strval(rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
    }

    // Generate unique reference code
    $ref_code = 'FF-' . rand(2000, 9999);

    // Handle Government ID Proof File Upload
    $id_proof_file = null;
    if (!empty($_FILES['id_proof_file']) && $_FILES['id_proof_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['id_proof_file'];
        $upload_dir = __DIR__ . '/../uploads/id_proofs/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        $file_name = basename($uploaded_file['name']);
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($ext, $allowed_exts) && $uploaded_file['size'] <= 8 * 1024 * 1024) {
            $safe_ref = preg_replace('/[^a-zA-Z0-9_-]/', '', $ref_code);
            $new_filename = 'id_' . strtolower($safe_ref) . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($uploaded_file['tmp_name'], $destination)) {
                $id_proof_file = $new_filename;
            }
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            reference_code, user_id, is_guest, guest_access_token, expires_at,
            villa_type, booking_source, billing_type, gst_number, billing_name, billing_address,
            gst_percentage, gst_amount, tax_amount,
            guest_name, guest_phone, guest_email,
            id_proof_type, id_proof_number, id_proof_file, city_state, country,
            guests_count, adults_count, kids_count, extra_adults, extra_kids,
            checkin_date, checkout_date, nights, addons,
            food_items, food_amount, room_amount, food_status,
            special_notes, total_amount, status
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, 'direct_website', ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, 'pending'
        )
    ");

    $stmt->execute([
        $ref_code,
        $user_id,
        $is_guest,
        $guest_access_token,
        $expires_at,
        $villa_type,
        $billing_type,
        $guest_gst_number,
        $billing_name,
        $billing_address,
        $gst_percentage,
        $gst_amount,
        $tax_amount,
        $guest_name,
        $guest_phone,
        $guest_email,
        $id_proof_type,
        $id_proof_number,
        $id_proof_file,
        $city_state,
        $country,
        $guests_count,
        $adults_count,
        $kids_count,
        $extra_adults,
        $extra_kids,
        $checkin_date,
        $checkout_date,
        $nights,
        $addons,
        !empty($verified_food_items) ? json_encode($verified_food_items) : null,
        $food_total,
        $room_total,
        $food_status,
        $special_notes,
        $total_amount
    ]);

    // Set guest session for instant access if booking as guest
    if ($is_guest) {
        set_guest_booking_session([
            'reference_code' => $ref_code,
            'guest_name' => $guest_name
        ]);
    }

    // Fetch concierge WhatsApp
    $whatsapp_num = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'concierge_whatsapp'")->fetchColumn() ?: '919234567890';

    $receipt_url = "receipt.php?ref=" . urlencode($ref_code);
    if ($guest_access_token) {
        $receipt_url .= "&passcode=" . urlencode($guest_access_token);
    }

    echo json_encode([
        'success' => true,
        'reference_code' => $ref_code,
        'is_guest' => (bool)$is_guest,
        'guest_access_token' => $guest_access_token,
        'expires_at' => $expires_at,
        'expires_date_formatted' => $expires_at ? date('d M Y', strtotime($expires_at)) : null,
        'guest_name' => $guest_name,
        'villa_title' => $villa_title,
        'adults_count' => $adults_count,
        'kids_count' => $kids_count,
        'checkin_date' => $checkin_date,
        'checkout_date' => $checkout_date,
        'nights' => $nights,
        'room_total' => $room_total,
        'food_total' => $food_total,
        'subtotal_amount' => $subtotal_amount,
        'billing_type' => $billing_type,
        'is_gst_bill' => ($billing_type === 'gst'),
        'gst_percentage' => $gst_percentage,
        'gst_amount' => $gst_amount,
        'tax_amount' => $tax_amount,
        'food_items_count' => count($verified_food_items),
        'food_status' => $food_status,
        'total_amount' => $total_amount,
        'receipt_url' => $receipt_url,
        'concierge_whatsapp' => $whatsapp_num,
        'message' => "Your reservation request ($ref_code) has been recorded! Your luxury booking receipt is ready."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to record reservation. Please contact concierge directly.',
        'error' => $e->getMessage()
    ]);
}
