<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/client_auth.php';

ensure_rooms_pricing_columns(get_db());
ensure_users_and_guest_columns(get_db());

$modal_villas = get_all_rooms(true);
$modal_all_food = get_food_menu_items(null, true);
$currency = get_setting('currency_symbol', '₹');

$modal_food_cats = [
    'breakfast' => ['name' => 'Breakfast', 'title' => 'Morning in the Orchards', 'time' => '07:30 AM — 10:00 AM', 'icon' => 'fa-solid fa-mug-saucer', 'items' => []],
    'lunch' => ['name' => 'Lunch', 'title' => 'Claypot Hearth Feast', 'time' => '12:30 PM — 02:30 PM', 'icon' => 'fa-solid fa-bowl-rice', 'items' => []],
    'snacks' => ['name' => 'Evening Snacks', 'title' => 'Plantation Tea Ritual', 'time' => '04:30 PM — 06:30 PM', 'icon' => 'fa-solid fa-cookie-bite', 'items' => []],
    'dinner' => ['name' => 'Dinner', 'title' => 'Twilight Campfire Dining', 'time' => '07:30 PM — 10:00 PM', 'icon' => 'fa-solid fa-fire-burner', 'items' => []]
];

foreach ($modal_all_food as $mf) {
    $c = strtolower(trim($mf['category']));
    if (isset($modal_food_cats[$c])) {
        $modal_food_cats[$c]['items'][] = $mf;
    } else {
        $modal_food_cats['breakfast']['items'][] = $mf;
    }
}

$is_logged_user = is_client_user_logged_in();
$logged_user = $is_logged_user ? get_logged_in_client_user() : null;
?>

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

                <!-- Step 2: Curated Estate Gastronomy (Food Menu Selection) -->
                <div class="booking-section-group" id="booking-gastronomy-section">
                    <div class="section-heading-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                        <div>
                            <h4 class="group-title font-serif" style="margin-bottom: 2px;">2. Curate Your Estate Dining Rituals</h4>
                            <span class="optional-tag font-sans" style="font-size: 11px; color: #C5A059;">Optional • Select your preferred sets or skip anytime</span>
                        </div>
                        <!-- Global Skip Toggle -->
                        <label class="food-global-skip-toggle font-sans" style="display: inline-flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer; color: #A1B5A9; background: rgba(255,255,255,0.05); padding: 5px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);">
                            <input type="checkbox" id="toggle-skip-all-food" style="cursor: pointer;">
                            <span>Skip meal pre-selection (Decide on arrival)</span>
                        </label>
                    </div>

                    <div id="food-selection-container" class="food-selection-wrapper">
                        <?php foreach ($modal_food_cats as $cat_key => $cat_data): ?>
                            <div class="modal-meal-category-block" data-category="<?php echo $cat_key; ?>" style="margin-bottom: 20px; background: rgba(0,0,0,0.2); border: 1px solid rgba(197, 160, 89, 0.15); border-radius: 8px; padding: 16px;">
                                
                                <div class="meal-cat-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px dashed rgba(255,255,255,0.1);">
                                    <div>
                                        <span class="font-serif" style="font-size: 17px; color: #C5A059; font-weight: 600;">
                                            <i class="<?php echo htmlspecialchars($cat_data['icon']); ?>" style="margin-right: 6px;"></i>
                                            <?php echo htmlspecialchars($cat_data['name']); ?>
                                        </span>
                                        <span class="font-sans" style="font-size: 11px; color: #A1B5A9; margin-left: 8px;">(<?php echo htmlspecialchars($cat_data['time']); ?>)</span>
                                    </div>
                                    <label class="meal-skip-btn font-sans" style="font-size: 11px; color: #94A3B8; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                        <input type="checkbox" class="cat-skip-checkbox" data-target-cat="<?php echo $cat_key; ?>">
                                        <span>Skip <?php echo htmlspecialchars($cat_data['name']); ?></span>
                                    </label>
                                </div>

                                <div class="modal-dishes-grid" id="dishes-grid-<?php echo $cat_key; ?>" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;">
                                    <?php if (!empty($cat_data['items'])): ?>
                                        <?php foreach ($cat_data['items'] as $d_item): 
                                            $is_veg = ($d_item['dietary_type'] ?? 'veg') === 'veg';
                                            $d_price = (float)$d_item['price'];
                                        ?>
                                            <div class="modal-dish-card" 
                                                 data-dish-id="<?php echo $d_item['id']; ?>"
                                                 data-dish-category="<?php echo $cat_key; ?>"
                                                 data-dish-name="<?php echo htmlspecialchars($d_item['heading']); ?>"
                                                 data-dish-subtitle="<?php echo htmlspecialchars($d_item['subtitle'] ?? ''); ?>"
                                                 data-dish-price="<?php echo $d_price; ?>"
                                                 style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
                                                
                                                <div>
                                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                                                        <strong class="font-serif" style="font-size: 14.5px; color: #EAEFED; line-height: 1.3;">
                                                            <?php echo htmlspecialchars($d_item['heading']); ?>
                                                        </strong>
                                                        <span style="font-size: 11px; padding: 1px 4px; border: 1px solid <?php echo $is_veg ? '#10B981' : '#EF4444'; ?>; color: <?php echo $is_veg ? '#10B981' : '#EF4444'; ?>; border-radius: 2px;">
                                                            ●
                                                        </span>
                                                    </div>

                                                    <?php if (!empty($d_item['subtitle'])): ?>
                                                        <p class="font-sans" style="font-size: 11px; color: #A1B5A9; margin-bottom: 8px; line-height: 1.3;">
                                                            <?php echo htmlspecialchars($d_item['subtitle']); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($d_item['inclusions_list'])): ?>
                                                        <div style="font-size: 10.5px; color: #CBD5E1; margin-bottom: 10px; line-height: 1.4;">
                                                            <strong style="color: #C5A059;">Includes:</strong> <?php echo htmlspecialchars(implode(', ', array_slice($d_item['inclusions_list'], 0, 3))); ?><?php echo count($d_item['inclusions_list']) > 3 ? '...' : ''; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px; padding-top: 8px; border-top: 1px dashed rgba(255,255,255,0.08);">
                                                    <span class="font-sans" style="font-size: 13px; font-weight: 700; color: #C5A059;">
                                                        <?php echo $currency . number_format($d_price, 0); ?>
                                                        <span style="font-size: 10px; font-weight: normal; color: #94A3B8;">/set</span>
                                                    </span>

                                                    <!-- Quantity Stepper -->
                                                    <div class="food-dish-stepper" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.4); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 4px; padding: 2px 4px;">
                                                        <button type="button" class="btn-dish-qty minus" data-target-input="dish-qty-<?php echo $d_item['id']; ?>" style="background: transparent; border: none; color: #C5A059; font-weight: bold; width: 22px; cursor: pointer;">−</button>
                                                        <input type="number" id="dish-qty-<?php echo $d_item['id']; ?>" class="dish-qty-input font-sans" value="0" min="0" max="10" readonly style="width: 26px; text-align: center; background: transparent; border: none; color: #FFFFFF; font-size: 12px; font-weight: 600;">
                                                        <button type="button" class="btn-dish-qty plus" data-target-input="dish-qty-<?php echo $d_item['id']; ?>" style="background: transparent; border: none; color: #C5A059; font-weight: bold; width: 22px; cursor: pointer;">+</button>
                                                    </div>
                                                </div>

                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 3: Bespoke Curated Add-ons -->
                <div class="booking-section-group">
                    <h4 class="group-title font-serif">3. Signature Add-On Experiences <span class="optional-tag font-sans">(Optional)</span></h4>
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

                <!-- Step 4: Guest Identification & Account Choice -->
                <div class="booking-section-group">
                    <h4 class="group-title font-serif">4. Guest Particulars & Account Access</h4>
                    
                    <?php if ($is_logged_user && $logged_user): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-circle-check" style="color: #10B981; font-size: 18px;"></i>
                            <div class="font-sans" style="font-size: 13px;">
                                <strong>Logged in as <?php echo htmlspecialchars($logged_user['full_name']); ?></strong> (<?php echo htmlspecialchars($logged_user['email']); ?>)<br>
                                <span style="color: #A1B5A9; font-size: 11px;">This reservation will be permanently linked to your personal guest account.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Account Mode Selection -->
                        <div class="booking-account-selector font-sans" style="display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                            <label style="flex: 1; min-width: 220px; background: rgba(255,255,255,0.04); border: 1px solid rgba(197,160,89,0.3); border-radius: 6px; padding: 10px 14px; cursor: pointer; display: flex; align-items: flex-start; gap: 8px;">
                                <input type="radio" name="modal_account_type" value="guest" checked style="margin-top: 3px;">
                                <div>
                                    <strong style="font-size: 13px; color: #C5A059; display: block;">30-Day Guest Pass</strong>
                                    <span style="font-size: 11px; color: #94A3B8;">Instant booking with 30-day guest passcode for PDF receipts.</span>
                                </div>
                            </label>
                            <label style="flex: 1; min-width: 220px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 14px; cursor: pointer; display: flex; align-items: flex-start; gap: 8px;">
                                <input type="radio" name="modal_account_type" value="create_account" style="margin-top: 3px;">
                                <div>
                                    <strong style="font-size: 13px; color: #EAEFED; display: block;">Permanent Account</strong>
                                    <span style="font-size: 11px; color: #94A3B8;">Preserve stay history and re-download receipts anytime.</span>
                                </div>
                            </label>
                        </div>
                    <?php endif; ?>

                    <div class="booking-grid-2">
                        <div class="form-field">
                            <label for="modal-name" class="form-label font-sans">Full Name *</label>
                            <input type="text" id="modal-name" class="form-input font-sans" placeholder="e.g. Dr. Siddharth Menon" value="<?php echo htmlspecialchars($logged_user['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="modal-phone" class="form-label font-sans">WhatsApp / Mobile *</label>
                            <input type="tel" id="modal-phone" class="form-input font-sans" placeholder="+91 98765 43210" value="<?php echo htmlspecialchars($logged_user['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="booking-grid-2">
                        <div class="form-field">
                            <label for="modal-email" class="form-label font-sans">Email Address</label>
                            <input type="email" id="modal-email" class="form-input font-sans" placeholder="siddharth@example.com" value="<?php echo htmlspecialchars($logged_user['email'] ?? ''); ?>">
                        </div>
                        <div class="form-field">
                            <label for="modal-notes" class="form-label font-sans">Special Preferences</label>
                            <input type="text" id="modal-notes" class="form-input font-sans" placeholder="Dietary needs, honeymoon arrangement...">
                        </div>
                    </div>

                    <!-- Password field if creating permanent account -->
                    <div id="modal-password-container" style="display: none; margin-top: 10px;">
                        <div class="form-field">
                            <label for="modal-password" class="form-label font-sans">Create Account Password *</label>
                            <input type="password" id="modal-password" class="form-input font-sans" placeholder="Choose a password (min 6 characters)">
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
                        <span class="font-sans"><i class="fa-solid fa-user-plus" style="font-size: 11px;"></i> <span id="summary-extra-adults-label">Extra Adults:</span></span>
                        <span id="summary-extra-adults-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>
                    <div class="summary-line" id="summary-extra-kids-line" style="display: none; color: #f59e0b;">
                        <span class="font-sans"><i class="fa-solid fa-child" style="font-size: 11px;"></i> <span id="summary-extra-kids-label">Extra Children:</span></span>
                        <span id="summary-extra-kids-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>
                    
                    <!-- Curated Food Menu Itemized Line -->
                    <div class="summary-line" id="summary-food-line" style="display: none; color: #f59e0b;">
                        <span class="font-sans"><i class="fa-solid fa-utensils" style="font-size: 11px;"></i> <span id="summary-food-label">Curated Gastronomy:</span></span>
                        <span id="summary-food-rate" class="font-sans font-weight-600">+₹0</span>
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
                    <button type="button" id="btn-submit-booking-direct" class="btn-primary font-sans" style="background: #C5A059; color: #101F15; font-weight: 700; padding: 14px 24px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Confirm Reservation & Generate PDF Receipt</span>
                    </button>
                    <button type="button" id="btn-submit-whatsapp" class="btn-secondary btn-whatsapp-luxury font-sans" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>Instant WhatsApp Concierge Confirmation</span>
                    </button>
                </div>
                
                <p class="booking-guarantee-note font-sans">
                    <i class="fa-solid fa-lock"></i> Direct reservation • Your styled booking receipt with chosen dishes will be generated instantly.
                </p>
            </form>

            <!-- Booking Confirmed / Success Screen (Initially Hidden) -->
            <div id="booking-confirmation-state" style="display: none; padding: 30px 10px; text-align: center;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); border: 2px solid #10B981; color: #10B981; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <span class="font-sans" style="font-size: 12px; letter-spacing: 2px; color: #C5A059; text-transform: uppercase;">Reservation Confirmed</span>
                <h3 class="font-serif" style="font-size: 30px; color: #EAEFED; margin: 6px 0 12px;">Your Sanctuary Stay is Booked</h3>
                
                <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 10px; max-width: 460px; margin: 0 auto 24px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13.5px;">
                        <span style="color: #94A3B8;">Reservation ID:</span>
                        <strong id="confirm-ref-code" class="font-serif" style="color: #C5A059; font-size: 18px;">FF-0000</strong>
                    </div>
                    <div id="confirm-passcode-row" style="display: none; justify-content: space-between; margin-bottom: 8px; font-size: 13.5px;">
                        <span style="color: #94A3B8;">30-Day Guest Passcode:</span>
                        <span id="confirm-passcode-val" style="font-family: monospace; font-weight: bold; background: #C5A059; color: #101F15; padding: 2px 8px; border-radius: 4px;">0000</span>
                    </div>
                    <div id="confirm-expiry-row" style="display: none; justify-content: space-between; font-size: 12px; color: #A1B5A9;">
                        <span>Guest Access Expires:</span>
                        <span id="confirm-expiry-val">30 Days</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; max-width: 380px; margin: 0 auto;">
                    <a href="#" id="confirm-receipt-btn" target="_blank" class="btn-primary font-sans" style="background: #C5A059; color: #101F15; font-weight: 700; padding: 14px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Download Luxury PDF Receipt</span>
                    </a>
                    <a href="guest_portal.php" class="btn-secondary font-sans" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.2); color: #EAEFED; padding: 12px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-compass"></i>
                        <span>Open Guest Portal & History</span>
                    </a>
                    <a href="#" id="confirm-wa-btn" target="_blank" class="font-sans" style="color: #25D366; text-decoration: none; font-size: 13px; margin-top: 6px;">
                        <i class="fa-brands fa-whatsapp"></i> Chat with Master Concierge
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
