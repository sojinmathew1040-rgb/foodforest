<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

$details = get_booking_billing_details($pdo, 7);
echo "=== BEFORE FIX ===\n";
echo "Food items count: " . count($details['parsed']['food_items']) . "\n";
print_r($details['parsed']['food_items']);
echo "Activities count: " . count($details['parsed']['activities']) . "\n";
print_r($details['parsed']['activities']);
