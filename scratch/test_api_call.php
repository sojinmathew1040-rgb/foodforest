<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

chdir(__DIR__ . '/../admin');
require_once 'includes/db.php';
$pdo = get_db();

$stmt = $pdo->query("SELECT id, reference_code, guest_name, status, villa_type FROM bookings ORDER BY id DESC LIMIT 5");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Latest bookings:\n";
print_r($bookings);

foreach ($bookings as $b) {
    echo "\nTesting api_checkout_audit.php for Booking ID: " . $b['id'] . " (" . $b['status'] . ")\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['action' => 'get_audit_data', 'booking_id' => $b['id']];
    ob_start();
    include 'api_checkout_audit.php';
    $res = ob_get_clean();
    echo "Result: " . substr($res, 0, 300) . "...\n";
}
