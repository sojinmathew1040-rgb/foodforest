<!-- Luxury Reservation & Concierge Modal -->
<div id="booking-modal" class="booking-modal-overlay" aria-hidden="true" role="dialog" data-lenis-prevent>
    <div class="booking-modal-backdrop"></div>
    <div class="booking-modal-container" data-lenis-prevent>
        <!-- Close Button -->
        <button class="booking-modal-close" id="booking-modal-close" aria-label="Close Reservation Modal">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="booking-modal-content">
            <!-- Modal Header -->
            <div class="booking-modal-header">
                <span class="booking-subtitle font-sans">RESERVE YOUR SANCTUARY</span>
                <h3 class="booking-title font-serif">Bespoke Forest Stay</h3>
                <p class="booking-tagline font-sans">
                    Experience unhurried luxury at Kanthalloor. Complete your details below for instant reservation concierge assistance.
                </p>
                <div class="booking-trust-pills">
                    <span><i class="fa-solid fa-utensils"></i> All Farm Meals Included</span>
                    <span><i class="fa-solid fa-seedling"></i> 100% Organic Estate</span>
                    <span><i class="fa-solid fa-shield-check"></i> Direct Best Rate Guarantee</span>
                </div>
            </div>

            <!-- Booking Form -->
            <form id="luxury-booking-form" class="booking-form" onsubmit="event.preventDefault();">
                <!-- Step 1: Stay Details -->
                <div class="booking-section-group">
                    <h4 class="group-title font-serif">1. Select Your Stay & Dates</h4>
                    <div class="booking-grid-2">
                    <!-- Villa Selection -->
                    <div class="form-field" style="margin-bottom: 16px;">
                        <label for="modal-villa" class="form-label font-sans">Sanctuary Villa or Cottage</label>
                        <div class="select-wrapper">
                            <select id="modal-villa" class="form-input font-sans" required>
                                <?php
                                require_once __DIR__ . '/../admin/includes/db.php';
                                ensure_rooms_pricing_columns(get_db());
                                $modal_villas = get_all_rooms(true);
                                if (!empty($modal_villas)):
                                    foreach ($modal_villas as $mv):
                                        $struct = $mv['structure_type'] ?? 'single_hut';
                                        $struct_label = ($struct === 'duplex_hut') ? 'Duplex Cottage' : 'Single Hut';
                                        $cat_icon = ($mv['stay_type'] === 'mudhouse') ? '🌿 Mudhouse' : '🌲 Treehouse';
                                ?>
                                    <option value="<?php echo htmlspecialchars($mv['slug']); ?>" 
                                            data-price="<?php echo htmlspecialchars($mv['rate_per_night']); ?>" 
                                            data-name="<?php echo htmlspecialchars($mv['title']); ?>"
                                            data-base-guests="<?php echo (int)($mv['base_guests'] ?? 2); ?>"
                                            data-max-guests="<?php echo (int)($mv['max_guests'] ?? 4); ?>"
                                            data-extra-rate="<?php echo htmlspecialchars($mv['extra_guest_rate'] ?? 1500); ?>"
                                            data-extra-child-rate="<?php echo htmlspecialchars($mv['extra_child_rate'] ?? 800); ?>"
                                            data-structure-type="<?php echo htmlspecialchars($struct); ?>"
                                            data-stay-type="<?php echo htmlspecialchars($mv['stay_type'] ?? 'treehouse'); ?>">
                                        <?php echo "{$cat_icon} [{$struct_label}]: " . htmlspecialchars($mv['title']); ?> (₹<?php echo number_format($mv['rate_per_night'], 0, '.', ','); ?>/nt • Base <?php echo (int)($mv['base_guests'] ?? 2); ?> Guests)
                                    </option>
                                <?php 
                                    endforeach;
                                else: 
                                ?>
                                    <option value="treehouse" data-price="14500" data-name="Luxury Canopy Treehouse" data-base-guests="2" data-max-guests="3" data-extra-rate="2000" data-extra-child-rate="1000" data-structure-type="single_hut" data-stay-type="treehouse">
                                        🌲 Treehouse [Single Hut]: Luxury Canopy Treehouse (₹14,500/nt • Base 2 Guests)
                                    </option>
                                    <option value="mudhouse" data-price="11500" data-name="Traditional Earthen Mudhouse" data-base-guests="2" data-max-guests="4" data-extra-rate="1500" data-extra-child-rate="800" data-structure-type="single_hut" data-stay-type="mudhouse">
                                        🌿 Mudhouse [Single Hut]: Traditional Earthen Mudhouse (₹11,500/nt • Base 2 Guests)
                                    </option>
                                    <option value="treehouse-double" data-price="24000" data-name="The Grand Timber Loft" data-base-guests="4" data-max-guests="6" data-extra-rate="2000" data-extra-child-rate="1000" data-structure-type="duplex_hut" data-stay-type="treehouse">
                                        🌲 Treehouse [Duplex Cottage]: The Grand Timber Loft (₹24,000/nt • Base 4 Guests)
                                    </option>
                                    <option value="mudhouse-duplex" data-price="21000" data-name="The Earthen Courtyard Cottage" data-base-guests="4" data-max-guests="8" data-extra-rate="1500" data-extra-child-rate="800" data-structure-type="duplex_hut" data-stay-type="mudhouse">
                                        🌿 Mudhouse [Duplex Cottage]: The Earthen Courtyard Cottage (₹21,000/nt • Base 4 Guests)
                                    </option>
                                <?php endif; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down select-arrow"></i>
                        </div>
                    </div>

                    <!-- Luxury Dual Steppers: Adults & Children -->
                    <div class="form-field guest-steppers-container" style="margin-bottom: 16px;">
                        <div class="steppers-header-row">
                            <label class="form-label font-sans" style="margin-bottom: 0;">Guests & Occupancy</label>
                            <span id="room-occupancy-note" class="font-sans occupancy-note">
                                <i class="fa-solid fa-circle-info"></i> Base: 2 Included • Max Capacity: 4
                            </span>
                        </div>
                        <div class="guest-steppers-grid">
                            <!-- Adults Stepper -->
                            <div class="guest-stepper-box">
                                <div class="stepper-label-group">
                                    <span class="stepper-title font-sans"><i class="fa-solid fa-user"></i> Adults</span>
                                    <span class="stepper-sub font-sans">Ages 12+ yrs</span>
                                </div>
                                <div class="stepper-controls">
                                    <button type="button" class="btn-stepper btn-stepper-minus" data-target="modal-adults" aria-label="Decrease Adults">−</button>
                                    <input type="number" id="modal-adults" name="adults_count" value="2" min="1" max="10" readonly class="stepper-val font-sans">
                                    <button type="button" class="btn-stepper btn-stepper-plus" data-target="modal-adults" aria-label="Increase Adults">+</button>
                                </div>
                            </div>

                            <!-- Children Stepper -->
                            <div class="guest-stepper-box">
                                <div class="stepper-label-group">
                                    <span class="stepper-title font-sans"><i class="fa-solid fa-child"></i> Children</span>
                                    <span class="stepper-sub font-sans">Ages 5–11 yrs <span class="infant-tag">(Under 5 Free)</span></span>
                                </div>
                                <div class="stepper-controls">
                                    <button type="button" class="btn-stepper btn-stepper-minus" data-target="modal-kids" aria-label="Decrease Children">−</button>
                                    <input type="number" id="modal-kids" name="kids_count" value="0" min="0" max="8" readonly class="stepper-val font-sans">
                                    <button type="button" class="btn-stepper btn-stepper-plus" data-target="modal-kids" aria-label="Increase Children">+</button>
                                </div>
                            </div>
                        </div>
                        <!-- Hidden input for backwards compatibility -->
                        <input type="hidden" id="modal-guests" value="2">
                    </div>
                </div>

                <div class="booking-grid-2">
                        <!-- Check-in -->
                        <div class="form-field">
                            <label for="modal-checkin" class="form-label font-sans">Check-In Date</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-regular fa-calendar input-icon"></i>
                                <input type="date" id="modal-checkin" class="form-input font-sans" required>
                            </div>
                        </div>

                        <!-- Check-out -->
                        <div class="form-field">
                            <label for="modal-checkout" class="form-label font-sans">Check-Out Date</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-regular fa-calendar input-icon"></i>
                                <input type="date" id="modal-checkout" class="form-input font-sans" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Bespoke Curated Add-ons -->
                <div class="booking-section-group">
                    <h4 class="group-title font-serif">2. Signature Add-On Experiences <span class="optional-tag font-sans">(Optional)</span></h4>
                    <div class="addons-grid">
                        <label class="addon-card">
                            <input type="checkbox" class="addon-checkbox" value="dinner" data-price="3000">
                            <div class="addon-card-inner">
                                <div class="addon-check-icon"><i class="fa-solid fa-check"></i></div>
                                <div class="addon-details">
                                    <span class="addon-name font-serif">Candlelight Orchard Dinner</span>
                                    <span class="addon-desc font-sans">Private 4-course dinner set under blooming apple trees</span>
                                </div>
                                <span class="addon-price font-sans">+₹3,000</span>
                            </div>
                        </label>

                        <label class="addon-card">
                            <input type="checkbox" class="addon-checkbox" value="pottery" data-price="1500">
                            <div class="addon-card-inner">
                                <div class="addon-check-icon"><i class="fa-solid fa-check"></i></div>
                                <div class="addon-details">
                                    <span class="addon-name font-serif">Mud Pottery & Clay Workshop</span>
                                    <span class="addon-desc font-sans">Hands-on natural cob crafting with local master artisans</span>
                                </div>
                                <span class="addon-price font-sans">+₹1,500</span>
                            </div>
                        </label>

                        <label class="addon-card">
                            <input type="checkbox" class="addon-checkbox" value="trek" data-price="2000">
                            <div class="addon-card-inner">
                                <div class="addon-check-icon"><i class="fa-solid fa-check"></i></div>
                                <div class="addon-details">
                                    <span class="addon-name font-serif">Sunrise High-Peak Valley Trail</span>
                                    <span class="addon-desc font-sans">Guided wilderness trek with hilltop forest breakfast</span>
                                </div>
                                <span class="addon-price font-sans">+₹2,000</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 3: Guest Information -->
                <div class="booking-section-group">
                    <h4 class="group-title font-serif">3. Guest Details</h4>
                    <div class="booking-grid-2">
                        <div class="form-field">
                            <label for="modal-name" class="form-label font-sans">Full Name *</label>
                            <input type="text" id="modal-name" class="form-input font-sans" placeholder="e.g. Dr. Siddharth Menon" required>
                        </div>
                        <div class="form-field">
                            <label for="modal-phone" class="form-label font-sans">WhatsApp / Mobile *</label>
                            <input type="tel" id="modal-phone" class="form-input font-sans" placeholder="+91 98765 43210" required>
                        </div>
                    </div>
                    <div class="booking-grid-2">
                        <div class="form-field">
                            <label for="modal-email" class="form-label font-sans">Email Address</label>
                            <input type="email" id="modal-email" class="form-input font-sans" placeholder="siddharth@example.com">
                        </div>
                        <div class="form-field">
                            <label for="modal-notes" class="form-label font-sans">Special Preferences</label>
                            <input type="text" id="modal-notes" class="form-input font-sans" placeholder="Dietary needs, honeymoon arrangement...">
                        </div>
                    </div>
                </div>

                <!-- Price Summary Box -->
                <div class="booking-summary-box">
                    <div class="summary-line">
                        <span class="font-sans">Duration:</span>
                        <span id="summary-nights" class="font-sans font-weight-600">1 Night</span>
                    </div>
                    <div class="summary-line">
                        <span class="font-sans">Base Villa Tariff:</span>
                        <span id="summary-villa-rate" class="font-sans">₹14,500</span>
                    </div>
                    <div class="summary-line" id="summary-extra-adults-line" style="display: none; color: #2ecc71;">
                        <span class="font-sans"><i class="fa-solid fa-user-plus" style="font-size: 11px;"></i> <span id="summary-extra-adults-label">Extra Adults (12+ yrs):</span></span>
                        <span id="summary-extra-adults-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>
                    <div class="summary-line" id="summary-extra-kids-line" style="display: none; color: #f59e0b;">
                        <span class="font-sans"><i class="fa-solid fa-child" style="font-size: 11px;"></i> <span id="summary-extra-kids-label">Extra Children (5-11 yrs):</span></span>
                        <span id="summary-extra-kids-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>
                    <!-- Legacy fallback -->
                    <div class="summary-line" id="summary-extra-guests-line" style="display: none; color: #2ecc71;">
                        <span class="font-sans"><i class="fa-solid fa-user-plus" style="font-size: 11px;"></i> <span id="summary-extra-guests-label">Extra Guests Charge:</span></span>
                        <span id="summary-extra-guests-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>
                    <div class="summary-line" id="summary-addons-line" style="display: none;">
                        <span class="font-sans">Selected Experiences:</span>
                        <span id="summary-addons-rate" class="font-sans">₹0</span>
                    </div>
                    <div class="summary-line total-line">
                        <span class="font-serif">Estimated Total (Taxes & Farm Meals Incl.):</span>
                        <span id="summary-total" class="font-serif price-highlight">₹14,500</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="booking-action-buttons">
                    <button type="button" id="btn-submit-whatsapp" class="btn-primary btn-whatsapp-luxury">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>Instant WhatsApp Concierge Confirmation</span>
                    </button>
                    <button type="submit" id="btn-submit-email" class="btn-secondary btn-enquiry-luxury">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Send Official Reservation Request</span>
                    </button>
                </div>
                
                <p class="booking-guarantee-note font-sans">
                    <i class="fa-solid fa-lock"></i> No payment required right now. Our estate concierge will confirm your dates and reservation within 30 minutes.
                </p>
            </form>
        </div>
    </div>
</div>
