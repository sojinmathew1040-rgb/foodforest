<?php
require_once __DIR__ . '/../admin/includes/db.php';

$pdo = get_db();
ensure_rooms_pricing_columns($pdo);

echo "=== TESTING DUPLEX SUITE RESERVATION & AVAILABILITY LOGIC ===\n\n";

// 1. Verify rooms schema and duplex photo lists
$rooms = get_all_rooms(false);
$duplexFound = false;
foreach ($rooms as $r) {
    if (($r['structure_type'] ?? '') === 'duplex_hut') {
        $duplexFound = true;
        echo "Found Duplex: {$r['slug']} ({$r['title']})\n";
        echo "  - Left Photos Count: " . count($r['photos_left_list'] ?? []) . "\n";
        echo "  - Right Photos Count: " . count($r['photos_right_list'] ?? []) . "\n";
        echo "  - General Photos Count: " . count($r['photos_list'] ?? []) . "\n";
        break;
    }
}

if (!$duplexFound) {
    echo "ERROR: No duplex hut found in rooms table!\n";
    exit(1);
}

// 2. Test Availability Scenarios
$testSlug = 'duplex-suite-01';
$cin = '2026-12-01';
$cout = '2026-12-03';

// Clean any previous test bookings
$delStmt = $pdo->prepare("DELETE FROM bookings WHERE guest_phone = '9999999999'");
$delStmt->execute();

echo "\n--- Scenario 1: Clean slate (No bookings) ---\n";
$availLeft = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'left');
$availRight = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'right');
$availFull = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'full');
echo "Left Available: " . ($availLeft ? 'YES' : 'NO') . "\n";
echo "Right Available: " . ($availRight ? 'YES' : 'NO') . "\n";
echo "Full Available: " . ($availFull ? 'YES' : 'NO') . "\n";

assert($availLeft === true, "Left should be available");
assert($availRight === true, "Right should be available");
assert($availFull === true, "Full should be available");

echo "\n--- Scenario 2: Booking Left Suite only ---\n";
$insStmt = $pdo->prepare("
    INSERT INTO bookings (reference_code, villa_type, guest_name, guest_phone, guest_email, checkin_date, checkout_date, guests_count, total_amount, status, duplex_unit)
    VALUES ('TEST-REF-001', ?, 'Test Guest Left', '9999999999', 'testleft@foodforest.com', ?, ?, 2, 8000, 'confirmed', 'left')
");
$insStmt->execute([$testSlug, $cin, $cout]);
$bookingId1 = $pdo->lastInsertId();
echo "Inserted Left Suite Booking ID: $bookingId1\n";

$availLeft2 = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'left');
$availRight2 = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'right');
$availFull2 = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'full');
echo "Left Available: " . ($availLeft2 ? 'YES' : 'NO') . " (Expected: NO)\n";
echo "Right Available: " . ($availRight2 ? 'YES' : 'NO') . " (Expected: YES)\n";
echo "Full Available: " . ($availFull2 ? 'YES' : 'NO') . " (Expected: NO)\n";

assert($availLeft2 === false, "Left should be booked");
assert($availRight2 === true, "Right should still be available!");
assert($availFull2 === false, "Full cannot be booked when left is booked!");

// Test get_all_rooms_availability_for_dates
$allAvail = get_all_rooms_availability_for_dates($pdo, $cin, $cout);
$duplexStat = $allAvail['rooms_status'][$testSlug] ?? null;
echo "\nDuplex Status from get_all_rooms_availability_for_dates:\n";
echo "  - available: " . ($duplexStat['available'] ? 'true' : 'false') . "\n";
echo "  - status: " . $duplexStat['status'] . "\n";
echo "  - left_available: " . ($duplexStat['left_available'] ? 'true' : 'false') . "\n";
echo "  - right_available: " . ($duplexStat['right_available'] ? 'true' : 'false') . "\n";
echo "  - full_available: " . ($duplexStat['full_available'] ? 'true' : 'false') . "\n";
echo "  - partially_booked_note: " . ($duplexStat['partially_booked_note'] ?? 'none') . "\n";

assert($duplexStat['available'] === true, "Duplex should still be available=true because right suite is available");
assert($duplexStat['status'] === 'partially_booked', "Duplex should have status=partially_booked");
assert($duplexStat['left_available'] === false, "left_available should be false");
assert($duplexStat['right_available'] === true, "right_available should be true");
assert($duplexStat['full_available'] === false, "full_available should be false");

echo "\n--- Scenario 3: Booking Right Suite as well (Both Booked) ---\n";
$insStmt2 = $pdo->prepare("
    INSERT INTO bookings (reference_code, villa_type, guest_name, guest_phone, guest_email, checkin_date, checkout_date, guests_count, total_amount, status, duplex_unit)
    VALUES ('TEST-REF-002', ?, 'Test Guest Right', '9999999999', 'testright@foodforest.com', ?, ?, 2, 8000, 'confirmed', 'right')
");
$insStmt2->execute([$testSlug, $cin, $cout]);
$bookingId2 = $pdo->lastInsertId();
echo "Inserted Right Suite Booking ID: $bookingId2\n";

$availLeft3 = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'left');
$availRight3 = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'right');
$availFull3 = check_room_availability($pdo, $testSlug, $cin, $cout, null, 'full');
echo "Left Available: " . ($availLeft3 ? 'YES' : 'NO') . " (Expected: NO)\n";
echo "Right Available: " . ($availRight3 ? 'YES' : 'NO') . " (Expected: NO)\n";
echo "Full Available: " . ($availFull3 ? 'YES' : 'NO') . " (Expected: NO)\n";

$allAvail2 = get_all_rooms_availability_for_dates($pdo, $cin, $cout);
$duplexStat2 = $allAvail2['rooms_status'][$testSlug] ?? null;
echo "\nDuplex Status when both booked:\n";
echo "  - available: " . ($duplexStat2['available'] ? 'true' : 'false') . " (Expected: false)\n";
echo "  - status: " . $duplexStat2['status'] . " (Expected: booked)\n";

assert($duplexStat2['available'] === false, "Duplex should be available=false when both wings booked");
assert($duplexStat2['status'] === 'booked', "Duplex should be status=booked when both wings booked");

// Clean up
$delStmt->execute();
echo "\n--- Test data cleaned up successfully ---\n";
echo "ALL TESTS PASSED WITH 100% SUCCESS!\n";
