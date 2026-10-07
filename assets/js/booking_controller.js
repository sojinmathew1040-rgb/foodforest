// =========================================================================
// Food Forest Sanctuary — Interactive Estate Booking Controller (BookMyShow Style)
// Real-Time Dynamic Map Availability & Property Status Synchronization
// =========================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Check if we are on booking page
    const mapLayout = document.getElementById('booking-map-layout');
    if (!mapLayout) return;

    // Hide bottom sticky booking pill if present on this dedicated booking page
    const stickyPill = document.getElementById('sticky-booking-pill');
    if (stickyPill) {
        stickyPill.style.display = 'none';
        stickyPill.classList.remove('visible');
    }

    const spotsData = window.bookingSanctuarySpots || [];
    const roomsData = window.bookingRooms || [];
    const preselectSlug = window.preselectVillaSlug || '';

    // DOM Elements
    const checkinInput = document.getElementById('book-checkin');
    const checkoutInput = document.getElementById('book-checkout');
    const adultsInput = document.getElementById('book-adults');
    const kidsInput = document.getElementById('book-kids');
    const viewBtnMap = document.getElementById('view-btn-map');
    const viewBtnGrid = document.getElementById('view-btn-grid');
    const gridLayout = document.getElementById('booking-grid-layout');
    const filterBtns = document.querySelectorAll('.chip-btn[data-stay-filter]');

    // Live Date Bar Elements
    const liveDatesTxt = document.getElementById('bms-live-dates-txt');
    const countAvailEl = document.getElementById('bms-count-avail');
    const countBookedEl = document.getElementById('bms-count-booked');

    // Sidebar Inspector Elements
    const sacTypePill = document.getElementById('sac-type-pill');
    const sacAvailPill = document.getElementById('sac-avail-pill');
    const sacMainImg = document.getElementById('sac-main-img');
    const sacGalPrev = document.getElementById('sac-gal-prev');
    const sacGalNext = document.getElementById('sac-gal-next');
    const sacGalIndicator = document.getElementById('sac-gal-indicator');
    const sacTitle = document.getElementById('sac-title');
    const sacDesc = document.getElementById('sac-desc');
    const sacBookedWarning = document.getElementById('sac-booked-warning');
    const sacBookedWarningMsg = document.getElementById('sac-booked-warning-msg');
    const sacTierBox = document.getElementById('sac-tier-box');
    const tocRateFull = document.getElementById('toc-rate-full');
    const tocRateSingle = document.getElementById('toc-rate-single');
    const sacPriceLabel = document.getElementById('sac-price-label');
    const sacBasePrice = document.getElementById('sac-base-price');
    const sacExtraRow = document.getElementById('sac-extra-row');
    const sacExtraPrice = document.getElementById('sac-extra-price');
    const sacTotalPrice = document.getElementById('sac-total-price');
    const btnSacOpenCheckout = document.getElementById('btn-sac-open-checkout');

    // New Facility vs Stay DOM Elements
    const sacRatesPolicy = document.getElementById('sac-rates-policy');
    const sacPriceBreakdown = document.getElementById('sac-price-breakdown');
    const sacFacilityCard = document.getElementById('sac-facility-card');
    const sfcDesc = document.getElementById('sfc-desc');
    const sfcHighlights = document.getElementById('sfc-highlights');
    const btnSacCheckInfo = document.getElementById('btn-sac-check-info');
    const sacWaBtn = document.getElementById('sac-wa-btn');
    const sacWaText = document.getElementById('sac-wa-text');
    const sacAmenitiesRow = document.getElementById('sac-amenities-row');
    const conciergeWhatsApp = window.conciergeWhatsApp || '919234567890';

    const nodes = Array.from(document.querySelectorAll('.bms-chalet-node'));
    nodes.forEach(node => {
        const topVal = parseFloat(node.style.top || "50");
        const leftVal = parseFloat(node.style.left || "50");
        if (topVal <= 45) node.classList.add('pos-bottom');
        if (leftVal <= 25) node.classList.add('pos-left');
        else if (leftVal >= 75) node.classList.add('pos-right');
    });

    // State
    let currentSpot = null;
    let currentRoom = null;
    let currentPhotoIdx = 0;
    let currentPhotos = [];
    let currentTier = 'full'; // 'full' or 'single_room'
    let currentDuplexUnit = 'left'; // 'left', 'right', or 'full'
    let currentAvailabilityData = null;
    let availAbortController = null;

    // 1. Helper to find room by slug (with fuzzy fallback)
    function findRoomBySlug(slug) {
        if (!slug) return null;
        let r = roomsData.find(rm => rm.slug === slug);
        if (!r) {
            r = roomsData.find(rm => {
                const s = rm.slug.toLowerCase();
                const q = slug.toLowerCase();
                return s.includes(q) || q.includes(s) ||
                    (s.includes('treehouse') && q.includes('treehouse')) ||
                    (s.includes('mudhouse') && q.includes('mudhouse')) ||
                    (s.includes('woodhouse') && q.includes('woodhouse'));
            });
        }
        return r || null;
    }

    // 2. Helper to calculate nights
    function getNights() {
        if (!checkinInput || !checkoutInput) return 1;
        const d1 = new Date(checkinInput.value);
        const d2 = new Date(checkoutInput.value);
        const diffTime = d2 - d1;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return Math.max(1, isNaN(diffDays) ? 1 : diffDays);
    }

    // 3. Update Photo Carousel in Sidebar
    function updateSidebarPhoto() {
        if (!currentPhotos || currentPhotos.length === 0) return;
        if (currentPhotoIdx >= currentPhotos.length) currentPhotoIdx = 0;
        if (currentPhotoIdx < 0) currentPhotoIdx = currentPhotos.length - 1;

        if (sacMainImg) {
            sacMainImg.classList.add('fade');
            setTimeout(() => {
                sacMainImg.src = currentPhotos[currentPhotoIdx];
                sacMainImg.classList.remove('fade');
            }, 120);
        }

        if (sacGalIndicator) {
            sacGalIndicator.innerText = `${currentPhotoIdx + 1} / ${currentPhotos.length}`;
        }
    }

    // Dynamic Photo Set Switcher for Duplex Wings & Chalets
    function syncPhotosForSelection() {
        if (!currentSpot) return;
        const isStay = (!emptyOrZero(currentSpot.is_stay) || currentSpot.category === 'stays');
        const isDuplex = isStay && (currentSpot.structure_type === 'duplex_hut' || (currentRoom && currentRoom.structure_type === 'duplex_hut'));

        if (!isStay) {
            if (currentSpot.photos_list && currentSpot.photos_list.length > 0 && currentSpot.photos_list[0]) {
                currentPhotos = currentSpot.photos_list.filter(p => p && p.trim() !== '');
            } else if (currentSpot.image_url && currentSpot.image_url.trim() !== '') {
                currentPhotos = [currentSpot.image_url];
            } else {
                const titleLower = (currentSpot.title || '').toLowerCase();
                if (currentSpot.category === 'dining' || titleLower.includes('dining') || titleLower.includes('kitchen')) {
                    currentPhotos = ['assets/images/food_kerala_sadya.jpg', 'assets/images/food_dosa_set.jpg', 'assets/images/food_evening_snacks.jpg'];
                } else if (titleLower.includes('pool')) {
                    currentPhotos = ['assets/images/01 (10).jpeg', 'assets/images/01 (14).jpeg'];
                } else if (titleLower.includes('campfire') || titleLower.includes('recreation')) {
                    currentPhotos = ['assets/images/01 (26).jpeg', 'assets/images/01 (7).jpeg'];
                } else if (titleLower.includes('kids')) {
                    currentPhotos = ['assets/images/01 (8).jpeg', 'assets/images/01 (9).jpeg'];
                } else {
                    currentPhotos = ['assets/images/01 (25).jpeg'];
                }
            }
        } else if (isDuplex && currentTier === 'single_room') {
            if (currentDuplexUnit === 'left' && currentSpot.photos_left_list && currentSpot.photos_left_list.length > 0) {
                currentPhotos = currentSpot.photos_left_list;
            } else if (currentDuplexUnit === 'right' && currentSpot.photos_right_list && currentSpot.photos_right_list.length > 0) {
                currentPhotos = currentSpot.photos_right_list;
            } else if (currentRoom && currentDuplexUnit === 'left' && currentRoom.photos_left_list && currentRoom.photos_left_list.length > 0) {
                currentPhotos = currentRoom.photos_left_list;
            } else if (currentRoom && currentDuplexUnit === 'right' && currentRoom.photos_right_list && currentRoom.photos_right_list.length > 0) {
                currentPhotos = currentRoom.photos_right_list;
            } else if (currentSpot.photos_list && currentSpot.photos_list.length > 0) {
                currentPhotos = currentSpot.photos_list;
            } else {
                currentPhotos = [currentSpot.image_url || 'assets/images/01 (25).jpeg'];
            }
        } else {
            if (currentSpot.photos_list && currentSpot.photos_list.length > 0) {
                currentPhotos = currentSpot.photos_list;
            } else if (currentRoom && currentRoom.photos_list && currentRoom.photos_list.length > 0) {
                currentPhotos = currentRoom.photos_list;
            } else if (currentSpot.image_url) {
                currentPhotos = [currentSpot.image_url];
            } else if (currentRoom && currentRoom.image_url) {
                currentPhotos = [currentRoom.image_url];
            } else {
                currentPhotos = ['assets/images/01 (25).jpeg'];
            }
        }
        currentPhotoIdx = 0;
        updateSidebarPhoto();
    }

    // 4. Recalculate and render pricing
    function recalculateSidebarPricing() {
        if (!currentSpot) return;
        const isStay = (!emptyOrZero(currentSpot.is_stay) || currentSpot.category === 'stays');
        if (!isStay) return; // Do not calculate nightly room tariff for facilities!

        const nights = getNights();
        const adults = parseInt(adultsInput ? adultsInput.value : "2", 10) || 2;
        const kids = parseInt(kidsInput ? kidsInput.value : "0", 10) || 0;

        let ratePerNight = parseFloat(currentSpot.room_rate || currentSpot.stay_price || 5000);
        let baseGuests = currentRoom ? parseInt(currentRoom.base_guests || 2, 10) : 2;
        let extraAdultRate = currentRoom ? parseFloat(currentRoom.extra_guest_rate || 750) : 750;
        let extraChildRate = currentRoom ? parseFloat(currentRoom.extra_child_rate || 0) : 0;

        const isDuplex = (currentSpot.structure_type === 'duplex_hut' || (currentRoom && currentRoom.structure_type === 'duplex_hut'));
        const maxAdultsForSpot = (isDuplex && currentTier !== 'single_room') ? 8 : 4;

        if (isDuplex && currentTier === 'single_room') {
            ratePerNight = parseFloat(currentSpot.single_room_rate || currentRoom.single_room_rate || 4000);
            baseGuests = 2;
        }

        // Occupancy calculations (Rule: For 1 room base 2, max 4 adults; full duplex base 4, max 8 adults)
        const cappedAdults = Math.min(adults, maxAdultsForSpot);
        const adultsInBase = Math.min(cappedAdults, baseGuests);
        const extraAdults = Math.max(0, cappedAdults - adultsInBase);
        const remainingBase = Math.max(0, baseGuests - adultsInBase);
        const kidsInBase = Math.min(kids, remainingBase);
        const extraKids = Math.max(0, kids - kidsInBase);

        const extraAdultsFee = extraAdults * extraAdultRate * nights;
        const extraKidsFee = extraKids * extraChildRate * nights;
        const extraTotal = extraAdultsFee + extraKidsFee;

        const baseTotal = ratePerNight * nights;
        const netTotal = baseTotal + extraTotal;

        if (sacPriceLabel) {
            sacPriceLabel.innerText = `Chalet Rate (${nights} ${nights === 1 ? 'Night' : 'Nights'}):`;
        }
        if (sacBasePrice) {
            sacBasePrice.innerText = `₹${baseTotal.toLocaleString('en-IN')}`;
        }

        if (sacExtraRow && sacExtraPrice) {
            if (extraTotal > 0) {
                sacExtraRow.style.display = 'flex';
                sacExtraPrice.innerText = `+₹${extraTotal.toLocaleString('en-IN')}`;
            } else {
                sacExtraRow.style.display = 'none';
            }
        }

        if (sacTotalPrice) {
            sacTotalPrice.innerText = `₹${netTotal.toLocaleString('en-IN')}`;
        }
    }

    // 5. Select Chalet by Spot ID or Element
    function selectChalet(spotId, triggerPopupIfBooked = false) {
        const spot = spotsData.find(s => parseInt(s.id, 10) === parseInt(spotId, 10));
        if (!spot) return;

        currentSpot = spot;
        const isStay = (!emptyOrZero(spot.is_stay) || spot.category === 'stays');
        const roomSlug = spot.linked_room_slug || 'treehouse';
        currentRoom = findRoomBySlug(roomSlug);

        // Update active node styling
        nodes.forEach(node => {
            if (parseInt(node.getAttribute('data-spot-id'), 10) === parseInt(spotId, 10)) {
                node.classList.add('is-selected');
            } else {
                node.classList.remove('is-selected');
            }
        });

        // Check availability status from latest fetched data
        let spotAvail = true;
        let spotPartiallyBooked = false;
        let leftAvail = true;
        let rightAvail = true;
        let fullAvail = true;
        let spotMessage = '';
        if (isStay && currentAvailabilityData && currentAvailabilityData.spots_status) {
            const spStatus = currentAvailabilityData.spots_status[spot.id];
            if (spStatus) {
                spotAvail = spStatus.available;
                spotPartiallyBooked = !!spStatus.partially_booked;
                leftAvail = (spStatus.left_available !== undefined) ? spStatus.left_available : true;
                rightAvail = (spStatus.right_available !== undefined) ? spStatus.right_available : true;
                fullAvail = (spStatus.full_available !== undefined) ? spStatus.full_available : true;
                spotMessage = spStatus.message;
            }
        }

        // Setup photos based on selection
        syncPhotosForSelection();

        // Update titles & descriptions
        if (sacTitle) sacTitle.innerText = spot.title;
        if (sacDesc) sacDesc.innerText = spot.description;

        if (sacTypePill) {
            sacTypePill.innerHTML = isStay ? '<i class="fa-solid fa-house-chimney"></i> BOOKABLE CHALET' : '<i class="fa-solid fa-water"></i> ESTATE FACILITY';
        }

        if (sacAvailPill) {
            if (!isStay) {
                sacAvailPill.innerHTML = '<i class="fa-solid fa-sparkles" style="color: #56c2c9;"></i> Open for Guests';
                sacAvailPill.className = 'sac-avail-pill font-sans';
            } else if (spotPartiallyBooked) {
                sacAvailPill.innerHTML = '<i class="fa-solid fa-bolt" style="color: #D97706;"></i> 1 Suite Available (Partially Booked)';
                sacAvailPill.className = 'sac-avail-pill sac-partially-booked font-sans';
            } else if (spotAvail) {
                sacAvailPill.innerHTML = '<i class="fa-solid fa-circle-check"></i> Available for Selected Dates';
                sacAvailPill.className = 'sac-avail-pill sac-available font-sans';
            } else {
                sacAvailPill.innerHTML = '<i class="fa-solid fa-ban"></i> Reserved for Selected Dates';
                sacAvailPill.className = 'sac-avail-pill sac-booked font-sans';
            }
        }

        // Booked or Partially Booked Warning in Sidebar
        if (sacBookedWarning) {
            if (isStay && spotPartiallyBooked) {
                sacBookedWarning.className = 'sac-partially-booked-box font-sans';
                sacBookedWarning.style.display = 'flex';
                const note = !leftAvail ? 'Left Suite (Wing A) is reserved. Right Suite (Wing B) is available!' : 'Right Suite (Wing B) is reserved. Left Suite (Wing A) is available!';
                sacBookedWarning.innerHTML = `
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <strong style="color: #92400E; font-size: 13px; display: block;">1 Suite Available (Partially Booked)</strong>
                        <p id="sac-booked-warning-msg" style="margin: 3px 0 0; font-size: 12px; color: #B45309; line-height: 1.45;">
                            ${spotMessage || note}
                        </p>
                    </div>
                `;
            } else if (isStay && !spotAvail) {
                sacBookedWarning.className = 'sac-booked-warning-box font-sans';
                sacBookedWarning.style.display = 'flex';
                sacBookedWarning.innerHTML = `
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong style="color: #991B1B; font-size: 13.5px; display: block;">Chalet Already Reserved</strong>
                        <p id="sac-booked-warning-msg" style="margin: 3px 0 6px; font-size: 12px; color: #B91C1C; line-height: 1.45;">
                            ${spotMessage || `We apologize, but ${spot.title} has already been reserved for your selected stay dates.`}
                        </p>
                        <span style="font-size: 11.5px; color: #7F1D1D; font-weight: 600;">Please select alternative dates above or pick another available chalet on the map.</span>
                    </div>
                `;
            } else {
                sacBookedWarning.style.display = 'none';
            }
        }

        // Proceed to reserve button state & facility vs stay UI toggles
        if (isStay) {
            // Show Stay rate policies & price breakdown
            if (sacRatesPolicy) sacRatesPolicy.style.display = 'block';
            if (sacPriceBreakdown) sacPriceBreakdown.style.display = 'block';
            if (sacFacilityCard) sacFacilityCard.style.display = 'none';

            // Show Proceed to Reserve button, Hide Check Info button
            if (btnSacOpenCheckout) btnSacOpenCheckout.style.display = 'inline-flex';
            if (btnSacCheckInfo) btnSacCheckInfo.style.display = 'none';

            if (btnSacOpenCheckout) {
                if (!spotAvail) {
                    btnSacOpenCheckout.disabled = true;
                    btnSacOpenCheckout.classList.add('btn-disabled-booked');
                    btnSacOpenCheckout.innerHTML = '<i class="fa-solid fa-calendar-xmark"></i> <span>Chalet Reserved for Selected Dates</span>';
                } else if (spotPartiallyBooked) {
                    btnSacOpenCheckout.disabled = false;
                    btnSacOpenCheckout.classList.remove('btn-disabled-booked');
                    btnSacOpenCheckout.innerHTML = '<span>Book Available Suite</span> <i class="fa-solid fa-arrow-right"></i>';
                } else {
                    btnSacOpenCheckout.disabled = false;
                    btnSacOpenCheckout.classList.remove('btn-disabled-booked');
                    btnSacOpenCheckout.innerHTML = '<span>Proceed to Reserve</span> <i class="fa-solid fa-arrow-right"></i>';
                }
            }

            // Restore Stay Amenities
            if (sacAmenitiesRow) {
                sacAmenitiesRow.innerHTML = `
                    <span><i class="fa-solid fa-utensils"></i> All Farm Meals Included</span>
                    <span><i class="fa-solid fa-wifi"></i> Forest Wi-Fi</span>
                    <span><i class="fa-solid fa-mug-hot"></i> Organic Tea Ritual</span>
                    <span><i class="fa-solid fa-square-parking"></i> Free Parking</span>
                `;
            }

            // Update WhatsApp Link for Stay Booking
            if (sacWaBtn) {
                sacWaBtn.href = `https://wa.me/${conciergeWhatsApp}?text=${encodeURIComponent(`Hello Concierge, I am interested in booking ${spot.title} at Food Forest.`)}`;
            }
            if (sacWaText) {
                sacWaText.innerText = 'WhatsApp Concierge';
            }
        } else {
            // Non-Stay Facilities & Amenities (Pool, Campfire Glade, Dining Hub, Kids Park, etc.)
            // Hide Stay rate policies & room price breakdown
            if (sacRatesPolicy) sacRatesPolicy.style.display = 'none';
            if (sacPriceBreakdown) sacPriceBreakdown.style.display = 'none';
            if (sacFacilityCard) sacFacilityCard.style.display = 'block';

            // Hide Proceed to Reserve button, Show Check Info button
            if (btnSacOpenCheckout) btnSacOpenCheckout.style.display = 'none';
            if (btnSacCheckInfo) {
                btnSacCheckInfo.style.display = 'inline-flex';
                btnSacCheckInfo.disabled = false;
            }

            // Update Facility Card text
            if (sfcDesc) {
                sfcDesc.innerText = spot.description || 'This facility is an open sanctuary amenity accessible to all guests staying at our cottages. No separate reservation required.';
            }

            // Contextual Highlights
            const titleLower = (spot.title || '').toLowerCase();
            if (sfcHighlights) {
                if (titleLower.includes('pool')) {
                    sfcHighlights.innerHTML = `
                        <span><i class="fa-solid fa-droplet" style="color: #0EA5E9;"></i> Mountain Spring Water (Zero Chemicals)</span>
                        <span><i class="fa-solid fa-person-swimming" style="color: #0EA5E9;"></i> Open Daily 07:00 AM – 06:00 PM</span>
                        <span><i class="fa-solid fa-gift" style="color: #10B981;"></i> Complimentary In-Stay Access</span>
                    `;
                } else if (titleLower.includes('campfire') || titleLower.includes('recreation')) {
                    sfcHighlights.innerHTML = `
                        <span><i class="fa-solid fa-fire" style="color: #F59E0B;"></i> Twilight Bonfire &amp; Acoustic Music</span>
                        <span><i class="fa-solid fa-star" style="color: #F59E0B;"></i> Stargazing Glade with Firepit Seating</span>
                        <span><i class="fa-solid fa-gift" style="color: #10B981;"></i> Complimentary In-Stay Access</span>
                    `;
                } else if (titleLower.includes('kitchen') || titleLower.includes('dining') || spot.category === 'dining') {
                    sfcHighlights.innerHTML = `
                        <span><i class="fa-solid fa-utensils" style="color: #F59E0B;"></i> All 4 Farm Meals Included in Tariff</span>
                        <span><i class="fa-solid fa-fire-burner" style="color: #F59E0B;"></i> Claypot Slow Hearth Cooking</span>
                        <span><i class="fa-solid fa-seedling" style="color: #10B981;"></i> 100% Soil-to-Plate Organic Harvest</span>
                    `;
                } else {
                    sfcHighlights.innerHTML = `
                        <span><i class="fa-regular fa-clock" style="color: #0EA5E9;"></i> Open Daily for In-House Guests</span>
                        <span><i class="fa-solid fa-mountain-sun" style="color: var(--accent-gold);"></i> 1,600M Scenic High-Range Atmosphere</span>
                        <span><i class="fa-solid fa-gift" style="color: #10B981;"></i> Complimentary In-Stay Access</span>
                    `;
                }
            }

            // Tailored Amenities Pills for Facility
            if (sacAmenitiesRow) {
                if (titleLower.includes('pool')) {
                    sacAmenitiesRow.innerHTML = `
                        <span><i class="fa-solid fa-droplet"></i> Natural Spring Water</span>
                        <span><i class="fa-solid fa-shower"></i> River Stone Bath</span>
                        <span><i class="fa-solid fa-leaf"></i> Zero Chlorine</span>
                        <span><i class="fa-solid fa-mountain"></i> Mountain Vista</span>
                    `;
                } else if (titleLower.includes('campfire') || titleLower.includes('recreation')) {
                    sacAmenitiesRow.innerHTML = `
                        <span><i class="fa-solid fa-fire"></i> Twilight Bonfire</span>
                        <span><i class="fa-solid fa-music"></i> Acoustic Stargazing</span>
                        <span><i class="fa-solid fa-mug-hot"></i> Evening Chai Ritual</span>
                        <span><i class="fa-solid fa-users"></i> Gathering Glade</span>
                    `;
                } else if (titleLower.includes('kitchen') || titleLower.includes('dining') || spot.category === 'dining') {
                    sacAmenitiesRow.innerHTML = `
                        <span><i class="fa-solid fa-utensils"></i> All Meals Included</span>
                        <span><i class="fa-solid fa-seedling"></i> Farm Fresh Harvest</span>
                        <span><i class="fa-solid fa-fire-burner"></i> Claypot Cooking</span>
                        <span><i class="fa-solid fa-mug-hot"></i> Marayoor Spices</span>
                    `;
                } else {
                    sacAmenitiesRow.innerHTML = `
                        <span><i class="fa-solid fa-sparkles"></i> Included Amenity</span>
                        <span><i class="fa-solid fa-mountain"></i> 1,600M High Range</span>
                        <span><i class="fa-solid fa-wifi"></i> Forest Wi-Fi</span>
                        <span><i class="fa-solid fa-leaf"></i> 100% Organic Sanctuary</span>
                    `;
                }
            }

            // Update WhatsApp Link for Facility Inquiry
            if (sacWaBtn) {
                sacWaBtn.href = `https://wa.me/${conciergeWhatsApp}?text=${encodeURIComponent(`Hello Concierge, I would like to know more about the ${spot.title} at Food Forest.`)}`;
            }
            if (sacWaText) {
                sacWaText.innerText = 'Inquire with Concierge';
            }
        }

        // Duplex Tier Controls
        const isDuplex = (spot.structure_type === 'duplex_hut' || (currentRoom && currentRoom.structure_type === 'duplex_hut'));
        const sacDuplexWingBox = document.getElementById('sac-duplex-wing-box');
        const sacWingCardLeft = document.getElementById('sac-wing-card-left');
        const sacWingCardRight = document.getElementById('sac-wing-card-right');
        const sacWingBadgeLeft = document.getElementById('sac-wing-badge-left');
        const sacWingBadgeRight = document.getElementById('sac-wing-badge-right');
        const sacWingStatusHint = document.getElementById('sac-wing-status-hint');
        const tierRadioFull = document.querySelector('input[name="sac_tier_choice"][value="full"]');
        const tierRadioSingle = document.querySelector('input[name="sac_tier_choice"][value="single_room"]');
        const tierLabelFull = document.getElementById('tier-label-full');

        if (sacTierBox) {
            if (isDuplex && isStay) {
                sacTierBox.style.display = 'block';
                const fullRate = parseFloat(spot.room_rate || 8000);
                const singleRate = parseFloat(spot.single_room_rate || 4000);
                if (tocRateFull) tocRateFull.innerText = `₹${fullRate.toLocaleString('en-IN')}/nt`;
                if (tocRateSingle) tocRateSingle.innerText = `₹${singleRate.toLocaleString('en-IN')}/nt`;

                // If partially booked or full villa not available, disable Entire Duplex choice
                if (spotPartiallyBooked || !fullAvail) {
                    if (tierRadioFull) {
                        tierRadioFull.disabled = true;
                        tierRadioFull.checked = false;
                    }
                    if (tierLabelFull) {
                        tierLabelFull.style.opacity = '0.45';
                        tierLabelFull.style.cursor = 'not-allowed';
                    }
                    if (tierRadioSingle) {
                        tierRadioSingle.checked = true;
                        currentTier = 'single_room';
                    }
                } else {
                    if (tierRadioFull) tierRadioFull.disabled = false;
                    if (tierLabelFull) {
                        tierLabelFull.style.opacity = '1';
                        tierLabelFull.style.cursor = 'pointer';
                    }
                }

                // Show wing selector if single_room is selected
                if (sacDuplexWingBox) {
                    sacDuplexWingBox.style.display = (currentTier === 'single_room' || spotPartiallyBooked) ? 'block' : 'none';
                }

                // Update wing cards state (Left vs Right)
                if (sacWingCardLeft && sacWingCardRight) {
                    const wingRadioLeft = sacWingCardLeft.querySelector('input[value="left"]');
                    const wingRadioRight = sacWingCardRight.querySelector('input[value="right"]');

                    if (!leftAvail) {
                        sacWingCardLeft.classList.add('disabled');
                        sacWingCardLeft.classList.remove('is-selected');
                        if (wingRadioLeft) wingRadioLeft.disabled = true;
                        if (sacWingBadgeLeft) {
                            sacWingBadgeLeft.className = 'wing-badge booked';
                            sacWingBadgeLeft.innerText = 'Booked';
                        }
                    } else {
                        sacWingCardLeft.classList.remove('disabled');
                        if (wingRadioLeft) wingRadioLeft.disabled = false;
                        if (sacWingBadgeLeft) {
                            sacWingBadgeLeft.className = 'wing-badge available';
                            sacWingBadgeLeft.innerText = 'Available';
                        }
                    }

                    if (!rightAvail) {
                        sacWingCardRight.classList.add('disabled');
                        sacWingCardRight.classList.remove('is-selected');
                        if (wingRadioRight) wingRadioRight.disabled = true;
                        if (sacWingBadgeRight) {
                            sacWingBadgeRight.className = 'wing-badge booked';
                            sacWingBadgeRight.innerText = 'Booked';
                        }
                    } else {
                        sacWingCardRight.classList.remove('disabled');
                        if (wingRadioRight) wingRadioRight.disabled = false;
                        if (sacWingBadgeRight) {
                            sacWingBadgeRight.className = 'wing-badge available';
                            sacWingBadgeRight.innerText = 'Available';
                        }
                    }

                    // Auto-select the available wing if one is booked
                    if (!leftAvail && rightAvail) {
                        if (wingRadioRight) wingRadioRight.checked = true;
                        sacWingCardRight.classList.add('is-selected');
                        sacWingCardLeft.classList.remove('is-selected');
                        currentDuplexUnit = 'right';
                        if (sacWingStatusHint) {
                            sacWingStatusHint.innerText = 'Right Suite Available';
                            sacWingStatusHint.style.background = '#FEF3C7';
                            sacWingStatusHint.style.color = '#92400E';
                            sacWingStatusHint.style.border = '1px solid #FCD34D';
                        }
                    } else if (leftAvail && !rightAvail) {
                        if (wingRadioLeft) wingRadioLeft.checked = true;
                        sacWingCardLeft.classList.add('is-selected');
                        sacWingCardRight.classList.remove('is-selected');
                        currentDuplexUnit = 'left';
                        if (sacWingStatusHint) {
                            sacWingStatusHint.innerText = 'Left Suite Available';
                            sacWingStatusHint.style.background = '#FEF3C7';
                            sacWingStatusHint.style.color = '#92400E';
                            sacWingStatusHint.style.border = '1px solid #FCD34D';
                        }
                    } else if (leftAvail && rightAvail) {
                        if (sacWingStatusHint) {
                            sacWingStatusHint.innerText = 'Both Wings Available';
                            sacWingStatusHint.style.background = '#DCFCE7';
                            sacWingStatusHint.style.color = '#166534';
                            sacWingStatusHint.style.border = '1px solid #86EFAC';
                        }
                        if (currentDuplexUnit === 'right') {
                            if (wingRadioRight) wingRadioRight.checked = true;
                            sacWingCardRight.classList.add('is-selected');
                            sacWingCardLeft.classList.remove('is-selected');
                        } else {
                            if (wingRadioLeft) wingRadioLeft.checked = true;
                            sacWingCardLeft.classList.add('is-selected');
                            sacWingCardRight.classList.remove('is-selected');
                            currentDuplexUnit = 'left';
                        }
                    } else {
                        if (sacWingStatusHint) {
                            sacWingStatusHint.innerText = 'Both Suites Reserved';
                            sacWingStatusHint.style.background = '#FEE2E2';
                            sacWingStatusHint.style.color = '#991B1B';
                            sacWingStatusHint.style.border = '1px solid #FCA5A5';
                        }
                    }
                }
            } else {
                sacTierBox.style.display = 'none';
                currentTier = 'full';
                currentDuplexUnit = 'full';
            }
        }

        // Re-sync photos to reflect wing
        syncPhotosForSelection();
        recalculateSidebarPricing();

        // Trigger real-time conflict popup modal if user specifically clicked an unavailable chalet
        if (isStay && !spotAvail && triggerPopupIfBooked) {
            const availableAlts = [];
            if (currentAvailabilityData && currentAvailabilityData.rooms_status) {
                for (const k in currentAvailabilityData.rooms_status) {
                    const rData = currentAvailabilityData.rooms_status[k];
                    if (rData.available) {
                        availableAlts.push(rData);
                    }
                }
            }

            if (typeof showRealtimeConflictAlert === 'function') {
                showRealtimeConflictAlert(
                    `${spot.title} Already Reserved`,
                    spotMessage || `We apologize, but ${spot.title} has already been reserved for your selected stay dates (via Direct Website, MakeMyTrip, or Airbnb). Please select alternative dates.`,
                    availableAlts
                );
            }
        }
    }

    function emptyOrZero(val) {
        return !val || val === '0' || val === 0;
    }

    // 6. Node Click Handlers
    nodes.forEach(node => {
        node.addEventListener('click', () => {
            const spotId = node.getAttribute('data-spot-id');
            selectChalet(spotId, true);

            // On mobile / tablet screens, smoothly scroll to inspector card
            if (window.innerWidth <= 1024) {
                const inspectorCard = document.getElementById('sidebar-active-card') || document.getElementById('bms-sidebar-inspector');
                if (inspectorCard) {
                    setTimeout(() => {
                        inspectorCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 80);
                }
            }
        });
    });

    // 7. Photo Gallery Navigation
    if (sacGalPrev) {
        sacGalPrev.addEventListener('click', (e) => {
            e.stopPropagation();
            currentPhotoIdx--;
            updateSidebarPhoto();
        });
    }

    if (sacGalNext) {
        sacGalNext.addEventListener('click', (e) => {
            e.stopPropagation();
            currentPhotoIdx++;
            updateSidebarPhoto();
        });
    }

    // 8. Tier Choice Radio Change
    document.querySelectorAll('input[name="sac_tier_choice"]').forEach(radio => {
        radio.addEventListener('change', function() {
            currentTier = this.value;
            const sacDuplexWingBox = document.getElementById('sac-duplex-wing-box');
            if (sacDuplexWingBox) {
                sacDuplexWingBox.style.display = (currentTier === 'single_room') ? 'block' : 'none';
            }
            syncPhotosForSelection();
            recalculateSidebarPricing();
        });
    });

    // Duplex Wing Choice Radio & Card Click Handlers
    document.querySelectorAll('input[name="sac_duplex_wing"]').forEach(radio => {
        radio.addEventListener('change', function() {
            currentDuplexUnit = this.value;
            const cardLeft = document.getElementById('sac-wing-card-left');
            const cardRight = document.getElementById('sac-wing-card-right');
            if (currentDuplexUnit === 'left') {
                if (cardLeft) cardLeft.classList.add('is-selected');
                if (cardRight) cardRight.classList.remove('is-selected');
            } else {
                if (cardRight) cardRight.classList.add('is-selected');
                if (cardLeft) cardLeft.classList.remove('is-selected');
            }
            syncPhotosForSelection();
        });
    });

    const sacWingCardLeftEl = document.getElementById('sac-wing-card-left');
    const sacWingCardRightEl = document.getElementById('sac-wing-card-right');
    if (sacWingCardLeftEl) {
        sacWingCardLeftEl.addEventListener('click', function(e) {
            if (this.classList.contains('disabled')) return;
            const r = this.querySelector('input[type="radio"]');
            if (r && !r.checked) {
                r.checked = true;
                r.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
    if (sacWingCardRightEl) {
        sacWingCardRightEl.addEventListener('click', function(e) {
            if (this.classList.contains('disabled')) return;
            const r = this.querySelector('input[type="radio"]');
            if (r && !r.checked) {
                r.checked = true;
                r.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    // -------------------------------------------------------------
    // 9. Real-Time Dynamic Date Availability Fetcher (BookMyShow Style)
    // -------------------------------------------------------------
    async function fetchLiveAvailabilityForDates(triggerNotification = false) {
        if (!checkinInput || !checkoutInput) return;

        const cin = checkinInput.value;
        const cout = checkoutInput.value;
        if (!cin || !cout) return;

        if (availAbortController) {
            availAbortController.abort();
        }
        availAbortController = new AbortController();

        try {
            const url = `api/check_availability.php?checkin=${encodeURIComponent(cin)}&checkout=${encodeURIComponent(cout)}`;
            const res = await fetch(url, { signal: availAbortController.signal });
            const data = await res.json();

            if (!data || !data.success) return;

            currentAvailabilityData = data;

            // 1. Update Live Summary Bar
            if (liveDatesTxt) {
                liveDatesTxt.innerText = `${data.checkin_formatted} – ${data.checkout_formatted} (${data.nights} ${data.nights === 1 ? 'Night' : 'Nights'})`;
            }
            if (countAvailEl && data.summary) {
                countAvailEl.innerText = `${data.summary.available_count} Available`;
            }
            if (countBookedEl && data.summary) {
                countBookedEl.innerText = `${data.summary.booked_count} Booked / Reserved`;
            }

            // 2. Update Map Chalet Nodes
            nodes.forEach(node => {
                const spotId = node.getAttribute('data-spot-id');
                const isStay = node.getAttribute('data-is-stay') === '1';
                if (!isStay) return;

                const spotStatus = data.spots_status ? data.spots_status[spotId] : null;
                const nodeBox = node.querySelector('.node-box');
                const statusDot = node.querySelector('.node-status-dot');
                const hoverStatus = node.querySelector('.nhc-status');
                const hoverCta = node.querySelector('.nhc-cta');
                let bookedPill = node.querySelector('.node-booked-pill');

                let partiallyPill = node.querySelector('.node-partially-booked-pill');

                if (spotStatus && spotStatus.partially_booked) {
                    // Partially Booked Duplex (1 Suite Booked, 1 Suite Free)
                    node.classList.remove('status-available', 'status-fast_filling', 'status-booked');
                    node.classList.add('status-partially-booked');
                    node.setAttribute('data-status', 'partially_booked');

                    if (statusDot) {
                        statusDot.className = 'node-status-dot status-dot-partially-booked';
                    }

                    if (bookedPill) bookedPill.remove();

                    if (!partiallyPill && nodeBox) {
                        partiallyPill = document.createElement('span');
                        partiallyPill.className = 'node-partially-booked-pill';
                        partiallyPill.innerHTML = '<i class="fa-solid fa-bolt"></i> 1 SUITE LEFT';
                        nodeBox.appendChild(partiallyPill);
                    }

                    if (hoverStatus) {
                        hoverStatus.className = 'nhc-status';
                        hoverStatus.style.color = '#F59E0B';
                        const availNote = spotStatus.partially_booked_note ? ` (${spotStatus.partially_booked_note})` : '';
                        hoverStatus.innerHTML = `<i class="fa-solid fa-bolt"></i> 1 Suite Available${availNote}`;
                    }
                    if (hoverCta) {
                        hoverCta.innerHTML = 'Click to Reserve Available Suite &rarr;';
                    }
                } else if (spotStatus && !spotStatus.available) {
                    // Marked as Booked
                    node.classList.remove('status-available', 'status-fast_filling', 'status-partially-booked');
                    node.classList.add('status-booked');
                    node.setAttribute('data-status', 'booked');

                    if (statusDot) {
                        statusDot.className = 'node-status-dot status-dot-booked';
                    }

                    if (partiallyPill) partiallyPill.remove();

                    if (!bookedPill && nodeBox) {
                        bookedPill = document.createElement('span');
                        bookedPill.className = 'node-booked-pill';
                        bookedPill.innerHTML = '<i class="fa-solid fa-lock"></i> BOOKED';
                        nodeBox.appendChild(bookedPill);
                    }

                    if (hoverStatus) {
                        hoverStatus.className = 'nhc-status status-label-booked';
                        hoverStatus.style.color = '';
                        hoverStatus.innerHTML = '<i class="fa-solid fa-ban"></i> Reserved for Dates';
                    }
                    if (hoverCta) {
                        hoverCta.innerHTML = 'Click to View Alternate Dates &rarr;';
                    }
                } else {
                    // Marked as Available
                    node.classList.remove('status-booked', 'status-partially-booked');
                    node.classList.add('status-available');
                    node.setAttribute('data-status', 'available');

                    if (statusDot) {
                        statusDot.className = 'node-status-dot status-dot-available';
                    }

                    if (bookedPill) {
                        bookedPill.remove();
                    }
                    if (partiallyPill) {
                        partiallyPill.remove();
                    }

                    if (hoverStatus) {
                        hoverStatus.className = 'nhc-status status-label-available';
                        hoverStatus.style.color = '';
                        hoverStatus.innerHTML = '<i class="fa-solid fa-circle-check"></i> Available for Dates';
                    }
                    if (hoverCta) {
                        hoverCta.innerHTML = 'Click to View Details & Rates &rarr;';
                    }
                }
            });

            // 3. Update Grid View Chalet Cards
            document.querySelectorAll('.chalet-card').forEach(card => {
                const slug = card.getAttribute('data-villa-slug')?.toLowerCase();
                let roomMatch = data.rooms_status ? data.rooms_status[slug] : null;
                if (!roomMatch && data.rooms_status) {
                    for (const k in data.rooms_status) {
                        if (slug.includes(k) || k.includes(slug) ||
                            (slug.includes('treehouse') && k.includes('treehouse')) ||
                            (slug.includes('mudhouse') && k.includes('mudhouse')) ||
                            (slug.includes('woodhouse') && k.includes('woodhouse'))) {
                            roomMatch = data.rooms_status[k];
                            break;
                        }
                    }
                }

                const btn = card.querySelector('.select-from-grid-btn');
                if (roomMatch && roomMatch.partially_booked) {
                    card.classList.remove('is-booked-card');
                    card.classList.add('is-partially-booked-card');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Book Available Suite';
                    }
                } else if (roomMatch && !roomMatch.available) {
                    card.classList.remove('is-partially-booked-card');
                    card.classList.add('is-booked-card');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa-solid fa-ban"></i> Reserved for Dates';
                    }
                } else {
                    card.classList.remove('is-booked-card', 'is-partially-booked-card');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Book Chalet';
                    }
                }
            });

            // 4. Re-sync currently inspected spot in sidebar
            if (currentSpot) {
                selectChalet(currentSpot.id, false);
            }

            // 5. Evaluate Smart Group Auto-Suggestions
            evaluateGroupRecommendations(triggerNotification);

        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Booking live availability error:', err);
            }
        }
    }

    // -------------------------------------------------------------
    // Smart Auto-Suggestion & Multi-Property Accommodation Engine
    // -------------------------------------------------------------
    let currentRecommendations = [];
    let activeRecommendationIdx = 0;
    let isRecommendationDismissed = false;

    function evaluateGroupRecommendations(userInitiated = false) {
        const recPanel = document.getElementById('bms-group-recommendation-panel');
        const restoreBtn = document.getElementById('btn-restore-recommendation');
        const subtitleTxt = document.getElementById('bms-rec-subtitle-txt');
        const optionsGrid = document.getElementById('bms-rec-options-grid');
        const activeTitleEl = document.getElementById('bms-rec-active-title');
        const activeMetaEl = document.getElementById('bms-rec-active-meta');
        const bookBtnTxt = document.getElementById('btn-rec-book-txt');

        if (!recPanel) return;

        const adults = parseInt(adultsInput ? adultsInput.value : "2", 10) || 2;
        const kids = parseInt(kidsInput ? kidsInput.value : "0", 10) || 0;
        const totalGuests = adults + kids;

        // When user explicitly clicks "Check Availability", reset dismissed state
        if (userInitiated) {
            isRecommendationDismissed = false;
        }

        // For small groups (<= 4 guests), standard single chalets fit everyone; hide group drawer
        if (totalGuests <= 4) {
            recPanel.style.display = 'none';
            if (restoreBtn) restoreBtn.style.display = 'none';
            clearMapSpotlightAndDimming();
            return;
        }

        // If user explicitly dismissed the suggestions previously for this search
        if (isRecommendationDismissed && !userInitiated) {
            recPanel.style.display = 'none';
            if (restoreBtn) restoreBtn.style.display = 'inline-flex';
            clearMapSpotlightAndDimming();
            return;
        }

        // Filter available stay spots for selected stay dates
        const availableStaySpots = spotsData.filter(s => {
            const isStay = (!emptyOrZero(s.is_stay) || s.category === 'stays');
            if (!isStay) return false;
            if (currentAvailabilityData && currentAvailabilityData.spots_status) {
                const spStat = currentAvailabilityData.spots_status[s.id];
                if (spStat && !spStat.available) return false;
            }
            return true;
        });

        // Helper to resolve spot object with linked room data
        function enrichSpot(s) {
            if (!s) return null;
            const r = findRoomBySlug(s.linked_room_slug);
            const isDuplex = s.structure_type === 'duplex_hut' || (r && r.structure_type === 'duplex_hut');
            return {
                id: parseInt(s.id, 10),
                spot_number: parseInt(s.spot_number || s.id, 10),
                title: s.title,
                slug: s.linked_room_slug || (r ? r.slug : 'treehouse'),
                linked_room_slug: s.linked_room_slug || (r ? r.slug : 'treehouse'),
                structure_type: isDuplex ? 'duplex_hut' : 'single_hut',
                base_guests: isDuplex ? 4 : (r ? parseInt(r.base_guests, 10) : 2),
                max_guests: isDuplex ? 8 : (r ? parseInt(r.max_guests, 10) : (s.slug === 'grand-wooden-alpine-house' ? 5 : 4)),
                room_rate: parseFloat(s.room_rate || s.stay_price || (r ? r.rate_per_night : 14500)),
                image_url: s.image_url || (r ? r.image_url : 'assets/images/treehouse_exterior_front.jpg')
            };
        }

        const enrichedAvailable = availableStaySpots.map(enrichSpot).filter(Boolean);

        // Build potential combinations
        const potentialCombos = [];

        // Helper to compile a combo
        function makeCombo(title, tag, desc, spotNumbers) {
            const matchedChalets = spotNumbers.map(num => enrichedAvailable.find(es => parseInt(es.spot_number, 10) === parseInt(num, 10) || parseInt(es.id, 10) === parseInt(num, 10))).filter(Boolean);
            if (matchedChalets.length < spotNumbers.length) {
                return null;
            }
            const totalBase = matchedChalets.reduce((acc, c) => acc + c.base_guests, 0);
            const totalMax = matchedChalets.reduce((acc, c) => acc + c.max_guests, 0);
            const totalRate = matchedChalets.reduce((acc, c) => acc + c.room_rate, 0);

            // CRITICAL: Ensure combo has enough capacity to house the ENTIRE group (Adults + Children)
            if (totalMax < totalGuests) {
                return null;
            }

            return {
                title: title,
                tag: tag,
                description: desc,
                chalets: matchedChalets,
                spotIds: matchedChalets.map(c => c.id),
                baseCapacity: totalBase,
                maxCapacity: totalMax,
                totalBaseRate: totalRate
            };
        }

        // Bracket 1: Large Parties (25 to 32+ guests, e.g. 27 Guests: 12 Adults + 15 Children)
        if (totalGuests >= 25) {
            // Option 1: 3 Duplex Residences + Grand Alpine Timber House (Cap: 8*3 + 5 = 29 Guests)
            let combo1 = makeCombo(
                '3 Duplex Residences + Grand Alpine Timber House',
                'Upper Ridge & Peak Meadow • Accommodates ' + totalGuests + ' Guests',
                '6 Master Bedroom Suites + Grand Alpine Timber House • Accommodates up to 29 guests with scenic connecting trails and panoramic mist balconies',
                [2, 3, 4, 10]
            ) || makeCombo(
                '3 Duplex Residences + Grand Alpine Timber House',
                'North Ridge & Meadow • Accommodates ' + totalGuests + ' Guests',
                '6 Master Bedroom Suites + Grand Alpine House • Private valley walkways and cloud view balconies',
                [3, 4, 5, 10]
            ) || makeCombo(
                '3 Duplex Residences + Grand Alpine Timber House',
                'Peak Ridge Cluster • Accommodates ' + totalGuests + ' Guests',
                '6 Master Bedroom Suites + Grand Alpine House • Top elevation forest retreat',
                [4, 5, 6, 10]
            );
            if (combo1) potentialCombos.push(combo1);

            // Option 2: 2 Duplex Residences + 3 Standalone Woodhouse Cottages & Mudhouse (Cap: 8*2 + 4*3 = 28 Guests)
            let combo2 = makeCombo(
                '2 Duplex Residences + 3 Standalone Cottages & Mudhouse',
                'Estate Ridge & Orchard Slope • Accommodates ' + totalGuests + ' Guests',
                '2 Duplexes (4 Suites) + 2 Standalone Woodhouse Cottages + 1 Earthen Cob Mudhouse with cozy fireplaces',
                [2, 3, 8, 7, 9]
            ) || makeCombo(
                '2 Duplex Residences + 3 Standalone Cottages & Mudhouse',
                'North Ridge & Orchard Slope • Accommodates ' + totalGuests + ' Guests',
                '4 Master Suites + 3 Private Standalone Cottages with stone hearths and forest lawn access',
                [4, 5, 8, 7, 9]
            );
            if (combo2) potentialCombos.push(combo2);

            // Option 3: 4 Adjoining Duplex Chalet Residences (Cap: 8*4 = 32 Guests)
            let combo3 = makeCombo(
                '4 Adjoining Duplex Chalet Residences',
                'Ridge Cloudscape Collection • Accommodates ' + totalGuests + ' Guests',
                '8 Master Bedroom Suites across 4 adjoining duplex chalets along the mist-clad upper ridge',
                [2, 3, 4, 5]
            ) || makeCombo(
                '4 Adjoining Duplex Chalet Residences',
                'North-Peak Ridge Collection • Accommodates ' + totalGuests + ' Guests',
                '8 Master Bedroom Suites across 4 adjoining duplex residences on the upper ridge',
                [3, 4, 5, 6]
            );
            if (combo3) potentialCombos.push(combo3);

        } else if (totalGuests >= 17) {
            // Bracket 2: Medium-Large Parties (17 to 24 guests)
            let combo1 = makeCombo(
                '3 Adjoining Duplex Chalet Residences',
                'Upper Ridge • Fits ' + totalGuests + ' Guests',
                '6 Master Bedroom Suites across 3 contiguous duplexes with panoramic valley decks (Capacity: 24)',
                [2, 3, 4]
            ) || makeCombo(
                '3 North Ridge Duplex Residences',
                'North Ridge • Fits ' + totalGuests + ' Guests',
                '6 Master Bedroom Suites along the high scenic trail (Capacity: 24)',
                [4, 5, 6]
            );
            if (combo1) potentialCombos.push(combo1);

            let combo2 = makeCombo(
                '2 Duplex Suites + 2 Standalone Woodhouse Cottages',
                'Ridge & Orchard Slope • Fits ' + totalGuests + ' Guests',
                '4 Master Suites + 2 Standalone Cottages with fireplaces and orchard views (Capacity: 24)',
                [2, 3, 8, 7]
            ) || makeCombo(
                '2 Duplex Suites + 2 Standalone Woodhouse Cottages',
                'North Ridge & Orchard • Fits ' + totalGuests + ' Guests',
                '4 Master Suites + 2 Standalone Cottages (Capacity: 24)',
                [4, 5, 8, 7]
            );
            if (combo2) potentialCombos.push(combo2);

            let combo3 = makeCombo(
                'Grand Alpine House + 2 Duplex Chalet Residences',
                'Peak Heritage & Ridge • Fits ' + totalGuests + ' Guests',
                'Grand alpine vaulted hall + 4 duplex suites accommodating up to 25 guests',
                [10, 2, 3]
            ) || makeCombo(
                'Grand Alpine House + Duplex Suite + 2 Cottages',
                'Heritage Meadow & Orchard • Fits ' + totalGuests + ' Guests',
                'Grand alpine house + 1 duplex + 2 standalone cottages (Capacity: 25)',
                [10, 2, 8, 7]
            );
            if (combo3) potentialCombos.push(combo3);

        } else if (totalGuests >= 9) {
            // Bracket 3: Medium Groups (9 to 16 guests)
            let comboDuplex = makeCombo(
                '2 Adjoining Duplex Chalet Residences',
                'Adjacent on Upper Ridge • Most Popular',
                '4 Master Bedroom Suites • 30-Second Scenic Walkway • Panoramic Mist Balconies (Capacity: 16)',
                [2, 3]
            ) || makeCombo(
                '2 North Ridge Duplex Suites',
                'Adjacent on North Ridge • Alpine Cloudscapes',
                '4 Master Bedrooms • Walking Path Proximity • Private Lounge Decks (Capacity: 16)',
                [4, 5]
            ) || makeCombo(
                '2 Peak Duplex Chalet Suites',
                'Adjacent on Peak Contour',
                '4 Master Bedrooms • High Altitude Cloud Views (Capacity: 16)',
                [5, 6]
            );
            if (comboDuplex) potentialCombos.push(comboDuplex);

            let comboBlend = makeCombo(
                '1 Duplex Suite + 2 Standalone Cottages',
                'Cluster Blend • Orchard Slope Proximity',
                '1 Duplex Residence (2 Suites) + 2 Standalone Woodhouse Cottages with Fireplaces (Capacity: 16)',
                [2, 8, 7]
            ) || makeCombo(
                '1 Duplex Suite + 2 Standalone Cottages',
                'Cluster Blend • Orchard Slope Proximity',
                '1 Duplex Residence (2 Suites) + 2 Standalone Woodhouse Cottages with Fireplaces (Capacity: 16)',
                [4, 8, 7]
            ) || makeCombo(
                '1 Duplex Suite + Pine Cottage & Mudhouse',
                'Cluster Blend • Heritage Meadow',
                '1 Duplex Residence + 1 Standalone Woodhouse + 1 Earthen Cob Mudhouse (Capacity: 16)',
                [2, 8, 9]
            );
            if (comboBlend) potentialCombos.push(comboBlend);

            let comboHeritage = makeCombo(
                'Grand Alpine House + Duplex Suite + Mudhouse',
                'Architectural Heritage Collection',
                'Vaulted pine living hall + 2 luxury duplex suites + thermal cob clay mudhouse (Capacity: 17)',
                [10, 4, 9]
            ) || makeCombo(
                'Grand Alpine House + Duplex Suite + Pine Cottage',
                'Mountain Meadow Collection',
                'Grand wooden alpine house + luxury duplex chalet + orchard pine cottage (Capacity: 17)',
                [10, 2, 8]
            ) || makeCombo(
                'Grand Alpine House + 2 Standalone Cottages',
                'Heritage Meadow Cluster',
                'Grand wooden alpine house + 2 private standalone cottages (Capacity: 17)',
                [10, 9, 8]
            );
            if (comboHeritage) potentialCombos.push(comboHeritage);

        } else {
            // Bracket 4: Groups of 5 to 8 guests
            let combo1 = makeCombo(
                'Duplex Chalet Residence (Entire Suite)',
                'Upper Ridge • 2 Private Suites',
                'Complete 2-bedroom duplex chalet accommodating up to 8 guests',
                [2]
            ) || makeCombo(
                'North Ridge Duplex Suite',
                'Alpine Cloudscape Duplex',
                'Two master bedrooms with private lounge decks',
                [4]
            );
            if (combo1) potentialCombos.push(combo1);

            let combo2 = makeCombo(
                '2 Standalone Pine Cottages',
                'Adjacent Orchard Slope',
                'Pine Cottage Hut 02 + Pine Cottage Hut 03 side-by-side with private fireplaces (Capacity: 8)',
                [8, 7]
            );
            if (combo2) potentialCombos.push(combo2);

            let combo3 = makeCombo(
                'Grand Alpine House + Heritage Mudhouse',
                'Heritage Meadow Cluster',
                'Pinewood vaulted hall + earthen cob mudhouse suite (Accommodates up to 9 guests)',
                [10, 9]
            );
            if (combo3) potentialCombos.push(combo3);
        }

        // Dynamic Greedy Combinatorial Fallback (if preset combos not found or less than 2)
        if (potentialCombos.length < 2 && enrichedAvailable.length >= 2) {
            // Strategy 1: Greedy Duplex First
            const sortedDuplexFirst = [...enrichedAvailable].sort((a, b) => {
                if (a.structure_type === 'duplex_hut' && b.structure_type !== 'duplex_hut') return -1;
                if (b.structure_type === 'duplex_hut' && a.structure_type !== 'duplex_hut') return 1;
                return b.max_guests - a.max_guests;
            });
            let cap1 = 0, spots1 = [];
            for (const sp of sortedDuplexFirst) {
                spots1.push(sp);
                cap1 += sp.max_guests;
                if (cap1 >= totalGuests) break;
            }
            if (spots1.length >= 2 && !potentialCombos.some(p => p.spotIds.slice().sort().join(',') === spots1.map(s => s.id).sort().join(','))) {
                potentialCombos.push({
                    title: `Curated High-Capacity Estate Cluster (${spots1.length} Chalets)`,
                    tag: `Fits Party of ${totalGuests} Guests`,
                    description: `Combined accommodation for ${totalGuests} guests (${adults} Adults, ${kids} Children) with ${spots1.reduce((a, b) => a + b.base_guests, 0)} base guests included`,
                    chalets: spots1,
                    spotIds: spots1.map(s => s.id),
                    baseCapacity: spots1.reduce((a, b) => a + b.base_guests, 0),
                    maxCapacity: cap1,
                    totalBaseRate: spots1.reduce((a, b) => a + b.room_rate, 0)
                });
            }

            // Strategy 2: Proximity Sequential Clustering
            const sortedBySpot = [...enrichedAvailable].sort((a, b) => a.spot_number - b.spot_number);
            let cap2 = 0, spots2 = [];
            for (const sp of sortedBySpot) {
                spots2.push(sp);
                cap2 += sp.max_guests;
                if (cap2 >= totalGuests) break;
            }
            if (spots2.length >= 2 && !potentialCombos.some(p => p.spotIds.slice().sort().join(',') === spots2.map(s => s.id).sort().join(','))) {
                potentialCombos.push({
                    title: `Adjacent Walking Path Chalets (${spots2.length} Chalets)`,
                    tag: `Contiguous Estate Walkway • Fits ${totalGuests} Guests`,
                    description: `Contiguous chalets along the stone trail accommodating up to ${cap2} guests with private balconies and mountain views`,
                    chalets: spots2,
                    spotIds: spots2.map(s => s.id),
                    baseCapacity: spots2.reduce((a, b) => a + b.base_guests, 0),
                    maxCapacity: cap2,
                    totalBaseRate: spots2.reduce((a, b) => a + b.room_rate, 0)
                });
            }
        }

        if (potentialCombos.length === 0) {
            recPanel.style.display = 'none';
            if (restoreBtn) restoreBtn.style.display = 'none';
            clearMapSpotlightAndDimming();
            return;
        }

        currentRecommendations = potentialCombos;
        activeRecommendationIdx = 0;

        // Render Panel Content
        if (subtitleTxt) {
            subtitleTxt.innerHTML = `To comfortably accommodate your party of <strong>${totalGuests} Guests (${adults} Adults, ${kids} Children)</strong> together, we recommend adjacent clustered chalets on the estate. Non-suggested properties are dimmed on the map.`;
        }

        if (optionsGrid) {
            optionsGrid.innerHTML = '';
            currentRecommendations.forEach((combo, idx) => {
                const card = document.createElement('div');
                card.className = `rec-option-card ${idx === 0 ? 'is-active-rec' : ''}`;
                card.setAttribute('data-combo-idx', idx);

                const chipsHtml = combo.chalets.map(c => {
                    const isDup = c.structure_type === 'duplex_hut';
                    return `<span class="rec-chalet-chip ${isDup ? 'duplex-chip' : ''}">
                        ${isDup ? '🏰' : '🏡'} Spot ${String(c.spot_number).padStart(2, '0')}: ${c.title}
                    </span>`;
                }).join('');

                card.innerHTML = `
                    <div>
                        <div class="rec-card-top-tag">
                            <i class="fa-solid fa-sparkles"></i> Option ${idx + 1} • ${combo.tag}
                        </div>
                        <h4 class="rec-card-title">${combo.title}</h4>
                        <div class="rec-card-chips">${chipsHtml}</div>
                        <p style="font-size: 11.5px; color: #94A3B8; margin: 0 0 10px; line-height: 1.4;">${combo.description}</p>
                    </div>
                    <div class="rec-card-meta">
                        <div class="rec-card-cap">
                            Capacity: <strong>Up to ${combo.maxCapacity} Guests</strong>
                            <div style="font-size: 10.5px; color: #64748B;">Base ${combo.baseCapacity} Guests Included</div>
                        </div>
                        <div class="rec-card-price">
                            <span class="rec-card-price-num">₹${combo.totalBaseRate.toLocaleString('en-IN')}</span>
                            <span class="rec-card-price-period">per night (All Meals Incl.)</span>
                        </div>
                    </div>
                `;

                card.addEventListener('click', () => {
                    document.querySelectorAll('.rec-option-card').forEach(c => c.classList.remove('is-active-rec'));
                    card.classList.add('is-active-rec');
                    activeRecommendationIdx = idx;
                    updateActiveRecommendationBar();
                    applySpotlightForCombo(combo);
                });

                optionsGrid.appendChild(card);
            });
        }

        function updateActiveRecommendationBar() {
            const active = currentRecommendations[activeRecommendationIdx];
            if (!active) return;
            if (activeTitleEl) activeTitleEl.innerText = `Option ${activeRecommendationIdx + 1}: ${active.title}`;
            if (activeMetaEl) activeMetaEl.innerText = `${active.maxCapacity} Guests Max • ${active.chalets.length} Chalets • ${active.tag}`;
            if (bookBtnTxt) bookBtnTxt.innerText = `Book Recommended Combination (${active.chalets.length} Chalets)`;
        }

        updateActiveRecommendationBar();

        // Apply spotlight on map for the first recommended combination
        applySpotlightForCombo(currentRecommendations[0]);

        // Show panel
        recPanel.style.display = 'block';
        if (restoreBtn) restoreBtn.style.display = 'none';

        if (userInitiated) {
            recPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Spotlights the suggested combination and dims all other nodes
    function applySpotlightForCombo(combo) {
        if (!combo || !combo.spotIds) return;

        nodes.forEach(node => {
            const spotId = parseInt(node.getAttribute('data-spot-id'), 10);
            const isMatch = combo.spotIds.includes(spotId);

            if (isMatch) {
                node.classList.add('node-spotlight');
                node.classList.remove('node-dimmed-by-recommendation');
                node.style.opacity = '1';
                node.style.pointerEvents = 'auto';

                let recPill = node.querySelector('.spotlight-rec-pill');
                if (!recPill) {
                    recPill = document.createElement('span');
                    recPill.className = 'spotlight-rec-pill';
                    recPill.innerHTML = '<i class="fa-solid fa-star"></i> Suggested Group Stay';
                    node.appendChild(recPill);
                }
            } else {
                node.classList.remove('node-spotlight');
                node.classList.add('node-dimmed-by-recommendation');
                node.style.opacity = '0.08';
                node.style.pointerEvents = 'none';

                const recPill = node.querySelector('.spotlight-rec-pill');
                if (recPill) recPill.remove();
            }
        });

        // Also spotlight in grid view if user switches to grid
        document.querySelectorAll('.chalet-card').forEach(card => {
            const cardSlug = card.getAttribute('data-villa-slug');
            const isMatch = combo.chalets.some(c => c.slug === cardSlug);
            if (isMatch) {
                card.style.borderColor = '#4ADE80';
                card.style.boxShadow = '0 0 16px rgba(74, 222, 128, 0.4)';
                card.style.opacity = '1';
            } else {
                card.style.borderColor = 'rgba(28, 56, 38, 0.15)';
                card.style.boxShadow = 'none';
                card.style.opacity = '0.35';
            }
        });

        // Set sidebar to inspect the first chalet of the combination
        if (combo.spotIds.length > 0) {
            selectChalet(combo.spotIds[0], false);
        }
    }

    // Clears spotlight and restores full visibility across all chalets
    function clearMapSpotlightAndDimming() {
        nodes.forEach(node => {
            node.classList.remove('node-spotlight', 'node-dimmed-by-recommendation');
            node.style.opacity = '1';
            node.style.pointerEvents = 'auto';
            const recPill = node.querySelector('.spotlight-rec-pill');
            if (recPill) recPill.remove();
        });

        document.querySelectorAll('.chalet-card').forEach(card => {
            card.style.borderColor = '';
            card.style.boxShadow = '';
            card.style.opacity = '1';
        });
    }

    // Dismiss suggestion button listener
    const btnRecDismiss = document.getElementById('btn-rec-dismiss-toggle');
    if (btnRecDismiss) {
        btnRecDismiss.addEventListener('click', () => {
            isRecommendationDismissed = true;
            const recPanel = document.getElementById('bms-group-recommendation-panel');
            const restoreBtn = document.getElementById('btn-restore-recommendation');
            if (recPanel) recPanel.style.display = 'none';
            if (restoreBtn) restoreBtn.style.display = 'inline-flex';
            clearMapSpotlightAndDimming();
        });
    }

    // Restore suggestion button listener
    const btnRestoreRec = document.getElementById('btn-restore-recommendation');
    if (btnRestoreRec) {
        btnRestoreRec.addEventListener('click', () => {
            isRecommendationDismissed = false;
            const recPanel = document.getElementById('bms-group-recommendation-panel');
            if (recPanel) recPanel.style.display = 'block';
            btnRestoreRec.style.display = 'none';
            if (currentRecommendations.length > 0) {
                applySpotlightForCombo(currentRecommendations[activeRecommendationIdx]);
            }
        });
    }

    // Book Recommended Combination Action CTA listener
    const btnRecBookActive = document.getElementById('btn-rec-book-active');
    if (btnRecBookActive) {
        btnRecBookActive.addEventListener('click', () => {
            const activeCombo = currentRecommendations[activeRecommendationIdx];
            if (!activeCombo) return;
            triggerBookingModalWithMultiChalet(activeCombo);
        });
    }

    // Multi-Chalet Group Reservation Modal Trigger
    function triggerBookingModalWithMultiChalet(chaletCombo) {
        const modal = document.getElementById('booking-modal');
        if (!modal) return;

        // Sync Dates
        const modalCin = document.getElementById('modal-checkin');
        if (modalCin && checkinInput && checkinInput.value) {
            modalCin.value = checkinInput.value;
            modalCin.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const modalCout = document.getElementById('modal-checkout');
        if (modalCout && checkoutInput && checkoutInput.value) {
            modalCout.value = checkoutInput.value;
            modalCout.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Sync Adults & Kids
        const modalAdults = document.getElementById('modal-adults');
        if (modalAdults && adultsInput && adultsInput.value) {
            modalAdults.value = adultsInput.value;
        }

        const modalKids = document.getElementById('modal-kids');
        if (modalKids && kidsInput && kidsInput.value) {
            modalKids.value = kidsInput.value;
        }

        // Populate and activate Multi-Chalet mode in modal
        if (typeof window.setModalMultiChaletStay === 'function') {
            window.setModalMultiChaletStay(chaletCombo);
        }

        // Open Modal
        if (typeof window.resetBookingModalState === 'function') {
            window.resetBookingModalState();
        }
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        const container = modal.querySelector('.booking-modal-content');
        if (container) container.scrollTop = 0;
    }

    // 10. Date & Guest Inputs change
    if (checkinInput) {
        checkinInput.addEventListener('change', () => {
            if (checkoutInput && checkinInput.value >= checkoutInput.value) {
                const nextDay = new Date(checkinInput.value);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutInput.value = nextDay.toISOString().split('T')[0];
            }
            recalculateSidebarPricing();
            fetchLiveAvailabilityForDates(true);
        });
    }

    if (checkoutInput) {
        checkoutInput.addEventListener('change', () => {
            recalculateSidebarPricing();
            fetchLiveAvailabilityForDates(true);
        });
    }

    document.querySelectorAll('.ctrl-step-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            let val = parseInt(input.value || "1", 10);
            const min = parseInt(input.min || "0", 10);
            const max = parseInt(input.max || "30", 10);

            if (btn.classList.contains('btn-plus')) {
                if (val < max) val++;
            } else if (btn.classList.contains('btn-minus')) {
                if (val > min) val--;
            }
            input.value = val;
            recalculateSidebarPricing();
            evaluateGroupRecommendations(false);
        });
    });

    // 10.1 Dedicated "Check Availability" CTA Button Trigger
    const btnCheckLiveAvail = document.getElementById('btn-check-live-availability');
    if (btnCheckLiveAvail) {
        btnCheckLiveAvail.addEventListener('click', async (e) => {
            e.preventDefault();

            // Date validation
            if (!checkinInput || !checkoutInput) return;
            const cin = checkinInput.value;
            const cout = checkoutInput.value;

            if (!cin || !cout) {
                alert('Please select both Check-In and Check-Out dates.');
                return;
            }

            const d1 = new Date(cin);
            const d2 = new Date(cout);
            if (d2 <= d1) {
                alert('Check-Out date must be at least 1 day after Check-In date.');
                return;
            }

            // Visual feedback on button
            btnCheckLiveAvail.classList.add('is-loading');
            const originalHTML = btnCheckLiveAvail.innerHTML;
            btnCheckLiveAvail.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Checking...</span>';

            // Recalculate sidebar pricing for updated dates and guests
            recalculateSidebarPricing();

            // Add subtle refresh pulse to content below (map or grid)
            const currentLayout = (viewBtnGrid && viewBtnGrid.classList.contains('active')) ? gridLayout : mapLayout;
            if (currentLayout) {
                currentLayout.classList.remove('refreshed-pulse');
                void currentLayout.offsetWidth; // Trigger reflow
                currentLayout.classList.add('refreshed-pulse');
            }

            // Trigger live availability API query
            await fetchLiveAvailabilityForDates(true);
            evaluateGroupRecommendations(true);

            // Button success state
            btnCheckLiveAvail.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #4ADE80;"></i> <span>Updated</span>';

            setTimeout(() => {
                btnCheckLiveAvail.classList.remove('is-loading');
                btnCheckLiveAvail.innerHTML = originalHTML;
            }, 1200);
        });
    }

    // 11. Filter Chips Handling
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = (btn.getAttribute('data-stay-filter') || 'all').toLowerCase().trim();
            let firstVisibleSpotId = null;

            nodes.forEach(node => {
                const isStay = node.getAttribute('data-is-stay') === '1';
                const stayCat = (node.getAttribute('data-stay-cat') || '').toLowerCase().trim();
                const struct = (node.getAttribute('data-structure') || '').toLowerCase().trim();

                let match = false;
                if (filter === 'all') match = true;
                else if (filter === 'duplex') match = (struct === 'duplex_hut' && isStay);
                else if (filter === 'single') match = (struct === 'single_hut' && isStay);
                else match = (stayCat === filter && isStay);

                if (match) {
                    node.style.display = 'block';
                    node.style.opacity = '1';
                    if (!firstVisibleSpotId && isStay) {
                        firstVisibleSpotId = node.getAttribute('data-spot-id');
                    }
                } else {
                    node.style.opacity = '0.15';
                }
            });

            // Filter Grid Cards as well
            document.querySelectorAll('.chalet-card').forEach(card => {
                const cardCat = (card.getAttribute('data-stay-cat') || '').toLowerCase().trim();
                const cardStruct = (card.getAttribute('data-structure') || '').toLowerCase().trim();
                let match = false;
                if (filter === 'all') match = true;
                else if (filter === 'duplex') match = (cardStruct === 'duplex_hut');
                else if (filter === 'single') match = (cardStruct === 'single_hut');
                else match = (cardCat === filter);

                card.style.display = match ? 'flex' : 'none';
            });

            if (firstVisibleSpotId) {
                selectChalet(firstVisibleSpotId, false);
            }
        });
    });

    // 11.1 Interactive Map Legend Filter Buttons Handling
    const legendFilterBtns = document.querySelectorAll('.legend-filter-btn');
    legendFilterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            legendFilterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-legend-filter');
            let firstVisibleSpotId = null;

            nodes.forEach(node => {
                const spotId = node.getAttribute('data-spot-id');
                const isStay = node.getAttribute('data-is-stay') === '1';
                const isDuplex = node.getAttribute('data-is-duplex') === '1';
                const struct = node.getAttribute('data-structure') || '';
                const nodeStatus = node.getAttribute('data-status') || 'available';

                let match = false;
                if (filter === 'all') {
                    match = true;
                } else if (filter === 'available') {
                    match = (isStay && nodeStatus !== 'booked');
                } else if (filter === 'booked') {
                    match = (isStay && nodeStatus === 'booked');
                } else if (filter === 'single') {
                    match = (isStay && !isDuplex && (struct === 'single_hut' || struct === 'single'));
                } else if (filter === 'duplex') {
                    match = (isStay && (isDuplex || struct === 'duplex_hut'));
                } else if (filter === 'facilities') {
                    match = (!isStay);
                }

                if (match) {
                    node.style.display = 'block';
                    node.style.opacity = '1';
                    if (!firstVisibleSpotId) {
                        firstVisibleSpotId = spotId;
                    }
                } else {
                    node.style.opacity = '0.12';
                }
            });

            // Filter Grid Cards if applicable
            document.querySelectorAll('.chalet-card').forEach(card => {
                const cardStruct = card.getAttribute('data-structure');
                const isBooked = card.classList.contains('is-booked-card');

                let match = false;
                if (filter === 'all') match = true;
                else if (filter === 'available') match = !isBooked;
                else if (filter === 'booked') match = isBooked;
                else if (filter === 'single') match = (cardStruct === 'single_hut');
                else if (filter === 'duplex') match = (cardStruct === 'duplex_hut');
                else if (filter === 'facilities') match = false;
                else match = true;

                card.style.display = match ? 'flex' : 'none';
            });

            if (firstVisibleSpotId) {
                selectChalet(firstVisibleSpotId, false);
            }
        });
    });

    // 12. View Switcher (Map View vs Grid View)
    if (viewBtnMap && viewBtnGrid && mapLayout && gridLayout) {
        viewBtnMap.addEventListener('click', () => {
            viewBtnMap.classList.add('active');
            viewBtnGrid.classList.remove('active');
            mapLayout.style.display = 'grid';
            gridLayout.style.display = 'none';
        });

        viewBtnGrid.addEventListener('click', () => {
            viewBtnGrid.classList.add('active');
            viewBtnMap.classList.remove('active');
            mapLayout.style.display = 'none';
            gridLayout.style.display = 'block';
        });
    }

    // 13. Grid Card "Book Chalet" Buttons
    document.querySelectorAll('.select-from-grid-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const card = btn.closest('.chalet-card');
            if (card && card.classList.contains('is-booked-card')) {
                return;
            }
            const slug = btn.getAttribute('data-slug');
            let targetSpot = spotsData.find(s => s.linked_room_slug === slug);
            if (!targetSpot) {
                targetSpot = spotsData.find(s => s.title && slug && s.title.toLowerCase().includes(slug.toLowerCase().replace(/-/g, ' ')));
            }
            if (targetSpot) {
                selectChalet(targetSpot.id, false);
            }
            triggerBookingModalWithSelectedStay(slug);
        });
    });

    // 14. "Proceed to Reserve" Button -> Triggers Modal with pre-filled state
    function triggerBookingModalWithSelectedStay(forcedSlug) {
        if (!currentSpot && !forcedSlug) return;
        const isStay = forcedSlug || (currentSpot && (!emptyOrZero(currentSpot.is_stay) || currentSpot.category === 'stays'));
        if (!isStay) {
            // Guard: non-stay facilities cannot be booked via room checkout, open info modal instead
            if (currentSpot && typeof openFacilityInfoModal === 'function') {
                openFacilityInfoModal(currentSpot);
            }
            return;
        }

        const roomSlug = forcedSlug || (currentSpot ? (currentSpot.linked_room_slug || 'mudhouse-stay') : 'mudhouse-stay');
        const modal = document.getElementById('booking-modal');
        if (!modal) return;

        // Check availability before triggering if spot exists
        if (currentSpot && currentAvailabilityData && currentAvailabilityData.spots_status) {
            const spStatus = currentAvailabilityData.spots_status[currentSpot.id];
            if (spStatus && !spStatus.available) {
                if (typeof showRealtimeConflictAlert === 'function') {
                    showRealtimeConflictAlert(
                        `${currentSpot.title} Already Reserved`,
                        spStatus.message || `We apologize, but this property has already been reserved for your selected stay dates. Please select alternative dates.`
                    );
                }
                return;
            }
        }

        // Sync Modal fields
        const modalVilla = document.getElementById('modal-villa');
        if (modalVilla) {
            let matched = false;
            for (let i = 0; i < modalVilla.options.length; i++) {
                if (modalVilla.options[i].value === roomSlug) {
                    modalVilla.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched && modalVilla.options.length > 0) {
                modalVilla.selectedIndex = 0;
            }
            // Trigger change event to sync prices
            const ev = new Event('change', { bubbles: true });
            modalVilla.dispatchEvent(ev);
        }

        // Sync Duplex tier if applicable
        const modalTierRadio = document.querySelector(`input[name="modal_tier"][value="${currentTier}"]`);
        if (modalTierRadio) {
            modalTierRadio.checked = true;
            modalTierRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Sync Duplex suite wing if applicable
        const modalWingRadio = document.querySelector(`input[name="modal_duplex_wing"][value="${currentDuplexUnit}"]`);
        if (modalWingRadio) {
            modalWingRadio.checked = true;
            modalWingRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const modalCin = document.getElementById('modal-checkin');
        if (modalCin && checkinInput && checkinInput.value) {
            modalCin.value = checkinInput.value;
            modalCin.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const modalCout = document.getElementById('modal-checkout');
        if (modalCout && checkoutInput && checkoutInput.value) {
            modalCout.value = checkoutInput.value;
            modalCout.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const modalAdults = document.getElementById('modal-adults');
        if (modalAdults && adultsInput && adultsInput.value) {
            modalAdults.value = adultsInput.value;
        }

        const modalKids = document.getElementById('modal-kids');
        if (modalKids && kidsInput && kidsInput.value) {
            modalKids.value = kidsInput.value;
        }

        // Hide raw dropdown wrapper so selected cottage card is cleanly showcased
        const selectWrapper = document.getElementById('modal-villa-select-wrapper');
        if (selectWrapper) selectWrapper.style.display = 'none';

        if (typeof onModalVillaChange === 'function') {
            onModalVillaChange(false);
        }

        // Open Modal
        if (typeof window.resetBookingModalState === 'function') {
            window.resetBookingModalState();
        }
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        // Scroll modal into view smoothly
        const container = modal.querySelector('.booking-modal-content');
        if (container) container.scrollTop = 0;
    }

    if (btnSacOpenCheckout) {
        btnSacOpenCheckout.addEventListener('click', () => triggerBookingModalWithSelectedStay());
    }

    if (btnSacCheckInfo) {
        btnSacCheckInfo.addEventListener('click', () => {
            if (currentSpot && typeof openFacilityInfoModal === 'function') {
                openFacilityInfoModal(currentSpot);
            } else if (typeof openAmenitiesGuide === 'function') {
                openAmenitiesGuide();
            }
        });
    }

    // Photo Gallery Carousel Controls
    if (sacGalPrev) {
        sacGalPrev.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            currentPhotoIdx--;
            updateSidebarPhoto();
        });
    }
    if (sacGalNext) {
        sacGalNext.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            currentPhotoIdx++;
            updateSidebarPhoto();
        });
    }

    // 15. Initial Selection and Live Availability Fetch on Page Load
    let initialSpotId = null;
    if (preselectSlug) {
        const found = spotsData.find(s => s.linked_room_slug === preselectSlug);
        if (found) initialSpotId = found.id;
    }

    if (!initialSpotId) {
        const firstStay = spotsData.find(s => (!emptyOrZero(s.is_stay) || s.category === 'stays'));
        if (firstStay) initialSpotId = firstStay.id;
        else if (spotsData.length > 0) initialSpotId = spotsData[0].id;
    }

    if (initialSpotId) {
        selectChalet(initialSpotId, false);
    }

    // Initial Live Availability Fetch
    fetchLiveAvailabilityForDates(false);
});
