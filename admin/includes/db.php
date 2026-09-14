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
?>
