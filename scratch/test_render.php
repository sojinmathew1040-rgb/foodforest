<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/foodforest/admin/bookings.php';
$_SERVER['SCRIPT_NAME'] = '/foodforest/admin/bookings.php';
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'Admin';
$_SESSION['admin_role'] = 'admin';

chdir(__DIR__ . '/../admin');
ob_start();
include 'bookings.php';
$html = ob_get_clean();

echo "HTML length: " . strlen($html) . "\n";
echo "modal-checkout-audit in HTML: " . (strpos($html, 'id="modal-checkout-audit"') !== false ? "YES" : "NO") . "\n";
echo "openCheckoutAuditModal in HTML: " . (strpos($html, 'function openCheckoutAuditModal') !== false ? "YES" : "NO") . "\n";
echo "closeCheckoutAuditModal in HTML: " . (strpos($html, 'function closeCheckoutAuditModal') !== false ? "YES" : "NO") . "\n";
echo "audit-booking-id in HTML: " . (strpos($html, 'id="audit-booking-id"') !== false ? "YES" : "NO") . "\n";
