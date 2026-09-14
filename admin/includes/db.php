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
        ensure_sanctuary_spots_table_exists($pdo);

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
        $sql = "SELECT * FROM experiences WHERE is_active = 1 ORDER BY display_order ASC, id ASC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        return $pdo->query($sql)->fetchAll();
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
        `elevation` VARCHAR(100) DEFAULT '1,600M MSL',
        `temperature` VARCHAR(100) DEFAULT '18°C Alpine Breeze',
        `description` TEXT NOT NULL,
        `aroma` VARCHAR(150) NULL,
        `sound` VARCHAR(150) NULL,
        `image_url` VARCHAR(255) NOT NULL,
        `cta_text` VARCHAR(100) DEFAULT 'Explore Details',
        `cta_link` VARCHAR(255) DEFAULT '#rooms',
        `x_coord` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
        `y_coord` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
        `display_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM `sanctuary_spots`")->fetchColumn();
    if ($count === 0) {
        $spots = [
            [
                'spot_number' => 1,
                'title' => 'Farmhouse Kitchen & Organic Dining',
                'subtitle_tag' => 'COMMON KITCHEN & DINING',
                'category' => 'dining',
                'elevation' => '1,580M MSL',
                'temperature' => '19°C Warm Hearth',
                'description' => 'Central farm hearth serving 100% organic farm-to-table meals harvested daily from our heirloom orchards. Wood-fired open kitchen and mountain view dining.',
                'aroma' => 'Woodsmoke, cardamom & roasted spices',
                'sound' => 'Crackling hearth, laughter & tea kettle',
                'image_url' => 'assets/images/01 (10).jpeg',
                'cta_text' => 'Farmhouse Dining',
                'cta_link' => '#philosophy',
                'x_coord' => 19.00,
                'y_coord' => 56.00,
                'display_order' => 1
            ],
            [
                'spot_number' => 2,
                'title' => 'The Earthen Mudhouse Enclave',
                'subtitle_tag' => 'EARTHEN HERITAGE VILLA',
                'category' => 'stays',
                'elevation' => '1,600M MSL',
                'temperature' => '21°C Thermal Comfort',
                'description' => 'Handcrafted cob clay cottages sculpted from native red soil, river sand, and straw. Naturally insulated against chilly nights with a private plantation sit-out.',
                'aroma' => 'Sun-baked earth, vetiver & woodsmoke',
                'sound' => 'Crackling hearth embers, crickets',
                'image_url' => 'assets/images/mudhouse_exterior.png',
                'cta_text' => 'Reserve Mudhouse',
                'cta_link' => '#booking-modal',
                'x_coord' => 32.00,
                'y_coord' => 68.00,
                'display_order' => 2
            ],
            [
                'spot_number' => 3,
                'title' => 'High-Altitude Canopy Treehouse',
                'subtitle_tag' => 'HIGH CANOPY RETREAT',
                'category' => 'stays',
                'elevation' => '1,620M MSL',
                'temperature' => '17°C Alpine Breeze',
                'description' => 'Elevated living among towering mountain trees. Floor-to-ceiling panoramic glass windows looking out over cascading mist, apple terraces, and sunrise valleys.',
                'aroma' => 'Fresh cedarwood, wild jasmine & pine',
                'sound' => 'Wind through high canopies, bulbul calls',
                'image_url' => 'assets/images/treehouse_exterior.png',
                'cta_text' => 'Reserve Treehouse',
                'cta_link' => '#booking-modal',
                'x_coord' => 58.00,
                'y_coord' => 26.00,
                'display_order' => 3
            ],
            [
                'spot_number' => 4,
                'title' => 'Crystal Mountain Brook & Plunge Pool',
                'subtitle_tag' => 'FRESH SPRING PLUNGE POOL',
                'category' => 'amenities',
                'elevation' => '1,560M MSL',
                'temperature' => '15°C Spring Freshwater',
                'description' => 'Pristine mountain brook feeding into a natural granite plunge pool. Serene freshwater bathing and riverside meditation amidst lush shola ferns.',
                'aroma' => 'Fern leaves, damp river stones & mineral mist',
                'sound' => 'Melodic rushing stream, pebble resonance',
                'image_url' => 'assets/images/01 (28).jpeg',
                'cta_text' => 'Explore Waters',
                'cta_link' => '#experiences',
                'x_coord' => 48.00,
                'y_coord' => 44.00,
                'display_order' => 4
            ],
            [
                'spot_number' => 5,
                'title' => 'Campfire Glade & BBQ Grilling Shed',
                'subtitle_tag' => 'EVENING BBQ & STARGAZING',
                'category' => 'amenities',
                'elevation' => '1,640M MSL',
                'temperature' => '14°C Crisp Night Air',
                'description' => 'Covered rustic timber barbecue pavilion and open granite firepit. Guests gather here for evening grilling rituals and acoustic stargazing under Class-1 dark skies.',
                'aroma' => 'Ember woodsmoke, roasted pepper & eucalyptus',
                'sound' => 'Acoustic guitar, crackling embers, mountain breeze',
                'image_url' => 'assets/images/01 (25).jpeg',
                'cta_text' => 'Evening Rituals',
                'cta_link' => '#experiences',
                'x_coord' => 36.00,
                'y_coord' => 22.00,
                'display_order' => 5
            ],
            [
                'spot_number' => 6,
                'title' => "Children's Play Glade & Orchard Walk",
                'subtitle_tag' => 'RECREATION & HARVEST TRAILS',
                'category' => 'nature',
                'elevation' => '1,570M MSL',
                'temperature' => '18°C Mild Mountain Sun',
                'description' => "Terraced grassy lawn equipped with traditional wooden swings, outdoor play zones for kids, and walking trails weaving through fruit-bearing apple and plum trees.",
                'aroma' => 'Wild berries, sweet apple blossoms & clover',
                'sound' => "Songbirds, children's laughter, rustling leaves",
                'image_url' => 'assets/images/01 (19).jpeg',
                'cta_text' => 'Orchard Activities',
                'cta_link' => '#experiences',
                'x_coord' => 68.00,
                'y_coord' => 64.00,
                'display_order' => 6
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO `sanctuary_spots` 
            (spot_number, title, subtitle_tag, category, elevation, temperature, description, aroma, sound, image_url, cta_text, cta_link, x_coord, y_coord, display_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        foreach ($spots as $s) {
            $stmt->execute([
                $s['spot_number'],
                $s['title'],
                $s['subtitle_tag'],
                $s['category'],
                $s['elevation'],
                $s['temperature'],
                $s['description'],
                $s['aroma'],
                $s['sound'],
                $s['image_url'],
                $s['cta_text'],
                $s['cta_link'],
                $s['x_coord'],
                $s['y_coord'],
                $s['display_order']
            ]);
        }
    }

    // Ensure 'photos' column exists for multiple photos support
    $colCheck = $pdo->query("SHOW COLUMNS FROM `sanctuary_spots` LIKE 'photos'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE `sanctuary_spots` ADD `photos` TEXT NULL AFTER `image_url`");
    }

    // Backfill photos column if empty
    $needs_backfill = $pdo->query("SELECT COUNT(*) FROM `sanctuary_spots` WHERE photos IS NULL OR photos = ''")->fetchColumn();
    if ($needs_backfill > 0) {
        $sample_galleries = [
            1 => ['assets/images/01 (10).jpeg', 'assets/images/01 (20).jpeg', 'assets/images/01 (1).jpeg'],
            2 => ['assets/images/mudhouse_exterior.png', 'assets/images/01 (26).jpeg', 'assets/images/01 (14).jpeg'],
            3 => ['assets/images/treehouse_exterior.png', 'assets/images/treehouse_curved_window.png', 'assets/images/treehouse_timber_balcony.png'],
            4 => ['assets/images/01 (28).jpeg', 'assets/images/01 (3).jpeg', 'assets/images/01 (2).jpeg'],
            5 => ['assets/images/01 (25).jpeg', 'assets/images/treehouse_stone_fireplace.png', 'assets/images/01 (12).jpeg'],
            6 => ['assets/images/01 (19).jpeg', 'assets/images/01 (11).jpeg', 'assets/images/01 (7).jpeg']
        ];
        $all_s = $pdo->query("SELECT id, spot_number, image_url FROM `sanctuary_spots` WHERE photos IS NULL OR photos = ''")->fetchAll();
        $upd_p = $pdo->prepare("UPDATE `sanctuary_spots` SET photos = ? WHERE id = ?");
        foreach ($all_s as $row) {
            $s_num = (int)$row['spot_number'];
            $photos = $sample_galleries[$s_num] ?? [!empty($row['image_url']) ? $row['image_url'] : 'assets/images/01 (10).jpeg'];
            $upd_p->execute([json_encode($photos), $row['id']]);
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
        $sql = "SELECT * FROM `sanctuary_spots`";
        if ($only_active) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY spot_number ASC, display_order ASC, id ASC";
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
?>
