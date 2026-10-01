<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

chdir(__DIR__ . '/../admin');
require_once 'includes/db.php';
$pdo = get_db();

// Find an in-house booking
$stmt = $pdo->query("SELECT id, reference_code, status FROM bookings WHERE status = 'inhouse' LIMIT 1");
$b = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$b) {
    $stmt = $pdo->query("SELECT id, reference_code, status FROM bookings ORDER BY id DESC LIMIT 1");
    $b = $stmt->fetch(PDO::FETCH_ASSOC);
}

echo "Testing with booking id: " . $b['id'] . " (status: " . $b['status'] . ")\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['action' => 'get_audit_data', 'booking_id' => $b['id']];
ob_start();
include 'api_checkout_audit.php';
$raw = ob_get_clean();

echo "Raw output length: " . strlen($raw) . "\n";
echo "First 100 chars: " . substr($raw, 0, 100) . "\n";
$decoded = json_decode($raw, true);
if ($decoded === null) {
    echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
    echo "FULL RAW OUTPUT:\n" . $raw . "\n";
} else {
    echo "SUCCESS: " . ($decoded['success'] ? 'true' : 'false') . "\n";
    if (!empty($decoded['error'])) {
        echo "ERROR MESSAGE: " . $decoded['error'] . "\n";
    }
}
