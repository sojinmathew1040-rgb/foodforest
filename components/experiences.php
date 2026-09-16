<?php
require_once __DIR__ . '/../admin/includes/db.php';
$exp_badge = get_setting('experiences_badge', 'Activities');
$exp_title = get_setting('experiences_title', 'Rituals of the High Range');
$exp_desc = get_setting('experiences_desc', 'Connect deeply with the pulse of Kanthalloor. Each experience is handcrafted to immerse you in vernacular craftsmanship, mountain wilderness, and restorative tranquility.');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');
$experiences_list = get_experiences();

// Curated In-Depth Profiles for Sanctuary Activities
$experience_profiles = [
    1 => [
        'tagline' => 'Hand-pluck crisp mountain fruits in certified organic permaculture orchards.',
        'gallery' => [
            'assets/images/01 (18).jpeg',
            'assets/images/01 (19).jpeg',
            'assets/images/01 (20).jpeg',
            'assets/images/01 (27).jpeg'
        ],
        'full_desc' => 'Wander through our terraced mountain orchards accompanied by resident botanists and native horticulturists. Stroll through mist-kissed rows of heirloom apples, wild blackberries, passion fruit trellises, and sweet tamarillo (tree tomato) groves. Learn the ancestral principles of multi-tiered food forestry, regenerative compost cycles, and natural pest balancing without a single drop of synthetic chemicals. Pluck sun-ripened fruits right off the branch and savor freshly pressed organic juice prepared on-site.',
        'highlights' => [
            'Guided walk led by resident botanist & indigenous farmers',
            'Hand-pluck seasonal heirloom apples, blackberries & tree tomatoes',
            'Multi-layer permaculture & living soil biodiversity demo',
            'Freshly pressed organic orchard juice & fruit tasting session'
        ],
        'inclusions' => 'Handwoven harvest wicker basket, guided botanical notes, organic fruit tasting, freshly pressed estate juice.',
        'schedule' => 'Daily at 07:30 AM & 10:30 AM (Duration: 2 Hours)',
        'location' => 'Terraced Mountain Orchards & Botanical Nursery',
        'suitable_for' => 'Couples, Families, and Nature Enthusiasts of all ages',
        'what_to_bring' => 'Comfortable walking shoes, sun hat, and a light jacket for morning mist.'
    ],
    2 => [
        'tagline' => 'Twilight slate stone fires, hot cardamom brews, and ancestral mountain folklore.',
        'gallery' => [
            'assets/images/01 (30).jpeg',
            'assets/images/01 (31).jpeg',
            'assets/images/01 (32).jpeg',
            'assets/images/01 (7).jpeg'
        ],
        'full_desc' => 'As dusk blankets the Western Ghats with deep indigo mist and the high-range night turns crisp and cold, gather around our open slate stone fire pit beneath an unpolluted Milky Way sky. Warm your hands against dancing flames of teak embers and breathe in the rich aroma of mountain wood smoke. Sip piping hot cardamom and crushed ginger spiced mountain tea, enjoy wood-roasted sweet farm corn sprinkled with Marayoor sea salt, and listen to timeless legends of Kanthalloor’s ancient megalithic dolmens and tribal mountain lore told by indigenous elders.',
        'highlights' => [
            'Open slate hearth campfire beneath dark celestial skies',
            'Steaming Marayoor cardamom-ginger spiced brew & roasted farm corn',
            'Folk stories of tribal ancestors and high-range wildlife legends',
            'Acoustic native music and tranquil meditation by the embers'
        ],
        'inclusions' => 'Unlimited cardamom spiced farm tea, fire-roasted sweet corn, handwoven wool shawls for the mountain chill.',
        'schedule' => 'Every Evening • 07:00 PM to 09:30 PM',
        'location' => 'Central Amphitheater & Stone Hearth Courtyard',
        'suitable_for' => 'All residing guests seeking cozy evening tranquility',
        'what_to_bring' => 'Warm jacket or fleece sweater, camera for starry night photography.'
    ],
    3 => [
        'tagline' => 'Ground your hands in red earth, vetiver straw, and ancestral thermal architecture.',
        'gallery' => [
            'assets/images/01 (6).jpeg',
            'assets/images/01 (1).jpeg',
            'assets/images/01 (17).jpeg',
            'assets/images/01 (14).jpeg'
        ],
        'full_desc' => 'Connect deeply with the living earth beneath your feet. In this deeply tactile and grounding workshop, our master vernacular builders introduce you to the timeless art of earthen cob construction. Discover how native red earth, fine river sand, chopped vetiver grass, and slaked lime create breathable, thermally stable walls that keep interiors cool by day and cozy through chilly mountain nights. Knead the clay mix, sculpt miniature wall alcoves, and try your hand at smooth terracotta plastering using traditional wooden floats.',
        'highlights' => [
            'Hands-on mixing of native red clay, lime plaster, and vetiver straw',
            'Understanding thermal physics and breathable zero-carbon design',
            'Sculpting earthen wall niches, decorative reliefs & pottery forms',
            'Mentored by veteran native cob and thatch craftsmen'
        ],
        'inclusions' => 'Natural clay sculpting materials, protective studio aprons, traditional herbal tea and farm refreshment.',
        'schedule' => 'Tuesdays, Thursdays & Saturdays • 02:30 PM to 05:00 PM',
        'location' => 'The Artisan Cob Studio & Clay Courtyard',
        'suitable_for' => 'Adults, architecture buffs, curious creative souls, and kids',
        'what_to_bring' => 'Comfortable clothes you do not mind getting clay on, slip-on shoes.'
    ],
    4 => [
        'tagline' => 'Ascend misty high-range ridges to witness golden dawn across the Anaimudi peaks.',
        'gallery' => [
            'assets/images/01 (33).jpeg',
            'assets/images/01 (34).jpeg',
            'assets/images/01 (35).jpeg',
            'assets/images/01 (28).jpeg'
        ],
        'full_desc' => 'Begin before first light, ascending along ancient forest trails through fragrant wild lemongrass meadows, private sandalwood groves, and emerald tea estate fringes. Reach the panoramic ridge just as the first amber rays ignite the mist rolling off the Anaimudi peak range and the expansive Marayoor valley below. Enjoy freshly steeped estate black tea poured from thermos flasks with hot organic harvest pastries atop the cliff while spotting rare high-altitude birds such as the Nilgiri Pipit and Malabar Whistling Thrush.',
        'highlights' => [
            'Guided 5km sunrise trek through sandalwood & tea estate frontiers',
            'Breathtaking 360-degree dawn panorama across the Western Ghats',
            'Cliffside tea ceremony with freshly steeped high-altitude black tea',
            'Birdwatching & wildlife tracking with our native naturalist'
        ],
        'inclusions' => 'Hand-carved wooden trekking pole, thermos mountain tea, organic fruit & nut energy packs, binoculars.',
        'schedule' => 'Daily Departure at 05:45 AM Sharp (Duration: 3.5 Hours)',
        'location' => 'Departs from Sanctuary Welcome Lounge',
        'suitable_for' => 'Guests with moderate fitness levels (beginner-to-intermediate trail)',
        'what_to_bring' => 'Sturdy walking / hiking footwear, windbreaker or jacket, reusable water flask.'
    ]
];

// Build JSON payload for the frontend (Dynamic DB with Profile fallbacks)
$activities_modal_data = [];
foreach ($experiences_list as $exp) {
    $id = (int)$exp['id'];
    $profile = $experience_profiles[$id] ?? null;
    
    // Gallery: prioritize DB gallery_list, then profile, then main image
    $gallery = !empty($exp['gallery_list']) ? $exp['gallery_list'] : (!empty($profile['gallery']) ? $profile['gallery'] : [$exp['image_url']]);
    if (!empty($exp['image_url']) && !in_array($exp['image_url'], $gallery)) {
        array_unshift($gallery, $exp['image_url']);
    }
    
    // Highlights: prioritize DB highlights_list, then profile, then fallback
    $highlights = !empty($exp['highlights_list']) ? $exp['highlights_list'] : ($profile['highlights'] ?? [
        'Bespoke guided sanctuary experience',
        'Led by native naturalist and estate experts',
        'Authentic high-range immersion in Kanthalloor',
        'All gear and farm refreshments included'
    ]);
    
    $activities_modal_data[$id] = [
        'id' => $id,
        'title' => $exp['title'],
        'badge' => $exp['badge'],
        'timing' => $exp['timing'],
        'short_desc' => $exp['description'],
        'image_url' => $exp['image_url'],
        'gallery' => $gallery,
        'tagline' => !empty($exp['tagline']) ? $exp['tagline'] : ($profile['tagline'] ?? $exp['description']),
        'full_desc' => !empty($exp['detailed_description']) ? $exp['detailed_description'] : ($profile['full_desc'] ?? $exp['description']),
        'highlights' => $highlights,
        'inclusions' => !empty($exp['inclusions']) ? $exp['inclusions'] : ($profile['inclusions'] ?? 'Guided tour, seasonal farm refreshments, all necessary equipment.'),
        'schedule' => !empty($exp['schedule_info']) ? $exp['schedule_info'] : ($profile['schedule'] ?? $exp['timing']),
        'location' => !empty($exp['location_info']) ? $exp['location_info'] : ($profile['location'] ?? 'Food Forest Sanctuary, Kanthalloor'),
        'suitable_for' => !empty($exp['suitable_for']) ? $exp['suitable_for'] : ($profile['suitable_for'] ?? 'All resident guests'),
        'what_to_bring' => !empty($exp['what_to_bring']) ? $exp['what_to_bring'] : ($profile['what_to_bring'] ?? 'Comfortable walking shoes, light warm layer, camera.')
    ];
}
?>
<!-- Curated Experiences / Activities Section -->
<section id="experiences" class="experiences-section section-padding">
    <div class="container">
        
        <div class="experiences-header text-center">
            <span class="section-label"><?php echo htmlspecialchars($exp_badge); ?></span>
            <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars($exp_title); ?></h3>
            <p class="experiences-desc font-sans" style="color: var(--text-light); max-width: 750px; margin: 15px auto 0;">
                <?php echo htmlspecialchars($exp_desc); ?>
            </p>
        </div>
        
        <div class="experiences-grid">
            <?php if (!empty($experiences_list)): ?>
                <?php foreach ($experiences_list as $idx => $exp): 
                    $delay = ($idx % 4) * 0.1;
                ?>
                    <!-- Dynamic Experience Card -->
                    <div class="experience-card scroll-reveal" style="transition-delay: <?php echo $delay; ?>s;" data-exp-id="<?php echo (int)$exp['id']; ?>">
                        <div class="experience-img-wrapper">
                            <img src="<?php echo htmlspecialchars($exp['image_url']); ?>" alt="<?php echo htmlspecialchars($exp['title']); ?>" class="experience-img" onerror="this.src='assets/images/treehouse_exterior.png'">
                            <div class="experience-badges">
                                <span class="exp-badge font-sans"><?php echo htmlspecialchars($exp['badge']); ?></span>
                                <span class="exp-timing font-sans"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($exp['timing']); ?></span>
                            </div>
                        </div>
                        <div class="experience-info">
                            <h4 class="experience-card-title font-serif"><?php echo htmlspecialchars($exp['title']); ?></h4>
                            <p class="experience-card-desc font-sans">
                                <?php echo htmlspecialchars($exp['description']); ?>
                            </p>
                            <button type="button" class="experience-arrow font-sans open-activity-detail-btn" data-exp-id="<?php echo (int)$exp['id']; ?>" aria-label="View details of <?php echo htmlspecialchars($exp['title']); ?>">
                                <span>View in Detail</span> <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</section>

<!-- Luxury In-Depth Activity Detail Modal -->
<div id="activity-detail-modal" class="activity-modal-overlay" aria-hidden="true" role="dialog" data-lenis-prevent>
    <div class="activity-modal-backdrop" id="activity-modal-backdrop"></div>
    <div class="activity-modal-container" data-lenis-prevent>
        <button type="button" class="activity-modal-close" id="activity-modal-close" aria-label="Close Activity Details">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="activity-modal-inner">
            <!-- Left: Interactive Gallery Showcase -->
            <div class="activity-modal-gallery-pane">
                <div class="activity-modal-main-frame">
                    <img id="act-modal-main-img" src="" alt="Activity Preview" class="activity-modal-main-img">
                    <div class="activity-modal-badges">
                        <span id="act-modal-badge" class="act-badge font-sans"></span>
                        <span id="act-modal-timing" class="act-timing font-sans"><i class="fa-regular fa-clock"></i> <span></span></span>
                    </div>
                    <button type="button" class="act-nav-arrow act-nav-prev" id="act-nav-prev" aria-label="Previous photo">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button type="button" class="act-nav-arrow act-nav-next" id="act-nav-next" aria-label="Next photo">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <div class="act-photo-counter font-sans" id="act-photo-counter">1 / 4</div>
                </div>

                <!-- Clickable Thumbnails Strip -->
                <div class="activity-modal-thumbs" id="act-modal-thumbs"></div>

                <!-- Practical Meta Box -->
                <div class="activity-quick-meta-box font-sans">
                    <div class="meta-item">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <div>
                            <strong>Schedule</strong>
                            <span id="act-modal-schedule"></span>
                        </div>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>Location</strong>
                            <span id="act-modal-location"></span>
                        </div>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-users"></i>
                        <div>
                            <strong>Suitable For</strong>
                            <span id="act-modal-suitable"></span>
                        </div>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-shoe-prints"></i>
                        <div>
                            <strong>What to Bring</strong>
                            <span id="act-modal-bring"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: In-Depth Details & CTAs -->
            <div class="activity-modal-info-pane">
                <span class="activity-modal-subtitle font-sans">SANCTUARY RITUALS • KANTHALLOOR</span>
                <h3 class="activity-modal-title font-serif" id="act-modal-title"></h3>
                <p class="activity-modal-tagline font-sans" id="act-modal-tagline"></p>

                <div class="activity-modal-divider"></div>

                <div class="activity-modal-section">
                    <h5 class="section-sub-title font-serif">The Experience</h5>
                    <p class="activity-modal-desc font-sans" id="act-modal-desc"></p>
                </div>

                <div class="activity-modal-section">
                    <h5 class="section-sub-title font-serif">Experience Highlights</h5>
                    <ul class="activity-highlights-list font-sans" id="act-modal-highlights"></ul>
                </div>

                <div class="activity-modal-section">
                    <h5 class="section-sub-title font-serif">What's Included</h5>
                    <div class="activity-inclusions-text font-sans" id="act-modal-inclusions"></div>
                </div>

                <!-- Actions / CTAs -->
                <div class="activity-modal-cta-row">
                    <button type="button" class="btn-primary act-book-btn open-booking-modal-btn font-sans" id="act-reserve-btn">
                        <span>Reserve Experience</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>" target="_blank" class="act-wa-btn font-sans" id="act-wa-btn">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp Concierge</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JSON Payload for Activity Modal -->
<script id="activities-json-data" type="application/json">
<?php echo json_encode($activities_modal_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
</script>

<script>
(function() {
    function setupActivityModal() {
        var modal = document.getElementById('activity-detail-modal');
        var dataScript = document.getElementById('activities-json-data');
        if (!modal || !dataScript) return;

        var activitiesData = {};
        try {
            activitiesData = JSON.parse(dataScript.textContent);
        } catch (e) {
            return;
        }

        var backdrop = document.getElementById('activity-modal-backdrop');
        var closeBtn = document.getElementById('activity-modal-close');
        var mainImg = document.getElementById('act-modal-main-img');
        var badgeEl = document.getElementById('act-modal-badge');
        var timingEl = document.querySelector('#act-modal-timing span');
        var counterEl = document.getElementById('act-photo-counter');
        var thumbsContainer = document.getElementById('act-modal-thumbs');
        var prevBtn = document.getElementById('act-nav-prev');
        var nextBtn = document.getElementById('act-nav-next');
        var titleEl = document.getElementById('act-modal-title');
        var taglineEl = document.getElementById('act-modal-tagline');
        var descEl = document.getElementById('act-modal-desc');
        var highlightsEl = document.getElementById('act-modal-highlights');
        var inclusionsEl = document.getElementById('act-modal-inclusions');
        var scheduleEl = document.getElementById('act-modal-schedule');
        var locationEl = document.getElementById('act-modal-location');
        var suitableEl = document.getElementById('act-modal-suitable');
        var bringEl = document.getElementById('act-modal-bring');
        var reserveBtn = document.getElementById('act-reserve-btn');
        var waBtn = document.getElementById('act-wa-btn');

        var currentActivity = null;
        var currentPhotoIdx = 0;

        function openModal(id) {
            var act = activitiesData[id];
            if (!act) return;

            currentActivity = act;
            currentPhotoIdx = 0;

            if (titleEl) titleEl.textContent = act.title;
            if (taglineEl) taglineEl.textContent = act.tagline;
            if (descEl) descEl.textContent = act.full_desc;
            if (badgeEl) badgeEl.textContent = act.badge;
            if (timingEl) timingEl.textContent = act.timing;
            if (inclusionsEl) inclusionsEl.textContent = act.inclusions;
            if (scheduleEl) scheduleEl.textContent = act.schedule;
            if (locationEl) locationEl.textContent = act.location;
            if (suitableEl) suitableEl.textContent = act.suitable_for;
            if (bringEl) bringEl.textContent = act.what_to_bring;

            // Render Highlights
            if (highlightsEl) {
                highlightsEl.innerHTML = '';
                (act.highlights || []).forEach(function(item) {
                    var li = document.createElement('li');
                    li.className = 'highlight-item';
                    li.innerHTML = '<i class="fa-solid fa-leaf"></i> <span>' + item + '</span>';
                    highlightsEl.appendChild(li);
                });
            }

            // WhatsApp link
            if (waBtn) {
                var conciergeWa = '<?php echo htmlspecialchars($concierge_wa); ?>';
                var text = encodeURIComponent('Hello Food Forest Concierge, I would like to inquire about reserving the "' + act.title + '" experience.');
                waBtn.href = 'https://wa.me/' + conciergeWa + '?text=' + text;
            }

            // Reserve CTA button
            if (reserveBtn) {
                reserveBtn.onclick = function() {
                    closeModal();
                    var notesField = document.getElementById('modal-notes');
                    if (notesField) {
                        notesField.value = 'Interested in Activity: ' + act.title;
                    }
                    if (typeof window.openBookingModal === 'function') {
                        window.openBookingModal();
                    } else {
                        var anyBookBtn = document.querySelector('.btn-book-now');
                        if (anyBookBtn) anyBookBtn.click();
                    }
                };
            }

            renderGallery();

            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function renderGallery() {
            if (!currentActivity || !currentActivity.gallery || !currentActivity.gallery.length) return;
            var gallery = currentActivity.gallery;
            if (currentPhotoIdx >= gallery.length) currentPhotoIdx = 0;
            if (currentPhotoIdx < 0) currentPhotoIdx = gallery.length - 1;

            if (mainImg) {
                mainImg.style.opacity = '0.35';
                mainImg.src = gallery[currentPhotoIdx];
                mainImg.onload = function() {
                    mainImg.style.opacity = '1';
                };
            }

            if (counterEl) {
                counterEl.textContent = (currentPhotoIdx + 1) + ' / ' + gallery.length;
            }

            if (thumbsContainer) {
                thumbsContainer.innerHTML = '';
                gallery.forEach(function(url, idx) {
                    var thumb = document.createElement('div');
                    thumb.className = 'act-thumb' + (idx === currentPhotoIdx ? ' active' : '');
                    thumb.innerHTML = '<img src="' + url + '" alt="Thumbnail ' + (idx + 1) + '">';
                    thumb.addEventListener('click', function(e) {
                        e.stopPropagation();
                        currentPhotoIdx = idx;
                        renderGallery();
                    });
                    thumbsContainer.appendChild(thumb);
                });
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!currentActivity || !currentActivity.gallery) return;
                currentPhotoIdx = (currentPhotoIdx - 1 + currentActivity.gallery.length) % currentActivity.gallery.length;
                renderGallery();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!currentActivity || !currentActivity.gallery) return;
                currentPhotoIdx = (currentPhotoIdx + 1) % currentActivity.gallery.length;
                renderGallery();
            });
        }

        // Bind clicks to "View in Detail" button or entire card
        document.querySelectorAll('.open-activity-detail-btn, .experience-card').forEach(function(el) {
            el.addEventListener('click', function(e) {
                var card = el.closest('.experience-card') || el;
                var expId = el.getAttribute('data-exp-id') || card.getAttribute('data-exp-id');
                if (expId) {
                    e.preventDefault();
                    e.stopPropagation();
                    openModal(expId);
                }
            });
        });

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);

        window.addEventListener('keydown', function(e) {
            if (!modal.classList.contains('active')) return;
            if (e.key === 'Escape') closeModal();
            if (e.key === 'ArrowLeft' && prevBtn) prevBtn.click();
            if (e.key === 'ArrowRight' && nextBtn) nextBtn.click();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupActivityModal);
    } else {
        setupActivityModal();
    }
})();
</script>
