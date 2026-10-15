<?php
// =========================================================================
// Food Forest Sanctuary Admin — Download Guest ID Proof Document
// Sets custom filename: <Guest_Name>_<ID_Proof_Number>.<ext>
// =========================================================================

require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';

$pdo = get_db();
$booking_id = (int)($_GET['id'] ?? 0);
$ref_code = trim($_GET['ref'] ?? '');

$booking = null;
if ($booking_id > 0) {
    $stmt = $pdo->prepare("SELECT id, reference_code, guest_name, id_proof_type, id_proof_number, id_proof_file FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (!empty($ref_code)) {
    $stmt = $pdo->prepare("SELECT id, reference_code, guest_name, id_proof_type, id_proof_number, id_proof_file FROM bookings WHERE reference_code = ?");
    $stmt->execute([$ref_code]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$booking || empty($booking['id_proof_file'])) {
    http_response_code(404);
    die("ID proof document not found for this reservation.");
}

$filename = basename($booking['id_proof_file']);
$filepath = dirname(__DIR__) . '/uploads/id_proofs/' . $filename;

if (!file_exists($filepath) || !is_readable($filepath)) {
    http_response_code(404);
    die("The requested ID document file does not exist on the server storage.");
}

// Build custom clean filename: <Guest_Name>_<ID_Number>.<ext>
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (empty($ext)) {
    $ext = 'jpg';
}

$safe_guest_name = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $booking['guest_name'] ?? 'Guest');
$safe_guest_name = trim(preg_replace('/\s+/', '_', $safe_guest_name), '_');
if (empty($safe_guest_name)) {
    $safe_guest_name = 'Guest';
}

$safe_id_num = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $booking['id_proof_number'] ?? '');
$safe_id_num = trim($safe_id_num, '_');

if (!empty($safe_id_num)) {
    $download_name = "{$safe_guest_name}_{$safe_id_num}.{$ext}";
} else {
    $safe_type = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $booking['id_proof_type'] ?? 'ID');
    $safe_type = trim($safe_type, '_');
    $download_name = "{$safe_guest_name}_{$safe_type}_Proof.{$ext}";
}

// Determine MIME type
$mime_types = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp'
];
$content_type = $mime_types[$ext] ?? 'application/octet-stream';

// Clear output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Send download headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $download_name . '"; filename*=UTF-8\'\'' . rawurlencode($download_name));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
