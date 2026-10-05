<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user'] = ['id' => 1, 'username' => 'admin', 'full_name' => 'Master Concierge', 'role' => 'admin'];
$_SERVER['PHP_SELF'] = '/foodforest/admin/kitchen.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';

ob_start();
require __DIR__ . '/../admin/kitchen.php';
$html = ob_get_clean();

echo "Kitchen output length: " . strlen($html) . " bytes\n";
echo "Contains 'Kitchen & Chef Orders': " . (strpos($html, 'Kitchen & Chef Orders') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'Chef\'s Preparation Batch Summary': " . (strpos($html, "Chef's Preparation Batch Summary") !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'Shift Date:': " . (strpos($html, 'Shift Date:') !== false ? 'YES' : 'NO') . "\n";

echo "\n--- PREP BATCH SUMMARY ---\n";
foreach ($prep_summary as $slot_key => $slot) {
    echo strtoupper($slot_key) . " (Total portions: {$slot['count']}):\n";
    if (empty($slot['items'])) {
        echo "   (No dishes ordered for this slot)\n";
    } else {
        foreach ($slot['items'] as $item) {
            echo "   • {$item['name']} x {$item['quantity']} portions (ordered by {$item['bookings_count']} chalet(s))\n";
        }
    }
}

echo "\n--- KITCHEN BOOKINGS (" . count($kitchen_bookings) . ") ---\n";
foreach ($kitchen_bookings as $kb) {
    echo "Ref #{$kb['reference_code']} - {$kb['guest_name']} (" . format_chalet_label($kb['villa_type']) . ") Status: {$kb['food_status']}\n";
    foreach ($kb['parsed_dishes'] as $d) {
        echo "    - [{$d['resolved_slot']}] {$d['heading']} x {$d['quantity']}\n";
    }
}

