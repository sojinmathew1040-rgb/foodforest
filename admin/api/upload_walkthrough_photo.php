<?php
// =========================================================================
// Food Forest Sanctuary — 360 Walkthrough Photo & Panorama Upload API
// Handles interactive cropped 360 equirectangular panoramas & 16:9 covers
// =========================================================================
require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/upload.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. POST required.']);
    exit;
}

$pdo = get_db();
$stay_key = trim($_POST['stay_key'] ?? ''); // 'woodhouse' or 'mudhouse'
$type_key = trim($_POST['type_key'] ?? ''); // 'pano' or 'cover'
$room_id  = (int)($_POST['room_id'] ?? 0);

if (!in_array($stay_key, ['woodhouse', 'mudhouse'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid stay type specified. Must be woodhouse or mudhouse.']);
    exit;
}
if (!in_array($type_key, ['pano', 'cover'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid photo type specified. Must be pano or cover.']);
    exit;
}

$uploads_dir = realpath(__DIR__ . '/../../assets/uploads');
if (!$uploads_dir || !is_dir($uploads_dir)) {
    $uploads_dir = __DIR__ . '/../../assets/uploads';
    if (!file_exists($uploads_dir)) {
        @mkdir($uploads_dir, 0755, true);
    }
}

$prefix = ($type_key === 'pano') ? 'pano_' : 'room_cover_';
$filename = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
$target_path = $uploads_dir . '/' . $filename;
$relative_url = 'assets/uploads/' . $filename;

$saved = false;

// Case 1: Base64 dataURL from interactive Cropper.js canvas
if (!empty($_POST['image_data'])) {
    $raw_data = $_POST['image_data'];
    if (preg_match('/^data:image\/(\w+);base64,/', $raw_data, $type)) {
        $raw_data = substr($raw_data, strpos($raw_data, ',') + 1);
        $raw_data = base64_decode($raw_data);
        if ($raw_data !== false) {
            if (@file_put_contents($target_path, $raw_data)) {
                $saved = true;
            }
        }
    }
}
// Case 2: Binary file upload
elseif (!empty($_FILES['image_file']['tmp_name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
    $f = $_FILES['image_file'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);

    if (in_array($mime, $allowed_mimes)) {
        if (@move_uploaded_file($f['tmp_name'], $target_path)) {
            $saved = true;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unsupported image format. Please upload JPG, PNG or WebP.']);
        exit;
    }
}

if (!$saved) {
    echo json_encode(['success' => false, 'message' => 'Failed to save cropped image to server directory.']);
    exit;
}

// Auto-compress & optimize photo if enabled
$auto_compress = (!isset($_POST['auto_compress']) || $_POST['auto_compress'] === '1' || $_POST['auto_compress'] === 'true');
$opt_info = null;
if ($auto_compress && function_exists('optimize_uploaded_image')) {
    $opts = [
        'auto_compress' => true,
        'max_dimension' => ($type_key === 'pano' ? 4096 : 2560),
        'quality'       => ($type_key === 'pano' ? 88 : 84),
        'convert_webp'  => false
    ];
    $opt_info = @optimize_uploaded_image($target_path, $opts);
}

// Update settings table
$setting_key = "walkthrough_{$stay_key}_{$type_key}";
set_setting($setting_key, $relative_url, $pdo);

// Also update linked room in rooms table if room_id is valid
if ($room_id > 0) {
    if ($type_key === 'pano') {
        $stmt = $pdo->prepare("UPDATE rooms SET interior_360_url = ? WHERE id = ?");
        $stmt->execute([$relative_url, $room_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE rooms SET image_url = ? WHERE id = ?");
        $stmt->execute([$relative_url, $room_id]);
    }
} else {
    // Attempt auto-matching room ID if not supplied
    $stay_filter = ($stay_key === 'mudhouse') ? 'mudhouse' : 'woodhouse';
    $chk = $pdo->prepare("SELECT id FROM rooms WHERE stay_type = ? OR slug LIKE ? ORDER BY id ASC LIMIT 1");
    $chk->execute([$stay_filter, "%{$stay_filter}%"]);
    $found_id = $chk->fetchColumn();
    if ($found_id) {
        if ($type_key === 'pano') {
            $stmt = $pdo->prepare("UPDATE rooms SET interior_360_url = ? WHERE id = ?");
            $stmt->execute([$relative_url, $found_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE rooms SET image_url = ? WHERE id = ?");
            $stmt->execute([$relative_url, $found_id]);
        }
    }
}

echo json_encode([
    'success' => true,
    'url' => $relative_url,
    'filename' => $filename,
    'stay_key' => $stay_key,
    'type_key' => $type_key,
    'message' => ($type_key === 'pano') ? '360° Interior Panorama updated successfully!' : 'Walkthrough 16:9 Exterior Cover updated successfully!'
]);
exit;
