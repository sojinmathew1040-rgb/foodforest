// =========================================================================
// Food Forest Sanctuary — Interactive Estate Booking Controller (BookMyShow Style)
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

    // Sidebar Inspector Elements
    const sacTypePill = document.getElementById('sac-type-pill');
    const sacAvailPill = document.getElementById('sac-avail-pill');
    const sacMainImg = document.getElementById('sac-main-img');
    const sacGalPrev = document.getElementById('sac-gal-prev');
    const sacGalNext = document.getElementById('sac-gal-next');
    const sacGalIndicator = document.getElementById('sac-gal-indicator');
    const sacTitle = document.getElementById('sac-title');
    const sacDesc = document.getElementById('sac-desc');
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

    // 1. Helper to find room by slug
    function findRoomBySlug(slug) {
        return roomsData.find(r => r.slug === slug) || null;
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
    function selectChalet(spotId) {
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

        if (sacAvailPill) {
            sacAvailPill.innerHTML = isStay ? '<i class="fa-solid fa-circle" style="color: #27ae60;"></i> Available' : '<i class="fa-solid fa-sparkles" style="color: #56c2c9;"></i> Open for Guests';
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
    }

    function emptyOrZero(val) {
        return !val || val === '0' || val === 0;
    }

    // 6. Node Click Handlers
    nodes.forEach(node => {
        node.addEventListener('click', () => {
            const spotId = node.getAttribute('data-spot-id');
            selectChalet(spotId);

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

    // 9. Date & Guest Inputs change
    if (checkinInput) checkinInput.addEventListener('change', () => {
        if (checkoutInput && checkinInput.value >= checkoutInput.value) {
            const nextDay = new Date(checkinInput.value);
            nextDay.setDate(nextDay.getDate() + 1);
            checkoutInput.value = nextDay.toISOString().split('T')[0];
        }
        recalculateSidebarPricing();
    });

    if (checkoutInput) checkoutInput.addEventListener('change', recalculateSidebarPricing);

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

    // 10. Filter Chips Handling
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
                selectChalet(firstVisibleSpotId);
            }
        });
    });

    // 11. View Switcher (Map View vs Grid View)
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

    // 12. Grid Card "Book Chalet" Buttons
    document.querySelectorAll('.select-from-grid-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const slug = btn.getAttribute('data-slug');
            const targetSpot = spotsData.find(s => s.linked_room_slug === slug);
            if (targetSpot) {
                selectChalet(targetSpot.id);
                triggerBookingModalWithSelectedStay();
            }
        });
    });

    // 13. "Proceed to Reserve" Button -> Triggers Modal with pre-filled state
    function triggerBookingModalWithSelectedStay() {
        if (!currentSpot) return;

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

    // 14. Initial Selection on Page Load
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
        selectChalet(initialSpotId);
    }
});
