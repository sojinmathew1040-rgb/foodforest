<?php
$content = file_get_contents(__DIR__ . '/../admin/includes/checkout_audit_modal.php');

// Extract all document.getElementById('...') from content
preg_match_all("/document\.getElementById\(['\"]([^'\"]+)['\"]\)/", $content, $matches);
$ids_used = array_unique($matches[1]);

echo "Total unique getElementById calls: " . count($ids_used) . "\n";

$missing = [];
foreach ($ids_used as $id) {
    if (strpos($content, 'id="' . $id . '"') === false && strpos($content, "id='" . $id . "'") === false) {
        $missing[] = $id;
    }
}

if (!empty($missing)) {
    echo "CRITICAL BUG: Missing element IDs in HTML:\n";
    print_r($missing);
} else {
    echo "All element IDs exist in checkout_audit_modal.php!\n";
}
