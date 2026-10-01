<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/foodforest/admin/bookings.php';
$_SERVER['SCRIPT_NAME'] = '/foodforest/admin/bookings.php';
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

chdir(__DIR__ . '/../admin');
ob_start();
include 'bookings.php';
$html = ob_get_clean();

// Find all script tags
preg_match_all('/<script(?:\s+[^>]*)?>(.*?)<\/script>/is', $html, $matches);
echo "Total inline script blocks: " . count($matches[1]) . "\n";

foreach ($matches[1] as $idx => $script) {
    $script = trim($script);
    if (empty($script)) continue;
    $filename = __DIR__ . "/script_block_{$idx}.js";
    file_put_contents($filename, $script);
    echo "Block $idx length: " . strlen($script) . " bytes -> saved to $filename\n";
}
