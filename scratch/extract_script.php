<?php
$content = file_get_contents(__DIR__ . '/../admin/includes/checkout_audit_modal.php');
preg_match('/<script>(.*?)<\/script>/s', $content, $m);
if (isset($m[1])) {
    file_put_contents(__DIR__ . '/test_modal_script.js', $m[1]);
    echo 'Saved scratch/test_modal_script.js (length: ' . strlen($m[1]) . ")\n";
} else {
    echo "No script tag found!\n";
}
