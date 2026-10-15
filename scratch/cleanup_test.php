<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();
$pdo->exec("DELETE FROM bookings WHERE reference_code = 'FF-3839' OR guest_name LIKE 'Automated MealTime Test Guest%'");
echo "Cleaned up any test bookings.\n";
