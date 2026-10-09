<?php
// =========================================================================
// Food Forest Sanctuary — Guest Feedback & Reflection Submission API
// Endpoint: POST /api/submit_feedback.php
// =========================================================================

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../admin/includes/upload.php';
require_once __DIR__ . '/../includes/client_auth.php';

client_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    $pdo = get_db();
    ensure_testimonials_columns($pdo);

    $is_user = is_client_user_logged_in();
    $current_user = $is_user ? get_logged_in_client_user() : null;
    $is_guest_session = is_guest_booking_session_active();
    $guest_booking = $is_guest_session ? get_active_guest_booking() : null;

    // 1. Validate Guest Name
    $guest_name = trim($_POST['guest_name'] ?? ($_POST['name'] ?? ''));
    if (empty($guest_name) && $current_user) {
        $guest_name = $current_user['name'] ?? ($current_user['full_name'] ?? '');
    }
    if (empty($guest_name) && $guest_booking) {
        $guest_name = $guest_booking['guest_name'] ?? '';
    }
    if (empty($guest_name)) {
        echo json_encode(['success' => false, 'message' => 'Please provide your exact full name.']);
        exit;
    }

    // 2. Validate Address & Location Details
    $guest_location = trim($_POST['guest_location'] ?? ($_POST['address'] ?? ($_POST['location'] ?? '')));
    if (empty($guest_location)) {
        $guest_location = 'Sanctuary Resident Guest';
    }

    // 3. Validate Feedback Quote / Story
    $quote = trim($_POST['quote'] ?? ($_POST['feedback'] ?? ($_POST['comment'] ?? ($_POST['message'] ?? ''))));
    if (empty($quote)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your feedback / experience reflection in the text box.']);
        exit;
    }

    // Optional Fields: Title, Stay Badge & Rating
    $title = trim($_POST['title'] ?? '');
    $stay_badge = trim($_POST['stay_badge'] ?? ($_POST['villa'] ?? ''));
    if (empty($stay_badge) && $guest_booking && !empty($guest_booking['room_details']['title'])) {
        $stay_badge = $guest_booking['room_details']['title'];
    }
    if (empty($stay_badge)) {
        $stay_badge = 'SANCTUARY DWELLING';
    }

    $stars = floatval($_POST['stars'] ?? 5.0);
    $stars = max(1.0, min(5.0, round($stars * 2) / 2)); // Snap to 0.5 step between 1 and 5

    // Contact metadata
    $user_id = $current_user ? (int)$current_user['id'] : null;
    $user_email = trim($_POST['user_email'] ?? ($current_user['email'] ?? ($guest_booking['guest_email'] ?? '')));
    $user_phone = trim($_POST['user_phone'] ?? ($current_user['phone'] ?? ($guest_booking['guest_phone'] ?? '')));

    // Initials generation
    $parts = preg_split('/\s+/', $guest_name);
    $initials = '';
    foreach ($parts as $p) {
        if (!empty($p)) $initials .= mb_strtoupper(mb_substr($p, 0, 1));
        if (mb_strlen($initials) >= 2) break;
    }
    if (empty($initials)) $initials = 'FF';

    // 4. Handle Profile Picture / Avatar Upload
    $upload_base = __DIR__ . '/../uploads/testimonials';
    if (!is_dir($upload_base)) {
        @mkdir($upload_base, 0777, true);
    }

    $avatar_url = null;
    $avatar_file_key = null;
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar_file_key = 'avatar';
    } elseif (!empty($_FILES['avatar_file']['name']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $avatar_file_key = 'avatar_file';
    } elseif (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $avatar_file_key = 'profile_picture';
    }

    if ($avatar_file_key) {
        $file_info = pathinfo($_FILES[$avatar_file_key]['name']);
        $ext = strtolower($file_info['extension'] ?? '');
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            $new_name = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $upload_base . '/' . $new_name;
            if (move_uploaded_file($_FILES[$avatar_file_key]['tmp_name'], $dest)) {
                if (function_exists('compress_and_resize_image_file')) {
                    $opt = compress_and_resize_image_file($dest, ['max_dimension' => 600]);
                    if (!empty($opt['success']) && !empty($opt['filename'])) {
                        $new_name = $opt['filename'];
                    }
                }
                $avatar_url = 'uploads/testimonials/' . $new_name;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid image format for profile picture. Supported formats: JPG, PNG, WEBP.']);
            exit;
        }
    }

    // Property Slug
    $property_slug = trim($_POST['property_slug'] ?? '');
    if (empty($property_slug)) {
        $property_slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($stay_badge));
    }

    $source = trim($_POST['source'] ?? 'guest_portal');
    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM `testimonials`")->fetchColumn();

    // 5. Insert with status = 'pending' and is_active = 0
    // Moderation requirement: Admin must review & approve before it appears on public site
    $stmt = $pdo->prepare("INSERT INTO `testimonials` 
        (`guest_name`, `guest_location`, `stay_badge`, `property_slug`, `title`, `stars`, `quote`, `initials`, `avatar_url`, `media_type`, `media_url`, `display_order`, `is_active`, `status`, `user_id`, `user_email`, `user_phone`, `source`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'image', NULL, ?, 0, 'pending', ?, ?, ?, ?)");
    
    $stmt->execute([
        $guest_name,
        $guest_location,
        $stay_badge,
        $property_slug,
        $title,
        $stars,
        $quote,
        $initials,
        $avatar_url,
        $max_order + 1,
        $user_id,
        $user_email,
        $user_phone,
        $source
    ]);

    $inserted_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your feedback! Your reflection has been submitted to the sanctuary administration. Once reviewed and approved by management, it will appear on our website.',
        'id' => (int)$inserted_id,
        'status' => 'pending',
        'guest_name' => $guest_name,
        'avatar_url' => $avatar_url
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit feedback: ' . $e->getMessage()
    ]);
}
