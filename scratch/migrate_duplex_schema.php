<?php
require_once __DIR__ . '/../admin/includes/db.php';
$pdo = get_db();

// 1. Add columns to rooms table
$r_cols = $pdo->query("SHOW COLUMNS FROM `rooms`")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('photos_left', $r_cols)) {
    $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `photos_left` TEXT NULL AFTER `photos`");
    echo "Added photos_left to rooms\n";
}
if (!in_array('photos_right', $r_cols)) {
    $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `photos_right` TEXT NULL AFTER `photos_left`");
    echo "Added photos_right to rooms\n";
}

// 2. Add column to bookings table
$b_cols = $pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('duplex_unit', $b_cols)) {
    $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `duplex_unit` VARCHAR(20) DEFAULT 'full' AFTER `villa_type`");
    echo "Added duplex_unit to bookings\n";
}

// 3. Seed default photos_left and photos_right for duplex rooms if NULL
$duplexes = [
    'duplex-suite-01' => [
        'left' => ['assets/images/treehouse_interior.png', 'assets/images/01 (2).jpeg', 'assets/images/01 (10).jpeg'],
        'right' => ['assets/images/treehouse_interior_pano.jpg', 'assets/images/01 (25).jpeg', 'assets/images/01 (32).jpeg']
    ],
    'duplex-suite-02' => [
        'left' => ['assets/images/treehouse_interior.png', 'assets/images/01 (2).jpeg', 'assets/images/01 (25).jpeg'],
        'right' => ['assets/images/treehouse_interior_pano.jpg', 'assets/images/01 (32).jpeg', 'assets/images/01 (26).jpeg']
    ],
    'duplex-suite-04' => [
        'left' => ['assets/images/treehouse_interior.png', 'assets/images/01 (26).jpeg', 'assets/images/01 (10).jpeg'],
        'right' => ['assets/images/treehouse_interior_pano.jpg', 'assets/images/01 (28).jpeg', 'assets/images/01 (29).jpeg']
    ],
    'duplex-suite-05' => [
        'left' => ['assets/images/treehouse_interior.png', 'assets/images/01 (28).jpeg', 'assets/images/01 (2).jpeg'],
        'right' => ['assets/images/treehouse_interior_pano.jpg', 'assets/images/01 (29).jpeg', 'assets/images/01 (30).jpeg']
    ],
    'duplex-suite-06' => [
        'left' => ['assets/images/treehouse_interior.png', 'assets/images/01 (30).jpeg', 'assets/images/01 (20).jpeg'],
        'right' => ['assets/images/treehouse_interior_pano.jpg', 'assets/images/01 (34).jpeg', 'assets/images/01 (27).jpeg']
    ]
];

foreach ($duplexes as $slug => $data) {
    $row = $pdo->query("SELECT photos_left, photos_right FROM rooms WHERE slug = '$slug'")->fetch(PDO::FETCH_ASSOC);
    if ($row && empty($row['photos_left'])) {
        $stmt = $pdo->prepare("UPDATE rooms SET photos_left = ?, photos_right = ? WHERE slug = ?");
        $stmt->execute([json_encode($data['left']), json_encode($data['right']), $slug]);
        echo "Seeded default left/right photos for $slug\n";
    }
}

echo "Migration finished successfully.\n";
