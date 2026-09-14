-- ========================================================
-- FOOD FOREST SANCTUARY DATABASE BACKUP
-- phpMyAdmin-Compatible Full MySQL Database Dump
-- Exported on: 2026-09-13 17:28:10
-- Database: `foodforest_db`
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `foodforest_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `foodforest_db`;

-- --------------------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'Concierge',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `admins` --
INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `last_login`, `created_at`) VALUES
('1', 'admin', '$2y$10$fDcVlo4l.D8M4ZSmI0ipXuzGatKcToTLu5YKIO.Yek4cRSVdaoC5u', 'Master Concierge', 'concierge@foodforestkanthalloor.com', 'General Manager', NULL, '2026-09-13 13:39:25');

-- --------------------------------------------------------
-- Table structure for table `bookings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_code` varchar(100) NOT NULL,
  `villa_type` varchar(100) NOT NULL,
  `guest_name` varchar(150) NOT NULL,
  `guest_phone` varchar(50) NOT NULL,
  `guest_email` varchar(150) DEFAULT NULL,
  `guests_count` int(11) DEFAULT 2,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `nights` int(11) DEFAULT 1,
  `addons` text DEFAULT NULL,
  `special_notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_code` (`reference_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `bookings` --
INSERT INTO `bookings` (`id`, `reference_code`, `villa_type`, `guest_name`, `guest_phone`, `guest_email`, `guests_count`, `checkin_date`, `checkout_date`, `nights`, `addons`, `special_notes`, `total_amount`, `status`, `created_at`) VALUES
('1', 'FF-8104', 'treehouse', 'Dr. Siddharth Menon', '+91 98450 12345', 'siddharth.menon@gmail.com', '2', '2026-09-16', '2026-09-18', '2', 'Candlelight Orchard Dinner (+₹3,000), Sunrise High-Peak Valley Trail (+₹2,000)', 'Honeymoon anniversary stay. Requesting fresh wildflower arrangement in room.', '34000.00', 'confirmed', '2026-09-13 13:39:25'),
('2', 'FF-8105', 'mudhouse', 'Aarav & Priya Varma', '+91 97401 88992', 'aarav.varma@outlook.com', '3', '2026-09-14', '2026-09-16', '2', 'Mud Pottery & Clay Workshop (+₹1,500)', 'Strictly vegan culinary requests. Arriving via private cab from Kochi.', '24500.00', 'pending', '2026-09-13 13:39:25'),
('3', 'FF-8098', 'treehouse', 'Meera Krishnan', '+91 94471 55667', 'meera.krishnan@yahoo.in', '2', '2026-09-20', '2026-09-21', '1', 'Candlelight Orchard Dinner (+₹3,000)', 'Late check-in requested around 6:00 PM due to ghat driving.', '17500.00', 'pending', '2026-09-13 13:39:25'),
('4', 'FF-8072', 'mudhouse', 'Rohan & Gayatri Kapoor', '+91 98200 44321', 'rohan.kapoor@innovate.co', '2', '2026-09-09', '2026-09-11', '2', 'Mud Pottery Workshop (+₹1,500), Sunrise Valley Trail (+₹2,000)', 'Loved the woodfire dinner experience.', '26500.00', 'completed', '2026-09-13 13:39:25'),
('5', 'FF-6172', 'treehouse', 'Aditi Sharma', '+91 99887 76655', 'aditi.sharma@example.com', '2', '2026-10-15', '2026-10-18', '3', 'Candlelight Orchard Dinner', 'Vegetarian, anniversary celebration.', '46500.00', 'pending', '2026-09-13 13:42:41');

-- --------------------------------------------------------
-- Table structure for table `experiences`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `experiences`;
CREATE TABLE `experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `badge` varchar(100) DEFAULT 'INCLUDED IN STAY',
  `timing` varchar(100) DEFAULT '2 Hours • Morning',
  `description` text NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `experiences` --
INSERT INTO `experiences` (`id`, `title`, `badge`, `timing`, `description`, `image_url`, `display_order`, `is_active`, `created_at`) VALUES
('1', 'Heirloom Orchard Harvest Walks', 'INCLUDED IN STAY', '2 Hours • Morning', 'Wander through terraced orchards accompanied by our resident botanist. Hand-pluck apples, blackberries, tree tomatoes, and learn organic permaculture.', 'assets/images/01 (18).jpeg', '1', '1', '2026-09-13 19:38:16'),
('2', 'Starlit Hearth & Folklore', 'EVENING RITUAL', 'Twilight — Night', 'Gather around an open slate fire beneath an unpolluted Milky Way sky. Unwind with hot cardamom spiced brews, roasted corn, and quiet acoustic music.', 'assets/images/01 (30).jpeg', '2', '1', '2026-09-13 19:38:16'),
('3', 'Vernacular Mud & Clay Workshops', 'WORKSHOP', '2.5 Hours • Afternoon', 'Get hands-on with native red earth. Learn the ancient alchemy of straw, clay, and terracotta to discover how homes can breathe without mechanical cooling.', 'assets/images/01 (6).jpeg', '3', '1', '2026-09-13 19:38:16'),
('4', 'Sunrise High-Ridge Valley Trek', 'ADVENTURE', '3.5 Hours • Sunrise', 'Ascend through sandalwood forests and misty tea fringes to witness the sunrise break across the Anaimudi peak range with fresh mountain tea.', 'assets/images/01 (33).jpeg', '4', '1', '2026-09-13 19:38:16');

-- --------------------------------------------------------
-- Table structure for table `gallery`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `caption` text DEFAULT NULL,
  `tag` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Landscape',
  `image_url` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `gallery` --
INSERT INTO `gallery` (`id`, `title`, `caption`, `tag`, `category`, `image_url`, `display_order`, `is_active`, `created_at`) VALUES
('1', 'High Canopy Treehouse in Mist', 'Perched 30 feet above the forest floor among silver oaks, overlooking rolling valley clouds.', 'CANOPY DWELLING · 1,640M', 'Villas & Stays', 'assets/images/treehouse_exterior.png', '1', '1', '2026-09-13 19:38:16'),
('2', 'Hand-Sculpted Cob Mudhouse', 'Naturally insulated clay and sand architecture with private herbal garden courtyards.', 'EARTHEN ARCHITECTURE', 'Handcrafted Living', 'assets/images/mudhouse_exterior.png', '2', '1', '2026-09-13 19:38:16'),
('3', 'The Western Ghats Vista', 'High-altitude horizon cloaked in shifting clouds and untouched shola wilderness.', 'ALPINE HORIZON', 'Landscape', 'assets/images/01 (25).jpeg', '3', '1', '2026-09-13 19:38:16'),
('4', 'Woodfire Claypot Lunch', 'Pure farm-to-table cooking over slow embers using hand-ground spices and organic produce.', 'EARTHEN GASTRONOMY', 'Gastronomy', 'assets/images/01 (3).jpeg', '4', '1', '2026-09-13 19:38:16'),
('5', 'Organic Winter Apple Orchards', 'Ancient heirloom trees yielding sweet, pesticide-free mountain apples each winter.', 'ESTATE HARVEST', 'Orchards', 'assets/images/01 (1).jpeg', '5', '1', '2026-09-13 19:38:16'),
('6', 'Stargazing by the Cob Hearth', 'Night skies at 1,600m altitude illuminated only by campfire crackle and constellations.', 'NIGHT SKY SANCTUARY', 'Nightscape', 'assets/images/01 (20).jpeg', '6', '1', '2026-09-13 19:38:16'),
('7', 'Morning Dew on Passion Fruit Vines', 'Wild pollinators and lush flora flourishing in our certified chemical-free sanctuary.', 'BOTANICAL HARMONY', 'Flora', 'assets/images/01 (15).jpeg', '7', '1', '2026-09-13 19:38:16'),
('8', 'Living Mud Courtyard Veranda', 'Unpaved, breathable courtyards connecting guest quarters directly with the soil.', 'BIOPHILIC SPACES', 'Architecture', 'assets/images/01 (7).jpeg', '8', '1', '2026-09-13 19:38:16');

-- --------------------------------------------------------
-- Table structure for table `inquiries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `inquiries`;
CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `inquiries` --
INSERT INTO `inquiries` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
('1', 'Kavita Sundaram', 'kavita.s@zenith.com', '+91 98840 99881', 'Exclusive Sanctuary Buyout for Wellness Retreat', 'Greetings. We are planning a 4-day private yoga and organic detox retreat for 8 guests in October. Is a full estate private buyout available?', 'unread', '2026-09-13 13:39:25'),
('2', 'George Matthew', 'george.matthew@gmail.com', '+91 97455 33221', 'EV Charging & Accessibility at Kanthalloor', 'Hello team, do you have EV charging facilities at the property, and is the access road suitable for sedans during light monsoon mist?', 'read', '2026-09-13 13:39:25');

-- --------------------------------------------------------
-- Table structure for table `rooms`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(150) NOT NULL,
  `rate_per_night` decimal(10,2) NOT NULL,
  `elevation` varchar(100) DEFAULT NULL,
  `max_guests` int(11) DEFAULT 2,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `rooms` --
INSERT INTO `rooms` (`id`, `slug`, `title`, `rate_per_night`, `elevation`, `max_guests`, `description`, `amenities`, `image_url`, `is_available`, `created_at`) VALUES
('1', 'treehouse', 'Luxury Canopy Treehouse', '14500.00', '30ft Elevated Canopy', '2', 'Suspended 30 feet above the forest floor within ancient trees. Crafted with wild teak timber, an open cantilevered deck, and expansive curved glass with panoramic mist views.', 'King Teak Bed, Panoramic Bay Window, Rain Mist Shower, Private Teak Balcony, Telescope, Complimentary Farm Meals, High Elevation Wi-Fi', '', '1', '2026-09-13 19:38:16'),
('2', 'mudhouse', 'Traditional Earthen Mudhouse', '11500.00', 'High-Altitude Orchard Ground', '4', 'Handcrafted using regenerative cob clay, lime plaster, and vetiver straw. Naturally climate-controlled interior keeping spaces cool in the day and warm through misty mountain nights.', 'Double Queen Beds, Clay Hearth Fireplace, Handcrafted Pottery Accents, Organic Herb Garden Veranda, Private Open-air Courtyard, Farm-to-table Dining', '', '1', '2026-09-13 19:38:16');

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(191) NOT NULL,
  `setting_value` mediumtext DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `settings` --
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('checkin_time', '02:00 PM'),
('checkout_time', '11:00 AM'),
('concierge_email', 'concierge@foodforestkanthalloor.com'),
('concierge_phone', '+91 92345 67890'),
('concierge_whatsapp', '919234567890'),
('currency_symbol', '₹'),
('estate_name', 'Food Forest Eco Sanctuary'),
('experiences_badge', 'CURATED JOURNEYS'),
('experiences_desc', 'Connect deeply with the pulse of Kanthalloor. Each experience is handcrafted to immerse you in vernacular craftsmanship, mountain wilderness, and restorative tranquility.'),
('experiences_title', 'Rituals of the High Range'),
('hero_bg_image', 'assets/images/01 (25).jpeg'),
('hero_desc', 'Tucked deep in the misty hills and organic orchards of Kanthalloor. Experience private earthen mudhouses, soaring canopy treehouses, and nourishing farm gastronomy cooked slowly over wood fires.'),
('hero_eyebrow', 'KANTHALLOOR, KERALA • PRIVATE ECO-SANCTUARY'),
('hero_title', 'Where Earth Breathes & Time Stands Still.'),
('location', 'Kanthalloor High Range, Idukki, Kerala - 685620'),
('seasons_badge', 'EVERY SEASON HAS A STORY'),
('seasons_desc', 'Kanthalloor shifts beautifully throughout the year. Each season paints our organic forest retreat in a completely distinct set of colors.'),
('seasons_title', 'Nature\'s Changing Canvas'),
('top_bar_accolade', 'Rated 4.98 / 5 • Top Sustainable Sanctuary 2026'),
('top_bar_location', 'Kanthalloor High Range • 1,600m Elevation • 18°C Misty Mountain Air'),
('welcome_badge', 'THE SANCTUARY PHILOSOPHY'),
('welcome_image', 'assets/images/01 (7).jpeg'),
('welcome_paragraph', 'Food Forest is not merely a getaway; it is a conscious return to living in harmony with nature. Tucked into the mist-veiled terraced hills of Kanthalloor, Kerala, our estate was conceived as a living ecosystem where luxury means silence, pure mountain spring water, and unhurried peace.'),
('welcome_title', 'Rooted in Earth, Reverence & Time'),
('why_badge', 'WHY FOOD FOREST?'),
('why_desc', 'At Food Forest Kanthalloor, every design detail is curated to offer comfort while leaving zero ecological footprint. As an organic farmstay in the high-altitude hills of Kerala, we offer two distinct living experiences: soaring high into the canopy in our Luxury Treehouse, or grounded in the ancient thermal cool of our Traditional Earthen Mudhouse.'),
('why_image', 'assets/images/01 (26).jpeg'),
('why_title', 'An Authentic Farmstay Sanctuary');

-- --------------------------------------------------------
-- Table structure for table `testimonials`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_name` varchar(150) NOT NULL,
  `guest_location` varchar(150) DEFAULT NULL,
  `stay_badge` varchar(100) DEFAULT 'CANOPY TREEHOUSE',
  `stars` int(11) DEFAULT 5,
  `quote` text NOT NULL,
  `initials` varchar(10) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `testimonials` --
INSERT INTO `testimonials` (`id`, `guest_name`, `guest_location`, `stay_badge`, `stars`, `quote`, `initials`, `display_order`, `is_active`, `created_at`) VALUES
('1', 'Ananya & Siddharth Nair', 'Kochi, India · Stayed Nov 2025', 'CANOPY TREEHOUSE', '5', 'The silence here is pure medicine. Waking up 30 feet above the valley floor to the mist drifting through our bedroom balcony was something we will carry in our hearts forever. The woodfire claypot lunch was unforgettable.', 'AN', '1', '1', '2026-09-13 19:38:16'),
('2', 'Dr. Julian & Clara Vance', 'Edinburgh, UK · Stayed Jan 2026', 'EARTHEN MUDHOUSE', '5', 'As architects passionate about sustainable living, the cob mudhouse blew us away. The natural indoor temperature stayed delightfully cool despite the midday sun. Stargazing by the open hearth was unmatched.', 'JV', '2', '1', '2026-09-13 19:38:16'),
('3', 'Meera Krishnan', 'Bengaluru, India · Stayed Feb 2026', 'SOLO RETREAT', '5', 'I came seeking refuge from city noise and found complete sanctuary. The sound of the mountain brook, the aroma of crushed wild rosemary on the morning trails, and the warmth of the hosts made me extend my stay by four days.', 'MK', '3', '1', '2026-09-13 19:38:16'),
('4', 'Vikramaditya & Rohini Sen', 'New Delhi, India · Stayed Dec 2025', 'ORCHARD HARVEST STAY', '5', 'Our children had never plucked apples and passionfruit directly from trees before. Eating ripe fruit straight from the branch while watching the clouds roll into the valley was the highlight of our year.', 'VS', '4', '1', '2026-09-13 19:38:16');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
