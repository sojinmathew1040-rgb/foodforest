-- =========================================================================
-- Food Forest Sanctuary — Automated Database Backup
-- Generated at: 2026-09-16 09:25:21
-- Database: foodforest_db
-- =========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- -------------------------------------------------------------
-- Table structure for `admins`
-- -------------------------------------------------------------
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

-- Dumping data for table `admins` (1 records)
INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$fDcVlo4l.D8M4ZSmI0ipXuzGatKcToTLu5YKIO.Yek4cRSVdaoC5u', 'Master Concierge', 'concierge@foodforestkanthalloor.com', 'General Manager', '2026-09-16 11:15:10', '2026-09-13 13:39:25');

-- -------------------------------------------------------------
-- Table structure for `bookings`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT 1,
  `guest_access_token` varchar(100) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `reference_code` varchar(100) NOT NULL,
  `villa_type` varchar(100) NOT NULL,
  `guest_name` varchar(150) NOT NULL,
  `guest_phone` varchar(50) NOT NULL,
  `guest_email` varchar(150) DEFAULT NULL,
  `adults_count` int(11) DEFAULT 2,
  `kids_count` int(11) DEFAULT 0,
  `extra_adults` int(11) DEFAULT 0,
  `extra_kids` int(11) DEFAULT 0,
  `guests_count` int(11) DEFAULT 2,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `nights` int(11) DEFAULT 1,
  `addons` text DEFAULT NULL,
  `food_items` longtext DEFAULT NULL,
  `food_amount` decimal(10,2) DEFAULT 0.00,
  `food_status` varchar(50) DEFAULT 'none',
  `room_amount` decimal(10,2) DEFAULT 0.00,
  `special_notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_code` (`reference_code`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `bookings` (11 records)
INSERT INTO `bookings` (`id`, `user_id`, `is_guest`, `guest_access_token`, `expires_at`, `reference_code`, `villa_type`, `guest_name`, `guest_phone`, `guest_email`, `adults_count`, `kids_count`, `extra_adults`, `extra_kids`, `guests_count`, `checkin_date`, `checkout_date`, `nights`, `addons`, `food_items`, `food_amount`, `food_status`, `room_amount`, `special_notes`, `total_amount`, `status`, `created_at`) VALUES
(1, NULL, 1, NULL, NULL, 'FF-8104', 'treehouse', 'Dr. Siddharth Menon', '+91 98450 12345', 'siddharth.menon@gmail.com', 2, 0, 0, 0, 2, '2026-09-16', '2026-09-18', 2, 'Candlelight Orchard Dinner (+₹3,000), Sunrise High-Peak Valley Trail (+₹2,000)', NULL, '0.00', 'none', '0.00', 'Honeymoon anniversary stay. Requesting fresh wildflower arrangement in room.', '34000.00', 'confirmed', '2026-09-13 13:39:25'),
(2, NULL, 1, NULL, NULL, 'FF-8105', 'mudhouse', 'Aarav & Priya Varma', '+91 97401 88992', 'aarav.varma@outlook.com', 2, 0, 0, 0, 3, '2026-09-14', '2026-09-16', 2, 'Mud Pottery & Clay Workshop (+₹1,500)', NULL, '0.00', 'none', '0.00', 'Strictly vegan culinary requests. Arriving via private cab from Kochi.', '24500.00', 'pending', '2026-09-13 13:39:25'),
(3, NULL, 1, NULL, NULL, 'FF-8098', 'treehouse', 'Meera Krishnan', '+91 94471 55667', 'meera.krishnan@yahoo.in', 2, 0, 0, 0, 2, '2026-09-20', '2026-09-21', 1, 'Candlelight Orchard Dinner (+₹3,000)', NULL, '0.00', 'none', '0.00', 'Late check-in requested around 6:00 PM due to ghat driving.', '17500.00', 'pending', '2026-09-13 13:39:25'),
(4, NULL, 1, NULL, NULL, 'FF-8072', 'mudhouse', 'Rohan & Gayatri Kapoor', '+91 98200 44321', 'rohan.kapoor@innovate.co', 2, 0, 0, 0, 2, '2026-09-09', '2026-09-11', 2, 'Mud Pottery Workshop (+₹1,500), Sunrise Valley Trail (+₹2,000)', NULL, '0.00', 'none', '0.00', 'Loved the woodfire dinner experience.', '26500.00', 'completed', '2026-09-13 13:39:25'),
(6, NULL, 1, NULL, NULL, 'FF-6225', 'treehouse', 'SOJIN MATHEWcsdcEC', '8943804920', 'sojinmathew1040@gmail.com', 2, 0, 0, 0, 2, '2026-09-14', '2026-09-16', 2, 'Mud Pottery & Clay Workshop', NULL, '0.00', 'none', '0.00', 'Ce', '30500.00', 'pending', '2026-09-13 21:31:39'),
(7, NULL, 1, NULL, NULL, 'FF-4837', 'mudhouse', 'Aditya Sharma', '+919876543210', 'aditya@example.com', 2, 0, 0, 0, 4, '2026-10-01', '2026-10-03', 2, 'None', NULL, '0.00', 'none', '0.00', 'Mudhouse test with 4 guests', '29000.00', 'confirmed', '2026-09-15 10:52:25'),
(8, NULL, 1, NULL, NULL, 'FF-7245', 'treehouse-double', 'Meera Nair', '+919845012345', 'meera@example.com', 2, 0, 0, 0, 5, '2026-10-05', '2026-10-06', 1, 'Candlelight Orchard Dinner', NULL, '0.00', 'none', '0.00', 'Treehouse double with 5 guests + dinner', '29000.00', 'confirmed', '2026-09-15 10:52:25'),
(9, NULL, 1, NULL, NULL, 'FF-6883', 'mudhouse', 'Aditya Sharma', '+919876543210', 'aditya@example.com', 2, 0, 0, 0, 4, '2026-10-01', '2026-10-03', 2, 'None', NULL, '0.00', 'none', '0.00', 'Mudhouse test with 4 guests', '29000.00', 'confirmed', '2026-09-15 10:52:25'),
(10, NULL, 1, NULL, NULL, 'FF-2690', 'treehouse-double', 'Meera Nair', '+919845012345', 'meera@example.com', 2, 0, 0, 0, 5, '2026-10-05', '2026-10-06', 1, 'Candlelight Orchard Dinner', NULL, '0.00', 'none', '0.00', 'Treehouse double with 5 guests + dinner', '29000.00', 'confirmed', '2026-09-15 10:52:25'),
(13, NULL, 1, '8183', '2026-10-16 08:52:36', 'FF-TEST-3665', 'treehouse', 'Siddharth Menon', '+91 99887 76655', 'siddharth@example.com', 2, 0, 0, 0, 2, '2026-10-01', '2026-10-03', 2, 'None', '[{\"id\":1,\"category\":\"breakfast\",\"heading\":\"Signature Heritage Dosa Set\",\"subtitle\":\"Crispy Ghee Dosas with 3 Chutneys & Sambar\",\"price\":220,\"quantity\":2,\"subtotal\":440,\"inclusions\":[\"3 Crispy Dosas\",\"Potato Masala\",\"3 Chutneys\",\"Sambar\"]},{\"id\":2,\"category\":\"lunch\",\"heading\":\"Kanthalloor Earthen Claypot Sadya\",\"subtitle\":\"Harvest Feast on Plantain Leaf\",\"price\":480,\"quantity\":2,\"subtotal\":960,\"inclusions\":[\"Red Rice\",\"Parippu & Ghee\",\"Aviyal\",\"Payasam\"]}]', '1400.00', 'selected', '29000.00', 'Anniversary stay', '30400.00', 'pending', '2026-09-16 12:22:36'),
(14, NULL, 1, '5862', '2026-10-16 08:52:36', 'FF-TEST-5942', 'treehouse', 'Siddharth Menon', '+91 99887 76655', 'siddharth@example.com', 2, 0, 0, 0, 2, '2026-10-01', '2026-10-03', 2, 'None', '[{\"id\":1,\"category\":\"breakfast\",\"heading\":\"Signature Heritage Dosa Set\",\"subtitle\":\"Crispy Ghee Dosas with 3 Chutneys & Sambar\",\"price\":220,\"quantity\":2,\"subtotal\":440,\"inclusions\":[\"3 Crispy Dosas\",\"Potato Masala\",\"3 Chutneys\",\"Sambar\"]},{\"id\":2,\"category\":\"lunch\",\"heading\":\"Kanthalloor Earthen Claypot Sadya\",\"subtitle\":\"Harvest Feast on Plantain Leaf\",\"price\":480,\"quantity\":2,\"subtotal\":960,\"inclusions\":[\"Red Rice\",\"Parippu & Ghee\",\"Aviyal\",\"Payasam\"]}]', '1400.00', 'selected', '29000.00', 'Anniversary stay', '30400.00', 'pending', '2026-09-16 12:22:36');

-- -------------------------------------------------------------
-- Table structure for `experiences`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `experiences`;
CREATE TABLE `experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `badge` varchar(100) DEFAULT 'INCLUDED IN STAY',
  `timing` varchar(100) DEFAULT '2 Hours • Morning',
  `schedule_info` varchar(255) DEFAULT NULL,
  `location_info` varchar(255) DEFAULT NULL,
  `suitable_for` varchar(255) DEFAULT NULL,
  `what_to_bring` text DEFAULT NULL,
  `description` text NOT NULL,
  `detailed_description` text DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `inclusions` text DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `gallery_images` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `experiences` (4 records)
INSERT INTO `experiences` (`id`, `title`, `tagline`, `badge`, `timing`, `schedule_info`, `location_info`, `suitable_for`, `what_to_bring`, `description`, `detailed_description`, `highlights`, `inclusions`, `image_url`, `gallery_images`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Heirloom Orchard Harvest Walks', 'Hand-pluck crisp mountain fruits in certified organic permaculture orchards.', 'INCLUDED IN STAY', '2 Hours • Morning', 'Daily at 07:30 AM & 10:30 AM (Duration: 2 Hours)', 'Terraced Mountain Orchards & Botanical Nursery', 'Couples, Families, and Nature Enthusiasts of all ages', 'Comfortable walking shoes, sun hat, and a light jacket for morning mist.', 'Wander through terraced orchards accompanied by our resident botanist. Hand-pluck apples, blackberries, tree tomatoes, and learn organic permaculture.', 'Wander through our terraced mountain orchards accompanied by resident botanists and native horticulturists. Stroll through mist-kissed rows of heirloom apples, wild blackberries, passion fruit trellises, and sweet tamarillo (tree tomato) groves. Learn the ancestral principles of multi-tiered food forestry, regenerative compost cycles, and natural pest balancing without a single drop of synthetic chemicals. Pluck sun-ripened fruits right off the branch and savor freshly pressed organic juice prepared on-site.', '[\"Guided walk led by resident botanist & indigenous farmers\",\"Hand-pluck seasonal heirloom apples, blackberries & tree tomatoes\",\"Multi-layer permaculture & living soil biodiversity demo\",\"Freshly pressed organic orchard juice & fruit tasting session\"]', 'Handwoven harvest wicker basket, guided botanical notes, organic fruit tasting, freshly pressed estate juice.', 'assets/images/01 (18).jpeg', '[\"assets\\/images\\/01 (18).jpeg\",\"assets\\/images\\/01 (19).jpeg\",\"assets\\/images\\/01 (20).jpeg\",\"assets\\/images\\/01 (27).jpeg\"]', 1, 1, '2026-09-13 19:38:16'),
(2, 'Starlit Hearth & Folklore', 'Twilight slate stone fires, hot cardamom brews, and ancestral mountain folklore.', 'EVENING RITUAL', 'Twilight — Night', 'Every Evening • 07:00 PM to 09:30 PM', 'Central Amphitheater & Stone Hearth Courtyard', 'All residing guests seeking cozy evening tranquility', 'Warm jacket or fleece sweater, camera for starry night photography.', 'Gather around an open slate fire beneath an unpolluted Milky Way sky. Unwind with hot cardamom spiced brews, roasted corn, and quiet acoustic music.', 'As dusk blankets the Western Ghats with deep indigo mist and the high-range night turns crisp and cold, gather around our open slate stone fire pit beneath an unpolluted Milky Way sky. Warm your hands against dancing flames of teak embers and breathe in the rich aroma of mountain wood smoke. Sip piping hot cardamom and crushed ginger spiced mountain tea, enjoy wood-roasted sweet farm corn sprinkled with Marayoor sea salt, and listen to timeless legends of Kanthalloor’s ancient megalithic dolmens and tribal mountain lore told by indigenous elders.', '[\"Open slate hearth campfire beneath dark celestial skies\",\"Steaming Marayoor cardamom-ginger spiced brew & roasted farm corn\",\"Folk stories of tribal ancestors and high-range wildlife legends\",\"Acoustic native music and tranquil meditation by the embers\"]', 'Unlimited cardamom spiced farm tea, fire-roasted sweet corn, handwoven wool shawls for the mountain chill.', 'assets/images/01 (30).jpeg', '[\"assets\\/images\\/01 (30).jpeg\",\"assets\\/images\\/01 (31).jpeg\",\"assets\\/images\\/01 (32).jpeg\",\"assets\\/images\\/01 (7).jpeg\"]', 2, 1, '2026-09-13 19:38:16'),
(3, 'Vernacular Mud & Clay Workshops', 'Ground your hands in red earth, vetiver straw, and ancestral thermal architecture.', 'WORKSHOP', '2.5 Hours • Afternoon', 'Tuesdays, Thursdays & Saturdays • 02:30 PM to 05:00 PM', 'The Artisan Cob Studio & Clay Courtyard', 'Adults, architecture buffs, curious creative souls, and kids', 'Comfortable clothes you do not mind getting clay on, slip-on shoes.', 'Get hands-on with native red earth. Learn the ancient alchemy of straw, clay, and terracotta to discover how homes can breathe without mechanical cooling.', 'Connect deeply with the living earth beneath your feet. In this deeply tactile and grounding workshop, our master vernacular builders introduce you to the timeless art of earthen cob construction. Discover how native red earth, fine river sand, chopped vetiver grass, and slaked lime create breathable, thermally stable walls that keep interiors cool by day and cozy through chilly mountain nights. Knead the clay mix, sculpt miniature wall alcoves, and try your hand at smooth terracotta plastering using traditional wooden floats.', '[\"Hands-on mixing of native red clay, lime plaster, and vetiver straw\",\"Understanding thermal physics and breathable zero-carbon design\",\"Sculpting earthen wall niches, decorative reliefs & pottery forms\",\"Mentored by veteran native cob and thatch craftsmen\"]', 'Natural clay sculpting materials, protective studio aprons, traditional herbal tea and farm refreshment.', 'assets/images/01 (6).jpeg', '[\"assets\\/images\\/01 (6).jpeg\",\"assets\\/images\\/01 (1).jpeg\",\"assets\\/images\\/01 (17).jpeg\",\"assets\\/images\\/01 (14).jpeg\"]', 3, 1, '2026-09-13 19:38:16'),
(4, 'Sunrise High-Ridge Valley Trek', 'Ascend misty high-range ridges to witness golden dawn across the Anaimudi peaks.', 'ADVENTURE', '3.5 Hours • Sunrise', 'Daily Departure at 05:45 AM Sharp (Duration: 3.5 Hours)', 'Departs from Sanctuary Welcome Lounge', 'Guests with moderate fitness levels (beginner-to-intermediate trail)', 'Sturdy walking / hiking footwear, windbreaker or jacket, reusable water flask.', 'Ascend through sandalwood forests and misty tea fringes to witness the sunrise break across the Anaimudi peak range with fresh mountain tea.', 'Begin before first light, ascending along ancient forest trails through fragrant wild lemongrass meadows, private sandalwood groves, and emerald tea estate fringes. Reach the panoramic ridge just as the first amber rays ignite the mist rolling off the Anaimudi peak range and the expansive Marayoor valley below. Enjoy freshly steeped estate black tea poured from thermos flasks with hot organic harvest pastries atop the cliff while spotting rare high-altitude birds such as the Nilgiri Pipit and Malabar Whistling Thrush.', '[\"Guided 5km sunrise trek through sandalwood & tea estate frontiers\",\"Breathtaking 360-degree dawn panorama across the Western Ghats\",\"Cliffside tea ceremony with freshly steeped high-altitude black tea\",\"Birdwatching & wildlife tracking with our native naturalist\"]', 'Hand-carved wooden trekking pole, thermos mountain tea, organic fruit & nut energy packs, binoculars.', 'assets/images/01 (33).jpeg', '[\"assets\\/images\\/01 (33).jpeg\",\"assets\\/images\\/01 (34).jpeg\",\"assets\\/images\\/01 (35).jpeg\",\"assets\\/images\\/01 (28).jpeg\"]', 4, 1, '2026-09-13 19:38:16');

-- -------------------------------------------------------------
-- Table structure for `food_menu`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `food_menu`;
CREATE TABLE `food_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `heading` varchar(200) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_note` varchar(100) DEFAULT 'Per Set',
  `description` text DEFAULT NULL,
  `inclusions` text DEFAULT NULL,
  `dietary_type` varchar(50) DEFAULT 'veg',
  `badge` varchar(100) DEFAULT 'Farm Fresh',
  `image_url` varchar(255) DEFAULT NULL,
  `gallery_images` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `food_menu` (9 records)
INSERT INTO `food_menu` (`id`, `category`, `heading`, `subtitle`, `price`, `price_note`, `description`, `inclusions`, `dietary_type`, `badge`, `image_url`, `gallery_images`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'breakfast', 'Signature Heritage Dosa Set', 'Crispy Ghee Dosas with 3 Stone-Ground Chutneys & Claypot Sambar', '220.00', 'Per Set • Farm Breakfast', 'Fermented batter of organic red rice and black lentils, ladled onto seasoned cast-iron skillets and crisped with pure A2 farm ghee. Served piping hot on a fresh plantain leaf with three distinctive regional chutneys.', '3 Crispy Golden Ghee Dosas\nSpiced Potato Podi Masala\nFresh Coconut-Mint Chutney\nRoasted Tomato & Garlic Chutney\nShallot & Kanthari White Chutney\nPiping Hot Drumstick Sambar', 'veg', 'ESTATE SIGNATURE', 'assets/images/food_dosa_set.jpg', '[\"assets\\/images\\/food_dosa_set.jpg\",\"assets\\/images\\/01 (10).jpeg\",\"assets\\/images\\/01 (3).jpeg\"]', 1, 1, '2026-09-16 11:43:21'),
(2, 'breakfast', 'Clay-Baked Appam & Vegetable Ishtu', 'Lacy Fermented Rice Crepes with Farm Coconut Milk Stew', '240.00', 'Per Set', 'Soft, pillowy centers with delicate paper-thin crispy lace edges baked in traditional earthen appachattis. Accompanied by fragrant coconut milk stew simmered with heirloom potatoes, baby carrots, and sweet garden peas.', '4 Lacy Steamed Appams\nFresh Coconut Milk Vegetable Ishtu\nRoasted Coconut Chammanthi\nCardamom Spiced Chai', 'veg', 'VILLAGE CLASSIC', 'assets/images/01 (10).jpeg', '[\"assets\\/images\\/01 (10).jpeg\",\"assets\\/images\\/01 (3).jpeg\",\"assets\\/images\\/food_dosa_set.jpg\"]', 2, 1, '2026-09-16 11:43:21'),
(3, 'breakfast', 'Heritage Red Rice Puttu & Kadala Curry', 'Steamed Bamboo Puttu Cylinders with Spiced Black Chickpea Gravy', '210.00', 'Per Set', 'Coarsely ground organic red matta rice flour layered with freshly grated coconut and steamed in bamboo hollows. Paired with rich black chickpea gravy roasted in native coconut oil.', '2 Bamboo Steamed Puttu Cylinders\nSlow-Braised Kadala Curry\nSmall Ripe Farm Banana\nPapadam & Ghee', 'veg', 'ORGANIC GRAIN', 'assets/images/01 (1).jpeg', '[\"assets\\/images\\/01 (1).jpeg\",\"assets\\/images\\/01 (10).jpeg\"]', 3, 1, '2026-09-16 11:43:21'),
(4, 'lunch', 'Kanthalloor Earthen Claypot Sadya', 'Full Traditional Harvest Feast Served on Fresh Plantain Leaf', '480.00', 'Per Person Feast', 'A ceremonial organic banquet celebrating the biodiverse harvest of Kanthalloor. Every side is slow-simmered in native red clay pots over wood embers using cold-pressed coconut oil.', 'Steamed Organic Red Matta Rice\nTraditional Mixed Vegetable Avial\nFarm Greens & Coconut Thoran\nSlow-Simmered Drumstick Sambar\nCurd-Tempered Pulissery\nCrispy Mountain Banana Chips & Sarkara Varatti\nStone-Ground Ginger Pickle (Inji Curry)\nCrisp Urud Papadam\nRich Marayoor Jaggery & Rice Payasam', 'veg', 'CHEF\'S HARVEST FEAST', 'assets/images/food_kerala_sadya.jpg', '[\"assets\\/images\\/food_kerala_sadya.jpg\",\"assets\\/images\\/01 (3).jpeg\",\"assets\\/images\\/01 (13).jpeg\"]', 1, 1, '2026-09-16 11:43:21'),
(5, 'lunch', 'Woodfire Jackfruit & Lentil Curry Set', 'Tender Raw Chakka Braised in Roasted Coconut & Mountain Spices', '360.00', 'Per Set', 'Tender heirloom jackfruit harvested from century-old trees on the sanctuary slopes, braised slowly with toasted shallots, coriander, and freshly grated coconut.', 'Tender Jackfruit Varutharacha Curry\nFragrant Jeera Samba Rice\nRaw Banana Podimas\nSun-Dried Chili Buttermilk (Moru)', 'veg', 'HEIRLOOM FORAGED', 'assets/images/01 (3).jpeg', '[\"assets\\/images\\/01 (3).jpeg\",\"assets\\/images\\/food_kerala_sadya.jpg\"]', 2, 1, '2026-09-16 11:43:21'),
(6, 'snacks', 'High-Range Evening Chai & Farm Fritters Set', 'Piping Hot Marayoor Cardamom Chai with Sweet & Savory Estate Bites', '160.00', 'Tea & Bites Set', 'Gather on the veranda as the afternoon mist rolls into the valley. Enjoy steaming cardamom milk tea poured from brass tumblers, alongside golden nendran banana fritters and crisp lentil vadas.', 'Steaming Marayoor Cardamom Spiced Milk Tea\n2 Golden Pazham Pori (Crispy Banana Fritters)\n2 Crispy Medu Uzhunnu Vada\nFresh Coconut & Green Chili Chutney', 'veg', 'TWILIGHT RITUAL', 'assets/images/food_evening_snacks.jpg', '[\"assets\\/images\\/food_evening_snacks.jpg\",\"assets\\/images\\/01 (20).jpeg\",\"assets\\/images\\/01 (30).jpeg\"]', 1, 1, '2026-09-16 11:43:21'),
(7, 'snacks', 'Steamed Sweet Ela Ada & Herbal Infusion', 'Banana Leaf Steamed Rice Parcels Stuffed with Marayoor Jaggery', '180.00', 'Per Set', 'Delicate thin rice dough pockets filled with a rich filling of freshly grated organic coconut, crushed cardamom, and pure GI-tagged Marayoor dark molasses jaggery, wrapped in fragrant banana leaves and steamed.', '2 Warm Banana-Leaf Steamed Ela Ada\nHot Ginger & Lemongrass Infusion\nRoasted Salted Cashews', 'veg', 'TRADITIONAL DELICACY', 'assets/images/01 (19).jpeg', '[\"assets\\/images\\/01 (19).jpeg\",\"assets\\/images\\/food_evening_snacks.jpg\"]', 2, 1, '2026-09-16 11:43:21'),
(8, 'dinner', 'Twilight Hearth Stew & Malabar Porotta Set', 'Aromatic Farm Vegetable Stew with Flaky Layered Hearth Breads', '390.00', 'Per Set', 'Served beside the crackling embers of the open hearth. Layered artisanal porottas or soft rice pathiri served with slow-cooked vegetable and wild mushroom stew scented with whole cinnamon and crushed black pepper.', '3 Golden Layered Artisanal Porottas\nEarthen Pot Coconut-Vegetable Stew\nGrilled Forest Mushroom Kurma\nCaramelized Onion & Tomato Relish', 'veg', 'CAMPFIRE SPECIAL', 'assets/images/01 (31).jpeg', '[\"assets\\/images\\/01 (31).jpeg\",\"assets\\/images\\/01 (30).jpeg\",\"assets\\/images\\/01 (3).jpeg\"]', 1, 1, '2026-09-16 11:43:21'),
(9, 'dinner', 'Charcoal Roasted Mountain Root Platter', 'Blistered Sweet Potatoes, Tapioca & Sweet Corn with Kanthari Dip', '320.00', 'Sharing Platter', 'Wholesome high-altitude root vegetables and tender corn on the cob roasted over aromatic teakwood charcoal. Served with stone-crushed bird\'s eye chili and raw shallot chutney.', 'Woodfire Blistered Farm Tapioca (Kappa)\nRoasted Sweet Mountain Potatoes\nCharred Sweet Corn Cobs with Rock Salt & Lime\nStone-Ground Kanthari Chili & Virgin Coconut Oil Dip', 'veg', 'WOODFIRE ROAST', 'assets/images/01 (25).jpeg', '[\"assets\\/images\\/01 (25).jpeg\",\"assets\\/images\\/01 (20).jpeg\"]', 2, 1, '2026-09-16 11:43:21');

-- -------------------------------------------------------------
-- Table structure for `gallery`
-- -------------------------------------------------------------
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `gallery` (9 records)
INSERT INTO `gallery` (`id`, `title`, `caption`, `tag`, `category`, `image_url`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'High Canopy Treehouse in Mist', 'Perched 30 feet above the forest floor among silver oaks, overlooking rolling valley clouds.', 'CANOPY DWELLING · 1,640M', 'Villas & Stays', 'assets/images/treehouse_exterior.png', 1, 1, '2026-09-13 19:38:16'),
(2, 'Hand-Sculpted Cob Mudhouse', 'Naturally insulated clay and sand architecture with private herbal garden courtyards.', 'EARTHEN ARCHITECTURE', 'Handcrafted Living', 'assets/images/mudhouse_exterior.png', 2, 1, '2026-09-13 19:38:16'),
(3, 'The Western Ghats Vista', 'High-altitude horizon cloaked in shifting clouds and untouched shola wilderness.', 'ALPINE HORIZON', 'Landscape', 'assets/images/01 (25).jpeg', 3, 1, '2026-09-13 19:38:16'),
(4, 'Woodfire Claypot Lunch', 'Pure farm-to-table cooking over slow embers using hand-ground spices and organic produce.', 'EARTHEN GASTRONOMY', 'Gastronomy', 'assets/images/01 (3).jpeg', 4, 1, '2026-09-13 19:38:16'),
(5, 'Organic Winter Apple Orchards', 'Ancient heirloom trees yielding sweet, pesticide-free mountain apples each winter.', 'ESTATE HARVEST', 'Orchards', 'assets/images/01 (1).jpeg', 5, 1, '2026-09-13 19:38:16'),
(6, 'Stargazing by the Cob Hearth', 'Night skies at 1,600m altitude illuminated only by campfire crackle and constellations.', 'NIGHT SKY SANCTUARY', 'Nightscape', 'assets/images/01 (20).jpeg', 6, 1, '2026-09-13 19:38:16'),
(7, 'Morning Dew on Passion Fruit Vines', 'Wild pollinators and lush flora flourishing in our certified chemical-free sanctuary.', 'BOTANICAL HARMONY', 'Flora', 'assets/images/01 (15).jpeg', 7, 1, '2026-09-13 19:38:16'),
(8, 'Living Mud Courtyard Veranda', 'Unpaved, breathable courtyards connecting guest quarters directly with the soil.', 'BIOPHILIC SPACES', 'Architecture', 'assets/images/01 (7).jpeg', 8, 1, '2026-09-13 19:38:16'),
(9, 'ehse', 'atr', 'SANCTUARY ARCHIVE', 'Landscape', 'assets/images/01 (1).jpeg', 9, 1, '2026-09-14 19:03:42');

-- -------------------------------------------------------------
-- Table structure for `inquiries`
-- -------------------------------------------------------------
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

-- -------------------------------------------------------------
-- Table structure for `rooms`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `stay_type` varchar(50) DEFAULT 'treehouse',
  `structure_type` varchar(50) DEFAULT 'single_hut',
  `title` varchar(150) NOT NULL,
  `rate_per_night` decimal(10,2) NOT NULL,
  `extra_guest_rate` decimal(10,2) DEFAULT 1500.00,
  `extra_child_rate` decimal(10,2) DEFAULT 800.00,
  `elevation` varchar(100) DEFAULT NULL,
  `base_guests` int(11) DEFAULT 2,
  `max_guests` int(11) DEFAULT 2,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `interior_360_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `rooms` (4 records)
INSERT INTO `rooms` (`id`, `slug`, `stay_type`, `structure_type`, `title`, `rate_per_night`, `extra_guest_rate`, `extra_child_rate`, `elevation`, `base_guests`, `max_guests`, `description`, `amenities`, `image_url`, `interior_360_url`, `is_available`, `created_at`) VALUES
(1, 'treehouse', 'treehouse', 'single_hut', 'Luxury Canopy Treehouse', '14500.00', '1500.00', '800.00', '30ft Elevated Canopy', 2, 3, 'Suspended 30 feet above the forest floor within ancient trees. Crafted with wild teak timber, an open cantilevered deck, and expansive curved glass with panoramic mist views.', 'King Teak Bed, Panoramic Bay Window, Rain Mist Shower, Private Teak Balcony, Telescope, Complimentary Farm Meals, High Elevation Wi-Fi', '', 'assets/images/treehouse_360_pano.jpg', 1, '2026-09-13 19:38:16'),
(2, 'mudhouse', 'mudhouse', 'single_hut', 'Traditional Earthen Mudhouse', '11500.00', '1500.00', '800.00', 'High-Altitude Orchard Ground', 2, 4, 'Handcrafted using regenerative cob clay, lime plaster, and vetiver straw. Naturally climate-controlled interior keeping spaces cool in the day and warm through misty mountain nights.', 'Double Queen Beds, Clay Hearth Fireplace, Handcrafted Pottery Accents, Organic Herb Garden Veranda, Private Open-air Courtyard, Farm-to-table Dining', '', 'assets/images/mudhouse_360_pano.jpg', 1, '2026-09-13 19:38:16'),
(3, 'treehouse-double', 'treehouse', 'duplex_hut', 'The Canopy Treehouse — Double Cottage', '24000.00', '2000.00', '800.00', '30FT ELEVATION • DUPLEX SUITE', 4, 6, 'An expansive two-tier canopy residence designed for larger families or companion groups. Accommodates four guests luxuriously across two master handcrafted teak bedrooms with dual private balconies soaring over the misty valley.', '2 Handcrafted King Teak Beds, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included', 'assets/images/treehouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', 1, '2026-09-15 10:43:43'),
(4, 'mudhouse-duplex', 'mudhouse', 'duplex_hut', 'The Earthen Mudhouse — Duplex Family Sanctuary', '21000.00', '1500.00', '800.00', 'COB HERITAGE • DUPLEX SUITE', 4, 8, 'An expansive two-level authentic cob residence sculpted from natural clay, straw, and river sand. Designed for families and private retreat groups seeking biophilic living, featuring two master cob chambers, terracotta veranda, and indoor slate hearth.', '2 Handcrafted Queen Clay Beds, Terracotta Veranda, Private Herb Garden, Slate Hearth Fireplace, Natural Clay Water Coolers, All Farm Meals Included', 'assets/images/mudhouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', 1, '2026-09-15 14:26:39');

-- -------------------------------------------------------------
-- Table structure for `sanctuary_spots`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `sanctuary_spots`;
CREATE TABLE `sanctuary_spots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spot_number` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `subtitle_tag` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'nature',
  `elevation` varchar(100) DEFAULT '1,600M MSL',
  `temperature` varchar(100) DEFAULT '18°C Alpine Breeze',
  `description` text NOT NULL,
  `aroma` varchar(150) DEFAULT NULL,
  `sound` varchar(150) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `photos` text DEFAULT NULL,
  `cta_text` varchar(100) DEFAULT 'Explore Details',
  `cta_link` varchar(255) DEFAULT '#rooms',
  `x_coord` decimal(5,2) NOT NULL DEFAULT 50.00,
  `y_coord` decimal(5,2) NOT NULL DEFAULT 50.00,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `sanctuary_spots` (6 records)
INSERT INTO `sanctuary_spots` (`id`, `spot_number`, `title`, `subtitle_tag`, `category`, `elevation`, `temperature`, `description`, `aroma`, `sound`, `image_url`, `photos`, `cta_text`, `cta_link`, `x_coord`, `y_coord`, `display_order`, `is_active`, `created_at`) VALUES
(1, 1, 'Farmhouse Kitchen & Organic Dining', 'COMMON KITCHEN & DINING', 'dining', '1,580M MSL', '19°C Warm Hearth', 'Central farm hearth serving 100% organic farm-to-table meals harvested daily from our heirloom orchards. Wood-fired open kitchen and mountain view dining.', 'Woodsmoke, cardamom & roasted spices', 'Crackling hearth, laughter & tea kettle', 'assets/images/01 (10).jpeg', '[\"assets\\/images\\/01 (10).jpeg\"]', 'Farmhouse Dining', '#philosophy', '19.00', '56.00', 1, 1, '2026-09-14 20:26:44'),
(2, 2, 'The Earthen Mudhouse Enclave', 'EARTHEN HERITAGE VILLA', 'stays', '1,600M MSL', '21°C Thermal Comfort', 'Handcrafted cob clay cottages sculpted from native red soil, river sand, and straw. Naturally insulated against chilly nights with a private plantation sit-out.', 'Sun-baked earth, vetiver & woodsmoke', 'Crackling hearth embers, crickets', 'assets/images/mudhouse_exterior.png', '[\"assets\\/images\\/mudhouse_exterior.png\"]', 'Reserve Mudhouse', '#booking-modal', '32.20', '41.10', 2, 1, '2026-09-14 20:26:44'),
(3, 3, 'High-Altitude Canopy Treehouse', 'HIGH CANOPY RETREAT', 'stays', '1,620M MSL', '17°C Alpine Breeze', 'Elevated living among towering mountain trees. Floor-to-ceiling panoramic glass windows looking out over cascading mist, apple terraces, and sunrise valleys.', 'Fresh cedarwood, wild jasmine & pine', 'Wind through high canopies, bulbul calls', 'assets/images/treehouse_exterior.png', '[\"assets\\/images\\/treehouse_exterior.png\"]', 'Reserve Treehouse', '#booking-modal', '21.50', '17.80', 3, 1, '2026-09-14 20:26:44'),
(4, 4, 'Crystal Mountain Brook & Plunge Pool', 'FRESH SPRING PLUNGE POOL', 'amenities', '1,560M MSL', '15°C Spring Freshwater', 'Pristine mountain brook feeding into a natural granite plunge pool. Serene freshwater bathing and riverside meditation amidst lush shola ferns.', 'Fern leaves, damp river stones & mineral mist', 'Melodic rushing stream, pebble resonance', 'assets/images/01 (28).jpeg', '[\"assets\\/images\\/01 (28).jpeg\"]', 'Explore Waters', '#experiences', '35.80', '7.40', 4, 1, '2026-09-14 20:26:44'),
(5, 5, 'Campfire Glade & BBQ Grilling Shed', 'EVENING BBQ & STARGAZING', 'amenities', '1,640M MSL', '14°C Crisp Night Air', 'Covered rustic timber barbecue pavilion and open granite firepit. Guests gather here for evening grilling rituals and acoustic stargazing under Class-1 dark skies.', 'Ember woodsmoke, roasted pepper & eucalyptus', 'Acoustic guitar, crackling embers, mountain breeze', 'assets/images/01 (25).jpeg', '[\"assets\\/images\\/01 (25).jpeg\"]', 'Evening Rituals', '#experiences', '73.40', '71.40', 5, 1, '2026-09-14 20:26:44'),
(6, 6, 'Children\'s Play Glade & Orchard Walk', 'RECREATION & HARVEST TRAILS', 'nature', '1,570M MSL', '18°C Mild Mountain Sun', 'Terraced grassy lawn equipped with traditional wooden swings, outdoor play zones for kids, and walking trails weaving through fruit-bearing apple and plum trees.', 'Wild berries, sweet apple blossoms & clover', 'Songbirds, children\'s laughter, rustling leaves', 'assets/images/01 (19).jpeg', '[\"assets\\/images\\/01 (19).jpeg\"]', 'Orchard Activities', '#experiences', '48.00', '87.20', 6, 1, '2026-09-14 20:26:44');

-- -------------------------------------------------------------
-- Table structure for `seasons`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `seasons`;
CREATE TABLE `seasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `months` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `seasons` (4 records)
INSERT INTO `seasons` (`id`, `title`, `months`, `description`, `image_url`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Monsoon Magic', 'June - September', 'Lush, deep green landscapes, rising mist, heavy refreshing rainfall, and crisp cold mountain breeze.', 'assets/images/01 (9).jpeg', 1, 1, '2026-09-14 19:30:33'),
(2, 'Cozy Winter', 'October - February', 'Chilly mist, clear bright blue skies, warm sunlit afternoons, and snug campfire nights under starry skies.', 'assets/images/01 (8).jpeg', 2, 1, '2026-09-14 19:30:33'),
(3, 'Harvest Season', 'March - May', 'Crisp mountain breezes, blooming orchards of apples, oranges, and plums, and vibrant farm life in full motion.', 'assets/images/01 (19).jpeg', 3, 1, '2026-09-14 19:30:33'),
(4, 'Summer Bloom', 'April - June', 'Pleasant, breezy weather. Kanthalloor acts as a cool refuge from the sweltering heat of the plains.', 'assets/images/01 (33).jpeg', 4, 1, '2026-09-14 19:30:33');

-- -------------------------------------------------------------
-- Table structure for `settings`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(191) NOT NULL,
  `setting_value` mediumtext DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `settings` (41 records)
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('checkin_time', '02:00 PM'),
('checkout_time', '11:00 AM'),
('concierge_email', 'concierge@foodforestkanthalloor.com'),
('concierge_phone', '+91 92345 67890'),
('concierge_whatsapp', '919234567890'),
('currency_symbol', '₹'),
('estate_name', 'Food Forest Eco Sanctuary'),
('experiences_badge', 'ACTIVITIES'),
('experiences_desc', 'Connect deeply with the pulse of Kanthalloor. Each experience is handcrafted to immerse you in vernacular craftsmanship, mountain wilderness, and restorative tranquility.'),
('experiences_title', 'Rituals of the High Range'),
('hero_bg_image', 'assets/images/01 (25).jpeg'),
('hero_desc', 'Tucked deep in the misty hills and organic orchards of Kanthalloor. Experience private earthen mudhouses, soaring canopy treehouses, and nourishing farm gastronomy cooked slowly over wood fires.'),
('hero_eyebrow', 'KANTHALLOOR, KERALA • PRIVATE ECO-SANCTUARY'),
('hero_title', 'Where Earth Breathes & Time Stands Still.'),
('location', 'Kanthalloor High Range, Idukki, Kerala - 685620'),
('menu_badge', 'ESTATE GASTRONOMY & ORGANIC DINING'),
('menu_desc', 'Food at Food Forest is a ritual. Cooked in indigenous clay pots over aromatic wood hearths, every meal is prepared with ingredients harvested minutes prior from our own organic soil.'),
('menu_desc_breakfast', 'Morning in the Orchards • Fresh farm juices, lacy hoppers & stone-ground breakfast sets'),
('menu_desc_dinner', 'Twilight Campfire Dining • Slow-simmered stews, charcoal grills & jaggery desserts by the embers'),
('menu_desc_lunch', 'Claypot Hearth Feast • Heirloom red rice, seasonal thorans & traditional banana-leaf sadya'),
('menu_desc_snacks', 'Plantation Tea Ritual • Steaming Marayoor cardamom chai, hot banana fritters & steamed ela ada'),
('menu_time_breakfast', '07:30 AM — 10:00 AM'),
('menu_time_dinner', '07:30 PM — 10:00 PM'),
('menu_time_lunch', '12:30 PM — 02:30 PM'),
('menu_time_snacks', '04:30 PM — 06:30 PM'),
('menu_title', 'The Forest Hearth & Living Menu'),
('sanctuary_section_label', 'The Living Landscape'),
('sanctuary_section_title', 'An Untamed Sanctuary'),
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

-- -------------------------------------------------------------
-- Table structure for `testimonials`
-- -------------------------------------------------------------
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

-- Dumping data for table `testimonials` (4 records)
INSERT INTO `testimonials` (`id`, `guest_name`, `guest_location`, `stay_badge`, `stars`, `quote`, `initials`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Ananya & Siddharth Nair', 'Kochi, India · Stayed Nov 2025', 'CANOPY TREEHOUSE', 5, 'The silence here is pure medicine. Waking up 30 feet above the valley floor to the mist drifting through our bedroom balcony was something we will carry in our hearts forever. The woodfire claypot lunch was unforgettable.', 'AN', 1, 1, '2026-09-13 19:38:16'),
(2, 'Dr. Julian & Clara Vance', 'Edinburgh, UK · Stayed Jan 2026', 'EARTHEN MUDHOUSE', 5, 'As architects passionate about sustainable living, the cob mudhouse blew us away. The natural indoor temperature stayed delightfully cool despite the midday sun. Stargazing by the open hearth was unmatched.', 'JV', 2, 1, '2026-09-13 19:38:16'),
(3, 'Meera Krishnan', 'Bengaluru, India · Stayed Feb 2026', 'SOLO RETREAT', 5, 'I came seeking refuge from city noise and found complete sanctuary. The sound of the mountain brook, the aroma of crushed wild rosemary on the morning trails, and the warmth of the hosts made me extend my stay by four days.', 'MK', 3, 1, '2026-09-13 19:38:16'),
(4, 'Vikramaditya & Rohini Sen', 'New Delhi, India · Stayed Dec 2025', 'ORCHARD HARVEST STAY', 5, 'Our children had never plucked apples and passionfruit directly from trees before. Eating ripe fruit straight from the branch while watching the clouds roll into the valley was the highlight of our year.', 'VS', 4, 1, '2026-09-13 19:38:16');

-- -------------------------------------------------------------
-- Table structure for `users`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users` (1 records)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `created_at`, `last_login`) VALUES
(1, 'Arya Varma', 'test_guest_1789541556@example.com', '+91 98765 00000', '$2y$10$rp7HdVqJwZEN57D3zglvf.qhNfROuR/cxIoGPBk9VX.7g4tYDWwKa', '2026-09-16 12:22:36', '2026-09-16 12:22:36');

SET FOREIGN_KEY_CHECKS = 1;
-- =========================================================================
-- End of Database Backup
-- =========================================================================
