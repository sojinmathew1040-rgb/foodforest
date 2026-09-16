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
        const maxRoomGuests = parseInt(selectedOption?.getAttribute('data-max-guests') || "4", 10);
        const baseGuests = parseInt(selectedOption?.getAttribute('data-base-guests') || "2", 10);
        const structureType = selectedOption?.getAttribute('data-structure-type') || 'single_hut';
        const structLabel = (structureType === 'duplex_hut') ? 'Duplex Cottage' : 'Single Hut';

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
        updateStepperButtons();
        recalculateBookingSummary();
    }

    // Price Calculation
    function recalculateBookingSummary() {
        if (!modalCheckin || !modalCheckout || !modalVillaSelect) return;

        const checkinDate = new Date(modalCheckin.value);
        const checkoutDate = new Date(modalCheckout.value);
        let nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        if (isNaN(nights) || nights < 1) nights = 1;

        const selectedOption = modalVillaSelect.options[modalVillaSelect.selectedIndex];
        if (!selectedOption) return;

        const villaPrice = parseFloat(selectedOption.getAttribute('data-price') || "14500");
        const villaName = selectedOption.getAttribute('data-name') || "Luxury Canopy Treehouse";
        const baseGuests = parseInt(selectedOption.getAttribute('data-base-guests') || "2", 10);
        const maxGuests = parseInt(selectedOption.getAttribute('data-max-guests') || "4", 10);
        const extraAdultRate = parseFloat(selectedOption.getAttribute('data-extra-rate') || "1500");
        const extraChildRate = parseFloat(selectedOption.getAttribute('data-extra-child-rate') || "800");
        const stayType = selectedOption.getAttribute('data-stay-type') || "treehouse";

        const adultsCount = parseInt(modalAdultsInput?.value || "2", 10);
        const kidsCount = parseInt(modalKidsInput?.value || "0", 10);
        const guestsCount = adultsCount + kidsCount;
        if (modalGuestsSelect) modalGuestsSelect.value = guestsCount;

        // Dual Adult & Child Occupancy Math:
        // Adults fill base slots first
        const adultsInBase = Math.min(adultsCount, baseGuests);
        const extraAdults = Math.max(0, adultsCount - adultsInBase);
        const remBaseSlots = Math.max(0, baseGuests - adultsInBase);
        const kidsInBase = Math.min(kidsCount, remBaseSlots);
        const extraKids = Math.max(0, kidsCount - kidsInBase);

        const extraAdultsTotal = extraAdults * extraAdultRate * nights;
        const extraKidsTotal = extraKids * extraChildRate * nights;
        const baseVillaTotal = villaPrice * nights;

        // Addons total
        let addonsTotal = 0;
        document.querySelectorAll('.addon-checkbox:checked').forEach(addon => {
            addonsTotal += parseInt(addon.getAttribute('data-price') || "0", 10);
        });

        // ---------------------------------------------------------
        // Curated Food Menu Selection Calculation
        // ---------------------------------------------------------
        let foodTotal = 0;
        let foodSetsCount = 0;
        const isAllFoodSkipped = document.getElementById('toggle-skip-all-food')?.checked || false;

        if (!isAllFoodSkipped) {
            document.querySelectorAll('.modal-dish-card').forEach(card => {
                const qtyInput = card.querySelector('.dish-qty-input');
                const qty = parseInt(qtyInput?.value || "0", 10);
                const price = parseFloat(card.getAttribute('data-dish-price') || "0");
                if (qty > 0) {
                    foodTotal += (qty * price);
                    foodSetsCount += qty;
                }
            });
        }

        const stayTotal = baseVillaTotal + extraAdultsTotal + extraKidsTotal + addonsTotal + foodTotal;

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
            if (foodTotal > 0) {
                summaryFoodLine.style.display = 'flex';
                if (summaryFoodLabel) {
                    summaryFoodLabel.innerText = `Curated Gastronomy (${foodSetsCount} Sets):`;
                }
                summaryFoodRate.innerText = `+₹${foodTotal.toLocaleString('en-IN')}`;
            } else {
                summaryFoodLine.style.display = 'none';
            }
        }

        // Hide legacy line
        if (summaryExtraGuestsLine) summaryExtraGuestsLine.style.display = 'none';

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

    if (modalVillaSelect) modalVillaSelect.addEventListener('change', onModalVillaChange);
    if (modalGuestsSelect) modalGuestsSelect.addEventListener('change', recalculateBookingSummary);
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
            if (!pwdBox) return;
            if (this.value === 'create_account') {
                pwdBox.style.display = 'block';
                document.getElementById('modal-password')?.setAttribute('required', 'required');
            } else {
                pwdBox.style.display = 'none';
                document.getElementById('modal-password')?.removeAttribute('required');
            }
        });
    });

    // -------------------------------------------------------------
    // 7. Instant WhatsApp & Direct Reservation Submission
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

    function collectBookingPayload() {
        const guestName = document.getElementById('modal-name')?.value.trim() || '';
        const guestPhone = document.getElementById('modal-phone')?.value.trim() || '';
        const guestEmail = document.getElementById('modal-email')?.value.trim() || '';
        const guestNotes = document.getElementById('modal-notes')?.value.trim() || '';
        const villaSlug = modalVillaSelect?.value || 'treehouse';

        const adultsCount = parseInt(modalAdultsInput?.value || "2", 10);
        const kidsCount = parseInt(modalKidsInput?.value || "0", 10);
        const guestsCount = adultsCount + kidsCount;

        const checkin = modalCheckin?.value || '';
        const checkout = modalCheckout?.value || '';

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
                if (qty > 0) {
                    foodItems.push({
                        id: card.getAttribute('data-dish-id'),
                        category: card.getAttribute('data-dish-category'),
                        heading: card.getAttribute('data-dish-name'),
                        subtitle: card.getAttribute('data-dish-subtitle'),
                        price: parseFloat(card.getAttribute('data-dish-price') || "0"),
                        quantity: qty,
                        subtotal: qty * parseFloat(card.getAttribute('data-dish-price') || "0")
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
            villa: villaSlug,
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
            const villaName = selectedOption?.getAttribute('data-name') || 'Sanctuary Suite';
            const total = document.getElementById('summary-total')?.innerText || '₹14,500';

            let foodSummary = 'Farm À La Carte on Arrival (Skipped)';
            if (payload.food_items && payload.food_items.length > 0) {
                foodSummary = payload.food_items.map(f => `${f.heading} (${f.quantity} sets)`).join(', ');
            }

            const message = `🌿 *RESERVATION REQUEST — FOOD FOREST KANTHALLOOR* 🌿\n\n` +
                `• *Booking Reference*: #${refCode}\n` +
                `• *Guest Name*: ${payload.name}\n` +
                `• *Contact*: ${payload.phone}\n` +
                `• *Suite*: ${villaName}\n` +
                `• *Check-in*: ${payload.checkin}\n` +
                `• *Check-out*: ${payload.checkout}\n` +
                `• *Occupancy*: ${payload.adults} Adults, ${payload.kids} Children\n` +
                `• *Curated Gastronomy*: ${foodSummary}\n` +
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
