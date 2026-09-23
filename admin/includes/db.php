<?php
// =========================================================================
// Food Forest Sanctuary — Database Connection & Dynamic CMS Layer
// Engine: MySQL / MariaDB via PHP PDO
// =========================================================================

// Database Connection Configuration (XAMPP Defaults)
defined('DB_HOST') or define('DB_HOST', '127.0.0.1');
defined('DB_PORT') or define('DB_PORT', '3306');
defined('DB_NAME') or define('DB_NAME', 'foodforest_db');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

/**
 * Get or initialize PDO MySQL Database connection
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Connect to MySQL server
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        // Auto-create database if not existing
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `" . DB_NAME . "`;");

        // Verify and initialize schema if needed
        init_mysql_database_schema($pdo);
        ensure_seasons_table_exists($pdo);
        ensure_rooms_360_column($pdo);
        ensure_rooms_pricing_columns($pdo);
        ensure_sanctuary_spots_table_exists($pdo);
        ensure_experiences_details_columns($pdo);
        ensure_food_menu_table_exists($pdo);
        ensure_users_and_guest_columns($pdo);
        ensure_billing_columns($pdo);

        return $pdo;
    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;padding:30px;background:#101F15;color:#EAEFED;border:1px solid #C5A059;max-width:600px;margin:50px auto;border-radius:8px;'>
            <h3 style='color:#C5A059;margin-top:0;'>Estate Database Connection Error (MySQL)</h3>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p style='font-size:13px;color:#A1B5A9;'>Ensure MySQL is running in your XAMPP Control Panel on host <strong>" . DB_HOST . "</strong> and port <strong>" . DB_PORT . "</strong>.</p>
        </div>");
    }
}

/**
 * Initialize MySQL tables and default seed data
 */
function init_mysql_database_schema(PDO $pdo) {
    // Check if admins table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'admins'")->fetchColumn();
    if ($tableCheck) {
        return; // Schema already created
    }

    // 1. Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) UNIQUE NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(150) NULL,
        `email` VARCHAR(150) NULL,
        `role` VARCHAR(50) DEFAULT 'Estate Administrator',
        `last_login` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Rooms / Villas Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rooms` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(100) UNIQUE NOT NULL,
        `title` VARCHAR(150) NOT NULL,
        `rate_per_night` DECIMAL(10,2) NOT NULL,
        `elevation` VARCHAR(100) NULL,
        `max_guests` INT DEFAULT 2,
        `description` TEXT NULL,
        `amenities` TEXT NULL,
        `image_url` VARCHAR(255) NULL,
        `is_available` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 3. Bookings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `bookings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reference_code` VARCHAR(100) UNIQUE NOT NULL,
        `villa_type` VARCHAR(100) NOT NULL,
        `guest_name` VARCHAR(150) NOT NULL,
        `guest_phone` VARCHAR(50) NOT NULL,
        `guest_email` VARCHAR(150) NULL,
        `guests_count` INT DEFAULT 2,
        `checkin_date` DATE NOT NULL,
        `checkout_date` DATE NOT NULL,
        `nights` INT DEFAULT 1,
        `addons` TEXT NULL,
        `special_notes` TEXT NULL,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `status` VARCHAR(50) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 4. Inquiries Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `inquiries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `phone` VARCHAR(50) NULL,
        `subject` VARCHAR(200) NULL,
        `message` TEXT NOT NULL,
        `status` VARCHAR(50) DEFAULT 'unread',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 5. Settings / Content Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` VARCHAR(191) PRIMARY KEY,
        `setting_value` MEDIUMTEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 6. Visual Gallery Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `gallery` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(200) NOT NULL,
        `caption` TEXT NULL,
        `tag` VARCHAR(100) NULL,
        `category` VARCHAR(100) DEFAULT 'Landscape',
        `image_url` VARCHAR(255) NOT NULL,
        `display_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 7. Testimonials Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `testimonials` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `guest_name` VARCHAR(150) NOT NULL,
        `guest_location` VARCHAR(150) NULL,
        `stay_badge` VARCHAR(100) DEFAULT 'CANOPY TREEHOUSE',
        `stars` INT DEFAULT 5,
        `quote` TEXT NOT NULL,
        `initials` VARCHAR(10) NULL,
        `display_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 8. Experiences Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `experiences` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(200) NOT NULL,
        `badge` VARCHAR(100) DEFAULT 'INCLUDED IN STAY',
        `timing` VARCHAR(100) DEFAULT '2 Hours • Morning',
        `description` TEXT NOT NULL,
        `image_url` VARCHAR(255) NOT NULL,
        `display_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    seed_mysql_initial_data($pdo);
}

/**
 * Seed initial records into MySQL tables
 */
function seed_mysql_initial_data(PDO $pdo) {
    // Seed Default Admin (admin / admin123)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $default_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $ins = $pdo->prepare("INSERT INTO admins (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([
            'admin',
            $default_hash,
            'Master Concierge',
            'concierge@foodforestkanthalloor.com',
            'Master Concierge'
        ]);
    }

    // Seed Rooms
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $ins = $pdo->prepare("INSERT INTO rooms (slug, title, rate_per_night, elevation, max_guests, description, amenities, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([
            'treehouse',
            'The Canopy Treehouse',
            14500.00,
            '30FT ELEVATION',
            2,
            'Perched thirty feet above the fertile soil of Kanthalloor, the Canopy Treehouse is an architectural homage to wild timber and morning mist. Floor-to-ceiling panoramic glass windows frame endless tea horizons, while a private cantilevered balcony brings you into the canopy birdsong.',
            'King Artisan Bed, Mountain-View Cantilevered Deck, Solar-Heated Rain Shower, Handcrafted Herbal Teas, Ambient Fire Hearth, Telescope for Stargazing, Zero-Plastic Toiletries',
            'assets/images/treehouse_exterior_front.jpg',
            1
        ]);
        $ins->execute([
            'mudhouse',
            'The Earthen Mudhouse',
            11500.00,
            'COB HERITAGE',
            3,
            'Sculpted entirely from native red earth, river sand, and straw, the Earthen Mudhouse breathes with the surrounding shola forest. Naturally air-conditioned by earthen cob walls, this sanctuary stays blissfully cool by day and soothingly warm by night.',
            'Hand-Carved Wooden Queen Bed, Natural Clay Cooler, Slate Hearth Fireplace, Terracotta Veranda, Private Organic Herb Garden, Forest Spring Water, Organic Bedding',
            'assets/images/mudhouse_exterior.png',
            1
        ]);
    }

    // Seed Gallery
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gallery");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $gallery_items = [
            ['title' => 'High Canopy Treehouse in Mist', 'caption' => 'Perched 30 feet above the forest floor among silver oaks, overlooking rolling valley clouds.', 'tag' => 'CANOPY DWELLING · 1,640M', 'category' => 'Villas & Stays', 'image_url' => 'assets/images/treehouse_exterior.png', 'display_order' => 1],
            ['title' => 'Hand-Sculpted Cob Mudhouse', 'caption' => 'Naturally insulated clay and sand architecture with private herbal garden courtyards.', 'tag' => 'EARTHEN ARCHITECTURE', 'category' => 'Handcrafted Living', 'image_url' => 'assets/images/mudhouse_exterior.png', 'display_order' => 2],
            ['title' => 'The Western Ghats Vista', 'caption' => 'High-altitude horizon cloaked in shifting clouds and untouched shola wilderness.', 'tag' => 'ALPINE HORIZON', 'category' => 'Landscape', 'image_url' => 'assets/images/01 (25).jpeg', 'display_order' => 3],
            ['title' => 'Woodfire Claypot Lunch', 'caption' => 'Pure farm-to-table cooking over slow embers using hand-ground spices and organic produce.', 'tag' => 'EARTHEN GASTRONOMY', 'category' => 'Gastronomy', 'image_url' => 'assets/images/01 (3).jpeg', 'display_order' => 4],
            ['title' => 'Organic Winter Apple Orchards', 'caption' => 'Ancient heirloom trees yielding sweet, pesticide-free mountain apples each winter.', 'tag' => 'ESTATE HARVEST', 'category' => 'Orchards', 'image_url' => 'assets/images/01 (1).jpeg', 'display_order' => 5],
            ['title' => 'Stargazing by the Cob Hearth', 'caption' => 'Night skies at 1,600m altitude illuminated only by campfire crackle and constellations.', 'tag' => 'NIGHT SKY SANCTUARY', 'category' => 'Nightscape', 'image_url' => 'assets/images/01 (20).jpeg', 'display_order' => 6],
            ['title' => 'Morning Dew on Passion Fruit Vines', 'caption' => 'Wild pollinators and lush flora flourishing in our certified chemical-free sanctuary.', 'tag' => 'BOTANICAL HARMONY', 'category' => 'Flora', 'image_url' => 'assets/images/01 (15).jpeg', 'display_order' => 7],
            ['title' => 'Living Mud Courtyard Veranda', 'caption' => 'Unpaved, breathable courtyards connecting guest quarters directly with the soil.', 'tag' => 'BIOPHILIC SPACES', 'category' => 'Architecture', 'image_url' => 'assets/images/01 (7).jpeg', 'display_order' => 8]
        ];
        $ins = $pdo->prepare("INSERT INTO gallery (title, caption, tag, category, image_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($gallery_items as $item) {
            $ins->execute([$item['title'], $item['caption'], $item['tag'], $item['category'], $item['image_url'], $item['display_order']]);
        }
    }

    // Seed Testimonials
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $testimonials = [
            ['guest_name' => 'Aarav & Nandita Nambiar', 'guest_location' => 'Kochi, Kerala · Stayed Feb 2026', 'stay_badge' => 'CANOPY TREEHOUSE', 'stars' => 5, 'quote' => 'The silence here is pure medicine. Waking up 30 feet above the valley floor to the mist drifting through our bedroom balcony was something we will carry in our hearts forever. The woodfire claypot lunch was unforgettable.', 'initials' => 'AN', 'display_order' => 1],
            ['guest_name' => 'Dr. Julian & Clara Vance', 'guest_location' => 'Edinburgh, UK · Stayed Jan 2026', 'stay_badge' => 'EARTHEN MUDHOUSE', 'stars' => 5, 'quote' => 'As architects passionate about sustainable living, the cob mudhouse blew us away. The natural indoor temperature stayed delightfully cool despite the midday sun. Stargazing by the open hearth was unmatched.', 'initials' => 'JV', 'display_order' => 2],
            ['guest_name' => 'Meera Krishnan', 'guest_location' => 'Bengaluru, India · Stayed Feb 2026', 'stay_badge' => 'SOLO RETREAT', 'stars' => 5, 'quote' => 'I came seeking refuge from city noise and found complete sanctuary. The sound of the mountain brook, the aroma of crushed wild rosemary on the morning trails, and the warmth of the hosts made me extend my stay by four days.', 'initials' => 'MK', 'display_order' => 3],
            ['guest_name' => 'Vikramaditya & Rohini Sen', 'guest_location' => 'New Delhi, India · Stayed Dec 2025', 'stay_badge' => 'ORCHARD HARVEST STAY', 'stars' => 5, 'quote' => 'Our children had never plucked apples and passionfruit directly from trees before. Eating ripe fruit straight from the branch while watching the clouds roll into the valley was the highlight of our year.', 'initials' => 'VS', 'display_order' => 4]
        ];
        $ins = $pdo->prepare("INSERT INTO testimonials (guest_name, guest_location, stay_badge, stars, quote, initials, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($testimonials as $t) {
            $ins->execute([$t['guest_name'], $t['guest_location'], $t['stay_badge'], $t['stars'], $t['quote'], $t['initials'], $t['display_order']]);
        }
    }

    // Seed Experiences
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM experiences");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $experiences = [
            ['title' => 'Heirloom Orchard Harvest Walks', 'badge' => 'INCLUDED IN STAY', 'timing' => '2 Hours • Morning', 'description' => 'Wander through terraced orchards accompanied by our resident botanist. Hand-pluck apples, blackberries, tree tomatoes, and learn organic permaculture.', 'image_url' => 'assets/images/01 (18).jpeg', 'display_order' => 1],
            ['title' => 'Starlit Hearth & Folklore', 'badge' => 'EVENING RITUAL', 'timing' => 'Twilight — Night', 'description' => 'Gather around an open slate fire beneath an unpolluted Milky Way sky. Unwind with hot cardamom spiced brews, roasted corn, and quiet acoustic music.', 'image_url' => 'assets/images/01 (30).jpeg', 'display_order' => 2],
            ['title' => 'Vernacular Mud & Clay Workshops', 'badge' => 'WORKSHOP', 'timing' => '2.5 Hours • Afternoon', 'description' => 'Get hands-on with native red earth. Learn the ancient alchemy of straw, clay, and terracotta to discover how homes can breathe without mechanical cooling.', 'image_url' => 'assets/images/01 (6).jpeg', 'display_order' => 3],
            ['title' => 'Sunrise High-Ridge Valley Trek', 'badge' => 'ADVENTURE', 'timing' => '3.5 Hours • Sunrise', 'description' => 'Ascend through sandalwood forests and misty tea fringes to witness the sunrise break across the Anaimudi peak range with fresh mountain tea.', 'image_url' => 'assets/images/01 (33).jpeg', 'display_order' => 4]
        ];
        $ins = $pdo->prepare("INSERT INTO experiences (title, badge, timing, description, image_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($experiences as $e) {
            $ins->execute([$e['title'], $e['badge'], $e['timing'], $e['description'], $e['image_url'], $e['display_order']]);
        }
    }

    // Seed Settings & CMS Content Blocks
    $defaults = [
        'estate_name' => 'Food Forest Eco Sanctuary',
        'location' => 'Kanthalloor, Marayoor Valley, Idukki District, Kerala 685620',
        'concierge_phone' => '+91 92345 67890',
        'concierge_whatsapp' => '919234567890',
        'concierge_email' => 'concierge@foodforestkanthalloor.com',
        'currency_symbol' => '₹',
        'checkin_time' => '02:00 PM',
        'checkout_time' => '11:00 AM',

        'hero_eyebrow' => 'KANTHALLOOR, KERALA • PRIVATE ECO-SANCTUARY',
        'hero_title' => 'Where Earth Breathes & Time Stands Still.',
        'hero_desc' => 'Tucked deep in the misty hills and organic orchards of Kanthalloor. Experience private earthen mudhouses, soaring canopy treehouses, and nourishing farm gastronomy cooked slowly over wood fires.',
        'hero_bg_image' => 'assets/images/01 (25).jpeg',
        'top_bar_location' => 'Kanthalloor High Range • 1,600m Elevation • 18°C Misty Mountain Air',
        'top_bar_accolade' => 'Rated 4.98 / 5 • Top Sustainable Sanctuary 2026',

        'welcome_badge' => 'THE SANCTUARY PHILOSOPHY',
        'welcome_title' => 'Rooted in Earth, Reverence & Time',
        'welcome_paragraph' => 'Food Forest is not merely a getaway; it is a conscious return to living in harmony with nature. Tucked into the mist-veiled terraced hills of Kanthalloor, Kerala, our estate was conceived as a living ecosystem where luxury means silence, pure mountain spring water, and unhurried peace.',
        'welcome_image' => 'assets/images/01 (7).jpeg',

        'why_badge' => 'WHY FOOD FOREST?',
        'why_title' => 'An Authentic Farmstay Sanctuary',
        'why_desc' => 'At Food Forest Kanthalloor, every design detail is curated to offer comfort while leaving zero ecological footprint. As an organic farmstay in the high-altitude hills of Kerala, we offer two distinct living experiences: soaring high into the canopy in our Luxury Treehouse, or grounded in the ancient thermal cool of our Traditional Earthen Mudhouse.',
        'why_image' => 'assets/images/01 (26).jpeg',

        'experiences_badge' => 'CURATED JOURNEYS',
        'experiences_title' => 'Rituals of the High Range',
        'experiences_desc' => 'Connect deeply with the pulse of Kanthalloor. Each experience is handcrafted to immerse you in vernacular craftsmanship, mountain wilderness, and restorative tranquility.',

        'seasons_badge' => 'EVERY SEASON HAS A STORY',
        'seasons_title' => "Nature's Changing Canvas",
        'seasons_desc' => 'Kanthalloor shifts beautifully throughout the year. Each season paints our organic forest retreat in a completely distinct set of colors.'
    ];

    $ins = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $ins->execute([$k, $v]);
    }
}

// =========================================================================
// Global CMS Helper Functions for Frontend & Backend
// =========================================================================

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Retrieve a site setting by key, with optional fallback.
 */
function get_setting($key, $default = '') {
    static $settings_cache = null;
    if ($settings_cache === null) {
        try {
            $pdo = get_db();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings_cache = [];
            while ($row = $stmt->fetch()) {
                $settings_cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            return $default;
        }
    }
    return $settings_cache[$key] ?? $default;
}

/**
 * Retrieve all rooms from database.
 */
function get_all_rooms($only_available = false) {
    try {
        $pdo = get_db();
        $sql = "SELECT * FROM rooms";
        if ($only_available) {
            $sql .= " WHERE is_available = 1";
        }
        $sql .= " ORDER BY id ASC";
        return $pdo->query($sql)->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Retrieve active gallery items.
 */
function get_gallery_items($limit = null, $category = null) {
    try {
        $pdo = get_db();
        $sql = "SELECT * FROM gallery WHERE is_active = 1";
        $params = [];
        if ($category && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        $sql .= " ORDER BY display_order ASC, id ASC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Retrieve curated multi-photo gallery collections with full photo arrays
 */
function get_gallery_collections($limit = null, $category = null) {
    $collections = [
        [
            'id' => 1,
            'slug' => 'treehouse-mist',
            'title' => 'High Canopy Treehouse in Mist',
            'category' => 'Villas & Stays',
            'category_slug' => 'dwellings',
            'tag' => 'CANOPY DWELLING · 1,640M',
            'cover_image' => 'assets/images/treehouse_exterior.png',
            'description' => 'Suspended 30 feet above the fertile forest floor, overlooking rolling valley clouds and silver oak groves.',
            'photos' => [
                ['src' => 'assets/images/treehouse_exterior.png', 'title' => 'Canopy Treehouse in Morning Mist', 'caption' => 'Rising 30 feet into the Kanthalloor sky with panoramic views of the eastern valley.'],
                ['src' => 'assets/images/treehouse_exterior_front.jpg', 'title' => 'Cantilevered Teak Balcony', 'caption' => 'Private forest deck facing morning sunrise and drifting cloud waves.'],
                ['src' => 'assets/images/treehouse_interior.png', 'title' => 'Loft Artisan Bedroom Suite', 'caption' => 'Warm reclaimed timber, panoramic glass walls, and handcrafted linens.'],
                ['src' => 'assets/images/treehouse_360_pano.jpg', 'title' => '360° Shola Forest Panorama', 'caption' => 'Surrounded by dense forest foliage, birdsong, and crisp mountain breeze.'],
                ['src' => 'assets/images/01 (10).jpeg', 'title' => 'Twilight Balcony Glow', 'caption' => 'Soft evening lighting filtering through the tree canopy as darkness falls.'],
                ['src' => 'assets/images/01 (25).jpeg', 'title' => 'Morning Fog Horizon', 'caption' => 'Awakening above the cloud blanket across the Western Ghats range.']
            ]
        ],
        [
            'id' => 2,
            'slug' => 'cob-mudhouse',
            'title' => 'Hand-Sculpted Cob Mudhouse',
            'category' => 'Handcrafted Living',
            'category_slug' => 'dwellings',
            'tag' => 'EARTHEN ARCHITECTURE',
            'cover_image' => 'assets/images/mudhouse_exterior.png',
            'description' => 'Naturally insulated clay, sand, and straw architecture with private herbal garden courtyards.',
            'photos' => [
                ['src' => 'assets/images/mudhouse_exterior.png', 'title' => 'Cob Mudhouse Cottage Frontage', 'caption' => 'Naturally breathable earthen walls that maintain cool temperatures by day and warmth by night.'],
                ['src' => 'assets/images/mudhouse_interior.png', 'title' => 'Earthen Living Room & Hearth', 'caption' => 'Organic textures, slate stone floors, and hand-carved wooden fixtures.'],
                ['src' => 'assets/images/01 (7).jpeg', 'title' => 'Terracotta Veranda & Cob Wall', 'caption' => 'Traditional clay roof tiles with sweeping views of the organic vegetable terraces.'],
                ['src' => 'assets/images/01 (20).jpeg', 'title' => 'Mudhouse by Campfire Hearth', 'caption' => 'Gentle crackle of open fires warming the terracotta courtyard at twilight.'],
                ['src' => 'assets/images/01 (17).jpeg', 'title' => 'Herbal Garden Courtyard Walk', 'caption' => 'Fragrant rosemary, lavender, and lemongrass bordering the mudhouse paths.'],
                ['src' => 'assets/images/01 (2).jpeg', 'title' => 'Artisan Handcrafted Bedroom', 'caption' => 'Peaceful sanctuary designed for deep rest away from digital screens.']
            ]
        ],
        [
            'id' => 3,
            'slug' => 'western-ghats-vista',
            'title' => 'The Western Ghats Vista',
            'category' => 'Landscape',
            'category_slug' => 'landscape',
            'tag' => 'ALPINE HORIZON · 1,600M',
            'cover_image' => 'assets/images/01 (25).jpeg',
            'description' => 'High-altitude horizon cloaked in shifting clouds and untouched shola wilderness.',
            'photos' => [
                ['src' => 'assets/images/01 (25).jpeg', 'title' => 'High-Altitude Valley Horizon', 'caption' => 'Sweeping vistas of the Anaimudi foothills and dense mountain slopes.'],
                ['src' => 'assets/images/01 (26).jpeg', 'title' => 'Misty Ridges & Cloud Waves', 'caption' => 'Clouds sweeping through the valley canyons during early morning hours.'],
                ['src' => 'assets/images/01 (27).jpeg', 'title' => 'Perennial Brook Stream', 'caption' => 'Pure mineral water cascading through mossy boulders across the sanctuary.'],
                ['src' => 'assets/images/01 (28).jpeg', 'title' => 'Golden Hour Mountain Silhouette', 'caption' => 'Sunlight breaking across the eastern mountain ridge.'],
                ['src' => 'assets/images/01 (29).jpeg', 'title' => 'Ancient Shola Forest Grove', 'caption' => 'Centuries-old biodiversity hotspot home to rare flora and fauna.'],
                ['src' => 'assets/images/01 (30).jpeg', 'title' => 'Twilight Valley Panorama', 'caption' => 'Serene purple twilight settling over the high-range tea gardens.']
            ]
        ],
        [
            'id' => 4,
            'slug' => 'woodfire-gastronomy',
            'title' => 'Woodfire Claypot Gastronomy',
            'category' => 'Gastronomy',
            'category_slug' => 'gastronomy',
            'tag' => 'EARTHEN GASTRONOMY',
            'cover_image' => 'assets/images/01 (3).jpeg',
            'description' => 'Pure farm-to-table cooking over slow embers using hand-ground spices and organic produce.',
            'photos' => [
                ['src' => 'assets/images/01 (3).jpeg', 'title' => 'Claypot Simmering on Open Fire', 'caption' => 'Slow-cooked heirloom grains and vegetables infused with natural wood smoke.'],
                ['src' => 'assets/images/food_kerala_sadya.jpg', 'title' => 'Traditional Farm Feast (Sadya)', 'caption' => 'Served on fresh banana leaves with organic estate-grown vegetables and spices.'],
                ['src' => 'assets/images/food_dosa_set.jpg', 'title' => 'Crisp Morning Dosa & Chutneys', 'caption' => 'Stone-ground fermented batter with fresh mountain coconut and mint chutneys.'],
                ['src' => 'assets/images/food_evening_snacks.jpg', 'title' => 'Twilight Spiced Tea & Snacks', 'caption' => 'Steaming cardamom mountain tea paired with hot steamed herbal snacks.'],
                ['src' => 'assets/images/01 (4).jpeg', 'title' => 'Open-Air Forest Dining Deck', 'caption' => 'Savoring wholesome organic meals surrounded by the rustle of leaves.'],
                ['src' => 'assets/images/01 (5).jpeg', 'title' => 'The Earthen Kitchen Hearth', 'caption' => 'Where age-old culinary secrets and slow nourishment come to life.']
            ]
        ],
        [
            'id' => 5,
            'slug' => 'winter-orchards',
            'title' => 'Organic Winter Apple Orchards',
            'category' => 'Orchards',
            'category_slug' => 'orchards',
            'tag' => 'ESTATE HARVEST',
            'cover_image' => 'assets/images/01 (1).jpeg',
            'description' => 'Ancient heirloom trees yielding sweet, pesticide-free mountain apples, plums, and passion fruits.',
            'photos' => [
                ['src' => 'assets/images/01 (1).jpeg', 'title' => 'Terraced Apple Orchard Rows', 'caption' => 'Heirloom apple trees flourishing in the sub-tropical temperate microclimate of Kanthalloor.'],
                ['src' => 'assets/images/01 (11).jpeg', 'title' => 'Fresh Hand-Plucked Apples', 'caption' => 'Crisp, sweet, and bursting with natural flavor straight from the tree.'],
                ['src' => 'assets/images/01 (12).jpeg', 'title' => 'Lush Passion Fruit Trellises', 'caption' => 'Organic passion fruit vines draping over natural bamboo pergolas.'],
                ['src' => 'assets/images/01 (13).jpeg', 'title' => 'Wild Berry & Plum Trees', 'caption' => 'Sweet seasonal berries harvested for fresh jams and morning preserves.'],
                ['src' => 'assets/images/01 (14).jpeg', 'title' => 'Orchard Walking Pathway', 'caption' => 'Stone-lined walking paths winding through the organic fruit groves.']
            ]
        ],
        [
            'id' => 6,
            'slug' => 'night-sky-sanctuary',
            'title' => 'Stargazing by the Cob Hearth',
            'category' => 'Nightscape',
            'category_slug' => 'landscape',
            'tag' => 'NIGHT SKY SANCTUARY',
            'cover_image' => 'assets/images/01 (20).jpeg',
            'description' => 'Night skies at 1,600m altitude illuminated only by campfire crackle, moonlight, and brilliant constellations.',
            'photos' => [
                ['src' => 'assets/images/01 (20).jpeg', 'title' => 'Cob Hearth Campfire Circle', 'caption' => 'Gathering around the warm coals under an unpolluted Milky Way galaxy.'],
                ['src' => 'assets/images/01 (21).jpeg', 'title' => 'Starlit Mountain Canopy', 'caption' => 'Zero light pollution allows clear visibility of shooting stars and constellations.'],
                ['src' => 'assets/images/01 (22).jpeg', 'title' => 'Lantern-Lit Stone Paths', 'caption' => 'Subtle warm ambient lighting guiding your way through the night forest.'],
                ['src' => 'assets/images/01 (23).jpeg', 'title' => 'Evening Fire Ritual', 'caption' => 'Sharing stories, folklore, and quiet acoustic music by the hearth.'],
                ['src' => 'assets/images/01 (24).jpeg', 'title' => 'Moonlight Over the Valleys', 'caption' => 'Silvery moonlight casting a tranquil glow over the mountain contours.']
            ]
        ],
        [
            'id' => 7,
            'slug' => 'botanical-harmony',
            'title' => 'Botanical Harmony & Shola Flora',
            'category' => 'Flora',
            'category_slug' => 'orchards',
            'tag' => 'BOTANICAL HARMONY',
            'cover_image' => 'assets/images/01 (15).jpeg',
            'description' => 'Wild pollinators, medicinal herbs, and lush endemic flora flourishing in our chemical-free sanctuary.',
            'photos' => [
                ['src' => 'assets/images/01 (15).jpeg', 'title' => 'Morning Dew on Passion Fruit Vines', 'caption' => 'Crystal dewdrops clinging to wild tendrils in the early dawn light.'],
                ['src' => 'assets/images/01 (16).jpeg', 'title' => 'Wild Shola Orchids & Ferns', 'caption' => 'Endemic ferns and rare orchids thriving in the moist mountain air.'],
                ['src' => 'assets/images/01 (18).jpeg', 'title' => 'Medicinal Herb Sanctuary', 'caption' => 'Cultivating tulsi, lemongrass, brahmi, and wild forest herbs.'],
                ['src' => 'assets/images/01 (19).jpeg', 'title' => 'Canopy Epiphytes & Silver Oaks', 'caption' => 'Lush mosses and air plants thriving on towering mountain trees.'],
                ['src' => 'assets/images/01 (31).jpeg', 'title' => 'Brook-Side Wildflowers', 'caption' => 'Vibrant blossoms lining the banks of the perennial forest brook.']
            ]
        ],
        [
            'id' => 8,
            'slug' => 'living-courtyards',
            'title' => 'Living Mud Courtyard Veranda',
            'category' => 'Architecture',
            'category_slug' => 'dwellings',
            'tag' => 'BIOPHILIC SPACES',
            'cover_image' => 'assets/images/01 (7).jpeg',
            'description' => 'Unpaved, breathable courtyards connecting guest quarters directly with the soil and mountain stone.',
            'photos' => [
                ['src' => 'assets/images/01 (7).jpeg', 'title' => 'Living Mud Courtyard Veranda', 'caption' => 'Terracotta verandas designed for slow morning teas and peaceful contemplation.'],
                ['src' => 'assets/images/01 (8).jpeg', 'title' => 'Natural Light & Breathable Clay', 'caption' => 'Deep overhangs that keep out harsh sun while welcoming cool valley breezes.'],
                ['src' => 'assets/images/01 (9).jpeg', 'title' => 'Earthen Seating Nook', 'caption' => 'Cob benches sculpted directly out of native red clay and river sand.'],
                ['src' => 'assets/images/01 (32).jpeg', 'title' => 'Hand-Cut Stone Pathways', 'caption' => 'Meandering flagstone walkways harmoniously embedded in clover and grass.'],
                ['src' => 'assets/images/01 (33).jpeg', 'title' => 'Sunrise on the Veranda', 'caption' => 'Watch the morning mist dissipate from the comfort of a shaded patio.']
            ]
        ]
    ];

    if ($category && $category !== 'all') {
        $collections = array_values(array_filter($collections, function($c) use ($category) {
            return $c['category_slug'] === $category || strtolower($c['category']) === strtolower($category);
        }));
    }

    if ($limit) {
        $collections = array_slice($collections, 0, (int)$limit);
    }

    return $collections;
}

/**
 * Retrieve active testimonials.
 */
function get_testimonials($limit = null) {
    try {
        $pdo = get_db();
        $sql = "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order ASC, id ASC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        return $pdo->query($sql)->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Retrieve active curated experiences.
 */
function get_experiences($limit = null) {
    try {
        $pdo = get_db();
        ensure_experiences_details_columns($pdo);
        $sql = "SELECT * FROM experiences WHERE is_active = 1 ORDER BY display_order ASC, id ASC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $exps = $pdo->query($sql)->fetchAll();
        foreach ($exps as &$exp) {
            $gallery = [];
            if (!empty($exp['gallery_images'])) {
                $dec = json_decode($exp['gallery_images'], true);
                if (is_array($dec)) {
                    $gallery = array_values(array_filter($dec));
                }
            }
            if (empty($gallery) && !empty($exp['image_url'])) {
                $gallery = [$exp['image_url']];
            }
            $exp['gallery_list'] = $gallery;

            $highlights = [];
            if (!empty($exp['highlights'])) {
                $dec = json_decode($exp['highlights'], true);
                if (is_array($dec)) {
                    $highlights = array_values(array_filter($dec));
                } else {
                    $highlights = array_values(array_filter(array_map('trim', explode("\n", $exp['highlights']))));
                }
            }
            $exp['highlights_list'] = $highlights;
        }
        return $exps;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Ensure the seasons table exists and seed initial data if empty
 */
function ensure_seasons_table_exists(PDO $pdo) {
    static $checked = false;
    if ($checked) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS `seasons` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(150) NOT NULL,
        `months` VARCHAR(150) NOT NULL,
        `description` TEXT NOT NULL,
        `image_url` VARCHAR(255) NOT NULL,
        `display_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM `seasons`")->fetchColumn();
    if ($count === 0) {
        $s1_name = get_setting('season_1_name', 'Monsoon Magic');
        $s1_months = get_setting('season_1_months', 'June - September');
        $s1_desc = get_setting('season_1_desc', 'Lush, deep green landscapes, rising mist, heavy refreshing rainfall, and crisp cold mountain breeze.');
        $s1_img = get_setting('season_1_image', 'assets/images/01 (9).jpeg');

        $s2_name = get_setting('season_2_name', 'Cozy Winter');
        $s2_months = get_setting('season_2_months', 'October - February');
        $s2_desc = get_setting('season_2_desc', 'Chilly mist, clear bright blue skies, warm sunlit afternoons, and snug campfire nights under starry skies.');
        $s2_img = get_setting('season_2_image', 'assets/images/01 (8).jpeg');

        $s3_name = get_setting('season_3_name', 'Harvest Season');
        $s3_months = get_setting('season_3_months', 'March - May');
        $s3_desc = get_setting('season_3_desc', 'Crisp mountain breezes, blooming orchards of apples, oranges, and plums, and vibrant farm life in full motion.');
        $s3_img = get_setting('season_3_image', 'assets/images/01 (19).jpeg');

        $s4_name = get_setting('season_4_name', 'Summer Bloom');
        $s4_months = get_setting('season_4_months', 'April - June');
        $s4_desc = get_setting('season_4_desc', 'Pleasant, breezy weather. Kanthalloor acts as a cool refuge from the sweltering heat of the plains.');
        $s4_img = get_setting('season_4_image', 'assets/images/01 (33).jpeg');

        $ins = $pdo->prepare("INSERT INTO `seasons` (title, months, description, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $ins->execute([$s1_name, $s1_months, $s1_desc, $s1_img, 1]);
        $ins->execute([$s2_name, $s2_months, $s2_desc, $s2_img, 2]);
        $ins->execute([$s3_name, $s3_months, $s3_desc, $s3_img, 3]);
        $ins->execute([$s4_name, $s4_months, $s4_desc, $s4_img, 4]);
    }
    $checked = true;
}

/**
 * Retrieve active or all seasons.
 */
function get_all_seasons($only_active = false) {
    try {
        $pdo = get_db();
        ensure_seasons_table_exists($pdo);
        $sql = "SELECT * FROM `seasons`";
        if ($only_active) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY display_order ASC, id ASC";
        return $pdo->query($sql)->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Ensure interior_360_url column exists on rooms table and set default 360 pano assets
 */
function ensure_rooms_360_column(PDO $pdo) {
    static $checked = false;
    if ($checked) return;

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'interior_360_url'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `interior_360_url` VARCHAR(255) NULL AFTER `image_url`");
        }

        // Set defaults for treehouse and mudhouse if not set
        $pdo->exec("UPDATE `rooms` SET `interior_360_url` = 'assets/images/treehouse_360_pano.jpg' WHERE `slug` = 'treehouse' AND (`interior_360_url` IS NULL OR `interior_360_url` = '')");
        $pdo->exec("UPDATE `rooms` SET `interior_360_url` = 'assets/images/mudhouse_360_pano.jpg' WHERE `slug` = 'mudhouse' AND (`interior_360_url` IS NULL OR `interior_360_url` = '')");
    } catch (Exception $e) {
        // Silently skip if DB not ready
    }

    $checked = true;
}

/**
 * Ensure stay_type, structure_type, min_guests, base_guests, single_room_rate, and extra_guest_rate columns exist on rooms table,
 * configure defaults for Single Cottages (Mudhouse, Treehouse) and Duplex Room (2 Adjoining Suites).
 */
function ensure_rooms_pricing_columns(PDO $pdo) {
    static $checked = false;
    if ($checked) return;

    try {
        // 1. Check & Add stay_type
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'stay_type'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `stay_type` VARCHAR(50) DEFAULT 'treehouse' AFTER `slug`");
        }

        // 2. Check & Add structure_type (single_hut vs duplex_hut)
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'structure_type'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `structure_type` VARCHAR(50) DEFAULT 'single_hut' AFTER `stay_type`");
        }

        // 3. Check & Add min_guests
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'min_guests'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `min_guests` INT DEFAULT 2 AFTER `elevation`");
        }

        // 4. Check & Add base_guests
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'base_guests'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `base_guests` INT DEFAULT 2 AFTER `min_guests`");
        }

        // 5. Check & Add extra_guest_rate (Adult extra rate)
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'extra_guest_rate'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `extra_guest_rate` DECIMAL(10,2) DEFAULT 1500.00 AFTER `rate_per_night`");
        }

        // 6. Check & Add extra_child_rate (Child extra rate)
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'extra_child_rate'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `extra_child_rate` DECIMAL(10,2) DEFAULT 800.00 AFTER `extra_guest_rate`");
        }

        // 7. Check & Add single_room_rate (for duplex single room rate option)
        $cols = $pdo->query("SHOW COLUMNS FROM `rooms` LIKE 'single_room_rate'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `rooms` ADD COLUMN `single_room_rate` DECIMAL(10,2) NULL AFTER `rate_per_night`");
        }

        // 8. Check & Add adults_count, kids_count, extra_adults, extra_kids to bookings table
        $b_cols = $pdo->query("SHOW COLUMNS FROM `bookings` LIKE 'adults_count'")->fetchAll();
        if (empty($b_cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `adults_count` INT DEFAULT 2 AFTER `guest_email`");
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `kids_count` INT DEFAULT 0 AFTER `adults_count`");
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `extra_adults` INT DEFAULT 0 AFTER `kids_count`");
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `extra_kids` INT DEFAULT 0 AFTER `extra_adults`");
        }

        // Update Treehouse Single Cottage defaults (Min 2, Base 2, Max 4, Rate ₹14,500, extra adult ₹1,500, extra child ₹800)
        $pdo->exec("UPDATE `rooms` SET 
            `title` = COALESCE(NULLIF(`title`, ''), 'High-Altitude Canopy Treehouse'),
            `stay_type` = 'treehouse',
            `structure_type` = 'single_hut',
            `min_guests` = COALESCE(NULLIF(`min_guests`, 0), 2),
            `base_guests` = COALESCE(NULLIF(`base_guests`, 0), 2),
            `max_guests` = GREATEST(`max_guests`, 4),
            `rate_per_night` = COALESCE(NULLIF(`rate_per_night`, 0), 14500.00),
            `extra_guest_rate` = COALESCE(NULLIF(`extra_guest_rate`, 0), 1500.00),
            `extra_child_rate` = COALESCE(NULLIF(`extra_child_rate`, 0), 800.00),
            `single_room_rate` = COALESCE(`single_room_rate`, `rate_per_night`)
            WHERE `slug` = 'treehouse'");

        // Update Mudhouse Single Cottage defaults (Min 2, Base 2, Max 4, Rate ₹11,500, extra adult ₹1,500, extra child ₹800)
        $pdo->exec("UPDATE `rooms` SET 
            `title` = COALESCE(NULLIF(`title`, ''), 'Traditional Earthen Mudhouse'),
            `stay_type` = 'mudhouse', 
            `structure_type` = 'single_hut',
            `min_guests` = COALESCE(NULLIF(`min_guests`, 0), 2),
            `base_guests` = COALESCE(NULLIF(`base_guests`, 0), 2),
            `max_guests` = GREATEST(`max_guests`, 4),
            `rate_per_night` = COALESCE(NULLIF(`rate_per_night`, 0), 11500.00),
            `extra_guest_rate` = COALESCE(NULLIF(`extra_guest_rate`, 0), 1500.00),
            `extra_child_rate` = COALESCE(NULLIF(`extra_child_rate`, 0), 800.00),
            `single_room_rate` = COALESCE(`single_room_rate`, `rate_per_night`)
            WHERE `slug` = 'mudhouse'");

        // Ensure exactly one signature Duplex Room exists (Woodhouse / Treehouse Duplex with 2 Adjoining Suites)
        $duplex_count = (int)$pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `structure_type` = 'duplex_hut'")->fetchColumn();
        if ($duplex_count === 0) {
            $ins = $pdo->prepare("INSERT INTO `rooms` 
                (slug, stay_type, structure_type, title, rate_per_night, single_room_rate, extra_guest_rate, extra_child_rate, elevation, min_guests, base_guests, max_guests, description, amenities, image_url, interior_360_url, is_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $ins->execute([
                'woodhouse-duplex',
                'treehouse',
                'duplex_hut',
                'Forest Heritage Duplex Chalet (2 Adjoining Suites)',
                24000.00,
                14500.00,
                2000.00,
                1000.00,
                '1,620M ELEVATION • DUPLEX RESIDENCE',
                2,
                4,
                8,
                'An expansive authentic two-wing duplex residence built under one roof, partitioned by a sound-insulated acoustic wall. Features two separate private master suites (each with king bed, ensuite bath, and private valley balcony). Can be reserved as Single Room (Base 2 Guests) or Entire 2-Wing Duplex (Base 4 Guests).',
                '2 King Bedrooms, 2 Ensuite Rain Showers, Dual Panoramic Decks, Acoustic Dividing Wall, Stone Fireplace, All Organic Farm Meals Included',
                'assets/images/01 (25).jpeg',
                'assets/images/treehouse_360_pano.jpg'
            ]);
            $pdo->exec("UPDATE `rooms` SET 
                `structure_type` = 'duplex_hut',
                `min_guests` = COALESCE(NULLIF(`min_guests`, 0), 2),
                `base_guests` = COALESCE(NULLIF(`base_guests`, 0), 4),
                `max_guests` = GREATEST(`max_guests`, 8),
                `single_room_rate` = COALESCE(NULLIF(`single_room_rate`, 0), 14500.00),
                `rate_per_night` = COALESCE(NULLIF(`rate_per_night`, 0), 24000.00),
                `extra_guest_rate` = COALESCE(NULLIF(`extra_guest_rate`, 0), 2000.00),
                `extra_child_rate` = COALESCE(NULLIF(`extra_child_rate`, 0), 1000.00)
                WHERE `structure_type` = 'duplex_hut'");
        }

        // Check if Woodhouse Single Hut exists; seed or update it!
        $wood_count = (int)$pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `slug` = 'woodhouse'")->fetchColumn();
        if ($wood_count === 0) {
            $ins = $pdo->prepare("INSERT INTO `rooms` 
                (slug, stay_type, structure_type, title, rate_per_night, single_room_rate, extra_guest_rate, extra_child_rate, elevation, base_guests, max_guests, description, amenities, image_url, interior_360_url, is_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $ins->execute([
                'woodhouse',
                'woodhouse',
                'single_hut',
                'The Alpine Woodhouse & Timber Chalet',
                13500.00,
                13500.00,
                1800.00,
                900.00,
                'PINEWOOD TIMBER • 1,620M MSL',
                2,
                4,
                'Warm pine and cedarwood timber lodge constructed using traditional joinery methods. Features panoramic glass valley gables, aromatic pine interiors, personal timber deck, and private star-gazing attic.',
                'Hand-Hewn Cedar King Bed, Mountain Balcony, Attic Skylight, Natural Pinewood Insulation, Organic Farm Dining Included',
                'assets/images/01 (25).jpeg',
                'assets/images/treehouse_360_pano.jpg'
            ]);
        }

        // Check if Woodhouse Duplex Chalet exists; seed or update it!
        $wood_duplex_count = (int)$pdo->query("SELECT COUNT(*) FROM `rooms` WHERE `slug` = 'woodhouse-duplex'")->fetchColumn();
        if ($wood_duplex_count === 0) {
            $ins = $pdo->prepare("INSERT INTO `rooms` 
                (slug, stay_type, structure_type, title, rate_per_night, single_room_rate, extra_guest_rate, extra_child_rate, elevation, base_guests, max_guests, description, amenities, image_url, interior_360_url, is_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $ins->execute([
                'woodhouse-duplex',
                'woodhouse',
                'duplex_hut',
                'The Alpine Woodhouse — Duplex Chalet',
                23000.00,
                13500.00,
                1800.00,
                900.00,
                'DOUBLE PINE CHALET • DUPLEX',
                4,
                7,
                'A two-level luxury pine residence overlooking the mist of Marayoor valley. Boasts two independent timber suites, dual cedar verandas, and a central stone hearth lounge for shared mountain evenings.',
                '2 Cedar King Suites, Dual Balconies, Central Stone Hearth, Attic Sun Lounge, All Organic Meals Included',
                'assets/images/01 (26).jpeg',
                'assets/images/treehouse_360_pano.jpg'
            ]);
        }
    } catch (Exception $e) {
        // Silently skip if DB not ready
    }

    $checked = true;
}

/**
 * Ensure sanctuary_spots table exists and seed initial mountain route data
 */
function ensure_sanctuary_spots_table_exists(PDO $pdo) {
    static $checked = false;
    if ($checked) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS `sanctuary_spots` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `spot_number` INT NOT NULL,
        `title` VARCHAR(150) NOT NULL,
        `subtitle_tag` VARCHAR(100) NULL,
        `category` VARCHAR(50) DEFAULT 'nature',
        `is_stay` TINYINT(1) DEFAULT 0,
        `linked_room_slug` VARCHAR(100) NULL,
        `stay_price` DECIMAL(10,2) NULL,
        `elevation` VARCHAR(100) DEFAULT '1,600M MSL',
        `temperature` VARCHAR(100) DEFAULT '18°C Alpine Breeze',
        `description` TEXT NOT NULL,
        `aroma` VARCHAR(150) NULL,
        `sound` VARCHAR(150) NULL,
        `image_url` VARCHAR(255) NOT NULL,
        `photos` TEXT NULL,
        `cta_text` VARCHAR(100) DEFAULT 'Explore Details',
        `cta_link` VARCHAR(255) DEFAULT '#rooms',
        `x_coord` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
        `y_coord` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
        `display_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Check & Add is_stay, linked_room_slug, stay_price, photos columns if missing
    $cols = $pdo->query("SHOW COLUMNS FROM `sanctuary_spots`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_stay', $cols)) {
        $pdo->exec("ALTER TABLE `sanctuary_spots` ADD COLUMN `is_stay` TINYINT(1) DEFAULT 0 AFTER `category`");
    }
    if (!in_array('linked_room_slug', $cols)) {
        $pdo->exec("ALTER TABLE `sanctuary_spots` ADD COLUMN `linked_room_slug` VARCHAR(100) NULL AFTER `is_stay`");
    }
    if (!in_array('stay_price', $cols)) {
        $pdo->exec("ALTER TABLE `sanctuary_spots` ADD COLUMN `stay_price` DECIMAL(10,2) NULL AFTER `linked_room_slug`");
    }
    if (!in_array('photos', $cols)) {
        $pdo->exec("ALTER TABLE `sanctuary_spots` ADD COLUMN `photos` TEXT NULL AFTER `image_url`");
    }
    if (!in_array('structure_type', $cols)) {
        $pdo->exec("ALTER TABLE `sanctuary_spots` ADD COLUMN `structure_type` VARCHAR(50) DEFAULT 'single_hut' AFTER `linked_room_slug`");
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM `sanctuary_spots`")->fetchColumn();
    if ($count === 0) {
        $spots = [
            [
                'spot_number' => 1,
                'title' => 'Farmhouse Kitchen & Organic Dining',
                'subtitle_tag' => 'COMMON KITCHEN & DINING',
                'category' => 'dining',
                'is_stay' => 0,
                'linked_room_slug' => null,
                'stay_price' => null,
                'elevation' => '1,580M MSL',
                'temperature' => '19°C Warm Hearth',
                'description' => 'Central farm hearth serving 100% organic farm-to-table meals harvested daily from our heirloom orchards. Wood-fired open kitchen and mountain view dining.',
                'aroma' => 'Woodsmoke, cardamom & roasted spices',
                'sound' => 'Crackling hearth, laughter & tea kettle',
                'image_url' => 'assets/images/01 (10).jpeg',
                'photos' => json_encode(['assets/images/01 (10).jpeg', 'assets/images/01 (20).jpeg', 'assets/images/01 (1).jpeg']),
                'cta_text' => 'Farmhouse Dining',
                'cta_link' => '#dining',
                'x_coord' => 18.00,
                'y_coord' => 56.00,
                'display_order' => 1
            ],
            [
                'spot_number' => 2,
                'title' => 'The Earthen Mudhouse Sanctuary',
                'subtitle_tag' => 'EARTHEN COB VILLA',
                'category' => 'stays',
                'is_stay' => 1,
                'linked_room_slug' => 'mudhouse',
                'stay_price' => 11500.00,
                'elevation' => '1,600M MSL',
                'temperature' => '21°C Thermal Comfort',
                'description' => 'Handcrafted cob clay cottages sculpted from native red soil, river sand, and straw. Naturally insulated against chilly nights with private sit-out and orchard panorama.',
                'aroma' => 'Sun-baked earth, vetiver & woodsmoke',
                'sound' => 'Crackling hearth embers, crickets',
                'image_url' => 'assets/images/mudhouse_exterior.png',
                'photos' => json_encode(['assets/images/mudhouse_exterior.png', 'assets/images/01 (26).jpeg', 'assets/images/01 (14).jpeg']),
                'cta_text' => 'Book Mudhouse',
                'cta_link' => 'booking.php?villa=mudhouse',
                'x_coord' => 30.00,
                'y_coord' => 70.00,
                'display_order' => 2
            ],
            [
                'spot_number' => 3,
                'title' => 'High-Altitude Canopy Treehouse',
                'subtitle_tag' => 'CANOPY TREEHOUSE',
                'category' => 'stays',
                'is_stay' => 1,
                'linked_room_slug' => 'treehouse',
                'stay_price' => 14500.00,
                'elevation' => '1,620M MSL',
                'temperature' => '17°C Alpine Breeze',
                'description' => 'Elevated living perched 30 feet above the forest floor among ancient high trees. Floor-to-ceiling panoramic glass windows looking out over cascading mist and apple terraces.',
                'aroma' => 'Fresh cedarwood, wild jasmine & pine',
                'sound' => 'Wind through high canopies, bulbul calls',
                'image_url' => 'assets/images/treehouse_exterior.png',
                'photos' => json_encode(['assets/images/treehouse_exterior.png', 'assets/images/treehouse_curved_window.png', 'assets/images/treehouse_timber_balcony.png']),
                'cta_text' => 'Book Treehouse',
                'cta_link' => 'booking.php?villa=treehouse',
                'x_coord' => 56.00,
                'y_coord' => 24.00,
                'display_order' => 3
            ],
            [
                'spot_number' => 4,
                'title' => 'The Alpine Woodhouse Chalet',
                'subtitle_tag' => 'PINE TIMBER CHALET',
                'category' => 'stays',
                'is_stay' => 1,
                'linked_room_slug' => 'woodhouse',
                'stay_price' => 13500.00,
                'elevation' => '1,620M MSL',
                'temperature' => '18°C Pine Forest Air',
                'description' => 'Handcrafted solid cedar and pinewood mountain chalet featuring aromatic wooden walls, high vaulted cathedral ceiling, valley-facing balcony deck, and private fire hearth.',
                'aroma' => 'Pine needles, cedar resin & crisp mist',
                'sound' => 'Rustling pine branches, mountain breeze',
                'image_url' => 'assets/images/01 (25).jpeg',
                'photos' => json_encode(['assets/images/01 (25).jpeg', 'assets/images/01 (26).jpeg', 'assets/images/treehouse_stone_fireplace.png']),
                'cta_text' => 'Book Woodhouse',
                'cta_link' => 'booking.php?villa=woodhouse',
                'x_coord' => 74.00,
                'y_coord' => 34.00,
                'display_order' => 4
            ],
            [
                'spot_number' => 5,
                'title' => 'Crystal Mountain Brook & Plunge Pool',
                'subtitle_tag' => 'FRESH SPRING PLUNGE POOL',
                'category' => 'amenities',
                'is_stay' => 0,
                'linked_room_slug' => null,
                'stay_price' => null,
                'elevation' => '1,560M MSL',
                'temperature' => '15°C Spring Freshwater',
                'description' => 'Pristine mountain brook feeding into a natural granite plunge pool. Serene freshwater bathing and riverside meditation amidst lush shola ferns.',
                'aroma' => 'Fern leaves, damp river stones & mineral mist',
                'sound' => 'Melodic rushing stream, pebble resonance',
                'image_url' => 'assets/images/01 (28).jpeg',
                'photos' => json_encode(['assets/images/01 (28).jpeg', 'assets/images/01 (3).jpeg', 'assets/images/01 (2).jpeg']),
                'cta_text' => 'Explore Waters',
                'cta_link' => '#experiences',
                'x_coord' => 46.00,
                'y_coord' => 48.00,
                'display_order' => 5
            ],
            [
                'spot_number' => 6,
                'title' => 'Campfire Glade & BBQ Grilling Shed',
                'subtitle_tag' => 'EVENING BBQ & STARGAZING',
                'category' => 'amenities',
                'is_stay' => 0,
                'linked_room_slug' => null,
                'stay_price' => null,
                'elevation' => '1,640M MSL',
                'temperature' => '14°C Crisp Night Air',
                'description' => 'Covered rustic timber barbecue pavilion and open granite firepit. Guests gather here for evening grilling rituals and acoustic stargazing under Class-1 dark skies.',
                'aroma' => 'Ember woodsmoke, roasted pepper & eucalyptus',
                'sound' => 'Acoustic guitar, crackling embers, mountain breeze',
                'image_url' => 'assets/images/01 (25).jpeg',
                'photos' => json_encode(['assets/images/01 (25).jpeg', 'assets/images/treehouse_stone_fireplace.png', 'assets/images/01 (12).jpeg']),
                'cta_text' => 'Evening Rituals',
                'cta_link' => '#experiences',
                'x_coord' => 38.00,
                'y_coord' => 22.00,
                'display_order' => 6
            ],
            [
                'spot_number' => 7,
                'title' => "Children's Play Glade & Orchard Walk",
                'subtitle_tag' => 'RECREATION & HARVEST TRAILS',
                'category' => 'nature',
                'is_stay' => 0,
                'linked_room_slug' => null,
                'stay_price' => null,
                'elevation' => '1,570M MSL',
                'temperature' => '18°C Mild Mountain Sun',
                'description' => "Terraced grassy lawn equipped with traditional wooden swings, outdoor play zones for kids, and walking trails weaving through fruit-bearing apple and plum trees.",
                'aroma' => 'Wild berries, sweet apple blossoms & clover',
                'sound' => "Songbirds, children's laughter, rustling leaves",
                'image_url' => 'assets/images/01 (19).jpeg',
                'photos' => json_encode(['assets/images/01 (19).jpeg', 'assets/images/01 (11).jpeg', 'assets/images/01 (7).jpeg']),
                'cta_text' => 'Orchard Activities',
                'cta_link' => '#experiences',
                'x_coord' => 68.00,
                'y_coord' => 68.00,
                'display_order' => 7
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO `sanctuary_spots` 
            (spot_number, title, subtitle_tag, category, is_stay, linked_room_slug, stay_price, elevation, temperature, description, aroma, sound, image_url, photos, cta_text, cta_link, x_coord, y_coord, display_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        foreach ($spots as $s) {
            $stmt->execute([
                $s['spot_number'],
                $s['title'],
                $s['subtitle_tag'],
                $s['category'],
                $s['is_stay'],
                $s['linked_room_slug'],
                $s['stay_price'],
                $s['elevation'],
                $s['temperature'],
                $s['description'],
                $s['aroma'],
                $s['sound'],
                $s['image_url'],
                $s['photos'],
                $s['cta_text'],
                $s['cta_link'],
                $s['x_coord'],
                $s['y_coord'],
                $s['display_order']
            ]);
        }
    } else {
        // Update existing spots to ensure stay metadata is synced
        $pdo->exec("UPDATE `sanctuary_spots` SET `is_stay` = 1, `linked_room_slug` = 'mudhouse', `stay_price` = 11500.00 WHERE `title` LIKE '%Mudhouse%'");
        $pdo->exec("UPDATE `sanctuary_spots` SET `is_stay` = 1, `linked_room_slug` = 'treehouse', `stay_price` = 14500.00 WHERE `title` LIKE '%Treehouse%'");
        
        // Ensure Woodhouse spot exists
        $wood_spot = (int)$pdo->query("SELECT COUNT(*) FROM `sanctuary_spots` WHERE `linked_room_slug` = 'woodhouse' OR `title` LIKE '%Woodhouse%'")->fetchColumn();
        if ($wood_spot === 0) {
            $next_num = (int)$pdo->query("SELECT MAX(spot_number) FROM `sanctuary_spots`")->fetchColumn() + 1;
            $ins = $pdo->prepare("INSERT INTO `sanctuary_spots` 
                (spot_number, title, subtitle_tag, category, is_stay, linked_room_slug, stay_price, elevation, temperature, description, aroma, sound, image_url, photos, cta_text, cta_link, x_coord, y_coord, display_order, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $ins->execute([
                $next_num,
                'The Alpine Woodhouse Chalet',
                'PINE TIMBER CHALET',
                'stays',
                1,
                'woodhouse',
                13500.00,
                '1,620M MSL',
                '18°C Pine Forest Air',
                'Handcrafted solid cedar and pinewood mountain chalet featuring aromatic wooden walls, high vaulted cathedral ceiling, valley-facing balcony deck, and private fire hearth.',
                'Pine needles, cedar resin & crisp mist',
                'Rustling pine branches, mountain breeze',
                'assets/images/01 (25).jpeg',
                json_encode(['assets/images/01 (25).jpeg', 'assets/images/01 (26).jpeg', 'assets/images/treehouse_stone_fireplace.png']),
                'Book Woodhouse',
                'booking.php?villa=woodhouse',
                74.00,
                34.00,
                $next_num
            ]);
        }
    }

    $checked = true;
}

/**
 * Retrieve active or all sanctuary spots ordered by spot_number / display_order
 */
function get_all_sanctuary_spots($only_active = false) {
    try {
        $pdo = get_db();
        ensure_sanctuary_spots_table_exists($pdo);
        $sql = "SELECT s.*, r.rate_per_night AS room_rate, r.single_room_rate, COALESCE(s.structure_type, r.structure_type, 'single_hut') AS structure_type, r.max_guests AS room_max_guests, r.base_guests AS room_base_guests 
                FROM `sanctuary_spots` s
                LEFT JOIN `rooms` r ON s.linked_room_slug = r.slug";
        if ($only_active) {
            $sql .= " WHERE s.is_active = 1";
        }
        $sql .= " ORDER BY s.spot_number ASC, s.display_order ASC, s.id ASC";
        $spots = $pdo->query($sql)->fetchAll();
        foreach ($spots as &$sp) {
            $photos_arr = [];
            if (!empty($sp['photos'])) {
                $dec = json_decode($sp['photos'], true);
                if (is_array($dec)) {
                    $photos_arr = $dec;
                }
            }
            if (empty($photos_arr) && !empty($sp['image_url'])) {
                $photos_arr = [$sp['image_url']];
            }
            $sp['photos_list'] = $photos_arr;
        }
        return $spots;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Ensure in-depth specification and multi-photo gallery columns exist in experiences table.
 * Seeds rich default content if records exist but are empty.
 */
function ensure_experiences_details_columns(PDO $pdo) {
    static $checked = false;
    if ($checked) return;

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `experiences`")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('tagline', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `tagline` VARCHAR(255) NULL AFTER `title`");
        }
        if (!in_array('detailed_description', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `detailed_description` TEXT NULL AFTER `description`");
        }
        if (!in_array('highlights', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `highlights` TEXT NULL AFTER `detailed_description`");
        }
        if (!in_array('inclusions', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `inclusions` TEXT NULL AFTER `highlights`");
        }
        if (!in_array('schedule_info', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `schedule_info` VARCHAR(255) NULL AFTER `timing`");
        }
        if (!in_array('location_info', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `location_info` VARCHAR(255) NULL AFTER `schedule_info`");
        }
        if (!in_array('suitable_for', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `suitable_for` VARCHAR(255) NULL AFTER `location_info`");
        }
        if (!in_array('what_to_bring', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `what_to_bring` TEXT NULL AFTER `suitable_for`");
        }
        if (!in_array('gallery_images', $cols)) {
            $pdo->exec("ALTER TABLE `experiences` ADD COLUMN `gallery_images` TEXT NULL AFTER `image_url`");
        }

        // Seed initial data for ID 1, 2, 3, 4 if tagline is empty
        $exp1_tagline = $pdo->query("SELECT tagline FROM `experiences` WHERE id = 1")->fetchColumn();
        if (empty($exp1_tagline)) {
            $pdo->prepare("UPDATE `experiences` SET 
                tagline = ?,
                detailed_description = ?,
                highlights = ?,
                inclusions = ?,
                schedule_info = ?,
                location_info = ?,
                suitable_for = ?,
                what_to_bring = ?,
                gallery_images = ?
                WHERE id = 1")->execute([
                    'Hand-pluck crisp mountain fruits in certified organic permaculture orchards.',
                    'Wander through our terraced mountain orchards accompanied by resident botanists and native horticulturists. Stroll through mist-kissed rows of heirloom apples, wild blackberries, passion fruit trellises, and sweet tamarillo (tree tomato) groves. Learn the ancestral principles of multi-tiered food forestry, regenerative compost cycles, and natural pest balancing without a single drop of synthetic chemicals. Pluck sun-ripened fruits right off the branch and savor freshly pressed organic juice prepared on-site.',
                    json_encode([
                        'Guided walk led by resident botanist & indigenous farmers',
                        'Hand-pluck seasonal heirloom apples, blackberries & tree tomatoes',
                        'Multi-layer permaculture & living soil biodiversity demo',
                        'Freshly pressed organic orchard juice & fruit tasting session'
                    ]),
                    'Handwoven harvest wicker basket, guided botanical notes, organic fruit tasting, freshly pressed estate juice.',
                    'Daily at 07:30 AM & 10:30 AM (Duration: 2 Hours)',
                    'Terraced Mountain Orchards & Botanical Nursery',
                    'Couples, Families, and Nature Enthusiasts of all ages',
                    'Comfortable walking shoes, sun hat, and a light jacket for morning mist.',
                    json_encode([
                        'assets/images/01 (18).jpeg',
                        'assets/images/01 (19).jpeg',
                        'assets/images/01 (20).jpeg',
                        'assets/images/01 (27).jpeg'
                    ])
                ]);

            $pdo->prepare("UPDATE `experiences` SET 
                tagline = ?,
                detailed_description = ?,
                highlights = ?,
                inclusions = ?,
                schedule_info = ?,
                location_info = ?,
                suitable_for = ?,
                what_to_bring = ?,
                gallery_images = ?
                WHERE id = 2")->execute([
                    'Twilight slate stone fires, hot cardamom brews, and ancestral mountain folklore.',
                    'As dusk blankets the Western Ghats with deep indigo mist and the high-range night turns crisp and cold, gather around our open slate stone fire pit beneath an unpolluted Milky Way sky. Warm your hands against dancing flames of teak embers and breathe in the rich aroma of mountain wood smoke. Sip piping hot cardamom and crushed ginger spiced mountain tea, enjoy wood-roasted sweet farm corn sprinkled with Marayoor sea salt, and listen to timeless legends of Kanthalloor’s ancient megalithic dolmens and tribal mountain lore told by indigenous elders.',
                    json_encode([
                        'Open slate hearth campfire beneath dark celestial skies',
                        'Steaming Marayoor cardamom-ginger spiced brew & roasted farm corn',
                        'Folk stories of tribal ancestors and high-range wildlife legends',
                        'Acoustic native music and tranquil meditation by the embers'
                    ]),
                    'Unlimited cardamom spiced farm tea, fire-roasted sweet corn, handwoven wool shawls for the mountain chill.',
                    'Every Evening • 07:00 PM to 09:30 PM',
                    'Central Amphitheater & Stone Hearth Courtyard',
                    'All residing guests seeking cozy evening tranquility',
                    'Warm jacket or fleece sweater, camera for starry night photography.',
                    json_encode([
                        'assets/images/01 (30).jpeg',
                        'assets/images/01 (31).jpeg',
                        'assets/images/01 (32).jpeg',
                        'assets/images/01 (7).jpeg'
                    ])
                ]);

            $pdo->prepare("UPDATE `experiences` SET 
                tagline = ?,
                detailed_description = ?,
                highlights = ?,
                inclusions = ?,
                schedule_info = ?,
                location_info = ?,
                suitable_for = ?,
                what_to_bring = ?,
                gallery_images = ?
                WHERE id = 3")->execute([
                    'Ground your hands in red earth, vetiver straw, and ancestral thermal architecture.',
                    'Connect deeply with the living earth beneath your feet. In this deeply tactile and grounding workshop, our master vernacular builders introduce you to the timeless art of earthen cob construction. Discover how native red earth, fine river sand, chopped vetiver grass, and slaked lime create breathable, thermally stable walls that keep interiors cool by day and cozy through chilly mountain nights. Knead the clay mix, sculpt miniature wall alcoves, and try your hand at smooth terracotta plastering using traditional wooden floats.',
                    json_encode([
                        'Hands-on mixing of native red clay, lime plaster, and vetiver straw',
                        'Understanding thermal physics and breathable zero-carbon design',
                        'Sculpting earthen wall niches, decorative reliefs & pottery forms',
                        'Mentored by veteran native cob and thatch craftsmen'
                    ]),
                    'Natural clay sculpting materials, protective studio aprons, traditional herbal tea and farm refreshment.',
                    'Tuesdays, Thursdays & Saturdays • 02:30 PM to 05:00 PM',
                    'The Artisan Cob Studio & Clay Courtyard',
                    'Adults, architecture buffs, curious creative souls, and kids',
                    'Comfortable clothes you do not mind getting clay on, slip-on shoes.',
                    json_encode([
                        'assets/images/01 (6).jpeg',
                        'assets/images/01 (1).jpeg',
                        'assets/images/01 (17).jpeg',
                        'assets/images/01 (14).jpeg'
                    ])
                ]);

            $pdo->prepare("UPDATE `experiences` SET 
                tagline = ?,
                detailed_description = ?,
                highlights = ?,
                inclusions = ?,
                schedule_info = ?,
                location_info = ?,
                suitable_for = ?,
                what_to_bring = ?,
                gallery_images = ?
                WHERE id = 4")->execute([
                    'Ascend misty high-range ridges to witness golden dawn across the Anaimudi peaks.',
                    'Begin before first light, ascending along ancient forest trails through fragrant wild lemongrass meadows, private sandalwood groves, and emerald tea estate fringes. Reach the panoramic ridge just as the first amber rays ignite the mist rolling off the Anaimudi peak range and the expansive Marayoor valley below. Enjoy freshly steeped estate black tea poured from thermos flasks with hot organic harvest pastries atop the cliff while spotting rare high-altitude birds such as the Nilgiri Pipit and Malabar Whistling Thrush.',
                    json_encode([
                        'Guided 5km sunrise trek through sandalwood & tea estate frontiers',
                        'Breathtaking 360-degree dawn panorama across the Western Ghats',
                        'Cliffside tea ceremony with freshly steeped high-altitude black tea',
                        'Birdwatching & wildlife tracking with our native naturalist'
                    ]),
                    'Hand-carved wooden trekking pole, thermos mountain tea, organic fruit & nut energy packs, binoculars.',
                    'Daily Departure at 05:45 AM Sharp (Duration: 3.5 Hours)',
                    'Departs from Sanctuary Welcome Lounge',
                    'Guests with moderate fitness levels (beginner-to-intermediate trail)',
                    'Sturdy walking / hiking footwear, windbreaker or jacket, reusable water flask.',
                    json_encode([
                        'assets/images/01 (33).jpeg',
                        'assets/images/01 (34).jpeg',
                        'assets/images/01 (35).jpeg',
                        'assets/images/01 (28).jpeg'
                    ])
                ]);
        }
    } catch (Exception $e) {
        // Silently skip if DB error
    }

    $checked = true;
}

/**
 * Ensure the food_menu table exists, seed initial luxury farm dishes, and ensure images are in place
 */
function ensure_food_menu_table_exists(?PDO $pdo = null) {
    static $checked = false;
    if ($checked) return;

    if (!$pdo) {
        $pdo = get_db();
    }

    // 1. Create table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `food_menu` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category` VARCHAR(50) NOT NULL,
        `heading` VARCHAR(200) NOT NULL,
        `subtitle` VARCHAR(255) NULL,
        `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `price_note` VARCHAR(100) NULL DEFAULT 'Per Set',
        `description` TEXT NULL,
        `inclusions` TEXT NULL,
        `dietary_type` VARCHAR(50) DEFAULT 'veg',
        `badge` VARCHAR(100) DEFAULT 'Farm Fresh',
        `image_url` VARCHAR(255) NULL,
        `gallery_images` TEXT NULL,
        `display_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Copy generated images if available
    $images_dir = __DIR__ . '/../../assets/images';
    if (!file_exists($images_dir . '/food_dosa_set.jpg')) {
        $gen1 = 'C:/Users/sojin/.gemini/antigravity-ide/brain/2d7eb0fb-9e13-4bb7-86a4-b64ac7ed8918/food_dosa_set_1789538856869.jpg';
        if (file_exists($gen1)) {
            @copy($gen1, $images_dir . '/food_dosa_set.jpg');
        }
    }
    if (!file_exists($images_dir . '/food_kerala_sadya.jpg')) {
        $gen2 = 'C:/Users/sojin/.gemini/antigravity-ide/brain/2d7eb0fb-9e13-4bb7-86a4-b64ac7ed8918/food_kerala_sadya_1789538876587.jpg';
        if (file_exists($gen2)) {
            @copy($gen2, $images_dir . '/food_kerala_sadya.jpg');
        }
    }
    if (!file_exists($images_dir . '/food_evening_snacks.jpg')) {
        $gen3 = 'C:/Users/sojin/.gemini/antigravity-ide/brain/2d7eb0fb-9e13-4bb7-86a4-b64ac7ed8918/food_evening_snacks_1789538895799.jpg';
        if (file_exists($gen3)) {
            @copy($gen3, $images_dir . '/food_evening_snacks.jpg');
        }
    }

    $dosa_img = file_exists($images_dir . '/food_dosa_set.jpg') ? 'assets/images/food_dosa_set.jpg' : 'assets/images/01 (10).jpeg';
    $sadya_img = file_exists($images_dir . '/food_kerala_sadya.jpg') ? 'assets/images/food_kerala_sadya.jpg' : 'assets/images/01 (3).jpeg';
    $snacks_img = file_exists($images_dir . '/food_evening_snacks.jpg') ? 'assets/images/food_evening_snacks.jpg' : 'assets/images/01 (19).jpeg';

    // 2. Seed initial dishes if empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `food_menu`")->fetchColumn();
    if ($count === 0) {
        $seed_items = [
            // Breakfast
            [
                'category' => 'breakfast',
                'heading' => 'Signature Heritage Dosa Set',
                'subtitle' => 'Crispy Ghee Dosas with 3 Stone-Ground Chutneys & Claypot Sambar',
                'price' => 220.00,
                'price_note' => 'Per Set • Farm Breakfast',
                'description' => 'Fermented batter of organic red rice and black lentils, ladled onto seasoned cast-iron skillets and crisped with pure A2 farm ghee. Served piping hot on a fresh plantain leaf with three distinctive regional chutneys.',
                'inclusions' => "3 Crispy Golden Ghee Dosas\nSpiced Potato Podi Masala\nFresh Coconut-Mint Chutney\nRoasted Tomato & Garlic Chutney\nShallot & Kanthari White Chutney\nPiping Hot Drumstick Sambar",
                'dietary_type' => 'veg',
                'badge' => 'ESTATE SIGNATURE',
                'image_url' => $dosa_img,
                'gallery_images' => json_encode([$dosa_img, 'assets/images/01 (10).jpeg', 'assets/images/01 (3).jpeg']),
                'display_order' => 1
            ],
            [
                'category' => 'breakfast',
                'heading' => 'Clay-Baked Appam & Vegetable Ishtu',
                'subtitle' => 'Lacy Fermented Rice Crepes with Farm Coconut Milk Stew',
                'price' => 240.00,
                'price_note' => 'Per Set',
                'description' => 'Soft, pillowy centers with delicate paper-thin crispy lace edges baked in traditional earthen appachattis. Accompanied by fragrant coconut milk stew simmered with heirloom potatoes, baby carrots, and sweet garden peas.',
                'inclusions' => "4 Lacy Steamed Appams\nFresh Coconut Milk Vegetable Ishtu\nRoasted Coconut Chammanthi\nCardamom Spiced Chai",
                'dietary_type' => 'veg',
                'badge' => 'VILLAGE CLASSIC',
                'image_url' => 'assets/images/01 (10).jpeg',
                'gallery_images' => json_encode(['assets/images/01 (10).jpeg', 'assets/images/01 (3).jpeg', $dosa_img]),
                'display_order' => 2
            ],
            [
                'category' => 'breakfast',
                'heading' => 'Heritage Red Rice Puttu & Kadala Curry',
                'subtitle' => 'Steamed Bamboo Puttu Cylinders with Spiced Black Chickpea Gravy',
                'price' => 210.00,
                'price_note' => 'Per Set',
                'description' => 'Coarsely ground organic red matta rice flour layered with freshly grated coconut and steamed in bamboo hollows. Paired with rich black chickpea gravy roasted in native coconut oil.',
                'inclusions' => "2 Bamboo Steamed Puttu Cylinders\nSlow-Braised Kadala Curry\nSmall Ripe Farm Banana\nPapadam & Ghee",
                'dietary_type' => 'veg',
                'badge' => 'ORGANIC GRAIN',
                'image_url' => 'assets/images/01 (1).jpeg',
                'gallery_images' => json_encode(['assets/images/01 (1).jpeg', 'assets/images/01 (10).jpeg']),
                'display_order' => 3
            ],
            // Lunch
            [
                'category' => 'lunch',
                'heading' => 'Kanthalloor Earthen Claypot Sadya',
                'subtitle' => 'Full Traditional Harvest Feast Served on Fresh Plantain Leaf',
                'price' => 480.00,
                'price_note' => 'Per Person Feast',
                'description' => 'A ceremonial organic banquet celebrating the biodiverse harvest of Kanthalloor. Every side is slow-simmered in native red clay pots over wood embers using cold-pressed coconut oil.',
                'inclusions' => "Steamed Organic Red Matta Rice\nTraditional Mixed Vegetable Avial\nFarm Greens & Coconut Thoran\nSlow-Simmered Drumstick Sambar\nCurd-Tempered Pulissery\nCrispy Mountain Banana Chips & Sarkara Varatti\nStone-Ground Ginger Pickle (Inji Curry)\nCrisp Urud Papadam\nRich Marayoor Jaggery & Rice Payasam",
                'dietary_type' => 'veg',
                'badge' => "CHEF'S HARVEST FEAST",
                'image_url' => $sadya_img,
                'gallery_images' => json_encode([$sadya_img, 'assets/images/01 (3).jpeg', 'assets/images/01 (13).jpeg']),
                'display_order' => 1
            ],
            [
                'category' => 'lunch',
                'heading' => 'Woodfire Jackfruit & Lentil Curry Set',
                'subtitle' => 'Tender Raw Chakka Braised in Roasted Coconut & Mountain Spices',
                'price' => 360.00,
                'price_note' => 'Per Set',
                'description' => 'Tender heirloom jackfruit harvested from century-old trees on the sanctuary slopes, braised slowly with toasted shallots, coriander, and freshly grated coconut.',
                'inclusions' => "Tender Jackfruit Varutharacha Curry\nFragrant Jeera Samba Rice\nRaw Banana Podimas\nSun-Dried Chili Buttermilk (Moru)",
                'dietary_type' => 'veg',
                'badge' => 'HEIRLOOM FORAGED',
                'image_url' => 'assets/images/01 (3).jpeg',
                'gallery_images' => json_encode(['assets/images/01 (3).jpeg', $sadya_img]),
                'display_order' => 2
            ],
            // Snacks
            [
                'category' => 'snacks',
                'heading' => 'High-Range Evening Chai & Farm Fritters Set',
                'subtitle' => 'Piping Hot Marayoor Cardamom Chai with Sweet & Savory Estate Bites',
                'price' => 160.00,
                'price_note' => 'Tea & Bites Set',
                'description' => 'Gather on the veranda as the afternoon mist rolls into the valley. Enjoy steaming cardamom milk tea poured from brass tumblers, alongside golden nendran banana fritters and crisp lentil vadas.',
                'inclusions' => "Steaming Marayoor Cardamom Spiced Milk Tea\n2 Golden Pazham Pori (Crispy Banana Fritters)\n2 Crispy Medu Uzhunnu Vada\nFresh Coconut & Green Chili Chutney",
                'dietary_type' => 'veg',
                'badge' => 'TWILIGHT RITUAL',
                'image_url' => $snacks_img,
                'gallery_images' => json_encode([$snacks_img, 'assets/images/01 (20).jpeg', 'assets/images/01 (30).jpeg']),
                'display_order' => 1
            ],
            [
                'category' => 'snacks',
                'heading' => 'Steamed Sweet Ela Ada & Herbal Infusion',
                'subtitle' => 'Banana Leaf Steamed Rice Parcels Stuffed with Marayoor Jaggery',
                'price' => 180.00,
                'price_note' => 'Per Set',
                'description' => 'Delicate thin rice dough pockets filled with a rich filling of freshly grated organic coconut, crushed cardamom, and pure GI-tagged Marayoor dark molasses jaggery, wrapped in fragrant banana leaves and steamed.',
                'inclusions' => "2 Warm Banana-Leaf Steamed Ela Ada\nHot Ginger & Lemongrass Infusion\nRoasted Salted Cashews",
                'dietary_type' => 'veg',
                'badge' => 'TRADITIONAL DELICACY',
                'image_url' => 'assets/images/01 (19).jpeg',
                'gallery_images' => json_encode(['assets/images/01 (19).jpeg', $snacks_img]),
                'display_order' => 2
            ],
            // Dinner
            [
                'category' => 'dinner',
                'heading' => 'Twilight Hearth Stew & Malabar Porotta Set',
                'subtitle' => 'Aromatic Farm Vegetable Stew with Flaky Layered Hearth Breads',
                'price' => 390.00,
                'price_note' => 'Per Set',
                'description' => 'Served beside the crackling embers of the open hearth. Layered artisanal porottas or soft rice pathiri served with slow-cooked vegetable and wild mushroom stew scented with whole cinnamon and crushed black pepper.',
                'inclusions' => "3 Golden Layered Artisanal Porottas\nEarthen Pot Coconut-Vegetable Stew\nGrilled Forest Mushroom Kurma\nCaramelized Onion & Tomato Relish",
                'dietary_type' => 'veg',
                'badge' => 'CAMPFIRE SPECIAL',
                'image_url' => 'assets/images/01 (31).jpeg',
                'gallery_images' => json_encode(['assets/images/01 (31).jpeg', 'assets/images/01 (30).jpeg', 'assets/images/01 (3).jpeg']),
                'display_order' => 1
            ],
            [
                'category' => 'dinner',
                'heading' => 'Charcoal Roasted Mountain Root Platter',
                'subtitle' => 'Blistered Sweet Potatoes, Tapioca & Sweet Corn with Kanthari Dip',
                'price' => 320.00,
                'price_note' => 'Sharing Platter',
                'description' => 'Wholesome high-altitude root vegetables and tender corn on the cob roasted over aromatic teakwood charcoal. Served with stone-crushed bird\'s eye chili and raw shallot chutney.',
                'inclusions' => "Woodfire Blistered Farm Tapioca (Kappa)\nRoasted Sweet Mountain Potatoes\nCharred Sweet Corn Cobs with Rock Salt & Lime\nStone-Ground Kanthari Chili & Virgin Coconut Oil Dip",
                'dietary_type' => 'veg',
                'badge' => 'WOODFIRE ROAST',
                'image_url' => 'assets/images/01 (25).jpeg',
                'gallery_images' => json_encode(['assets/images/01 (25).jpeg', 'assets/images/01 (20).jpeg']),
                'display_order' => 2
            ]
        ];

        $ins = $pdo->prepare("INSERT INTO `food_menu` 
            (category, heading, subtitle, price, price_note, description, inclusions, dietary_type, badge, image_url, gallery_images, display_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        foreach ($seed_items as $item) {
            $ins->execute([
                $item['category'],
                $item['heading'],
                $item['subtitle'],
                $item['price'],
                $item['price_note'],
                $item['description'],
                $item['inclusions'],
                $item['dietary_type'],
                $item['badge'],
                $item['image_url'],
                $item['gallery_images'],
                $item['display_order']
            ]);
        }
    }

    // 3. Seed default menu settings
    $default_settings = [
        'menu_badge' => 'ESTATE GASTRONOMY & ORGANIC DINING',
        'menu_title' => 'The Forest Hearth & Living Menu',
        'menu_desc' => 'Food at Food Forest is a ritual. Cooked in indigenous clay pots over aromatic wood hearths, every meal is prepared with ingredients harvested minutes prior from our own organic soil.',
        'menu_time_breakfast' => '07:30 AM — 10:00 AM',
        'menu_desc_breakfast' => 'Morning in the Orchards • Fresh farm juices, lacy hoppers & stone-ground breakfast sets',
        'menu_time_lunch' => '12:30 PM — 02:30 PM',
        'menu_desc_lunch' => 'Claypot Hearth Feast • Heirloom red rice, seasonal thorans & traditional banana-leaf sadya',
        'menu_time_snacks' => '04:30 PM — 06:30 PM',
        'menu_desc_snacks' => 'Plantation Tea Ritual • Steaming Marayoor cardamom chai, hot banana fritters & steamed ela ada',
        'menu_time_dinner' => '07:30 PM — 10:00 PM',
        'menu_desc_dinner' => 'Twilight Campfire Dining • Slow-simmered stews, charcoal grills & jaggery desserts by the embers'
    ];

    $ins_set = $pdo->prepare("INSERT IGNORE INTO `settings` (setting_key, setting_value) VALUES (?, ?)");
    foreach ($default_settings as $k => $v) {
        $ins_set->execute([$k, $v]);
    }

    // Ensure latest image paths for signature items
    $pdo->exec("UPDATE `food_menu` SET `image_url` = 'assets/images/food_dosa_set.jpg' WHERE `heading` LIKE '%Dosa%' AND (`image_url` IS NULL OR `image_url` NOT LIKE '%food_dosa_set%')");
    $pdo->exec("UPDATE `food_menu` SET `image_url` = 'assets/images/food_kerala_sadya.jpg' WHERE `heading` LIKE '%Sadya%' AND (`image_url` IS NULL OR `image_url` NOT LIKE '%food_kerala_sadya%')");
    $pdo->exec("UPDATE `food_menu` SET `image_url` = 'assets/images/food_evening_snacks.jpg' WHERE `heading` LIKE '%Chai%' AND (`image_url` IS NULL OR `image_url` NOT LIKE '%food_evening_snacks%')");

    $checked = true;
}

/**
 * Retrieve active or filtered food menu items
 */
function get_food_menu_items($category = null, $only_active = true) {
    try {
        $pdo = get_db();
        ensure_food_menu_table_exists($pdo);
        $sql = "SELECT * FROM `food_menu` WHERE 1=1";
        $params = [];
        if ($only_active) {
            $sql .= " AND is_active = 1";
        }
        if ($category && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = strtolower(trim($category));
        }
        $sql .= " ORDER BY display_order ASC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            // Process inclusions into an array of clean items
            $inc_list = [];
            if (!empty($item['inclusions'])) {
                $lines = preg_split('/[\r\n]+/', $item['inclusions']);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $inc_list[] = ltrim($trimmed, "-*• \t");
                    }
                }
            }
            $item['inclusions_list'] = $inc_list;

            // Process gallery images for slider
            $gal_list = [];
            if (!empty($item['gallery_images'])) {
                $dec = json_decode($item['gallery_images'], true);
                if (is_array($dec)) {
                    $gal_list = array_values(array_filter($dec));
                }
            }
            if (empty($gal_list) && !empty($item['image_url'])) {
                $gal_list = [$item['image_url']];
            }
            $item['gallery_list'] = $gal_list;
        }
        return $items;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Ensure users table exists and bookings table has client/guest authentication and food columns
 */
function ensure_users_and_guest_columns(?PDO $pdo = null) {
    static $checked = false;
    if ($checked) return;

    if (!$pdo) {
        $pdo = get_db();
    }

    try {
        // 1. Ensure users table exists for permanent client logins
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `full_name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) UNIQUE NOT NULL,
            `phone` VARCHAR(50) NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `last_login` DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Ensure columns exist in bookings table
        $cols = $pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('user_id', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `user_id` INT NULL AFTER `id`");
        }
        if (!in_array('is_guest', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `is_guest` TINYINT(1) DEFAULT 1 AFTER `user_id`");
        }
        if (!in_array('guest_access_token', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `guest_access_token` VARCHAR(100) NULL AFTER `is_guest`");
        }
        if (!in_array('expires_at', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `expires_at` DATETIME NULL AFTER `guest_access_token`");
        }
        if (!in_array('food_items', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `food_items` LONGTEXT NULL AFTER `addons`");
        }
        if (!in_array('food_amount', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `food_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `food_items`");
        }
        if (!in_array('room_amount', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `room_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `food_amount`");
        }
        if (!in_array('food_status', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `food_status` VARCHAR(50) DEFAULT 'none' AFTER `food_amount`");
        }

        $checked = true;
    } catch (Exception $e) {
        // Silently skip if DB error
    }
}

/**
 * Register a permanent client account
 */
function register_client_user($full_name, $email, $phone, $password) {
    try {
        $pdo = get_db();
        ensure_users_and_guest_columns($pdo);

        $email = strtolower(trim($email));
        $full_name = trim($full_name);
        $phone = trim($phone);

        if (empty($full_name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Full name, valid email, and password are required.'];
        }

        // Check duplicate email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn()) {
            return ['success' => false, 'message' => 'An account with this email already exists. Please sign in instead.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, last_login) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$full_name, $email, $phone, $hash]);
        $user_id = (int)$pdo->lastInsertId();

        return [
            'success' => true,
            'user' => [
                'id' => $user_id,
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Registration error: ' . $e->getMessage()];
    }
}

/**
 * Authenticate a permanent client account
 */
function authenticate_client_user($email, $password) {
    try {
        $pdo = get_db();
        ensure_users_and_guest_columns($pdo);

        $email = strtolower(trim($email));
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'No account found with this email address.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Incorrect password. Please verify and try again.'];
        }

        // Update last login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        unset($user['password_hash']);
        return ['success' => true, 'user' => $user];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Authentication error: ' . $e->getMessage()];
    }
}

/**
 * Authenticate a 30-day guest booking login
 */
function authenticate_guest_booking($ref_code, $passcode_or_phone) {
    try {
        $pdo = get_db();
        ensure_users_and_guest_columns($pdo);

        $ref_code = strtoupper(trim($ref_code));
        $passcode = trim($passcode_or_phone);

        $clean_phone = preg_replace('/[^0-9]/', '', $passcode);

        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE reference_code = ?");
        $stmt->execute([$ref_code]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            return ['success' => false, 'message' => 'Reservation reference code not found. Please check your booking details.'];
        }

        // Check passcode match (guest_access_token or matched phone number)
        $token_match = !empty($booking['guest_access_token']) && (strcasecmp($booking['guest_access_token'], $passcode) === 0);
        $phone_clean_db = preg_replace('/[^0-9]/', '', $booking['guest_phone'] ?? '');
        $phone_match = !empty($clean_phone) && strlen($clean_phone) >= 4 && str_ends_with($phone_clean_db, substr($clean_phone, -4));

        if (!$token_match && !$phone_match) {
            return ['success' => false, 'message' => 'Invalid passcode or registered phone number.'];
        }

        // Check 30-day auto-destruct expiration
        if (!empty($booking['expires_at'])) {
            $exp_time = strtotime($booking['expires_at']);
            if ($exp_time && $exp_time < time()) {
                return [
                    'success' => false,
                    'expired' => true,
                    'message' => 'This 30-day guest reservation pass has expired. Upgrade to a permanent account or contact our concierge.'
                ];
            }
        }

        return ['success' => true, 'booking' => $booking];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error accessing guest booking: ' . $e->getMessage()];
    }
}

/**
 * Retrieve all bookings for a registered client user
 */
function get_client_bookings($user_id) {
    try {
        $pdo = get_db();
        ensure_users_and_guest_columns($pdo);

        $stmt = $pdo->prepare("SELECT b.*, r.title AS room_title, r.image_url AS room_image, r.elevation AS room_elevation, r.stay_type AS room_stay_type
                               FROM bookings b 
                               LEFT JOIN rooms r ON b.villa_type = r.slug 
                               WHERE b.user_id = ? 
                               ORDER BY b.id DESC");
        $stmt->execute([(int)$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['food_items_list'] = !empty($r['food_items']) ? json_decode($r['food_items'], true) : [];
        }
        return $rows;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Retrieve a specific booking by reference code with room details
 */
function get_booking_by_ref($ref_code) {
    try {
        $pdo = get_db();
        ensure_users_and_guest_columns($pdo);

        $ref_code = strtoupper(trim($ref_code));
        $stmt = $pdo->prepare("SELECT b.*, r.title AS room_title, r.image_url AS room_image, r.elevation AS room_elevation, r.stay_type AS room_stay_type, r.description AS room_description
                               FROM bookings b 
                               LEFT JOIN rooms r ON b.villa_type = r.slug 
                               WHERE b.reference_code = ?");
        $stmt->execute([$ref_code]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            $booking['food_items_list'] = !empty($booking['food_items']) ? json_decode($booking['food_items'], true) : [];
        }
        return $booking;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Upgrade / convert a guest booking to a permanent user account
 */
function upgrade_guest_to_user($booking_ref, $password) {
    try {
        $pdo = get_db();
        ensure_users_and_guest_columns($pdo);

        $booking = get_booking_by_ref($booking_ref);
        if (!$booking) {
            return ['success' => false, 'message' => 'Reservation reference not found.'];
        }

        $email = strtolower(trim($booking['guest_email'] ?? ''));
        if (empty($email)) {
            return ['success' => false, 'message' => 'No email associated with this booking.'];
        }

        // Check if user already exists
        $user_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $user_check->execute([$email]);
        $user_id = $user_check->fetchColumn();

        if (!$user_id) {
            $reg = register_client_user($booking['guest_name'], $email, $booking['guest_phone'], $password);
            if (!$reg['success']) {
                return $reg;
            }
            $user_id = $reg['user']['id'];
        }

        // Link booking to user and remove guest expiration
        $pdo->prepare("UPDATE bookings SET user_id = ?, is_guest = 0, expires_at = NULL WHERE reference_code = ?")
            ->execute([$user_id, $booking['reference_code']]);

        return ['success' => true, 'user_id' => $user_id, 'message' => 'Reservation successfully linked to your permanent account!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Upgrade error: ' . $e->getMessage()];
    }
}

/**
 * Ensure iCal channel feeds table and booking source columns exist
 */
function ensure_ical_and_channel_schema($pdo) {
    try {
        // 1. Create room_ical_feeds table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `room_ical_feeds` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `room_slug` VARCHAR(100) NOT NULL,
            `channel_name` VARCHAR(100) NOT NULL,
            `feed_url` TEXT NOT NULL,
            `last_synced_at` DATETIME NULL,
            `sync_status` VARCHAR(50) DEFAULT 'pending',
            `sync_error` TEXT NULL,
            `sync_events_count` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Add columns to bookings table if missing
        $cols = $pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('booking_source', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `booking_source` VARCHAR(50) DEFAULT 'direct_website' AFTER `villa_type`");
        }
        if (!in_array('external_uid', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `external_uid` VARCHAR(255) NULL AFTER `booking_source`");
        }
        if (!in_array('sync_hash', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `sync_hash` VARCHAR(64) NULL AFTER `external_uid`");
        }
    } catch (Exception $e) {
        error_log('iCal Schema migration notice: ' . $e->getMessage());
    }
}

/**
 * Check if a room is available for given checkin/checkout dates
 * Returns true if available, false if overlapping reservation exists
 */
function check_room_availability($pdo, $room_slug, $checkin_date, $checkout_date, $exclude_booking_id = null) {
    try {
        ensure_ical_and_channel_schema($pdo);
        
        $sql = "SELECT id, reference_code, guest_name, checkin_date, checkout_date, booking_source 
                FROM bookings 
                WHERE villa_type = ? 
                  AND status NOT IN ('cancelled', 'rejected') 
                  AND (checkin_date < ? AND checkout_date > ?)";
        
        $params = [$room_slug, $checkout_date, $checkin_date];
        
        if (!empty($exclude_booking_id)) {
            $sql .= " AND id != ?";
            $params[] = (int)$exclude_booking_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $overlap = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $overlap ? false : true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all booked date ranges for a room (to disable in datepicker)
 */
function get_room_booked_ranges($pdo, $room_slug = null) {
    try {
        ensure_ical_and_channel_schema($pdo);
        
        $sql = "SELECT id, villa_type, checkin_date, checkout_date, status, booking_source, guest_name 
                FROM bookings 
                WHERE status NOT IN ('cancelled', 'rejected') 
                  AND checkout_date >= CURDATE()";
        
        $params = [];
        if (!empty($room_slug) && $room_slug !== 'all') {
            $sql .= " AND villa_type = ?";
            $params[] = $room_slug;
        }
        
        $sql .= " ORDER BY checkin_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Ensure billing columns exist in bookings table
 */
function ensure_billing_columns($pdo) {
    try {
        static $checked = false;
        if ($checked) return;

        $cols = $pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('advance_paid', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `advance_paid` DECIMAL(10,2) DEFAULT 0.00 AFTER `total_amount`");
        }
        if (!in_array('discount_amount', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `advance_paid`");
        }
        if (!in_array('tax_amount', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `tax_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_amount`");
        }
        if (!in_array('extra_charges', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `extra_charges` DECIMAL(10,2) DEFAULT 0.00 AFTER `tax_amount`");
        }
        if (!in_array('payment_method', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'unspecified' AFTER `extra_charges`");
        }
        if (!in_array('payment_status', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `payment_status` VARCHAR(50) DEFAULT 'unpaid' AFTER `payment_method`");
        }
        if (!in_array('activities_json', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `activities_json` LONGTEXT NULL AFTER `addons`");
        }
        if (!in_array('billing_items_json', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `billing_items_json` LONGTEXT NULL AFTER `activities_json`");
        }
        if (!in_array('billing_notes', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `billing_notes` TEXT NULL AFTER `special_notes`");
        }
        if (!in_array('checked_in_at', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `checked_in_at` DATETIME NULL");
        }
        if (!in_array('checked_out_at', $cols)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `checked_out_at` DATETIME NULL");
        }

        $checked = true;
    } catch (Exception $e) {
        error_log('Billing schema migration notice: ' . $e->getMessage());
    }
}

/**
 * Retrieve parsed and calculated billing structure for a booking
 */
function get_booking_billing_details($pdo, $identifier) {
    try {
        ensure_billing_columns($pdo);
        ensure_users_and_guest_columns($pdo);

        if (is_numeric($identifier)) {
            $stmt = $pdo->prepare("SELECT b.*, r.title AS room_title, r.image_url AS room_image, r.elevation AS room_elevation, r.stay_type AS room_stay_type, r.rate_per_night AS room_rate, r.base_guests, r.extra_guest_rate, r.extra_child_rate 
                                   FROM bookings b 
                                   LEFT JOIN rooms r ON b.villa_type = r.slug 
                                   WHERE b.id = ?");
            $stmt->execute([(int)$identifier]);
        } else {
            $stmt = $pdo->prepare("SELECT b.*, r.title AS room_title, r.image_url AS room_image, r.elevation AS room_elevation, r.stay_type AS room_stay_type, r.rate_per_night AS room_rate, r.base_guests, r.extra_guest_rate, r.extra_child_rate 
                                   FROM bookings b 
                                   LEFT JOIN rooms r ON b.villa_type = r.slug 
                                   WHERE b.reference_code = ?");
            $stmt->execute([strtoupper(trim($identifier))]);
        }
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$b) return null;

        // Fallbacks for room metadata
        if (empty($b['room_title'])) {
            $b['room_title'] = ($b['villa_type'] === 'treehouse' ? 'The Canopy Treehouse' : ($b['villa_type'] === 'mudhouse' ? 'Traditional Earthen Mudhouse' : 'Luxury Forest Sanctuary'));
        }
        if (empty($b['room_image'])) {
            $b['room_image'] = ($b['villa_type'] === 'treehouse' ? 'assets/images/treehouse_exterior.png' : 'assets/images/mudhouse_exterior.png');
        }

        // Room calculation
        $nights = max(1, (int)($b['nights'] ?? 1));
        $rate_per_night = (float)($b['room_rate'] ?? 14500);
        $adults_count = max(1, (int)($b['adults_count'] ?? 2));
        $kids_count = max(0, (int)($b['kids_count'] ?? 0));
        $base_guests = max(1, (int)($b['base_guests'] ?? 2));
        $extra_adult_rate = (float)($b['extra_guest_rate'] ?? 1500);
        $extra_child_rate = (float)($b['extra_child_rate'] ?? 800);

        $adults_in_base = min($adults_count, $base_guests);
        $extra_adults = max(0, $adults_count - $adults_in_base);
        $rem_base = max(0, $base_guests - $adults_in_base);
        $kids_in_base = min($kids_count, $rem_base);
        $extra_kids = max(0, $kids_count - $kids_in_base);

        $room_base_total = $rate_per_night * $nights;
        $extra_guest_total = ($extra_adults * $extra_adult_rate * $nights) + ($extra_kids * $extra_child_rate * $nights);
        $calculated_room_amount = $room_base_total + $extra_guest_total;

        $room_amount = (float)($b['room_amount'] > 0 ? $b['room_amount'] : $calculated_room_amount);

        // Parse Food Items (Gastronomy)
        $food_items = !empty($b['food_items']) ? json_decode($b['food_items'], true) : [];
        if (!is_array($food_items)) $food_items = [];
        $food_total = 0.00;
        foreach ($food_items as $fi) {
            $qty = max(1, (int)($fi['quantity'] ?? 1));
            $pr = (float)($fi['price'] ?? 0);
            $sub = (float)($fi['subtotal'] ?? ($qty * $pr));
            $food_total += $sub;
        }

        // Parse Activities & Experiences
        $activities = !empty($b['activities_json']) ? json_decode($b['activities_json'], true) : [];
        if (!is_array($activities)) $activities = [];

        // If activities_json is empty but addons text has content, parse addons text
        if (empty($activities) && !empty($b['addons']) && strtolower(trim($b['addons'])) !== 'none') {
            // Split by comma ONLY when outside parentheses (so numbers like 1,500 inside (+₹1,500) don't get split)
            $parts = preg_split('/,(?![^(]*\))/', $b['addons']);
            if (!$parts) {
                $parts = [$b['addons']];
            }
            foreach ($parts as $p) {
                $p = trim($p);
                if (empty($p)) continue;
                $price = 0;
                if (preg_match('/\(\s*\+?\s*[^0-9\(\)]*?([\d,]+)\s*\)/u', $p, $m)) {
                    $price = (float)str_replace(',', '', $m[1]);
                } elseif (preg_match('/(?:\+₹|\+\s*₹|\+INR|\+Rs\.?|\+|\?+)\s*([\d,]+)/i', $p, $m)) {
                    $price = (float)str_replace(',', '', $m[1]);
                }
                
                $title = preg_replace('/\s*\([^)]*\)/', '', $p);
                $title = preg_replace('/[?]+/', '', $title);
                $title = trim($title, " \t\n\r\0\x0B,+");

                if (!empty($title)) {
                    $activities[] = [
                        'title' => $title,
                        'timing' => 'Curated Experience',
                        'quantity' => 1,
                        'price' => $price,
                        'subtotal' => $price
                    ];
                }
            }
        }
        $activities_total = 0.00;
        foreach ($activities as $act) {
            $qty = max(1, (int)($act['quantity'] ?? 1));
            $pr = (float)($act['price'] ?? 0);
            $sub = (float)($act['subtotal'] ?? ($qty * $pr));
            $activities_total += $sub;
        }

        // Custom Billing Items (Services, Misc)
        $custom_items = !empty($b['billing_items_json']) ? json_decode($b['billing_items_json'], true) : [];
        if (!is_array($custom_items)) $custom_items = [];
        $custom_total = 0.00;
        foreach ($custom_items as $ci) {
            $qty = max(1, (int)($ci['quantity'] ?? 1));
            $pr = (float)($ci['price'] ?? 0);
            $sub = (float)($ci['subtotal'] ?? ($qty * $pr));
            $custom_total += $sub;
        }

        $extra_charges = (float)($b['extra_charges'] ?? 0);
        $discount_amount = (float)($b['discount_amount'] ?? 0);
        $advance_paid = (float)($b['advance_paid'] ?? 0);
        $tax_amount = (float)($b['tax_amount'] ?? 0);

        // Subtotal
        $gross_total = $room_amount + $food_total + $activities_total + $custom_total + $extra_charges;
        $net_total = max(0, $gross_total + $tax_amount - $discount_amount);
        
        // Balance Due
        $balance_due = max(0, $net_total - $advance_paid);

        $payment_status = $b['payment_status'] ?? 'unpaid';
        if ($balance_due <= 0 && $net_total > 0) {
            $payment_status = 'paid';
        } elseif ($advance_paid > 0 && $balance_due > 0) {
            $payment_status = 'partial';
        }

        $b['parsed'] = [
            'nights' => $nights,
            'rate_per_night' => $rate_per_night,
            'adults_count' => $adults_count,
            'kids_count' => $kids_count,
            'extra_adults' => $extra_adults,
            'extra_kids' => $extra_kids,
            'room_base_total' => $room_base_total,
            'extra_guest_total' => $extra_guest_total,
            'room_amount' => $room_amount,
            'food_items' => $food_items,
            'food_total' => $food_total,
            'activities' => $activities,
            'activities_total' => $activities_total,
            'custom_items' => $custom_items,
            'custom_total' => $custom_total,
            'extra_charges' => $extra_charges,
            'discount_amount' => $discount_amount,
            'tax_amount' => $tax_amount,
            'gross_total' => $gross_total,
            'net_total' => $net_total,
            'advance_paid' => $advance_paid,
            'balance_due' => $balance_due,
            'payment_status' => $payment_status,
            'payment_method' => $b['payment_method'] ?? 'unspecified'
        ];

        return $b;
    } catch (Exception $e) {
        return null;
    }
}
?>
