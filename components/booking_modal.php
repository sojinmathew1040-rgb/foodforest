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
                        <div class="form-field">
                            <label for="modal-villa" class="form-label font-sans">Sanctuary Villa</label>
                            <div class="select-wrapper">
                                <select id="modal-villa" class="form-input font-sans" required>
                                    <?php
                                    require_once __DIR__ . '/../admin/includes/db.php';
                                    $modal_villas = get_all_rooms(true);
                                    if (!empty($modal_villas)):
                                        foreach ($modal_villas as $mv):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($mv['slug']); ?>" data-price="<?php echo htmlspecialchars($mv['rate_per_night']); ?>" data-name="<?php echo htmlspecialchars($mv['title']); ?>">
                                            <?php echo htmlspecialchars($mv['title']); ?> (₹<?php echo number_format($mv['rate_per_night'], 0, '.', ','); ?> / night)
                                        </option>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <option value="treehouse" data-price="14500" data-name="Luxury Canopy Treehouse">
                                            Luxury Canopy Treehouse (₹14,500 / night)
                                        </option>
                                        <option value="mudhouse" data-price="11500" data-name="Traditional Earthen Mudhouse">
                                            Traditional Earthen Mudhouse (₹11,500 / night)
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down select-arrow"></i>
                            </div>
                        </div>

                        <!-- Guests -->
                        <div class="form-field">
                            <label for="modal-guests" class="form-label font-sans">Guests</label>
                            <div class="select-wrapper">
                                <select id="modal-guests" class="form-input font-sans">
                                    <option value="1">1 Guest</option>
                                    <option value="2" selected>2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests (Mudhouse Family)</option>
                                </select>
                                <i class="fa-solid fa-chevron-down select-arrow"></i>
                            </div>
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
                        <span class="font-sans">Villa Rate:</span>
                        <span id="summary-villa-rate" class="font-sans">₹14,500</span>
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
