<?php
require_once __DIR__ . '/../admin/includes/db.php';
$rooms = get_all_rooms();
foreach ($rooms as $r) {
    echo $r['id'] . ' | ' . $r['slug'] . ' | ' . $r['title'] . "\n";
    echo "  image_url: " . ($r['image_url'] ?? '') . "\n";
    echo "  interior_360_url: " . ($r['interior_360_url'] ?? '') . "\n";
}
