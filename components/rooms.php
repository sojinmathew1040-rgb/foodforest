<!-- WebGL 3D Rooms Experience Section -->
<section id="rooms-experience">
    <!-- Room Selector Tabs (Premium floating UI) -->
    <div class="room-selector-container">
        <button class="room-select-btn active magnetic" data-room="treehouse" data-strength="10">CANOPY
            TREEHOUSE</button>
        <button class="room-select-btn magnetic" data-room="mudhouse" data-strength="10">EARTHEN MUDHOUSE</button>
    </div>

    <!-- Three.js Canvas -->
    <canvas id="rooms-webgl-canvas"></canvas>

    <!-- Cinematic Vignette Overlay -->
    <div class="webgl-vignette"></div>

    <!-- HTML Floating Content (Syncs with active stays and scroll progress) -->
    <div class="rooms-text-overlay">

        <!-- Slide 1: Tree House -->
        <div class="room-slide room-slide-treehouse active" id="slide-treehouse">
            <div class="room-info-box">
                <span class="room-type font-sans">Canopy Farm Stay</span>
                <h3 class="room-name font-serif">Luxury Tree House</h3>
                <p class="room-desc font-sans">
                    Perched high in the canopy, our luxury treehouse is one of the two unique farm stays at Food Forest. Crafted with natural timber and large circular bay windows, it feels like floating inside nature.
                </p>
                <ul class="room-features font-sans">
                    <li><i class="fa-solid fa-tree"></i> Elevated 30ft above forest floor</li>
                    <li><i class="fa-solid fa-wind"></i> Large circular viewing window</li>
                    <li><i class="fa-solid fa-cloud-sun-rain"></i> Private misty valley balcony</li>
                </ul>
                <a href="#contact" class="btn-primary room-btn magnetic" data-strength="15">
                    <span>Reserve Stay</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Slide 2: Mud House -->
        <div class="room-slide room-slide-mudhouse" id="slide-mudhouse" style="display: none; opacity: 0;">
            <div class="room-info-box">
                <span class="room-type font-sans">Earthen Farm Stay</span>
                <h3 class="room-name font-serif">Traditional Mud House</h3>
                <p class="room-desc font-sans">
                    The traditional mud house is the second unique farm stay type at our Food Forest retreat. Built using native soil, grass, and wood, the thick clay walls naturally regulate temperatures.
                </p>
                <ul class="room-features font-sans">
                    <li><i class="fa-solid fa-temperature-arrow-down"></i> Natural thermal clay insulation</li>
                    <li><i class="fa-solid fa-couch"></i> Semi-private veranda courtyard</li>
                    <li><i class="fa-solid fa-fire"></i> Cozy ambient interior fire pit</li>
                    <li><i class="fa-solid fa-bed"></i> Organic hand-loomed bedding & teak wood bed</li>
                    <li><i class="fa-solid fa-bath"></i> Open-air stone courtyard private bath</li>
                </ul>
                <a href="#contact" class="btn-primary room-btn magnetic" data-strength="15">
                    <span>Reserve Stay</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- Room Details Floating 3D Hotspots (Sequenced during 360 scroll rotation) -->
    <!-- Treehouse Hotspots -->
    <div class="room-details-overlay active" id="treehouse-details">
        <div class="detail-hotspot" id="tree-detail-1" style="top: 32%; left: 18%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Canopy Nest</span>
                <h5 class="hotspot-title font-serif">30ft High Canopy</h5>
                <p class="hotspot-text font-sans">Tucked among ancient branches for absolute birdsong privacy.</p>
            </div>
        </div>
        <div class="detail-hotspot" id="tree-detail-2" style="top: 42%; right: 18%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Views</span>
                <h5 class="hotspot-title font-serif">Misty Deck Balcony</h5>
                <p class="hotspot-text font-sans">Walk out to heavy morning fog overlooking the Idukki valleys.</p>
            </div>
        </div>
        <div class="detail-hotspot" id="tree-detail-3" style="top: 60%; left: 32%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Craft</span>
                <h5 class="hotspot-title font-serif">Wild Teak Woodwork</h5>
                <p class="hotspot-text font-sans">Hand-hewn supports and furniture crafted by local craftsmen.</p>
            </div>
        </div>
        <div class="detail-hotspot" id="tree-detail-4" style="top: 25%; left: 45%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Glasswork</span>
                <h5 class="hotspot-title font-serif">Circular Bay Window</h5>
                <p class="hotspot-text font-sans">Floor-to-ceiling panoramic glass to float inside the tree leaves.</p>
            </div>
        </div>
    </div>

    <!-- Mudhouse Hotspots (Earthy details, no balconies) -->
    <div class="room-details-overlay" id="mudhouse-details" style="display: none;">
        <div class="detail-hotspot" id="mud-detail-1" style="top: 30%; left: 22%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Adobe</span>
                <h5 class="hotspot-title font-serif">Cob Mud Walls</h5>
                <p class="hotspot-text font-sans">Earthen clay regulates humidity and temperature naturally.</p>
            </div>
        </div>
        <div class="detail-hotspot" id="mud-detail-2" style="top: 50%; right: 22%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Garden Side</span>
                <h5 class="hotspot-title font-serif">Veranda Courtyard</h5>
                <p class="hotspot-text font-sans">A quiet clay-tiled porch stepping into organic orchards.</p>
            </div>
        </div>
        <div class="detail-hotspot" id="mud-detail-3" style="top: 55%; left: 36%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Cozy</span>
                <h5 class="hotspot-title font-serif">Stone Fire Pit</h5>
                <p class="hotspot-text font-sans">Traditional indoor slate hearth to warm winter nights.</p>
            </div>
        </div>
        <div class="detail-hotspot" id="mud-detail-4" style="top: 35%; right: 30%;">
            <div class="hotspot-dot"></div>
            <div class="hotspot-card">
                <span class="hotspot-tag font-sans">Structure</span>
                <h5 class="hotspot-title font-serif">Terracotta Tiles</h5>
                <p class="hotspot-text font-sans">Premium clay roof canopy crafted by local village potters.</p>
            </div>
        </div>
    </div>

    <!-- Responsive Fallback Structure (Visible only when WebGL fails or on mobile) -->
    <div class="webgl-fallback" id="webgl-fallback-container">
        <div class="container webgl-fallback-container">
            <div class="fallback-header text-center" style="margin-bottom: 50px;">
                <span class="section-label">Stays at Food Forest</span>
                <h3 class="section-title font-serif" style="color: var(--accent-green);">Two Unique Farm Stays</h3>
            </div>

            <!-- Treehouse Card -->
            <div class="fallback-card">
                <div class="fallback-img-container">
                    <img src="assets/images/treehouse_exterior.png" alt="Luxury Treehouse Exterior"
                        class="fallback-img">
                </div>
                <div class="fallback-text">
                    <span class="room-type font-sans" style="color: var(--accent-terracotta);">Canopy Farm Stay</span>
                    <h4 class="room-name font-serif" style="color: var(--accent-green); font-size: 2.8rem;">Luxury Tree House</h4>
                    <p class="room-desc font-sans" style="color: var(--text-dark);">
                        Perched high in the canopy, our luxury treehouse is one of the two unique farm stays at Food Forest. Crafted with natural timber and large circular bay windows, it feels like floating inside nature.
                    </p>
                    <ul class="room-features font-sans" style="color: var(--text-dark);">
                        <li><i class="fa-solid fa-tree"></i> Elevated 30ft above forest floor</li>
                        <li><i class="fa-solid fa-wind"></i> Large circular viewing window</li>
                        <li><i class="fa-solid fa-cloud-sun-rain"></i> Private misty valley balcony</li>
                    </ul>
                    <a href="#contact" class="btn-primary room-btn magnetic" data-strength="15">
                        <span>Reserve Stay</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Mudhouse Card -->
            <div class="fallback-card">
                <div class="fallback-img-container">
                    <img src="assets/images/01 (4).jpeg" alt="Mud House Exterior" class="fallback-img">
                </div>
                <div class="fallback-text">
                    <span class="room-type font-sans" style="color: var(--accent-terracotta);">Earthen Farm Stay</span>
                    <h4 class="room-name font-serif" style="color: var(--accent-green); font-size: 2.8rem;">Traditional Mud House</h4>
                    <p class="room-desc font-sans" style="color: var(--text-dark);">
                        The traditional mud house is the second unique farm stay type at our Food Forest retreat. Built using native soil, grass, and wood, the thick clay walls naturally regulate temperatures.
                    </p>
                    <ul class="room-features font-sans" style="color: var(--text-dark);">
                        <li><i class="fa-solid fa-temperature-arrow-down"></i> Natural thermal clay insulation</li>
                        <li><i class="fa-solid fa-couch"></i> Semi-private veranda courtyard</li>
                        <li><i class="fa-solid fa-fire"></i> Cozy ambient interior fire pit</li>
                        <li><i class="fa-solid fa-bed"></i> Organic hand-loomed bedding & teak wood bed</li>
                        <li><i class="fa-solid fa-bath"></i> Open-air stone courtyard private bath</li>
                    </ul>
                    <a href="#contact" class="btn-primary room-btn magnetic" data-strength="15">
                        <span>Reserve Stay</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>