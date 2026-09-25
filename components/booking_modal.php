<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/client_auth.php';

$modal_db = get_db();
ensure_rooms_pricing_columns($modal_db);
ensure_users_and_guest_columns($modal_db);
ensure_booking_gst_columns($modal_db);

$modal_villas = get_all_rooms(true);
$modal_all_food = get_food_menu_items(null, true);
$currency = get_setting('currency_symbol', '₹');
$gst_rate_percent = (float)get_setting('gst_rate_percentage', '12');

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
            <form id="luxury-booking-form" class="booking-form" data-gst-rate="<?php echo htmlspecialchars($gst_rate_percent); ?>" onsubmit="event.preventDefault();">
                
                <!-- Step 1: Stay Details -->
                <div class="booking-section-group">
                    <h4 class="group-title font-serif">1. Select Your Stay & Dates</h4>
                    <div class="booking-grid-2">
                        <!-- Villa Selection -->
                        <div class="form-field" style="margin-bottom: 16px;">
                            <label for="modal-villa" class="form-label font-sans">Sanctuary Villa or Cottage</label>
                            <div class="select-wrapper">
                                <select id="modal-villa" class="form-input font-sans" style="-webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 38px; cursor: pointer;" required>
                                    <?php
                                    if (!empty($modal_villas)):
                                        foreach ($modal_villas as $mv):
                                            $struct = $mv['structure_type'] ?? 'single_hut';
                                            $struct_label = ($struct === 'duplex_hut') ? 'Duplex (2-Room Suite)' : 'Single Cottage';
                                            $cat_icon = ($mv['stay_type'] === 'mudhouse') ? '🌿 Mudhouse' : '🌲 Treehouse';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($mv['slug']); ?>" 
                                                data-price="<?php echo htmlspecialchars($mv['rate_per_night']); ?>" 
                                                data-single-rate="<?php echo htmlspecialchars($mv['single_room_rate'] ?? $mv['rate_per_night']); ?>"
                                                data-name="<?php echo htmlspecialchars($mv['title']); ?>"
                                                data-min-guests="<?php echo (int)($mv['min_guests'] ?? 2); ?>"
                                                data-base-guests="<?php echo (int)($mv['base_guests'] ?? 2); ?>"
                                                data-max-guests="<?php echo (int)($mv['max_guests'] ?? 4); ?>"
                                                data-extra-rate="<?php echo htmlspecialchars($mv['extra_guest_rate'] ?? 1500); ?>"
                                                data-extra-child-rate="<?php echo htmlspecialchars($mv['extra_child_rate'] ?? 800); ?>"
                                                data-structure-type="<?php echo htmlspecialchars($struct); ?>"
                                                data-stay-type="<?php echo htmlspecialchars($mv['stay_type'] ?? 'treehouse'); ?>">
                                            <?php echo "{$cat_icon} [{$struct_label}]: " . htmlspecialchars($mv['title']); ?> (From ₹<?php echo number_format($mv['rate_per_night'], 0, '.', ','); ?>/nt • Base <?php echo (int)($mv['base_guests'] ?? 2); ?> Guests)
                                        </option>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <option value="treehouse" data-price="14500" data-single-rate="14500" data-name="Luxury Canopy Treehouse" data-min-guests="2" data-base-guests="2" data-max-guests="4" data-extra-rate="1500" data-extra-child-rate="800" data-structure-type="single_hut" data-stay-type="treehouse">
                                            🌲 Treehouse [Single Cottage]: Luxury Canopy Treehouse (₹14,500/nt • Base 2 Guests)
                                        </option>
                                        <option value="mudhouse" data-price="11500" data-single-rate="11500" data-name="Traditional Earthen Mudhouse" data-min-guests="2" data-base-guests="2" data-max-guests="4" data-extra-rate="1500" data-extra-child-rate="800" data-structure-type="single_hut" data-stay-type="mudhouse">
                                            🌿 Mudhouse [Single Cottage]: Traditional Earthen Mudhouse (₹11,500/nt • Base 2 Guests)
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down select-arrow"></i>
                            </div>

                            <!-- Duplex Tier Switcher & Visual Architecture Guide Button -->
                            <div id="modal-duplex-tier-box" style="display: none; margin-top: 10px; background: rgba(197, 160, 89, 0.08); border: 1px dashed rgba(197, 160, 89, 0.45); border-radius: 8px; padding: 12px 14px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 6px;">
                                    <span class="font-sans" style="font-size: 11px; font-weight: 700; color: var(--accent-green); text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-layer-group" style="color: var(--accent-gold);"></i> Duplex Reservation Options:
                                    </span>
                                    <button type="button" class="btn-open-duplex-guide font-sans" onclick="openDuplexExplainer();" style="background: transparent; border: none; color: #0E7490; font-size: 11.5px; cursor: pointer; text-decoration: underline; display: flex; align-items: center; gap: 4px; padding: 0; font-weight: 600;">
                                        <i class="fa-solid fa-circle-question"></i> What is a Duplex? View Layout Plan
                                    </button>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <label class="modal-tier-radio-label" id="modal-tier-label-full" style="background: #FFFFFF; border: 1.5px solid var(--accent-gold); border-radius: 6px; padding: 10px; cursor: pointer; display: flex; align-items: flex-start; gap: 8px;">
                                        <input type="radio" name="modal_tier" value="full" checked style="margin-top: 2px; accent-color: var(--accent-gold);">
                                        <div>
                                            <strong style="font-size: 12.5px; color: var(--accent-green); display: block; font-weight: 700;">Entire Duplex (2 Rooms)</strong>
                                            <span style="font-size: 12px; color: var(--accent-gold); font-weight: 700;" id="modal-duplex-full-rate-txt">₹24,000/nt</span>
                                            <small style="font-size: 10px; color: #64748B; display: block;">Base 4 Guests • Max 8</small>
                                        </div>
                                    </label>
                                    <label class="modal-tier-radio-label" id="modal-tier-label-single" style="background: #FAFAFA; border: 1.5px solid rgba(28, 56, 38, 0.15); border-radius: 6px; padding: 10px; cursor: pointer; display: flex; align-items: flex-start; gap: 8px;">
                                        <input type="radio" name="modal_tier" value="single_room" style="margin-top: 2px; accent-color: var(--accent-gold);">
                                        <div>
                                            <strong style="font-size: 12.5px; color: var(--accent-green); display: block; font-weight: 700;">Single Room in Duplex</strong>
                                            <span style="font-size: 12px; color: #0E7490; font-weight: 700;" id="modal-duplex-single-rate-txt">₹14,500/nt</span>
                                            <small style="font-size: 10px; color: #64748B; display: block;">Base 2 Guests • Max 4</small>
                                        </div>
                                    </label>
                                </div>
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

                    <!-- Dynamic Real-Time Availability Feedback & Alternative Suggestions Box -->
                    <div id="modal-availability-box" class="modal-availability-status-box font-sans" style="display: none; margin-top: 14px; border-radius: 8px; padding: 14px 16px; border: 1.5px solid transparent; transition: all 0.25s ease;">
                        <div id="modal-avail-loading" style="display: none; align-items: center; gap: 8px; font-size: 13px; color: #64748B;">
                            <i class="fa-solid fa-spinner fa-spin" style="color: var(--accent-gold); font-size: 16px;"></i>
                            <span>Verifying live chalet availability with sanctuary reservation system...</span>
                        </div>
                        
                        <!-- State: Available -->
                        <div id="modal-avail-success" style="display: none; align-items: flex-start; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #059669; font-size: 15px;">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div style="flex: 1;">
                                <strong style="display: block; font-size: 13.5px; color: #065F46; font-weight: 700;" id="modal-avail-title">Chalet Available for Selected Dates</strong>
                                <span style="font-size: 12px; color: #047857; line-height: 1.45; display: block;" id="modal-avail-desc">This sanctuary suite is fully available for your stay. You can proceed with reservation and farm meal curation.</span>
                            </div>
                        </div>
                        
                        <!-- State: Booked / Conflict -->
                        <div id="modal-avail-conflict" style="display: none; align-items: flex-start; gap: 12px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #FEE2E2; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #DC2626; font-size: 16px;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div style="flex: 1;">
                                <strong style="display: block; font-size: 14px; color: #991B1B; font-weight: 700;" id="modal-conflict-title">Chalet Already Reserved for Selected Dates</strong>
                                <span style="font-size: 12.5px; color: #B91C1C; line-height: 1.5; display: block; margin-top: 3px;" id="modal-conflict-desc">We apologize, but this property has already been reserved for your chosen dates (via Direct Website, MakeMyTrip, or Airbnb). Please select alternative dates.</span>
                                
                                <!-- Quick Alternate Chalet Switch Pills -->
                                <div id="modal-alternate-chalets" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(220, 38, 38, 0.35); display: none;">
                                    <span style="font-size: 11.5px; font-weight: 700; color: #991B1B; display: block; margin-bottom: 6px;">
                                        <i class="fa-solid fa-sparkles" style="color: var(--accent-gold);"></i> Available Chalets on these exact dates:
                                    </span>
                                    <div id="modal-alternate-pills" style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <!-- Injected via JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Curated Estate Gastronomy (Food Menu Selection) -->
                <div class="booking-section-group" id="booking-gastronomy-section">
                    <div class="section-heading-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                        <div>
                            <h4 class="group-title font-serif" style="margin-bottom: 2px;">2. Curate Your Estate Dining Rituals</h4>
                            <span class="optional-tag font-sans" style="font-size: 12px; color: #8C6D28; font-weight: 500;">Optional • Select your preferred sets or skip anytime</span>
                        </div>
                        <!-- Global Skip Toggle -->
                        <label class="food-global-skip-toggle font-sans" style="display: inline-flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer; color: var(--accent-green); background: #FFFFFF; padding: 6px 14px; border-radius: 20px; border: 1.5px solid rgba(28,56,38,0.2);">
                            <input type="checkbox" id="toggle-skip-all-food" style="cursor: pointer; accent-color: var(--accent-gold);">
                            <span style="font-weight: 600;">Skip meal pre-selection (Decide on arrival)</span>
                        </label>
                    </div>

                    <div id="food-selection-container" class="food-selection-wrapper">
                        <?php foreach ($modal_food_cats as $cat_key => $cat_data): ?>
                            <div class="modal-meal-category-block" id="modal-cat-block-<?php echo $cat_key; ?>" data-category="<?php echo $cat_key; ?>" style="margin-bottom: 20px; background: #F8FAF8; border: 1.5px solid rgba(28, 56, 38, 0.12); border-radius: 10px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                                
                                <div class="meal-cat-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px dashed rgba(28, 56, 38, 0.15);">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span class="font-serif" style="font-size: 18px; color: var(--accent-green); font-weight: 700; letter-spacing: 0.3px;">
                                            <i class="<?php echo htmlspecialchars($cat_data['icon']); ?>" style="margin-right: 6px; color: var(--accent-gold);"></i>
                                            <?php echo htmlspecialchars($cat_data['name']); ?>
                                        </span>
                                        <span class="font-sans" style="font-size: 12px; color: var(--text-muted); background: rgba(28,56,38,0.06); padding: 2px 8px; border-radius: 4px; font-weight: 500;">
                                            <?php echo htmlspecialchars($cat_data['time']); ?>
                                        </span>
                                        <?php if ($cat_key === 'breakfast'): ?>
                                            <span class="font-sans" style="font-size: 11px; background: rgba(16, 185, 129, 0.12); color: #065F46; border: 1px solid #10B981; padding: 2px 8px; border-radius: 12px; font-weight: 700;">
                                                <i class="fa-solid fa-gift" style="color: #10B981;"></i> Complimentary
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <label class="meal-skip-btn font-sans" style="font-size: 12px; color: #475569; cursor: pointer; display: flex; align-items: center; gap: 6px; background: #FFFFFF; padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(28,56,38,0.15); font-weight: 500;">
                                        <input type="checkbox" class="cat-skip-checkbox" data-target-cat="<?php echo $cat_key; ?>" style="accent-color: var(--accent-gold);">
                                        <span>Skip <?php echo htmlspecialchars($cat_data['name']); ?></span>
                                    </label>
                                </div>

                                <?php if ($cat_key === 'breakfast'): ?>
                                    <!-- Breakfast Stay Duration Notice Banner -->
                                    <div id="breakfast-stay-notice" style="background: rgba(197, 160, 89, 0.12); border: 1px solid rgba(197, 160, 89, 0.45); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid fa-mug-hot" style="color: #8C6D28; font-size: 18px;"></i>
                                        <div class="font-sans" style="font-size: 12.5px; color: #1E293B; line-height: 1.45;">
                                            <strong style="color: #8C6D28;">Complimentary Breakfast Policy:</strong>
                                            <span id="breakfast-nights-dynamic-msg">For reservations of <strong>2 or more nights</strong>, select your customized breakfast dishes below. (Single-night stays receive Chef's Daily Organic Orchard Breakfast complimentary on departure morning).</span>
                                        </div>
                                    </div>

                                    <!-- 1-Night Single Stay Placeholder State (Shown when stay is 1 night) -->
                                    <div id="breakfast-1night-placeholder" style="display: none; background: rgba(16, 185, 129, 0.08); border: 1px dashed rgba(16, 185, 129, 0.4); border-radius: 8px; padding: 14px 16px; text-align: center; color: #065F46; font-size: 13px; font-weight: 600;">
                                        <i class="fa-solid fa-circle-check" style="color: #10B981; font-size: 16px; margin-right: 6px;"></i>
                                        Chef's Signature Farm Breakfast is automatically included complimentary on departure morning for your 1-night sanctuary stay.
                                    </div>
                                <?php endif; ?>

                                <div class="modal-dishes-grid" id="dishes-grid-<?php echo $cat_key; ?>" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px;">
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
                                                 style="background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.14); border-radius: 8px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                                
                                                <div>
                                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px;">
                                                        <strong class="font-serif" style="font-size: 15px; color: var(--accent-green); line-height: 1.35; font-weight: 700;">
                                                            <?php echo htmlspecialchars($d_item['heading']); ?>
                                                        </strong>
                                                        <span style="font-size: 10.5px; padding: 2px 6px; border: 1.5px solid <?php echo $is_veg ? '#059669' : '#DC2626'; ?>; color: <?php echo $is_veg ? '#059669' : '#DC2626'; ?>; border-radius: 3px; background: <?php echo $is_veg ? 'rgba(5, 150, 105, 0.08)' : 'rgba(220, 38, 38, 0.08)'; ?>; font-weight: 700;">
                                                            ● <?php echo $is_veg ? 'VEG' : 'NON-VEG'; ?>
                                                        </span>
                                                    </div>

                                                    <?php if (!empty($d_item['subtitle'])): ?>
                                                        <p class="font-sans" style="font-size: 12.5px; color: #4B5563; margin-bottom: 8px; line-height: 1.4;">
                                                            <?php echo htmlspecialchars($d_item['subtitle']); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($d_item['inclusions_list'])): ?>
                                                        <div style="font-size: 11.5px; color: #374151; margin-bottom: 12px; line-height: 1.4; background: #F4F6F4; padding: 6px 8px; border-radius: 4px; border: 1px solid rgba(28,56,38,0.06);">
                                                            <strong style="color: var(--accent-green);">Includes:</strong> <?php echo htmlspecialchars(implode(', ', array_slice($d_item['inclusions_list'], 0, 3))); ?><?php echo count($d_item['inclusions_list']) > 3 ? '...' : ''; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 10px; border-top: 1px dashed rgba(28, 56, 38, 0.15);">
                                                    <span class="font-sans" style="font-size: 14px; font-weight: 700; color: #935B28;">
                                                        <?php if ($cat_key === 'breakfast'): ?>
                                                            <span style="color: #059669; font-weight: 700;"><i class="fa-solid fa-gift"></i> Included</span>
                                                        <?php else: ?>
                                                            <?php echo $currency . number_format($d_price, 0); ?>
                                                            <span style="font-size: 11px; font-weight: normal; color: #64748B;">/set</span>
                                                        <?php endif; ?>
                                                    </span>

                                                    <!-- Quantity Stepper -->
                                                    <div class="food-dish-stepper" style="display: inline-flex; align-items: center; gap: 4px; background: #F3F4F6; border: 1px solid rgba(28, 56, 38, 0.2); border-radius: 6px; padding: 2px 6px;">
                                                        <button type="button" class="btn-dish-qty minus" data-target-input="dish-qty-<?php echo $d_item['id']; ?>" style="background: #FFFFFF; border: 1px solid rgba(28, 56, 38, 0.2); color: var(--accent-green); font-weight: bold; width: 24px; height: 24px; border-radius: 4px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center;">−</button>
                                                        <input type="number" id="dish-qty-<?php echo $d_item['id']; ?>" class="dish-qty-input font-sans" value="0" min="0" max="10" readonly style="width: 30px; text-align: center; background: transparent; border: none; color: var(--accent-green); font-size: 13px; font-weight: 700;">
                                                        <button type="button" class="btn-dish-qty plus" data-target-input="dish-qty-<?php echo $d_item['id']; ?>" style="background: #FFFFFF; border: 1px solid rgba(28, 56, 38, 0.2); color: var(--accent-green); font-weight: bold; width: 24px; height: 24px; border-radius: 4px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center;">+</button>
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
                    <h4 class="group-title font-serif" style="color: #1C3826 !important; font-size: 1.35rem; margin-bottom: 14px;">
                        3. Signature Add-On Experiences <span class="optional-tag font-sans" style="font-size: 12px; color: #8C6D28 !important; font-weight: 600;">(Optional)</span>
                    </h4>
                    
                    <div class="addons-grid">
                        <label class="addon-card">
                            <input type="checkbox" class="addon-checkbox" value="dinner" data-price="3000">
                            <div class="addon-card-inner" style="background: #FFFFFF !important; border: 1.5px solid rgba(28, 56, 38, 0.18) !important; border-radius: 8px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                <div class="addon-check-icon" style="border: 1.5px solid #94A3B8;"><i class="fa-solid fa-check"></i></div>
                                <div class="addon-details" style="flex: 1; padding-left: 8px;">
                                    <span class="addon-name font-serif" style="color: #1C3826 !important; font-size: 16px !important; font-weight: 700 !important; display: block;">Candlelight Orchard Dinner</span>
                                    <span class="addon-desc font-sans" style="color: #334155 !important; font-size: 13px !important; margin-top: 3px; display: block; font-weight: 500;">Private 4-course dinner set under blooming apple trees</span>
                                </div>
                                <div style="text-align: right; margin-left: 14px; min-width: 95px;">
                                    <span class="addon-price font-sans" style="color: #0E7490 !important; font-weight: 700 !important; font-size: 16px !important; display: block;">₹3,000</span>
                                    <small style="display: block; font-size: 11px; color: #64748B !important; font-weight: 600; line-height: 1.2;">Payable on-site</small>
                                </div>
                            </div>
                        </label>

                        <label class="addon-card">
                            <input type="checkbox" class="addon-checkbox" value="pottery" data-price="1500">
                            <div class="addon-card-inner" style="background: #FFFFFF !important; border: 1.5px solid rgba(28, 56, 38, 0.18) !important; border-radius: 8px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                <div class="addon-check-icon" style="border: 1.5px solid #94A3B8;"><i class="fa-solid fa-check"></i></div>
                                <div class="addon-details" style="flex: 1; padding-left: 8px;">
                                    <span class="addon-name font-serif" style="color: #1C3826 !important; font-size: 16px !important; font-weight: 700 !important; display: block;">Mud Pottery & Clay Workshop</span>
                                    <span class="addon-desc font-sans" style="color: #334155 !important; font-size: 13px !important; margin-top: 3px; display: block; font-weight: 500;">Hands-on natural cob crafting with local master artisans</span>
                                </div>
                                <div style="text-align: right; margin-left: 14px; min-width: 95px;">
                                    <span class="addon-price font-sans" style="color: #0E7490 !important; font-weight: 700 !important; font-size: 16px !important; display: block;">₹1,500</span>
                                    <small style="display: block; font-size: 11px; color: #64748B !important; font-weight: 600; line-height: 1.2;">Payable on-site</small>
                                </div>
                            </div>
                        </label>

                        <label class="addon-card">
                            <input type="checkbox" class="addon-checkbox" value="trek" data-price="2000">
                            <div class="addon-card-inner" style="background: #FFFFFF !important; border: 1.5px solid rgba(28, 56, 38, 0.18) !important; border-radius: 8px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                <div class="addon-check-icon" style="border: 1.5px solid #94A3B8;"><i class="fa-solid fa-check"></i></div>
                                <div class="addon-details" style="flex: 1; padding-left: 8px;">
                                    <span class="addon-name font-serif" style="color: #1C3826 !important; font-size: 16px !important; font-weight: 700 !important; display: block;">Sunrise High-Peak Valley Trail</span>
                                    <span class="addon-desc font-sans" style="color: #334155 !important; font-size: 13px !important; margin-top: 3px; display: block; font-weight: 500;">Guided wilderness trek with hilltop forest breakfast</span>
                                </div>
                                <div style="text-align: right; margin-left: 14px; min-width: 95px;">
                                    <span class="addon-price font-sans" style="color: #0E7490 !important; font-weight: 700 !important; font-size: 16px !important; display: block;">₹2,000</span>
                                    <small style="display: block; font-size: 11px; color: #64748B !important; font-weight: 600; line-height: 1.2;">Payable on-site</small>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Direct Artisan & Guide Payment Note (N.B.) -->
                    <div style="background: #F0F9FF !important; border: 1.5px solid #0284C7 !important; border-radius: 8px; padding: 14px 18px; margin-top: 16px; display: flex; align-items: flex-start; gap: 12px; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.08);">
                        <i class="fa-solid fa-circle-info" style="color: #0284C7; font-size: 20px; margin-top: 2px; flex-shrink: 0;"></i>
                        <div class="font-sans" style="font-size: 13px; color: #0F172A !important; line-height: 1.55;">
                            <strong style="color: #0369A1 !important; display: block; font-size: 13.5px; margin-bottom: 3px; font-weight: 700;">N.B. Direct Payment to Local Artisans & Guides:</strong>
                            <span style="color: #334155 !important; font-weight: 500;">Please note that Signature Add-On Experiences are operated directly by our local artisan community & mountain guides. Payments for these experiences are paid directly to the guides on-site upon participation and are <strong style="color: #0F172A;">not billed or collected</strong> in this advance accommodation voucher.</span>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Invoice & Billing Preference (Estimate vs GST Bill) -->
                <div class="booking-section-group" style="background: #F8FAF8; border: 1.5px solid rgba(197, 160, 89, 0.35); border-radius: 10px; padding: 20px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <h4 class="group-title font-serif" style="color: #1C3826 !important; font-size: 1.35rem; margin-bottom: 0;">
                            <i class="fa-solid fa-file-invoice-dollar" style="color: var(--accent-gold); margin-right: 6px;"></i> 4. Invoice & Billing Preference
                        </h4>
                        <span id="badge-gst-rate" style="font-size: 11.5px; background: rgba(14, 116, 144, 0.1); color: #0E7490; padding: 3px 10px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(14, 116, 144, 0.2);">
                            GST Tax Rate: <?php echo htmlspecialchars($gst_rate_percent); ?>%
                        </span>
                    </div>
                    
                    <p class="font-sans" style="font-size: 12.5px; color: #475569; margin-bottom: 14px; line-height: 1.5;">
                        Choose whether you require an official <strong>GST Tax Invoice</strong> (for corporate expense claim & input tax credit) or a standard <strong>Estimate Bill</strong>.
                    </p>

                    <!-- Billing Type Interactive Selection Cards -->
                    <div class="booking-billing-type-selector font-sans" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <!-- Estimate Bill Card -->
                        <label class="modal-billing-card modal-billing-card-active" id="label-bill-estimate" style="background: #FEF9C3; border: 2px solid #CA8A04; border-radius: 8px; padding: 14px 16px; cursor: pointer; display: flex; align-items: flex-start; gap: 12px; transition: all 0.2s ease;">
                            <input type="radio" name="modal_billing_type" value="estimate" checked style="margin-top: 3px; accent-color: #CA8A04;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                    <strong style="font-size: 14px; color: #854D0E; font-weight: 700;">Estimate Bill</strong>
                                    <span style="font-size: 10.5px; background: #FEF08A; color: #854D0E; padding: 2px 7px; border-radius: 3px; font-weight: 700;">Standard</span>
                                </div>
                                <span style="font-size: 11.5px; color: #475569; line-height: 1.4; display: block;">Standard reservation voucher & stay folio. No GSTIN required (0% GST added).</span>
                            </div>
                        </label>

                        <!-- GST Tax Invoice Card -->
                        <label class="modal-billing-card" id="label-bill-gst" style="background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 8px; padding: 14px 16px; cursor: pointer; display: flex; align-items: flex-start; gap: 12px; transition: all 0.2s ease;">
                            <input type="radio" name="modal_billing_type" value="gst" style="margin-top: 3px; accent-color: #0284C7;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                    <strong style="font-size: 14px; color: #0369A1; font-weight: 700;">GST Tax Invoice</strong>
                                    <span style="font-size: 10.5px; background: #E0F2FE; color: #0284C7; padding: 2px 7px; border-radius: 3px; font-weight: 700;">+<?php echo htmlspecialchars($gst_rate_percent); ?>% GST</span>
                                </div>
                                <span style="font-size: 11.5px; color: #475569; line-height: 1.4; display: block;">Official B2B / B2C Tax Invoice with GSTIN breakdown & billing address.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Collapsible GST Details Form (Shown when GST Bill is selected) -->
                    <div id="modal-gst-fields-wrapper" style="display: none; background: #FFFFFF; border: 1.5px solid #0284C7; border-radius: 8px; padding: 16px 18px; margin-top: 14px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08); transition: all 0.3s ease;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; color: #0284C7; font-size: 13px; font-weight: 700;">
                            <i class="fa-solid fa-building-flag"></i> <span>Company & GST Identification Details</span>
                        </div>

                        <div class="booking-grid-2">
                            <div class="form-field">
                                <label for="modal-gst-number" class="form-label font-sans" style="color: #0F172A !important; font-weight: 600;">
                                    GSTIN Number * <small style="font-size: 11px; color: #0284C7; font-weight: normal;">(15-Character GST ID)</small>
                                </label>
                                <input type="text" id="modal-gst-number" class="form-input font-sans" placeholder="e.g. 32AAAAA0000A1Z5" maxlength="15" style="text-transform: uppercase; font-family: monospace; font-weight: 700; color: #0F172A !important; letter-spacing: 1px;">
                            </div>
                            <div class="form-field">
                                <label for="modal-billing-name" class="form-label font-sans" style="color: #0F172A !important; font-weight: 600;">
                                    Billing Company / Registered Name *
                                </label>
                                <input type="text" id="modal-billing-name" class="form-input font-sans" placeholder="e.g. Acme Eco Enterprises Pvt Ltd" style="color: #0F172A !important;">
                            </div>
                        </div>

                        <div class="form-field" style="margin-top: 12px; margin-bottom: 0;">
                            <label for="modal-billing-address" class="form-label font-sans" style="color: #0F172A !important; font-weight: 600;">
                                Registered Company / Billing Address *
                            </label>
                            <textarea id="modal-billing-address" class="form-input font-sans" rows="2" placeholder="e.g. Suite 402, Green Valley Towers, Kakkanad, Kochi, Kerala 682030" style="color: #0F172A !important; resize: vertical; min-height: 55px;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Guest Identification & Account Choice -->
                <div class="booking-section-group">
                    <h4 class="group-title font-serif" style="color: #1C3826 !important; font-size: 1.35rem; margin-bottom: 16px;">5. Guest Particulars & Account Access</h4>
                    
                    <?php if ($is_logged_user && $logged_user): ?>
                        <div style="background: rgba(16, 185, 129, 0.12); border: 1.5px solid #10B981; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-circle-check" style="color: #10B981; font-size: 20px;"></i>
                            <div class="font-sans" style="font-size: 13px; color: var(--accent-green);">
                                <strong>Logged in as <?php echo htmlspecialchars($logged_user['full_name']); ?></strong> (<?php echo htmlspecialchars($logged_user['email']); ?>)<br>
                                <span style="color: #065F46; font-size: 11.5px;">This reservation will be permanently linked to your personal guest account.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- High Contrast Account Mode Selection -->
                        <div class="booking-account-selector font-sans" style="display: flex; gap: 14px; margin-bottom: 18px; flex-wrap: wrap;">
                            <label class="modal-account-card modal-account-card-active" id="label-acc-guest" style="flex: 1; min-width: 240px; background: #FEF9C3 !important; border: 2px solid #CA8A04 !important; border-radius: 8px; padding: 14px 16px; cursor: pointer; display: flex; align-items: flex-start; gap: 12px; transition: all 0.2s ease;">
                                <input type="radio" name="modal_account_type" value="guest" checked style="margin-top: 3px; accent-color: #CA8A04;">
                                <div>
                                    <strong style="font-size: 14.5px; color: #854D0E !important; display: block; margin-bottom: 3px; font-weight: 700;">30-Day Guest Pass</strong>
                                    <span style="font-size: 12px; color: #334155 !important; line-height: 1.4; display: block; font-weight: 500;">Instant booking with 30-day guest passcode for PDF receipts.</span>
                                </div>
                            </label>
                            <label class="modal-account-card" id="label-acc-permanent" style="flex: 1; min-width: 240px; background: #F8FAFC !important; border: 1.5px solid #CBD5E1 !important; border-radius: 8px; padding: 14px 16px; cursor: pointer; display: flex; align-items: flex-start; gap: 12px; transition: all 0.2s ease;">
                                <input type="radio" name="modal_account_type" value="create_account" style="margin-top: 3px; accent-color: #CA8A04;">
                                <div>
                                    <strong style="font-size: 14.5px; color: #1C3826 !important; display: block; margin-bottom: 3px; font-weight: 700;">Permanent Account</strong>
                                    <span style="font-size: 12px; color: #334155 !important; line-height: 1.4; display: block; font-weight: 500;">Preserve stay history, auto-fill details & re-download receipts anytime.</span>
                                </div>
                            </label>
                        </div>
                    <?php endif; ?>

                    <!-- Standard Comprehensive Guest Details Form -->
                    <div class="booking-grid-2">
                        <div class="form-field">
                            <label for="modal-name" class="form-label font-sans" style="color: #1E293B !important; font-weight: 600;">Full Name *</label>
                            <input type="text" id="modal-name" class="form-input font-sans" placeholder="e.g. Dr. Siddharth Menon" value="<?php echo htmlspecialchars($logged_user['full_name'] ?? ''); ?>" style="color: #0F172A !important;" required>
                        </div>
                        <div class="form-field">
                            <label for="modal-phone" class="form-label font-sans" style="color: #1E293B !important; font-weight: 600;">WhatsApp / Mobile *</label>
                            <input type="tel" id="modal-phone" class="form-input font-sans" placeholder="+91 98765 43210" value="<?php echo htmlspecialchars($logged_user['phone'] ?? ''); ?>" style="color: #0F172A !important;" required>
                        </div>
                    </div>

                    <div class="booking-grid-2">
                        <div class="form-field">
                            <label for="modal-email" class="form-label font-sans" style="color: #1E293B !important; font-weight: 600;">Email Address *</label>
                            <input type="email" id="modal-email" class="form-input font-sans" placeholder="siddharth@example.com" value="<?php echo htmlspecialchars($logged_user['email'] ?? ''); ?>" style="color: #0F172A !important;" required>
                        </div>
                        <div class="form-field">
                            <label for="modal-city" class="form-label font-sans" style="color: #1E293B !important; font-weight: 600;">City & State / Province</label>
                            <input type="text" id="modal-city" class="form-input font-sans" placeholder="e.g. Kochi, Kerala / Bengaluru, KA" style="color: #0F172A !important;">
                        </div>
                    </div>

                    <!-- Government ID Proof Verification Fields -->
                    <div class="booking-grid-2">
                        <div class="form-field">
                            <label for="modal-id-type" class="form-label font-sans" style="color: #1E293B !important; font-weight: 600;">Government ID Proof Type</label>
                            <div class="select-wrapper">
                                <select id="modal-id-type" class="form-input font-sans" style="color: #0F172A !important; -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 38px; cursor: pointer;">
                                    <option value="Aadhaar Card" selected>Aadhaar Card (Indian Residents)</option>
                                    <option value="Driving License">Driving License</option>
                                    <option value="Passport">Passport (International / NRI / Indian)</option>
                                    <option value="Voter ID">Voter ID Card</option>
                                    <option value="PAN Card">PAN Card</option>
                                    <option value="Government ID">Other Government Photo ID</option>
                                </select>
                                <i class="fa-solid fa-chevron-down select-arrow"></i>
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="modal-id-number" class="form-label font-sans" style="color: #1E293B !important; font-weight: 600;">ID Number / Reference (Optional)</label>
                            <input type="text" id="modal-id-number" class="form-input font-sans" placeholder="e.g. XXXX-XXXX-1234 or Pass No." style="color: #0F172A !important;">
                        </div>
                    </div>

                    <!-- Upload Government ID Document / Photo -->
                    <div class="form-field" style="margin-bottom: 16px;">
                        <label class="form-label font-sans" style="color: #1E293B !important; font-weight: 600; display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span><i class="fa-solid fa-id-card-clip" style="color: var(--accent-gold); margin-right: 5px;"></i> Upload Government ID Proof (Optional)</span>
                            <span style="font-size: 11px; color: #64748B; font-weight: 500;">Max 8MB (JPG, PNG, WEBP, PDF)</span>
                        </label>
                        <div class="id-proof-upload-wrapper" id="id-proof-upload-box" style="position: relative; border: 1.5px dashed #0284C7; background: #F0F9FF; border-radius: 8px; padding: 14px 18px; text-align: center; cursor: pointer; transition: all 0.2s ease;">
                            <input type="file" id="modal-id-file" name="id_proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 5;">
                            
                            <div id="id-file-prompt" style="display: flex; align-items: center; justify-content: center; gap: 12px; color: #0369A1;">
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(2, 132, 199, 0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 18px; color: #0284C7;"></i>
                                </div>
                                <div style="text-align: left;">
                                    <span class="font-sans" style="font-size: 13px; font-weight: 700; color: #0369A1; display: block;">Click or drag photo/PDF of ID card</span>
                                    <span class="font-sans" style="font-size: 11.5px; color: #475569; display: block;">Securely encrypted • Enables fast contactless sanctuary check-in</span>
                                </div>
                            </div>

                            <div id="id-file-selected" style="display: none; align-items: center; justify-content: space-between; background: #FFFFFF; border: 1.5px solid #059669; border-radius: 6px; padding: 8px 12px; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 10px; overflow: hidden; text-align: left;">
                                    <i class="fa-solid fa-file-circle-check" style="color: #059669; font-size: 20px; flex-shrink: 0;"></i>
                                    <div style="overflow: hidden;">
                                        <div id="id-file-name" class="font-sans" style="font-size: 13px; font-weight: 700; color: #065F46; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">filename.pdf</div>
                                        <span id="id-file-size" class="font-sans" style="font-size: 11px; color: #64748B;">(1.2 MB)</span>
                                    </div>
                                </div>
                                <button type="button" id="btn-remove-id-file" style="background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.3); color: #DC2626; cursor: pointer; font-size: 11.5px; padding: 4px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; position: relative; z-index: 10;" title="Remove chosen file">
                                    <i class="fa-solid fa-trash-can"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-field" style="margin-bottom: 12px;">
                        <label for="modal-notes" class="form-label font-sans" style="color: #1E293B !important; font-weight: 600;">Special Preferences & Dietary Requests</label>
                        <input type="text" id="modal-notes" class="form-input font-sans" placeholder="e.g. Vegan, Jain food, Anniversary celebration, Late arrival..." style="color: #0F172A !important;">
                        <div style="font-size: 12.5px; color: #166534 !important; background: #F0FDF4; border: 1px solid #86EFAC; padding: 8px 12px; border-radius: 6px; margin-top: 10px; display: flex; align-items: center; gap: 8px; font-weight: 500;">
                            <i class="fa-solid fa-shield-halved" style="color: #16A34A; font-size: 15px;"></i>
                            <span>Original government photo ID must be presented upon sanctuary check-in for registration.</span>
                        </div>
                    </div>

                    <!-- Password field if creating permanent account -->
                    <div id="modal-password-container" style="display: none; margin-top: 10px;">
                        <div class="form-field">
                            <label for="modal-password" class="form-label font-sans">Create Account Password *</label>
                            <input type="password" id="modal-password" class="form-input font-sans" placeholder="Choose a secure password (min 4 characters)">
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
                    <div class="summary-line" id="summary-extra-adults-line" style="display: none; color: #15803d;">
                        <span class="font-sans"><i class="fa-solid fa-user-plus" style="font-size: 11px;"></i> <span id="summary-extra-adults-label">Extra Adults:</span></span>
                        <span id="summary-extra-adults-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>
                    <div class="summary-line" id="summary-extra-kids-line" style="display: none; color: #d97706;">
                        <span class="font-sans"><i class="fa-solid fa-child" style="font-size: 11px;"></i> <span id="summary-extra-kids-label">Extra Children:</span></span>
                        <span id="summary-extra-kids-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>
                    
                    <!-- Curated Food Menu Itemized Line -->
                    <div class="summary-line" id="summary-food-line" style="display: none; color: #d97706;">
                        <span class="font-sans"><i class="fa-solid fa-utensils" style="font-size: 11px;"></i> <span id="summary-food-label">Curated Gastronomy:</span></span>
                        <span id="summary-food-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>

                    <div class="summary-line" id="summary-addons-line" style="display: none; color: #0E7490;">
                        <span class="font-sans"><i class="fa-solid fa-person-hiking" style="font-size: 11px;"></i> <span id="summary-addons-label">Selected Experiences:</span></span>
                        <span id="summary-addons-rate" class="font-sans font-weight-600" style="color: #0E7490;">Payable On-Site (₹0 in Bill)</span>
                    </div>

                    <!-- Taxable Subtotal Line (Shown when GST is Active) -->
                    <div class="summary-line" id="summary-subtotal-line" style="display: none; border-top: 1px dashed rgba(28, 56, 38, 0.15); padding-top: 8px; margin-top: 4px;">
                        <span class="font-sans font-weight-600" style="color: #475569;">Taxable Subtotal (Stay + Meals):</span>
                        <span id="summary-subtotal-rate" class="font-sans font-weight-600" style="color: #1E293B;">₹14,500</span>
                    </div>

                    <!-- GST Tax Rate Line (Shown when GST Bill is Chosen) -->
                    <div class="summary-line" id="summary-gst-line" style="display: none; color: #0284C7; font-weight: 600;">
                        <span class="font-sans"><i class="fa-solid fa-file-invoice-dollar" style="font-size: 11.5px;"></i> <span id="summary-gst-label">GST Tax (<?php echo htmlspecialchars($gst_rate_percent); ?>%):</span></span>
                        <span id="summary-gst-rate" class="font-sans font-weight-600">+₹0</span>
                    </div>

                    <div class="summary-line total-line">
                        <span class="font-serif" id="summary-total-title">Estimated Total (Standard Folio):</span>
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
                <span class="font-sans" style="font-size: 12px; letter-spacing: 2px; color: var(--accent-gold); text-transform: uppercase; font-weight: 700;">Reservation Confirmed</span>
                <h3 class="font-serif" style="font-size: 30px; color: var(--accent-green); margin: 6px 0 12px;">Your Sanctuary Stay is Booked</h3>
                
                <div style="background: #F8FAF8; border: 1.5px solid rgba(197, 160, 89, 0.4); border-radius: 10px; max-width: 460px; margin: 0 auto 24px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13.5px;">
                        <span style="color: #64748B;">Reservation ID:</span>
                        <strong id="confirm-ref-code" class="font-serif" style="color: var(--accent-green); font-size: 18px;">FF-0000</strong>
                    </div>
                    <div id="confirm-passcode-row" style="display: none; justify-content: space-between; margin-bottom: 8px; font-size: 13.5px;">
                        <span style="color: #64748B;">30-Day Guest Passcode:</span>
                        <span id="confirm-passcode-val" style="font-family: monospace; font-weight: bold; background: #C5A059; color: #101F15; padding: 2px 8px; border-radius: 4px;">0000</span>
                    </div>
                    <div id="confirm-expiry-row" style="display: none; justify-content: space-between; font-size: 12px; color: #64748B;">
                        <span>Guest Access Expires:</span>
                        <span id="confirm-expiry-val" style="font-weight: 600; color: var(--accent-green);">30 Days</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; max-width: 380px; margin: 0 auto;">
                    <a href="#" id="confirm-receipt-btn" target="_blank" class="btn-primary font-sans" style="background: #C5A059; color: #101F15; font-weight: 700; padding: 14px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Download Luxury PDF Receipt</span>
                    </a>
                    <a href="guest_portal.php" class="btn-secondary font-sans" style="background: #FFFFFF; border: 1.5px solid rgba(28,56,38,0.2); color: var(--accent-green); padding: 12px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600;">
                        <i class="fa-solid fa-compass" style="color: var(--accent-gold);"></i>
                        <span>Open Guest Portal & History</span>
                    </a>
                    <a href="#" id="confirm-wa-btn" target="_blank" class="font-sans" style="color: #25D366; text-decoration: none; font-size: 13.5px; margin-top: 6px; font-weight: 600;">
                        <i class="fa-brands fa-whatsapp"></i> Chat with Master Concierge
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Real-Time Reservation Conflict / Availability Notice Popup Modal -->
<div id="realtime-conflict-modal" class="realtime-alert-modal-overlay" aria-hidden="true" role="dialog">
    <div class="realtime-alert-card font-sans">
        <div class="realtime-alert-icon-wrap">
            <i class="fa-solid fa-calendar-xmark"></i>
        </div>
        <h3 class="realtime-alert-title font-serif" id="rt-alert-title">Dates Already Reserved</h3>
        <p class="realtime-alert-msg" id="rt-alert-msg">
            We apologize, but this chalet has already been reserved for the selected dates (via Direct Website, MakeMyTrip, or Airbnb). Please select alternative dates.
        </p>

        <!-- Dynamic Available Chalets List in Popup -->
        <div id="rt-alert-alternatives" style="display: none; background: #F8FAF8; border: 1px solid rgba(28, 56, 38, 0.12); border-radius: 8px; padding: 12px; margin-bottom: 18px; text-align: left;">
            <strong style="font-size: 12px; color: var(--accent-green); display: block; margin-bottom: 6px;">
                <i class="fa-solid fa-sparkles" style="color: var(--accent-gold);"></i> Available Chalets for your exact dates:
            </strong>
            <div id="rt-alert-alt-list" style="display: flex; flex-direction: column; gap: 6px;"></div>
        </div>

        <div class="realtime-alert-actions">
            <button type="button" class="btn-primary font-sans" id="rt-alert-close-btn" style="background: var(--accent-green); color: #FFFFFF; padding: 10px 22px; border-radius: 6px; border: none; font-weight: 700; cursor: pointer;">
                <span>Select Alternative Dates</span>
            </button>
            <a href="booking.php" class="btn-secondary font-sans" id="rt-alert-map-btn" style="background: #FAF8F5; border: 1.5px solid rgba(197, 160, 89, 0.5); color: var(--accent-green); padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-map-location-dot" style="color: var(--accent-gold);"></i>
                <span>View Map Availability</span>
            </a>
        </div>
    </div>
</div>

<?php
// Load Duplex Explainer & Layout Guide Modal
require_once __DIR__ . '/duplex_explainer_modal.php';
?>
