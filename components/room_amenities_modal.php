<?php
// =========================================================================
// Food Forest Sanctuary — Chalet Inclusions & Room Amenities Modal
// Linked with corresponding rooms (Canopy Treehouse & Earthen Mudhouse)
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
        'title' => 'Room & Chalet Features',
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
            ['name' => 'Hairdryer & Shaving Mirror', 'desc' => 'Convenient grooming accessories provided in room or available on request.', 'icon' => 'fa-solid fa-wind', 'highlight' => false],
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

// Room-specific unique highlights
$room_features = [
    'treehouse' => [
        'title' => 'The Canopy Treehouse',
        'tagline' => 'Elevated 30 Feet in Ancient Trees • Floor-to-Ceiling Curved Bay Window',
        'icon' => 'fa-solid fa-tree',
        'badge' => '30FT ELEVATION SUITE',
        'rate' => '₹14,500 / night',
        'specific_items' => [
            ['name' => '30ft High Canopy Cantilevered Deck', 'desc' => 'Private suspended timber deck immersed in clouds and tree-canopy birdsong.', 'icon' => 'fa-solid fa-wind'],
            ['name' => 'Curved 180° Panoramic Glasswork', 'desc' => 'Floor-to-ceiling panoramic glass framing shifting mountain mist and tea horizon.', 'icon' => 'fa-solid fa-mountain-sun'],
            ['name' => 'Hand-Hewn Wild Teak Bed Suite', 'desc' => 'Master artisan king bed dressed in pure breathable high-thread organic cotton.', 'icon' => 'fa-solid fa-bed'],
            ['name' => 'Stargazing Balcony Telescope', 'desc' => 'Private high-resolution telescope provided for observing the pristine night sky.', 'icon' => 'fa-solid fa-star']
        ]
    ],
    'mudhouse' => [
        'title' => 'The Earthen Mudhouse',
        'tagline' => 'Hand-Sculpted Cob Architecture • Natural Thermal Insulation & Cob Veranda',
        'icon' => 'fa-solid fa-house-chimney',
        'badge' => 'COB HERITAGE SUITE',
        'rate' => '₹11,500 / night',
        'specific_items' => [
            ['name' => 'Natural Earthen Cob Thermal Cooling', 'desc' => 'Breathable red clay and straw walls naturally keeping indoor air cool by day and warm by night.', 'icon' => 'fa-solid fa-temperature-arrow-down'],
            ['name' => 'Private Earthen Courtyard Veranda', 'desc' => 'Breathable terracotta courtyard connecting guest quarters directly with the soil.', 'icon' => 'fa-solid fa-couch'],
            ['name' => 'Slate Hearth Indoor Fireplace', 'desc' => 'Authentic stone fireplace for cozy slow evenings accompanied by crackling embers.', 'icon' => 'fa-solid fa-fire'],
            ['name' => 'Direct Orchard Herb Garden Access', 'desc' => 'Step right outside into heirloom apple orchards and organic culinary herb beds.', 'icon' => 'fa-solid fa-seedling']
        ]
    ]
];
?>

<!-- Chalet Inclusions & Room Amenities Modal -->
<div id="modal-room-amenities" class="ram-modal-backdrop" style="display: none;">
    <div class="ram-modal-container">
        
        <!-- Modal Header -->
        <div class="ram-modal-header">
            <div class="ram-header-info">
                <div class="ram-badge-row">
                    <span class="ram-badge-pill font-sans" id="ram-header-badge"><i class="fa-solid fa-sparkles"></i> SANCTUARY INCLUSIONS</span>
                    <span class="ram-verified-tag font-sans"><i class="fa-solid fa-circle-check"></i> ALL INCLUSIVE STAY</span>
                </div>
                <h3 class="ram-title font-serif" id="ram-chalet-title">The Canopy Treehouse Inclusions</h3>
                <p class="ram-subtitle font-sans" id="ram-chalet-subtitle">
                    Elevated 30 Feet in Ancient Trees • Floor-to-Ceiling Curved Bay Window
                </p>
            </div>
            <button type="button" class="ram-modal-close" onclick="closeRoomAmenitiesModal();" aria-label="Close Inclusions Modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Room Switcher Tabs inside Modal -->
        <div class="ram-room-switcher font-sans">
            <button type="button" class="ram-room-btn active" id="ram-btn-treehouse" onclick="switchRoomAmenitiesChalet('treehouse');">
                <i class="fa-solid fa-tree"></i>
                <span>Canopy Treehouse (₹14,500)</span>
            </button>
            <button type="button" class="ram-room-btn" id="ram-btn-mudhouse" onclick="switchRoomAmenitiesChalet('mudhouse');">
                <i class="fa-solid fa-house-chimney"></i>
                <span>Earthen Mudhouse (₹11,500)</span>
            </button>
        </div>

        <!-- Room Specific Highlight Banner -->
        <div class="ram-chalet-hero-strip font-sans">
            <div class="ram-strip-heading">
                <span id="ram-spec-badge"><i class="fa-solid fa-star"></i> SIGNATURE ARCHITECTURE INCLUSIONS</span>
            </div>
            <div class="ram-spec-grid" id="ram-spec-grid">
                <!-- Dynamically populated via JS -->
            </div>
        </div>

        <!-- Category Filter Tabs -->
        <div class="ram-cat-tabs-wrap">
            <div class="ram-cat-tabs-scroller font-sans" id="ram-cat-tabs-scroller">
                <?php 
                $c_idx = 0;
                foreach ($amenity_categories as $c_key => $cat): 
                    $c_active = ($c_idx === 0);
                ?>
                    <button type="button" 
                            class="ram-cat-tab <?php echo $c_active ? 'active' : ''; ?>" 
                            data-cat-target="<?php echo htmlspecialchars($c_key); ?>"
                            onclick="switchRoomAmenitiesCategory('<?php echo htmlspecialchars($c_key); ?>', this);">
                        <i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($cat['title']); ?></span>
                        <span class="ram-cat-count"><?php echo count($cat['items']); ?></span>
                    </button>
                <?php 
                    $c_idx++;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- Category Content Panels -->
        <div class="ram-modal-body">
            <?php 
            $panel_idx = 0;
            foreach ($amenity_categories as $c_key => $cat): 
                $p_active = ($panel_idx === 0);
            ?>
                <div class="ram-cat-panel <?php echo $p_active ? 'active' : ''; ?>" id="ram-panel-<?php echo htmlspecialchars($c_key); ?>">
                    <div class="ram-panel-header">
                        <div>
                            <span class="ram-panel-badge font-sans"><?php echo htmlspecialchars($cat['badge']); ?></span>
                            <h4 class="ram-panel-title font-serif"><?php echo htmlspecialchars($cat['title']); ?></h4>
                            <p class="ram-panel-desc font-sans"><?php echo htmlspecialchars($cat['subtitle']); ?></p>
                        </div>
                        <div class="ram-verified-count font-sans">
                            <i class="fa-solid fa-circle-check"></i> <?php echo count($cat['items']); ?> Verified Inclusions
                        </div>
                    </div>

                    <div class="ram-items-grid">
                        <?php foreach ($cat['items'] as $item): ?>
                            <div class="ram-item-card font-sans <?php echo !empty($item['highlight']) ? 'is-featured' : ''; ?>">
                                <div class="ram-item-icon-box">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                </div>
                                <div class="ram-item-info">
                                    <div class="ram-item-name-row">
                                        <h5 class="ram-item-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <?php if (!empty($item['highlight'])): ?>
                                            <span class="ram-item-featured-tag"><i class="fa-solid fa-star"></i> Included</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="ram-item-desc"><?php echo htmlspecialchars($item['desc']); ?></p>
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

        <!-- Modal Footer CTA -->
        <div class="ram-modal-footer">
            <div class="ram-footer-trust font-sans">
                <span><i class="fa-solid fa-utensils"></i> All 4 Organic Farm Meals Included</span>
                <span><i class="fa-solid fa-shield-halved"></i> 100% Verified Amenities</span>
            </div>
            <div class="ram-footer-actions">
                <button type="button" class="ram-btn-outline font-sans" onclick="closeRoomAmenitiesModal();">Close</button>
                <button type="button" class="ram-btn-gold font-sans" id="ram-btn-reserve" onclick="bookFromAmenitiesModal();">
                    <span id="ram-btn-reserve-text">Reserve This Chalet</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<style>
/* Chalet Inclusions Modal Luxury Styling */
.ram-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(4, 12, 7, 0.85);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: ramFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes ramFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.ram-modal-container {
    background: #0D1C13;
    border: 1.5px solid rgba(197, 160, 89, 0.4);
    border-radius: 16px;
    width: 100%;
    max-width: 960px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.7), 0 0 30px rgba(197, 160, 89, 0.15);
    overflow: hidden;
    position: relative;
    animation: ramSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes ramSlideUp {
    from { transform: translateY(20px) scale(0.98); }
    to { transform: translateY(0) scale(1); }
}

.ram-modal-header {
    padding: 24px 28px 18px;
    border-bottom: 1px solid rgba(197, 160, 89, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: linear-gradient(180deg, rgba(20, 42, 28, 0.8) 0%, rgba(13, 28, 19, 0.95) 100%);
}

.ram-badge-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.ram-badge-pill {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #C5A059;
    background: rgba(197, 160, 89, 0.15);
    border: 1px solid rgba(197, 160, 89, 0.35);
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.ram-verified-tag {
    font-size: 11px;
    font-weight: 700;
    color: #34D399;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    padding: 3px 10px;
    border-radius: 20px;
}

.ram-title {
    font-size: 24px;
    font-weight: 700;
    color: #F7F5F0;
    margin: 4px 0 2px;
}

.ram-subtitle {
    font-size: 13px;
    color: rgba(234, 239, 237, 0.7);
    margin: 0;
}

.ram-modal-close {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #EAEFED;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ram-modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: #EF4444;
    color: #F87171;
    transform: rotate(90deg);
}

/* Room Switcher */
.ram-room-switcher {
    display: flex;
    gap: 12px;
    padding: 12px 28px;
    background: rgba(0, 0, 0, 0.25);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.ram-room-btn {
    flex: 1;
    padding: 10px 16px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.04);
    color: #A1B5A9;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.ram-room-btn.active {
    background: rgba(197, 160, 89, 0.2);
    border-color: #C5A059;
    color: #F5E8C7;
    box-shadow: 0 0 12px rgba(197, 160, 89, 0.25);
}

.ram-room-btn:hover:not(.active) {
    background: rgba(255, 255, 255, 0.08);
    color: #FFFFFF;
}

/* Signature Inclusions Strip */
.ram-chalet-hero-strip {
    padding: 14px 28px;
    background: rgba(197, 160, 89, 0.07);
    border-bottom: 1px solid rgba(197, 160, 89, 0.18);
}

.ram-strip-heading {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #D4AF37;
    margin-bottom: 8px;
}

.ram-spec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.ram-spec-item {
    background: rgba(16, 35, 24, 0.8);
    border: 1px solid rgba(197, 160, 89, 0.25);
    border-radius: 8px;
    padding: 8px 12px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.ram-spec-item i {
    color: #C5A059;
    font-size: 14px;
    margin-top: 3px;
}

.ram-spec-item h6 {
    margin: 0 0 2px;
    font-size: 12px;
    font-weight: 700;
    color: #FFFFFF;
}

.ram-spec-item p {
    margin: 0;
    font-size: 11px;
    color: rgba(234, 239, 237, 0.7);
    line-height: 1.35;
}

/* Category Tabs */
.ram-cat-tabs-wrap {
    background: #09150E;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    overflow-x: auto;
}

.ram-cat-tabs-scroller {
    display: flex;
    gap: 4px;
    padding: 8px 24px;
    min-width: max-content;
}

.ram-cat-tab {
    padding: 8px 14px;
    border-radius: 20px;
    border: 1px solid transparent;
    background: transparent;
    color: #8EAA97;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: all 0.18s ease;
    white-space: nowrap;
}

.ram-cat-tab:hover {
    color: #FFFFFF;
    background: rgba(255, 255, 255, 0.05);
}

.ram-cat-tab.active {
    background: #1C3826;
    border-color: rgba(197, 160, 89, 0.4);
    color: #EAEFED;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.ram-cat-tab.active i {
    color: #C5A059;
}

.ram-cat-count {
    font-size: 10px;
    background: rgba(0, 0, 0, 0.35);
    padding: 1px 6px;
    border-radius: 10px;
    color: #A1B5A9;
}

/* Modal Body */
.ram-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 28px;
    scrollbar-width: thin;
    scrollbar-color: rgba(197, 160, 89, 0.4) transparent;
}

.ram-cat-panel {
    display: none;
}

.ram-cat-panel.active {
    display: block;
    animation: ramPanelFade 0.2s ease;
}

@keyframes ramPanelFade {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.ram-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 12px;
}

.ram-panel-badge {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #C5A059;
    letter-spacing: 0.5px;
}

.ram-panel-title {
    font-size: 20px;
    color: #F7F5F0;
    margin: 3px 0 2px;
}

.ram-panel-desc {
    font-size: 12.5px;
    color: rgba(234, 239, 237, 0.65);
    margin: 0;
}

.ram-verified-count {
    font-size: 12px;
    font-weight: 700;
    color: #34D399;
    white-space: nowrap;
}

.ram-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 14px;
}

.ram-item-card {
    background: rgba(18, 38, 26, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 14px;
    display: flex;
    gap: 12px;
    transition: all 0.2s ease;
}

.ram-item-card:hover {
    background: rgba(25, 52, 36, 0.85);
    border-color: rgba(197, 160, 89, 0.35);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
}

.ram-item-card.is-featured {
    border-color: rgba(197, 160, 89, 0.28);
    background: linear-gradient(145deg, rgba(25, 48, 33, 0.75) 0%, rgba(14, 30, 21, 0.85) 100%);
}

.ram-item-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(197, 160, 89, 0.12);
    border: 1px solid rgba(197, 160, 89, 0.25);
    color: #C5A059;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.ram-item-info {
    flex: 1;
}

.ram-item-name-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    margin-bottom: 4px;
}

.ram-item-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #FFFFFF;
    margin: 0;
}

.ram-item-featured-tag {
    font-size: 9.5px;
    font-weight: 700;
    color: #F5E8C7;
    background: rgba(197, 160, 89, 0.25);
    border: 1px solid rgba(197, 160, 89, 0.4);
    padding: 1px 6px;
    border-radius: 4px;
    white-space: nowrap;
}

.ram-item-desc {
    font-size: 12px;
    color: rgba(234, 239, 237, 0.7);
    line-height: 1.4;
    margin: 0;
}

/* Modal Footer */
.ram-modal-footer {
    padding: 16px 28px;
    border-top: 1px solid rgba(197, 160, 89, 0.2);
    background: #09150E;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.ram-footer-trust {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #34D399;
    font-weight: 600;
}

.ram-footer-trust span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.ram-footer-actions {
    display: flex;
    gap: 10px;
}

.ram-btn-outline {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #EAEFED;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ram-btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
}

.ram-btn-gold {
    background: linear-gradient(135deg, #D4AF37 0%, #C5A059 100%);
    border: none;
    color: #08150D;
    padding: 9px 22px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 14px rgba(197, 160, 89, 0.35);
}

.ram-btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(197, 160, 89, 0.5);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .ram-modal-container {
        max-height: 94vh;
        border-radius: 12px;
    }
    .ram-modal-header,
    .ram-room-switcher,
    .ram-chalet-hero-strip,
    .ram-modal-body,
    .ram-modal-footer {
        padding-left: 16px;
        padding-right: 16px;
    }
    .ram-items-grid {
        grid-template-columns: 1fr;
    }
    .ram-footer-trust {
        display: none;
    }
    .ram-footer-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
window.CHALET_FEATURES_DATA = <?php echo json_encode($room_features); ?>;
window.currentAmenityRoom = 'treehouse';

function openRoomAmenitiesModal(stayType) {
    stayType = (stayType === 'mudhouse' || stayType === 'mud') ? 'mudhouse' : 'treehouse';
    window.currentAmenityRoom = stayType;

    const modal = document.getElementById('modal-room-amenities');
    if (!modal) return;

    switchRoomAmenitiesChalet(stayType);
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRoomAmenitiesModal() {
    const modal = document.getElementById('modal-room-amenities');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function switchRoomAmenitiesChalet(stayType) {
    window.currentAmenityRoom = stayType;
    const data = window.CHALET_FEATURES_DATA[stayType] || window.CHALET_FEATURES_DATA['treehouse'];

    // Update Header
    const titleEl = document.getElementById('ram-chalet-title');
    const subEl = document.getElementById('ram-chalet-subtitle');
    const badgeEl = document.getElementById('ram-header-badge');
    const btnTree = document.getElementById('ram-btn-treehouse');
    const btnMud = document.getElementById('ram-btn-mudhouse');
    const reserveBtnText = document.getElementById('ram-btn-reserve-text');

    if (titleEl) titleEl.innerText = data.title + ' Inclusions';
    if (subEl) subEl.innerText = data.tagline;
    if (badgeEl) badgeEl.innerHTML = '<i class="' + data.icon + '"></i> ' + data.badge;

    if (btnTree) btnTree.classList.toggle('active', stayType === 'treehouse');
    if (btnMud) btnMud.classList.toggle('active', stayType === 'mudhouse');
    if (reserveBtnText) reserveBtnText.innerText = 'Reserve ' + data.title;

    // Populate Specific Architecture Items
    const specGrid = document.getElementById('ram-spec-grid');
    if (specGrid && data.specific_items) {
        specGrid.innerHTML = data.specific_items.map(item => `
            <div class="ram-spec-item">
                <i class="${item.icon}"></i>
                <div>
                    <h6>${item.name}</h6>
                    <p>${item.desc}</p>
                </div>
            </div>
        `).join('');
    }
}

function switchRoomAmenitiesCategory(catKey, tabBtn) {
    document.querySelectorAll('.ram-cat-tab').forEach(t => t.classList.remove('active'));
    if (tabBtn) tabBtn.classList.add('active');

    document.querySelectorAll('.ram-cat-panel').forEach(p => p.classList.remove('active'));
    const targetPanel = document.getElementById('ram-panel-' + catKey);
    if (targetPanel) {
        targetPanel.classList.add('active');
    }
}

function bookFromAmenitiesModal() {
    const stay = window.currentAmenityRoom || 'treehouse';
    closeRoomAmenitiesModal();
    if (typeof openBookingModalWithChalet === 'function') {
        openBookingModalWithChalet(stay);
    } else {
        const btn = document.querySelector(`.open-booking-modal-btn[data-villa="${stay}"]`) || document.querySelector('.open-booking-modal-btn');
        if (btn) btn.click();
    }
}

// Close on backdrop click or Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRoomAmenitiesModal();
    }
});
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modal-room-amenities');
    if (modal && e.target === modal) {
        closeRoomAmenitiesModal();
    }
});
</script>
