<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['action' => 'get_audit_data', 'booking_id' => 6];
ob_start();
include __DIR__ . '/../admin/api_checkout_audit.php';
$jsonStr = ob_get_clean();

$data = json_decode($jsonStr, true);
$b = $data['booking'];
$foodList = $data['food_items'];
$actList = $data['activities'];
$invList = $data['room_inventory'];
$availableMenuItems = $data['available_menu_items'];
$availableExperiences = $data['available_experiences'];
$availableRoomsList = $data['available_rooms'];

echo "Booking 6 loaded:\n";
echo "Guest: " . $b['guest_name'] . "\n";
echo "Food items count: " . count($foodList) . "\n";
echo "Activities count: " . count($actList) . "\n";
echo "Room inventory count: " . count($invList) . "\n";
echo "Available menu items: " . count($availableMenuItems) . "\n";
echo "Available experiences: " . count($availableExperiences) . "\n";
echo "Available rooms: " . count($availableRoomsList) . "\n";
