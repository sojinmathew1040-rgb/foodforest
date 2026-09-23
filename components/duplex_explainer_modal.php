<?php
// =========================================================================
// Food Forest Sanctuary — Duplex Chalet Architecture & Options Modal
// Interactive Visual Explainer with Architectural Diagram & Tier Switcher
// =========================================================================
require_once __DIR__ . '/../admin/includes/db.php';
$duplex_room = null;
$rooms_d = get_all_rooms(false);
foreach ($rooms_d as $rd) {
    if (($rd['structure_type'] ?? '') === 'duplex_hut') {
        $duplex_room = $rd;
        break;
    }
}
$duplex_full_rate = $duplex_room ? (float)$duplex_room['rate_per_night'] : 24000;
$duplex_single_rate = $duplex_room ? (float)($duplex_room['single_room_rate'] ?? 14500) : 14500;
?>
<div id="duplex-explainer-modal" class="duplex-modal-overlay font-sans" aria-hidden="true" role="dialog" style="display: none;">
    <div class="duplex-modal-backdrop" onclick="closeDuplexExplainer();"></div>
    <div class="duplex-modal-dialog">
        
        <!-- Modal Header -->
        <div class="duplex-modal-header">
            <div class="duplex-header-badge font-sans">
                <i class="fa-solid fa-layer-group"></i> SANCTUARY ARCHITECTURE GUIDE
            </div>
            <button type="button" class="duplex-modal-close" onclick="closeDuplexExplainer();" aria-label="Close Explainer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="duplex-modal-body">
            
            <div class="duplex-intro-block text-center">
                <span class="duplex-concept-tag font-sans">TWO ADJOINING SUITES • ONE CONTINUOUS ROOF</span>
                <h2 class="duplex-modal-title font-serif">What is a Sanctuary Duplex Chalet?</h2>
                <p class="duplex-modal-subtitle font-sans">
                    A Duplex Chalet is an expansive high-range timber & stone residence designed with <strong>two self-contained private suites</strong> under one roof, separated by a sound-insulated acoustic partition wall.
                </p>
            </div>

            <!-- Visual Architectural Cutaway Diagram (Interactive SVG & CSS Floorplan) -->
            <div class="duplex-architecture-card">
                <div class="arch-roof-canopy font-serif">
                    <i class="fa-solid fa-mountain"></i> Continuous Cedarwood Sloping Alpine Roof
                </div>

                <div class="arch-wings-container">
                    
                    <!-- Wing A: Left Master Suite -->
                    <div class="arch-wing wing-left" id="arch-wing-a">
                        <div class="arch-wing-tag font-sans">SUITE WING 01</div>
                        <div class="arch-wing-title font-serif">Master Suite A</div>
                        <div class="arch-wing-features font-sans">
                            <span><i class="fa-solid fa-door-closed"></i> Independent Private Entry</span>
                            <span><i class="fa-solid fa-bed"></i> Handcrafted King Bed</span>
                            <span><i class="fa-solid fa-shower"></i> Ensuite Rain Shower & Bath</span>
                            <span><i class="fa-solid fa-water"></i> Private Valley View Deck</span>
                        </div>
                        <div class="arch-wing-cap font-sans">
                            <i class="fa-solid fa-users"></i> Base: 2 Guests • Max: 4
                        </div>
                    </div>

                    <!-- Center Acoustic Partition Barrier -->
                    <div class="arch-partition-barrier">
                        <div class="barrier-line"></div>
                        <div class="barrier-badge font-sans">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Acoustic Dividing Wall</span>
                        </div>
                        <div class="barrier-sub font-sans">Full Sound Isolation</div>
                        <div class="barrier-line"></div>
                    </div>

                    <!-- Wing B: Right Master Suite -->
                    <div class="arch-wing wing-right" id="arch-wing-b">
                        <div class="arch-wing-tag font-sans">SUITE WING 02</div>
                        <div class="arch-wing-title font-serif">Master Suite B</div>
                        <div class="arch-wing-features font-sans">
                            <span><i class="fa-solid fa-door-closed"></i> Independent Private Entry</span>
                            <span><i class="fa-solid fa-bed"></i> Handcrafted King Bed</span>
                            <span><i class="fa-solid fa-shower"></i> Ensuite Rain Shower & Bath</span>
                            <span><i class="fa-solid fa-water"></i> Private Valley View Deck</span>
                        </div>
                        <div class="arch-wing-cap font-sans">
                            <i class="fa-solid fa-users"></i> Base: 2 Guests • Max: 4
                        </div>
                    </div>

                </div>

                <!-- Ground Foundation Note -->
                <div class="arch-foundation-note font-sans">
                    <i class="fa-solid fa-circle-info"></i> Each wing has completely private access, private keys, private ensuite bathrooms, and separate valley balconies.
                </div>
            </div>

            <!-- Booking Choice Selector Cards -->
            <div class="duplex-booking-options-section">
                <h4 class="options-heading font-serif">Select Your Preferred Duplex Configuration:</h4>
                
                <div class="duplex-options-grid">
                    
                    <!-- Option 1: Single Room in Duplex -->
                    <div class="duplex-choice-card" id="choice-card-single" onclick="selectDuplexOption('single_room');">
                        <div class="choice-header">
                            <div class="choice-radio">
                                <input type="radio" name="duplex_modal_choice" value="single_room" id="radio-choice-single">
                                <label for="radio-choice-single"></label>
                            </div>
                            <div>
                                <span class="choice-badge font-sans">SINGLE SUITE OPTION</span>
                                <h3 class="choice-title font-serif">Single Room in Duplex</h3>
                            </div>
                        </div>

                        <div class="choice-price-box">
                            <div class="choice-rate font-serif" id="duplex-modal-rate-single">₹<?php echo number_format($duplex_single_rate, 0, '.', ','); ?></div>
                            <span class="choice-rate-period font-sans">/ night (Taxes & Farm Meals Included)</span>
                        </div>

                        <ul class="choice-perks-list font-sans">
                            <li><i class="fa-solid fa-check"></i> Reserves <strong>1 Private Master Suite Wing</strong></li>
                            <li><i class="fa-solid fa-check"></i> Base <strong>2 Guests</strong> (Can add up to 2 extra guests)</li>
                            <li><i class="fa-solid fa-check"></i> Private ensuite bath & private valley balcony</li>
                            <li><i class="fa-solid fa-check"></i> All 4 Organic Farm Meals Included</li>
                            <li class="perk-note"><i class="fa-solid fa-circle-question"></i> The adjoining second suite remains occupied by fellow quiet sanctuary guests.</li>
                        </ul>

                        <button type="button" class="btn-select-choice font-sans" onclick="applyDuplexChoice('single_room');">
                            <span>Select Single Room (₹<?php echo number_format($duplex_single_rate, 0, '.', ','); ?>/nt)</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

                    <!-- Option 2: Full Duplex House (Both Suites) -->
                    <div class="duplex-choice-card featured" id="choice-card-full" onclick="selectDuplexOption('full');">
                        <div class="featured-ribbon font-sans"><i class="fa-solid fa-crown"></i> COMPLETE PRIVACY</div>
                        
                        <div class="choice-header">
                            <div class="choice-radio">
                                <input type="radio" name="duplex_modal_choice" value="full" id="radio-choice-full" checked>
                                <label for="radio-choice-full"></label>
                            </div>
                            <div>
                                <span class="choice-badge badge-gold font-sans">FULL RESIDENCE OPTION</span>
                                <h3 class="choice-title font-serif">Entire Duplex Residence</h3>
                            </div>
                        </div>

                        <div class="choice-price-box">
                            <div class="choice-rate font-serif" id="duplex-modal-rate-full">₹<?php echo number_format($duplex_full_rate, 0, '.', ','); ?></div>
                            <span class="choice-rate-period font-sans">/ night (Taxes & Farm Meals Included)</span>
                        </div>

                        <ul class="choice-perks-list font-sans">
                            <li><i class="fa-solid fa-check"></i> Reserves <strong>Both Suite Wings (Entire 2-Room Chalet)</strong></li>
                            <li><i class="fa-solid fa-check"></i> Base <strong>4 Guests Included</strong> (Accommodates up to 8 guests)</li>
                            <li><i class="fa-solid fa-check"></i> 100% Exclusive House Privacy (No other guests in building)</li>
                            <li><i class="fa-solid fa-check"></i> 2 King Bedrooms, 2 Rain Showers, Dual Valley Decks</li>
                            <li><i class="fa-solid fa-check"></i> All 4 Organic Farm Meals Included for All Guests</li>
                        </ul>

                        <button type="button" class="btn-select-choice btn-gold font-sans" onclick="applyDuplexChoice('full');">
                            <span>Select Full Duplex (₹<?php echo number_format($duplex_full_rate, 0, '.', ','); ?>/nt)</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

                </div>
            </div>

        </div>

        <div class="duplex-modal-footer">
            <button type="button" class="btn-duplex-cancel font-sans" onclick="closeDuplexExplainer();">
                Close Explainer
            </button>
        </div>

    </div>
</div>
