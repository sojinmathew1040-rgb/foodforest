<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

echo "=== ROOMS (DUPLEX) ===\n";
$stmt = $pdo->query("SELECT id, slug, title, structure_type, photos FROM rooms WHERE structure_type = 'duplex_hut'");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | Slug: {$r['slug']} | Title: {$r['title']}\n";
    echo "Photos: {$r['photos']}\n\n";
}

echo "=== SANCTUARY SPOTS (DUPLEX) ===\n";
$stmt = $pdo->query("SELECT id, spot_number, title, linked_room_slug, structure_type, photos FROM sanctuary_spots WHERE structure_type = 'duplex_hut'");
while ($s = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$s['id']} | Spot #: {$s['spot_number']} | Title: {$s['title']} | Linked: {$s['linked_room_slug']}\n";
}

echo "=== BOOKINGS WITH DUPLEX ===\n";
$stmt = $pdo->query("SELECT id, reference_code, villa_type, checkin_date, checkout_date, status, billing_items_json FROM bookings WHERE villa_type LIKE '%duplex%'");
while ($b = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$b['id']} | Ref: {$b['reference_code']} | Villa: {$b['villa_type']} | Dates: {$b['checkin_date']} to {$b['checkout_date']} | Status: {$b['status']}\n";
    echo "Billing items: {$b['billing_items_json']}\n\n";
}
