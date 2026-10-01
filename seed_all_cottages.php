<?php
require_once __DIR__ . '/admin/includes/db.php';
$pdo = get_db();

echo "=============================================" . PHP_EOL;
echo "SEEDING ALL COTTAGES & MAP SPOTS INTO DATABASE" . PHP_EOL;
echo "=============================================" . PHP_EOL;

// Ensure 'photos' column exists in rooms table
$cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'photos'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `photos` TEXT NULL AFTER `image_url`");
}

// 1. Array of all 10 Cottages / Chalets with High-Res Photos
$rooms_data = [
    [
        'slug' => 'canopy-treehouse',
        'stay_type' => 'treehouse',
        'structure_type' => 'single_hut',
        'title' => 'Luxury Canopy Treehouse',
        'rate_per_night' => 5000.00,
        'single_room_rate' => 5000.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => '30FT ELEVATION • 1,620M MSL',
        'min_guests' => 1,
        'base_guests' => 2,
        'max_guests' => 4,
        'description' => 'Suspended 30 feet above the forest floor within ancient trees. Crafted with wild teak timber, an open cantilevered deck, and expansive curved glass with panoramic mist views.',
        'amenities' => 'King Artisan Teak Bed, Mountain-View Cantilevered Deck, Solar-Heated Rain Shower, Handcrafted Herbal Teas, Ambient Fire Hearth, Telescope for Stargazing, Zero-Plastic Toiletries',
        'image_url' => 'assets/images/treehouse_exterior_front.jpg',
        'photos' => [
            'assets/images/treehouse_exterior_front.jpg',
            'assets/images/treehouse_interior.png',
            'assets/images/treehouse_exterior.png',
            'assets/images/01 (10).jpeg',
            'assets/images/treehouse_360_pano.jpg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'duplex-suite-01',
        'stay_type' => 'treehouse',
        'structure_type' => 'duplex_hut',
        'title' => 'Duplex Chalet Suite 01',
        'rate_per_night' => 8000.00,
        'single_room_rate' => 4000.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'UPPER RIDGE • 1,600M MSL',
        'min_guests' => 2,
        'base_guests' => 4,
        'max_guests' => 8,
        'description' => 'High-elevation sanctuary duplex suite with morning mist exposure, two independent master bedrooms, private panoramic balconies, and shared lounge.',
        'amenities' => '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included',
        'image_url' => 'assets/images/duplex_suite_exterior.jpg',
        'photos' => [
            'assets/images/duplex_suite_exterior.jpg',
            'assets/images/treehouse_interior.png',
            'assets/images/01 (2).jpeg',
            'assets/images/01 (10).jpeg',
            'assets/images/treehouse_exterior.png'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'duplex-suite-02',
        'stay_type' => 'treehouse',
        'structure_type' => 'duplex_hut',
        'title' => 'Duplex Chalet Suite 02',
        'rate_per_night' => 8000.00,
        'single_room_rate' => 4000.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'UPPER RIDGE • 1,600M MSL',
        'min_guests' => 2,
        'base_guests' => 4,
        'max_guests' => 8,
        'description' => 'Two-tier alpine timber duplex overlooking rolling mountain cloudscapes with dual handcrafted king beds and private verandas.',
        'amenities' => '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included',
        'image_url' => 'assets/images/duplex_suite_exterior.jpg',
        'photos' => [
            'assets/images/duplex_suite_exterior.jpg',
            'assets/images/treehouse_interior.png',
            'assets/images/01 (2).jpeg',
            'assets/images/01 (25).jpeg',
            'assets/images/01 (32).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'duplex-suite-04',
        'stay_type' => 'treehouse',
        'structure_type' => 'duplex_hut',
        'title' => 'Duplex Chalet Suite 03 (Aerial View)',
        'rate_per_night' => 9000.00,
        'single_room_rate' => 4500.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'NORTH RIDGE • 1,600M MSL',
        'min_guests' => 2,
        'base_guests' => 4,
        'max_guests' => 8,
        'description' => 'High-elevation sanctuary suite with aerial view and morning mist exposure, double bedrooms, and sweeping northern alpine views.',
        'amenities' => '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included',
        'image_url' => 'assets/images/treehouse_exterior.png',
        'photos' => [
            'assets/images/treehouse_exterior.png',
            'assets/images/duplex_suite_exterior.jpg',
            'assets/images/treehouse_interior.png',
            'assets/images/01 (26).jpeg',
            'assets/images/01 (10).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'duplex-suite-05',
        'stay_type' => 'treehouse',
        'structure_type' => 'duplex_hut',
        'title' => 'Duplex Chalet Suite 04 (Aerial View)',
        'rate_per_night' => 9000.00,
        'single_room_rate' => 4500.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'NORTH RIDGE • 1,600M MSL',
        'min_guests' => 2,
        'base_guests' => 4,
        'max_guests' => 8,
        'description' => 'Upper ridge master duplex with unobstructed northern aerial high range views, crafted timber ceilings, and forest-facing viewing deck.',
        'amenities' => '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included',
        'image_url' => 'assets/images/duplex_suite_exterior.jpg',
        'photos' => [
            'assets/images/duplex_suite_exterior.jpg',
            'assets/images/01 (28).jpeg',
            'assets/images/treehouse_interior.png',
            'assets/images/01 (2).jpeg',
            'assets/images/01 (29).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'duplex-suite-06',
        'stay_type' => 'treehouse',
        'structure_type' => 'duplex_hut',
        'title' => 'Duplex Chalet Suite 05 (Peak Aerial View)',
        'rate_per_night' => 9000.00,
        'single_room_rate' => 4500.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'PEAK CONTOUR • 1,600M MSL',
        'min_guests' => 2,
        'base_guests' => 4,
        'max_guests' => 8,
        'description' => 'Peak-level luxury duplex chalet with panoramic aerial views on the highest mountain contour with grand high-ceiling interiors and dual master suites.',
        'amenities' => '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included',
        'image_url' => 'assets/images/treehouse_exterior.png',
        'photos' => [
            'assets/images/treehouse_exterior.png',
            'assets/images/duplex_suite_exterior.jpg',
            'assets/images/01 (30).jpeg',
            'assets/images/treehouse_interior.png',
            'assets/images/01 (20).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'cottage-hut-03',
        'stay_type' => 'woodhouse',
        'structure_type' => 'single_hut',
        'title' => 'Pine Cottage Hut 03',
        'rate_per_night' => 5000.00,
        'single_room_rate' => 5000.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'ORCHARD SLOPE • 1,600M MSL',
        'min_guests' => 1,
        'base_guests' => 2,
        'max_guests' => 4,
        'description' => 'Cozy standalone pine timber cottage nestled along the western orchard slope with private garden patio and fireplace.',
        'amenities' => 'Handcrafted Queen Bed, Pine Veranda, Slate Hearth Fireplace, Solar Rain Shower, Orchard View Patio, Organic Bedding',
        'image_url' => 'assets/images/pine_cottage_exterior.jpg',
        'photos' => [
            'assets/images/pine_cottage_exterior.jpg',
            'assets/images/01 (2).jpeg',
            'assets/images/01 (7).jpeg',
            'assets/images/01 (1).jpeg',
            'assets/images/01 (14).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'cottage-hut-02',
        'stay_type' => 'woodhouse',
        'structure_type' => 'single_hut',
        'title' => 'Pine Cottage Hut 02',
        'rate_per_night' => 5000.00,
        'single_room_rate' => 5000.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'ORCHARD SLOPE • 1,600M MSL',
        'min_guests' => 1,
        'base_guests' => 2,
        'max_guests' => 4,
        'description' => 'Private single cottage with handcrafted wooden furnishings, forest balcony, and direct access to organic apple trails.',
        'amenities' => 'Handcrafted Queen Bed, Pine Veranda, Slate Hearth Fireplace, Solar Rain Shower, Orchard View Patio, Organic Bedding',
        'image_url' => 'assets/images/pine_cottage_exterior.jpg',
        'photos' => [
            'assets/images/pine_cottage_exterior.jpg',
            'assets/images/01 (34).jpeg',
            'assets/images/01 (27).jpeg',
            'assets/images/01 (2).jpeg',
            'assets/images/01 (33).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'mudhouse-stay',
        'stay_type' => 'mudhouse',
        'structure_type' => 'single_hut',
        'title' => 'Earthen Mudhouse Suite',
        'rate_per_night' => 5000.00,
        'single_room_rate' => 5000.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'COB HERITAGE • 1,600M MSL',
        'min_guests' => 1,
        'base_guests' => 2,
        'max_guests' => 4,
        'description' => 'Naturally thermal-insulated cob clay dwelling sculpted from native red earth, river sand, and straw. Features private sit-out and orchard veranda.',
        'amenities' => 'Hand-Carved Wooden Queen Bed, Natural Clay Cooler, Slate Hearth Fireplace, Terracotta Veranda, Private Organic Herb Garden, Forest Spring Water',
        'image_url' => 'assets/images/mudhouse_exterior.png',
        'photos' => [
            'assets/images/mudhouse_exterior.png',
            'assets/images/mudhouse_interior.png',
            'assets/images/01 (7).jpeg',
            'assets/images/01 (8).jpeg',
            'assets/images/01 (9).jpeg',
            'assets/images/01 (20).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ],
    [
        'slug' => 'grand-wooden-alpine-house',
        'stay_type' => 'woodhouse',
        'structure_type' => 'single_hut',
        'title' => 'Grand Wooden Alpine House',
        'rate_per_night' => 5000.00,
        'single_room_rate' => 5000.00,
        'extra_guest_rate' => 750.00,
        'extra_child_rate' => 0.00,
        'elevation' => 'EAST MEADOW • 1,600M MSL',
        'min_guests' => 1,
        'base_guests' => 2,
        'max_guests' => 4,
        'description' => 'Classic pinewood mountain house with expansive living hall, artisan craftsmanship, vaulted timber ceiling, and wide sun deck.',
        'amenities' => 'King Artisan Bed, Mountain View Sun Deck, Solar Rain Shower, Woodfire Stove, Tea Bar, Stargazing Binoculars',
        'image_url' => 'assets/images/grand_alpine_house.jpg',
        'photos' => [
            'assets/images/grand_alpine_house.jpg',
            'assets/images/treehouse_interior.png',
            'assets/images/01 (2).jpeg',
            'assets/images/01 (26).jpeg',
            'assets/images/01 (28).jpeg'
        ],
        'interior_360_url' => 'assets/images/treehouse_360_pano.jpg',
        'is_available' => 1
    ]
];

// Insert or update rooms
$upsert_room = $pdo->prepare("INSERT INTO rooms (
    slug, stay_type, structure_type, title, rate_per_night, single_room_rate, extra_guest_rate, extra_child_rate,
    elevation, min_guests, base_guests, max_guests, description, amenities, image_url, photos, interior_360_url, is_available
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    stay_type = VALUES(stay_type),
    structure_type = VALUES(structure_type),
    title = VALUES(title),
    rate_per_night = VALUES(rate_per_night),
    single_room_rate = VALUES(single_room_rate),
    extra_guest_rate = VALUES(extra_guest_rate),
    extra_child_rate = VALUES(extra_child_rate),
    elevation = VALUES(elevation),
    min_guests = VALUES(min_guests),
    base_guests = VALUES(base_guests),
    max_guests = VALUES(max_guests),
    description = VALUES(description),
    amenities = VALUES(amenities),
    image_url = VALUES(image_url),
    photos = VALUES(photos),
    interior_360_url = VALUES(interior_360_url),
    is_available = VALUES(is_available)");

foreach ($rooms_data as $rm) {
    $upsert_room->execute([
        $rm['slug'],
        $rm['stay_type'],
        $rm['structure_type'],
        $rm['title'],
        $rm['rate_per_night'],
        $rm['single_room_rate'],
        $rm['extra_guest_rate'],
        $rm['extra_child_rate'],
        $rm['elevation'],
        $rm['min_guests'],
        $rm['base_guests'],
        $rm['max_guests'],
        $rm['description'],
        $rm['amenities'],
        $rm['image_url'],
        json_encode($rm['photos']),
        $rm['interior_360_url'],
        $rm['is_available']
    ]);
    echo "✓ Room [{$rm['slug']}]: {$rm['title']} upserted successfully." . PHP_EOL;
}

// 2. Ensure Spot 01 (Canopy Treehouse) exists in sanctuary_spots
$chk_spot1 = $pdo->query("SELECT id FROM sanctuary_spots WHERE spot_number = 1")->fetchColumn();
if (!$chk_spot1) {
    $ins_spot1 = $pdo->prepare("INSERT INTO sanctuary_spots (
        spot_number, title, subtitle_tag, category, icon_class, pin_color, is_stay, linked_room_slug, structure_type,
        stay_price, elevation, temperature, description, image_url, photos, cta_text, cta_link, x_coord, y_coord, display_order, is_active
    ) VALUES (
        1, 'Luxury Canopy Treehouse', 'HIGH CANOPY RETREAT', 'stays', 'fa-solid fa-tree', '#10B981', 1, 'canopy-treehouse', 'single_hut',
        5000.00, '30FT ELEVATION • 1,620M MSL', '17°C Alpine Breeze',
        'Elevated living suspended 30 feet above the forest floor among towering mountain trees with panoramic mist views.',
        'assets/images/treehouse_exterior_front.jpg', '[\"assets/images/treehouse_exterior_front.jpg\"]',
        'Explore Details', '#rooms', 21.50, 17.80, 1, 1
    )");
    $ins_spot1->execute();
    echo "✓ Spot 1 (Luxury Canopy Treehouse) created successfully." . PHP_EOL;
}

// 3. Link each stay spot in sanctuary_spots to its matching room slug and sync photos
$spot_links = [
    1 => 'canopy-treehouse',
    2 => 'duplex-suite-01',
    3 => 'duplex-suite-02',
    4 => 'duplex-suite-04',
    5 => 'duplex-suite-05',
    6 => 'duplex-suite-06',
    7 => 'cottage-hut-03',
    8 => 'cottage-hut-02',
    9 => 'mudhouse-stay',
    10 => 'grand-wooden-alpine-house'
];

$upd_spot = $pdo->prepare("UPDATE sanctuary_spots SET linked_room_slug = ?, image_url = ?, photos = ? WHERE spot_number = ?");
$rooms_by_slug = [];
foreach ($rooms_data as $rd) {
    $rooms_by_slug[$rd['slug']] = $rd;
}

foreach ($spot_links as $s_num => $r_slug) {
    $img = $rooms_by_slug[$r_slug]['image_url'] ?? '';
    $photos_json = json_encode($rooms_by_slug[$r_slug]['photos'] ?? [$img]);
    $upd_spot->execute([$r_slug, $img, $photos_json, $s_num]);
    echo "✓ Spot #{$s_num} linked to room [{$r_slug}] with photos." . PHP_EOL;
}

// 4. Also verify non-stay spots have linked_room_slug = null or empty
$pdo->exec("UPDATE sanctuary_spots SET linked_room_slug = NULL WHERE is_stay = 0");

echo PHP_EOL . "ALL DONE! Verification:" . PHP_EOL;
$final_rooms = $pdo->query("SELECT id, slug, title, structure_type, image_url, rate_per_night FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Total Rooms in database: " . count($final_rooms) . PHP_EOL;
foreach ($final_rooms as $fr) {
    echo "  - [ID {$fr['id']}] {$fr['slug']} | {$fr['title']} | Image: {$fr['image_url']}" . PHP_EOL;
}
