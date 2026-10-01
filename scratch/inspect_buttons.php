<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

chdir(__DIR__ . '/../admin');
ob_start();
include 'bookings.php';
$html = ob_get_clean();

// Find inhouse buttons in html
preg_match_all('/<button[^>]*class="[^"]*checkout[^"]*"[^>]*>.*?<\/button>/s', $html, $matches);
echo "Checkout buttons found: " . count($matches[0]) . "\n";
foreach ($matches[0] as $m) {
    echo $m . "\n";
}

preg_match_all('/<button[^>]*title="Audit Room[^>]*>.*?<\/button>/s', $html, $matches2);
echo "\nAudit buttons found: " . count($matches2[0]) . "\n";
foreach ($matches2[0] as $m) {
    echo $m . "\n";
}
