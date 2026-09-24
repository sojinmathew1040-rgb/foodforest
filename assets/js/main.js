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
    // 2. Character & Word Split Text Engine
    // -------------------------------------------------------------
    function initSplitText() {
        document.querySelectorAll('.split-text').forEach(el => {
            if (el.getAttribute('data-split-done')) return;
            el.setAttribute('data-split-done', 'true');
            
            // Preserve child line-breaks if any
            const rawHTML = el.innerHTML;
            const lines = rawHTML.split(/<br\s*[\/]?>/gi);
            el.innerHTML = '';
            
            lines.forEach((lineText, lineIdx) => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = lineText;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';
                const words = textContent.trim().split(/\s+/);
                
                words.forEach((word, wordIdx) => {
                    if (!word) return;
                    const wordSpan = document.createElement('span');
                    wordSpan.className = 'split-text-word';
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
                
                if (lineIdx < lines.length - 1) {
                    el.appendChild(document.createElement('br'));
                }
            });
        });
    }
    initSplitText();

    // -------------------------------------------------------------
    // 3. Ultra-Luxury GSAP ScrollTrigger Animation System
    // -------------------------------------------------------------
    function initGsapScrollTriggers() {
        const hasGsap = typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined';
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Image loaded state for hero background
        const heroBgImg = document.querySelector('.hero-bg-img');
        if (heroBgImg) {
            if (heroBgImg.complete) {
                heroBgImg.classList.add('loaded');
            } else {
                heroBgImg.addEventListener('load', () => heroBgImg.classList.add('loaded'));
            }
        }

        // Header and Sticky Booking Pill Scroll Watcher
        function handleScrollEffects(scrolled) {
            const headerWrapper = document.getElementById('site-header-wrapper');
            const header = document.getElementById('site-header');
            if (scrolled > 50) {
                if (headerWrapper) headerWrapper.classList.add('scrolled');
                if (header) header.classList.add('scrolled');
            } else {
                if (headerWrapper) headerWrapper.classList.remove('scrolled');
                if (header) header.classList.remove('scrolled');
            }

            const stickyPill = document.getElementById('sticky-booking-pill');
            if (stickyPill) {
                if (scrolled > 450) {
                    stickyPill.classList.add('visible');
                } else {
                    stickyPill.classList.remove('visible');
                }
            }
        }

        if (lenis) {
            lenis.on('scroll', (e) => {
                const scrollPos = typeof e.scroll !== 'undefined' ? e.scroll : window.scrollY;
                handleScrollEffects(scrollPos);
            });
        }
        window.addEventListener('scroll', () => {
            handleScrollEffects(window.scrollY);
        });

        // If GSAP is not loaded, fallback gracefully
        if (!hasGsap) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });
            document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
            return;
        }

        // Add class to body to indicate GSAP animation engine is active
        document.body.classList.add('gsap-active');

        // =========================================================
        // A. HERO SECTION CINEMATIC ENTRANCE & PARALLAX
        // =========================================================
        const heroTimeline = gsap.timeline({ defaults: { ease: "power3.out" } });

        // Hero Background Scale In
        if (heroBgImg) {
            heroTimeline.fromTo(heroBgImg,
                { scale: 1.14, opacity: 0.85 },
                { scale: 1.0, opacity: 1, duration: 1.6, ease: "power2.out" }
            );
        }

        // Hero Eyebrow Pill
        const heroEyebrow = document.querySelector('.hero-eyebrow-pill');
        if (heroEyebrow) {
            heroTimeline.fromTo(heroEyebrow,
                { y: -25, opacity: 0, scale: 0.94 },
                { y: 0, opacity: 1, scale: 1, duration: 0.9 },
                "-=1.1"
            );
        }

        // Hero Headline Split Characters
        const heroChars = document.querySelectorAll('.hero-title .char');
        if (heroChars.length > 0 && !prefersReducedMotion) {
            heroTimeline.fromTo(heroChars,
                { yPercent: 120, opacity: 0, rotateX: -25 },
                { yPercent: 0, opacity: 1, rotateX: 0, stagger: 0.014, duration: 1.0, ease: "power3.out" },
                "-=0.7"
            );
        } else {
            const heroTitle = document.querySelector('.hero-title');
            if (heroTitle) {
                heroTimeline.fromTo(heroTitle,
                    { y: 35, opacity: 0 },
                    { y: 0, opacity: 1, duration: 1.0 },
                    "-=0.7"
                );
            }
        }

        // Hero Description Paragraph
        const heroDesc = document.querySelector('.hero-desc');
        if (heroDesc) {
            heroTimeline.fromTo(heroDesc,
                { y: 25, opacity: 0 },
                { y: 0, opacity: 1, duration: 0.9 },
                "-=0.6"
            );
        }

        // Hero Floating Availability Bar
        const heroBookingBar = document.querySelector('.hero-booking-bar');
        if (heroBookingBar) {
            heroTimeline.fromTo(heroBookingBar,
                { y: 40, opacity: 0, scale: 0.96 },
                { y: 0, opacity: 1, scale: 1, duration: 1.0, ease: "power3.out" },
                "-=0.5"
            );
        }

        // Hero Scroll Indicator
        const heroScrollIndicator = document.querySelector('.hero-scroll-indicator');
        if (heroScrollIndicator) {
            heroTimeline.fromTo(heroScrollIndicator,
                { y: 15, opacity: 0 },
                { y: 0, opacity: 1, duration: 0.8 },
                "-=0.4"
            );
        }

        // Hero Parallax Scrub on Scroll
        if (!prefersReducedMotion) {
            const heroBg = document.querySelector('.hero-bg-container');
            if (heroBg) {
                gsap.to(heroBg, {
                    yPercent: 25,
                    scale: 1.12,
                    ease: "none",
                    scrollTrigger: {
                        trigger: '#hero',
                        start: "top top",
                        end: "bottom top",
                        scrub: true
                    }
                });
            }

            const heroContent = document.querySelector('.hero-content');
            if (heroContent) {
                gsap.to(heroContent, {
                    yPercent: -18,
                    opacity: 0.2,
                    ease: "none",
                    scrollTrigger: {
                        trigger: '#hero',
                        start: "top top",
                        end: "bottom 40%",
                        scrub: true
                    }
                });
            }
        }

        // =========================================================
        // B. UNIVERSAL SECTION HEADERS & SPLIT TEXT CHOREOGRAPHY
        // =========================================================
        const sectionHeaderSelectors = [
            '.welcome-text-side',
            '.experiences-header',
            '.why-intro',
            '.sanctuary-header',
            '.seasons-header',
            '.gallery-header-bar',
            '.testimonials-header-bar',
            '.page-hero-content'
        ];

        sectionHeaderSelectors.forEach(selector => {
            const header = document.querySelector(selector);
            if (!header) return;

            const label = header.querySelector('.section-label');
            const title = header.querySelector('.section-title, .page-hero-title');
            const desc = header.querySelector('.welcome-paragraph, .experiences-desc, .why-intro-desc, .seasons-desc, .testimonials-desc, .page-hero-desc, p');
            const actionBtn = header.querySelector('.btn-luxury-solid, .gallery-header-action, .testimonials-slider-controls');

            const tl = gsap.timeline({
                scrollTrigger: {
                    trigger: header,
                    start: "top 85%",
                    toggleActions: "play none none none"
                }
            });

            if (label) {
                tl.fromTo(label,
                    { opacity: 0, y: 18, letterSpacing: "0.26em" },
                    { opacity: 1, y: 0, letterSpacing: "0.18em", duration: 0.75, ease: "power2.out" }
                );
            }

            if (title) {
                const chars = title.querySelectorAll('.char');
                if (chars.length > 0 && !prefersReducedMotion) {
                    tl.fromTo(chars,
                        { yPercent: 115, opacity: 0, rotateX: -22 },
                        { yPercent: 0, opacity: 1, rotateX: 0, duration: 0.85, stagger: 0.012, ease: "power3.out" },
                        label ? "-=0.45" : 0
                    );
                } else {
                    tl.fromTo(title,
                        { opacity: 0, y: 30 },
                        { opacity: 1, y: 0, duration: 0.85, ease: "power3.out" },
                        label ? "-=0.45" : 0
                    );
                }
            }

            if (desc) {
                tl.fromTo(desc,
                    { opacity: 0, y: 22 },
                    { opacity: 1, y: 0, duration: 0.75, ease: "power2.out" },
                    "-=0.45"
                );
            }

            if (actionBtn) {
                tl.fromTo(actionBtn,
                    { opacity: 0, y: 15, scale: 0.96 },
                    { opacity: 1, y: 0, scale: 1, duration: 0.7, ease: "power2.out" },
                    "-=0.4"
                );
            }
        });

        // =========================================================
        // C. WELCOME & SANCTUARY PHILOSOPHY SECTION (#welcome)
        // =========================================================
        const welcomeDetails = document.querySelector('#welcome .welcome-details');
        if (welcomeDetails) {
            const detailCards = welcomeDetails.querySelectorAll('.detail-card');
            if (detailCards.length > 0) {
                gsap.fromTo(detailCards,
                    { opacity: 0, y: 35, scale: 0.96 },
                    {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.8,
                        stagger: 0.12,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: welcomeDetails,
                            start: "top 84%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        const welcomeFrame = document.querySelector('.welcome-visual-side .luxury-image-frame');
        const welcomeImg = document.querySelector('.welcome-visual-side .welcome-img');
        if (welcomeFrame) {
            gsap.fromTo(welcomeFrame,
                { opacity: 0, scale: 0.93, y: 35 },
                {
                    opacity: 1,
                    scale: 1,
                    y: 0,
                    duration: 1.1,
                    ease: "power3.out",
                    scrollTrigger: {
                        trigger: welcomeFrame,
                        start: "top 80%",
                        toggleActions: "play none none none"
                    }
                }
            );

            const ornaments = welcomeFrame.querySelectorAll('.image-corner-ornament');
            if (ornaments.length > 0) {
                gsap.fromTo(ornaments,
                    { scale: 0, opacity: 0 },
                    {
                        scale: 1,
                        opacity: 1,
                        duration: 0.7,
                        stagger: 0.2,
                        ease: "back.out(2.2)",
                        scrollTrigger: {
                            trigger: welcomeFrame,
                            start: "top 78%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        if (welcomeImg && !prefersReducedMotion) {
            gsap.fromTo(welcomeImg,
                { yPercent: -10, scale: 1.14 },
                {
                    yPercent: 10,
                    scale: 1.04,
                    ease: "none",
                    scrollTrigger: {
                        trigger: '.welcome-visual-side',
                        start: "top bottom",
                        end: "bottom top",
                        scrub: 1
                    }
                }
            );
        }

        // =========================================================
        // D. 3D ROOMS & SIGNATURE STAYS SECTION (#rooms-experience)
        // =========================================================
        const roomsSec = document.getElementById('rooms-experience');
        if (roomsSec) {
            const stayTabs = roomsSec.querySelector('.stay-concept-tabs');
            if (stayTabs) {
                gsap.fromTo(stayTabs,
                    { opacity: 0, y: -25, scale: 0.94 },
                    {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.85,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: roomsSec,
                            start: "top 75%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }

            const tourHeader = roomsSec.querySelector('.treehouse-tour-header');
            if (tourHeader) {
                gsap.fromTo(tourHeader,
                    { opacity: 0, x: -30 },
                    {
                        opacity: 1,
                        x: 0,
                        duration: 0.95,
                        ease: "power3.out",
                        scrollTrigger: {
                            trigger: roomsSec,
                            start: "top 75%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }

            const tourProg = roomsSec.querySelector('.treehouse-tour-progress');
            if (tourProg) {
                gsap.fromTo(tourProg,
                    { opacity: 0, y: 25 },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.85,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: roomsSec,
                            start: "top 75%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }

            const mobileCards = document.querySelectorAll('.mobile-tour-card');
            if (mobileCards.length > 0) {
                gsap.fromTo(mobileCards,
                    { opacity: 0, y: 45, scale: 0.96 },
                    {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.85,
                        stagger: 0.18,
                        ease: "power3.out",
                        scrollTrigger: {
                            trigger: '.tour-mobile-fallback',
                            start: "top 80%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        // =========================================================
        // E. CURATED EXPERIENCES SECTION (#experiences)
        // =========================================================
        const expGrid = document.querySelector('.experiences-grid');
        if (expGrid) {
            const expCards = expGrid.querySelectorAll('.experience-card');
            if (expCards.length > 0) {
                gsap.fromTo(expCards,
                    { opacity: 0, y: 55, scale: 0.95 },
                    {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.9,
                        stagger: 0.14,
                        ease: "power3.out",
                        scrollTrigger: {
                            trigger: expGrid,
                            start: "top 82%",
                            toggleActions: "play none none none"
                        }
                    }
                );

                if (!prefersReducedMotion) {
                    expCards.forEach(card => {
                        const img = card.querySelector('.experience-img');
                        if (img) {
                            gsap.fromTo(img,
                                { yPercent: -8, scale: 1.15 },
                                {
                                    yPercent: 8,
                                    scale: 1.04,
                                    ease: "none",
                                    scrollTrigger: {
                                        trigger: card,
                                        start: "top bottom",
                                        end: "bottom top",
                                        scrub: 1.2
                                    }
                                }
                            );
                        }
                    });
                }
            }
        }

        // =========================================================
        // F. WHY FOOD FOREST & FARMSTAY SECTION (#why-mudhouse)
        // =========================================================
        const whyVisual = document.querySelector('.why-visual-side');
        const whyImg = document.querySelector('.why-visual-side .why-img');
        if (whyVisual) {
            gsap.fromTo(whyVisual,
                { opacity: 0, scale: 0.93, x: -30 },
                {
                    opacity: 1,
                    scale: 1,
                    x: 0,
                    duration: 1.1,
                    ease: "power3.out",
                    scrollTrigger: {
                        trigger: whyVisual,
                        start: "top 80%",
                        toggleActions: "play none none none"
                    }
                }
            );
        }

        if (whyImg && !prefersReducedMotion && whyVisual) {
            gsap.fromTo(whyImg,
                { yPercent: -12, scale: 1.16 },
                {
                    yPercent: 12,
                    scale: 1.05,
                    ease: "none",
                    scrollTrigger: {
                        trigger: whyVisual,
                        start: "top bottom",
                        end: "bottom top",
                        scrub: 1.2
                    }
                }
            );
        }

        const whyFeaturesList = document.querySelector('.why-features-list');
        if (whyFeaturesList) {
            const features = whyFeaturesList.querySelectorAll('.why-feature');
            if (features.length > 0) {
                gsap.fromTo(features,
                    { opacity: 0, x: 35, scale: 0.98 },
                    {
                        opacity: 1,
                        x: 0,
                        scale: 1,
                        duration: 0.85,
                        stagger: 0.13,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: whyFeaturesList,
                            start: "top 82%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        // =========================================================
        // G. SANCTUARY ESTATE MAP SECTION (#sanctuary)
        // =========================================================
        const mapBoard = document.getElementById('sanctuary-map-board');
        if (mapBoard) {
            gsap.fromTo(mapBoard,
                { opacity: 0, y: 45, scale: 0.95, rotateX: 6 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    rotateX: 0,
                    duration: 1.15,
                    ease: "power3.out",
                    scrollTrigger: {
                        trigger: mapBoard,
                        start: "top 82%",
                        toggleActions: "play none none none"
                    }
                }
            );

            const mapPins = mapBoard.querySelectorAll('.sanctuary-pin');
            if (mapPins.length > 0) {
                gsap.fromTo(mapPins,
                    { scale: 0, opacity: 0 },
                    {
                        scale: 1,
                        opacity: 1,
                        duration: 0.75,
                        stagger: 0.08,
                        delay: 0.25,
                        ease: "back.out(2.2)",
                        scrollTrigger: {
                            trigger: mapBoard,
                            start: "top 78%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        // =========================================================
        // H. SEASONS OF KANTHALLOOR SECTION (#seasons)
        // =========================================================
        const seasonsGrid = document.querySelector('.seasons-grid');
        if (seasonsGrid) {
            const seasonCards = seasonsGrid.querySelectorAll('.season-card');
            if (seasonCards.length > 0) {
                gsap.fromTo(seasonCards,
                    { opacity: 0, y: 55, scale: 0.94 },
                    {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.9,
                        stagger: 0.14,
                        ease: "power3.out",
                        scrollTrigger: {
                            trigger: seasonsGrid,
                            start: "top 82%",
                            toggleActions: "play none none none"
                        }
                    }
                );

                if (!prefersReducedMotion) {
                    seasonCards.forEach(card => {
                        const img = card.querySelector('.season-img');
                        if (img) {
                            gsap.fromTo(img,
                                { yPercent: -10, scale: 1.15 },
                                {
                                    yPercent: 10,
                                    scale: 1.05,
                                    ease: "none",
                                    scrollTrigger: {
                                        trigger: card,
                                        start: "top bottom",
                                        end: "bottom top",
                                        scrub: 1.2
                                    }
                                }
                            );
                        }
                    });
                }
            }
        }

        // =========================================================
        // I. VISUAL GALLERY SECTION (#gallery & gallery.php)
        // =========================================================
        const galleryGrid = document.getElementById('gallery-grid');
        if (galleryGrid) {
            const galleryCards = galleryGrid.querySelectorAll('.gallery-card');
            if (galleryCards.length > 0) {
                gsap.fromTo(galleryCards,
                    { opacity: 0, y: 45, scale: 0.94 },
                    {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.85,
                        stagger: {
                            amount: 0.5,
                            from: "start"
                        },
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: galleryGrid,
                            start: "top 85%",
                            toggleActions: "play none none none"
                        }
                    }
                );

                if (!prefersReducedMotion) {
                    galleryCards.forEach(card => {
                        const img = card.querySelector('.gallery-img');
                        if (img) {
                            gsap.fromTo(img,
                                { yPercent: -6, scale: 1.12 },
                                {
                                    yPercent: 6,
                                    scale: 1.02,
                                    ease: "none",
                                    scrollTrigger: {
                                        trigger: card,
                                        start: "top bottom",
                                        end: "bottom top",
                                        scrub: 1.5
                                    }
                                }
                            );
                        }
                    });
                }
            }
        }

        // =========================================================
        // J. TESTIMONIALS SECTION (#testimonials)
        // =========================================================
        const testContainer = document.getElementById('testimonials-carousel-container');
        if (testContainer) {
            const testCards = testContainer.querySelectorAll('.testimonial-card');
            if (testCards.length > 0) {
                gsap.fromTo(testCards,
                    { opacity: 0, y: 45, scale: 0.96 },
                    {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.85,
                        stagger: 0.12,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: testContainer,
                            start: "top 85%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        // =========================================================
        // K. FOOTER ACCOLADES & COLUMNS (#contact)
        // =========================================================
        const footerBadgesBanner = document.querySelector('.footer-badges-banner');
        if (footerBadgesBanner) {
            const footerBadges = footerBadgesBanner.querySelectorAll('.footer-badge-item');
            if (footerBadges.length > 0) {
                gsap.fromTo(footerBadges,
                    { opacity: 0, y: 28 },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.8,
                        stagger: 0.1,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: footerBadgesBanner,
                            start: "top 90%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        const footerGrid = document.querySelector('.footer-grid');
        if (footerGrid) {
            const footerCols = footerGrid.querySelectorAll(':scope > div');
            if (footerCols.length > 0) {
                gsap.fromTo(footerCols,
                    { opacity: 0, y: 35 },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.85,
                        stagger: 0.12,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: footerGrid,
                            start: "top 88%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            }
        }

        // Refresh ScrollTrigger calculations after all page images load
        window.addEventListener('load', () => {
            ScrollTrigger.refresh();
        });

        // Debounced refresh on window resize
        let resizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                ScrollTrigger.refresh();
            }, 250);
        });
    }
    initGsapScrollTriggers();

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
    const modalAdultsInput = document.getElementById('modal-adults');
    const modalKidsInput = document.getElementById('modal-kids');

    // -------------------------------------------------------------
    // Duplex Explainer Modal & Architectural Guide Functions
    // -------------------------------------------------------------
    function openDuplexExplainer() {
        const modal = document.getElementById('duplex-explainer-modal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            const currentModalTier = document.querySelector('input[name="modal_tier"]:checked')?.value || 
                                     document.querySelector('input[name="sac_tier_choice"]:checked')?.value || 'full';
            selectDuplexOption(currentModalTier);
        }
    }
    window.openDuplexExplainer = openDuplexExplainer;

    function closeDuplexExplainer() {
        const modal = document.getElementById('duplex-explainer-modal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('active');
            const bModal = document.getElementById('booking-modal');
            if (!bModal || !bModal.classList.contains('active')) {
                document.body.style.overflow = '';
            }
        }
    }
    window.closeDuplexExplainer = closeDuplexExplainer;

    function selectDuplexOption(tier) {
        const cardSingle = document.getElementById('choice-card-single');
        const cardFull = document.getElementById('choice-card-full');
        const radioSingle = document.getElementById('radio-choice-single');
        const radioFull = document.getElementById('radio-choice-full');

        if (tier === 'single_room') {
            if (cardSingle) cardSingle.classList.add('active-selected');
            if (cardFull) cardFull.classList.remove('active-selected');
            if (radioSingle) radioSingle.checked = true;
        } else {
            if (cardFull) cardFull.classList.add('active-selected');
            if (cardSingle) cardSingle.classList.remove('active-selected');
            if (radioFull) radioFull.checked = true;
        }
    }
    window.selectDuplexOption = selectDuplexOption;

    function applyDuplexChoice(tier) {
        // Update Booking Modal tier radio
        const modalRadio = document.querySelector(`input[name="modal_tier"][value="${tier}"]`);
        if (modalRadio) {
            modalRadio.checked = true;
            modalRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Update Booking Page sidebar radio if present
        const sacRadio = document.querySelector(`input[name="sac_tier_choice"][value="${tier}"]`);
        if (sacRadio) {
            sacRadio.checked = true;
            sacRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }

        closeDuplexExplainer();
    }
    window.applyDuplexChoice = applyDuplexChoice;

    function updateGuestOptions(selectElement, baseGuests, maxGuests, extraRate, preferredVal) {
        if (!selectElement) return;
        const currentVal = parseInt(preferredVal || selectElement.value || "2", 10);
        const targetVal = Math.min(Math.max(1, currentVal), maxGuests);
        
        selectElement.innerHTML = '';
        for (let g = 1; g <= maxGuests; g++) {
            const opt = document.createElement('option');
            opt.value = g;
            if (g <= baseGuests) {
                opt.textContent = `${g} ${g === 1 ? 'Guest' : 'Guests'} (Base Occupancy Included)`;
            } else {
                const extraCount = g - baseGuests;
                const extraPerNight = extraCount * extraRate;
                opt.textContent = `${g} Guests (+${extraCount} Extra @ ₹${extraPerNight.toLocaleString('en-IN')}/nt)`;
            }
            if (g === targetVal) opt.selected = true;
            selectElement.appendChild(opt);
        }
    }

    function updateStepperButtons() {
        const selectedOption = modalVillaSelect ? modalVillaSelect.options[modalVillaSelect.selectedIndex] : null;
        const structureType = selectedOption?.getAttribute('data-structure-type') || 'single_hut';
        const isDuplex = (structureType === 'duplex_hut');
        
        const selectedTierRadio = document.querySelector('input[name="modal_tier"]:checked');
        const modalTier = selectedTierRadio ? selectedTierRadio.value : 'full';

        let maxRoomGuests = parseInt(selectedOption?.getAttribute('data-max-guests') || "4", 10);
        let baseGuests = parseInt(selectedOption?.getAttribute('data-base-guests') || "2", 10);
        let minGuests = parseInt(selectedOption?.getAttribute('data-min-guests') || "2", 10);

        if (isDuplex) {
            if (modalTier === 'single_room') {
                baseGuests = 2;
                maxRoomGuests = 4;
                minGuests = 2;
            } else {
                baseGuests = parseInt(selectedOption?.getAttribute('data-base-guests') || "4", 10);
                maxRoomGuests = parseInt(selectedOption?.getAttribute('data-max-guests') || "8", 10);
                minGuests = parseInt(selectedOption?.getAttribute('data-min-guests') || "2", 10);
            }
        }

        let structLabel = 'Single Cottage';
        if (isDuplex) {
            structLabel = (modalTier === 'single_room') ? 'Duplex (Single Room Suite)' : 'Entire Duplex (Both Suites)';
        }

        let currentAdults = parseInt(modalAdultsInput?.value || "2", 10);
        let currentKids = parseInt(modalKidsInput?.value || "0", 10);

        // Clamping bounds
        if (currentAdults < 1) currentAdults = 1;
        if (currentKids < 0) currentKids = 0;
        if (currentAdults + currentKids > maxRoomGuests) {
            currentKids = Math.max(0, maxRoomGuests - currentAdults);
            if (currentAdults > maxRoomGuests) {
                currentAdults = maxRoomGuests;
                currentKids = 0;
            }
        }
        if (modalAdultsInput) modalAdultsInput.value = currentAdults;
        if (modalKidsInput) modalKidsInput.value = currentKids;
        if (modalGuestsSelect) modalGuestsSelect.value = (currentAdults + currentKids);

        const totalGuests = currentAdults + currentKids;

        // Update occupancy note badge
        const noteEl = document.getElementById('room-occupancy-note');
        if (noteEl) {
            noteEl.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${structLabel} • Base: ${baseGuests} Included • Max: ${maxRoomGuests}`;
        }

        // Stepper button disabled states
        const adultsMinus = document.querySelector('.btn-stepper-minus[data-target="modal-adults"]');
        const adultsPlus = document.querySelector('.btn-stepper-plus[data-target="modal-adults"]');
        if (adultsMinus) adultsMinus.disabled = (currentAdults <= 1);
        if (adultsPlus) adultsPlus.disabled = (totalGuests >= maxRoomGuests);

        const kidsMinus = document.querySelector('.btn-stepper-minus[data-target="modal-kids"]');
        const kidsPlus = document.querySelector('.btn-stepper-plus[data-target="modal-kids"]');
        if (kidsMinus) kidsMinus.disabled = (currentKids <= 0);
        if (kidsPlus) kidsPlus.disabled = (totalGuests >= maxRoomGuests);
    }

    // Attach click events to luxury steppers (+ / -)
    document.querySelectorAll('.btn-stepper').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const isPlus = btn.classList.contains('btn-stepper-plus');
            let val = parseInt(input.value || "0", 10);
            const min = parseInt(input.min || "0", 10);

            const selectedOption = modalVillaSelect ? modalVillaSelect.options[modalVillaSelect.selectedIndex] : null;
            const maxRoomGuests = parseInt(selectedOption?.getAttribute('data-max-guests') || "4", 10);

            let currentAdults = parseInt(modalAdultsInput?.value || "2", 10);
            let currentKids = parseInt(modalKidsInput?.value || "0", 10);

            if (targetId === 'modal-adults') {
                if (isPlus) {
                    if (currentAdults + currentKids < maxRoomGuests) {
                        val++;
                        if (modalAdultsInput) modalAdultsInput.dataset.userEdited = "true";
                    } else {
                        return;
                    }
                } else {
                    if (val > min) {
                        val--;
                        if (modalAdultsInput) modalAdultsInput.dataset.userEdited = "true";
                    }
                }
            } else if (targetId === 'modal-kids') {
                if (isPlus) {
                    if (currentAdults + currentKids < maxRoomGuests) {
                        val++;
                    } else {
                        return;
                    }
                } else {
                    if (val > min) val--;
                }
            }

            input.value = val;
            updateStepperButtons();
            recalculateBookingSummary();
        });
    });

    const heroVillaSelect = document.getElementById('hero-villa');
    const heroGuestsSelect = document.getElementById('hero-guests');
    if (heroVillaSelect && heroGuestsSelect) {
        function onHeroVillaChange() {
            const opt = heroVillaSelect.options[heroVillaSelect.selectedIndex];
            if (!opt) return;
            const baseGuests = parseInt(opt.getAttribute('data-base-guests') || "2", 10);
            const maxGuests = parseInt(opt.getAttribute('data-max-guests') || "4", 10);
            const extraRate = parseFloat(opt.getAttribute('data-extra-rate') || "1500");
            updateGuestOptions(heroGuestsSelect, baseGuests, maxGuests, extraRate, heroGuestsSelect.value);
        }
        heroVillaSelect.addEventListener('change', onHeroVillaChange);
        onHeroVillaChange();
    }

    function openBookingModal(preferredVilla) {
        if (!bookingModal) return;
        
        if (preferredVilla && modalVillaSelect) {
            modalVillaSelect.value = preferredVilla;
        } else if (document.getElementById('hero-villa') && modalVillaSelect) {
            modalVillaSelect.value = document.getElementById('hero-villa').value;
        }

        const heroGuestVal = document.getElementById('hero-guests') ? parseInt(document.getElementById('hero-guests').value, 10) : 2;
        if (modalAdultsInput && !modalAdultsInput.dataset.userEdited) {
            modalAdultsInput.value = Math.max(1, heroGuestVal);
            if (modalKidsInput) modalKidsInput.value = 0;
        }

        updateStepperButtons();
        recalculateBookingSummary();
        bookingModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (lenis) lenis.stop();
    }
    window.openBookingModal = openBookingModal;

    function closeBookingModal() {
        if (!bookingModal) return;
        bookingModal.classList.remove('active');
        document.body.style.overflow = '';
        if (lenis) lenis.start();
    }
    window.closeBookingModal = closeBookingModal;

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

    function onModalVillaChange() {
        if (!modalVillaSelect) return;
        const selectedOption = modalVillaSelect.options[modalVillaSelect.selectedIndex];
        const structureType = selectedOption?.getAttribute('data-structure-type') || 'single_hut';
        const isDuplex = (structureType === 'duplex_hut');
        
        const duplexTierBox = document.getElementById('modal-duplex-tier-box');
        if (duplexTierBox) {
            if (isDuplex) {
                duplexTierBox.style.display = 'block';
                const fullPrice = parseFloat(selectedOption.getAttribute('data-price') || "24000");
                const singlePrice = parseFloat(selectedOption.getAttribute('data-single-rate') || "14500");
                const fullTxt = document.getElementById('modal-duplex-full-rate-txt');
                const singleTxt = document.getElementById('modal-duplex-single-rate-txt');
                if (fullTxt) fullTxt.innerText = `₹${fullPrice.toLocaleString('en-IN')}/nt`;
                if (singleTxt) singleTxt.innerText = `₹${singlePrice.toLocaleString('en-IN')}/nt`;
            } else {
                duplexTierBox.style.display = 'none';
                const fullRadio = document.querySelector('input[name="modal_tier"][value="full"]');
                if (fullRadio) fullRadio.checked = true;
            }
        }

        updateStepperButtons();
        recalculateBookingSummary();
    }

    // Modal Duplex Tier Radio Buttons Listener
    document.querySelectorAll('input[name="modal_tier"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const labelFull = document.getElementById('modal-tier-label-full');
            const labelSingle = document.getElementById('modal-tier-label-single');
            if (radio.value === 'full') {
                if (labelFull) { labelFull.style.borderColor = 'var(--accent-gold)'; labelFull.style.background = 'rgba(197, 160, 89, 0.15)'; }
                if (labelSingle) { labelSingle.style.borderColor = 'rgba(255,255,255,0.15)'; labelSingle.style.background = 'rgba(0,0,0,0.3)'; }
            } else {
                if (labelSingle) { labelSingle.style.borderColor = '#56c2c9'; labelSingle.style.background = 'rgba(86, 194, 201, 0.15)'; }
                if (labelFull) { labelFull.style.borderColor = 'rgba(255,255,255,0.15)'; labelFull.style.background = 'rgba(0,0,0,0.3)'; }
            }
            updateStepperButtons();
            recalculateBookingSummary();
        });
    });

    // Price Calculation & Dynamic Rules
    function recalculateBookingSummary() {
        if (!modalCheckin || !modalCheckout || !modalVillaSelect) return;

        const checkinDate = new Date(modalCheckin.value);
        const checkoutDate = new Date(modalCheckout.value);
        let nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        if (isNaN(nights) || nights < 1) nights = 1;

        // Dynamic Breakfast Selection Rule:
        // Breakfast is complimentary. Custom dish pre-selection is available for stays of 2+ nights.
        // For 1-night stays, chef's signature farm breakfast is served automatically on departure morning.
        const breakfastGrid = document.getElementById('dishes-grid-breakfast');
        const breakfastNoticeMsg = document.getElementById('breakfast-nights-dynamic-msg');
        const breakfast1NightPlaceholder = document.getElementById('breakfast-1night-placeholder');
        if (breakfastGrid && breakfast1NightPlaceholder) {
            if (nights >= 2) {
                breakfastGrid.style.display = 'grid';
                breakfast1NightPlaceholder.style.display = 'none';
                if (breakfastNoticeMsg) {
                    breakfastNoticeMsg.innerHTML = `For your <strong>${nights}-night stay</strong>, select your tailored breakfast morning sets below (Complimentary).`;
                }
            } else {
                breakfastGrid.style.display = 'none';
                breakfast1NightPlaceholder.style.display = 'block';
                // Reset any selected breakfast quantities for single night stay
                breakfastGrid.querySelectorAll('.dish-qty-input').forEach(inp => inp.value = 0);
                if (breakfastNoticeMsg) {
                    breakfastNoticeMsg.innerHTML = `Chef's Daily Organic Orchard Breakfast is included complimentary on departure morning for single-night stays. (Custom dish selection is unlocked for stays of 2+ nights).`;
                }
            }
        }

        const selectedOption = modalVillaSelect.options[modalVillaSelect.selectedIndex];
        if (!selectedOption) return;

        const structureType = selectedOption.getAttribute('data-structure-type') || "single_hut";
        const isDuplex = (structureType === 'duplex_hut');
        const selectedTierRadio = document.querySelector('input[name="modal_tier"]:checked');
        const modalTier = selectedTierRadio ? selectedTierRadio.value : 'full';

        let villaPrice = parseFloat(selectedOption.getAttribute('data-price') || "14500");
        let villaName = selectedOption.getAttribute('data-name') || "Luxury Canopy Treehouse";
        let baseGuests = parseInt(selectedOption.getAttribute('data-base-guests') || "2", 10);
        let maxGuests = parseInt(selectedOption.getAttribute('data-max-guests') || "4", 10);
        const extraAdultRate = parseFloat(selectedOption.getAttribute('data-extra-rate') || "1500");
        const extraChildRate = parseFloat(selectedOption.getAttribute('data-extra-child-rate') || "800");
        const stayType = selectedOption.getAttribute('data-stay-type') || "treehouse";

        if (isDuplex) {
            if (modalTier === 'single_room') {
                villaPrice = parseFloat(selectedOption.getAttribute('data-single-rate') || "14500");
                baseGuests = 2;
                maxGuests = 4;
            } else {
                villaPrice = parseFloat(selectedOption.getAttribute('data-price') || "24000");
                baseGuests = parseInt(selectedOption.getAttribute('data-base-guests') || "4", 10);
                maxGuests = parseInt(selectedOption.getAttribute('data-max-guests') || "8", 10);
            }
        }

        const adultsCount = parseInt(modalAdultsInput?.value || "2", 10);
        const kidsCount = parseInt(modalKidsInput?.value || "0", 10);
        const guestsCount = adultsCount + kidsCount;
        if (modalGuestsSelect) modalGuestsSelect.value = guestsCount;

        // Dual Adult & Child Occupancy Math:
        const adultsInBase = Math.min(adultsCount, baseGuests);
        const extraAdults = Math.max(0, adultsCount - adultsInBase);
        const remBaseSlots = Math.max(0, baseGuests - adultsInBase);
        const kidsInBase = Math.min(kidsCount, remBaseSlots);
        const extraKids = Math.max(0, kidsCount - kidsInBase);

        const extraAdultsTotal = extraAdults * extraAdultRate * nights;
        const extraKidsTotal = extraKids * extraChildRate * nights;
        const baseVillaTotal = villaPrice * nights;

        // Addons total (Direct on-site payment to local guides; NOT billed in advance total)
        let selectedAddonsCount = 0;
        document.querySelectorAll('.addon-checkbox:checked').forEach(() => {
            selectedAddonsCount++;
        });

        // Curated Food Menu Selection Calculation
        let foodTotal = 0;
        let foodSetsCount = 0;
        const isAllFoodSkipped = document.getElementById('toggle-skip-all-food')?.checked || false;

        if (!isAllFoodSkipped) {
            document.querySelectorAll('.modal-dish-card').forEach(card => {
                const qtyInput = card.querySelector('.dish-qty-input');
                const qty = parseInt(qtyInput?.value || "0", 10);
                const category = card.getAttribute('data-dish-category');
                // Breakfast is complimentary (price = 0)
                const price = (category === 'breakfast') ? 0 : parseFloat(card.getAttribute('data-dish-price') || "0");
                if (qty > 0) {
                    foodTotal += (qty * price);
                    foodSetsCount += qty;
                }
            });
        }

        // Addons are payable on-site directly to local artisans/guides, so addonsTotal is 0 in advance bill
        const stayTotal = baseVillaTotal + extraAdultsTotal + extraKidsTotal + foodTotal;

        // Update summary elements
        const summaryNights = document.getElementById('summary-nights');
        const summaryVillaRate = document.getElementById('summary-villa-rate');
        const summaryExtraAdultsLine = document.getElementById('summary-extra-adults-line');
        const summaryExtraAdultsLabel = document.getElementById('summary-extra-adults-label');
        const summaryExtraAdultsRate = document.getElementById('summary-extra-adults-rate');
        const summaryExtraKidsLine = document.getElementById('summary-extra-kids-line');
        const summaryExtraKidsLabel = document.getElementById('summary-extra-kids-label');
        const summaryExtraKidsRate = document.getElementById('summary-extra-kids-rate');
        const summaryExtraGuestsLine = document.getElementById('summary-extra-guests-line');
        const summaryFoodLine = document.getElementById('summary-food-line');
        const summaryFoodLabel = document.getElementById('summary-food-label');
        const summaryFoodRate = document.getElementById('summary-food-rate');
        const summaryAddonsLine = document.getElementById('summary-addons-line');
        const summaryAddonsRate = document.getElementById('summary-addons-rate');
        const summaryTotal = document.getElementById('summary-total');

        if (summaryNights) summaryNights.innerText = `${nights} ${nights === 1 ? 'Night' : 'Nights'}`;
        if (summaryVillaRate) summaryVillaRate.innerText = `₹${baseVillaTotal.toLocaleString('en-IN')}`;

        // Extra Adults itemized line
        if (summaryExtraAdultsLine && summaryExtraAdultsRate) {
            if (extraAdults > 0) {
                summaryExtraAdultsLine.style.display = 'flex';
                if (summaryExtraAdultsLabel) {
                    summaryExtraAdultsLabel.innerText = `Extra Adults (${extraAdults} × ₹${extraAdultRate.toLocaleString('en-IN')}/nt × ${nights}N):`;
                }
                summaryExtraAdultsRate.innerText = `+₹${extraAdultsTotal.toLocaleString('en-IN')}`;
            } else {
                summaryExtraAdultsLine.style.display = 'none';
            }
        }

        // Extra Children itemized line
        if (summaryExtraKidsLine && summaryExtraKidsRate) {
            if (extraKids > 0) {
                summaryExtraKidsLine.style.display = 'flex';
                if (summaryExtraKidsLabel) {
                    summaryExtraKidsLabel.innerText = `Extra Children (${extraKids} × ₹${extraChildRate.toLocaleString('en-IN')}/nt × ${nights}N):`;
                }
                summaryExtraKidsRate.innerText = `+₹${extraKidsTotal.toLocaleString('en-IN')}`;
            } else {
                summaryExtraKidsLine.style.display = 'none';
            }
        }

        // Food Menu itemized line
        if (summaryFoodLine && summaryFoodRate) {
            if (foodTotal > 0 || foodSetsCount > 0) {
                summaryFoodLine.style.display = 'flex';
                if (summaryFoodLabel) {
                    summaryFoodLabel.innerText = `Curated Gastronomy (${foodSetsCount} Sets):`;
                }
                summaryFoodRate.innerText = foodTotal > 0 ? `+₹${foodTotal.toLocaleString('en-IN')}` : 'Included';
            } else {
                summaryFoodLine.style.display = 'none';
            }
        }

        if (summaryExtraGuestsLine) summaryExtraGuestsLine.style.display = 'none';

        // Addons Experiences line (Payable on-site notice)
        if (summaryAddonsLine && summaryAddonsRate) {
            if (selectedAddonsCount > 0) {
                summaryAddonsLine.style.display = 'flex';
                summaryAddonsRate.innerText = 'Payable On-Site (₹0 in Bill)';
            } else {
                summaryAddonsLine.style.display = 'none';
            }
        }

        if (summaryTotal) summaryTotal.innerText = `₹${stayTotal.toLocaleString('en-IN')}`;
    }

    if (modalVillaSelect) modalVillaSelect.addEventListener('change', onModalVillaChange);
    if (modalGuestsSelect) modalGuestsSelect.addEventListener('change', recalculateBookingSummary);
    if (modalCheckin) modalCheckin.addEventListener('change', recalculateBookingSummary);
    if (modalCheckout) modalCheckout.addEventListener('change', recalculateBookingSummary);
    document.querySelectorAll('.addon-checkbox').forEach(cb => {
        cb.addEventListener('change', recalculateBookingSummary);
    });

    // -------------------------------------------------------------
    // Food Menu Interactive Steppers & Skip Toggles
    // -------------------------------------------------------------
    // Dish Quantity Steppers (+ / -)
    document.querySelectorAll('.btn-dish-qty').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target-input');
            const input = document.getElementById(targetId);
            if (!input) return;

            let val = parseInt(input.value || "0", 10);
            if (btn.classList.contains('plus')) {
                if (val < 10) val++;
            } else if (btn.classList.contains('minus')) {
                if (val > 0) val--;
            }
            input.value = val;
            recalculateBookingSummary();
        });
    });

    // Meal Category Skip Checkboxes
    document.querySelectorAll('.cat-skip-checkbox').forEach(chk => {
        chk.addEventListener('change', function() {
            const cat = this.getAttribute('data-target-cat');
            const grid = document.getElementById('dishes-grid-' + cat);
            if (!grid) return;

            if (this.checked) {
                grid.style.opacity = '0.35';
                grid.style.pointerEvents = 'none';
                grid.querySelectorAll('.dish-qty-input').forEach(inp => inp.value = 0);
            } else {
                grid.style.opacity = '1';
                grid.style.pointerEvents = 'auto';
            }
            recalculateBookingSummary();
        });
    });

    // Global Skip Food Pre-Selection Toggle
    const toggleSkipAllFood = document.getElementById('toggle-skip-all-food');
    if (toggleSkipAllFood) {
        toggleSkipAllFood.addEventListener('change', function() {
            const container = document.getElementById('food-selection-container');
            if (!container) return;

            if (this.checked) {
                container.style.opacity = '0.35';
                container.style.pointerEvents = 'none';
                container.querySelectorAll('.dish-qty-input').forEach(inp => inp.value = 0);
            } else {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
            recalculateBookingSummary();
        });
    }

    // Modal Account Type Radio Switcher (Guest vs Create Permanent Account)
    document.querySelectorAll('input[name="modal_account_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const pwdBox = document.getElementById('modal-password-container');
            const labelGuest = document.getElementById('label-acc-guest');
            const labelPermanent = document.getElementById('label-acc-permanent');
            
            if (this.value === 'create_account') {
                if (pwdBox) pwdBox.style.display = 'block';
                document.getElementById('modal-password')?.setAttribute('required', 'required');
                if (labelPermanent) {
                    labelPermanent.style.background = '#FEF9C3';
                    labelPermanent.style.border = '2px solid #CA8A04';
                    const strong = labelPermanent.querySelector('strong');
                    if (strong) strong.style.color = '#854D0E';
                    const span = labelPermanent.querySelector('span');
                    if (span) span.style.color = '#334155';
                }
                if (labelGuest) {
                    labelGuest.style.background = '#F8FAFC';
                    labelGuest.style.border = '1.5px solid #CBD5E1';
                    const strong = labelGuest.querySelector('strong');
                    if (strong) strong.style.color = '#1C3826';
                    const span = labelGuest.querySelector('span');
                    if (span) span.style.color = '#334155';
                }
            } else {
                if (pwdBox) pwdBox.style.display = 'none';
                document.getElementById('modal-password')?.removeAttribute('required');
                if (labelGuest) {
                    labelGuest.style.background = '#FEF9C3';
                    labelGuest.style.border = '2px solid #CA8A04';
                    const strong = labelGuest.querySelector('strong');
                    if (strong) strong.style.color = '#854D0E';
                    const span = labelGuest.querySelector('span');
                    if (span) span.style.color = '#334155';
                }
                if (labelPermanent) {
                    labelPermanent.style.background = '#F8FAFC';
                    labelPermanent.style.border = '1.5px solid #CBD5E1';
                    const strong = labelPermanent.querySelector('strong');
                    if (strong) strong.style.color = '#1C3826';
                    const span = labelPermanent.querySelector('span');
                    if (span) span.style.color = '#334155';
                }
            }
        });
    });

    // -------------------------------------------------------------
    // 6.2 Government ID Proof File Upload Interactions
    // -------------------------------------------------------------
    const idFileInput = document.getElementById('modal-id-file');
    const idFilePrompt = document.getElementById('id-file-prompt');
    const idFileSelected = document.getElementById('id-file-selected');
    const idFileNameEl = document.getElementById('id-file-name');
    const idFileSizeEl = document.getElementById('id-file-size');
    const btnRemoveIdFile = document.getElementById('btn-remove-id-file');

    if (idFileInput) {
        idFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 8 * 1024 * 1024) {
                    alert('The chosen file is larger than 8MB. Please select a smaller file (JPG, PNG, WEBP, PDF).');
                    this.value = '';
                    if (idFilePrompt) idFilePrompt.style.display = 'flex';
                    if (idFileSelected) idFileSelected.style.display = 'none';
                    return;
                }
                const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
                if (idFileNameEl) idFileNameEl.innerText = file.name;
                if (idFileSizeEl) idFileSizeEl.innerText = `(${sizeMb} MB)`;
                if (idFilePrompt) idFilePrompt.style.display = 'none';
                if (idFileSelected) idFileSelected.style.display = 'flex';
            }
        });
    }

    if (btnRemoveIdFile) {
        btnRemoveIdFile.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (idFileInput) idFileInput.value = '';
            if (idFilePrompt) idFilePrompt.style.display = 'flex';
            if (idFileSelected) idFileSelected.style.display = 'none';
        });
    }

    // -------------------------------------------------------------
    // 7. Instant WhatsApp & Direct Reservation Submission
    // -------------------------------------------------------------
    async function saveBookingToDatabase(payload) {
        try {
            const formData = new FormData();
            for (const key in payload) {
                if (key === 'food_items') {
                    formData.append(key, JSON.stringify(payload[key]));
                } else if (payload[key] !== null && payload[key] !== undefined) {
                    formData.append(key, payload[key]);
                }
            }
            if (idFileInput && idFileInput.files && idFileInput.files[0]) {
                formData.append('id_proof_file', idFileInput.files[0]);
            }
            const res = await fetch('api/book.php', {
                method: 'POST',
                body: formData
            });
            return await res.json();
        } catch (err) {
            console.error('Booking save error:', err);
            return null;
        }
    }

    function collectBookingPayload() {
        const guestName = document.getElementById('modal-name')?.value.trim() || '';
        const guestPhone = document.getElementById('modal-phone')?.value.trim() || '';
        const guestEmail = document.getElementById('modal-email')?.value.trim() || '';
        const guestCity = document.getElementById('modal-city')?.value.trim() || '';
        const idProofType = document.getElementById('modal-id-type')?.value || 'Aadhaar Card';
        const idProofNumber = document.getElementById('modal-id-number')?.value.trim() || '';
        const guestNotes = document.getElementById('modal-notes')?.value.trim() || '';
        const villaSlug = modalVillaSelect?.value || 'treehouse';

        const adultsCount = parseInt(modalAdultsInput?.value || "2", 10);
        const kidsCount = parseInt(modalKidsInput?.value || "0", 10);
        const guestsCount = adultsCount + kidsCount;

        const checkin = modalCheckin?.value || '';
        const checkout = modalCheckout?.value || '';

        // Duplex tier choice
        const tierRadio = document.querySelector('input[name="modal_tier"]:checked');
        const modalTier = tierRadio ? tierRadio.value : 'full';

        // Addons
        const addonsList = [];
        document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
            const card = cb.closest('.addon-card');
            const name = card?.querySelector('.addon-name')?.innerText || 'Experience';
            addonsList.push(name);
        });

        // Food Items Collection
        const foodItems = [];
        const isAllFoodSkipped = document.getElementById('toggle-skip-all-food')?.checked || false;

        if (!isAllFoodSkipped) {
            document.querySelectorAll('.modal-dish-card').forEach(card => {
                const qtyInput = card.querySelector('.dish-qty-input');
                const qty = parseInt(qtyInput?.value || "0", 10);
                const cat = card.getAttribute('data-dish-category');
                const price = (cat === 'breakfast') ? 0 : parseFloat(card.getAttribute('data-dish-price') || "0");
                if (qty > 0) {
                    foodItems.push({
                        id: card.getAttribute('data-dish-id'),
                        category: cat,
                        heading: card.getAttribute('data-dish-name'),
                        subtitle: card.getAttribute('data-dish-subtitle'),
                        price: price,
                        quantity: qty,
                        subtotal: qty * price
                    });
                }
            });
        }

        // Account Type & Password
        const accountTypeRadio = document.querySelector('input[name="modal_account_type"]:checked');
        const createAccount = accountTypeRadio ? (accountTypeRadio.value === 'create_account') : false;
        const password = document.getElementById('modal-password')?.value || '';

        return {
            name: guestName,
            phone: guestPhone,
            email: guestEmail,
            city_state: guestCity,
            id_proof_type: idProofType,
            id_proof_number: idProofNumber,
            has_id_file: (idFileInput?.files && idFileInput.files.length > 0),
            villa: villaSlug,
            tier: modalTier,
            adults: adultsCount,
            kids: kidsCount,
            guests: guestsCount,
            checkin: checkin,
            checkout: checkout,
            addons: addonsList.join(', '),
            notes: guestNotes,
            food_items: foodItems,
            food_skipped: isAllFoodSkipped || (foodItems.length === 0),
            create_account: createAccount,
            password: password
        };
    }

    function showBookingConfirmationState(res) {
        const form = document.getElementById('luxury-booking-form');
        const confirmBox = document.getElementById('booking-confirmation-state');
        if (!confirmBox) return;

        if (form) form.style.display = 'none';
        confirmBox.style.display = 'block';

        const refCodeEl = document.getElementById('confirm-ref-code');
        if (refCodeEl) refCodeEl.innerText = res.reference_code || 'FF-0000';

        const passcodeRow = document.getElementById('confirm-passcode-row');
        const passcodeVal = document.getElementById('confirm-passcode-val');
        const expiryRow = document.getElementById('confirm-expiry-row');
        const expiryVal = document.getElementById('confirm-expiry-val');

        if (res.is_guest && res.guest_access_token) {
            if (passcodeRow && passcodeVal) {
                passcodeRow.style.display = 'flex';
                passcodeVal.innerText = res.guest_access_token;
            }
            if (expiryRow && expiryVal) {
                expiryRow.style.display = 'flex';
                expiryVal.innerText = res.expires_date_formatted || '30 Days';
            }
        } else {
            if (passcodeRow) passcodeRow.style.display = 'none';
            if (expiryRow) expiryRow.style.display = 'none';
        }

        const receiptBtn = document.getElementById('confirm-receipt-btn');
        if (receiptBtn && res.receipt_url) {
            receiptBtn.href = res.receipt_url;
        }

        const waBtn = document.getElementById('confirm-wa-btn');
        if (waBtn) {
            const waNum = res.concierge_whatsapp || '919234567890';
            const waMsg = encodeURIComponent(`Hello Concierge, I have just reserved my stay under Ref: ${res.reference_code}. Please verify my reservation.`);
            waBtn.href = `https://wa.me/${waNum}?text=${waMsg}`;
        }
    }

    // Submit Reservation & Generate PDF Receipt
    const submitBookingDirectBtn = document.getElementById('btn-submit-booking-direct');
    if (submitBookingDirectBtn) {
        submitBookingDirectBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const payload = collectBookingPayload();

            if (!payload.name || !payload.phone) {
                alert('Please enter your full name and WhatsApp contact number.');
                document.getElementById('modal-name')?.focus();
                return;
            }

            if (!payload.checkin || !payload.checkout) {
                alert('Please select both Check-In and Check-Out dates.');
                document.getElementById('modal-checkin')?.focus();
                return;
            }

            if (payload.create_account && (!payload.password || payload.password.length < 4)) {
                alert('Please enter a password with at least 4 characters for your permanent account.');
                document.getElementById('modal-password')?.focus();
                return;
            }

            submitBookingDirectBtn.disabled = true;
            submitBookingDirectBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Recording Reservation...</span>';

            const res = await saveBookingToDatabase(payload);

            if (res && res.success) {
                showBookingConfirmationState(res);
            } else {
                submitBookingDirectBtn.disabled = false;
                submitBookingDirectBtn.innerHTML = '<i class="fa-solid fa-receipt"></i> <span>Confirm Reservation & Generate PDF Receipt</span>';
                alert(res?.message || 'Could not record reservation. Please connect directly via WhatsApp Concierge.');
            }
        });
    }

    // WhatsApp Concierge Submission
    const whatsappSubmitBtn = document.getElementById('btn-submit-whatsapp');
    if (whatsappSubmitBtn) {
        whatsappSubmitBtn.addEventListener('click', async () => {
            const payload = collectBookingPayload();

            if (!payload.name || !payload.phone) {
                alert('Please enter your full name and WhatsApp contact number before connecting.');
                document.getElementById('modal-name')?.focus();
                return;
            }

            whatsappSubmitBtn.disabled = true;
            whatsappSubmitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Connecting...</span>';

            const saveRes = await saveBookingToDatabase(payload);
            const refCode = saveRes?.reference_code || 'FF-' + Math.floor(1000 + Math.random() * 9000);

            // Construct WhatsApp message
            const selectedOption = modalVillaSelect?.options[modalVillaSelect.selectedIndex];
            let villaName = selectedOption?.getAttribute('data-name') || 'Sanctuary Suite';
            const structureType = selectedOption?.getAttribute('data-structure-type') || 'single_hut';
            if (structureType === 'duplex_hut') {
                villaName += (payload.tier === 'single_room') ? ' [Duplex: Single Room Suite]' : ' [Full Duplex: Both Suites]';
            }
            const total = document.getElementById('summary-total')?.innerText || '₹14,500';

            let foodSummary = 'Farm À La Carte on Arrival (Skipped)';
            if (payload.food_items && payload.food_items.length > 0) {
                foodSummary = payload.food_items.map(f => `${f.heading} (${f.quantity} sets)`).join(', ');
            }

            let expNote = payload.addons ? `\n• *Experiences (On-Site Direct Pay)*: ${payload.addons}` : '';
            let cityNote = payload.city_state ? `\n• *City/Origin*: ${payload.city_state}` : '';
            let idNote = payload.id_proof_type ? (`\n• *ID Proof*: ${payload.id_proof_type}` + (payload.has_id_file ? ' (📎 Document Attached)' : '')) : '';

            const message = `🌿 *RESERVATION REQUEST — FOOD FOREST KANTHALLOOR* 🌿\n\n` +
                `• *Booking Reference*: #${refCode}\n` +
                `• *Guest Name*: ${payload.name}\n` +
                `• *Contact*: ${payload.phone}\n` +
                `• *Suite*: ${villaName}\n` +
                `• *Check-in*: ${payload.checkin}\n` +
                `• *Check-out*: ${payload.checkout}\n` +
                `• *Occupancy*: ${payload.adults} Adults, ${payload.kids} Children` +
                `${cityNote}${idNote}\n` +
                `• *Curated Gastronomy*: ${foodSummary}` +
                `${expNote}\n` +
                `• *Estimated Total*: ${total}\n\n` +
                `Kindly confirm availability. Luxury receipt is available at Ref #${refCode}.`;

            const encodedMessage = encodeURIComponent(message);
            const whatsappNumber = saveRes?.concierge_whatsapp || '919234567890';
            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
            window.open(whatsappUrl, '_blank');

            whatsappSubmitBtn.disabled = false;
            whatsappSubmitBtn.innerHTML = '<i class="fa-brands fa-whatsapp"></i> <span>Instant WhatsApp Concierge Confirmation</span>';

            if (saveRes && saveRes.success) {
                showBookingConfirmationState(saveRes);
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
                const isStay = (s.is_stay == 1 || s.category === 'stays');
                const rate = parseFloat(s.room_rate || s.stay_price || 0);
                const roomSlug = s.linked_room_slug || (s.title && s.title.toLowerCase().includes('treehouse') ? 'treehouse' : (s.title && s.title.toLowerCase().includes('woodhouse') ? 'woodhouse' : 'mudhouse'));

                return {
                    id: parseInt(s.id, 10),
                    spotNum: parseInt(s.spot_number, 10) || (idx + 1),
                    num: "SPOT " + String(s.spot_number || (idx + 1)).padStart(2, '0'),
                    title: s.title || '',
                    desc: s.description || '',
                    category: s.category || 'nature',
                    isStay: isStay,
                    roomSlug: roomSlug,
                    rate: rate,
                    singleRoomRate: parseFloat(s.single_room_rate || 0),
                    structureType: s.structure_type || 'single_hut',
                    ctaLink: s.cta_link || (isStay ? `booking.php?villa=${roomSlug}` : '#experiences'),
                    ctaText: s.cta_text || (isStay ? 'Book This Stay' : 'Explore Details'),
                    photos: photosArr
                };
            });
        }

        let currentIdx = 0;
        let currentPhotoIdx = 0;
        const pins = document.querySelectorAll('.sanctuary-pin');

        // Inspector DOM Elements
        const insNum = document.getElementById('ins-zone-num');
        const insZoneType = document.getElementById('ins-zone-type');
        const insPriceBadge = document.getElementById('ins-price-badge');
        const insTitle = document.getElementById('ins-title');
        const insDesc = document.getElementById('ins-desc');
        const insImg = document.getElementById('ins-img');
        const insCurrIdx = document.getElementById('ins-curr-idx');
        const insPrevBtn = document.getElementById('ins-prev-btn');
        const insNextBtn = document.getElementById('ins-next-btn');
        const insCtaBtn = document.getElementById('ins-cta-btn');
        const insCtaText = document.getElementById('ins-cta-text');
        const insQuickBookBtn = document.getElementById('ins-quick-book-btn');

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
            if (insZoneType) {
                if (zone.isStay) {
                    const isDup = (zone.structureType === 'duplex_hut' || (zone.title && zone.title.toLowerCase().includes('duplex')));
                    insZoneType.innerHTML = isDup ? '<i class="fa-solid fa-layer-group"></i> 🏰 DUPLEX CHALET (2 SUITES)' : '<i class="fa-solid fa-house-chimney"></i> 🏡 SINGLE COTTAGE';
                    insZoneType.style.color = isDup ? '#56C2C9' : '#C5A059';
                } else {
                    insZoneType.innerHTML = zone.category === 'dining' ? '<i class="fa-solid fa-utensils"></i> 🍲 FARM DINING' : '<i class="fa-solid fa-water"></i> 🌿 ESTATE FACILITY';
                    insZoneType.style.color = '#56c2c9';
                }
            }

            if (insPriceBadge) {
                if (zone.isStay && zone.rate > 0) {
                    insPriceBadge.innerText = `₹${zone.rate.toLocaleString('en-IN')}/nt`;
                    insPriceBadge.style.display = 'inline-block';
                } else {
                    insPriceBadge.innerText = '';
                    insPriceBadge.style.display = 'none';
                }
            }

            if (insTitle) insTitle.innerText = zone.title;
            if (insDesc) insDesc.innerText = zone.desc;
            if (insCurrIdx) insCurrIdx.innerText = index + 1;

            if (insCtaBtn) {
                if (zone.isStay) {
                    insCtaBtn.href = `booking.php?villa=${zone.roomSlug}`;
                    if (insCtaText) insCtaText.innerText = 'Reserve Stay (Map Booking)';
                } else {
                    insCtaBtn.href = zone.ctaLink;
                    if (insCtaText) insCtaText.innerText = 'Explore Feature';
                }
            }

            if (insQuickBookBtn) {
                if (zone.isStay) {
                    insQuickBookBtn.style.display = 'inline-flex';
                    insQuickBookBtn.setAttribute('data-villa', zone.roomSlug);
                } else {
                    insQuickBookBtn.style.display = 'none';
                }
            }

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

        // Map Category Filter Buttons
        const mapFilterBtns = document.querySelectorAll('.sanctuary-filter-btn[data-map-filter]');
        mapFilterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                mapFilterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.getAttribute('data-map-filter');
                let firstVisibleIdx = -1;

                pins.forEach((pin, pIdx) => {
                    const pinCategory = pin.getAttribute('data-category');
                    const isStay = pin.getAttribute('data-is-stay') === '1';
                    const isDuplex = pin.getAttribute('data-is-duplex') === '1';

                    let isMatch = false;
                    if (filter === 'all') {
                        isMatch = true;
                    } else if (filter === 'stays') {
                        isMatch = isStay;
                    } else if (filter === 'single') {
                        isMatch = (isStay && !isDuplex);
                    } else if (filter === 'duplex') {
                        isMatch = (isStay && isDuplex);
                    } else if (filter === 'dining') {
                        isMatch = (pinCategory === 'dining');
                    } else if (filter === 'amenities') {
                        isMatch = (pinCategory === 'amenities' || pinCategory === 'nature');
                    }

                    if (isMatch) {
                        pin.style.opacity = '1';
                        pin.style.pointerEvents = 'auto';
                        pin.style.transform = 'scale(1)';
                        if (firstVisibleIdx === -1) {
                            firstVisibleIdx = pIdx;
                        }
                    } else {
                        pin.style.opacity = '0.15';
                        pin.style.pointerEvents = 'none';
                        pin.style.transform = 'scale(0.8)';
                    }
                });

                // If current zone is now filtered out, switch to first visible
                if (firstVisibleIdx !== -1) {
                    updateZoneView(firstVisibleIdx, true);
                }
            });
        });

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
    // 13. Sanctuary Visual Gallery & Collections Engine handled in initGalleryCollections()
    // -------------------------------------------------------------

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
    // 17. Sanctuary Multi-Photo Collection Showcase & Zoom Lightbox
    // -------------------------------------------------------------
    function initGalleryCollections() {
        const lightbox = document.getElementById('luxury-lightbox');
        if (!lightbox) return;

        // Ensure lightbox is a direct child of body to prevent any parent container transforms or clipping
        if (lightbox.parentElement !== document.body) {
            document.body.appendChild(lightbox);
        }

        // Lightbox Elements
        const lbBackdrop = document.getElementById('lb-backdrop');
        const lbCloseBtn = document.getElementById('lb-close-btn');
        const lbTag = document.getElementById('lb-tag');
        const lbCollectionTitle = document.getElementById('lb-collection-title');
        const lbCurrIndex = document.getElementById('lb-curr-index');
        const lbTotalCount = document.getElementById('lb-total-count');
        const lbTitle = document.getElementById('lb-title');
        const lbCaption = document.getElementById('lb-caption');
        const lbActiveImg = document.getElementById('lb-active-img');
        const lbCanvas = document.getElementById('lb-canvas');
        const lbViewport = document.getElementById('lb-viewport');
        const lbPrevBtn = document.getElementById('lb-prev-btn');
        const lbNextBtn = document.getElementById('lb-next-btn');
        const lbThumbnailsStrip = document.getElementById('lb-thumbnails-strip');
        const lbZoomIn = document.getElementById('lb-zoom-in');
        const lbZoomOut = document.getElementById('lb-zoom-out');
        const lbZoomReset = document.getElementById('lb-zoom-reset');
        const lbZoomLevelText = document.getElementById('lb-zoom-level-text');
        const lbFullscreenBtn = document.getElementById('lb-fullscreen-btn');
        const lbFsIcon = document.getElementById('lb-fs-icon');
        const lbZoomHint = document.getElementById('lb-zoom-hint');

        // State
        let currentCollection = null;
        let currentPhotos = [];
        let currentPhotoIdx = 0;
        let scale = 1.0;
        let translateX = 0;
        let translateY = 0;
        let isDragging = false;
        let dragStartX = 0;
        let dragStartY = 0;
        let initialTranslateX = 0;
        let initialTranslateY = 0;
        let hintTimeout = null;

        // Apply Transform to Canvas
        function updateTransform(smooth = true) {
            if (!lbCanvas) return;
            lbCanvas.style.transition = smooth ? 'transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1)' : 'none';
            lbCanvas.style.transform = `translate3d(${translateX}px, ${translateY}px, 0) scale(${scale})`;

            if (lbZoomLevelText) {
                lbZoomLevelText.innerText = Math.round(scale * 100) + '%';
            }

            if (lbViewport) {
                if (scale > 1.05) {
                    lbViewport.classList.add('is-zoomed');
                    lbViewport.style.cursor = isDragging ? 'grabbing' : 'grab';
                } else {
                    lbViewport.classList.remove('is-zoomed');
                    lbViewport.style.cursor = 'default';
                }
            }
        }

        // Zoom Operations
        function zoomIn(step = 0.4) {
            const prevScale = scale;
            scale = Math.min(3.5, scale + step);
            if (scale > prevScale && scale > 1.05) {
                translateX = Math.max(-500, Math.min(500, translateX));
                translateY = Math.max(-400, Math.min(400, translateY));
            }
            updateTransform(true);
        }

        function zoomOut(step = 0.4) {
            scale = Math.max(1.0, scale - step);
            if (scale <= 1.05) {
                scale = 1.0;
                translateX = 0;
                translateY = 0;
            }
            updateTransform(true);
        }

        function resetZoom() {
            scale = 1.0;
            translateX = 0;
            translateY = 0;
            updateTransform(true);
        }

        function toggleZoom() {
            if (scale > 1.2) {
                resetZoom();
            } else {
                scale = 2.2;
                updateTransform(true);
            }
        }

        // Render Active Photo
        function renderPhoto(idx, smooth = true) {
            if (!currentPhotos || currentPhotos.length === 0) return;
            if (idx < 0) idx = currentPhotos.length - 1;
            if (idx >= currentPhotos.length) idx = 0;

            currentPhotoIdx = idx;
            const photo = currentPhotos[currentPhotoIdx];

            // Reset Zoom for new photo
            resetZoom();

            if (lbActiveImg) {
                if (smooth) {
                    lbActiveImg.classList.add('fade-out');
                    setTimeout(() => {
                        lbActiveImg.src = photo.src;
                        lbActiveImg.alt = photo.title || 'Sanctuary Photo';
                        lbActiveImg.classList.remove('fade-out');
                    }, 80);
                } else {
                    lbActiveImg.src = photo.src;
                    lbActiveImg.alt = photo.title || 'Sanctuary Photo';
                    lbActiveImg.classList.remove('fade-out');
                }
            }

            if (lbCurrIndex) lbCurrIndex.innerText = String(currentPhotoIdx + 1).padStart(2, '0');
            if (lbTotalCount) lbTotalCount.innerText = String(currentPhotos.length).padStart(2, '0');
            if (lbTitle) lbTitle.innerText = photo.title || currentCollection.title;
            if (lbCaption) lbCaption.innerText = photo.caption || currentCollection.description || '';

            // Update active thumbnail in strip
            if (lbThumbnailsStrip) {
                const thumbs = lbThumbnailsStrip.querySelectorAll('.lb-thumb');
                thumbs.forEach((t, tIdx) => {
                    if (tIdx === currentPhotoIdx) {
                        t.classList.add('active');
                        t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    } else {
                        t.classList.remove('active');
                    }
                });
            }
        }

        // Open Lightbox with Collection
        function openCollection(collectionData, startIdx = 0) {
            if (!collectionData) return;
            currentCollection = collectionData;
            currentPhotos = (collectionData.photos && collectionData.photos.length > 0) ? collectionData.photos : [{
                src: collectionData.cover_image || collectionData.image_url,
                title: collectionData.title,
                caption: collectionData.description || collectionData.caption
            }];

            if (lbTag) lbTag.innerText = currentCollection.tag || currentCollection.category || 'SANCTUARY';
            if (lbCollectionTitle) lbCollectionTitle.innerText = currentCollection.title || 'Sanctuary Showcase';

            // Build Thumbnails Strip
            if (lbThumbnailsStrip) {
                lbThumbnailsStrip.innerHTML = '';
                currentPhotos.forEach((p, pIdx) => {
                    const thumb = document.createElement('div');
                    thumb.className = `lb-thumb ${pIdx === startIdx ? 'active' : ''}`;
                    thumb.setAttribute('data-photo-idx', pIdx);
                    thumb.setAttribute('title', p.title || `Photo ${pIdx + 1}`);
                    thumb.innerHTML = `
                        <img src="${p.src}" alt="${p.title || 'Photo'}" loading="lazy">
                        <span class="lb-thumb-num font-sans">${pIdx + 1}</span>
                    `;
                    thumb.addEventListener('click', (e) => {
                        e.stopPropagation();
                        renderPhoto(pIdx, true);
                    });
                    lbThumbnailsStrip.appendChild(thumb);
                });
            }

            renderPhoto(startIdx, false);

            lightbox.style.display = 'flex';
            lightbox.classList.add('active');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            // Show hint briefly
            if (lbZoomHint) {
                lbZoomHint.style.opacity = '1';
                clearTimeout(hintTimeout);
                hintTimeout = setTimeout(() => {
                    lbZoomHint.style.opacity = '0';
                }, 3500);
            }
        }

        // Expose globally for inline and external triggers
        window.openGalleryCollection = function(idx) {
            if (window.sanctuaryGalleryCollections && window.sanctuaryGalleryCollections[idx]) {
                openCollection(window.sanctuaryGalleryCollections[idx], 0);
                return;
            }
            const allCards = document.querySelectorAll('.gallery-card');
            if (allCards[idx]) {
                const colAttr = allCards[idx].getAttribute('data-collection');
                if (colAttr) {
                    try {
                        const colData = JSON.parse(colAttr);
                        openCollection(colData, 0);
                        return;
                    } catch (e) {}
                }
            }
        };

        // Close Lightbox
        function closeLightbox() {
            lightbox.classList.remove('active');
            lightbox.setAttribute('aria-hidden', 'true');
            setTimeout(() => {
                if (!lightbox.classList.contains('active')) {
                    lightbox.style.display = 'none';
                }
            }, 300);
            document.body.style.overflow = '';
            resetZoom();
            if (document.fullscreenElement) {
                document.exitFullscreen?.().catch(() => {});
            }
        }
        window.closeGalleryLightbox = closeLightbox;

        // Global Event Delegation for Gallery Card Clicks
        document.addEventListener('click', (e) => {
            const card = e.target.closest('.gallery-card');
            if (card) {
                e.preventDefault();
                const idxStr = card.getAttribute('data-index');
                const idx = idxStr !== null ? parseInt(idxStr, 10) : -1;
                if (idx >= 0 && window.sanctuaryGalleryCollections && window.sanctuaryGalleryCollections[idx]) {
                    openCollection(window.sanctuaryGalleryCollections[idx], 0);
                    return;
                }

                const colAttr = card.getAttribute('data-collection');
                if (colAttr) {
                    try {
                        const colData = JSON.parse(colAttr);
                        openCollection(colData, 0);
                        return;
                    } catch (err) {
                        console.warn("Collection parse error:", err);
                    }
                }

                const fallbackData = {
                    title: card.getAttribute('data-title') || 'Sanctuary Photo',
                    category: card.getAttribute('data-tag') || 'Gallery',
                    tag: card.getAttribute('data-tag') || 'CANOPY DWELLING',
                    description: card.getAttribute('data-caption') || '',
                    cover_image: card.querySelector('.gallery-img')?.src || '',
                    photos: [{
                        src: card.querySelector('.gallery-img')?.src || '',
                        title: card.getAttribute('data-title') || 'Sanctuary Photo',
                        caption: card.getAttribute('data-caption') || ''
                    }]
                };
                openCollection(fallbackData, 0);
            }
        });

        // Close Handlers
        if (lbCloseBtn) lbCloseBtn.addEventListener('click', closeLightbox);
        if (lbBackdrop) lbBackdrop.addEventListener('click', closeLightbox);

        // Next / Prev Handlers
        if (lbNextBtn) lbNextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            renderPhoto(currentPhotoIdx + 1, true);
        });

        if (lbPrevBtn) lbPrevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            renderPhoto(currentPhotoIdx - 1, true);
        });

        // Zoom Button Handlers
        if (lbZoomIn) lbZoomIn.addEventListener('click', (e) => {
            e.stopPropagation();
            zoomIn(0.4);
        });

        if (lbZoomOut) lbZoomOut.addEventListener('click', (e) => {
            e.stopPropagation();
            zoomOut(0.4);
        });

        if (lbZoomReset) lbZoomReset.addEventListener('click', (e) => {
            e.stopPropagation();
            resetZoom();
        });

        // Fullscreen Toggle
        if (lbFullscreenBtn) lbFullscreenBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!document.fullscreenElement) {
                lightbox.requestFullscreen?.().catch(() => {});
                if (lbFsIcon) lbFsIcon.className = 'fa-solid fa-compress';
            } else {
                document.exitFullscreen?.().catch(() => {});
                if (lbFsIcon) lbFsIcon.className = 'fa-solid fa-expand';
            }
        });

        // Double-click to Toggle Zoom
        if (lbViewport) {
            lbViewport.addEventListener('dblclick', (e) => {
                e.preventDefault();
                toggleZoom();
            });

            // Mouse Wheel Zoom
            lbViewport.addEventListener('wheel', (e) => {
                e.preventDefault();
                if (e.deltaY < 0) {
                    zoomIn(0.25);
                } else {
                    zoomOut(0.25);
                }
            }, { passive: false });

            // Mouse Drag Pan when Zoomed
            lbViewport.addEventListener('mousedown', (e) => {
                if (scale <= 1.05) return;
                isDragging = true;
                dragStartX = e.clientX;
                dragStartY = e.clientY;
                initialTranslateX = translateX;
                initialTranslateY = translateY;
                updateTransform(false);
            });

            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                const dx = e.clientX - dragStartX;
                const dy = e.clientY - dragStartY;
                translateX = initialTranslateX + dx;
                translateY = initialTranslateY + dy;
                updateTransform(false);
            });

            window.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    updateTransform(true);
                }
            });

            // Touch Drag Pan for Mobile & Tablets
            let touchStartX = 0;
            let touchStartY = 0;
            lbViewport.addEventListener('touchstart', (e) => {
                if (e.touches.length === 1 && scale > 1.05) {
                    isDragging = true;
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    initialTranslateX = translateX;
                    initialTranslateY = translateY;
                }
            }, { passive: true });

            lbViewport.addEventListener('touchmove', (e) => {
                if (isDragging && e.touches.length === 1) {
                    const dx = e.touches[0].clientX - touchStartX;
                    const dy = e.touches[0].clientY - touchStartY;
                    translateX = initialTranslateX + dx;
                    translateY = initialTranslateY + dy;
                    updateTransform(false);
                }
            }, { passive: true });

            lbViewport.addEventListener('touchend', () => {
                if (isDragging) {
                    isDragging = false;
                    updateTransform(true);
                }
            }, { passive: true });
        }

        // Global Keyboard Controls
        window.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;

            switch (e.key) {
                case 'Escape':
                    closeLightbox();
                    break;
                case 'ArrowRight':
                    renderPhoto(currentPhotoIdx + 1, true);
                    break;
                case 'ArrowLeft':
                    renderPhoto(currentPhotoIdx - 1, true);
                    break;
                case '+':
                case '=':
                    zoomIn(0.4);
                    break;
                case '-':
                case '_':
                    zoomOut(0.4);
                    break;
                case '0':
                    resetZoom();
                    break;
                case 'f':
                case 'F':
                    lbFullscreenBtn?.click();
                    break;
            }
        });

        // Filter Tabs for Dedicated Gallery Page
        const filterBtns = document.querySelectorAll('.gallery-filter-btn');
        const galleryGrid = document.getElementById('gallery-grid');
        if (filterBtns.length > 0 && galleryGrid) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const filter = btn.getAttribute('data-filter') || 'all';
                    const allCards = galleryGrid.querySelectorAll('.gallery-card');

                    allCards.forEach(card => {
                        const cardCat = card.getAttribute('data-category') || '';
                        if (filter === 'all' || cardCat === filter) {
                            card.style.display = 'block';
                            setTimeout(() => { card.style.opacity = '1'; card.style.transform = 'translateY(0)'; }, 50);
                        } else {
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(15px)';
                            setTimeout(() => { card.style.display = 'none'; }, 250);
                        }
                    });
                });
            });
        }
    }
    initGalleryCollections();
});

