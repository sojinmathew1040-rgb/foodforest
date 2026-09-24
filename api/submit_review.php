<?php
// =========================================================================
// Food Forest Sanctuary — Guest Feedback & Review Submission API
// =========================================================================

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/client_auth.php';

client_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $pdo = get_db();
    ensure_testimonials_columns($pdo);

    $is_user = is_client_user_logged_in();
    $current_user = $is_user ? get_logged_in_client_user() : null;

    $guest_name = trim($_POST['guest_name'] ?? '');
    if (empty($guest_name) && $current_user) {
        $guest_name = $current_user['name'] ?? '';
    }
    if (empty($guest_name)) {
        echo json_encode(['success' => false, 'message' => 'Please provide your full name.']);
        exit;
    }

    $quote = trim($_POST['quote'] ?? ($_POST['comment'] ?? ''));
    if (empty($quote)) {
        echo json_encode(['success' => false, 'message' => 'Please share your sanctuary reflection or review.']);
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $guest_location = trim($_POST['guest_location'] ?? 'Sanctuary Traveler');
    $stay_badge = trim($_POST['stay_badge'] ?? 'CANOPY TREEHOUSE');
    if (empty($stay_badge)) $stay_badge = 'SANCTUARY RETREAT';

    $stars = floatval($_POST['stars'] ?? 5.0);
    $stars = max(0.5, min(5.0, round($stars * 2) / 2)); // Snap to 0.5 increments

    $user_id = $current_user ? (int)$current_user['id'] : null;
    $user_email = trim($_POST['user_email'] ?? ($current_user['email'] ?? ''));
    $user_phone = trim($_POST['user_phone'] ?? ($current_user['phone'] ?? ''));

    // Initials for avatar fallback
    $parts = preg_split('/\s+/', $guest_name);
    $initials = '';
    foreach ($parts as $p) {
        if (!empty($p)) $initials .= mb_strtoupper(mb_substr($p, 0, 1));
        if (mb_strlen($initials) >= 2) break;
    }
    if (empty($initials)) $initials = 'FF';

    $upload_base = __DIR__ . '/../uploads/testimonials';
    if (!is_dir($upload_base)) {
        mkdir($upload_base, 0777, true);
    }

    $avatar_url = null;
    // 1. Process Avatar Photo Upload
    if (!empty($_FILES['avatar_file']['name']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['avatar_file']['name']);
        $ext = strtolower($file_info['extension'] ?? '');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $upload_base . '/' . $new_name;
            if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $dest)) {
                $avatar_url = 'uploads/testimonials/' . $new_name;
            }
        }
    }

    // 2. Process Media Photo / Video Vlog Upload
    $media_url = trim($_POST['media_url'] ?? '');
    $media_type = 'image';

    if (!empty($_FILES['media_file']['name']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['media_file']['name']);
        $ext = strtolower($file_info['extension'] ?? '');
        $img_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $video_exts = ['mp4', 'webm', 'mov', 'm4v'];

        if (in_array($ext, $img_exts)) {
            $new_name = 'media_img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $upload_base . '/' . $new_name;
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $dest)) {
                $media_url = 'uploads/testimonials/' . $new_name;
                $media_type = 'image';
            }
        } elseif (in_array($ext, $video_exts)) {
            $new_name = 'media_vlog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $upload_base . '/' . $new_name;
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $dest)) {
                $media_url = 'uploads/testimonials/' . $new_name;
                $media_type = 'video';
            }
        }
    } elseif (!empty($media_url)) {
        if (preg_match('/(youtube\.com|youtu\.be|vimeo\.com|\.mp4|\.webm)/i', $media_url)) {
            $media_type = 'video';
        } else {
            $media_type = 'image';
        }
    }

    $property_slug = trim($_POST['property_slug'] ?? '');
    if (empty($property_slug)) {
        $property_slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($stay_badge));
    }

    $max_order = (int)$pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM `testimonials`")->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO `testimonials` 
        (`guest_name`, `guest_location`, `stay_badge`, `property_slug`, `title`, `stars`, `quote`, `initials`, `avatar_url`, `media_type`, `media_url`, `display_order`, `is_active`, `status`, `user_id`, `user_email`, `user_phone`, `source`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending', ?, ?, ?, 'website')");
    
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
        $media_type,
        $media_url,
        $max_order + 1,
        $user_id,
        $user_email,
        $user_phone
    ]);

    $inserted_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for sharing your sanctuary memory! Your reflection has been submitted to the Estate Concierge and will appear on the live site upon verification.',
        'id' => $inserted_id,
        'status' => 'pending'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save review: ' . $e->getMessage()
    ]);
}
