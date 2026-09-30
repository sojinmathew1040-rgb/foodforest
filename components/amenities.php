<?php
// =========================================================================
// Food Forest Sanctuary — Categorized Amenities & Estate Facilities Hub
// Modeled after MakeMyTrip Comprehensive Hotel Details & Luxury Farmstay Perks
// =========================================================================

$amenity_categories = [
    'basic' => [
        'id' => 'basic',
        'title' => 'Basic Facilities',
        'subtitle' => 'Essential conveniences crafted for seamless mountain living',
        'icon' => 'fa-solid fa-hotel',
        'badge' => '8 ESSENTIALS',
        'items' => [
            ['name' => 'High-Speed Forest Wi-Fi', 'desc' => 'High-speed optical fiber connectivity accessible across chalets and common pavilions.', 'icon' => 'fa-solid fa-wifi', 'highlight' => true],
            ['name' => 'Free Private Self-Parking', 'desc' => 'Spacious, secure on-site private parking area with valet and driver assistance.', 'icon' => 'fa-solid fa-square-parking', 'highlight' => true],
            ['name' => '24/7 Power Backup', 'desc' => 'Eco-friendly hybrid solar and silent inverter backup ensuring uninterrupted power.', 'icon' => 'fa-solid fa-bolt', 'highlight' => true],
            ['name' => 'Daily Housekeeping', 'desc' => 'Impeccable organic housekeeping and evening turn-down herbal aromatherapy service.', 'icon' => 'fa-solid fa-broom', 'highlight' => false],
            ['name' => 'Luggage Storage & Porters', 'desc' => 'Complimentary luggage assistance from entrance gate to your elevated chalet.', 'icon' => 'fa-solid fa-boxes-packing', 'highlight' => false],
            ['name' => 'RO Pure Spring Drinking Water', 'desc' => 'Unlimited natural mountain spring water, filtered through multi-stage RO systems.', 'icon' => 'fa-solid fa-bottle-water', 'highlight' => true],
            ['name' => 'Umbrellas & High-Beam Torches', 'desc' => 'Heavy-duty mountain umbrellas and rechargeable LED torches in every chalet.', 'icon' => 'fa-solid fa-umbrella', 'highlight' => false],
            ['name' => 'Iron & Ironing Board', 'desc' => 'Complimentary garment care accessories available on request from housekeeping.', 'icon' => 'fa-solid fa-shirt', 'highlight' => false],
        ]
    ],
    'services' => [
        'id' => 'services',
        'title' => 'Staff & Key Services',
        'subtitle' => 'Unhurried, warm hospitality rooted in traditional Kerala reverence',
        'icon' => 'fa-solid fa-bell-concierge',
        'badge' => '24/7 CARE',
        'items' => [
            ['name' => '24/7 Estate Concierge & Caretaker', 'desc' => 'Dedicated resident estate manager and on-demand concierge for all guest requests.', 'icon' => 'fa-solid fa-user-tie', 'highlight' => true],
            ['name' => 'Doctor on Call & Medical First-Aid', 'desc' => 'Immediate access to local medical practitioners and fully equipped on-site first-aid.', 'icon' => 'fa-solid fa-user-doctor', 'highlight' => true],
            ['name' => 'Multilingual Sanctuary Staff', 'desc' => 'Warm, courteous staff fluent in Malayalam, English, Tamil, and Hindi.', 'icon' => 'fa-solid fa-language', 'highlight' => false],
            ['name' => 'Express Check-In & Check-Out', 'desc' => 'Contactless digital or personalized welcome ritual with welcome herbal drink.', 'icon' => 'fa-solid fa-id-card-clip', 'highlight' => false],
            ['name' => 'Sightseeing & Safari Desk', 'desc' => 'Arrangement of jeep safaris to Chinnar Wildlife Sanctuary, Muniyara dolmens & waterfalls.', 'icon' => 'fa-solid fa-compass', 'highlight' => true],
            ['name' => 'Morning Birdsong Wake-Up', 'desc' => 'Customized gentle wake-up alerts accompanied by hot mountain plantation tea.', 'icon' => 'fa-solid fa-sun', 'highlight' => false],
        ]
    ],
    'wellness' => [
        'id' => 'wellness',
        'title' => 'Health & Wellness',
        'subtitle' => 'Restorative nature therapies in a 1,600m MSL zero-pollution ecosystem',
        'icon' => 'fa-solid fa-spa',
        'badge' => 'NATURE THERAPY',
        'items' => [
            ['name' => 'Mountain Yoga & Meditation Decks', 'desc' => 'Panoramic timber platforms facing the sunrise over cloud-filled valleys.', 'icon' => 'fa-solid fa-person-praying', 'highlight' => true],
            ['name' => 'Organic Herbal Steam Inhalation', 'desc' => 'Ayurvedic vapor rituals infused with fresh eucalyptus and lemongrass leaves.', 'icon' => 'fa-solid fa-wind', 'highlight' => true],
            ['name' => 'Pure Mountain Air (Zero-Pollution)', 'desc' => 'Breathe pristine 1,600-meter high-altitude air with crisp 14°C–22°C ambient temperatures.', 'icon' => 'fa-solid fa-mountain', 'highlight' => true],
            ['name' => 'Shinrin-Yoku Forest Bathing', 'desc' => 'Guided barefoot walking trails beneath canopy trees for sensory mindfulness.', 'icon' => 'fa-solid fa-shoe-prints', 'highlight' => false],
            ['name' => 'River Brook Hydro-Reflexology', 'desc' => 'Walk across smooth river pebbles in our perennial crystal-clear estate stream.', 'icon' => 'fa-solid fa-water', 'highlight' => false],
            ['name' => 'Stargazing Celestial Clearing', 'desc' => 'Zero light pollution zone perfect for observing the Milky Way and constellations.', 'icon' => 'fa-solid fa-star', 'highlight' => true],
        ]
    ],
    'room' => [
        'id' => 'room',
        'title' => 'Room Amenities',
        'subtitle' => 'Handcrafted eco-sanctuary suites engineered for pure tranquility',
        'icon' => 'fa-solid fa-bed',
        'badge' => 'ARTISAN SUITES',
        'items' => [
            ['name' => '180° Valley & Orchard Views', 'desc' => 'Expansive curved panoramic bay windows framing misty mountain slopes.', 'icon' => 'fa-solid fa-mountain-sun', 'highlight' => true],
            ['name' => 'Private Cantilevered Balcony', 'desc' => 'Suspended timber sit-out deck with cane chairs for cloud watching.', 'icon' => 'fa-solid fa-couch', 'highlight' => true],
            ['name' => 'Handcrafted Teak & Cob Furniture', 'desc' => 'Hand-hewn wild teak King bed, artisan bedside lamps, and earthen seating.', 'icon' => 'fa-solid fa-chair', 'highlight' => false],
            ['name' => '100% Organic Breathable Linen', 'desc' => 'High-thread-count organic cotton sheets, plush pillows, and duck-down warm quilts.', 'icon' => 'fa-solid fa-rug', 'highlight' => true],
            ['name' => 'Writing Desk & Multi-Charging', 'desc' => 'Solid wood laptop work desk with universal electrical and USB charging sockets.', 'icon' => 'fa-solid fa-plug', 'highlight' => false],
            ['name' => 'Wardrobe & Luggage Bench', 'desc' => 'Spacious open timber wardrobe with hangers and dedicated luggage bench.', 'icon' => 'fa-solid fa-vest-patches', 'highlight' => false],
            ['name' => 'Electric Kettle & Tea Station', 'desc' => 'Complimentary estate-plucked herbal tea, roasted coffee & fresh cardamom pods.', 'icon' => 'fa-solid fa-mug-hot', 'highlight' => true],
            ['name' => 'Daily Orchard Fruit Basket', 'desc' => 'Fresh seasonal harvest of Kanthalloor apples, oranges, and wild passion fruit.', 'icon' => 'fa-solid fa-apple-whole', 'highlight' => false],
        ]
    ],
    'dining' => [
        'id' => 'dining',
        'title' => 'Food & Drink (Farm Gastronomy)',
        'subtitle' => 'Farm-to-table culinary heritage slow-cooked over open wood fires',
        'icon' => 'fa-solid fa-utensils',
        'badge' => 'ALL MEALS INCLUDED',
        'items' => [
            ['name' => 'Woodfire Hearth Dining Pavilion', 'desc' => 'Charming semi-open dining hall surrounded by heirloom apple and orange trees.', 'icon' => 'fa-solid fa-fire-burner', 'highlight' => true],
            ['name' => '100% Organic Farm-to-Table Feasts', 'desc' => 'Freshly harvested estate vegetables, hand-pounded spices, and heirloom recipes.', 'icon' => 'fa-solid fa-seedling', 'highlight' => true],
            ['name' => 'Complimentary Orchard Breakfast', 'desc' => 'Steaming Kerala appam, idiyappam, fresh coconut milk, and seasonal orchard fruits.', 'icon' => 'fa-solid fa-mug-saucer', 'highlight' => true],
            ['name' => 'Traditional Claypot Slow Cooking', 'desc' => 'Slow-ember earthen claypot culinary techniques preserving natural mountain flavors.', 'icon' => 'fa-solid fa-bowl-rice', 'highlight' => false],
            ['name' => 'Evening Plantation Chai Ritual', 'desc' => 'Freshly brewed cardamom tea served with hot pazham pori or steamed elayappam.', 'icon' => 'fa-solid fa-cookie-bite', 'highlight' => true],
            ['name' => 'Twilight Campfire Barbecue', 'desc' => 'Outdoor grilling experience under the stars with marinated organic vegetables & paneer.', 'icon' => 'fa-solid fa-fire', 'highlight' => false],
            ['name' => 'Pure Vegetarian & Vegan Menus', 'desc' => 'Wholesome satvik, vegan, and customized dietary meals crafted on request.', 'icon' => 'fa-solid fa-carrot', 'highlight' => false],
        ]
    ],
    'safety' => [
        'id' => 'safety',
        'title' => 'Safety & Security',
        'subtitle' => 'Carefree seclusion protected by discreet, round-the-clock protocols',
        'icon' => 'fa-solid fa-shield-halved',
        'badge' => 'SECURE ESTATE',
        'items' => [
            ['name' => 'Gated Sanctuary Perimeter', 'desc' => 'Fully fenced 10-acre estate perimeter with solar-powered boundary protection.', 'icon' => 'fa-solid fa-lock', 'highlight' => true],
            ['name' => '24/7 Resident Caretaker & Patrol', 'desc' => 'Trained staff on-site day and night to ensure complete privacy and security.', 'icon' => 'fa-solid fa-shield-check', 'highlight' => true],
            ['name' => 'Fire Extinguishers & Solar Lights', 'desc' => 'Multi-zone fire safety equipment and emergency solar backup illumination.', 'icon' => 'fa-solid fa-fire-extinguisher', 'highlight' => false],
            ['name' => 'Medical First-Aid & Trauma Kit', 'desc' => 'Comprehensive emergency medical kit, burn dressing, and oxygen saturation monitor.', 'icon' => 'fa-solid fa-kit-medical', 'highlight' => true],
            ['name' => 'Illuminated Stone Pathways', 'desc' => 'Subtle low-glare warm lanterns lighting all walking trails from dusk till dawn.', 'icon' => 'fa-solid fa-lightbulb', 'highlight' => false],
        ]
    ],
    'bathroom' => [
        'id' => 'bathroom',
        'title' => 'Bathroom & En-Suite',
        'subtitle' => 'Natural river stone en-suite bathrooms with continuous hot spring water',
        'icon' => 'fa-solid fa-shower',
        'badge' => 'ECO EN-SUITE',
        'items' => [
            ['name' => '24-Hour Eco Solar Hot Water', 'desc' => 'High-capacity solar water heating supplemented by wood-fired backup.', 'icon' => 'fa-solid fa-temperature-arrow-up', 'highlight' => true],
            ['name' => 'Private River Stone Bathrooms', 'desc' => 'Handcrafted river rock basins, earthen slate tiles, and natural skylights.', 'icon' => 'fa-solid fa-bath', 'highlight' => true],
            ['name' => 'Handmade Organic Herbal Toiletries', 'desc' => 'Pure botanical soap bars, neem body wash, and vetiver hair wash.', 'icon' => 'fa-solid fa-pump-soap', 'highlight' => true],
            ['name' => 'Plush Organic Cotton Bath Towels', 'desc' => 'Extra-large 100% organic cotton bath sheets, hand towels, and bath mats.', 'icon' => 'fa-solid fa-toilet-paper', 'highlight' => false],
            ['name' => 'Western Ceramic WC with Jet', 'desc' => 'Modern sanitized ceramic water closet equipped with hygienic health faucet.', 'icon' => 'fa-solid fa-faucet-drip', 'highlight' => false],
            ['name' => 'Hairdryer & Shaving Mirror', 'desc' => 'Convenient grooming amenities available in room or provided on request.', 'icon' => 'fa-solid fa-wind', 'highlight' => false],
        ]
    ],
    'outdoors' => [
        'id' => 'outdoors',
        'title' => 'Common Area & Outdoors',
        'subtitle' => '10 acres of heirloom orchards, flowing mountain streams, and living decks',
        'icon' => 'fa-solid fa-tree',
        'badge' => '10-ACRE GROUNDS',
        'items' => [
            ['name' => 'Heirloom Apple & Orange Orchards', 'desc' => 'Wander through flourishing apple trees, Valencia oranges, and plum groves.', 'icon' => 'fa-solid fa-apple-whole', 'highlight' => true],
            ['name' => 'Mountain Brook & Wooden Bridge', 'desc' => 'Perennial fresh water stream running through the heart of the forest sanctuary.', 'icon' => 'fa-solid fa-water', 'highlight' => true],
            ['name' => 'Twilight Campfire Stargazing Glade', 'desc' => 'Natural stone firepit with log seating for storytelling under the cosmos.', 'icon' => 'fa-solid fa-campground', 'highlight' => true],
            ['name' => 'Canopy Hammocks & Garden Lawns', 'desc' => 'Woven string hammocks strung between pine trees for afternoon reading.', 'icon' => 'fa-solid fa-cloud', 'highlight' => false],
            ['name' => 'Living Lounge & Nature Library', 'desc' => 'Sheltered open-air lounge stocked with botany journals, fiction, and board games.', 'icon' => 'fa-solid fa-book-open-reader', 'highlight' => false],
            ['name' => 'Sunset Valley Viewpoint Deck', 'desc' => 'Highest elevation vantage point commanding 360° views across the Marayoor gap.', 'icon' => 'fa-solid fa-binoculars', 'highlight' => true],
        ]
    ],
];
?>

<!-- Sanctuary Amenities & Comprehensive Facilities Section -->
<section id="sanctuary-amenities" class="sanctuary-amenities-section">
    <div class="container">
        
        <!-- Section Header -->
        <div class="amenities-header text-center">
            <div class="amenities-badge-pill font-sans">
                <i class="fa-solid fa-sparkles"></i> COMPREHENSIVE SANCTUARY FACILITIES
            </div>
            <h2 class="amenities-main-title font-serif">World-Class Comfort in Pristine Nature</h2>
            <p class="amenities-main-subtitle font-sans">
                Every amenity at Food Forest Kanthalloor is designed to harmonize unhurried luxury with zero ecological footprint. Explore our 8 categorized guest facilities below.
            </p>
        </div>

        <!-- Interactive Category Selector Tabs -->
        <div class="amenities-tabs-wrapper">
            <div class="amenities-tabs-scroller" id="amenities-tabs-scroller" role="tablist">
                <?php 
                $tab_idx = 0;
                foreach ($amenity_categories as $cat_key => $cat): 
                    $is_active = ($tab_idx === 0);
                ?>
                    <button type="button" 
                            class="amenity-tab-btn font-sans <?php echo $is_active ? 'active' : ''; ?>" 
                            data-target-tab="<?php echo htmlspecialchars($cat_key); ?>" 
                            role="tab" 
                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" 
                            id="tab-btn-<?php echo htmlspecialchars($cat_key); ?>">
                        <span class="tab-icon"><i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i></span>
                        <span class="tab-title"><?php echo htmlspecialchars($cat['title']); ?></span>
                        <span class="tab-count-badge"><?php echo count($cat['items']); ?></span>
                    </button>
                <?php 
                    $tab_idx++;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- Categorized Content Panels -->
        <div class="amenities-panels-container">
            <?php 
            $panel_idx = 0;
            foreach ($amenity_categories as $cat_key => $cat): 
                $is_panel_active = ($panel_idx === 0);
            ?>
                <div class="amenity-tab-panel <?php echo $is_panel_active ? 'active' : ''; ?>" 
                     id="panel-<?php echo htmlspecialchars($cat_key); ?>" 
                     role="tabpanel" 
                     aria-labelledby="tab-btn-<?php echo htmlspecialchars($cat_key); ?>">
                    
                    <!-- Panel Headline Banner -->
                    <div class="panel-headline-card font-sans">
                        <div class="ph-left">
                            <span class="ph-badge"><i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i> <?php echo htmlspecialchars($cat['badge']); ?></span>
                            <h3 class="ph-title font-serif"><?php echo htmlspecialchars($cat['title']); ?></h3>
                            <p class="ph-subtitle"><?php echo htmlspecialchars($cat['subtitle']); ?></p>
                        </div>
                        <div class="ph-right">
                            <span class="ph-count-tag font-sans"><i class="fa-solid fa-circle-check"></i> <?php echo count($cat['items']); ?> Verified Inclusions</span>
                        </div>
                    </div>

                    <!-- Items Grid -->
                    <div class="amenity-items-grid">
                        <?php foreach ($cat['items'] as $item): ?>
                            <div class="amenity-item-card font-sans <?php echo !empty($item['highlight']) ? 'is-highlight' : ''; ?>">
                                <div class="aic-icon-box">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                </div>
                                <div class="aic-body">
                                    <div class="aic-title-row">
                                        <h4 class="aic-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <?php if (!empty($item['highlight'])): ?>
                                            <span class="aic-pill-highlight"><i class="fa-solid fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="aic-desc"><?php echo htmlspecialchars($item['desc']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php 
                $panel_idx++;
            endforeach; 
            ?>
        </div>

        <!-- Transparent Rates & Extra Guest Inclusions Banner -->
        <div class="amenities-rates-banner font-sans">
            <div class="arb-header">
                <div class="arb-badge"><i class="fa-solid fa-receipt"></i> TRANSPARENT TARIFF & EXTRA GUEST POLICY</div>
                <h3 class="arb-title font-serif">All-Inclusive Sanctuary Experience</h3>
                <p class="arb-subtitle">Every booking directly guarantees 100% farm-to-table breakfast, lunch, plantation tea, dinner, and all estate amenities.</p>
            </div>

            <div class="arb-grid">
                <!-- Card 1: Standard Stays -->
                <div class="arb-card">
                    <div class="arb-card-top">
                        <span class="arb-type font-serif">Canopy Treehouse</span>
                        <div class="arb-price font-serif">₹14,500 <small>/night</small></div>
                    </div>
                    <ul class="arb-list">
                        <li><i class="fa-solid fa-check"></i> <strong>Base: 2 Adult Guests</strong> included</li>
                        <li><i class="fa-solid fa-check"></i> All 4 Farm Gastronomy Meals</li>
                        <li><i class="fa-solid fa-check"></i> 30ft High Forest Canopy Deck</li>
                        <li><i class="fa-solid fa-check"></i> Orchard Walking Tour Included</li>
                    </ul>
                    <a href="booking.php?villa=treehouse" class="arb-btn">Reserve Treehouse &rarr;</a>
                </div>

                <!-- Card 2: Mudhouse Stays -->
                <div class="arb-card">
                    <div class="arb-card-top">
                        <span class="arb-type font-serif">Earthen Mudhouse</span>
                        <div class="arb-price font-serif">₹11,500 <small>/night</small></div>
                    </div>
                    <ul class="arb-list">
                        <li><i class="fa-solid fa-check"></i> <strong>Base: 2 Adult Guests</strong> included</li>
                        <li><i class="fa-solid fa-check"></i> All 4 Farm Gastronomy Meals</li>
                        <li><i class="fa-solid fa-check"></i> Ancient Thermal Cob Architecture</li>
                        <li><i class="fa-solid fa-check"></i> Orchard Harvest Walk Included</li>
                    </ul>
                    <a href="booking.php?villa=mudhouse" class="arb-btn">Reserve Mudhouse &rarr;</a>
                </div>

                <!-- Card 3: Duplex 2-Room Suite -->
                <div class="arb-card featured-arb">
                    <div class="arb-ribbon font-sans">BEST FOR FAMILIES</div>
                    <div class="arb-card-top">
                        <span class="arb-type font-serif">Duplex Chalet Suite</span>
                        <div class="arb-price font-serif">₹24,000 <small>/night</small></div>
                    </div>
                    <ul class="arb-list">
                        <li><i class="fa-solid fa-check"></i> <strong>Base: 4 Adult Guests</strong> included</li>
                        <li><i class="fa-solid fa-check"></i> Entire 2-Floor Private Residence</li>
                        <li><i class="fa-solid fa-check"></i> All 4 Farm Gastronomy Meals</li>
                        <li><i class="fa-solid fa-check"></i> Single room option @ ₹14,500/nt</li>
                    </ul>
                    <a href="booking.php?villa=duplex" class="arb-btn gold-arb-btn">Reserve Duplex Suite &rarr;</a>
                </div>

                <!-- Card 4: Extra Guests & Children -->
                <div class="arb-card policy-card">
                    <div class="arb-card-top">
                        <span class="arb-type font-serif">Extra Guest Pricing</span>
                        <div class="arb-price font-serif" style="font-size: 22px;">Simple &amp; Fair</div>
                    </div>
                    <ul class="arb-list">
                        <li>
                            <i class="fa-solid fa-user-plus"></i>
                            <div>
                                <strong>Extra Adult (12+ yrs):</strong>
                                <span class="badge-price">₹1,500 / night</span>
                                <small>Includes extra artisan bedding & all 4 organic meals</small>
                            </div>
                        </li>
                        <li>
                            <i class="fa-solid fa-child"></i>
                            <div>
                                <strong>Extra Child (5–11 yrs):</strong>
                                <span class="badge-price">₹800 / night</span>
                                <small>Includes farm meals & curated orchard activities</small>
                            </div>
                        </li>
                        <li>
                            <i class="fa-solid fa-baby"></i>
                            <div>
                                <strong>Infants (0–4 yrs):</strong>
                                <span class="badge-price badge-free">COMPLIMENTARY</span>
                                <small>Free stay with parents</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom CTA -->
            <div class="arb-footer text-center">
                <button type="button" class="btn-primary open-booking-modal-btn font-sans" style="padding: 14px 34px; font-size: 15px;">
                    <i class="fa-solid fa-calendar-check" style="margin-right: 8px;"></i>
                    <span>Check Live Availability &amp; Reserve</span>
                </button>
                <span class="arb-guarantee font-sans"><i class="fa-solid fa-shield-check"></i> 100% Best Direct Rate Guarantee • No Hidden Service Fees</span>
            </div>
        </div>

    </div>
</section>

<!-- Interactive Tab Switcher Script for Amenities Hub -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tabBtns = document.querySelectorAll('.amenity-tab-btn');
    var panels = document.querySelectorAll('.amenity-tab-panel');

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target-tab');
            
            tabBtns.forEach(function(b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            panels.forEach(function(p) {
                p.classList.remove('active');
            });

            var targetPanel = document.getElementById('panel-' + targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }

            // Scroll active tab into view on mobile
            var scroller = document.getElementById('amenities-tabs-scroller');
            if (scroller && window.innerWidth < 768) {
                var btnLeft = this.offsetLeft - 20;
                scroller.scrollTo({ left: btnLeft, behavior: 'smooth' });
            }
        });
    });
});
</script>
