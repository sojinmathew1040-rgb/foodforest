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

    // Multi-Chalet & Single Chalet Handling
    $villas_raw = $input['villas'] ?? $villa_type;
    if (is_string($villas_raw) && strpos($villas_raw, ',') !== false) {
        $slugs_list = array_values(array_filter(array_map('trim', explode(',', $villas_raw))));
    } elseif (is_array($villas_raw)) {
        $slugs_list = array_values(array_filter(array_map('trim', $villas_raw)));
    } else {
        $slugs_list = [trim($villa_type)];
    }

    $is_multi_room = count($slugs_list) > 1;
    $booking_tier = trim($input['tier'] ?? ($input['pricing_tier'] ?? 'full'));
    $matched_rooms = [];
    $total_rate_per_night = 0.0;
    $total_base_guests = 0;
    $total_max_guests = 0;
    $room_titles = [];

    $room_stmt = $pdo->prepare("SELECT slug, rate_per_night, single_room_rate, title, base_guests, max_guests, extra_guest_rate, extra_child_rate, stay_type, structure_type FROM rooms WHERE slug = ?");

    foreach ($slugs_list as $s_slug) {
        $room_stmt->execute([$s_slug]);
        $r_data = $room_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r_data) {
            $all_r = $pdo->query("SELECT slug, rate_per_night, single_room_rate, title, base_guests, max_guests, extra_guest_rate, extra_child_rate, stay_type, structure_type FROM rooms")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($all_r as $ar) {
                if (stripos($ar['slug'], $s_slug) !== false || stripos($s_slug, $ar['slug']) !== false) {
                    $r_data = $ar;
                    break;
                }
            }
        }
        if (!$r_data) {
            $r_data = [
                'slug' => $s_slug,
                'rate_per_night' => 5000,
                'single_room_rate' => 5000,
                'title' => ucwords(str_replace('-', ' ', $s_slug)),
                'base_guests' => 2,
                'max_guests' => 4,
                'extra_guest_rate' => 750,
                'extra_child_rate' => 0,
                'stay_type' => 'treehouse',
                'structure_type' => 'single_hut'
            ];
        }

        // Double-Booking & Multi-Channel Availability Check for each chalet
        if (!check_room_availability($pdo, $r_data['slug'], $checkin_date, $checkout_date)) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'We apologize, but ' . ($r_data['title'] ?? 'one of the selected chalets') . ' has already been reserved for the selected dates (via Direct Website, MakeMyTrip, or Airbnb). Please select alternative dates or chalets.'
            ]);
            exit;
        }

        $r_rate = (float)$r_data['rate_per_night'];
        $r_base = (int)($r_data['base_guests'] ?? 2);
        $r_max = (int)($r_data['max_guests'] ?? 4);
        if (!$is_multi_room && $booking_tier === 'single_room' && !empty($r_data['single_room_rate'])) {
            $r_rate = (float)$r_data['single_room_rate'];
            $r_base = 2;
            $r_max = 4;
        }

        $matched_rooms[] = $r_data;
        $total_rate_per_night += $r_rate;
        $total_base_guests += $r_base;
        $total_max_guests += $r_max;
        $room_titles[] = $r_data['title'];
    }

    if ($adults_count > $total_max_guests) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Maximum {$total_max_guests} Adults are permitted for the selected room(s) (Max 4 Adults per room)."
        ]);
        exit;
    }

    $villa_type = implode(', ', array_map(function($r) { return $r['slug']; }, $matched_rooms));
    $villa_title = implode(' & ', $room_titles);
    if (!$is_multi_room && $booking_tier === 'single_room' && !empty($matched_rooms[0]['structure_type']) && $matched_rooms[0]['structure_type'] === 'duplex_hut') {
        $villa_title .= ' (Single Room)';
    }

    $rate_per_night = $total_rate_per_night;
    $base_guests = $total_base_guests;
    $extra_adult_rate = !empty($matched_rooms[0]['extra_guest_rate']) ? (float)$matched_rooms[0]['extra_guest_rate'] : 750.0;
    $extra_child_rate = isset($matched_rooms[0]['extra_child_rate']) ? (float)$matched_rooms[0]['extra_child_rate'] : 0.0;

    // Dual Adult & Child Occupancy Math:
    // Adults fill the combined base slots first
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

    // Auto-detect and include signature dining experiences (e.g. Candlelight Orchard Dinner) from addons into verified food items
    $has_candlelight = false;
    foreach ($verified_food_items as $vfi) {
        if (stripos($vfi['heading'] ?? '', 'candlelight') !== false) {
            $has_candlelight = true;
            break;
        }
    }
    if (!$has_candlelight && (stripos($addons, 'candlelight') !== false || (is_array($addons_input) && in_array('dinner', $addons_input)))) {
        $verified_food_items[] = [
            'id' => 999,
            'category' => 'dinner',
            'category_title' => 'Curated Dining Experience',
            'heading' => 'Candlelight Orchard Dinner',
            'subtitle' => 'Private 4-course dinner set under blooming apple trees',
            'price' => 3000.00,
            'quantity' => 1,
            'subtotal' => 3000.00,
            'served' => true,
            'inclusions' => ['Private 4-course dinner', 'Under blooming apple trees', 'Curated table setting'],
            'special_notes' => $special_notes
        ];
        $food_total += 3000.00;
        $food_status = 'selected';
    }

    if (empty($verified_food_items)) {
        $food_status = 'skipped';
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
    // Generate unique reference code
    $ref_code = 'FF-' . rand(2000, 9999);

    // -------------------------------------------------------------
    // Government ID Proof File Upload & Deep Anti-Virus Inspection
    // -------------------------------------------------------------
    $id_proof_file = null;
    
    // Check if ID file was provided
    if (empty($_FILES['id_proof_file']) || $_FILES['id_proof_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Government ID Proof document is mandatory. Please upload a clear photo or PDF (Aadhaar, Passport, Driving License, etc.) to complete your registration.'
        ]);
        exit;
    }

    $uploaded_file = $_FILES['id_proof_file'];
    $tmp_path = $uploaded_file['tmp_name'];
    $orig_name = strtolower(basename($uploaded_file['name']));
    $file_size = (int)$uploaded_file['size'];

    // 1. File size validation (64 bytes to 8MB)
    if ($file_size < 64 || $file_size > 8 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'The uploaded ID proof file must be between 64 bytes and 8MB.'
        ]);
        exit;
    }

    // 2. Extension Whitelist & Anti-Double Extension / Null-Byte Inspection
    $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($ext, $allowed_exts, true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file format. Only JPG, PNG, WEBP, and PDF ID documents are accepted.'
        ]);
        exit;
    }

    $dangerous_patterns = [
        '.exe', '.bat', '.cmd', '.sh', '.php', '.phtml', '.js', '.vbs', '.scr',
        '.pif', '.jar', '.dll', '.bin', '.apk', '.msi', '.com', '.vbe', '.wsf', '.hta',
        '.svg', '.html', '.htm', '.py', '.pl', "\0", '%00'
    ];
    foreach ($dangerous_patterns as $bad) {
        if (strpos($orig_name, $bad) !== false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Security Alert: Disallowed executable file naming pattern detected.'
            ]);
            exit;
        }
    }

    // 3. MIME Type Inspection via PHP Fileinfo
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_path);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'application/x-pdf'];
        if (!in_array($mime, $allowed_mimes, true)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Security Alert: File MIME signature does not match allowed ID formats (detected ' . htmlspecialchars($mime) . ').'
            ]);
            exit;
        }
    }

    // 4. Binary Magic Bytes & Header Threat Signatures
    $handle = @fopen($tmp_path, 'rb');
    if (!$handle) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to read uploaded file buffer for security inspection.'
        ]);
        exit;
    }

    $header_bytes = fread($handle, 32);
    $header_hex = strtoupper(bin2hex($header_bytes));

    // DOS / Windows PE Executable Signature ('MZ' = 4D 5A)
    if (substr($header_hex, 0, 4) === '4D5A') {
        fclose($handle);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Security Threat Blocked: Executable DOS/PE binary detected in place of ID document.'
        ]);
        exit;
    }

    // Linux ELF Executable Signature (\x7FELF = 7F 45 4C 46)
    if (substr($header_hex, 0, 8) === '7F454C46') {
        fclose($handle);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Security Threat Blocked: Executable ELF binary detected.'
        ]);
        exit;
    }

    // ZIP/JAR archive signature (PK\x03\x04 = 50 4B 03 04)
    if (substr($header_hex, 0, 8) === '504B0304') {
        fclose($handle);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Security Alert: Compressed archive (ZIP/JAR) detected instead of legitimate ID document.'
        ]);
        exit;
    }

    // Validate type-specific magic bytes
    $is_valid_magic = false;
    if (in_array($ext, ['jpg', 'jpeg'], true) && substr($header_hex, 0, 6) === 'FFD8FF') {
        $is_valid_magic = true;
    } elseif ($ext === 'png' && substr($header_hex, 0, 16) === '89504E470D0A1A0A') {
        $is_valid_magic = true;
    } elseif ($ext === 'webp' && substr($header_hex, 0, 8) === '52494646' && substr($header_hex, 16, 8) === '57454250') {
        $is_valid_magic = true;
    } elseif ($ext === 'pdf' && substr($header_hex, 0, 10) === '255044462D') { // %PDF-
        $is_valid_magic = true;
    }

    if (!$is_valid_magic) {
        fclose($handle);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'MIME Header Validation Failed: The file binary does not match a valid ' . strtoupper($ext) . ' structure.'
        ]);
        exit;
    }

    // 5. Deep Heuristic & Virus Signature Scanning (Scanning up to 1MB stream)
    rewind($handle);
    $scan_buffer = fread($handle, 1048576);
    fclose($handle);

    // Check EICAR standard antivirus test signature
    if (strpos($scan_buffer, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE') !== false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Virus Scanner Alert: EICAR test virus signature detected in uploaded file.'
        ]);
        exit;
    }

    // Check malicious webshell & injection signatures
    $malicious_keywords = [
        '<?php', '<?=', '<script', 'eval(', 'base64_decode(', 'shell_exec(',
        'passthru(', 'system(', 'powershell', 'cmd.exe', '/javascript', '/launch'
    ];
    $lower_buffer = strtolower($scan_buffer);
    foreach ($malicious_keywords as $kw) {
        if (strpos($lower_buffer, $kw) !== false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Security Alert: Suspicious script or executable command detected (' . htmlspecialchars($kw) . '). Upload rejected.'
            ]);
            exit;
        }
    }

    // 6. Image Structure Integrity Check
    if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        $img_info = @getimagesize($tmp_path);
        if ($img_info === false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Image Verification Failed: The uploaded image is corrupted or incomplete.'
            ]);
            exit;
        }
    }

    // 7. Windows Defender Local Engine Scan (if available on Windows host)
    $mpcmdrun = "C:\\Program Files\\Windows Defender\\MpCmdRun.exe";
    if (file_exists($mpcmdrun)) {
        $scan_cmd = escapeshellarg($mpcmdrun) . " -Scan -ScanType 3 -File " . escapeshellarg($tmp_path) . " -DisableRemediation";
        @exec($scan_cmd, $scan_output, $scan_return_code);
        if ($scan_return_code === 2) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Antivirus Engine Alert: Threat detected by server-side anti-malware scanner.'
            ]);
            exit;
        }
    }

    // 8. Safe Storage with Randomized Filename
    $upload_dir = __DIR__ . '/../uploads/id_proofs/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $safe_token = bin2hex(random_bytes(10));
    $safe_ref = preg_replace('/[^a-zA-Z0-9_-]/', '', $ref_code);
    $new_filename = 'id_' . strtolower($safe_ref) . '_' . $safe_token . '.' . $ext;
    $destination = $upload_dir . $new_filename;

    if (!move_uploaded_file($tmp_path, $destination)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to securely store the verified ID document on server.'
        ]);
        exit;
    }
    $id_proof_file = $new_filename;

    $billing_items_json = json_encode([
        'is_multi_room' => $is_multi_room,
        'rooms' => array_map(function($rm) {
            return [
                'slug' => $rm['slug'],
                'title' => $rm['title'],
                'rate_per_night' => (float)$rm['rate_per_night'],
                'base_guests' => (int)($rm['base_guests'] ?? 2),
                'max_guests' => (int)($rm['max_guests'] ?? 4),
                'structure_type' => $rm['structure_type'] ?? 'single_hut'
            ];
        }, $matched_rooms)
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            reference_code, user_id, is_guest, guest_access_token, expires_at,
            villa_type, booking_source, billing_type, gst_number, billing_name, billing_address,
            gst_percentage, gst_amount, tax_amount,
            guest_name, guest_phone, guest_email,
            id_proof_type, id_proof_number, id_proof_file, city_state, country,
            guests_count, adults_count, kids_count, extra_adults, extra_kids,
            checkin_date, checkout_date, nights, addons, billing_items_json,
            food_items, food_amount, room_amount, food_status,
            special_notes, total_amount, status
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, 'direct_website', ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
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
        $billing_items_json,
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
