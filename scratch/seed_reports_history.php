<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

// Check if historical bookings for July/August already exist
$chk = $pdo->query("SELECT COUNT(*) FROM bookings WHERE checkin_date < '2026-09-01'")->fetchColumn();
if ($chk == 0) {
    echo "Adding realistic historical bookings for July and August 2026...\n";
    $historical = [
        [
            'ref' => 'FF-HIST-01', 'guest' => 'Anand Kumar', 'phone' => '9847123456', 'email' => 'anand@example.com',
            'villa' => 'grand-wooden-alpine-house', 'checkin' => '2026-07-12', 'checkout' => '2026-07-14', 'nights' => 2,
            'room' => 13000.00, 'food' => 2400.00, 'tax' => 1680.00, 'total' => 17080.00, 'advance' => 17080.00,
            'status' => 'completed', 'payment' => 'paid',
            'food_items' => json_encode([
                ["id" => 27, "category" => "lunch", "heading" => "Chicken Biriyani", "price" => 220, "quantity" => 4, "subtotal" => 880, "dietary_type" => "non-veg"],
                ["id" => 10, "category" => "breakfast", "heading" => "Thattu Dosa (Set – 3 Nos)", "price" => 75, "quantity" => 6, "subtotal" => 450, "dietary_type" => "veg"],
                ["id" => 11, "category" => "breakfast", "heading" => "Masala Dosa", "price" => 100, "quantity" => 4, "subtotal" => 400, "dietary_type" => "veg"],
                ["id" => 12, "category" => "breakfast", "heading" => "Egg Dosa", "price" => 75, "quantity" => 4, "subtotal" => 300, "dietary_type" => "non-veg"],
                ["id" => 30, "category" => "dinner", "heading" => "Kerala Meals & Fish Curry", "price" => 185, "quantity" => 2, "subtotal" => 370, "dietary_type" => "non-veg"]
            ]),
            'created' => '2026-07-05 10:15:00'
        ],
        [
            'ref' => 'FF-HIST-02', 'guest' => 'Priya Nair', 'phone' => '9447654321', 'email' => 'priya@example.com',
            'villa' => 'mudhouse-stay', 'checkin' => '2026-07-24', 'checkout' => '2026-07-26', 'nights' => 2,
            'room' => 10000.00, 'food' => 1850.00, 'tax' => 1290.00, 'total' => 13140.00, 'advance' => 13140.00,
            'status' => 'completed', 'payment' => 'paid',
            'food_items' => json_encode([
                ["id" => 10, "category" => "breakfast", "heading" => "Thattu Dosa (Set – 3 Nos)", "price" => 75, "quantity" => 4, "subtotal" => 300, "dietary_type" => "veg"],
                ["id" => 27, "category" => "lunch", "heading" => "Chicken Biriyani", "price" => 220, "quantity" => 2, "subtotal" => 440, "dietary_type" => "non-veg"],
                ["id" => 15, "category" => "snacks", "heading" => "Banana Fritters (Pazham Pori) & Tea", "price" => 60, "quantity" => 4, "subtotal" => 240, "dietary_type" => "veg"],
                ["id" => 30, "category" => "dinner", "heading" => "Kerala Meals & Fish Curry", "price" => 185, "quantity" => 4, "subtotal" => 740, "dietary_type" => "non-veg"],
                ["id" => 12, "category" => "breakfast", "heading" => "Egg Dosa", "price" => 75, "quantity" => 1, "subtotal" => 75, "dietary_type" => "non-veg"],
                ["id" => 35, "category" => "beverages", "heading" => "Fresh Farm Passionfruit Juice", "price" => 55, "quantity" => 1, "subtotal" => 55, "dietary_type" => "veg"]
            ]),
            'created' => '2026-07-18 14:20:00'
        ],
        [
            'ref' => 'FF-HIST-03', 'guest' => 'Siddharth Rao', 'phone' => '9880123456', 'email' => 'sid@example.com',
            'villa' => 'duplex-suite-01', 'checkin' => '2026-08-08', 'checkout' => '2026-08-10', 'nights' => 2,
            'room' => 17500.00, 'food' => 3100.00, 'tax' => 2250.00, 'total' => 22850.00, 'advance' => 22850.00,
            'status' => 'completed', 'payment' => 'paid',
            'food_items' => json_encode([
                ["id" => 27, "category" => "lunch", "heading" => "Chicken Biriyani", "price" => 220, "quantity" => 5, "subtotal" => 1100, "dietary_type" => "non-veg"],
                ["id" => 11, "category" => "breakfast", "heading" => "Masala Dosa", "price" => 100, "quantity" => 4, "subtotal" => 400, "dietary_type" => "veg"],
                ["id" => 10, "category" => "breakfast", "heading" => "Thattu Dosa (Set – 3 Nos)", "price" => 75, "quantity" => 4, "subtotal" => 300, "dietary_type" => "veg"],
                ["id" => 30, "category" => "dinner", "heading" => "Kerala Meals & Fish Curry", "price" => 185, "quantity" => 4, "subtotal" => 740, "dietary_type" => "non-veg"],
                ["id" => 15, "category" => "snacks", "heading" => "Banana Fritters (Pazham Pori) & Tea", "price" => 60, "quantity" => 6, "subtotal" => 360, "dietary_type" => "veg"],
                ["id" => 35, "category" => "beverages", "heading" => "Fresh Farm Passionfruit Juice", "price" => 55, "quantity" => 4, "subtotal" => 200, "dietary_type" => "veg"]
            ]),
            'created' => '2026-08-01 09:30:00'
        ],
        [
            'ref' => 'FF-HIST-04', 'guest' => 'Dr. Meera Varma', 'phone' => '9744123456', 'email' => 'meera@example.com',
            'villa' => 'luxury-canopy-treehouse', 'checkin' => '2026-08-20', 'checkout' => '2026-08-22', 'nights' => 2,
            'room' => 10000.00, 'food' => 2150.00, 'tax' => 1300.00, 'total' => 13450.00, 'advance' => 13450.00,
            'status' => 'completed', 'payment' => 'paid',
            'food_items' => json_encode([
                ["id" => 27, "category" => "lunch", "heading" => "Chicken Biriyani", "price" => 220, "quantity" => 3, "subtotal" => 660, "dietary_type" => "non-veg"],
                ["id" => 10, "category" => "breakfast", "heading" => "Thattu Dosa (Set – 3 Nos)", "price" => 75, "quantity" => 4, "subtotal" => 300, "dietary_type" => "veg"],
                ["id" => 12, "category" => "breakfast", "heading" => "Egg Dosa", "price" => 75, "quantity" => 4, "subtotal" => 300, "dietary_type" => "non-veg"],
                ["id" => 30, "category" => "dinner", "heading" => "Kerala Meals & Fish Curry", "price" => 185, "quantity" => 3, "subtotal" => 555, "dietary_type" => "non-veg"],
                ["id" => 35, "category" => "beverages", "heading" => "Fresh Farm Passionfruit Juice", "price" => 55, "quantity" => 6, "subtotal" => 335, "dietary_type" => "veg"]
            ]),
            'created' => '2026-08-14 11:45:00'
        ]
    ];

    $ins = $pdo->prepare("INSERT INTO bookings (
        reference_code, guest_name, guest_phone, guest_email, villa_type,
        checkin_date, checkout_date, nights, adults_count, kids_count, guests_count,
        room_amount, food_amount, tax_amount, total_amount, advance_paid,
        status, payment_status, payment_method, food_items, created_at
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, 2, 0, 2,
        ?, ?, ?, ?, ?,
        ?, ?, 'upi', ?, ?
    )");

    foreach ($historical as $h) {
        $ins->execute([
            $h['ref'], $h['guest'], $h['phone'], $h['email'], $h['villa'],
            $h['checkin'], $h['checkout'], $h['nights'],
            $h['room'], $h['food'], $h['tax'], $h['total'], $h['advance'],
            $h['status'], $h['payment'], $h['food_items'], $h['created']
        ]);
        echo "Inserted {$h['ref']} ({$h['checkin']})\n";
    }
} else {
    echo "Historical records already exist.\n";
}
