<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

echo "=== SEARCH BOOKINGS ===\n";
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE guest_name LIKE ? OR guest_name LIKE ?");
$stmt->execute(['%sunny%', '%jacob%']);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($bookings) . " bookings:\n";
foreach ($bookings as $b) {
    echo "ID: " . $b['id'] . " | Guest: " . $b['guest_name'] . " | Room: " . ($b['room_title'] ?? $b['room_type'] ?? '') . " | Status: " . $b['status'] . "\n";
    echo "Fields:\n";
    foreach ($b as $k => $v) {
        if (!empty($v) && (stripos($k, 'food') !== false || stripos($k, 'meal') !== false || stripos($k, 'dish') !== false || stripos($k, 'menu') !== false || stripos($k, 'dining') !== false || stripos($k, 'notes') !== false || stripos($k, 'detail') !== false || stripos($k, 'item') !== false || stripos($k, 'addon') !== false || stripos($k, 'json') !== false || stripos($k, 'extra') !== false)) {
            echo "  $k: $v\n";
        }
    }
    echo "Full dump for booking {$b['id']}:\n";
    print_r($b);
}

echo "\n=== ALL FOOD MENU ITEMS ===\n";
$fstmt = $pdo->query("SELECT id, category, heading, price, is_active FROM food_menu ORDER BY category, id");
print_r($fstmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== ALL RECENT BOOKINGS ===\n";
$bstmt = $pdo->query("SELECT id, reference_code, guest_name, nights, addons, food_items, food_status, special_notes FROM bookings ORDER BY id DESC LIMIT 5");
print_r($bstmt->fetchAll(PDO::FETCH_ASSOC));

