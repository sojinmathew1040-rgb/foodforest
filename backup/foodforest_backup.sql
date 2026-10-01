-- =========================================================================
-- Food Forest Sanctuary — Automated Database Backup
-- Generated at: 2026-10-01 08:04:30
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
(1, 'admin', 'admin123', 'Food Forest', 'foodforestkanthalloor@gmail.com', 'General Manager', '2026-09-29 11:27:16', '2026-09-13 19:09:25');

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
  `booking_source` varchar(50) DEFAULT 'direct_website',
  `billing_type` varchar(20) DEFAULT 'estimate',
  `gst_number` varchar(50) DEFAULT NULL,
  `billing_name` varchar(150) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `gst_percentage` decimal(5,2) DEFAULT 0.00,
  `gst_amount` decimal(10,2) DEFAULT 0.00,
  `external_uid` varchar(255) DEFAULT NULL,
  `sync_hash` varchar(64) DEFAULT NULL,
  `guest_name` varchar(150) NOT NULL,
  `guest_phone` varchar(50) NOT NULL,
  `guest_email` varchar(150) DEFAULT NULL,
  `id_proof_type` varchar(100) DEFAULT NULL,
  `id_proof_number` varchar(100) DEFAULT NULL,
  `id_proof_file` varchar(255) DEFAULT NULL,
  `city_state` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `adults_count` int(11) DEFAULT 2,
  `kids_count` int(11) DEFAULT 0,
  `extra_adults` int(11) DEFAULT 0,
  `extra_kids` int(11) DEFAULT 0,
  `guests_count` int(11) DEFAULT 2,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `nights` int(11) DEFAULT 1,
  `addons` text DEFAULT NULL,
  `activities_json` longtext DEFAULT NULL,
  `billing_items_json` longtext DEFAULT NULL,
  `food_items` longtext DEFAULT NULL,
  `food_amount` decimal(10,2) DEFAULT 0.00,
  `food_status` varchar(50) DEFAULT 'none',
  `room_amount` decimal(10,2) DEFAULT 0.00,
  `special_notes` text DEFAULT NULL,
  `billing_notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `advance_paid` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `extra_charges` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'unspecified',
  `payment_status` varchar(50) DEFAULT 'unpaid',
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_code` (`reference_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `bookings` (3 records)
INSERT INTO `bookings` (`id`, `user_id`, `is_guest`, `guest_access_token`, `expires_at`, `reference_code`, `villa_type`, `booking_source`, `billing_type`, `gst_number`, `billing_name`, `billing_address`, `gst_percentage`, `gst_amount`, `external_uid`, `sync_hash`, `guest_name`, `guest_phone`, `guest_email`, `id_proof_type`, `id_proof_number`, `id_proof_file`, `city_state`, `country`, `adults_count`, `kids_count`, `extra_adults`, `extra_kids`, `guests_count`, `checkin_date`, `checkout_date`, `checked_in_at`, `checked_out_at`, `nights`, `addons`, `activities_json`, `billing_items_json`, `food_items`, `food_amount`, `food_status`, `room_amount`, `special_notes`, `billing_notes`, `total_amount`, `advance_paid`, `discount_amount`, `tax_amount`, `extra_charges`, `payment_method`, `payment_status`, `status`, `created_at`) VALUES
(3, NULL, 1, '3386', '2026-10-24 14:08:57', 'FF-8363', 'mudhouse-stay', 'direct_website', 'estimate', NULL, NULL, NULL, '0.00', '0.00', NULL, NULL, 'Anna RAjeev', '9946020724', 'anna@gmail.com', 'Aadhaar Card', '1234567891011', 'id_ff-8363_1790251737.png', 'Kerala', 'India', 4, 0, 2, 0, 4, '2026-09-25', '2026-09-26', '2026-09-25 09:33:08', '2026-09-30 15:41:55', 1, 'Candlelight Orchard Dinner, Mud Pottery & Clay Workshop, Sunrise High-Peak Valley Trail', NULL, NULL, NULL, '0.00', 'skipped', '7000.00', '', NULL, '7000.00', '0.00', '0.00', '0.00', '0.00', 'unspecified', 'unpaid', 'completed', '2026-09-24 17:38:57'),
(4, NULL, 1, '8189', '2026-10-25 07:54:51', 'FF-6835', 'mudhouse-stay', 'direct_website', 'gst', '111111111111111', 'Daba Magic', 'Vettathukandathil House\r\nVeliyannoor PO', '12.00', '672.00', NULL, NULL, 'Robin Mathew', '9946020724', 'dijoperumalil@gmail.com', 'Aadhaar Card', '1234567891011', NULL, 'Areekara', 'India', 2, 0, 0, 0, 2, '2026-09-26', '2026-09-26', NULL, NULL, 1, '', NULL, NULL, NULL, '0.00', 'skipped', '5600.00', '', NULL, '6272.00', '0.00', '0.00', '672.00', '0.00', 'unspecified', 'unpaid', 'confirmed', '2026-09-25 11:24:51'),
(5, NULL, 1, '2894', '2026-10-30 11:58:47', 'FF-2873', 'mudhouse-stay', 'direct_website', 'gst', '111111111111111', 'Daba Magic', 'Vettathukandathil House\r\nVeliyannoor PO', '17.62', '2431.56', NULL, NULL, 'Robin Mathew', '9946020724', 'dijoperumalil@gmail.com', 'Aadhaar Card', '1234567891011', 'id_ff-2873_de15f72f96ef993dc699.png', 'Areekara', 'India', 3, 1, 1, 1, 4, '2026-09-30', '2026-10-01', '2026-10-01 10:00:26', '2026-10-01 10:21:09', 1, 'Candlelight Orchard Dinner, Sunrise High-Peak Valley Trail', NULL, NULL, NULL, '0.00', 'skipped', '13800.00', 'Sugarless food for one person', NULL, '16231.56', '0.00', '0.00', '2431.56', '0.00', 'unspecified', 'unpaid', 'completed', '2026-09-30 15:28:48');

-- -------------------------------------------------------------
-- Table structure for `experiences`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `experiences`;
CREATE TABLE `experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `badge` varchar(100) DEFAULT 'INCLUDED IN STAY',
  `timing` varchar(100) DEFAULT '2 Hours ??? Morning',
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
(1, 'Heirloom Orchard Harvest Walks', 'Hand-pluck crisp mountain fruits in certified organic permaculture orchards.', 'INCLUDED IN STAY', '2 Hours ??? Morning', 'Daily at 07:30 AM & 10:30 AM (Duration: 2 Hours)', 'Terraced Mountain Orchards & Botanical Nursery', 'Couples, Families, and Nature Enthusiasts of all ages', 'Comfortable walking shoes, sun hat, and a light jacket for morning mist.', 'Wander through terraced orchards accompanied by our resident botanist. Hand-pluck apples, blackberries, tree tomatoes, and learn organic permaculture.', 'Wander through our terraced mountain orchards accompanied by resident botanists and native horticulturists. Stroll through mist-kissed rows of heirloom apples, wild blackberries, passion fruit trellises, and sweet tamarillo (tree tomato) groves. Learn the ancestral principles of multi-tiered food forestry, regenerative compost cycles, and natural pest balancing without a single drop of synthetic chemicals. Pluck sun-ripened fruits right off the branch and savor freshly pressed organic juice prepared on-site.', '[\"Guided walk led by resident botanist & indigenous farmers\",\"Hand-pluck seasonal heirloom apples, blackberries & tree tomatoes\",\"Multi-layer permaculture & living soil biodiversity demo\",\"Freshly pressed organic orchard juice & fruit tasting session\"]', 'Handwoven harvest wicker basket, guided botanical notes, organic fruit tasting, freshly pressed estate juice.', 'assets/images/01 (18).jpeg', '[\"assets\\/images\\/01 (18).jpeg\",\"assets\\/images\\/01 (19).jpeg\",\"assets\\/images\\/01 (20).jpeg\",\"assets\\/images\\/01 (27).jpeg\"]', 1, 1, '2026-09-14 01:08:16'),
(2, 'Starlit Hearth & Folklore', 'Twilight slate stone fires, hot cardamom brews, and ancestral mountain folklore.', 'EVENING RITUAL', 'Twilight ??? Night', 'Every Evening ??? 07:00 PM to 09:30 PM', 'Central Amphitheater & Stone Hearth Courtyard', 'All residing guests seeking cozy evening tranquility', 'Warm jacket or fleece sweater, camera for starry night photography.', 'Gather around an open slate fire beneath an unpolluted Milky Way sky. Unwind with hot cardamom spiced brews, roasted corn, and quiet acoustic music.', 'As dusk blankets the Western Ghats with deep indigo mist and the high-range night turns crisp and cold, gather around our open slate stone fire pit beneath an unpolluted Milky Way sky. Warm your hands against dancing flames of teak embers and breathe in the rich aroma of mountain wood smoke. Sip piping hot cardamom and crushed ginger spiced mountain tea, enjoy wood-roasted sweet farm corn sprinkled with Marayoor sea salt, and listen to timeless legends of Kanthalloor???s ancient megalithic dolmens and tribal mountain lore told by indigenous elders.', '[\"Open slate hearth campfire beneath dark celestial skies\",\"Steaming Marayoor cardamom-ginger spiced brew & roasted farm corn\",\"Folk stories of tribal ancestors and high-range wildlife legends\",\"Acoustic native music and tranquil meditation by the embers\"]', 'Unlimited cardamom spiced farm tea, fire-roasted sweet corn, handwoven wool shawls for the mountain chill.', 'assets/images/01 (30).jpeg', '[\"assets\\/images\\/01 (30).jpeg\",\"assets\\/images\\/01 (31).jpeg\",\"assets\\/images\\/01 (32).jpeg\",\"assets\\/images\\/01 (7).jpeg\"]', 2, 1, '2026-09-14 01:08:16'),
(3, 'Vernacular Mud & Clay Workshops', 'Ground your hands in red earth, vetiver straw, and ancestral thermal architecture.', 'WORKSHOP', '2.5 Hours ??? Afternoon', 'Tuesdays, Thursdays & Saturdays ??? 02:30 PM to 05:00 PM', 'The Artisan Cob Studio & Clay Courtyard', 'Adults, architecture buffs, curious creative souls, and kids', 'Comfortable clothes you do not mind getting clay on, slip-on shoes.', 'Get hands-on with native red earth. Learn the ancient alchemy of straw, clay, and terracotta to discover how homes can breathe without mechanical cooling.', 'Connect deeply with the living earth beneath your feet. In this deeply tactile and grounding workshop, our master vernacular builders introduce you to the timeless art of earthen cob construction. Discover how native red earth, fine river sand, chopped vetiver grass, and slaked lime create breathable, thermally stable walls that keep interiors cool by day and cozy through chilly mountain nights. Knead the clay mix, sculpt miniature wall alcoves, and try your hand at smooth terracotta plastering using traditional wooden floats.', '[\"Hands-on mixing of native red clay, lime plaster, and vetiver straw\",\"Understanding thermal physics and breathable zero-carbon design\",\"Sculpting earthen wall niches, decorative reliefs & pottery forms\",\"Mentored by veteran native cob and thatch craftsmen\"]', 'Natural clay sculpting materials, protective studio aprons, traditional herbal tea and farm refreshment.', 'assets/images/01 (6).jpeg', '[\"assets\\/images\\/01 (6).jpeg\",\"assets\\/images\\/01 (1).jpeg\",\"assets\\/images\\/01 (17).jpeg\",\"assets\\/images\\/01 (14).jpeg\"]', 3, 1, '2026-09-14 01:08:16'),
(4, 'Sunrise High-Ridge Valley Trek', 'Ascend misty high-range ridges to witness golden dawn across the Anaimudi peaks.', 'ADVENTURE', '3.5 Hours ??? Sunrise', 'Daily Departure at 05:45 AM Sharp (Duration: 3.5 Hours)', 'Departs from Sanctuary Welcome Lounge', 'Guests with moderate fitness levels (beginner-to-intermediate trail)', 'Sturdy walking / hiking footwear, windbreaker or jacket, reusable water flask.', 'Ascend through sandalwood forests and misty tea fringes to witness the sunrise break across the Anaimudi peak range with fresh mountain tea.', 'Begin before first light, ascending along ancient forest trails through fragrant wild lemongrass meadows, private sandalwood groves, and emerald tea estate fringes. Reach the panoramic ridge just as the first amber rays ignite the mist rolling off the Anaimudi peak range and the expansive Marayoor valley below. Enjoy freshly steeped estate black tea poured from thermos flasks with hot organic harvest pastries atop the cliff while spotting rare high-altitude birds such as the Nilgiri Pipit and Malabar Whistling Thrush.', '[\"Guided 5km sunrise trek through sandalwood & tea estate frontiers\",\"Breathtaking 360-degree dawn panorama across the Western Ghats\",\"Cliffside tea ceremony with freshly steeped high-altitude black tea\",\"Birdwatching & wildlife tracking with our native naturalist\"]', 'Hand-carved wooden trekking pole, thermos mountain tea, organic fruit & nut energy packs, binoculars.', 'assets/images/01 (33).jpeg', '[\"assets\\/images\\/01 (33).jpeg\",\"assets\\/images\\/01 (34).jpeg\",\"assets\\/images\\/01 (35).jpeg\",\"assets\\/images\\/01 (28).jpeg\"]', 4, 1, '2026-09-14 01:08:16');

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
(1, 'breakfast', 'Signature Heritage Dosa Set', 'Crispy Ghee Dosas with 3 Stone-Ground Chutneys & Claypot Sambar', '220.00', 'Per Set ??? Farm Breakfast', 'Fermented batter of organic red rice and black lentils, ladled onto seasoned cast-iron skillets and crisped with pure A2 farm ghee. Served piping hot on a fresh plantain leaf with three distinctive regional chutneys.', '3 Crispy Golden Ghee Dosas\nSpiced Potato Podi Masala\nFresh Coconut-Mint Chutney\nRoasted Tomato & Garlic Chutney\nShallot & Kanthari White Chutney\nPiping Hot Drumstick Sambar', 'veg', 'ESTATE SIGNATURE', 'assets/images/food_dosa_set.jpg', '[\"assets\\/images\\/food_dosa_set.jpg\",\"assets\\/images\\/food_appam_stew.jpg\"]', 1, 1, '2026-09-16 17:13:21'),
(2, 'breakfast', 'Clay-Baked Appam & Vegetable Ishtu', 'Lacy Fermented Rice Crepes with Farm Coconut Milk Stew', '240.00', 'Per Set', 'Soft, pillowy centers with delicate paper-thin crispy lace edges baked in traditional earthen appachattis. Accompanied by fragrant coconut milk stew simmered with heirloom potatoes, baby carrots, and sweet garden peas.', '4 Lacy Steamed Appams\nFresh Coconut Milk Vegetable Ishtu\nRoasted Coconut Chammanthi\nCardamom Spiced Chai', 'veg', 'VILLAGE CLASSIC', 'assets/images/food_appam_stew.jpg', '[\"assets\\/images\\/food_appam_stew.jpg\",\"assets\\/images\\/food_dosa_set.jpg\"]', 2, 1, '2026-09-16 17:13:21'),
(3, 'breakfast', 'Heritage Red Rice Puttu & Kadala Curry', 'Steamed Bamboo Puttu Cylinders with Spiced Black Chickpea Gravy', '210.00', 'Per Set', 'Coarsely ground organic red matta rice flour layered with freshly grated coconut and steamed in bamboo hollows. Paired with rich black chickpea gravy roasted in native coconut oil.', '2 Bamboo Steamed Puttu Cylinders\nSlow-Braised Kadala Curry\nSmall Ripe Farm Banana\nPapadam & Ghee', 'veg', 'ORGANIC GRAIN', 'assets/images/food_puttu_kadala.jpg', '[\"assets\\/images\\/food_puttu_kadala.jpg\",\"assets\\/images\\/food_appam_stew.jpg\"]', 3, 1, '2026-09-16 17:13:21'),
(4, 'lunch', 'Kanthalloor Earthen Claypot Sadya', 'Full Traditional Harvest Feast Served on Fresh Plantain Leaf', '480.00', 'Per Person Feast', 'A ceremonial organic banquet celebrating the biodiverse harvest of Kanthalloor. Every side is slow-simmered in native red clay pots over wood embers using cold-pressed coconut oil.', 'Steamed Organic Red Matta Rice\nTraditional Mixed Vegetable Avial\nFarm Greens & Coconut Thoran\nSlow-Simmered Drumstick Sambar\nCurd-Tempered Pulissery\nCrispy Mountain Banana Chips & Sarkara Varatti\nStone-Ground Ginger Pickle (Inji Curry)\nCrisp Urud Papadam\nRich Marayoor Jaggery & Rice Payasam', 'veg', 'CHEF\'S HARVEST FEAST', 'assets/images/food_kerala_sadya.jpg', '[\"assets\\/images\\/food_kerala_sadya.jpg\",\"assets\\/images\\/food_jackfruit_curry.jpg\"]', 1, 1, '2026-09-16 17:13:21'),
(5, 'lunch', 'Woodfire Jackfruit & Lentil Curry Set', 'Tender Raw Chakka Braised in Roasted Coconut & Mountain Spices', '360.00', 'Per Set', 'Tender heirloom jackfruit harvested from century-old trees on the sanctuary slopes, braised slowly with toasted shallots, coriander, and freshly grated coconut.', 'Tender Jackfruit Varutharacha Curry\nFragrant Jeera Samba Rice\nRaw Banana Podimas\nSun-Dried Chili Buttermilk (Moru)', 'veg', 'HEIRLOOM FORAGED', 'assets/images/food_jackfruit_curry.jpg', '[\"assets\\/images\\/food_jackfruit_curry.jpg\",\"assets\\/images\\/food_kerala_sadya.jpg\"]', 2, 1, '2026-09-16 17:13:21'),
(6, 'snacks', 'High-Range Evening Chai & Farm Fritters Set', 'Piping Hot Marayoor Cardamom Chai with Sweet & Savory Estate Bites', '160.00', 'Tea & Bites Set', 'Gather on the veranda as the afternoon mist rolls into the valley. Enjoy steaming cardamom milk tea poured from brass tumblers, alongside golden nendran banana fritters and crisp lentil vadas.', 'Steaming Marayoor Cardamom Spiced Milk Tea\n2 Golden Pazham Pori (Crispy Banana Fritters)\n2 Crispy Medu Uzhunnu Vada\nFresh Coconut & Green Chili Chutney', 'veg', 'TWILIGHT RITUAL', 'assets/images/food_evening_snacks.jpg', '[\"assets\\/images\\/food_evening_snacks.jpg\"]', 1, 1, '2026-09-16 17:13:21'),
(7, 'snacks', 'Steamed Sweet Ela Ada & Herbal Infusion', 'Banana Leaf Steamed Rice Parcels Stuffed with Marayoor Jaggery', '180.00', 'Per Set', 'Delicate thin rice dough pockets filled with a rich filling of freshly grated organic coconut, crushed cardamom, and pure GI-tagged Marayoor dark molasses jaggery, wrapped in fragrant banana leaves and steamed.', '2 Warm Banana-Leaf Steamed Ela Ada\nHot Ginger & Lemongrass Infusion\nRoasted Salted Cashews', 'veg', 'TRADITIONAL DELICACY', 'assets/images/food_evening_snacks.jpg', '[\"assets\\/images\\/food_evening_snacks.jpg\"]', 2, 1, '2026-09-16 17:13:21'),
(8, 'dinner', 'Twilight Hearth Stew & Malabar Porotta Set', 'Aromatic Farm Vegetable Stew with Flaky Layered Hearth Breads', '390.00', 'Per Set', 'Served beside the crackling embers of the open hearth. Layered artisanal porottas or soft rice pathiri served with slow-cooked vegetable and wild mushroom stew scented with whole cinnamon and crushed black pepper.', '3 Golden Layered Artisanal Porottas\nEarthen Pot Coconut-Vegetable Stew\nGrilled Forest Mushroom Kurma\nCaramelized Onion & Tomato Relish', 'veg', 'CAMPFIRE SPECIAL', 'assets/images/food_appam_stew.jpg', '[\"assets\\/images\\/food_appam_stew.jpg\",\"assets\\/images\\/food_jackfruit_curry.jpg\"]', 1, 1, '2026-09-16 17:13:21'),
(9, 'dinner', 'Charcoal Roasted Mountain Root Platter', 'Blistered Sweet Potatoes, Tapioca & Sweet Corn with Kanthari Dip', '320.00', 'Sharing Platter', 'Wholesome high-altitude root vegetables and tender corn on the cob roasted over aromatic teakwood charcoal. Served with stone-crushed bird\'s eye chili and raw shallot chutney.', 'Woodfire Blistered Farm Tapioca (Kappa)\nRoasted Sweet Mountain Potatoes\nCharred Sweet Corn Cobs with Rock Salt & Lime\nStone-Ground Kanthari Chili & Virgin Coconut Oil Dip', 'veg', 'WOODFIRE ROAST', 'assets/images/food_jackfruit_curry.jpg', '[\"assets\\/images\\/food_jackfruit_curry.jpg\",\"assets\\/images\\/food_puttu_kadala.jpg\"]', 2, 1, '2026-09-16 17:13:21');

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
  `category_slug` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `photos` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `gallery` (9 records)
INSERT INTO `gallery` (`id`, `title`, `caption`, `tag`, `category`, `category_slug`, `image_url`, `photos`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'High Canopy Treehouse in Mist', 'Perched 30 feet above the forest floor among silver oaks, overlooking rolling valley clouds.', 'CANOPY DWELLING ?? 1,640M', 'Villas & Stays', 'dwellings', 'assets/images/treehouse_exterior.png', '[{\"src\":\"assets\\/images\\/treehouse_exterior.png\",\"title\":\"Canopy Treehouse in Morning Mist\",\"caption\":\"Rising 30 feet into the Kanthalloor sky with panoramic views of the eastern valley.\"},{\"src\":\"assets\\/images\\/treehouse_exterior_front.jpg\",\"title\":\"Cantilevered Teak Balcony\",\"caption\":\"Private forest deck facing morning sunrise and drifting cloud waves.\"},{\"src\":\"assets\\/images\\/treehouse_interior.png\",\"title\":\"Loft Artisan Bedroom Suite\",\"caption\":\"Warm reclaimed timber, panoramic glass walls, and handcrafted linens.\"},{\"src\":\"assets\\/images\\/treehouse_360_pano.jpg\",\"title\":\"360\\u00b0 Shola Forest Panorama\",\"caption\":\"Surrounded by dense forest foliage, birdsong, and crisp mountain breeze.\"},{\"src\":\"assets\\/images\\/01 (10).jpeg\",\"title\":\"Twilight Balcony Glow\",\"caption\":\"Soft evening lighting filtering through the tree canopy as darkness falls.\"},{\"src\":\"assets\\/images\\/01 (25).jpeg\",\"title\":\"Morning Fog Horizon\",\"caption\":\"Awakening above the cloud blanket across the Western Ghats range.\"}]', 1, 1, '2026-09-14 01:08:16'),
(2, 'Hand-Sculpted Cob Mudhouse', 'Naturally insulated clay and sand architecture with private herbal garden courtyards.', 'EARTHEN ARCHITECTURE', 'Handcrafted Living', 'dwellings', 'assets/images/mudhouse_exterior.png', '[{\"src\":\"assets\\/images\\/mudhouse_exterior.png\",\"title\":\"Cob Mudhouse Cottage Frontage\",\"caption\":\"Naturally breathable earthen walls that maintain cool temperatures by day and warmth by night.\"},{\"src\":\"assets\\/images\\/mudhouse_interior.png\",\"title\":\"Earthen Living Room & Hearth\",\"caption\":\"Organic textures, slate stone floors, and hand-carved wooden fixtures.\"},{\"src\":\"assets\\/images\\/01 (7).jpeg\",\"title\":\"Terracotta Veranda & Cob Wall\",\"caption\":\"Traditional clay roof tiles with sweeping views of the organic vegetable terraces.\"},{\"src\":\"assets\\/images\\/01 (20).jpeg\",\"title\":\"Mudhouse by Campfire Hearth\",\"caption\":\"Gentle crackle of open fires warming the terracotta courtyard at twilight.\"},{\"src\":\"assets\\/images\\/01 (17).jpeg\",\"title\":\"Herbal Garden Courtyard Walk\",\"caption\":\"Fragrant rosemary, lavender, and lemongrass bordering the mudhouse paths.\"},{\"src\":\"assets\\/images\\/01 (2).jpeg\",\"title\":\"Artisan Handcrafted Bedroom\",\"caption\":\"Peaceful sanctuary designed for deep rest away from digital screens.\"}]', 2, 1, '2026-09-14 01:08:16'),
(3, 'The Western Ghats Vista', 'High-altitude horizon cloaked in shifting clouds and untouched shola wilderness.', 'ALPINE HORIZON', 'Landscape', 'landscape', 'assets/images/01 (25).jpeg', '[{\"src\":\"assets\\/images\\/01 (25).jpeg\",\"title\":\"High-Altitude Valley Horizon\",\"caption\":\"Sweeping vistas of the Anaimudi foothills and dense mountain slopes.\"},{\"src\":\"assets\\/images\\/01 (26).jpeg\",\"title\":\"Misty Ridges & Cloud Waves\",\"caption\":\"Clouds sweeping through the valley canyons during early morning hours.\"},{\"src\":\"assets\\/images\\/01 (27).jpeg\",\"title\":\"Perennial Brook Stream\",\"caption\":\"Pure mineral water cascading through mossy boulders across the sanctuary.\"},{\"src\":\"assets\\/images\\/01 (28).jpeg\",\"title\":\"Golden Hour Mountain Silhouette\",\"caption\":\"Sunlight breaking across the eastern mountain ridge.\"},{\"src\":\"assets\\/images\\/01 (29).jpeg\",\"title\":\"Ancient Shola Forest Grove\",\"caption\":\"Centuries-old biodiversity hotspot home to rare flora and fauna.\"},{\"src\":\"assets\\/images\\/01 (30).jpeg\",\"title\":\"Twilight Valley Panorama\",\"caption\":\"Serene purple twilight settling over the high-range tea gardens.\"}]', 3, 1, '2026-09-14 01:08:16'),
(4, 'Woodfire Claypot Lunch', 'Pure farm-to-table cooking over slow embers using hand-ground spices and organic produce.', 'EARTHEN GASTRONOMY', 'Gastronomy', 'gastronomy', 'assets/images/01 (3).jpeg', '[{\"src\":\"assets\\/images\\/01 (3).jpeg\",\"title\":\"Claypot Simmering on Open Fire\",\"caption\":\"Slow-cooked heirloom grains and vegetables infused with natural wood smoke.\"},{\"src\":\"assets\\/images\\/food_kerala_sadya.jpg\",\"title\":\"Traditional Farm Feast (Sadya)\",\"caption\":\"Served on fresh banana leaves with organic estate-grown vegetables and spices.\"},{\"src\":\"assets\\/images\\/food_dosa_set.jpg\",\"title\":\"Crisp Morning Dosa & Chutneys\",\"caption\":\"Stone-ground fermented batter with fresh mountain coconut and mint chutneys.\"},{\"src\":\"assets\\/images\\/food_evening_snacks.jpg\",\"title\":\"Twilight Spiced Tea & Snacks\",\"caption\":\"Steaming cardamom mountain tea paired with hot steamed herbal snacks.\"},{\"src\":\"assets\\/images\\/01 (4).jpeg\",\"title\":\"Open-Air Forest Dining Deck\",\"caption\":\"Savoring wholesome organic meals surrounded by the rustle of leaves.\"},{\"src\":\"assets\\/images\\/01 (5).jpeg\",\"title\":\"The Earthen Kitchen Hearth\",\"caption\":\"Where age-old culinary secrets and slow nourishment come to life.\"}]', 4, 1, '2026-09-14 01:08:16'),
(5, 'Organic Winter Apple Orchards', 'Ancient heirloom trees yielding sweet, pesticide-free mountain apples each winter.', 'ESTATE HARVEST', 'Orchards', 'orchards', 'assets/images/01 (1).jpeg', '[{\"src\":\"assets\\/images\\/01 (1).jpeg\",\"title\":\"Terraced Apple Orchard Rows\",\"caption\":\"Heirloom apple trees flourishing in the sub-tropical temperate microclimate of Kanthalloor.\"},{\"src\":\"assets\\/images\\/01 (11).jpeg\",\"title\":\"Fresh Hand-Plucked Apples\",\"caption\":\"Crisp, sweet, and bursting with natural flavor straight from the tree.\"},{\"src\":\"assets\\/images\\/01 (12).jpeg\",\"title\":\"Lush Passion Fruit Trellises\",\"caption\":\"Organic passion fruit vines draping over natural bamboo pergolas.\"},{\"src\":\"assets\\/images\\/01 (13).jpeg\",\"title\":\"Wild Berry & Plum Trees\",\"caption\":\"Sweet seasonal berries harvested for fresh jams and morning preserves.\"},{\"src\":\"assets\\/images\\/01 (14).jpeg\",\"title\":\"Orchard Walking Pathway\",\"caption\":\"Stone-lined walking paths winding through the organic fruit groves.\"}]', 5, 1, '2026-09-14 01:08:16'),
(6, 'Stargazing by the Cob Hearth', 'Night skies at 1,600m altitude illuminated only by campfire crackle and constellations.', 'NIGHT SKY SANCTUARY', 'Nightscape', 'landscape', 'assets/images/01 (20).jpeg', '[{\"src\":\"assets\\/images\\/01 (20).jpeg\",\"title\":\"Cob Hearth Campfire Circle\",\"caption\":\"Gathering around the warm coals under an unpolluted Milky Way galaxy.\"},{\"src\":\"assets\\/images\\/01 (21).jpeg\",\"title\":\"Starlit Mountain Canopy\",\"caption\":\"Zero light pollution allows clear visibility of shooting stars and constellations.\"},{\"src\":\"assets\\/images\\/01 (22).jpeg\",\"title\":\"Lantern-Lit Stone Paths\",\"caption\":\"Subtle warm ambient lighting guiding your way through the night forest.\"},{\"src\":\"assets\\/images\\/01 (23).jpeg\",\"title\":\"Evening Fire Ritual\",\"caption\":\"Sharing stories, folklore, and quiet acoustic music by the hearth.\"},{\"src\":\"assets\\/images\\/01 (24).jpeg\",\"title\":\"Moonlight Over the Valleys\",\"caption\":\"Silvery moonlight casting a tranquil glow over the mountain contours.\"}]', 6, 1, '2026-09-14 01:08:16'),
(7, 'Morning Dew on Passion Fruit Vines', 'Wild pollinators and lush flora flourishing in our certified chemical-free sanctuary.', 'BOTANICAL HARMONY', 'Flora', 'orchards', 'assets/images/01 (15).jpeg', '[{\"src\":\"assets\\/images\\/01 (15).jpeg\",\"title\":\"Morning Dew on Passion Fruit Vines\",\"caption\":\"Crystal dewdrops clinging to wild tendrils in the early dawn light.\"},{\"src\":\"assets\\/images\\/01 (16).jpeg\",\"title\":\"Wild Shola Orchids & Ferns\",\"caption\":\"Endemic ferns and rare orchids thriving in the moist mountain air.\"},{\"src\":\"assets\\/images\\/01 (18).jpeg\",\"title\":\"Medicinal Herb Sanctuary\",\"caption\":\"Cultivating tulsi, lemongrass, brahmi, and wild forest herbs.\"},{\"src\":\"assets\\/images\\/01 (19).jpeg\",\"title\":\"Canopy Epiphytes & Silver Oaks\",\"caption\":\"Lush mosses and air plants thriving on towering mountain trees.\"},{\"src\":\"assets\\/images\\/01 (31).jpeg\",\"title\":\"Brook-Side Wildflowers\",\"caption\":\"Vibrant blossoms lining the banks of the perennial forest brook.\"}]', 7, 1, '2026-09-14 01:08:16'),
(8, 'Living Mud Courtyard Veranda', 'Unpaved, breathable courtyards connecting guest quarters directly with the soil.', 'BIOPHILIC SPACES', 'Architecture', 'dwellings', 'assets/images/01 (7).jpeg', '[{\"src\":\"assets\\/images\\/01 (7).jpeg\",\"title\":\"Living Mud Courtyard Veranda\",\"caption\":\"Terracotta verandas designed for slow morning teas and peaceful contemplation.\"},{\"src\":\"assets\\/images\\/01 (8).jpeg\",\"title\":\"Natural Light & Breathable Clay\",\"caption\":\"Deep overhangs that keep out harsh sun while welcoming cool valley breezes.\"},{\"src\":\"assets\\/images\\/01 (9).jpeg\",\"title\":\"Earthen Seating Nook\",\"caption\":\"Cob benches sculpted directly out of native red clay and river sand.\"},{\"src\":\"assets\\/images\\/01 (32).jpeg\",\"title\":\"Hand-Cut Stone Pathways\",\"caption\":\"Meandering flagstone walkways harmoniously embedded in clover and grass.\"},{\"src\":\"assets\\/images\\/01 (33).jpeg\",\"title\":\"Sunrise on the Veranda\",\"caption\":\"Watch the morning mist dissipate from the comfort of a shaded patio.\"}]', 8, 1, '2026-09-14 01:08:16'),
(9, 'ehse', 'atr', 'SANCTUARY ARCHIVE', 'Landscape', NULL, 'assets/images/01 (1).jpeg', '[{\"src\":\"assets\\/images\\/01 (1).jpeg\",\"title\":\"ehse\",\"caption\":\"atr\"}]', 9, 1, '2026-09-15 00:33:42');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table structure for `room_ical_feeds`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `room_ical_feeds`;
CREATE TABLE `room_ical_feeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_slug` varchar(100) NOT NULL,
  `channel_name` varchar(100) NOT NULL,
  `feed_url` text NOT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `sync_status` varchar(50) DEFAULT 'pending',
  `sync_error` text DEFAULT NULL,
  `sync_events_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `single_room_rate` decimal(10,2) DEFAULT NULL,
  `extra_guest_rate` decimal(10,2) DEFAULT 1500.00,
  `extra_child_rate` decimal(10,2) DEFAULT 800.00,
  `elevation` varchar(100) DEFAULT NULL,
  `min_guests` int(11) DEFAULT 2,
  `base_guests` int(11) DEFAULT 2,
  `max_guests` int(11) DEFAULT 2,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `inventory_checklist` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `interior_360_url` varchar(255) DEFAULT NULL,
  `tour_stages_json` longtext DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `rooms` (10 records)
INSERT INTO `rooms` (`id`, `slug`, `stay_type`, `structure_type`, `title`, `rate_per_night`, `single_room_rate`, `extra_guest_rate`, `extra_child_rate`, `elevation`, `min_guests`, `base_guests`, `max_guests`, `description`, `amenities`, `inventory_checklist`, `image_url`, `interior_360_url`, `tour_stages_json`, `is_available`, `created_at`) VALUES
(15, 'mudhouse-stay', 'mudhouse', 'single_hut', 'Earthen Mudhouse Suite', '11500.00', '11500.00', '1500.00', '800.00', 'COB HERITAGE • 1,600M MSL', 1, 2, 4, 'Naturally thermal-insulated cob clay dwelling sculpted from native red earth, river sand, and straw. Features private sit-out and orchard veranda.', 'Hand-Carved Wooden Queen Bed, Natural Clay Cooler, Slate Hearth Fireplace, Terracotta Veranda, Private Organic Herb Garden, Forest Spring Water', NULL, 'assets/images/mudhouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-24 09:25:17'),
(16, 'canopy-treehouse', 'treehouse', 'single_hut', 'Luxury Canopy Treehouse', '14500.00', '14500.00', '1500.00', '800.00', '30FT ELEVATION • 1,620M MSL', 1, 2, 4, 'Suspended 30 feet above the forest floor within ancient trees. Crafted with wild teak timber, an open cantilevered deck, and expansive curved glass with panoramic mist views.', 'King Artisan Teak Bed, Mountain-View Cantilevered Deck, Solar-Heated Rain Shower, Handcrafted Herbal Teas, Ambient Fire Hearth, Telescope for Stargazing, Zero-Plastic Toiletries', NULL, 'assets/images/treehouse_exterior_front.jpg', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(17, 'duplex-suite-01', 'treehouse', 'duplex_hut', 'Duplex Chalet Suite 01', '24000.00', '14500.00', '1500.00', '800.00', 'UPPER RIDGE • 1,600M MSL', 2, 4, 8, 'High-elevation sanctuary duplex suite with morning mist exposure, two independent master bedrooms, private panoramic balconies, and shared lounge.', '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included', NULL, 'assets/images/treehouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(18, 'duplex-suite-02', 'treehouse', 'duplex_hut', 'Duplex Chalet Suite 02', '24000.00', '14500.00', '1500.00', '800.00', 'UPPER RIDGE • 1,600M MSL', 2, 4, 8, 'Two-tier alpine timber duplex overlooking rolling mountain cloudscapes with dual handcrafted king beds and private verandas.', '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included', NULL, 'assets/images/treehouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(19, 'duplex-suite-04', 'treehouse', 'duplex_hut', 'Duplex Chalet Suite 04', '24000.00', '14500.00', '1500.00', '800.00', 'NORTH RIDGE • 1,600M MSL', 2, 4, 8, 'High-elevation sanctuary suite with morning mist exposure, double bedrooms, and sweeping northern alpine views.', '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included', NULL, 'assets/images/treehouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(20, 'duplex-suite-05', 'treehouse', 'duplex_hut', 'Duplex Chalet Suite 05', '24000.00', '14500.00', '1500.00', '800.00', 'NORTH RIDGE • 1,600M MSL', 2, 4, 8, 'Upper ridge master duplex with unobstructed northern high range views, crafted timber ceilings, and forest-facing viewing deck.', '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included', NULL, 'assets/images/treehouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(21, 'duplex-suite-06', 'treehouse', 'duplex_hut', 'Duplex Chalet Suite 06', '24000.00', '14500.00', '1500.00', '800.00', 'PEAK CONTOUR • 1,600M MSL', 2, 4, 8, 'Peak-level luxury duplex chalet situated on the highest mountain contour with grand high-ceiling interiors and dual master suites.', '2 Master Bedrooms with Ensuite Baths, Dual Panoramic Balconies, Private Sun Lounge, Hearth Fireplace, Double Rain Showers, Farm Breakfast & Dinners Included', NULL, 'assets/images/treehouse_exterior.png', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(22, 'cottage-hut-03', 'woodhouse', 'single_hut', 'Pine Cottage Hut 03', '13500.00', '13500.00', '1500.00', '800.00', 'ORCHARD SLOPE • 1,600M MSL', 1, 2, 4, 'Cozy standalone pine timber cottage nestled along the western orchard slope with private garden patio and fireplace.', 'Handcrafted Queen Bed, Pine Veranda, Slate Hearth Fireplace, Solar Rain Shower, Orchard View Patio, Organic Bedding', NULL, 'assets/images/01 (25).jpeg', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(23, 'cottage-hut-02', 'woodhouse', 'single_hut', 'Pine Cottage Hut 02', '13500.00', '13500.00', '1500.00', '800.00', 'ORCHARD SLOPE • 1,600M MSL', 1, 2, 4, 'Private single cottage with handcrafted wooden furnishings, forest balcony, and direct access to organic apple trails.', 'Handcrafted Queen Bed, Pine Veranda, Slate Hearth Fireplace, Solar Rain Shower, Orchard View Patio, Organic Bedding', NULL, 'assets/images/01 (25).jpeg', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24'),
(25, 'grand-wooden-alpine-house', 'woodhouse', 'single_hut', 'Grand Wooden Alpine House', '15500.00', '15500.00', '1500.00', '800.00', 'EAST MEADOW • 1,600M MSL', 1, 2, 5, 'Classic pinewood mountain house with expansive living hall, artisan craftsmanship, vaulted timber ceiling, and wide sun deck.', 'King Artisan Bed, Mountain View Sun Deck, Solar Rain Shower, Woodfire Stove, Tea Bar, Stargazing Binoculars', NULL, 'assets/images/01 (26).jpeg', 'assets/images/treehouse_360_pano.jpg', NULL, 1, '2026-09-30 14:35:24');

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
  `icon_class` varchar(100) DEFAULT NULL,
  `pin_color` varchar(50) DEFAULT NULL,
  `is_stay` tinyint(1) DEFAULT 0,
  `linked_room_slug` varchar(100) DEFAULT NULL,
  `structure_type` varchar(50) DEFAULT 'single_hut',
  `stay_price` decimal(10,2) DEFAULT NULL,
  `elevation` varchar(100) DEFAULT '1,600M MSL',
  `temperature` varchar(100) DEFAULT '18??C Alpine Breeze',
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `sanctuary_spots` (13 records)
INSERT INTO `sanctuary_spots` (`id`, `spot_number`, `title`, `subtitle_tag`, `category`, `icon_class`, `pin_color`, `is_stay`, `linked_room_slug`, `structure_type`, `stay_price`, `elevation`, `temperature`, `description`, `aroma`, `sound`, `image_url`, `photos`, `cta_text`, `cta_link`, `x_coord`, `y_coord`, `display_order`, `is_active`, `created_at`) VALUES
(25, 2, 'Duplex Chalet Suite 01', NULL, 'stays', 'fa-solid fa-layer-group', '#06b6d4', 1, 'duplex-suite-01', 'duplex_hut', '24000.00', '1,600M MSL', '18??C Alpine Breeze', 'Upper cedar deck with panoramic plantation vistas and dual master suites.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '46.70', '32.10', 2, 1, '2026-09-30 11:50:49'),
(26, 3, 'Duplex Chalet Suite 02', NULL, 'stays', 'fa-solid fa-layer-group', '#06b6d4', 1, 'duplex-suite-02', 'duplex_hut', '24000.00', '1,600M MSL', '18??C Alpine Breeze', 'Independent two-tier luxury chalet overlooking the central organic valley.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '61.70', '32.90', 3, 1, '2026-09-30 11:50:49'),
(27, 4, 'Duplex Chalet Suite 04', NULL, 'stays', 'fa-solid fa-layer-group', '#06b6d4', 1, 'duplex-suite-04', 'duplex_hut', '24000.00', '1,600M MSL', '18??C Alpine Breeze', 'High-elevation sanctuary suite with morning mist exposure and double bedrooms.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '36.30', '10.20', 4, 1, '2026-09-30 11:50:49'),
(28, 5, 'Duplex Chalet Suite 05', NULL, 'stays', 'fa-solid fa-layer-group', '#06b6d4', 1, 'duplex-suite-05', 'duplex_hut', '24000.00', '1,600M MSL', '18??C Alpine Breeze', 'Upper ridge master duplex with unobstructed northern high range views.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '46.90', '10.20', 5, 1, '2026-09-30 11:50:49'),
(29, 6, 'Duplex Chalet Suite 06', NULL, 'stays', 'fa-solid fa-layer-group', '#06b6d4', 1, 'duplex-suite-06', 'duplex_hut', '24000.00', '1,600M MSL', '18??C Alpine Breeze', 'Peak-level luxury duplex chalet situated on the highest mountain contour.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '57.60', '10.20', 6, 1, '2026-09-30 11:50:49'),
(30, 7, 'Cottage Hut 03', NULL, 'stays', 'fa-solid fa-house-chimney', '#10b981', 1, 'cottage-hut-03', 'single_hut', '13500.00', '1,600M MSL', '18??C Alpine Breeze', 'Cozy standalone pine timber cottage nestled along the western orchard slope.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '32.80', '47.10', 7, 1, '2026-09-30 11:50:49'),
(31, 8, 'Cottage Hut 02', NULL, 'stays', 'fa-solid fa-house-chimney', '#10b981', 1, 'cottage-hut-02', 'single_hut', '13500.00', '1,600M MSL', '18??C Alpine Breeze', 'Private single cottage with handcrafted wooden furnishings and forest balcony.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '39.50', '49.40', 8, 1, '2026-09-30 11:50:49'),
(32, 9, 'Earthen Mudhouse Suite', NULL, 'stays', 'fa-solid fa-mountain-sun', '#ea580c', 1, 'mudhouse-stay', 'single_hut', '11500.00', '1,600M MSL', '18??C Alpine Breeze', 'Naturally thermal-insulated cob clay dwelling with private sit-out and veranda.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '45.80', '50.70', 9, 1, '2026-09-30 11:50:49'),
(33, 10, 'Grand Wooden Alpine House', NULL, 'stays', 'fa-solid fa-tree', '#10b981', 1, 'grand-wooden-alpine-house', 'single_hut', '15500.00', '1,600M MSL', '18??C Alpine Breeze', 'Classic pinewood mountain house with expansive living hall and deck.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '61.20', '56.60', 10, 1, '2026-09-30 11:50:49'),
(34, 11, 'Recreation Area & Campfire Glade', NULL, 'amenities', 'fa-solid fa-fire', '#d97706', 0, NULL, 'single_hut', NULL, '1,600M MSL', '18??C Alpine Breeze', 'Central open lawn for evening barbecues, acoustic music, and stargazing.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '33.50', '59.40', 11, 1, '2026-09-30 11:50:49'),
(35, 12, 'Kids Adventure Park & Swings', NULL, 'amenities', 'fa-solid fa-shapes', '#8b5cf6', 0, NULL, 'single_hut', NULL, '1,600M MSL', '18??C Alpine Breeze', 'Shaded outdoor play glade with wooden swings, seesaws, and grassy lawns.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '46.10', '68.60', 12, 1, '2026-09-30 11:50:49'),
(37, 14, 'Farmhouse Kitchen & Dining Hub', NULL, 'dining', 'fa-solid fa-utensils', '#f59e0b', 0, NULL, 'single_hut', NULL, '1,600M MSL', '18??C Alpine Breeze', 'Central open hearth serving organic farm-to-table breakfast, lunch and dinner.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '66.70', '74.00', 14, 1, '2026-09-30 11:50:49'),
(38, 15, 'Natural Plunge Pool & Spring Bath', NULL, 'amenities', 'fa-solid fa-person-swimming', '#0ea5e9', 0, NULL, 'single_hut', NULL, '1,600M MSL', '18??C Alpine Breeze', 'Fresh crystal-clear natural mountain spring water pool.', NULL, NULL, '', '[\"\"]', 'Explore Details', '#rooms', '50.70', '86.90', 15, 1, '2026-09-30 11:50:49');

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
(1, 'Monsoon Magic', 'June - September', 'Lush, deep green landscapes, rising mist, heavy refreshing rainfall, and crisp cold mountain breeze.', 'assets/images/01 (9).jpeg', 1, 1, '2026-09-15 01:00:33'),
(2, 'Cozy Winter', 'October - February', 'Chilly mist, clear bright blue skies, warm sunlit afternoons, and snug campfire nights under starry skies.', 'assets/images/01 (8).jpeg', 2, 1, '2026-09-15 01:00:33'),
(3, 'Harvest Season', 'March - May', 'Crisp mountain breezes, blooming orchards of apples, oranges, and plums, and vibrant farm life in full motion.', 'assets/images/01 (19).jpeg', 3, 1, '2026-09-15 01:00:33'),
(4, 'Summer Bloom', 'April - June', 'Pleasant, breezy weather. Kanthalloor acts as a cool refuge from the sweltering heat of the plains.', 'assets/images/01 (33).jpeg', 4, 1, '2026-09-15 01:00:33');

-- -------------------------------------------------------------
-- Table structure for `settings`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(191) NOT NULL,
  `setting_value` mediumtext DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `settings` (103 records)
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('bank_account_holder', 'Food Forest Eco Sanctuary'),
('bank_account_number', '40982314981'),
('bank_account_type', 'Current Account'),
('bank_branch', 'Munnar / Kanthalloor Branch'),
('bank_ifsc', 'SBIN0070123'),
('bank_name', 'State Bank of India'),
('bank_qr_image', 'assets/images/foodforest_upi_qr.svg'),
('bank_upi_id', 'foodforest@upi'),
('bill_footer_notes', 'All payments via UPI, IMPS, or NEFT must be confirmed with transaction ID. For official GST tax invoices, notify concierge prior to checkout.'),
('bill_show_bank_details', '1'),
('bill_show_qr_code', '1'),
('checkin_time', '02:00 PM'),
('checkout_time', '11:00 AM'),
('concierge_email', 'concierge@foodforestkanthalloor.com'),
('concierge_hours', '08:00 AM – 09:00 PM'),
('concierge_phone', '+91 92345 67890'),
('concierge_whatsapp', '919234567890'),
('currency_symbol', '₹'),
('estate_name', 'Food Forest'),
('estate_tagline', 'Eco Sanctuary & Agro Farmstay'),
('experiences_badge', 'ACTIVITIES'),
('experiences_desc', 'Connect deeply with the pulse of Kanthalloor. Each experience is handcrafted to immerse you in vernacular craftsmanship, mountain wilderness, and restorative tranquility.'),
('experiences_title', 'Rituals of the High Range'),
('footer_badge1_desc', 'Zero synthetic pesticides or fertilizers'),
('footer_badge1_icon', 'fa-solid fa-seedling'),
('footer_badge1_title', '100% Organic Soil'),
('footer_badge2_desc', 'Traditional low-carbon architecture'),
('footer_badge2_icon', 'fa-solid fa-house-chimney'),
('footer_badge2_title', 'Vernacular Cob Clay'),
('footer_badge3_desc', 'Filtered natural water, zero single-use plastic'),
('footer_badge3_icon', 'fa-solid fa-droplet'),
('footer_badge3_title', 'Mountain Spring Water'),
('footer_badge4_desc', 'Crafted & staffed by native artisans'),
('footer_badge4_icon', 'fa-solid fa-people-roof'),
('footer_badge4_title', 'Local Community First'),
('footer_concierge_badge_text', 'Estate Concierge Available'),
('footer_contact_title', 'Direct Concierge'),
('footer_copyright_text', '© {year} Food Forest Sanctuary Kanthalloor. Crafted for conscious travelers.'),
('footer_gazette_desc', 'Receive private seasonal bulletins on apple harvests, wild honey collection, and intimate villa releases.'),
('footer_gazette_msg', 'Thank you for subscribing to our Gazette.'),
('footer_gazette_placeholder', 'Enter your email address'),
('footer_gazette_title', 'Sanctuary Gazette'),
('footer_legal1_title', 'Privacy Charter'),
('footer_legal1_url', '#'),
('footer_legal2_title', 'Sustainability Policy'),
('footer_legal2_url', '#'),
('footer_legal3_title', 'Guest Etiquette'),
('footer_legal3_url', '#'),
('footer_nav_links', 'Our Story & Ethos|#welcome\nCanopy Treehouse|#rooms-experience\nEarthen Mudhouse|#rooms-experience\nActivities|#experiences\nFood Menu & Hearth|#dining\nGuest Portal & Receipts|guest_portal.php|fa-solid fa-key|1\nVisual Gallery|#gallery\nEstate Landscape|#sanctuary\nGuest Stories|#testimonials'),
('footer_nav_title', 'The Sanctuary');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('footer_reserve_btn_text', 'Reserve Your Sanctuary'),
('footer_staff_label', 'Staff Portal'),
('footer_staff_url', 'admin/'),
('footer_tagline', 'An intimate sanctuary where ancestral architecture meets untamed nature. Rediscover silence, wholesome farm-to-table flavors, and deep mountain tranquility.'),
('footer_whatsapp_label', '(Instant Concierge)'),
('gst_number', '32AAECF1234M1Z5'),
('gst_rate_percentage', '17.62'),
('hero_bg_image', 'assets/images/01 (25).jpeg'),
('hero_desc', 'Tucked deep in the misty hills and organic orchards of Kanthalloor. Experience private earthen mudhouses, soaring canopy treehouses, and nourishing farm gastronomy cooked slowly over wood fires.'),
('hero_eyebrow', 'KANTHALLOOR, KERALA, PRIVATE ECO-SANCTUARY'),
('hero_tag_climate', '10°C MISTY EVENINGS • YEAR-ROUND NATURAL CHILL'),
('hero_title', 'Where Earth Breathes & Time Stands Still.'),
('location', 'Kanthalloor High Range, Idukki, Kerala - 685620'),
('menu_badge', 'ESTATE GASTRONOMY & ORGANIC DINING'),
('menu_desc', 'Food at Food Forest is a ritual. Cooked in indigenous clay pots over aromatic wood hearths, every meal is prepared with ingredients harvested minutes prior from our own organic soil.'),
('menu_desc_breakfast', 'Morning in the Orchards ??? Fresh farm juices, lacy hoppers & stone-ground breakfast sets'),
('menu_desc_dinner', 'Twilight Campfire Dining ??? Slow-simmered stews, charcoal grills & jaggery desserts by the embers'),
('menu_desc_lunch', 'Claypot Hearth Feast ??? Heirloom red rice, seasonal thorans & traditional banana-leaf sadya'),
('menu_desc_snacks', 'Plantation Tea Ritual ??? Steaming Marayoor cardamom chai, hot banana fritters & steamed ela ada'),
('menu_time_breakfast', '07:30 AM ??? 10:00 AM'),
('menu_time_dinner', '07:30 PM ??? 10:00 PM'),
('menu_time_lunch', '12:30 PM ??? 02:30 PM'),
('menu_time_snacks', '04:30 PM ??? 06:30 PM'),
('menu_title', 'The Forest Hearth & Living Menu'),
('sanctuary_map_config', '{\"mode\":\"custom_routes\",\"entrance\":{\"enabled\":true,\"label\":\"MAIN ENTRANCE\",\"x\":82,\"y\":92},\"exit\":{\"enabled\":false,\"label\":\"ESTATE EXIT\",\"x\":85,\"y\":92},\"custom_waypoints\":[{\"id\":\"wp_r_turn\",\"label\":\"Main Road Turn\",\"x\":81,\"y\":78},{\"id\":\"wp_r_up\",\"label\":\"Eastern Ascent\",\"x\":78,\"y\":45},{\"id\":\"wp_tr_corner\",\"label\":\"Northeast Bend\",\"x\":72,\"y\":20},{\"id\":\"wp_t_right\",\"label\":\"Top Ridge East\",\"x\":58,\"y\":8},{\"id\":\"wp_t_mid\",\"label\":\"High Peak Pass\",\"x\":46,\"y\":7},{\"id\":\"wp_t_left\",\"label\":\"Top Ridge West\",\"x\":34,\"y\":9},{\"id\":\"wp_tl_corner\",\"label\":\"Northwest Bend\",\"x\":22,\"y\":22},{\"id\":\"wp_l_mid\",\"label\":\"Western Ridge Trail\",\"x\":23,\"y\":46},{\"id\":\"wp_bl_curve\",\"label\":\"Southwest Orchard Turn\",\"x\":31,\"y\":64},{\"id\":\"wp_b_mid\",\"label\":\"Central Valley Junction\",\"x\":50,\"y\":73},{\"id\":\"wp_br_join\",\"label\":\"Lower Promenade Spur\",\"x\":73,\"y\":80},{\"id\":\"wp_mid_sub_start\",\"label\":\"Duplex Lane Junction\",\"x\":76.5,\"y\":39},{\"id\":\"wp_mid_sub_end\",\"label\":\"Duplex Lane Terminus\",\"x\":48,\"y\":38}],\"routes\":[{\"id\":\"route_main_loop\",\"name\":\"Main Estate Outer Loop Road\",\"color\":\"#D4AF37\",\"stroke_type\":\"dashed\",\"line_width\":3.4,\"is_closed\":true,\"nodes\":[{\"type\":\"entrance\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_r_turn\",\"curve\":\"arch_left\",\"curve_offset\":-10},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_r_up\",\"curve\":\"arch_left\",\"curve_offset\":-8},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_tr_corner\",\"curve\":\"arch_left\",\"curve_offset\":-15},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_t_right\",\"curve\":\"arch_left\",\"curve_offset\":-12},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_t_mid\",\"curve\":\"arch_left\",\"curve_offset\":-10},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_t_left\",\"curve\":\"arch_left\",\"curve_offset\":-12},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_tl_corner\",\"curve\":\"arch_left\",\"curve_offset\":-18},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_l_mid\",\"curve\":\"arch_left\",\"curve_offset\":-10},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_bl_curve\",\"curve\":\"arch_left\",\"curve_offset\":-14},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_b_mid\",\"curve\":\"arch_left\",\"curve_offset\":-10},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_br_join\",\"curve\":\"straight\",\"curve_offset\":0}]},{\"id\":\"route_duplex_sub_branch\",\"name\":\"Middle Duplex Sub-Road\",\"color\":\"#06B6D4\",\"stroke_type\":\"solid\",\"line_width\":3,\"is_closed\":false,\"nodes\":[{\"type\":\"waypoint\",\"waypoint_id\":\"wp_mid_sub_start\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":3,\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":2,\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_mid_sub_end\",\"curve\":\"straight\",\"curve_offset\":0}]},{\"id\":\"route_amenities_sub_branch\",\"name\":\"Recreation & Amenities Spur\",\"color\":\"#F59E0B\",\"stroke_type\":\"solid\",\"line_width\":3,\"is_closed\":false,\"nodes\":[{\"type\":\"waypoint\",\"waypoint_id\":\"wp_b_mid\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":11,\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":12,\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":13,\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":14,\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":15,\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"entrance\",\"curve\":\"straight\",\"curve_offset\":0}]}]}'),
('sanctuary_map_route_data', '{\"mode\":\"custom_routes\",\"entrance\":{\"enabled\":true,\"label\":\"MAIN ENTRANCE\",\"x\":90.9,\"y\":92.9},\"exit\":{\"enabled\":false,\"label\":\"ESTATE EXIT\",\"x\":85,\"y\":92},\"custom_waypoints\":[{\"id\":\"wp_1790749558577\",\"label\":\"Path Waypoint\",\"x\":79.3,\"y\":64.8},{\"id\":\"wp_1790749565238\",\"label\":\"Path Waypoint\",\"x\":77.5,\"y\":10.3},{\"id\":\"wp_1790749569320\",\"label\":\"Path Waypoint\",\"x\":20.2,\"y\":7.9},{\"id\":\"wp_1790749573281\",\"label\":\"Path Waypoint\",\"x\":20,\"y\":42.9},{\"id\":\"wp_1790749637081\",\"label\":\"Path Waypoint\",\"x\":77.8,\"y\":33.6},{\"id\":\"wp_1790751004632\",\"label\":\"Path Waypoint\",\"x\":46.2,\"y\":67.5},{\"id\":\"wp_1790751007649\",\"label\":\"Path Waypoint\",\"x\":55.3,\"y\":56.6},{\"id\":\"wp_1790751314463\",\"label\":\"Path Waypoint\",\"x\":51.1,\"y\":85},{\"id\":\"wp_1790751321346\",\"label\":\"Path Waypoint\",\"x\":85.9,\"y\":80.8},{\"id\":\"wp_1790751790628\",\"label\":\"Path Waypoint\",\"x\":66.4,\"y\":72.5},{\"id\":\"wp_1790751791446\",\"label\":\"Path Waypoint\",\"x\":69.9,\"y\":62.3}],\"routes\":[{\"id\":\"route_main_promenade\",\"name\":\"Main Sanctuary Walking Trail\",\"color\":\"#D4AF37\",\"stroke_type\":\"dashed\",\"line_width\":3.2,\"is_closed\":false,\"nodes\":[{\"type\":\"entrance\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790749558577\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790749565238\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790749569320\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790749573281\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790749558577\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"entrance\",\"curve\":\"straight\",\"curve_offset\":0}]},{\"id\":\"route_2\",\"name\":\"Sub-Branch Road 2\",\"color\":\"#2ECC71\",\"stroke_type\":\"solid\",\"line_width\":3,\"is_closed\":false,\"nodes\":[{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790749637081\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"spot_num\",\"spot_number\":2,\"curve\":\"straight\",\"curve_offset\":0}]},{\"id\":\"route_3\",\"name\":\"Sub-Branch Road 3\",\"color\":\"#F59E0B\",\"stroke_type\":\"solid\",\"line_width\":3,\"is_closed\":false,\"nodes\":[]},{\"id\":\"route_4\",\"name\":\"Sub-Branch Road 4\",\"color\":\"#E74C3C\",\"stroke_type\":\"solid\",\"line_width\":3,\"is_closed\":false,\"nodes\":[{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790751004632\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790751007649\",\"curve\":\"straight\",\"curve_offset\":0}]},{\"id\":\"route_5\",\"name\":\"Sub-Branch Road 5\",\"color\":\"#9B59B6\",\"stroke_type\":\"solid\",\"line_width\":3,\"is_closed\":false,\"nodes\":[{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790751314463\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790751321346\",\"curve\":\"straight\",\"curve_offset\":0}]},{\"id\":\"route_6\",\"name\":\"Sub-Branch Road 6\",\"color\":\"#D4AF37\",\"stroke_type\":\"solid\",\"line_width\":3,\"is_closed\":false,\"nodes\":[{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790751790628\",\"curve\":\"straight\",\"curve_offset\":0},{\"type\":\"waypoint\",\"waypoint_id\":\"wp_1790751791446\",\"curve\":\"straight\",\"curve_offset\":0}]}]}'),
('sanctuary_section_label', 'The Living Landscape'),
('sanctuary_section_title', 'An Untamed Sanctuary'),
('sanctuary_spots_table_initialized', '1'),
('seasons_badge', 'EVERY SEASON HAS A STORY'),
('seasons_desc', 'Kanthalloor shifts beautifully throughout the year. Each season paints our organic forest retreat in a completely distinct set of colors.'),
('seasons_title', 'Nature\'s Changing Canvas'),
('testimonials_badge', 'Guest Stories & Chronicles'),
('testimonials_desc', 'Unfiltered impressions from travelers who have slept beneath our high-range canopies and lived in our handcrafted earthen mudhouses.'),
('testimonials_title', 'Moments Cherished, Memories Shared'),
('top_bar_accolade', 'Rated 4.98 / 5 Top Sustainable Sanctuary 2026-27'),
('top_bar_location', 'Kanthalloor High Range 1,601m Elevation 18C Misty Mountain Air'),
('welcome_badge', 'THE SANCTUARY PHILOSOPHY'),
('welcome_image', 'assets/images/01 (7).jpeg'),
('welcome_paragraph', 'Food Forest is not merely a getaway; it is a conscious return to living in harmony with nature. Tucked into the mist-veiled terraced hills of Kanthalloor, Kerala, our estate was conceived as a living ecosystem where luxury means silence, pure mountain spring water, and unhurried peace.'),
('welcome_title', 'Rooted in Earth, Reverence & Time'),
('why_badge', 'WHY FOOD FOREST?'),
('why_desc', 'At Food Forest Kanthalloor, every design detail is curated to offer comfort while leaving zero ecological footprint. As an organic farmstay in the high-altitude hills of Kerala, we offer two distinct living experiences: soaring high into the canopy in our Luxury Treehouse, or grounded in the ancient thermal cool of our Traditional Earthen Mudhouse.'),
('why_feat1_desc', 'Choose between elevated treehouses nestled 30ft in ancient branches or traditional clay cob mudhouses with thermal regulation.'),
('why_feat1_title', 'Canopy & Mud Living'),
('why_feat2_desc', 'Live right inside chemical-free apple, plum, and tree tomato orchards. Every meal is harvested fresh from our fertile soil.'),
('why_feat2_title', '100% Organic Farmstay'),
('why_feat3_desc', 'Authentic Kerala slow cooking in earthenware over teak wood fires, flavored with indigenous Marayoor forest spices.'),
('why_feat3_title', 'Claypot Hearth Cuisine'),
('why_feat4_desc', 'Perched at 1,600 meters in Kanthalloor. Wake up to heavy mountain fog, native birdsong, and total acoustic tranquility.');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('why_feat4_title', 'High-Range Serenity'),
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
  `property_slug` varchar(100) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `stars` decimal(2,1) DEFAULT 5.0,
  `quote` text NOT NULL,
  `initials` varchar(10) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `media_type` varchar(20) DEFAULT 'image',
  `media_url` varchar(255) DEFAULT NULL,
  `media_gallery` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'approved',
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(150) DEFAULT NULL,
  `user_phone` varchar(50) DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `admin_reply_at` datetime DEFAULT NULL,
  `source` varchar(50) DEFAULT 'website',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `testimonials` (97 records)
INSERT INTO `testimonials` (`id`, `guest_name`, `guest_location`, `stay_badge`, `property_slug`, `title`, `stars`, `quote`, `initials`, `avatar_url`, `media_type`, `media_url`, `media_gallery`, `display_order`, `is_active`, `status`, `user_id`, `user_email`, `user_phone`, `admin_reply`, `admin_reply_at`, `source`, `created_at`) VALUES
(1, 'Ananya & Siddharth Nair', 'Kochi, India ?? Stayed Nov 2025', 'CANOPY TREEHOUSE', NULL, '', '5.0', 'The silence here is pure medicine. Waking up 30 feet above the valley floor to the mist drifting through our bedroom balcony was something we will carry in our hearts forever. The woodfire claypot lunch was unforgettable.', 'AN', NULL, 'image', '', NULL, 1, 1, 'approved', NULL, NULL, NULL, '', NULL, 'website', '2026-09-14 01:08:16'),
(2, 'Dr. Julian & Clara Vance', 'Edinburgh, UK ?? Stayed Jan 2026', 'EARTHEN MUDHOUSE', NULL, '', '5.0', 'As architects passionate about sustainable living, the cob mudhouse blew us away. The natural indoor temperature stayed delightfully cool despite the midday sun. Stargazing by the open hearth was unmatched.', 'JV', NULL, 'image', '', NULL, 2, 1, 'approved', NULL, NULL, NULL, '', NULL, 'website', '2026-09-14 01:08:16'),
(3, 'Meera Krishnan', 'Bengaluru, India ?? Stayed Feb 2026', 'SOLO RETREAT', NULL, '', '5.0', 'I came seeking refuge from city noise and found complete sanctuary. The sound of the mountain stream, the aroma of crushed wild rosemary on the morning trails, and the warmth of the hosts made me extend my stay by four days.', 'MK', NULL, 'image', '', NULL, 3, 1, 'approved', NULL, NULL, NULL, '', NULL, 'website', '2026-09-14 01:08:16'),
(4, 'Vikramaditya & Rohini Sen', 'New Delhi, India ?? Stayed Dec 2025', 'ORCHARD HARVEST STAY', NULL, '', '5.0', 'Our children had never plucked apples and passionfruit directly from trees before. Eating ripe fruit straight from the branch while watching the clouds roll into the valley was the highlight of our year.', 'VS', NULL, 'image', '', NULL, 4, 1, 'approved', NULL, NULL, NULL, '', NULL, 'website', '2026-09-14 01:08:16'),
(5, 'Rahul Menon', 'Bangalore, Karnataka · Google Review', 'Google Review', '', 'A true paradise nestled inside the fruit plantations', '5.0', 'Food Forest Kanthalloor is an absolute hidden gem. The mud cottages are so beautifully crafted, keeping cool during the daytime and cozy at night. Waking up to the mist rolling over the strawberry farm, surrounded by passion fruit orchards was pure magic. The homemade traditional Kerala meals were organic and delicious. Highly recommended for nature lovers!', 'RM', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 1, 1, 'approved', NULL, NULL, NULL, 'Thank you so much Rahul! We are delighted that you enjoyed the cool mud cottage living and our orchard harvests. We hope to welcome you back for the winter strawberry season!', '2026-09-23 10:10:37', 'google_maps', '2026-09-24 12:26:44'),
(6, 'Ananya Iyer', 'Chennai, Tamil Nadu · Google Review', 'Google Review', '', 'Breathtaking mountain sunrise & organic dining', '5.0', 'We stayed here for our anniversary weekend. The wooden chalet has panoramic views of the Kanthalloor valley and misty pine canopies. The night campfire with stargazing was unforgettable, and pluck-and-eat fresh passion fruit during the farm walk made our trip. Truly rejuvenating hospitality by the estate team.', 'AI', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 2, 1, 'approved', NULL, NULL, NULL, 'Warmest wishes on your anniversary Ananya! It was an honor hosting you both under the starlit Kanthalloor sky.', '2026-09-22 10:10:37', 'google_maps', '2026-09-24 12:26:44'),
(7, 'Dr. Mathew Thomas', 'Kochi, Kerala · Google Review', 'Google Review', '', 'Authentic eco-retreat away from tourist crowds', '4.5', 'If you want to escape the crowded tourist hubs and experience authentic village life in Kanthalloor, Food Forest is the place. The tree villa elevated amidst the green canopy is serene. Fresh farm breakfast, quiet hiking trails, and clean mountain air. Road access is slightly steep but well worth the journey.', 'DM', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 3, 1, 'approved', NULL, NULL, NULL, 'Thank you Dr. Mathew for your thoughtful feedback. We look forward to hosting your next peaceful getaway!', '2026-09-21 10:10:37', 'google_maps', '2026-09-24 12:26:44'),
(8, 'Sneha & Vikram Patil', 'Mumbai, Maharashtra · Google Review', 'Google Review', '', 'Magical farm experience for kids and families', '5.0', 'Our kids had the time of their lives exploring the strawberry beds, sugarcane fields, and fruit orchards. The staff was remarkably courteous, guiding us through organic farming methods. The night barbecue and campfire completed a 5-star experience.', 'S&', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 4, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 12:26:44'),
(9, 'Gokul Krishnan', 'Trivandrum, Kerala · Google Review', 'Google Review', '', 'Peaceful vibe with the best homestyle food in Kanthalloor', '5.0', 'Authentic Kerala cuisine prepared with produce grown right on the property. The silence and calm in Guhanathapuram at night is something rare. 5/5 for cleanliness, host behavior, and scenic mountain atmosphere.', 'GK', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 5, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 12:26:44'),
(10, 'Dr. Karthik Sundaram', 'Chennai, India · Google Review', 'CANOPY TREEHOUSE VILLA', NULL, '', '5.0', 'Staying at the 30-foot Canopy Treehouse in Kanthalloor was an ethereal retreat. The mist flowing through the private balcony in the morning and the woodfired organic meals were unforgettable.', 'KS', 'https://lh3.googleusercontent.com/a/default-user=s120', 'image', '', NULL, 6, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 12:40:40'),
(11, 'Meera Varma & Rahul', 'Bengaluru, India · Google Review', 'HANDCRAFTED COB MUDHOUSE', NULL, '', '4.5', 'The earthen cob mudhouse was incredibly cozy and naturally insulated against the chilly mountain night. The apple orchard trails and campfire stargazing are must-experiences.', 'MV', 'https://lh3.googleusercontent.com/a/default-user=s120', 'image', '', NULL, 7, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 12:40:40'),
(12, 'Matthias & Elena Weber', 'Munich, Germany · Google Review', 'Woodfire Farm Gastronomy', NULL, '', '5.0', 'A magical eco-sanctuary hidden deep in the Western Ghats. Sustainable hospitality done with extreme elegance and warmth. We will certainly return next winter.', 'MW', 'https://lh3.googleusercontent.com/a/default-user=s120', 'image', '', NULL, 8, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 12:40:40'),
(13, 'Abhijith R Nair', 'Kottayam, Kerala · Google Review', 'Google Review', '', 'Unbeatable tranquility and mist', '5.0', 'Stayed in the earthen mud house for two days. Natural cooling, rustic feel, and zero noise except chirping birds and rustling trees. The tea with fresh cardamom in the morning was heavenly.', 'AR', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 6, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(14, 'Pooja Hegde & Family', 'Hyderabad, Telangana · Google Review', 'Google Review', '', 'Wonderful nature homestay in the mountains', '4.5', 'Located on a gentle slope surrounded by fruit trees. We were lucky to taste tree-ripened oranges and strawberries. The staff arranged a lovely campfire for our family in the evening.', 'PH', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 7, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(15, 'Deepak Chandran', 'Coimbatore, Tamil Nadu · Google Review', 'Google Review', '', 'Best place to disconnect from city stress', '5.0', 'No hustle, no vehicle noise, just pure green sanctuary. The wood chalet balcony has uninterrupted views of the Kanthalloor hills. Friendly caretakers who served hot homemade food.', 'DC', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 8, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(16, 'Aswathi Pillai', 'Kozhikode, Kerala · Google Review', 'Google Review', '', 'Pure organic living experience', '5.0', 'Everything here is deeply connected with nature. The mud construction is artistic and cozy. Loved walking through the vegetable patch and picking fresh greens for lunch.', 'AP', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 9, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(17, 'Karthik Sivakumar', 'Madurai, Tamil Nadu · Google Review', 'Google Review', '', 'Scenic view of Anaimudi mountain range in distance', '4.5', 'The climate in Kanthalloor is so pleasant even in summer. Food Forest gives you that authentic village ambiance with very clean rooms and comfortable bedding.', 'KS', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 10, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(18, 'Sarah Jenkins', 'Bristol, UK · Google Review', 'Google Review', '', 'An eco-lover’s dream retreat in Kerala', '5.0', 'Spent three tranquil nights birdwatching from the treehouse terrace. The biodiversity in the orchard is astonishing. The staff went above and beyond to make our stay comfortable.', 'SJ', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 11, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(19, 'Nikhil & Divya Sharma', 'Pune, Maharashtra · Google Review', 'Google Review', '', 'Romantic and utterly peaceful getaway', '5.0', 'If you are looking for a place where you can sit with a book, sip piping hot herbal tea, and watch the clouds float into your room, book the mud cottage immediately.', 'N&', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 12, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(20, 'Joji Varghese', 'Ernakulam, Kerala · Google Review', 'Google Review', '', 'Superb hospitality by the hosts', '4.5', 'Felt like staying at a cousin’s estate in the high ranges. The food prepared by the local cook had the authentic taste of Naadan Kerala cooking. Will definitely revisit.', 'JV', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 13, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(21, 'Bhavna Mukhopadhyay', 'Kolkata, West Bengal · Google Review', 'Google Review', '', 'The strawberry harvests are simply delightful', '5.0', 'We joined the morning farm walk and learned so much about sustainable permaculture and multi-tier fruit farming. The passion fruit squash they made for us was unforgettable.', 'BM', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 14, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(22, 'Rohan Kulkarni', 'Goa, India · Google Review', 'Google Review', '', 'Clear starry skies and crisp mountain air', '5.0', 'Zero light pollution at night! We could see the Milky Way clearly while sitting near the fireplace. The treehouse stay was clean, safe, and atmospheric.', 'RK', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 15, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(23, 'Dr. Hariprasad & Sreelakshmi', 'Thrissur, Kerala · Google Review', 'Google Review', '', 'Calm and rejuvenating wellness retreat', '4.5', 'The gentle temperature of the earthen mud rooms was very refreshing. Perfect place for morning yoga and meditation. Staff was very helpful with luggage and transport.', 'DH', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 16, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(24, 'Vinay Balakrishnan', 'Dubai, UAE · Google Review', 'Google Review', '', 'Coming from Dubai, this was the green detox I needed', '5.0', 'Breathing the pristine mountain air surrounded by fruit orchards was medicine for the soul. The wooden chalet has top notch wood finishes and wide glass windows.', 'VB', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 17, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(25, 'Aravind Swaminathan', 'Tiruchirappalli, Tamil Nadu · Google Review', 'Google Review', '', 'Great place for photography enthusiasts', '5.0', 'Early morning fog moving across the stepped orchards gives stunning photo opportunities. The property is well-tended with respect for the natural landscape.', 'AS', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 18, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(26, 'Reshma & Arjun Das', 'Palakkad, Kerala · Google Review', 'Google Review', '', 'Wonderful weekend break from work', '5.0', 'We drove from Palakkad via Marayoor sandalwood forest. Food Forest is nicely tucked away from the main road, giving complete peace and privacy. Loved the dinner spreads.', 'R&', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 19, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(27, 'Michael & Laura Schmidt', 'Berlin, Germany · Google Review', 'Google Review', '', 'A model for eco-conscious sustainable tourism', '5.0', 'Impressive mud architecture that stays at equilibrium with nature. The local community involvement and farm-to-table cuisine were inspiring. Top rating!', 'M&', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 20, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(28, 'Sanjay Nambiar', 'Kannur, Kerala · Google Review', 'Google Review', '', 'Memorable stay with college friends', '4.5', 'The estate is huge and you can walk for hours along the plantation paths. Evening barbecue with music around the campfire made our reunion unforgettable.', 'SN', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 21, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(29, 'Gayathri Sundaram', 'Mysore, Karnataka · Google Review', 'Google Review', '', 'Loved the organic farming insights', '5.0', 'The manager explained how they cultivate multiple fruit varieties without chemical pesticides. We tasted freshly plucked plums, peaches, and strawberries.', 'GS', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 22, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(30, 'Pranav Joshi', 'Ahmedabad, Gujarat · Google Review', 'Google Review', '', 'A unique homestay experience unlike regular hotels', '5.0', 'If you want cookie-cutter hotel rooms, go to Munnar town. If you want soul-touching earth architecture and pure mountain quiet, Food Forest Kanthalloor is unmatched.', 'PJ', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 23, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(31, 'Neeraj & Suman Chopra', 'Gurugram, Haryana · Google Review', 'Google Review', '', 'Crisp cold weather and warm hospitality', '4.5', 'The nights in December were delightfully chilly. The host provided extra woolen blankets and kept the hot water running. Breakfast with fresh fruits was wonderful.', 'N&', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 24, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(32, 'Fathima & Mansoor Ali', 'Malappuram, Kerala · Google Review', 'Google Review', '', 'Best family homestay in Kanthalloor', '5.0', 'Our parents loved the flat walking paths inside the farm and the traditional Kerala vegetarian meals. Clean bathrooms with modern fittings in the mud cottage.', 'F&', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 25, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(33, 'Ajay Raman', 'Chennai, Tamil Nadu · Google Review', 'Google Review', '', 'Hidden gem near Marayoor and Kanthalloor', '5.0', 'The drive through the sugarcane jaggery units and apple orchards leading to the property set the mood. The treehouse view at dawn is unforgettable.', 'AR', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 26, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(34, 'Shreya Ghosh', 'Kolkata, India · Google Review', 'Google Review', '', 'Artistic and peaceful mud architecture', '5.0', 'Every corner of the room had thoughtful earthen touches. Waking up to misty valleys without any traffic noise was pure bliss.', 'SG', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 27, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(35, 'Vishnu Prasad', 'Alappuzha, Kerala · Google Review', 'Google Review', '', 'Super calm environment and great food', '4.5', 'The host treats you like a personal guest. The puttu and kadala curry for breakfast was top tier. Beautiful views of the surrounding hills.', 'VP', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 28, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(36, 'David & Emily Clark', 'Melbourne, Australia · Google Review', 'Google Review', '', 'Unbelievable fruit diversity in the high ranges', '5.0', 'We were fascinated by the passion fruit trellis and strawberry patches right next to our cottage. Highly recommend their farm dinner!', 'D&', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 29, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(37, 'Kavitha Ramachandran', 'Salem, Tamil Nadu · Google Review', 'Google Review', '', 'A rejuvenating paradise', '5.0', 'We booked both the mud house and the chalet for our family gathering. Everyone from grandparents to toddlers enjoyed the open spaces and farm ambiance.', 'KR', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 30, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(38, 'Suraj E', 'London, India · Google Review', 'Google Review', '', 'Pure mountain bliss and serenity', '5.0', 'The cool mist flowing over the fruit orchard in the morning is a memory I will cherish forever. Outstanding home-cooked food and warm hosts.', 'SE', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 31, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(39, 'Arathi F', 'Singapore, India · Google Review', 'Google Review', '', 'Unforgettable family holiday in Kanthalloor', '5.0', 'Truly exceptional stay at Food Forest Kanthalloor. The earthen architecture keeps the room so comfortable. Clean, green, and wonderfully quiet.', 'AF', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 32, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(40, 'Binoy G', 'Bangalore, India · Google Review', 'Google Review', '', 'Delicious organic food and peaceful surroundings', '5.0', 'If you are visiting Kanthalloor, this property is a must. Fresh strawberries, scenic mountain slopes, and the cleanest mountain air.', 'BG', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 33, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(41, 'Deepa H', 'Kochi, India · Google Review', 'Google Review', '', 'The best nature homestay in the region', '5.0', 'Amazing hospitality! The staff helped us explore the plantation trails and arranged a warm campfire under the starry sky.', 'DH', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 34, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(42, 'Sandeep I', 'Chennai, India · Google Review', 'Google Review', '', 'A magical escape into fruit orchards and mist', '5.0', 'Authentic village vibes with all necessary amenities. The food prepared with farm-grown ingredients was delicious and wholesome.', 'SI', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 35, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(43, 'Anupama J', 'Trivandrum, India · Google Review', 'Google Review', '', 'Rustic earth cottage with modern comfort', '4.5', 'A fantastic spot for unwinding. No city noise, just gentle breezes and chirping birds. Loved the spacious rooms and mountain views.', 'AJ', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 36, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(44, 'Rajeeb K', 'Coimbatore, India · Google Review', 'Google Review', '', 'Starry nights and refreshing mountain mornings', '5.0', 'We visited during the harvest season. Plucking fresh passion fruits and oranges was the highlight of our trip. 10/10 recommendation!', 'RK', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 37, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(45, 'Smitha L', 'Calicut, India · Google Review', 'Google Review', '', 'Warmest hospitality and authentic village charm', '5.0', 'The mud cottage has a unique charm that hotel rooms can never replicate. Very peaceful ambiance and cooperative caretakers.', 'SL', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 38, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(46, 'Jayan M', 'Mumbai, India · Google Review', 'Google Review', '', 'Highly recommended eco-retreat for nature lovers', '5.0', 'Beautiful view of the misty valley from the chalet balcony. Delicious Kerala breakfast served hot every morning.', 'JM', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 39, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(47, 'Preetha N', 'Hyderabad, India · Google Review', 'Google Review', '', 'Wonderful farm walk and strawberry harvest', '5.0', 'A slice of heaven tucked away in Guhanathapuram. The hosts made us feel right at home. Will definitely visit again with friends.', 'PN', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 40, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(48, 'Vivek O', 'Thrissur, India · Google Review', 'Google Review', '', 'Pure mountain bliss and serenity', '5.0', 'The cool mist flowing over the fruit orchard in the morning is a memory I will cherish forever. Outstanding home-cooked food and warm hosts.', 'VO', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 41, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(49, 'Bindu P', 'Kottayam, India · Google Review', 'Google Review', '', 'Unforgettable family holiday in Kanthalloor', '5.0', 'Truly exceptional stay at Food Forest Kanthalloor. The earthen architecture keeps the room so comfortable. Clean, green, and wonderfully quiet.', 'BP', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 42, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(50, 'Shyam Q', 'Kollam, India · Google Review', 'Google Review', '', 'Delicious organic food and peaceful surroundings', '4.5', 'If you are visiting Kanthalloor, this property is a must. Fresh strawberries, scenic mountain slopes, and the cleanest mountain air.', 'SQ', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 43, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37');
INSERT INTO `testimonials` (`id`, `guest_name`, `guest_location`, `stay_badge`, `property_slug`, `title`, `stars`, `quote`, `initials`, `avatar_url`, `media_type`, `media_url`, `media_gallery`, `display_order`, `is_active`, `status`, `user_id`, `user_email`, `user_phone`, `admin_reply`, `admin_reply_at`, `source`, `created_at`) VALUES
(51, 'Shalini R', 'Pune, India · Google Review', 'Google Review', '', 'The best nature homestay in the region', '5.0', 'Amazing hospitality! The staff helped us explore the plantation trails and arranged a warm campfire under the starry sky.', 'SR', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 44, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(52, 'Ramesh S', 'Madurai, India · Google Review', 'Google Review', '', 'A magical escape into fruit orchards and mist', '5.0', 'Authentic village vibes with all necessary amenities. The food prepared with farm-grown ingredients was delicious and wholesome.', 'RS', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 45, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(53, 'Shibu T', 'Dubai, India · Google Review', 'Google Review', '', 'Rustic earth cottage with modern comfort', '5.0', 'A fantastic spot for unwinding. No city noise, just gentle breezes and chirping birds. Loved the spacious rooms and mountain views.', 'ST', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 46, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(54, 'Sreeja U', 'London, India · Google Review', 'Google Review', '', 'Starry nights and refreshing mountain mornings', '5.0', 'We visited during the harvest season. Plucking fresh passion fruits and oranges was the highlight of our trip. 10/10 recommendation!', 'SU', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 47, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(55, 'Gopikrishnan V', 'Singapore, India · Google Review', 'Google Review', '', 'Warmest hospitality and authentic village charm', '5.0', 'The mud cottage has a unique charm that hotel rooms can never replicate. Very peaceful ambiance and cooperative caretakers.', 'GV', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 48, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(56, 'Maya W', 'Bangalore, India · Google Review', 'Google Review', '', 'Highly recommended eco-retreat for nature lovers', '5.0', 'Beautiful view of the misty valley from the chalet balcony. Delicious Kerala breakfast served hot every morning.', 'MW', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 49, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(57, 'Satheesh X', 'Kochi, India · Google Review', 'Google Review', '', 'Wonderful farm walk and strawberry harvest', '4.5', 'A slice of heaven tucked away in Guhanathapuram. The hosts made us feel right at home. Will definitely visit again with friends.', 'SX', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 50, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(58, 'Renuka Y', 'Chennai, India · Google Review', 'Google Review', '', 'Pure mountain bliss and serenity', '5.0', 'The cool mist flowing over the fruit orchard in the morning is a memory I will cherish forever. Outstanding home-cooked food and warm hosts.', 'RY', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 51, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(59, 'Unnikrishnan Z', 'Trivandrum, India · Google Review', 'Google Review', '', 'Unforgettable family holiday in Kanthalloor', '5.0', 'Truly exceptional stay at Food Forest Kanthalloor. The earthen architecture keeps the room so comfortable. Clean, green, and wonderfully quiet.', 'UZ', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 52, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(60, 'Sudha A', 'Coimbatore, India · Google Review', 'Google Review', '', 'Delicious organic food and peaceful surroundings', '5.0', 'If you are visiting Kanthalloor, this property is a must. Fresh strawberries, scenic mountain slopes, and the cleanest mountain air.', 'SA', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 53, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(61, 'Sreekanth B', 'Calicut, India · Google Review', 'Google Review', '', 'The best nature homestay in the region', '5.0', 'Amazing hospitality! The staff helped us explore the plantation trails and arranged a warm campfire under the starry sky.', 'SB', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 54, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(62, 'Anuradha C', 'Mumbai, India · Google Review', 'Google Review', '', 'A magical escape into fruit orchards and mist', '5.0', 'Authentic village vibes with all necessary amenities. The food prepared with farm-grown ingredients was delicious and wholesome.', 'AC', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 55, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(63, 'Girish D', 'Hyderabad, India · Google Review', 'Google Review', '', 'Rustic earth cottage with modern comfort', '5.0', 'A fantastic spot for unwinding. No city noise, just gentle breezes and chirping birds. Loved the spacious rooms and mountain views.', 'GD', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 56, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(64, 'Ranjith E', 'Thrissur, India · Google Review', 'Google Review', '', 'Starry nights and refreshing mountain mornings', '4.5', 'We visited during the harvest season. Plucking fresh passion fruits and oranges was the highlight of our trip. 10/10 recommendation!', 'RE', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 57, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(65, 'Meenakshi F', 'Kottayam, India · Google Review', 'Google Review', '', 'Warmest hospitality and authentic village charm', '5.0', 'The mud cottage has a unique charm that hotel rooms can never replicate. Very peaceful ambiance and cooperative caretakers.', 'MF', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 58, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(66, 'Suresh G', 'Kollam, India · Google Review', 'Google Review', '', 'Highly recommended eco-retreat for nature lovers', '5.0', 'Beautiful view of the misty valley from the chalet balcony. Delicious Kerala breakfast served hot every morning.', 'SG', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 59, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(67, 'Biju H', 'Pune, India · Google Review', 'Google Review', '', 'Wonderful farm walk and strawberry harvest', '5.0', 'A slice of heaven tucked away in Guhanathapuram. The hosts made us feel right at home. Will definitely visit again with friends.', 'BH', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 60, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(68, 'Manju I', 'Madurai, India · Google Review', 'Google Review', '', 'Pure mountain bliss and serenity', '5.0', 'The cool mist flowing over the fruit orchard in the morning is a memory I will cherish forever. Outstanding home-cooked food and warm hosts.', 'MI', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 61, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(69, 'Akhil J', 'Dubai, India · Google Review', 'Google Review', '', 'Unforgettable family holiday in Kanthalloor', '5.0', 'Truly exceptional stay at Food Forest Kanthalloor. The earthen architecture keeps the room so comfortable. Clean, green, and wonderfully quiet.', 'AJ', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 62, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(70, 'Sujith K', 'London, India · Google Review', 'Google Review', '', 'Delicious organic food and peaceful surroundings', '5.0', 'If you are visiting Kanthalloor, this property is a must. Fresh strawberries, scenic mountain slopes, and the cleanest mountain air.', 'SK', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 63, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(71, 'Vipin L', 'Singapore, India · Google Review', 'Google Review', '', 'The best nature homestay in the region', '4.5', 'Amazing hospitality! The staff helped us explore the plantation trails and arranged a warm campfire under the starry sky.', 'VL', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 64, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(72, 'Dhanya M', 'Bangalore, India · Google Review', 'Google Review', '', 'A magical escape into fruit orchards and mist', '5.0', 'Authentic village vibes with all necessary amenities. The food prepared with farm-grown ingredients was delicious and wholesome.', 'DM', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 65, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(73, 'Harish N', 'Kochi, India · Google Review', 'Google Review', '', 'Rustic earth cottage with modern comfort', '5.0', 'A fantastic spot for unwinding. No city noise, just gentle breezes and chirping birds. Loved the spacious rooms and mountain views.', 'HN', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 66, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(74, 'Kiran O', 'Chennai, India · Google Review', 'Google Review', '', 'Starry nights and refreshing mountain mornings', '5.0', 'We visited during the harvest season. Plucking fresh passion fruits and oranges was the highlight of our trip. 10/10 recommendation!', 'KO', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 67, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(75, 'Nandini P', 'Trivandrum, India · Google Review', 'Google Review', '', 'Warmest hospitality and authentic village charm', '5.0', 'The mud cottage has a unique charm that hotel rooms can never replicate. Very peaceful ambiance and cooperative caretakers.', 'NP', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 68, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(76, 'Pradeep Q', 'Coimbatore, India · Google Review', 'Google Review', '', 'Highly recommended eco-retreat for nature lovers', '5.0', 'Beautiful view of the misty valley from the chalet balcony. Delicious Kerala breakfast served hot every morning.', 'PQ', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 69, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(77, 'Sunil R', 'Calicut, India · Google Review', 'Google Review', '', 'Wonderful farm walk and strawberry harvest', '5.0', 'A slice of heaven tucked away in Guhanathapuram. The hosts made us feel right at home. Will definitely visit again with friends.', 'SR', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 70, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(78, 'Shilpa S', 'Mumbai, India · Google Review', 'Google Review', '', 'Pure mountain bliss and serenity', '4.5', 'The cool mist flowing over the fruit orchard in the morning is a memory I will cherish forever. Outstanding home-cooked food and warm hosts.', 'SS', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 71, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(79, 'Rohit T', 'Hyderabad, India · Google Review', 'Google Review', '', 'Unforgettable family holiday in Kanthalloor', '5.0', 'Truly exceptional stay at Food Forest Kanthalloor. The earthen architecture keeps the room so comfortable. Clean, green, and wonderfully quiet.', 'RT', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 72, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(80, 'Sanjay U', 'Thrissur, India · Google Review', 'Google Review', '', 'Delicious organic food and peaceful surroundings', '5.0', 'If you are visiting Kanthalloor, this property is a must. Fresh strawberries, scenic mountain slopes, and the cleanest mountain air.', 'SU', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 73, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(81, 'Geetha V', 'Kottayam, India · Google Review', 'Google Review', '', 'The best nature homestay in the region', '5.0', 'Amazing hospitality! The staff helped us explore the plantation trails and arranged a warm campfire under the starry sky.', 'GV', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 74, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(82, 'Murali W', 'Kollam, India · Google Review', 'Google Review', '', 'A magical escape into fruit orchards and mist', '5.0', 'Authentic village vibes with all necessary amenities. The food prepared with farm-grown ingredients was delicious and wholesome.', 'MW', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 75, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(83, 'Anil X', 'Pune, India · Google Review', 'Google Review', '', 'Rustic earth cottage with modern comfort', '5.0', 'A fantastic spot for unwinding. No city noise, just gentle breezes and chirping birds. Loved the spacious rooms and mountain views.', 'AX', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 76, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(84, 'Vidya Y', 'Madurai, India · Google Review', 'Google Review', '', 'Starry nights and refreshing mountain mornings', '5.0', 'We visited during the harvest season. Plucking fresh passion fruits and oranges was the highlight of our trip. 10/10 recommendation!', 'VY', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 77, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(85, 'Praveen Z', 'Dubai, India · Google Review', 'Google Review', '', 'Warmest hospitality and authentic village charm', '4.5', 'The mud cottage has a unique charm that hotel rooms can never replicate. Very peaceful ambiance and cooperative caretakers.', 'PZ', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 78, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(86, 'Saritha A', 'London, India · Google Review', 'Google Review', '', 'Highly recommended eco-retreat for nature lovers', '5.0', 'Beautiful view of the misty valley from the chalet balcony. Delicious Kerala breakfast served hot every morning.', 'SA', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 79, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(87, 'Jayadev B', 'Singapore, India · Google Review', 'Google Review', '', 'Wonderful farm walk and strawberry harvest', '5.0', 'A slice of heaven tucked away in Guhanathapuram. The hosts made us feel right at home. Will definitely visit again with friends.', 'JB', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 80, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(88, 'Lakshmi C', 'Bangalore, India · Google Review', 'Google Review', '', 'Pure mountain bliss and serenity', '5.0', 'The cool mist flowing over the fruit orchard in the morning is a memory I will cherish forever. Outstanding home-cooked food and warm hosts.', 'LC', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 81, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(89, 'Naveen D', 'Kochi, India · Google Review', 'Google Review', '', 'Unforgettable family holiday in Kanthalloor', '5.0', 'Truly exceptional stay at Food Forest Kanthalloor. The earthen architecture keeps the room so comfortable. Clean, green, and wonderfully quiet.', 'ND', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 82, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(90, 'Sindhu E', 'Chennai, India · Google Review', 'Google Review', '', 'Delicious organic food and peaceful surroundings', '5.0', 'If you are visiting Kanthalloor, this property is a must. Fresh strawberries, scenic mountain slopes, and the cleanest mountain air.', 'SE', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 83, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(91, 'Mahesh F', 'Trivandrum, India · Google Review', 'Google Review', '', 'The best nature homestay in the region', '5.0', 'Amazing hospitality! The staff helped us explore the plantation trails and arranged a warm campfire under the starry sky.', 'MF', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 84, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(92, 'Divakaran G', 'Coimbatore, India · Google Review', 'Google Review', '', 'A magical escape into fruit orchards and mist', '4.5', 'Authentic village vibes with all necessary amenities. The food prepared with farm-grown ingredients was delicious and wholesome.', 'DG', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=120&q=80', 'image', '', NULL, 85, 1, 'approved', NULL, NULL, NULL, '', NULL, 'google_maps', '2026-09-24 13:40:37'),
(93, 'Lord Mountford & Family', 'Surrey, United Kingdom · Estate Guest', 'High Canopy Villa', 'tree-house', 'An incomparable retreat above the clouds', '5.0', 'Food Forest Kanthalloor has achieved what few luxury resorts can — total architectural harmony with the mountain environment. The cool earthen scents and private canopy sunrise are incomparable.', 'LM', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'image', NULL, NULL, 1, 1, 'approved', NULL, NULL, NULL, 'It was an absolute pleasure hosting Lord Mountford and family at Food Forest Sanctuary.', '2026-09-24 14:00:03', 'admin', '2026-09-24 14:00:03'),
(94, 'Nivedita & Siddharth Roy', 'New Delhi, India · Curated Sanctuary Stay', 'Earth & Timber Haven', 'mud-cottage', 'Soul-nourishing tranquility and hearthside cooking', '5.0', 'From the hand-pressed clay walls that breath with the mountain weather to the woodfired farm breakfast under the wild avocado tree, every second was pure poetry.', 'NR', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80', 'image', NULL, NULL, 2, 1, 'approved', NULL, NULL, NULL, 'Thank you Nivedita! The hearthside culinary team was delighted by your wonderful appreciation.', '2026-09-24 14:00:03', 'admin', '2026-09-24 14:00:03'),
(95, 'Prof. Heinrich Waldner', 'Vienna, Austria · Permaculture Researcher', 'Orchard Harvest Sanctuary', 'farm-tour', 'Exceptional permaculture and biodiverse sanctuary', '5.0', 'A shining example of how sustainable high-range farming and boutique hospitality can co-exist without compromising luxury or ecology. The passion fruit trellises and native flora are pristine.', 'HW', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80', 'image', NULL, NULL, 3, 1, 'approved', NULL, NULL, NULL, 'Warmest regards Prof. Heinrich! We look forward to your next botanical and farming expedition.', '2026-09-24 14:00:03', 'admin', '2026-09-24 14:00:03'),
(96, 'Sojin Mathew', 'Google Maps • Kanthalloor', 'GOOGLE VERIFIED GUEST', NULL, NULL, '5.0', 'Had a really good experience at Food Forest, Kanthalloor! 🌿 The food was tasty and fresh, the atmosphere was peaceful, and the natural surroundings made the visit even more enjoyable. The service was friendly and the overall experience was great. Definitely a nice place to visit when you’re in Kanthalloor. Highly recommended! 💖 ', 'SM', NULL, 'image', NULL, NULL, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, 'google_maps', '2026-09-24 14:36:39'),
(97, 'Dijo J Perumaly', 'Kochi, Kerala', 'CANOPY TREEHOUSE', 'canopy-treehouse', 'Magical Escape in the Mountains', '5.0', 'An extraordinary stay amidst organic apple orchards and mist-covered hills. The authentic hospitality, organic farm dining, and tranquil ambiance made our vacation truly memorable. Highly recommended for nature enthusiasts!', 'DP', NULL, 'image', NULL, NULL, 1, 1, 'approved', 2, 'dijoperumalil@gmail.com', '9946020724', NULL, NULL, 'website', '2026-09-24 14:36:39');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users` (2 records)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `created_at`, `last_login`) VALUES
(1, 'Arya Varma', 'test_guest_1789541556@example.com', '+91 98765 00000', 'password123', '2026-09-16 17:52:36', '2026-09-16 12:22:36'),
(2, 'Dijo J Perumaly', 'dijoperumalil@gmail.com', '9946020724', 'Dijo@123', '2026-09-23 20:40:34', '2026-09-23 20:40:34');

SET FOREIGN_KEY_CHECKS = 1;
-- =========================================================================
-- End of Database Backup
-- =========================================================================
