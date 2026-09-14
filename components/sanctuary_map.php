<!-- Sanctuary Estate & Environmental Harmony Section -->
<section id="sanctuary" class="sanctuary-section section-padding">
    <div class="container">
        
        <!-- Header -->
        <div class="sanctuary-header text-center">
            <span class="section-label">The Living Landscape</span>
            <h3 class="section-title font-serif split-text" style="color: var(--accent-green);">An Untamed Sanctuary</h3>
            <p class="sanctuary-desc font-sans" style="color: var(--text-light); max-width: 750px; margin: 15px auto 0;">
                Nestled at an elevation of 1,600 meters in the Western Ghats, Food Forest spans pristine wilderness, organic orchards, and crystal natural mountain brooks.
            </p>
        </div>

        <!-- Sanctuary Environmental Microclimate HUD Bar -->
        <div class="sanctuary-hud-bar scroll-reveal">
            <div class="hud-item">
                <span class="hud-pulse-dot"></span>
                <div class="hud-meta">
                    <span class="hud-label">ELEVATION</span>
                    <span class="hud-value font-serif">1,600<span class="hud-unit">M</span> <span class="hud-sub">MSL</span></span>
                </div>
            </div>
            <div class="hud-divider"></div>
            <div class="hud-item">
                <span class="hud-pulse-dot gold"></span>
                <div class="hud-meta">
                    <span class="hud-label">AIR PURITY</span>
                    <span class="hud-value font-serif">AQI 11 <span class="hud-unit">· PRISTINE</span></span>
                </div>
            </div>
            <div class="hud-divider"></div>
            <div class="hud-item">
                <span class="hud-pulse-dot"></span>
                <div class="hud-meta">
                    <span class="hud-label">ACOUSTIC PURITY</span>
                    <span class="hud-value font-serif">0<span class="hud-unit">dB</span> <span class="hud-unit">· WILD SILENCE</span></span>
                </div>
            </div>
            <div class="hud-divider"></div>
            <div class="hud-item">
                <span class="hud-pulse-dot gold"></span>
                <div class="hud-meta">
                    <span class="hud-label">NIGHT SKY</span>
                    <span class="hud-value font-serif">Class 1 <span class="hud-unit">· DARK SKY</span></span>
                </div>
            </div>
            <div class="hud-divider"></div>
            <div class="hud-item">
                <span class="hud-pulse-dot"></span>
                <div class="hud-meta">
                    <span class="hud-label">AGRICULTURE</span>
                    <span class="hud-value font-serif">100% <span class="hud-unit">· PERMACULTURE</span></span>
                </div>
            </div>
        </div>

        <!-- Interactive Estate Map & Exploration Console -->
        <div class="sanctuary-explorer-wrapper scroll-reveal">
            <!-- Filter Tabs -->
            <div class="sanctuary-map-controls">
                <div class="map-category-tabs" role="tablist">
                    <button class="map-filter-btn active" data-filter="all">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                        All Sanctuary Points
                    </button>
                    <button class="map-filter-btn" data-filter="stays">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Luxury Stays
                    </button>
                    <button class="map-filter-btn" data-filter="nature">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-9"/><path d="M7 13a5 5 0 0 1 10 0v1a5 5 0 0 1-10 0Z"/><path d="M12 13V8"/></svg>
                        Orchards & Trails
                    </button>
                    <button class="map-filter-btn" data-filter="features">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        Waters & Stargazing
                    </button>
                </div>
                <div class="map-hint font-sans">
                    <span class="hint-pulse"></span> Tap or hover hotspot pins to explore the estate
                </div>
            </div>

            <!-- Two-Column Interactive Console -->
            <div class="sanctuary-console-grid">
                
                <!-- Left: Topographic Map Canvas -->
                <div class="sanctuary-map-board" id="sanctuary-map-board">
                    <!-- Topographic Background SVG -->
                    <svg class="topo-svg-canvas" viewBox="0 0 800 520" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <radialGradient id="topo-forest-glow" cx="50%" cy="50%" r="65%">
                                <stop offset="0%" stop-color="#1c3826" stop-opacity="0.95" />
                                <stop offset="60%" stop-color="#14281c" stop-opacity="0.98" />
                                <stop offset="100%" stop-color="#0c1912" stop-opacity="1" />
                            </radialGradient>
                            <linearGradient id="stream-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#56c2c9" stop-opacity="0.8" />
                                <stop offset="50%" stop-color="#38a3a5" stop-opacity="0.9" />
                                <stop offset="100%" stop-color="#22577a" stop-opacity="0.8" />
                            </linearGradient>
                            <filter id="map-glow" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur stdDeviation="3" result="blur" />
                                <feComposite in="SourceGraphic" in2="blur" operator="over" />
                            </filter>
                        </defs>

                        <!-- Base Map Terrain -->
                        <rect width="800" height="520" fill="url(#topo-forest-glow)" />

                        <!-- Topographic Contour Lines -->
                        <g class="topo-contours" stroke="rgba(197, 160, 89, 0.16)" fill="none" stroke-width="1.2">
                            <!-- 1,560m contour -->
                            <path d="M -20,440 Q 150,480 320,430 T 650,470 T 820,420" />
                            <path d="M -20,380 Q 180,410 340,360 T 670,390 T 820,350" stroke="rgba(197, 160, 89, 0.22)" />
                            <!-- 1,580m contour -->
                            <path d="M -20,320 Q 160,350 350,300 T 630,320 T 820,290" />
                            <path d="M -20,260 Q 200,290 400,240 T 650,260 T 820,220" stroke="rgba(197, 160, 89, 0.28)" />
                            <!-- 1,600m contour (Master index) -->
                            <path d="M -20,200 Q 180,220 420,170 T 680,190 T 820,150" stroke="rgba(197, 160, 89, 0.4)" stroke-width="1.8" />
                            <!-- 1,620m contour -->
                            <path d="M -20,140 Q 220,170 450,110 T 700,130 T 820,90" />
                            <path d="M -20,80 Q 240,110 470,60 T 720,80 T 820,30" stroke="rgba(197, 160, 89, 0.22)" />
                            <!-- 1,640m peak contour -->
                            <path d="M 280,-20 Q 420,70 560,-20" stroke="rgba(197, 160, 89, 0.35)" />
                            <path d="M 330,-20 Q 430,45 520,-20" stroke="rgba(197, 160, 89, 0.45)" stroke-width="1.5" />
                        </g>

                        <!-- Natural Mountain Brook / River Stream (meandering) -->
                        <path class="topo-stream" d="M 120,-20 C 140,80 190,140 240,210 C 290,280 340,310 410,380 C 470,440 520,480 580,540" 
                              stroke="url(#stream-gradient)" stroke-width="4.5" fill="none" stroke-linecap="round" filter="url(#map-glow)" />
                        <!-- Tributary Stream -->
                        <path class="topo-stream-sub" d="M 390,260 C 430,280 470,320 480,350" 
                              stroke="url(#stream-gradient)" stroke-width="2.2" fill="none" stroke-linecap="round" opacity="0.75" />

                        <!-- Cobblestone Nature Walking Trails (Dashed paths) -->
                        <path class="topo-trail" d="M 80,480 Q 180,390 220,310 T 360,200 T 540,150 T 670,110" 
                              stroke="rgba(244, 237, 222, 0.35)" stroke-width="2" stroke-dasharray="5,6" fill="none" />
                        <path class="topo-trail" d="M 220,310 Q 300,340 430,370 T 640,390" 
                              stroke="rgba(244, 237, 222, 0.3)" stroke-width="1.8" stroke-dasharray="4,5" fill="none" />
                        <path class="topo-trail" d="M 360,200 Q 320,110 390,50" 
                              stroke="rgba(244, 237, 222, 0.3)" stroke-width="1.8" stroke-dasharray="4,5" fill="none" />

                        <!-- Decorative Shola Tree Clusters -->
                        <g class="topo-trees" fill="rgba(64, 115, 84, 0.35)">
                            <circle cx="110" cy="180" r="14" />
                            <circle cx="130" cy="190" r="11" />
                            <circle cx="95" cy="195" r="9" />
                            
                            <circle cx="670" cy="240" r="16" />
                            <circle cx="690" cy="255" r="12" />
                            <circle cx="650" cy="260" r="10" />

                            <circle cx="210" cy="420" r="15" />
                            <circle cx="230" cy="435" r="11" />

                            <circle cx="610" cy="90" r="14" />
                            <circle cx="630" cy="105" r="10" />
                        </g>

                        <!-- Contour Elevation Labels -->
                        <text x="685" y="185" fill="rgba(197, 160, 89, 0.55)" font-size="10" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,600M MSL</text>
                        <text x="685" y="125" fill="rgba(197, 160, 89, 0.4)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,620M MSL</text>
                        <text x="685" y="385" fill="rgba(197, 160, 89, 0.4)" font-size="9" font-family="'Cinzel', Georgia, serif" letter-spacing="1">1,580M MSL</text>
                    </svg>

                    <!-- Luxury Compass Rose -->
                    <div class="map-compass font-serif">
                        <span class="compass-n">N</span>
                        <div class="compass-pointer"></div>
                        <span class="compass-coords">KANTHALLOOR · 1,600M</span>
                    </div>

                    <!-- Water Brook Tag -->
                    <div class="map-stream-badge font-sans">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12c3-3 6-3 9 0s6 3 9 0"/><path d="M2 18c3-3 6-3 9 0s6 3 9 0"/></svg>
                        Perennial Brook
                    </div>

                    <!-- Interactive Hotspot Pins on the Map -->
                    <div class="sanctuary-pins-container">
                        
                        <!-- PIN 01: Orchard Trails -->
                        <div class="sanctuary-pin active" data-zone="1" data-category="nature" style="top: 56%; left: 19%;">
                            <div class="pin-beacon"></div>
                            <div class="pin-marker">
                                <span class="pin-index">01</span>
                            </div>
                            <div class="pin-tooltip">
                                <span class="pin-title">Heirloom Orchards</span>
                                <span class="pin-alt">1,580M</span>
                            </div>
                        </div>

                        <!-- PIN 02: High Canopy Treehouses -->
                        <div class="sanctuary-pin" data-zone="2" data-category="stays" style="top: 22%; left: 74%;">
                            <div class="pin-beacon"></div>
                            <div class="pin-marker">
                                <span class="pin-index">02</span>
                            </div>
                            <div class="pin-tooltip">
                                <span class="pin-title">High Canopy Ridge</span>
                                <span class="pin-alt">1,640M</span>
                            </div>
                        </div>

                        <!-- PIN 03: Earthen Mudhouse Sanctuary -->
                        <div class="sanctuary-pin" data-zone="3" data-category="stays" style="top: 68%; left: 36%;">
                            <div class="pin-beacon"></div>
                            <div class="pin-marker">
                                <span class="pin-index">03</span>
                            </div>
                            <div class="pin-tooltip">
                                <span class="pin-title">Earthen Mud Enclave</span>
                                <span class="pin-alt">1,600M</span>
                            </div>
                        </div>

                        <!-- PIN 04: Crystal Mountain Brook -->
                        <div class="sanctuary-pin" data-zone="4" data-category="features" style="top: 48%; left: 46%;">
                            <div class="pin-beacon"></div>
                            <div class="pin-marker">
                                <span class="pin-index">04</span>
                            </div>
                            <div class="pin-tooltip">
                                <span class="pin-title">Crystal Brook & Pool</span>
                                <span class="pin-alt">1,560M</span>
                            </div>
                        </div>

                        <!-- PIN 05: Dark Sky Stargazing Horizon -->
                        <div class="sanctuary-pin" data-zone="5" data-category="features" style="top: 15%; left: 44%;">
                            <div class="pin-beacon"></div>
                            <div class="pin-marker">
                                <span class="pin-index">05</span>
                            </div>
                            <div class="pin-tooltip">
                                <span class="pin-title">Dark Sky Horizon</span>
                                <span class="pin-alt">1,660M</span>
                            </div>
                        </div>

                        <!-- PIN 06: Forest Hearth & Farm Dining -->
                        <div class="sanctuary-pin" data-zone="6" data-category="nature" style="top: 76%; left: 66%;">
                            <div class="pin-beacon"></div>
                            <div class="pin-marker">
                                <span class="pin-index">06</span>
                            </div>
                            <div class="pin-tooltip">
                                <span class="pin-title">Forest Hearth Kitchen</span>
                                <span class="pin-alt">1,595M</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Right: Dynamic Interactive Zone Inspector Card -->
                <div class="sanctuary-inspector-card" id="sanctuary-inspector">
                    
                    <!-- Zone Card Header -->
                    <div class="inspector-header">
                        <div class="inspector-zone-pill">
                            <span class="inspector-zone-num" id="ins-zone-num">ZONE 01</span>
                            <span class="inspector-zone-type" id="ins-zone-type">ORCHARD & TRAILS</span>
                        </div>
                        <div class="inspector-nav-arrows">
                            <button class="ins-arrow-btn" id="ins-prev-btn" aria-label="Previous Zone">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <span class="ins-fraction font-serif"><span id="ins-curr-idx">1</span> / 6</span>
                            <button class="ins-arrow-btn" id="ins-next-btn" aria-label="Next Zone">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Zone Image Showcase with Zoom -->
                    <div class="inspector-media-wrap">
                        <img src="assets/images/01 (24).jpeg" alt="The Heirloom Orchard Trails" id="ins-img" class="inspector-img">
                        <div class="inspector-img-overlay"></div>
                        <div class="inspector-floating-tags">
                            <span class="ins-tag" id="ins-tag-alt">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 3 4 8 5-5 5 15H2L8 3z"/></svg>
                                1,580M MSL
                            </span>
                            <span class="ins-tag" id="ins-tag-temp">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                                18°C Alpine Breeze
                            </span>
                        </div>
                    </div>

                    <!-- Zone Title & Details -->
                    <div class="inspector-body">
                        <h4 class="inspector-title font-serif" id="ins-title">The Heirloom Orchard Trails</h4>
                        <p class="inspector-desc font-sans" id="ins-desc">
                            Apple, plum, peach, and wild berry groves where guests can wander and harvest directly from low-hanging branches. Meandering cobblestone trails weave through terraced organic slopes nurtured without synthetic fertilizers.
                        </p>

                        <!-- Sensory Notes Bar -->
                        <div class="inspector-sensory-grid">
                            <div class="sensory-item">
                                <span class="sensory-label">FOREST AROMA</span>
                                <span class="sensory-val" id="ins-aroma">Ripening plums, sweet clover & mountain pine</span>
                            </div>
                            <div class="sensory-item">
                                <span class="sensory-label">ACOUSTIC PROFILE</span>
                                <span class="sensory-val" id="ins-sound">Rustling leaves, Himalayan bulbul calls</span>
                            </div>
                        </div>

                        <!-- Action CTA Bar -->
                        <div class="inspector-actions">
                            <a href="#rooms" class="btn-luxury-solid btn-sm" id="ins-cta-btn">
                                <span>Explore Dwellings</span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </a>
                            <button class="btn-luxury-outline btn-sm" id="ins-sound-toggle-btn">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                                <span>Sanctuary Audio</span>
                            </button>
                        </div>
                    </div>

                </div>

            </div>

        </div>



    </div>
</section>
