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

    const nodes = Array.from(document.querySelectorAll('.bms-chalet-node'));
    nodes.forEach(node => {
        const topVal = parseFloat(node.style.top || "50");
        const leftVal = parseFloat(node.style.left || "50");
        if (topVal < 36) node.classList.add('pos-bottom');
        if (leftVal < 22) node.classList.add('pos-left');
        else if (leftVal > 78) node.classList.add('pos-right');
    });

    // State
    let currentSpot = null;
    let currentRoom = null;
    let currentPhotoIdx = 0;
    let currentPhotos = [];
    let currentTier = 'full'; // 'full' or 'single_room'
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

    // 4. Recalculate and render pricing
    function recalculateSidebarPricing() {
        if (!currentSpot) return;

        const nights = getNights();
        const adults = parseInt(adultsInput ? adultsInput.value : "2", 10) || 2;
        const kids = parseInt(kidsInput ? kidsInput.value : "0", 10) || 0;

        let ratePerNight = parseFloat(currentSpot.room_rate || currentSpot.stay_price || 14500);
        let baseGuests = currentRoom ? parseInt(currentRoom.base_guests || 2, 10) : 2;
        let extraAdultRate = currentRoom ? parseFloat(currentRoom.extra_guest_rate || 1500) : 1500;
        let extraChildRate = currentRoom ? parseFloat(currentRoom.extra_child_rate || 800) : 800;

        const isDuplex = (currentSpot.structure_type === 'duplex_hut' || (currentRoom && currentRoom.structure_type === 'duplex_hut'));

        if (isDuplex && currentTier === 'single_room') {
            ratePerNight = parseFloat(currentSpot.single_room_rate || currentRoom.single_room_rate || 14500);
            baseGuests = 2;
        }

        // Occupancy calculations
        const adultsInBase = Math.min(adults, baseGuests);
        const extraAdults = Math.max(0, adults - adultsInBase);
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

        // Setup photos
        currentPhotos = [];
        if (spot.photos_list && spot.photos_list.length > 0) {
            currentPhotos = spot.photos_list;
        } else if (spot.image_url) {
            currentPhotos = [spot.image_url];
        } else {
            currentPhotos = ['assets/images/01 (25).jpeg'];
        }
        currentPhotoIdx = 0;
        updateSidebarPhoto();

        // Update titles & descriptions
        if (sacTitle) sacTitle.innerText = spot.title;
        if (sacDesc) sacDesc.innerText = spot.description;

        if (sacTypePill) {
            sacTypePill.innerHTML = isStay ? '<i class="fa-solid fa-house-chimney"></i> BOOKABLE CHALET' : '<i class="fa-solid fa-water"></i> ESTATE FACILITY';
        }

        // Check availability status from latest fetched data
        let spotAvail = true;
        let spotMessage = '';
        if (isStay && currentAvailabilityData && currentAvailabilityData.spots_status) {
            const spStatus = currentAvailabilityData.spots_status[spot.id];
            if (spStatus) {
                spotAvail = spStatus.available;
                spotMessage = spStatus.message;
            }
        }

        if (sacAvailPill) {
            if (!isStay) {
                sacAvailPill.innerHTML = '<i class="fa-solid fa-sparkles" style="color: #56c2c9;"></i> Open for Guests';
                sacAvailPill.className = 'sac-avail-pill font-sans';
            } else if (spotAvail) {
                sacAvailPill.innerHTML = '<i class="fa-solid fa-circle-check"></i> Available for Selected Dates';
                sacAvailPill.className = 'sac-avail-pill sac-available font-sans';
            } else {
                sacAvailPill.innerHTML = '<i class="fa-solid fa-ban"></i> Reserved for Selected Dates';
                sacAvailPill.className = 'sac-avail-pill sac-booked font-sans';
            }
        }

        // Booked Warning in Sidebar
        if (sacBookedWarning) {
            if (isStay && !spotAvail) {
                sacBookedWarning.style.display = 'flex';
                if (sacBookedWarningMsg) {
                    sacBookedWarningMsg.innerText = spotMessage || `We apologize, but ${spot.title} has already been reserved for your selected stay dates. Please choose alternative dates or pick another available chalet on the map.`;
                }
            } else {
                sacBookedWarning.style.display = 'none';
            }
        }

        // Proceed to reserve button state
        if (btnSacOpenCheckout) {
            if (isStay && !spotAvail) {
                btnSacOpenCheckout.disabled = true;
                btnSacOpenCheckout.classList.add('btn-disabled-booked');
                btnSacOpenCheckout.innerHTML = '<i class="fa-solid fa-calendar-xmark"></i> <span>Chalet Reserved for Selected Dates</span>';
            } else {
                btnSacOpenCheckout.disabled = false;
                btnSacOpenCheckout.classList.remove('btn-disabled-booked');
                btnSacOpenCheckout.innerHTML = '<span>Proceed to Reserve</span> <i class="fa-solid fa-arrow-right"></i>';
            }
        }

        // Duplex Tier Controls
        const isDuplex = (spot.structure_type === 'duplex_hut' || (currentRoom && currentRoom.structure_type === 'duplex_hut'));
        if (sacTierBox) {
            if (isDuplex && isStay) {
                sacTierBox.style.display = 'block';
                const fullRate = parseFloat(spot.room_rate || 24000);
                const singleRate = parseFloat(spot.single_room_rate || 14500);
                if (tocRateFull) tocRateFull.innerText = `₹${fullRate.toLocaleString('en-IN')}/nt`;
                if (tocRateSingle) tocRateSingle.innerText = `₹${singleRate.toLocaleString('en-IN')}/nt`;
            } else {
                sacTierBox.style.display = 'none';
                currentTier = 'full';
            }
        }

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
            recalculateSidebarPricing();
        });
    });

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

                if (spotStatus && !spotStatus.available) {
                    // Marked as Booked
                    node.classList.remove('status-available', 'status-fast_filling');
                    node.classList.add('status-booked');
                    node.setAttribute('data-status', 'booked');

                    if (statusDot) {
                        statusDot.className = 'node-status-dot status-dot-booked';
                    }

                    if (!bookedPill && nodeBox) {
                        bookedPill = document.createElement('span');
                        bookedPill.className = 'node-booked-pill';
                        bookedPill.innerHTML = '<i class="fa-solid fa-lock"></i> BOOKED';
                        nodeBox.appendChild(bookedPill);
                    }

                    if (hoverStatus) {
                        hoverStatus.className = 'nhc-status status-label-booked';
                        hoverStatus.innerHTML = '<i class="fa-solid fa-ban"></i> Reserved for Dates';
                    }
                    if (hoverCta) {
                        hoverCta.innerHTML = 'Click to View Alternate Dates &rarr;';
                    }
                } else {
                    // Marked as Available
                    node.classList.remove('status-booked');
                    node.classList.add('status-available');
                    node.setAttribute('data-status', 'available');

                    if (statusDot) {
                        statusDot.className = 'node-status-dot status-dot-available';
                    }

                    if (bookedPill) {
                        bookedPill.remove();
                    }

                    if (hoverStatus) {
                        hoverStatus.className = 'nhc-status status-label-available';
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
                if (roomMatch && !roomMatch.available) {
                    card.classList.add('is-booked-card');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa-solid fa-ban"></i> Reserved for Dates';
                    }
                } else {
                    card.classList.remove('is-booked-card');
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

        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Booking live availability error:', err);
            }
        }
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
            const max = parseInt(input.max || "10", 10);

            if (btn.classList.contains('btn-plus')) {
                if (val < max) val++;
            } else if (btn.classList.contains('btn-minus')) {
                if (val > min) val--;
            }
            input.value = val;
            recalculateSidebarPricing();
        });
    });

    // 11. Filter Chips Handling
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-stay-filter');
            let firstVisibleSpotId = null;

            nodes.forEach(node => {
                const isStay = node.getAttribute('data-is-stay') === '1';
                const stayCat = node.getAttribute('data-stay-cat') || '';
                const struct = node.getAttribute('data-structure') || '';

                let match = false;
                if (filter === 'all') match = true;
                else if (filter === 'treehouse') match = (stayCat === 'treehouse' && isStay);
                else if (filter === 'mudhouse') match = (stayCat === 'mudhouse' && isStay);
                else if (filter === 'woodhouse') match = (stayCat === 'woodhouse' && isStay);
                else if (filter === 'duplex') match = (struct === 'duplex_hut' && isStay);
                else if (filter === 'single') match = (struct === 'single_hut' && isStay);

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
                const cardCat = card.getAttribute('data-stay-cat');
                const cardStruct = card.getAttribute('data-structure');
                let match = false;
                if (filter === 'all') match = true;
                else if (filter === 'treehouse') match = (cardCat === 'treehouse');
                else if (filter === 'mudhouse') match = (cardCat === 'mudhouse');
                else if (filter === 'woodhouse') match = (cardCat === 'woodhouse');
                else if (filter === 'duplex') match = (cardStruct === 'duplex_hut');
                else if (filter === 'single') match = (cardStruct === 'single_hut');
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
        btn.addEventListener('click', () => {
            const card = btn.closest('.chalet-card');
            if (card && card.classList.contains('is-booked-card')) {
                return;
            }
            const slug = btn.getAttribute('data-slug');
            const targetSpot = spotsData.find(s => s.linked_room_slug === slug);
            if (targetSpot) {
                selectChalet(targetSpot.id, false);
                triggerBookingModalWithSelectedStay();
            }
        });
    });

    // 14. "Proceed to Reserve" Button -> Triggers Modal with pre-filled state
    function triggerBookingModalWithSelectedStay() {
        if (!currentSpot) return;

        // Check availability before triggering
        if (currentAvailabilityData && currentAvailabilityData.spots_status) {
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

        const roomSlug = currentSpot.linked_room_slug || 'treehouse';
        const modal = document.getElementById('booking-modal');
        if (!modal) return;

        // Sync Modal fields
        const modalVilla = document.getElementById('modal-villa');
        if (modalVilla) {
            modalVilla.value = roomSlug;
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

        const modalCin = document.getElementById('modal-checkin');
        if (modalCin && checkinInput) {
            modalCin.value = checkinInput.value;
            modalCin.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const modalCout = document.getElementById('modal-checkout');
        if (modalCout && checkoutInput) {
            modalCout.value = checkoutInput.value;
            modalCout.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const modalAdults = document.getElementById('modal-adults');
        if (modalAdults && adultsInput) {
            modalAdults.value = adultsInput.value;
        }

        const modalKids = document.getElementById('modal-kids');
        if (modalKids && kidsInput) {
            modalKids.value = kidsInput.value;
        }

        // Open Modal
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        // Scroll modal into view smoothly
        const container = modal.querySelector('.booking-modal-container');
        if (container) container.scrollTop = 0;
    }

    if (btnSacOpenCheckout) {
        btnSacOpenCheckout.addEventListener('click', triggerBookingModalWithSelectedStay);
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
