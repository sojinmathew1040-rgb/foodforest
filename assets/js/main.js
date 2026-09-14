/* -------------------------------------------------------------
   Food Forest — Ultra-Luxury Interactive Javascript
   (GSAP, Lenis, Web Audio Nature Soundscape, Booking Concierge)
   ------------------------------------------------------------- */

document.addEventListener("DOMContentLoaded", () => {
    // Register ScrollTrigger plugin with GSAP
    if (window.gsap && window.ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
    }

    // -------------------------------------------------------------
    // 1. Lenis Smooth Scrolling
    // -------------------------------------------------------------
    let lenis = null;
    if (typeof Lenis !== 'undefined') {
        lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            direction: 'vertical',
            gestureDirection: 'vertical',
            smooth: true,
            mouseMultiplier: 1,
            smoothTouch: false,
            touchMultiplier: 2,
        });

        if (window.ScrollTrigger) {
            lenis.on('scroll', ScrollTrigger.update);
            gsap.ticker.add((time) => {
                lenis.raf(time * 1000);
            });
            gsap.ticker.lagSmoothing(0);
        }
    }

    // -------------------------------------------------------------
    // 2. Character Split Text Engine
    // -------------------------------------------------------------
    function initSplitText() {
        document.querySelectorAll('.split-text').forEach(el => {
            if (el.getAttribute('data-split-done')) return;
            el.setAttribute('data-split-done', 'true');
            
            const rawText = el.innerText;
            el.innerHTML = '';
            
            const words = rawText.trim().split(/\s+/);
            words.forEach((word, wordIdx) => {
                const wordSpan = document.createElement('span');
                wordSpan.style.display = 'inline-block';
                wordSpan.style.whiteSpace = 'nowrap';
                wordSpan.style.overflow = 'hidden';
                wordSpan.style.verticalAlign = 'top';
                
                word.split('').forEach(char => {
                    const span = document.createElement('span');
                    span.className = 'char';
                    span.style.display = 'inline-block';
                    span.innerText = char;
                    wordSpan.appendChild(span);
                });
                
                el.appendChild(wordSpan);
                if (wordIdx < words.length - 1) {
                    el.appendChild(document.createTextNode(' '));
                }
            });
        });
    }
    initSplitText();

    // -------------------------------------------------------------
    // 3. Scroll Reveal & Hero Animation
    // -------------------------------------------------------------
    function initScrollAnimations() {
        const heroBgImg = document.querySelector('.hero-bg-img');
        if (heroBgImg) {
            if (heroBgImg.complete) {
                heroBgImg.classList.add('loaded');
            } else {
                heroBgImg.addEventListener('load', () => heroBgImg.classList.add('loaded'));
            }
        }

        // Scroll reveals for cards and text blocks
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));

    function handleScrollEffects(scrolled) {
        const heroBg = document.querySelector('.hero-bg-container');
        if (heroBg && scrolled < window.innerHeight) {
            heroBg.style.transform = `translateY(${scrolled * 0.35}px) scale(1.05)`;
        }

        // Header state change
        const header = document.getElementById('site-header');
        if (header) {
            if (scrolled > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }

        // Sticky booking pill visibility
        const stickyPill = document.getElementById('sticky-booking-pill');
        if (stickyPill) {
            if (scrolled > 450) {
                stickyPill.classList.add('visible');
            } else {
                stickyPill.classList.remove('visible');
            }
        }
    }

    // Connect with Lenis scroll
    if (lenis) {
        lenis.on('scroll', (e) => {
            const scrollPos = typeof e.scroll !== 'undefined' ? e.scroll : window.scrollY;
            handleScrollEffects(scrollPos);
        });
    }

    // Parallax & scroll effects listener fallback
    window.addEventListener('scroll', () => {
        handleScrollEffects(window.scrollY);
    });
}
initScrollAnimations();

    // -------------------------------------------------------------
    // 4. Ambient Nature Soundscape (Web Audio API)
    // -------------------------------------------------------------
    let audioCtx = null;
    let isSoundPlaying = false;
    let noiseNode = null;
    let birdInterval = null;
    let gainNode = null;

    function initNatureAudio() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioContext();

            // Create gentle stream/wind pink noise
            const bufferSize = audioCtx.sampleRate * 2;
            const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
            const output = buffer.getChannelData(0);
            let b0 = 0, b1 = 0, b2 = 0, b3 = 0, b4 = 0, b5 = 0, b6 = 0;
            for (let i = 0; i < bufferSize; i++) {
                const white = Math.random() * 2 - 1;
                b0 = 0.99886 * b0 + white * 0.0555179;
                b1 = 0.99332 * b1 + white * 0.0750759;
                b2 = 0.96900 * b2 + white * 0.1538520;
                b3 = 0.86650 * b3 + white * 0.3104856;
                b4 = 0.55000 * b4 + white * 0.5329522;
                b5 = -0.7616 * b5 - white * 0.0168980;
                output[i] = (b0 + b1 + b2 + b3 + b4 + b5 + b6 + white * 0.5362) * 0.04;
                b6 = white * 0.115926;
            }

            noiseNode = audioCtx.createBufferSource();
            noiseNode.buffer = buffer;
            noiseNode.loop = true;

            // Low-pass filter for soft mountain breeze and water rustle
            const filter = audioCtx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(380, audioCtx.currentTime);

            gainNode = audioCtx.createGain();
            gainNode.gain.setValueAtTime(0.001, audioCtx.currentTime);

            noiseNode.connect(filter);
            filter.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            noiseNode.start();

            // Subtle occasional high-altitude bird call synthesis
            function playBirdCall() {
                if (!isSoundPlaying || !audioCtx) return;
                try {
                    const osc = audioCtx.createOscillator();
                    const birdGain = audioCtx.createGain();
                    osc.type = 'sine';
                    const baseFreq = 2200 + Math.random() * 800;
                    osc.frequency.setValueAtTime(baseFreq, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(baseFreq + 600, audioCtx.currentTime + 0.08);
                    osc.frequency.exponentialRampToValueAtTime(baseFreq - 300, audioCtx.currentTime + 0.18);

                    birdGain.gain.setValueAtTime(0.001, audioCtx.currentTime);
                    birdGain.gain.linearRampToValueAtTime(0.025, audioCtx.currentTime + 0.05);
                    birdGain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.25);

                    osc.connect(birdGain);
                    birdGain.connect(audioCtx.destination);

                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.3);
                } catch (e) {}
            }

            birdInterval = setInterval(() => {
                if (isSoundPlaying && Math.random() > 0.4) {
                    playBirdCall();
                }
            }, 4500);

        } catch (e) {
            console.warn("Web Audio not supported or blocked", e);
        }
    }

    const audioToggleBtn = document.getElementById("ambient-audio-toggle");
    const soundStatusLabel = document.getElementById("sound-status-label");

    if (audioToggleBtn) {
        audioToggleBtn.addEventListener("click", () => {
            if (!audioCtx) {
                initNatureAudio();
            }

            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            isSoundPlaying = !isSoundPlaying;

            if (isSoundPlaying) {
                audioToggleBtn.classList.add("playing");
                if (soundStatusLabel) soundStatusLabel.innerText = "ON";
                if (gainNode && audioCtx) {
                    gainNode.gain.cancelScheduledValues(audioCtx.currentTime);
                    gainNode.gain.linearRampToValueAtTime(0.2, audioCtx.currentTime + 1.5);
                }
            } else {
                audioToggleBtn.classList.remove("playing");
                if (soundStatusLabel) soundStatusLabel.innerText = "OFF";
                if (gainNode && audioCtx) {
                    gainNode.gain.cancelScheduledValues(audioCtx.currentTime);
                    gainNode.gain.linearRampToValueAtTime(0.001, audioCtx.currentTime + 0.8);
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 5. Booking Dates Setup & Synchronization
    // -------------------------------------------------------------
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dayAfter = new Date(tomorrow);
    dayAfter.setDate(dayAfter.getDate() + 2);

    function formatDate(d) {
        return d.toISOString().split('T')[0];
    }

    const heroCheckin = document.getElementById('hero-checkin');
    const heroCheckout = document.getElementById('hero-checkout');
    const modalCheckin = document.getElementById('modal-checkin');
    const modalCheckout = document.getElementById('modal-checkout');

    if (heroCheckin && heroCheckout) {
        heroCheckin.value = formatDate(tomorrow);
        heroCheckin.min = formatDate(today);
        heroCheckout.value = formatDate(dayAfter);
        heroCheckout.min = formatDate(tomorrow);

        heroCheckin.addEventListener('change', () => {
            const nextDay = new Date(heroCheckin.value);
            nextDay.setDate(nextDay.getDate() + 1);
            heroCheckout.min = formatDate(nextDay);
            if (new Date(heroCheckout.value) <= new Date(heroCheckin.value)) {
                heroCheckout.value = formatDate(nextDay);
            }
            if (modalCheckin) modalCheckin.value = heroCheckin.value;
            if (modalCheckout) modalCheckout.value = heroCheckout.value;
            recalculateBookingSummary();
        });

        heroCheckout.addEventListener('change', () => {
            if (modalCheckout) modalCheckout.value = heroCheckout.value;
            recalculateBookingSummary();
        });
    }

    if (modalCheckin && modalCheckout) {
        modalCheckin.value = formatDate(tomorrow);
        modalCheckin.min = formatDate(today);
        modalCheckout.value = formatDate(dayAfter);
        modalCheckout.min = formatDate(tomorrow);

        modalCheckin.addEventListener('change', () => {
            const nextDay = new Date(modalCheckin.value);
            nextDay.setDate(nextDay.getDate() + 1);
            modalCheckout.min = formatDate(nextDay);
            if (new Date(modalCheckout.value) <= new Date(modalCheckin.value)) {
                modalCheckout.value = formatDate(nextDay);
            }
            if (heroCheckin) heroCheckin.value = modalCheckin.value;
            if (heroCheckout) heroCheckout.value = modalCheckout.value;
            recalculateBookingSummary();
        });

        modalCheckout.addEventListener('change', () => {
            if (heroCheckout) heroCheckout.value = modalCheckout.value;
            recalculateBookingSummary();
        });
    }

    // -------------------------------------------------------------
    // 6. Interactive Booking Concierge Modal Logic
    // -------------------------------------------------------------
    const bookingModal = document.getElementById('booking-modal');
    const modalCloseBtn = document.getElementById('booking-modal-close');
    const modalBackdrop = document.querySelector('.booking-modal-backdrop');
    const modalVillaSelect = document.getElementById('modal-villa');
    const modalGuestsSelect = document.getElementById('modal-guests');

    function openBookingModal(preferredVilla) {
        if (!bookingModal) return;
        
        if (preferredVilla && modalVillaSelect) {
            modalVillaSelect.value = preferredVilla;
        } else if (document.getElementById('hero-villa') && modalVillaSelect) {
            modalVillaSelect.value = document.getElementById('hero-villa').value;
        }

        if (document.getElementById('hero-guests') && modalGuestsSelect) {
            modalGuestsSelect.value = document.getElementById('hero-guests').value;
        }

        recalculateBookingSummary();
        bookingModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (lenis) lenis.stop();
    }

    function closeBookingModal() {
        if (!bookingModal) return;
        bookingModal.classList.remove('active');
        document.body.style.overflow = '';
        if (lenis) lenis.start();
    }

    // Allow native scrolling and wheel propagation within modal container
    const modalScrollContainer = document.querySelector('.booking-modal-container');
    if (modalScrollContainer) {
        modalScrollContainer.addEventListener('wheel', (e) => {
            e.stopPropagation();
        }, { passive: true });
        modalScrollContainer.addEventListener('touchmove', (e) => {
            e.stopPropagation();
        }, { passive: true });
    }

    // Bind open modal buttons
    document.querySelectorAll('.open-booking-modal-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const villa = btn.getAttribute('data-villa');
            openBookingModal(villa);
        });
    });

    const heroCheckAvailabilityBtn = document.getElementById('btn-hero-check-availability');
    if (heroCheckAvailabilityBtn) {
        heroCheckAvailabilityBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const villa = document.getElementById('hero-villa') ? document.getElementById('hero-villa').value : 'treehouse';
            openBookingModal(villa);
        });
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeBookingModal();
        });
    }
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeBookingModal);

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && bookingModal && bookingModal.classList.contains('active')) {
            closeBookingModal();
        }
    });

    // Price Calculation
    function recalculateBookingSummary() {
        if (!modalCheckin || !modalCheckout || !modalVillaSelect) return;

        const checkinDate = new Date(modalCheckin.value);
        const checkoutDate = new Date(modalCheckout.value);
        let nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        if (isNaN(nights) || nights < 1) nights = 1;

        const selectedOption = modalVillaSelect.options[modalVillaSelect.selectedIndex];
        const villaPrice = parseInt(selectedOption.getAttribute('data-price') || "14500", 10);
        const villaName = selectedOption.getAttribute('data-name') || "Luxury Canopy Treehouse";

        // Addons total
        let addonsTotal = 0;
        document.querySelectorAll('.addon-checkbox:checked').forEach(addon => {
            addonsTotal += parseInt(addon.getAttribute('data-price') || "0", 10);
        });

        const stayTotal = (villaPrice * nights) + addonsTotal;

        // Update summary elements
        const summaryNights = document.getElementById('summary-nights');
        const summaryVillaRate = document.getElementById('summary-villa-rate');
        const summaryAddonsLine = document.getElementById('summary-addons-line');
        const summaryAddonsRate = document.getElementById('summary-addons-rate');
        const summaryTotal = document.getElementById('summary-total');

        if (summaryNights) summaryNights.innerText = `${nights} ${nights === 1 ? 'Night' : 'Nights'}`;
        if (summaryVillaRate) summaryVillaRate.innerText = `₹${(villaPrice * nights).toLocaleString('en-IN')}`;

        if (summaryAddonsLine && summaryAddonsRate) {
            if (addonsTotal > 0) {
                summaryAddonsLine.style.display = 'flex';
                summaryAddonsRate.innerText = `+₹${addonsTotal.toLocaleString('en-IN')}`;
            } else {
                summaryAddonsLine.style.display = 'none';
            }
        }

        if (summaryTotal) summaryTotal.innerText = `₹${stayTotal.toLocaleString('en-IN')}`;
    }

    if (modalVillaSelect) modalVillaSelect.addEventListener('change', recalculateBookingSummary);
    document.querySelectorAll('.addon-checkbox').forEach(cb => {
        cb.addEventListener('change', recalculateBookingSummary);
    });

    // -------------------------------------------------------------
    // 7. Instant WhatsApp & Live Concierge Form Dispatch
    // -------------------------------------------------------------
    async function saveBookingToDatabase(payload) {
        try {
            const res = await fetch('api/book.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            return await res.json();
        } catch (err) {
            console.error('Booking save error:', err);
            return null;
        }
    }

    const whatsappSubmitBtn = document.getElementById('btn-submit-whatsapp');
    if (whatsappSubmitBtn) {
        whatsappSubmitBtn.addEventListener('click', async () => {
            const guestName = document.getElementById('modal-name')?.value.trim() || 'Guest';
            const guestPhone = document.getElementById('modal-phone')?.value.trim() || '';
            const guestEmail = document.getElementById('modal-email')?.value.trim() || '';
            const guestNotes = document.getElementById('modal-notes')?.value.trim() || 'None';

            if (!guestName || !guestPhone) {
                alert('Please enter your full name and WhatsApp contact number before connecting.');
                document.getElementById('modal-name')?.focus();
                return;
            }

            const villaName = modalVillaSelect?.options[modalVillaSelect.selectedIndex]?.text.split('(')[0].trim() || 'Luxury Villa';
            const villaSlug = modalVillaSelect?.value || 'treehouse';
            const guestsCount = modalGuestsSelect?.value || '2';
            const checkin = modalCheckin?.value || '';
            const checkout = modalCheckout?.value || '';
            const total = document.getElementById('summary-total')?.innerText || '₹14,500';

            const addonsList = [];
            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                const card = cb.closest('.addon-card');
                const name = card?.querySelector('.addon-name')?.innerText || 'Experience';
                addonsList.push(name);
            });
            const addonsText = addonsList.length > 0 ? addonsList.join(', ') : 'None';

            // Also record in estate database
            const dbPayload = {
                name: guestName,
                phone: guestPhone,
                email: guestEmail,
                villa: villaSlug,
                guests: guestsCount,
                checkin: checkin,
                checkout: checkout,
                addons: addonsText,
                notes: guestNotes
            };
            const saveRes = await saveBookingToDatabase(dbPayload);
            const refCode = saveRes?.reference_code || 'FF-' + Math.floor(1000 + Math.random() * 9000);

            const message = `🌿 *RESERVATION ENQUIRY — FOOD FOREST KANTHALLOOR* 🌿\n\n` +
                `• *Booking Reference*: #${refCode}\n` +
                `• *Guest Name*: ${guestName}\n` +
                `• *Phone / WhatsApp*: ${guestPhone}\n` +
                `• *Email*: ${guestEmail}\n\n` +
                `• *Sanctuary Stay*: ${villaName}\n` +
                `• *Check-in*: ${checkin}\n` +
                `• *Check-out*: ${checkout}\n` +
                `• *Guests*: ${guestsCount}\n` +
                `• *Add-On Experiences*: ${addonsText}\n` +
                `• *Estimated Total*: ${total} (All Organic Meals Included)\n\n` +
                `• *Special Requests*: ${guestNotes}\n\n` +
                `Kindly confirm availability and reserve our sanctuary stay. Thank you!`;

            const encodedMessage = encodeURIComponent(message);
            const whatsappUrl = `https://wa.me/919234567890?text=${encodedMessage}`;
            window.open(whatsappUrl, '_blank');
        });
    }

    // Email / Form Submit Confirmation
    const luxuryBookingForm = document.getElementById('luxury-booking-form');
    if (luxuryBookingForm) {
        luxuryBookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('btn-submit-email');
            const guestName = document.getElementById('modal-name')?.value.trim();
            const guestPhone = document.getElementById('modal-phone')?.value.trim();
            const guestEmail = document.getElementById('modal-email')?.value.trim();
            const guestNotes = document.getElementById('modal-notes')?.value.trim();
            const villaSlug = modalVillaSelect?.value || 'treehouse';
            const guestsCount = modalGuestsSelect?.value || '2';
            const checkin = modalCheckin?.value;
            const checkout = modalCheckout?.value;

            const addonsList = [];
            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                const card = cb.closest('.addon-card');
                const name = card?.querySelector('.addon-name')?.innerText || 'Experience';
                addonsList.push(name);
            });
            const addonsText = addonsList.length > 0 ? addonsList.join(', ') : 'None';

            if (submitBtn) {
                const originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Securing Reservation...</span>';

                const dbPayload = {
                    name: guestName,
                    phone: guestPhone,
                    email: guestEmail,
                    villa: villaSlug,
                    guests: guestsCount,
                    checkin: checkin,
                    checkout: checkout,
                    addons: addonsText,
                    notes: guestNotes
                };

                const res = await saveBookingToDatabase(dbPayload);

                if (res && res.success) {
                    submitBtn.innerHTML = `<i class="fa-solid fa-check"></i> <span>Confirmed! Ref: ${res.reference_code}</span>`;
                    submitBtn.style.backgroundColor = 'var(--accent-green)';
                    submitBtn.style.color = '#FFFFFF';
                    alert(`✨ Thank you, ${res.guest_name}!\n\nYour reservation request has been registered under Reference Code: ${res.reference_code}.\n\nOur Master Concierge will contact you within 30 minutes to confirm your stay.`);
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                        submitBtn.style.backgroundColor = '';
                        submitBtn.style.color = '';
                        closeBookingModal();
                    }, 2500);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                    alert('Could not submit reservation. Please connect directly via WhatsApp Concierge.');
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 8. Gastronomy Menu Tab Switcher
    // -------------------------------------------------------------
    const gastroTabs = document.querySelectorAll('.gastro-tab-btn');
    const gastroPanes = document.querySelectorAll('.gastro-tab-pane');

    gastroTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            gastroTabs.forEach(t => t.classList.remove('active'));
            gastroPanes.forEach(p => {
                p.style.display = 'none';
                p.classList.remove('active');
            });

            tab.classList.add('active');
            const targetId = tab.getAttribute('data-target');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.style.display = 'block';
                setTimeout(() => targetPane.classList.add('active'), 20);
            }
        });
    });

    // -------------------------------------------------------------
    // 9. Mobile Menu Navigation Toggle
    // -------------------------------------------------------------
    const navToggle = document.querySelector('.mobile-nav-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (navToggle && mobileMenu) {
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
        });

        document.querySelectorAll('.mobile-link').forEach(link => {
            link.addEventListener('click', () => {
                navToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }

    // -------------------------------------------------------------
    // 10. Newsletter Form
    // -------------------------------------------------------------
    const newsletterForm = document.getElementById('sanctuary-newsletter-form');
    const newsletterMsg = document.getElementById('newsletter-msg');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (newsletterMsg) {
                newsletterMsg.style.display = 'block';
                newsletterForm.reset();
                setTimeout(() => {
                    newsletterMsg.style.display = 'none';
                }, 4000);
            }
        });
    }

    // -------------------------------------------------------------
    // 12. Interactive Sanctuary Estate Map Controller
    // -------------------------------------------------------------
    function initSanctuaryMap() {
        const mapBoard = document.getElementById('sanctuary-map-board');
        if (!mapBoard) return;

        let zones = [];
        if (window.sanctuarySpotsData && Array.isArray(window.sanctuarySpotsData) && window.sanctuarySpotsData.length > 0) {
            zones = window.sanctuarySpotsData.map((s, idx) => {
                let photosArr = [];
                if (s.photos_list && Array.isArray(s.photos_list) && s.photos_list.length > 0) {
                    photosArr = s.photos_list;
                } else if (s.image_url) {
                    photosArr = [s.image_url];
                } else {
                    photosArr = ['assets/images/01 (10).jpeg'];
                }
                return {
                    id: parseInt(s.id, 10),
                    spotNum: parseInt(s.spot_number, 10) || (idx + 1),
                    num: "ROUTE SPOT " + String(s.spot_number || (idx + 1)).padStart(2, '0'),
                    title: s.title || '',
                    desc: s.description || '',
                    photos: photosArr
                };
            });
        } else {
            zones = [
                {
                    id: 1,
                    spotNum: 1,
                    num: "ROUTE SPOT 01",
                    title: "Farmhouse Kitchen & Organic Dining",
                    desc: "Central farm hearth serving 100% organic farm-to-table meals harvested daily from our heirloom orchards. Wood-fired open kitchen and mountain view dining.",
                    photos: ['assets/images/01 (10).jpeg', 'assets/images/01 (20).jpeg']
                },
                {
                    id: 2,
                    spotNum: 2,
                    num: "ROUTE SPOT 02",
                    title: "Handcrafted Mudhouse Villa",
                    desc: "Handcrafted cob clay cottages sculpted from native red soil, river sand, and straw. Naturally insulated against chilly nights with a private plantation sit-out.",
                    photos: ['assets/images/mudhouse_exterior.png', 'assets/images/01 (26).jpeg']
                },
                {
                    id: 3,
                    spotNum: 3,
                    num: "ROUTE SPOT 03",
                    title: "High-Altitude Canopy Treehouse",
                    desc: "Elevated living among towering mountain trees. Floor-to-ceiling panoramic glass windows looking out over cascading mist, apple terraces, and sunrise valleys.",
                    photos: ['assets/images/treehouse_exterior.png', 'assets/images/treehouse_curved_window.png']
                }
            ];
        }

        let currentIdx = 0;
        let currentPhotoIdx = 0;
        const pins = document.querySelectorAll('.sanctuary-pin');

        // Inspector DOM Elements
        const insNum = document.getElementById('ins-zone-num');
        const insTitle = document.getElementById('ins-title');
        const insDesc = document.getElementById('ins-desc');
        const insImg = document.getElementById('ins-img');
        const insCurrIdx = document.getElementById('ins-curr-idx');
        const insPrevBtn = document.getElementById('ins-prev-btn');
        const insNextBtn = document.getElementById('ins-next-btn');

        // Photo Gallery Elements
        const photoPrevBtn = document.getElementById('ins-photo-prev-btn');
        const photoNextBtn = document.getElementById('ins-photo-next-btn');
        const photoIndicator = document.getElementById('ins-photo-indicator');
        const photoBadge = document.getElementById('ins-photo-badge');

        function updatePhotoView() {
            const zone = zones[currentIdx];
            if (!zone || !zone.photos || zone.photos.length === 0) return;

            if (currentPhotoIdx >= zone.photos.length) currentPhotoIdx = 0;
            if (currentPhotoIdx < 0) currentPhotoIdx = zone.photos.length - 1;

            const photoSrc = zone.photos[currentPhotoIdx];

            if (insImg) {
                insImg.classList.add('fade');
                setTimeout(() => {
                    insImg.src = photoSrc;
                    insImg.alt = zone.title;
                    insImg.classList.remove('fade');
                }, 140);
            }

            if (photoIndicator) {
                photoIndicator.innerText = `${currentPhotoIdx + 1} / ${zone.photos.length}`;
            }

            // If only 1 photo, hide arrows and badge
            if (zone.photos.length <= 1) {
                if (photoPrevBtn) photoPrevBtn.style.display = 'none';
                if (photoNextBtn) photoNextBtn.style.display = 'none';
                if (photoBadge) photoBadge.style.display = 'none';
            } else {
                if (photoPrevBtn) photoPrevBtn.style.display = 'inline-flex';
                if (photoNextBtn) photoNextBtn.style.display = 'inline-flex';
                if (photoBadge) photoBadge.style.display = 'inline-flex';
            }
        }

        function updateZoneView(index, resetPhoto = true) {
            if (index < 0 || index >= zones.length) return;
            currentIdx = index;
            if (resetPhoto) {
                currentPhotoIdx = 0;
            }
            const zone = zones[index];

            // Animate Pins
            pins.forEach(pin => {
                const zId = parseInt(pin.getAttribute('data-zone'), 10);
                if (zId === zone.id) {
                    pin.classList.add('active');
                } else {
                    pin.classList.remove('active');
                }
            });

            if (insNum) insNum.innerText = zone.num;
            if (insTitle) insTitle.innerText = zone.title;
            if (insDesc) insDesc.innerText = zone.desc;
            if (insCurrIdx) insCurrIdx.innerText = index + 1;

            updatePhotoView();
        }

        // Photo Prev & Next Buttons
        if (photoPrevBtn) {
            photoPrevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const zone = zones[currentIdx];
                if (!zone || !zone.photos || zone.photos.length <= 1) return;
                currentPhotoIdx = (currentPhotoIdx - 1 + zone.photos.length) % zone.photos.length;
                updatePhotoView();
            });
        }

        if (photoNextBtn) {
            photoNextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const zone = zones[currentIdx];
                if (!zone || !zone.photos || zone.photos.length <= 1) return;
                currentPhotoIdx = (currentPhotoIdx + 1) % zone.photos.length;
                updatePhotoView();
            });
        }

        // Pin Click and Hover Events
        pins.forEach(pin => {
            pin.addEventListener('click', () => {
                const zId = parseInt(pin.getAttribute('data-zone'), 10);
                const targetIdx = zones.findIndex(z => z.id === zId);
                if (targetIdx !== -1) {
                    updateZoneView(targetIdx, true);
                }
            });
            pin.addEventListener('mouseenter', () => {
                const zId = parseInt(pin.getAttribute('data-zone'), 10);
                const targetIdx = zones.findIndex(z => z.id === zId);
                if (targetIdx !== -1 && targetIdx !== currentIdx) {
                    updateZoneView(targetIdx, true);
                }
            });
        });

        // Spot Prev & Next Buttons
        if (insPrevBtn) {
            insPrevBtn.addEventListener('click', () => {
                const newIdx = (currentIdx - 1 + zones.length) % zones.length;
                updateZoneView(newIdx, true);
            });
        }
        if (insNextBtn) {
            insNextBtn.addEventListener('click', () => {
                const newIdx = (currentIdx + 1) % zones.length;
                updateZoneView(newIdx, true);
            });
        }

        // Category Filter Buttons
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.getAttribute('data-filter');
                let firstVisibleIdx = -1;

                pins.forEach((pin, pIdx) => {
                    const pinCategory = pin.getAttribute('data-category');
                    if (filter === 'all' || pinCategory === filter) {
                        pin.classList.remove('filtered-out');
                        if (firstVisibleIdx === -1) {
                            firstVisibleIdx = pIdx;
                        }
                    } else {
                        pin.classList.add('filtered-out');
                    }
                });

                // If current zone is now filtered out, switch to first visible
                const currentPin = pins[currentIdx];
                if (currentPin && currentPin.classList.contains('filtered-out') && firstVisibleIdx !== -1) {
                    updateZoneView(firstVisibleIdx);
                }
            });
        });

        // Sync Audio button with Header Sound Player
        // Sync Audio button with Header Sound Player
        if (insSoundBtn) {
            const masterAudioBtn = document.getElementById('ambient-audio-toggle');
            insSoundBtn.addEventListener('click', () => {
                if (masterAudioBtn) {
                    masterAudioBtn.click();
                    const isPlaying = masterAudioBtn.classList.contains('playing');
                    const label = insSoundBtn.querySelector('span');
                    if (label) {
                        label.innerText = isPlaying ? "Mute Nature Audio" : "Sanctuary Audio";
                    }
                }
            });
        }
    }
    initSanctuaryMap();

    // -------------------------------------------------------------
    // 13. Sanctuary Visual Gallery & Interactive Lightbox Controller
    // -------------------------------------------------------------
    function initGallery() {
        const galleryGrid = document.getElementById('gallery-grid');
        const lightbox = document.getElementById('luxury-lightbox');
        if (!galleryGrid || !lightbox) return;

        const cards = Array.from(galleryGrid.querySelectorAll('.gallery-card'));
        const filterBtns = document.querySelectorAll('.gallery-filter-btn');

        // Lightbox Elements
        const lbImg = document.getElementById('lb-active-img');
        const lbTitle = document.getElementById('lb-title');
        const lbCaption = document.getElementById('lb-caption');
        const lbTag = document.getElementById('lb-tag');
        const lbCurrIdx = document.getElementById('lb-curr-index');
        const lbTotalCount = document.getElementById('lb-total-count');
        const lbCloseBtn = document.getElementById('lb-close-btn');
        const lbPrevBtn = document.getElementById('lb-prev-btn');
        const lbNextBtn = document.getElementById('lb-next-btn');
        const lbBackdrop = lightbox.querySelector('.lightbox-backdrop');

        let currentActiveCards = [...cards];
        let currentPhotoIndex = 0;

        function getCardData(card) {
            const img = card.querySelector('.gallery-img');
            return {
                src: img ? img.src : '',
                title: card.getAttribute('data-title') || '',
                caption: card.getAttribute('data-caption') || '',
                tag: card.getAttribute('data-tag') || 'SANCTUARY'
            };
        }

        function updateLightboxContent() {
            const activeCard = currentActiveCards[currentPhotoIndex];
            if (!activeCard) return;
            const data = getCardData(activeCard);

            if (lbImg) {
                lbImg.classList.add('fade');
                setTimeout(() => {
                    lbImg.src = data.src;
                    lbImg.alt = data.title;
                    lbImg.classList.remove('fade');
                }, 140);
            }

            if (lbTitle) lbTitle.innerText = data.title;
            if (lbCaption) lbCaption.innerText = data.caption;
            if (lbTag) lbTag.innerText = data.tag;
            if (lbCurrIdx) lbCurrIdx.innerText = String(currentPhotoIndex + 1).padStart(2, '0');
            if (lbTotalCount) lbTotalCount.innerText = String(currentActiveCards.length).padStart(2, '0');
        }

        function openLightbox(index) {
            if (index < 0 || index >= currentActiveCards.length) return;
            currentPhotoIndex = index;
            updateLightboxContent();
            lightbox.classList.add('active');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function nextPhoto() {
            if (currentActiveCards.length === 0) return;
            currentPhotoIndex = (currentPhotoIndex + 1) % currentActiveCards.length;
            updateLightboxContent();
        }

        function prevPhoto() {
            if (currentActiveCards.length === 0) return;
            currentPhotoIndex = (currentPhotoIndex - 1 + currentActiveCards.length) % currentActiveCards.length;
            updateLightboxContent();
        }

        // Card Click Handlers
        cards.forEach((card) => {
            card.addEventListener('click', () => {
                const idxInActive = currentActiveCards.indexOf(card);
                if (idxInActive !== -1) {
                    openLightbox(idxInActive);
                }
            });
        });

        // Lightbox Navigation Controls
        if (lbCloseBtn) lbCloseBtn.addEventListener('click', closeLightbox);
        if (lbBackdrop) lbBackdrop.addEventListener('click', closeLightbox);
        if (lbNextBtn) lbNextBtn.addEventListener('click', nextPhoto);
        if (lbPrevBtn) lbPrevBtn.addEventListener('click', prevPhoto);

        // Keyboard Navigation
        window.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') nextPhoto();
            if (e.key === 'ArrowLeft') prevPhoto();
        });

        // Category Filter Buttons
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.getAttribute('data-filter');
                currentActiveCards = [];

                cards.forEach(card => {
                    const cardCat = card.getAttribute('data-category');
                    if (filter === 'all' || cardCat === filter) {
                        card.classList.remove('hidden');
                        currentActiveCards.push(card);
                    } else {
                        card.classList.add('hidden');
                    }
                });
            });
        });
    }
    initGallery();

    // -------------------------------------------------------------
    // 15. Guest Stories (Testimonials) Interactive Carousel Controller
    // -------------------------------------------------------------
    function initTestimonialsCarousel() {
        const track = document.getElementById('testimonials-track');
        if (!track) return;

        const btnPrev = document.getElementById('btn-prev-testimonial');
        const btnNext = document.getElementById('btn-next-testimonial');
        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let isPaused = false;
        let autoScrollSpeed = 0.65; // pixels per animation frame
        let animationFrameId = null;

        // Auto-Scroll Loop
        function autoScroll() {
            if (!isPaused && !isDown) {
                if (track.scrollLeft >= track.scrollWidth - track.clientWidth - 1) {
                    track.scrollLeft = 0;
                } else {
                    track.scrollLeft += autoScrollSpeed;
                }
            }
            animationFrameId = requestAnimationFrame(autoScroll);
        }
        animationFrameId = requestAnimationFrame(autoScroll);

        // Pause on Hover
        track.addEventListener('mouseenter', () => { isPaused = true; });
        track.addEventListener('mouseleave', () => {
            if (!isDown) isPaused = false;
        });

        // Touch Interaction (Mobile Hand Scrolling)
        track.addEventListener('touchstart', () => { isPaused = true; }, { passive: true });
        track.addEventListener('touchend', () => {
            setTimeout(() => { isPaused = false; }, 1400);
        }, { passive: true });

        // Mouse Drag to Scroll (Desktop Hand & Mouse)
        track.addEventListener('mousedown', (e) => {
            isDown = true;
            isPaused = true;
            track.classList.add('grabbing');
            startX = e.pageX - track.offsetLeft;
            scrollLeft = track.scrollLeft;
        });

        window.addEventListener('mouseup', () => {
            if (isDown) {
                isDown = false;
                track.classList.remove('grabbing');
                setTimeout(() => { isPaused = false; }, 1600);
            }
        });

        track.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - track.offsetLeft;
            const walk = (x - startX) * 1.5;
            track.scrollLeft = scrollLeft - walk;
        });

        // Prev & Next Buttons
        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                isPaused = true;
                const cardWidth = 404; // 380px card + 24px gap
                track.scrollBy({ left: -cardWidth, behavior: 'smooth' });
                setTimeout(() => { isPaused = false; }, 2500);
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                isPaused = true;
                const cardWidth = 404;
                track.scrollBy({ left: cardWidth, behavior: 'smooth' });
                setTimeout(() => { isPaused = false; }, 2500);
            });
        }

        // Horizontal Mouse Wheel Scroll
        track.addEventListener('wheel', (e) => {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault();
                isPaused = true;
                track.scrollLeft += e.deltaY;
                setTimeout(() => { isPaused = false; }, 1200);
            }
        }, { passive: false });
    }
    initTestimonialsCarousel();

    // -------------------------------------------------------------
    // 16. Magnetic Button Micro-Interactions
    // -------------------------------------------------------------
    const isDesktop = window.innerWidth > 1024;
    if (isDesktop) {
        document.querySelectorAll('.magnetic').forEach(elem => {
            const strength = parseFloat(elem.getAttribute('data-strength')) || 10;
            elem.addEventListener('mousemove', (e) => {
                const rect = elem.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                elem.style.transform = `translate(${x * (strength / 50)}px, ${y * (strength / 50)}px)`;
            });

            elem.addEventListener('mouseleave', () => {
                elem.style.transform = 'translate(0px, 0px)';
                elem.style.transition = 'transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            });

            elem.addEventListener('mouseenter', () => {
                elem.style.transition = 'none';
            });
        });
    }
});
