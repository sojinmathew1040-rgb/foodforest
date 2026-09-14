<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_role'] = 'principal';
$_SESSION['admin_id'] = 1;

$_GET['tab'] = 'estate';
ob_start();
include 'admin/settings.php';
$html = ob_get_clean();

header('Content-Type: text/plain');
echo "HTML LENGTH: " . strlen($html) . "\n";
echo "CONTAINS pane-estate: " . (strpos($html, 'pane-estate') !== false ? 'YES' : 'NO') . "\n";
echo "CONTAINS pane-whatsapp: " . (strpos($html, 'pane-whatsapp') !== false ? 'YES' : 'NO') . "\n";
echo "CONTAINS pane-hero: " . (strpos($html, 'pane-hero') !== false ? 'YES' : 'NO') . "\n";
echo "\n--- PANE-ESTATE SNIPPET ---\n";
$pos = strpos($html, 'id="pane-estate"');
if ($pos !== false) {
    echo substr($html, $pos, 600);
}


