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
    // 2.2 Navigation Scrollspy & Active Section Highlighting
    // -------------------------------------------------------------
    function initScrollSpy() {
        const navItems = document.querySelectorAll('.nav-links .nav-item, .mobile-menu-links .mobile-link');
        if (!navItems.length) return;

        // Extract sections from navigation links
        const sectionMap = [];
        navItems.forEach(item => {
            const href = item.getAttribute('href') || '';
            const hashIndex = href.indexOf('#');
            if (hashIndex !== -1) {
                const id = href.substring(hashIndex + 1);
                const target = document.getElementById(id);
                if (target && !sectionMap.some(s => s.id === id)) {
                    sectionMap.push({ id, el: target });
                }
            }
        });

        if (!sectionMap.length) return;

        let ticking = false;

        function updateActiveSection() {
            const scrollPos = window.scrollY || window.pageYOffset || document.documentElement.scrollTop;
            const triggerOffset = scrollPos + 160; // Offset for header height and comfortable viewport threshold
            const docHeight = document.documentElement.scrollHeight;
            const winHeight = window.innerHeight;
            const isAtBottom = (scrollPos + winHeight) >= (docHeight - 60);

            let activeId = null;

            if (isAtBottom) {
                // If user reached bottom of page, highlight the last section
                activeId = sectionMap[sectionMap.length - 1].id;
            } else if (scrollPos < 100) {
                // Near very top (Hero section)
                activeId = null;
            } else {
                for (let i = sectionMap.length - 1; i >= 0; i--) {
                    const sec = sectionMap[i];
                    const top = sec.el.offsetTop;
                    if (triggerOffset >= top) {
                        activeId = sec.id;
                        break;
                    }
                }
            }

            navItems.forEach(item => {
                const href = item.getAttribute('href') || '';
                const hashIndex = href.indexOf('#');
                if (hashIndex !== -1) {
                    const id = href.substring(hashIndex + 1);
                    if (activeId && id === activeId) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                }
            });

            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateActiveSection);
                ticking = true;
            }
        }

        if (lenis) {
            lenis.on('scroll', requestTick);
        }
        window.addEventListener('scroll', requestTick, { passive: true });
        window.addEventListener('resize', requestTick, { passive: true });
        
        // Initial invocation
        updateActiveSection();

        // Smooth Scrolling on Link Click
        navItems.forEach(link => {
            const href = link.getAttribute('href') || '';
            const hashIndex = href.indexOf('#');
            if (hashIndex !== -1) {
                const targetId = href.substring(hashIndex + 1);
                const targetEl = document.getElementById(targetId);
                if (targetEl) {
                    link.addEventListener('click', (e) => {
                        const isHashOnly = href.startsWith('#');
                        const isIndexHash = href.startsWith('index.php#') && (window.location.pathname.endsWith('index.php') || window.location.pathname.endsWith('/') || window.location.pathname.indexOf('.php') === -1);
                        
                        if (isHashOnly || isIndexHash) {
                            e.preventDefault();
                            
                            // Close mobile menu if active
                            const mobileMenu = document.querySelector('.mobile-menu');
                            const navToggle = document.querySelector('.mobile-nav-toggle');
                            if (mobileMenu && mobileMenu.classList.contains('active')) {
                                mobileMenu.classList.remove('active');
                                if (navToggle) navToggle.classList.remove('active');
                                document.body.style.overflow = '';
                            }

                            // Update active class immediately on click
                            navItems.forEach(item => {
                                const iHref = item.getAttribute('href') || '';
                                if (iHref.endsWith('#' + targetId)) {
                                    item.classList.add('active');
                                } else {
                                    item.classList.remove('active');
                                }
                            });

                            // Smooth scroll
                            if (lenis) {
                                lenis.scrollTo(targetEl, { offset: -70, duration: 1.2 });
                            } else {
                                const targetY = targetEl.getBoundingClientRect().top + window.pageYOffset - 70;
                                window.scrollTo({ top: targetY, behavior: 'smooth' });
                            }

                            // Update history URL hash cleanly without jumping
                            if (history.pushState) {
                                history.pushState(null, null, '#' + targetId);
                            }
                        }
                    });
                }
            }
        });
    }
    initScrollSpy();

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
                minGuests = 1;
            } else {
                baseGuests = parseInt(selectedOption?.getAttribute('data-base-guests') || "4", 10);
                maxRoomGuests = parseInt(selectedOption?.getAttribute('data-max-guests') || "8", 10);
                minGuests = parseInt(selectedOption?.getAttribute('data-min-guests') || "2", 10);
            }
        }

        const maxAdultsAllowed = (isDuplex && modalTier !== 'single_room') ? 8 : 4;

        let structLabel = 'Single Cottage';
        if (isDuplex) {
            structLabel = (modalTier === 'single_room') ? 'Duplex (Single Room Suite)' : 'Entire Duplex (Both Suites)';
        }

        let currentAdults = parseInt(modalAdultsInput?.value || "2", 10);
        let currentKids = parseInt(modalKidsInput?.value || "0", 10);

        // Clamping bounds
        if (currentAdults < 1) currentAdults = 1;
        if (currentKids < 0) currentKids = 0;
        if (currentAdults > maxAdultsAllowed) {
            currentAdults = maxAdultsAllowed;
        }
        if (currentAdults + currentKids > maxRoomGuests) {
            currentKids = Math.max(0, maxRoomGuests - currentAdults);
            if (currentAdults > maxRoomGuests) {
                currentAdults = Math.min(maxAdultsAllowed, maxRoomGuests);
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
            noteEl.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${structLabel} • Base: ${baseGuests} Adults Included (Max ${maxAdultsAllowed} Adults) • Extra Person ₹750/nt • Kids below 10 Free`;
        }

        // Stepper button disabled states
        const adultsMinus = document.querySelector('.btn-stepper-minus[data-target="modal-adults"]');
        const adultsPlus = document.querySelector('.btn-stepper-plus[data-target="modal-adults"]');
        if (adultsMinus) adultsMinus.disabled = (currentAdults <= 1);
        if (adultsPlus) adultsPlus.disabled = (currentAdults >= maxAdultsAllowed || totalGuests >= maxRoomGuests);

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
            const structureType = selectedOption?.getAttribute('data-structure-type') || 'single_hut';
            const isDuplex = (structureType === 'duplex_hut');
            const selectedTierRadio = document.querySelector('input[name="modal_tier"]:checked');
            const modalTier = selectedTierRadio ? selectedTierRadio.value : 'full';
            const maxRoomGuests = (isDuplex && modalTier === 'single_room') ? 4 : parseInt(selectedOption?.getAttribute('data-max-guests') || "4", 10);
            const maxAdultsAllowed = (isDuplex && modalTier !== 'single_room') ? 8 : 4;

            let currentAdults = parseInt(modalAdultsInput?.value || "2", 10);
            let currentKids = parseInt(modalKidsInput?.value || "0", 10);

            if (targetId === 'modal-adults') {
                if (isPlus) {
                    if (currentAdults < maxAdultsAllowed && currentAdults + currentKids < maxRoomGuests) {
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
            const extraRate = parseFloat(opt.getAttribute('data-extra-rate') || "750");
            updateGuestOptions(heroGuestsSelect, baseGuests, maxGuests, extraRate, heroGuestsSelect.value);
        }
        heroVillaSelect.addEventListener('change', onHeroVillaChange);
        onHeroVillaChange();
    }

    function openBookingModal(preferredVilla) {
        if (!bookingModal) return;
        
        if (preferredVilla && modalVillaSelect) {
            let matched = false;
            for (let i = 0; i < modalVillaSelect.options.length; i++) {
                const optVal = modalVillaSelect.options[i].value.toLowerCase();
                const prefVal = preferredVilla.toLowerCase();
                if (optVal === prefVal || optVal.includes(prefVal) || prefVal.includes(optVal)) {
                    modalVillaSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                modalVillaSelect.value = preferredVilla;
            }
        } else if (document.getElementById('hero-villa') && modalVillaSelect && document.getElementById('hero-villa').value) {
            modalVillaSelect.value = document.getElementById('hero-villa').value;
        }

        if (modalVillaSelect && (!modalVillaSelect.value || modalVillaSelect.selectedIndex === -1)) {
            modalVillaSelect.selectedIndex = 0;
        }

        const heroGuestVal = document.getElementById('hero-guests') ? parseInt(document.getElementById('hero-guests').value, 10) : 2;
        if (modalAdultsInput && !modalAdultsInput.dataset.userEdited) {
            modalAdultsInput.value = Math.max(1, heroGuestVal);
            if (modalKidsInput) modalKidsInput.value = 0;
        }

        const selectWrapper = document.getElementById('modal-villa-select-wrapper');
        if (selectWrapper) selectWrapper.style.display = 'none';

        if (typeof onModalVillaChange === 'function') {
            onModalVillaChange(false);
        } else {
            updateStepperButtons();
            recalculateBookingSummary();
            checkLiveModalAvailability(false);
        }
        if (typeof resetBookingModalState === 'function') {
            resetBookingModalState();
        }
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

        const confirmBox = document.getElementById('booking-confirmation-state');
        if (confirmBox && confirmBox.style.display !== 'none') {
            if (typeof resetBookingModalState === 'function') {
                resetBookingModalState();
            }
        }
    }
    window.closeBookingModal = closeBookingModal;

    // -------------------------------------------------------------
    // 6.1 Real-Time Live Availability Engine & Conflict Alerts
    // -------------------------------------------------------------
    let liveAvailAbortController = null;
    let isCurrentVillaAvailable = true;

    async function checkLiveModalAvailability(triggerPopupOnConflict = false) {
        if (!modalCheckin || !modalCheckout || !modalVillaSelect) return;

        const cin = modalCheckin.value;
        const cout = modalCheckout.value;
        const villa = modalVillaSelect.value;

        if (!cin || !cout) return;

        const availBox = document.getElementById('modal-availability-box');
        const loadingEl = document.getElementById('modal-avail-loading');
        const successEl = document.getElementById('modal-avail-success');
        const conflictEl = document.getElementById('modal-avail-conflict');
        const availTitle = document.getElementById('modal-avail-title');
        const availDesc = document.getElementById('modal-avail-desc');
        const conflictTitle = document.getElementById('modal-conflict-title');
        const conflictDesc = document.getElementById('modal-conflict-desc');
        const altBox = document.getElementById('modal-alternate-chalets');
        const altPillsWrap = document.getElementById('modal-alternate-pills');
        const submitDirectBtn = document.getElementById('btn-submit-booking-direct');
        const submitWaBtn = document.getElementById('btn-submit-whatsapp');

        if (availBox) {
            availBox.style.display = 'block';
            availBox.style.background = '#F8FAF8';
            availBox.style.borderColor = 'rgba(197, 160, 89, 0.35)';
        }
        if (loadingEl) loadingEl.style.display = 'flex';
        if (successEl) successEl.style.display = 'none';
        if (conflictEl) conflictEl.style.display = 'none';

        if (liveAvailAbortController) {
            liveAvailAbortController.abort();
        }
        liveAvailAbortController = new AbortController();

        try {
            const selectedTierRadio = document.querySelector('input[name="modal_tier"]:checked');
            const modalTier = selectedTierRadio ? selectedTierRadio.value : 'full';
            let duplexUnit = 'full';
            if (modalTier === 'single_room') {
                const wingRadio = document.querySelector('input[name="modal_duplex_wing"]:checked');
                duplexUnit = wingRadio ? wingRadio.value : 'left';
            }

            const url = `api/check_availability.php?checkin=${encodeURIComponent(cin)}&checkout=${encodeURIComponent(cout)}&villa=${encodeURIComponent(villa)}&duplex_unit=${encodeURIComponent(duplexUnit)}`;
            const res = await fetch(url, { signal: liveAvailAbortController.signal });
            const data = await res.json();

            if (!data || !data.success) {
                if (loadingEl) loadingEl.style.display = 'none';
                return;
            }

            if (loadingEl) loadingEl.style.display = 'none';

            // Handle Duplex Partial Booking state in Modal
            const labelFull = document.getElementById('modal-tier-label-full');
            const radioFull = document.querySelector('input[name="modal_tier"][value="full"]');
            const radioSingle = document.querySelector('input[name="modal_tier"][value="single_room"]');
            const duplexWingBox = document.getElementById('modal-duplex-wing-box');
            const wingLabelLeft = document.getElementById('modal-wing-label-left');
            const wingLabelRight = document.getElementById('modal-wing-label-right');
            const wingRadioLeft = document.querySelector('input[name="modal_duplex_wing"][value="left"]');
            const wingRadioRight = document.querySelector('input[name="modal_duplex_wing"][value="right"]');
            const wingBadgeLeft = document.getElementById('modal-wing-badge-left');
            const wingBadgeRight = document.getElementById('modal-wing-badge-right');
            const wingHint = document.getElementById('modal-wing-status-hint');

            if (data.partially_booked) {
                if (labelFull) {
                    labelFull.style.opacity = '0.45';
                    labelFull.style.cursor = 'not-allowed';
                }
                if (radioFull) radioFull.disabled = true;

                // Auto switch to single suite if on full
                if (radioFull && radioFull.checked) {
                    if (radioSingle) {
                        radioSingle.checked = true;
                        radioSingle.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                if (duplexWingBox) duplexWingBox.style.display = 'block';

                if (data.left_available === false) {
                    if (wingLabelLeft) {
                        wingLabelLeft.classList.add('disabled');
                        wingLabelLeft.classList.remove('is-selected');
                    }
                    if (wingRadioLeft) wingRadioLeft.disabled = true;
                    if (wingBadgeLeft) {
                        wingBadgeLeft.className = 'wing-badge booked';
                        wingBadgeLeft.innerText = 'Booked';
                    }
                    if (wingRadioLeft && wingRadioLeft.checked && wingRadioRight && !wingRadioRight.disabled) {
                        wingRadioRight.checked = true;
                        wingRadioRight.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else {
                    if (wingLabelLeft) {
                        wingLabelLeft.classList.remove('disabled');
                    }
                    if (wingRadioLeft) wingRadioLeft.disabled = false;
                    if (wingBadgeLeft) {
                        wingBadgeLeft.className = 'wing-badge available';
                        wingBadgeLeft.innerText = 'Available';
                    }
                }

                if (data.right_available === false) {
                    if (wingLabelRight) {
                        wingLabelRight.classList.add('disabled');
                        wingLabelRight.classList.remove('is-selected');
                    }
                    if (wingRadioRight) wingRadioRight.disabled = true;
                    if (wingBadgeRight) {
                        wingBadgeRight.className = 'wing-badge booked';
                        wingBadgeRight.innerText = 'Booked';
                    }
                    if (wingRadioRight && wingRadioRight.checked && wingRadioLeft && !wingRadioLeft.disabled) {
                        wingRadioLeft.checked = true;
                        wingRadioLeft.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else {
                    if (wingLabelRight) {
                        wingLabelRight.classList.remove('disabled');
                    }
                    if (wingRadioRight) wingRadioRight.disabled = false;
                    if (wingBadgeRight) {
                        wingBadgeRight.className = 'wing-badge available';
                        wingBadgeRight.innerText = 'Available';
                    }
                }

                if (wingHint) {
                    wingHint.innerText = !data.left_available ? 'Right Suite Available' : 'Left Suite Available';
                    wingHint.style.background = '#FEF3C7';
                    wingHint.style.color = '#92400E';
                    wingHint.style.border = '1px solid #FCD34D';
                }
            } else if (data.is_available) {
                if (labelFull) {
                    labelFull.style.opacity = '1';
                    labelFull.style.cursor = 'pointer';
                }
                if (radioFull) radioFull.disabled = false;
                if (wingLabelLeft) {
                    wingLabelLeft.classList.remove('disabled');
                }
                if (wingRadioLeft) wingRadioLeft.disabled = false;
                if (wingBadgeLeft) {
                    wingBadgeLeft.className = 'wing-badge available';
                    wingBadgeLeft.innerText = 'Available';
                }
                if (wingLabelRight) {
                    wingLabelRight.classList.remove('disabled');
                }
                if (wingRadioRight) wingRadioRight.disabled = false;
                if (wingBadgeRight) {
                    wingBadgeRight.className = 'wing-badge available';
                    wingBadgeRight.innerText = 'Available';
                }
                if (wingHint) {
                    wingHint.innerText = 'Both Wings Available';
                    wingHint.style.background = '#DCFCE7';
                    wingHint.style.color = '#166534';
                    wingHint.style.border = '1px solid #86EFAC';
                }
            }

            // 1. Update dropdown options visual status
            if (modalVillaSelect && data.rooms_status) {
                Array.from(modalVillaSelect.options).forEach(opt => {
                    const optVal = opt.value.toLowerCase();
                    let optMatch = data.rooms_status[optVal];
                    if (!optMatch) {
                        for (const k in data.rooms_status) {
                            if (optVal.includes(k) || k.includes(optVal) ||
                                (optVal.includes('treehouse') && k.includes('treehouse')) ||
                                (optVal.includes('mudhouse') && k.includes('mudhouse')) ||
                                (optVal.includes('woodhouse') && k.includes('woodhouse'))) {
                                optMatch = data.rooms_status[k];
                                break;
                            }
                        }
                    }

                    // Strip existing [⛔ RESERVED] prefix
                    let baseText = opt.text.replace(/\[⛔ RESERVED FOR DATES\]\s*/g, '');
                    if (optMatch && !optMatch.available) {
                        opt.text = `[⛔ RESERVED FOR DATES] ` + baseText;
                        opt.style.color = '#DC2626';
                    } else {
                        opt.text = baseText;
                        opt.style.color = '';
                    }
                });
            }

            // 2. Determine if currently chosen villa is available
            isCurrentVillaAvailable = data.is_available;

            const selectedOption = modalVillaSelect.options[modalVillaSelect.selectedIndex];
            const currentTitle = data.requested_room_title || selectedOption?.getAttribute('data-name') || 'This Chalet';

            // Find available alternatives
            const availableAlts = [];
            if (data.rooms_status) {
                for (const k in data.rooms_status) {
                    const rData = data.rooms_status[k];
                    if (rData.available && rData.slug !== data.requested_villa) {
                        availableAlts.push(rData);
                    }
                }
            }

            if (data.is_available) {
                // Available state
                if (availBox) {
                    availBox.style.background = 'rgba(16, 185, 129, 0.08)';
                    availBox.style.borderColor = 'rgba(16, 185, 129, 0.4)';
                }
                if (successEl) successEl.style.display = 'flex';
                if (conflictEl) conflictEl.style.display = 'none';

                if (availTitle) availTitle.innerText = `${currentTitle} is Available!`;
                if (availDesc) availDesc.innerText = `Great news! This sanctuary suite is fully available for your selected stay (${data.nights} ${data.nights === 1 ? 'Night' : 'Nights'}: ${data.checkin_formatted} – ${data.checkout_formatted}).`;

                // Re-enable buttons
                if (submitDirectBtn) {
                    submitDirectBtn.disabled = false;
                    submitDirectBtn.style.opacity = '1';
                    submitDirectBtn.style.cursor = 'pointer';
                    submitDirectBtn.innerHTML = '<i class="fa-solid fa-receipt"></i> <span>Confirm Reservation & Generate PDF Receipt</span>';
                }
                if (submitWaBtn) {
                    submitWaBtn.disabled = false;
                    submitWaBtn.style.opacity = '1';
                    submitWaBtn.style.cursor = 'pointer';
                }
            } else {
                // Booked conflict state
                if (availBox) {
                    availBox.style.background = '#FEF2F2';
                    availBox.style.borderColor = '#F87171';
                }
                if (successEl) successEl.style.display = 'none';
                if (conflictEl) conflictEl.style.display = 'flex';

                if (conflictTitle) conflictTitle.innerText = `${currentTitle} is Already Reserved`;
                if (conflictDesc) conflictDesc.innerText = data.message || `We apologize, but ${currentTitle} has already been reserved for your selected stay dates. Please choose alternative dates or switch to another available chalet.`;

                // Render alternative chalet switch buttons
                if (altPillsWrap) altPillsWrap.innerHTML = '';
                if (availableAlts.length > 0) {
                    if (altBox) altBox.style.display = 'block';
                    availableAlts.forEach(alt => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn-switch-alt-chalet font-sans';
                        btn.innerHTML = `<i class="fa-solid fa-arrow-right-arrow-left"></i> Switch to ${alt.title}`;
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            modalVillaSelect.value = alt.slug;
                            onModalVillaChange(false);
                            closeRealtimeConflictAlert();
                        });
                        altPillsWrap.appendChild(btn);
                    });
                } else {
                    if (altBox) altBox.style.display = 'none';
                }

                // Update submit buttons state
                if (submitDirectBtn) {
                    submitDirectBtn.disabled = true;
                    submitDirectBtn.style.opacity = '0.75';
                    submitDirectBtn.style.cursor = 'not-allowed';
                    submitDirectBtn.innerHTML = '<i class="fa-solid fa-calendar-xmark"></i> <span>Chalet Reserved for Selected Dates (Change Dates)</span>';
                }

                // Show dynamic modal alert popup if requested
                if (triggerPopupOnConflict) {
                    showRealtimeConflictAlert(
                        `${currentTitle} Already Reserved`,
                        data.message || `We apologize, but ${currentTitle} has already been reserved for the selected dates (via Direct Website, MakeMyTrip, or Airbnb). Please select alternative dates.`,
                        availableAlts
                    );
                }
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Availability check error:', err);
            }
        }
    }

    // Interactive Real-Time Conflict Alert Modal Controls
    function showRealtimeConflictAlert(title, message, alternatives = []) {
        const modal = document.getElementById('realtime-conflict-modal');
        if (!modal) return;

        const titleEl = document.getElementById('rt-alert-title');
        const msgEl = document.getElementById('rt-alert-msg');
        const altContainer = document.getElementById('rt-alert-alternatives');
        const altList = document.getElementById('rt-alert-alt-list');

        if (titleEl) titleEl.innerText = title;
        if (msgEl) msgEl.innerText = message;

        if (altList && altContainer) {
            altList.innerHTML = '';
            if (alternatives && alternatives.length > 0) {
                altContainer.style.display = 'block';
                alternatives.forEach(alt => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn-switch-alt-chalet font-sans';
                    btn.style.width = '100%';
                    btn.style.justifyContent = 'space-between';
                    btn.style.padding = '8px 12px';
                    btn.innerHTML = `<span><strong>${alt.title}</strong> is available</span> <span><i class="fa-solid fa-arrow-right"></i> Select</span>`;
                    btn.addEventListener('click', () => {
                        if (modalVillaSelect) {
                            modalVillaSelect.value = alt.slug;
                            onModalVillaChange(false);
                        }
                        closeRealtimeConflictAlert();
                    });
                    altList.appendChild(btn);
                });
            } else {
                altContainer.style.display = 'none';
            }
        }

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeRealtimeConflictAlert() {
        const modal = document.getElementById('realtime-conflict-modal');
        if (modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    const rtAlertCloseBtn = document.getElementById('rt-alert-close-btn');
    if (rtAlertCloseBtn) {
        rtAlertCloseBtn.addEventListener('click', closeRealtimeConflictAlert);
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

    function onModalVillaChange(triggerPopup = true) {
        if (!modalVillaSelect) return;
        const selectedOption = modalVillaSelect.options[modalVillaSelect.selectedIndex];
        const structureType = selectedOption?.getAttribute('data-structure-type') || 'single_hut';
        const isDuplex = (structureType === 'duplex_hut');
        
        const duplexTierBox = document.getElementById('modal-duplex-tier-box');
        const duplexWingBox = document.getElementById('modal-duplex-wing-box');
        if (duplexTierBox) {
            if (isDuplex) {
                duplexTierBox.style.display = 'block';
                const fullPrice = parseFloat(selectedOption.getAttribute('data-price') || "8000");
                const singlePrice = parseFloat(selectedOption.getAttribute('data-single-rate') || "4000");
                const fullTxt = document.getElementById('modal-duplex-full-rate-txt');
                const singleTxt = document.getElementById('modal-duplex-single-rate-txt');
                if (fullTxt) fullTxt.innerText = `₹${fullPrice.toLocaleString('en-IN')}/nt`;
                if (singleTxt) singleTxt.innerText = `₹${singlePrice.toLocaleString('en-IN')}/nt`;
            } else {
                duplexTierBox.style.display = 'none';
                if (duplexWingBox) duplexWingBox.style.display = 'none';
                const fullRadio = document.querySelector('input[name="modal_tier"][value="full"]');
                if (fullRadio) fullRadio.checked = true;
            }
        }

        // Update Rich Selected Chalet Card UI
        const chaletTitleEl = document.getElementById('modal-chalet-card-title');
        const chaletThumbEl = document.getElementById('modal-chalet-card-thumb');
        const mscTypePill = document.getElementById('msc-type-pill');
        const mscStructPill = document.getElementById('msc-struct-pill');
        const mscBaseGuestsPill = document.getElementById('msc-base-guests-pill');
        const mscRatePill = document.getElementById('msc-rate-pill');

        if (selectedOption) {
            let villaTitle = selectedOption.getAttribute('data-name') || selectedOption.text.split('(')[0].replace(/^.*]:\s*/, '').trim();
            let villaImg = selectedOption.getAttribute('data-image');
            const stayType = selectedOption.getAttribute('data-stay-type') || 'treehouse';
            const baseGuests = parseInt(selectedOption.getAttribute('data-base-guests') || "2", 10);
            const rate = parseFloat(selectedOption.getAttribute('data-price') || "5000");
            const singleRate = parseFloat(selectedOption.getAttribute('data-single-rate') || rate);
            
            const selectedTierRadio = document.querySelector('input[name="modal_tier"]:checked');
            const modalTier = selectedTierRadio ? selectedTierRadio.value : 'full';

            if (isDuplex && modalTier === 'single_room') {
                if (duplexWingBox) duplexWingBox.style.display = 'block';
                const wingRadio = document.querySelector('input[name="modal_duplex_wing"]:checked');
                const wingVal = wingRadio ? wingRadio.value : 'left';
                let wingPhotos = [];
                try {
                    const photosAttr = (wingVal === 'left') ? selectedOption.getAttribute('data-photos-left') : selectedOption.getAttribute('data-photos-right');
                    if (photosAttr) wingPhotos = JSON.parse(photosAttr);
                } catch(e) {}
                if (wingPhotos && wingPhotos.length > 0) {
                    villaImg = wingPhotos[0];
                }
                villaTitle += (wingVal === 'left') ? ' (Left Suite - Wing A)' : ' (Right Suite - Wing B)';
            } else if (isDuplex) {
                if (duplexWingBox && !duplexWingBox.hasAttribute('data-force-show')) {
                    duplexWingBox.style.display = 'none';
                }
            } else {
                if (duplexWingBox) duplexWingBox.style.display = 'none';
            }

            if (chaletTitleEl) chaletTitleEl.innerText = villaTitle;
            if (chaletThumbEl && villaImg) chaletThumbEl.src = villaImg;
            if (mscTypePill) {
                if (stayType === 'mudhouse') {
                    mscTypePill.innerHTML = '<i class="fa-solid fa-seedling"></i> Earthen Mudhouse';
                } else if (stayType === 'woodhouse') {
                    mscTypePill.innerHTML = '<i class="fa-solid fa-tree"></i> Alpine Woodhouse';
                } else {
                    mscTypePill.innerHTML = '<i class="fa-solid fa-tree"></i> Canopy Treehouse';
                }
            }
            if (mscStructPill) {
                if (isDuplex) {
                    mscStructPill.innerText = (modalTier === 'single_room') ? 'Duplex (Single Suite)' : 'Duplex (Entire 2-Room Suite)';
                } else {
                    mscStructPill.innerText = 'Single Cottage';
                }
            }
            if (mscBaseGuestsPill) {
                const effectiveBase = isDuplex && (modalTier === 'single_room') ? 2 : baseGuests;
                mscBaseGuestsPill.innerHTML = `<i class="fa-solid fa-users"></i> Base: ${effectiveBase} Guests Included`;
            }
            if (mscRatePill) {
                const effectiveRate = isDuplex && (modalTier === 'single_room') ? singleRate : rate;
                mscRatePill.innerHTML = `<i class="fa-solid fa-tag"></i> <strong>₹${effectiveRate.toLocaleString('en-IN')}</strong> / nt`;
            }
        }

        updateStepperButtons();
        recalculateBookingSummary();
        checkLiveModalAvailability(triggerPopup);
    }

    // Modal Duplex Tier Radio Buttons Listener
    document.querySelectorAll('input[name="modal_tier"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const labelFull = document.getElementById('modal-tier-label-full');
            const labelSingle = document.getElementById('modal-tier-label-single');
            const duplexWingBox = document.getElementById('modal-duplex-wing-box');
            if (radio.value === 'full') {
                if (labelFull) { labelFull.style.borderColor = 'var(--accent-gold)'; labelFull.style.background = 'rgba(197, 160, 89, 0.15)'; }
                if (labelSingle) { labelSingle.style.borderColor = 'rgba(255,255,255,0.15)'; labelSingle.style.background = 'rgba(0,0,0,0.3)'; }
                if (duplexWingBox) duplexWingBox.style.display = 'none';
            } else {
                if (labelSingle) { labelSingle.style.borderColor = '#56c2c9'; labelSingle.style.background = 'rgba(86, 194, 201, 0.15)'; }
                if (labelFull) { labelFull.style.borderColor = 'rgba(255,255,255,0.15)'; labelFull.style.background = 'rgba(0,0,0,0.3)'; }
                if (duplexWingBox) duplexWingBox.style.display = 'block';
            }
            // Update struct pill, image and base guests in card (only for duplex stays)
            const mscStructPill = document.getElementById('msc-struct-pill');
            const mscBaseGuestsPill = document.getElementById('msc-base-guests-pill');
            const mscRatePill = document.getElementById('msc-rate-pill');
            const chaletTitleEl = document.getElementById('modal-chalet-card-title');
            const chaletThumbEl = document.getElementById('modal-chalet-card-thumb');
            const selectedOption = modalVillaSelect ? modalVillaSelect.options[modalVillaSelect.selectedIndex] : null;
            if (selectedOption) {
                const structType = selectedOption.getAttribute('data-structure-type') || 'single_hut';
                const isDuplexStay = (structType === 'duplex_hut');
                const rate = parseFloat(selectedOption.getAttribute('data-price') || "8000");
                const singleRate = parseFloat(selectedOption.getAttribute('data-single-rate') || "4000");
                const baseGuests = parseInt(selectedOption.getAttribute('data-base-guests') || "2", 10);
                const rawTitle = selectedOption.getAttribute('data-name') || selectedOption.text.split('(')[0].replace(/^.*]:\s*/, '').trim();
                
                if (isDuplexStay) {
                    if (mscStructPill) {
                        mscStructPill.innerText = (radio.value === 'single_room') ? 'Duplex (Single Suite)' : 'Duplex (Entire 2-Room Suite)';
                    }
                    if (mscBaseGuestsPill) {
                        mscBaseGuestsPill.innerHTML = (radio.value === 'single_room') ? '<i class="fa-solid fa-users"></i> Base: 2 Guests Included' : '<i class="fa-solid fa-users"></i> Base: 4 Guests Included';
                    }
                    if (mscRatePill) {
                        const effectiveRate = (radio.value === 'single_room') ? singleRate : rate;
                        mscRatePill.innerHTML = `<i class="fa-solid fa-tag"></i> <strong>₹${effectiveRate.toLocaleString('en-IN')}</strong> / nt`;
                    }
                    if (radio.value === 'single_room') {
                        const wingRadio = document.querySelector('input[name="modal_duplex_wing"]:checked');
                        const wingVal = wingRadio ? wingRadio.value : 'left';
                        let wingPhotos = [];
                        try {
                            const photosAttr = (wingVal === 'left') ? selectedOption.getAttribute('data-photos-left') : selectedOption.getAttribute('data-photos-right');
                            if (photosAttr) wingPhotos = JSON.parse(photosAttr);
                        } catch(e) {}
                        if (chaletThumbEl && wingPhotos && wingPhotos.length > 0) {
                            chaletThumbEl.src = wingPhotos[0];
                        }
                        if (chaletTitleEl) {
                            chaletTitleEl.innerText = rawTitle + (wingVal === 'left' ? ' (Left Suite - Wing A)' : ' (Right Suite - Wing B)');
                        }
                    } else {
                        const defaultImg = selectedOption.getAttribute('data-image');
                        if (chaletThumbEl && defaultImg) chaletThumbEl.src = defaultImg;
                        if (chaletTitleEl) chaletTitleEl.innerText = rawTitle;
                    }
                } else {
                    if (mscStructPill) mscStructPill.innerText = 'Single Cottage';
                    if (mscBaseGuestsPill) mscBaseGuestsPill.innerHTML = `<i class="fa-solid fa-users"></i> Base: ${baseGuests} Guests Included`;
                    if (mscRatePill) mscRatePill.innerHTML = `<i class="fa-solid fa-tag"></i> <strong>₹${rate.toLocaleString('en-IN')}</strong> / nt`;
                }
            }
            updateStepperButtons();
            recalculateBookingSummary();
            checkLiveModalAvailability(false);
        });
    });

    // Modal Duplex Suite Wing Selection Listener (Left vs Right)
    document.querySelectorAll('input[name="modal_duplex_wing"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const wingVal = radio.value;
            const wingLabelLeft = document.getElementById('modal-wing-label-left');
            const wingLabelRight = document.getElementById('modal-wing-label-right');
            if (wingVal === 'left') {
                if (wingLabelLeft) wingLabelLeft.classList.add('is-selected');
                if (wingLabelRight) wingLabelRight.classList.remove('is-selected');
            } else {
                if (wingLabelRight) wingLabelRight.classList.add('is-selected');
                if (wingLabelLeft) wingLabelLeft.classList.remove('is-selected');
            }

            const selectedOption = modalVillaSelect ? modalVillaSelect.options[modalVillaSelect.selectedIndex] : null;
            const chaletTitleEl = document.getElementById('modal-chalet-card-title');
            const chaletThumbEl = document.getElementById('modal-chalet-card-thumb');
            if (selectedOption) {
                const rawTitle = selectedOption.getAttribute('data-name') || selectedOption.text.split('(')[0].replace(/^.*]:\s*/, '').trim();
                let wingPhotos = [];
                try {
                    const photosAttr = (wingVal === 'left') ? selectedOption.getAttribute('data-photos-left') : selectedOption.getAttribute('data-photos-right');
                    if (photosAttr) wingPhotos = JSON.parse(photosAttr);
                } catch(e) {}
                if (chaletThumbEl && wingPhotos && wingPhotos.length > 0) {
                    chaletThumbEl.src = wingPhotos[0];
                }
                if (chaletTitleEl) {
                    chaletTitleEl.innerText = rawTitle + (wingVal === 'left' ? ' (Left Suite - Wing A)' : ' (Right Suite - Wing B)');
                }
            }

            checkLiveModalAvailability(false);
        });
    });

    // Multi-Chalet Group Booking State & API
    window.selectedMultiChaletCombo = null;

    window.setModalMultiChaletStay = function(chaletCombo) {
        if (!chaletCombo || !chaletCombo.chalets || chaletCombo.chalets.length === 0) return;
        window.selectedMultiChaletCombo = chaletCombo;

        const multiContainer = document.getElementById('modal-multi-chalet-container');
        const singleCard = document.getElementById('modal-selected-chalet-card');
        const selectWrapper = document.getElementById('modal-villa-select-wrapper');
        const duplexTierBox = document.getElementById('modal-duplex-tier-box');
        const multiList = document.getElementById('modal-multi-chalet-list');
        const groupTitle = document.getElementById('mmc-group-title');
        const capTxt = document.getElementById('mmc-total-capacity-txt');
        const baseCapTxt = document.getElementById('mmc-base-capacity-txt');
        const rateTxt = document.getElementById('mmc-combined-rate-txt');

        if (singleCard) singleCard.style.display = 'none';
        if (selectWrapper) selectWrapper.style.display = 'none';
        if (duplexTierBox) duplexTierBox.style.display = 'none';
        if (multiContainer) multiContainer.style.display = 'block';

        if (groupTitle) groupTitle.innerText = chaletCombo.title || `Combined Clustered Stays (${chaletCombo.chalets.length} Chalets)`;
        if (capTxt) capTxt.innerText = `Up to ${chaletCombo.maxCapacity} Guests`;
        if (baseCapTxt) baseCapTxt.innerText = `${chaletCombo.baseCapacity} Base Included`;
        if (rateTxt) rateTxt.innerText = `₹${(chaletCombo.totalBaseRate || 0).toLocaleString('en-IN')} / nt`;

        if (multiList) {
            multiList.innerHTML = '';
            chaletCombo.chalets.forEach(ch => {
                const item = document.createElement('div');
                item.className = 'mmc-chalet-item-card';
                const img = ch.image_url || (ch.photos_list && ch.photos_list[0]) || 'assets/images/treehouse_exterior_front.jpg';
                const isDuplex = ch.structure_type === 'duplex_hut' || (ch.title && ch.title.toLowerCase().includes('duplex'));
                const typeLabel = isDuplex ? '🏰 Duplex Residence (2 Suites)' : '🏡 Single Forest Cottage';
                const spotNum = ch.spot_number ? `Spot ${String(ch.spot_number).padStart(2, '0')} • ` : '';
                const baseG = ch.base_guests || (isDuplex ? 4 : 2);
                const maxG = ch.max_guests || (isDuplex ? 8 : 4);
                const rate = parseFloat(ch.room_rate || ch.rate_per_night || ch.stay_price || 14500);

                item.innerHTML = `
                    <div class="mmc-chalet-item-left">
                        <img src="${img}" alt="${ch.title}" class="mmc-item-thumb" onerror="this.src='assets/images/treehouse_exterior_front.jpg';">
                        <div>
                            <span class="mmc-item-type">${spotNum}${typeLabel}</span>
                            <h5 class="mmc-item-name">${ch.title}</h5>
                            <span class="mmc-item-guests"><i class="fa-solid fa-users"></i> Base: ${baseG} Guests Included • Max ${maxG} Guests</span>
                        </div>
                    </div>
                    <div class="mmc-chalet-item-right">
                        <span class="mmc-item-rate">₹${rate.toLocaleString('en-IN')}<small style="font-size:10px; font-weight:normal;">/nt</small></span>
                        <span class="mmc-item-rate-sub">Meals Included</span>
                    </div>
                `;
                multiList.appendChild(item);
            });
        }

        recalculateBookingSummary();
    };

    const btnSwitchSingleModal = document.getElementById('btn-modal-switch-single');
    if (btnSwitchSingleModal) {
        btnSwitchSingleModal.addEventListener('click', () => {
            window.selectedMultiChaletCombo = null;
            const multiContainer = document.getElementById('modal-multi-chalet-container');
            const singleCard = document.getElementById('modal-selected-chalet-card');
            const selectWrapper = document.getElementById('modal-villa-select-wrapper');
            if (multiContainer) multiContainer.style.display = 'none';
            if (singleCard) singleCard.style.display = 'flex';
            if (selectWrapper) selectWrapper.style.display = 'block';
            onModalVillaChange(false);
        });
    }

    // Chalet selection dropdown toggle buttons
    const btnToggleChaletSelect = document.getElementById('btn-toggle-chalet-select');
    const btnHideChaletSelect = document.getElementById('btn-hide-chalet-select');
    const modalVillaSelectWrapper = document.getElementById('modal-villa-select-wrapper');

    if (btnToggleChaletSelect && modalVillaSelectWrapper) {
        btnToggleChaletSelect.addEventListener('click', (e) => {
            e.preventDefault();
            const isHidden = modalVillaSelectWrapper.style.display === 'none' || !modalVillaSelectWrapper.style.display;
            modalVillaSelectWrapper.style.display = isHidden ? 'block' : 'none';
            if (isHidden && modalVillaSelect) modalVillaSelect.focus();
        });
    }

    if (btnHideChaletSelect && modalVillaSelectWrapper) {
        btnHideChaletSelect.addEventListener('click', (e) => {
            e.preventDefault();
            modalVillaSelectWrapper.style.display = 'none';
        });
    }

    // Price Calculation & Dynamic Rules
    function recalculateBookingSummary() {
        if (!modalCheckin || !modalCheckout) return;

        const checkinDate = new Date(modalCheckin.value);
        const checkoutDate = new Date(modalCheckout.value);
        let nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        if (isNaN(nights) || nights < 1) nights = 1;

        let villaPrice = 5000;
        let villaName = "Luxury Canopy Treehouse";
        let baseGuests = 2;
        let maxGuests = 4;
        let extraAdultRate = 750;
        let extraChildRate = 0;

        if (window.selectedMultiChaletCombo) {
            villaPrice = parseFloat(window.selectedMultiChaletCombo.totalBaseRate || 48000);
            villaName = window.selectedMultiChaletCombo.title || "Group Clustered Chalets";
            baseGuests = parseInt(window.selectedMultiChaletCombo.baseCapacity || 8, 10);
            maxGuests = parseInt(window.selectedMultiChaletCombo.maxCapacity || 16, 10);
        } else {
            if (!modalVillaSelect) return;
            const selectedOption = modalVillaSelect.options[modalVillaSelect.selectedIndex];
            if (!selectedOption) return;

            const structureType = selectedOption.getAttribute('data-structure-type') || "single_hut";
            const isDuplex = (structureType === 'duplex_hut');
            const selectedTierRadio = document.querySelector('input[name="modal_tier"]:checked');
            const modalTier = selectedTierRadio ? selectedTierRadio.value : 'full';

            villaPrice = parseFloat(selectedOption.getAttribute('data-price') || "5000");
            villaName = selectedOption.getAttribute('data-name') || "Luxury Canopy Treehouse";
            baseGuests = parseInt(selectedOption.getAttribute('data-base-guests') || "2", 10);
            maxGuests = parseInt(selectedOption.getAttribute('data-max-guests') || "4", 10);
            extraAdultRate = parseFloat(selectedOption.getAttribute('data-extra-rate') || "750");
            extraChildRate = parseFloat(selectedOption.getAttribute('data-extra-child-rate') || "0");

            if (isDuplex) {
                if (modalTier === 'single_room') {
                    villaPrice = parseFloat(selectedOption.getAttribute('data-single-rate') || "4000");
                    baseGuests = 2;
                    maxGuests = 4;
                } else {
                    villaPrice = parseFloat(selectedOption.getAttribute('data-price') || "8000");
                    baseGuests = parseInt(selectedOption.getAttribute('data-base-guests') || "4", 10);
                    maxGuests = parseInt(selectedOption.getAttribute('data-max-guests') || "8", 10);
                }
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
        const selectedAddonsList = [];
        document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
            const card = cb.closest('.addon-card');
            const name = card?.querySelector('.addon-name')?.innerText?.trim() || 'Signature Experience';
            const price = card?.querySelector('.addon-price')?.innerText?.trim() || '₹0';
            selectedAddonsList.push({
                name: name,
                price: price
            });
        });
        const selectedAddonsCount = selectedAddonsList.length;

        // Curated Food Menu Selection Calculation & Itemization
        let foodTotal = 0;
        let foodSetsCount = 0;
        const selectedFoodList = [];
        const isAllFoodSkipped = document.getElementById('toggle-skip-all-food')?.checked || false;

        if (!isAllFoodSkipped) {
            document.querySelectorAll('.modal-dish-card').forEach(card => {
                const qtyInput = card.querySelector('.dish-qty-input');
                const qty = parseInt(qtyInput?.value || "0", 10);
                const category = card.getAttribute('data-dish-category');
                const name = card.getAttribute('data-dish-name') || 'Signature Dish';
                const price = parseFloat(card.getAttribute('data-dish-price') || "0");
                const mealTimeInp = card.querySelector('.dish-meal-time-val');
                const mealTime = mealTimeInp ? mealTimeInp.value : (card.getAttribute('data-default-meal') || category || 'breakfast');

                if (qty > 0) {
                    const subtotal = qty * price;
                    foodTotal += subtotal;
                    foodSetsCount += qty;
                    selectedFoodList.push({
                        name: name,
                        category: category,
                        meal_time: mealTime,
                        qty: qty,
                        price: price,
                        subtotal: subtotal
                    });
                    card.style.borderColor = '#059669';
                    card.style.background = '#F0FDF4';
                } else {
                    card.style.borderColor = 'rgba(28, 56, 38, 0.14)';
                    card.style.background = '#FFFFFF';
                }
            });
        }

        // Addons are payable on-site directly to local artisans/guides, so addonsTotal is 0 in advance bill
        const stayTotal = baseVillaTotal + extraAdultsTotal + extraKidsTotal + foodTotal;

        // GST & Billing Preference Calculation (Official 5% GST on all bookings)
        const bookingFormEl = document.getElementById('luxury-booking-form');
        const gstRateAttr = parseFloat(bookingFormEl?.getAttribute('data-gst-rate') || '5');
        const gstRate = (isNaN(gstRateAttr) || gstRateAttr <= 0) ? 5 : gstRateAttr;
        const billingTypeRadio = document.querySelector('input[name="modal_billing_type"]:checked');
        const billingTypeValue = billingTypeRadio ? billingTypeRadio.value : 'gst_without_address';
        const isWithAddress = (billingTypeValue === 'gst_with_address');

        const gstAmount = Math.round(stayTotal * (gstRate / 100));
        const grandTotal = stayTotal + gstAmount;

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
        const summarySubtotalLine = document.getElementById('summary-subtotal-line');
        const summarySubtotalRate = document.getElementById('summary-subtotal-rate');
        const summaryGstLine = document.getElementById('summary-gst-line');
        const summaryGstLabel = document.getElementById('summary-gst-label');
        const summaryGstRate = document.getElementById('summary-gst-rate');
        const summaryTotalTitle = document.getElementById('summary-total-title');
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

        // Food Menu itemized container update
        const summaryFoodSection = document.getElementById('summary-food-section');
        const summaryFoodHeading = document.getElementById('summary-food-heading');
        const summaryFoodRate = document.getElementById('summary-food-rate');
        const summaryFoodItemsContainer = document.getElementById('summary-food-items-container');

        if (summaryFoodSection && summaryFoodItemsContainer) {
            if (selectedFoodList.length > 0) {
                summaryFoodSection.style.display = 'block';
                if (summaryFoodHeading) {
                    summaryFoodHeading.innerText = `Curated Gastronomy (${foodSetsCount} Dish Set${foodSetsCount > 1 ? 's' : ''}):`;
                }
                if (summaryFoodRate) {
                    summaryFoodRate.innerText = foodTotal > 0 ? `+₹${foodTotal.toLocaleString('en-IN')}` : 'Included (₹0.00)';
                }
                
                let foodHtml = '';
                selectedFoodList.forEach(item => {
                    const mealLabel = (item.meal_time || item.category || 'meal').toUpperCase();
                    const rateBadge = item.price === 0
                        ? '<span style="color: #059669; font-weight: 700; font-size: 11.5px;">Included (₹0.00)</span>'
                        : `<span style="color: #92400E; font-weight: 700; font-size: 12px;">+₹${item.subtotal.toLocaleString('en-IN')} <small style="font-weight: normal; color: #78716C; font-size: 10px;">(${item.qty} × ₹${item.price.toLocaleString('en-IN')})</small></span>`;
                    
                    const catIcon = item.meal_time === 'breakfast' ? 'fa-mug-saucer' : (item.meal_time === 'lunch' ? 'fa-bowl-rice' : (item.meal_time === 'snacks' ? 'fa-cookie-bite' : 'fa-fire-burner'));

                    foodHtml += `
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #374151; padding: 3px 0;">
                            <span style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                <i class="fa-solid ${catIcon}" style="font-size: 10px; color: var(--accent-gold); flex-shrink: 0;"></i>
                                <span style="font-weight: 600; color: #1F2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.name}</span>
                                <span style="background: rgba(28,56,38,0.08); color: #15803D; font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 3px; flex-shrink: 0;">× ${item.qty}</span>
                                <span style="background: #FEF3C7; color: #92400E; font-size: 9px; font-weight: 700; padding: 1px 4px; border-radius: 3px; flex-shrink: 0; text-transform: uppercase;">${mealLabel}</span>
                            </span>
                            <span style="margin-left: 8px; white-space: nowrap; text-align: right;">
                                ${rateBadge}
                            </span>
                        </div>
                    `;
                });
                summaryFoodItemsContainer.innerHTML = foodHtml;
            } else {
                summaryFoodSection.style.display = 'none';
                summaryFoodItemsContainer.innerHTML = '';
            }
        }

        // Update live category selected counts and badges
        const categoryTotals = {};
        document.querySelectorAll('.modal-dish-card').forEach(card => {
            const cat = card.getAttribute('data-dish-category');
            const qty = parseInt(card.querySelector('.dish-qty-input')?.value || "0", 10);
            const price = parseFloat(card.getAttribute('data-dish-price') || "0");
            if (!categoryTotals[cat]) categoryTotals[cat] = { qty: 0, total: 0 };
            categoryTotals[cat].qty += qty;
            categoryTotals[cat].total += (qty * price);
        });

        Object.keys(categoryTotals).forEach(cat => {
            const badge = document.getElementById('cat-badge-selected-' + cat);
            const block = document.getElementById('modal-cat-block-' + cat);
            const data = categoryTotals[cat];
            if (badge) {
                if (data.qty > 0) {
                    badge.style.display = 'inline-flex';
                    badge.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span class="badge-num">${data.qty}</span> selected <small style="font-weight: 600; opacity: 0.85; margin-left: 2px;">(₹${data.total.toLocaleString('en-IN')})</small>`;
                    if (block) block.classList.add('has-selected');
                } else {
                    badge.style.display = 'none';
                    if (block) block.classList.remove('has-selected');
                }
            }
        });

        if (summaryExtraGuestsLine) summaryExtraGuestsLine.style.display = 'none';

        // Addons Experiences itemized container update
        const summaryAddonsSection = document.getElementById('summary-addons-section');
        const summaryAddonsHeading = document.getElementById('summary-addons-heading');
        const summaryAddonsItemsContainer = document.getElementById('summary-addons-items-container');

        if (summaryAddonsSection && summaryAddonsItemsContainer) {
            if (selectedAddonsList.length > 0) {
                summaryAddonsSection.style.display = 'block';
                if (summaryAddonsHeading) {
                    summaryAddonsHeading.innerText = `Selected Signature Experiences (${selectedAddonsList.length}):`;
                }

                let addonsHtml = '';
                selectedAddonsList.forEach(ad => {
                    addonsHtml += `
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #374151; padding: 3px 0;">
                            <span style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                <i class="fa-solid fa-sparkles" style="font-size: 10px; color: #0E7490; flex-shrink: 0;"></i>
                                <span style="font-weight: 600; color: #1F2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${ad.name}</span>
                            </span>
                            <span style="margin-left: 8px; white-space: nowrap; text-align: right; color: #0E7490; font-weight: 700; font-size: 11.5px;">
                                ${ad.price} <span style="font-size: 10px; font-weight: 500; color: #64748B;">(On-Site)</span>
                            </span>
                        </div>
                    `;
                });
                summaryAddonsItemsContainer.innerHTML = addonsHtml;
            } else {
                summaryAddonsSection.style.display = 'none';
                summaryAddonsItemsContainer.innerHTML = '';
            }
        }

        // Subtotal & GST Lines (Always 5% GST)
        if (summarySubtotalLine && summarySubtotalRate) {
            summarySubtotalLine.style.display = 'flex';
            summarySubtotalRate.innerText = `₹${stayTotal.toLocaleString('en-IN')}`;
        }
        if (summaryGstLine && summaryGstRate) {
            summaryGstLine.style.display = 'flex';
            if (summaryGstLabel) summaryGstLabel.innerText = `GST Tax (${gstRate}%):`;
            summaryGstRate.innerText = `+₹${gstAmount.toLocaleString('en-IN')}`;
        }
        if (summaryTotalTitle) {
            summaryTotalTitle.innerText = `Grand Total (${gstRate}% GST Incl.):`;
        }

        if (summaryTotal) summaryTotal.innerText = `₹${grandTotal.toLocaleString('en-IN')}`;

        // Real-Time Update for Floating Sticky Checkout Bar
        const mscbLiveTotal = document.getElementById('mscb-live-total');
        const mscbTxtNights = document.getElementById('mscb-txt-nights');
        const mscbTxtGuests = document.getElementById('mscb-txt-guests');
        const mscbTxtExtra = document.getElementById('mscb-txt-extra');
        const mscbTxtFood = document.getElementById('mscb-txt-food');
        const mscbTaxPill = document.getElementById('mscb-tax-pill');
        const mscbReserveBtn = document.getElementById('btn-mscb-submit');

        if (mscbLiveTotal) {
            mscbLiveTotal.innerText = `₹${grandTotal.toLocaleString('en-IN')}`;
            // Subtle pulse micro-animation on change
            mscbLiveTotal.classList.remove('mscb-pulse');
            void mscbLiveTotal.offsetWidth;
            mscbLiveTotal.classList.add('mscb-pulse');
        }
        if (mscbTxtNights) {
            mscbTxtNights.innerText = `${nights} ${nights === 1 ? 'Night' : 'Nights'}`;
        }
        if (mscbTxtGuests) {
            let guestStr = `${adultsCount} Adult${adultsCount > 1 ? 's' : ''}`;
            if (kidsCount > 0) guestStr += `, ${kidsCount} Kid${kidsCount > 1 ? 's' : ''}`;
            mscbTxtGuests.innerHTML = `<i class="fa-solid fa-user-group"></i> ${guestStr}`;
        }
        if (mscbTxtExtra) {
            const extraTotalVal = extraAdultsTotal + extraKidsTotal;
            if (extraTotalVal > 0) {
                mscbTxtExtra.style.display = 'inline-flex';
                mscbTxtExtra.innerHTML = `<i class="fa-solid fa-user-plus"></i> +₹${extraTotalVal.toLocaleString('en-IN')} Extra`;
            } else {
                mscbTxtExtra.style.display = 'none';
            }
        }
        if (mscbTxtFood) {
            if (foodTotal > 0) {
                mscbTxtFood.innerHTML = `<i class="fa-solid fa-utensils"></i> +₹${foodTotal.toLocaleString('en-IN')} Food`;
            } else if (foodSetsCount > 0) {
                mscbTxtFood.innerHTML = `<i class="fa-solid fa-utensils"></i> ${foodSetsCount} Sets Incl.`;
            } else {
                mscbTxtFood.innerHTML = `<i class="fa-solid fa-utensils"></i> Meals Incl.`;
            }
        }
        if (mscbTaxPill) {
            mscbTaxPill.innerHTML = `<i class="fa-solid fa-file-invoice-dollar"></i> ${gstRate}% GST Incl.`;
            mscbTaxPill.style.background = 'rgba(2, 132, 199, 0.2)';
            mscbTaxPill.style.color = '#38BDF8';
            mscbTaxPill.style.borderColor = 'rgba(56, 189, 248, 0.4)';
        }
        if (mscbReserveBtn) {
            if (!isCurrentVillaAvailable) {
                mscbReserveBtn.disabled = true;
                mscbReserveBtn.classList.add('btn-mscb-disabled');
                mscbReserveBtn.innerHTML = '<span>Chalet Reserved</span> <i class="fa-solid fa-calendar-xmark"></i>';
            } else {
                mscbReserveBtn.disabled = false;
                mscbReserveBtn.classList.remove('btn-mscb-disabled');
                mscbReserveBtn.innerHTML = '<span>Confirm &amp; Reserve</span> <i class="fa-solid fa-arrow-right"></i>';
            }
        }
    }

    if (modalVillaSelect) modalVillaSelect.addEventListener('change', () => onModalVillaChange(true));
    if (modalGuestsSelect) modalGuestsSelect.addEventListener('change', recalculateBookingSummary);
    if (modalCheckin) modalCheckin.addEventListener('change', () => {
        recalculateBookingSummary();
        checkLiveModalAvailability(true);
    });
    if (modalCheckout) modalCheckout.addEventListener('change', () => {
        recalculateBookingSummary();
        checkLiveModalAvailability(true);
    });
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
            const card = input.closest('.modal-dish-card');
            if (card) {
                if (val > 0) {
                    card.style.borderColor = '#059669';
                    card.style.background = '#F0FDF4';
                } else {
                    card.style.borderColor = 'rgba(28, 56, 38, 0.14)';
                    card.style.background = '#FFFFFF';
                }
            }
            recalculateBookingSummary();
        });
    });

    // Meal Serving Time Selector Pills (Breakfast, Lunch, Snacks, Dinner)
    document.querySelectorAll('.btn-meal-pill').forEach(pill => {
        pill.addEventListener('click', function(e) {
            e.preventDefault();
            const dishId = this.getAttribute('data-dish-id');
            const meal = this.getAttribute('data-meal');
            const card = document.getElementById('dish-card-' + dishId) || this.closest('.modal-dish-card');
            if (!card) return;

            // Update active pill in this dish card
            card.querySelectorAll('.btn-meal-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            // Update hidden input
            const hiddenInp = document.getElementById('dish-meal-' + dishId) || card.querySelector('.dish-meal-time-val');
            if (hiddenInp) hiddenInp.value = meal;

            // Update badge display
            const badge = document.getElementById('dish-badge-meal-' + dishId) || card.querySelector('.dish-selected-meal-badge');
            if (badge) badge.innerText = meal.toUpperCase();

            // If quantity is 0, automatically select 1 portion
            const qtyInp = document.getElementById('dish-qty-' + dishId) || card.querySelector('.dish-qty-input');
            if (qtyInp && parseInt(qtyInp.value || '0', 10) === 0) {
                qtyInp.value = 1;
                card.style.borderColor = '#059669';
                card.style.background = '#F0FDF4';
            }

            recalculateBookingSummary();
        });
    });

    // -------------------------------------------------------------
    // Interactive Collapsible Gastronomy Accordion & Category Tabs
    // -------------------------------------------------------------
    function toggleMealCategory(catKey, forceState = null) {
        const block = document.getElementById('modal-cat-block-' + catKey);
        const panel = document.getElementById('dishes-panel-' + catKey);
        const header = block ? block.querySelector('.meal-cat-header-row') : null;
        if (!block || !panel) return;

        const isCurrentlyOpen = block.classList.contains('is-open');
        const shouldOpen = (forceState !== null) ? forceState : !isCurrentlyOpen;

        if (shouldOpen) {
            block.classList.add('is-open');
            panel.style.display = 'block';
            if (header) header.setAttribute('aria-expanded', 'true');
        } else {
            block.classList.remove('is-open');
            panel.style.display = 'none';
            if (header) header.setAttribute('aria-expanded', 'false');
        }
        updateToggleAllCatsButtonText();
    }

    // Header click & keyboard listener
    document.querySelectorAll('.meal-cat-header-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.meal-skip-btn') || e.target.closest('.cat-skip-checkbox')) {
                return;
            }
            const cat = this.getAttribute('data-cat');
            toggleMealCategory(cat);
        });
        row.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                if (e.target.closest('.meal-skip-btn')) return;
                e.preventDefault();
                const cat = this.getAttribute('data-cat');
                toggleMealCategory(cat);
            }
        });
    });

    // Expand All / Collapse All Master Button
    const btnToggleAllCats = document.getElementById('btn-toggle-all-food-cats');
    function updateToggleAllCatsButtonText() {
        if (!btnToggleAllCats) return;
        const visibleBlocks = Array.from(document.querySelectorAll('.modal-meal-category-block')).filter(b => b.style.display !== 'none');
        const anyClosed = visibleBlocks.some(b => !b.classList.contains('is-open'));
        if (anyClosed) {
            btnToggleAllCats.innerHTML = '<i class="fa-solid fa-angles-down"></i> <span>Expand All</span>';
        } else {
            btnToggleAllCats.innerHTML = '<i class="fa-solid fa-angles-up"></i> <span>Collapse All</span>';
        }
    }

    if (btnToggleAllCats) {
        btnToggleAllCats.addEventListener('click', function(e) {
            e.preventDefault();
            const visibleBlocks = Array.from(document.querySelectorAll('.modal-meal-category-block')).filter(b => b.style.display !== 'none');
            const anyClosed = visibleBlocks.some(b => !b.classList.contains('is-open'));
            const targetOpen = anyClosed; // If any closed, expand all; otherwise collapse all
            visibleBlocks.forEach(b => {
                const cat = b.getAttribute('data-category');
                toggleMealCategory(cat, targetOpen);
            });
            updateToggleAllCatsButtonText();
        });
    }

    // Category Filter Tabs (All, Breakfast, Lunch, Snacks, Dinner, Millets, Curries, Juices)
    document.querySelectorAll('.btn-food-filter-tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const targetCat = this.getAttribute('data-cat');

            document.querySelectorAll('.btn-food-filter-tab').forEach(t => {
                t.classList.remove('active');
                t.style.background = '#fff';
                t.style.color = 'var(--accent-green)';
                t.style.borderColor = 'rgba(28,56,38,0.2)';
            });
            this.classList.add('active');
            this.style.background = 'var(--accent-green)';
            this.style.color = '#fff';
            this.style.borderColor = 'var(--accent-green)';

            document.querySelectorAll('.modal-meal-category-block').forEach(block => {
                const blockCat = block.getAttribute('data-category');
                if (targetCat === 'all' || blockCat === targetCat) {
                    block.style.display = 'block';
                    if (targetCat !== 'all') {
                        // Automatically expand selected single category for convenient browsing
                        toggleMealCategory(blockCat, true);
                    }
                } else {
                    block.style.display = 'none';
                }
            });
            updateToggleAllCatsButtonText();
        });
    });

    // Meal Category Skip Checkboxes
    document.querySelectorAll('.cat-skip-checkbox').forEach(chk => {
        chk.addEventListener('change', function() {
            const cat = this.getAttribute('data-target-cat');
            const block = document.getElementById('modal-cat-block-' + cat);
            const grid = document.getElementById('dishes-grid-' + cat);
            if (!block) return;

            if (this.checked) {
                if (grid) {
                    grid.style.opacity = '0.35';
                    grid.style.pointerEvents = 'none';
                    grid.querySelectorAll('.dish-qty-input').forEach(inp => inp.value = 0);
                }
                block.style.opacity = '0.6';
                toggleMealCategory(cat, false);
            } else {
                if (grid) {
                    grid.style.opacity = '1';
                    grid.style.pointerEvents = 'auto';
                }
                block.style.opacity = '1';
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
                container.style.opacity = '0.4';
                container.style.pointerEvents = 'none';
                container.querySelectorAll('.dish-qty-input').forEach(inp => inp.value = 0);
                // Collapse all categories
                document.querySelectorAll('.modal-meal-category-block').forEach(b => {
                    const cat = b.getAttribute('data-category');
                    toggleMealCategory(cat, false);
                });
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

    // Modal Billing Type Radio Switcher (GST Without Address vs GST With Address)
    document.querySelectorAll('input[name="modal_billing_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const gstWrapper = document.getElementById('modal-gst-fields-wrapper');
            const labelWithout = document.getElementById('label-bill-without-address');
            const labelWith = document.getElementById('label-bill-with-address');

            if (this.value === 'gst_with_address') {
                if (gstWrapper) {
                    gstWrapper.style.display = 'block';
                    gstWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                document.getElementById('modal-gst-number')?.setAttribute('required', 'required');
                document.getElementById('modal-billing-name')?.setAttribute('required', 'required');
                document.getElementById('modal-billing-address')?.setAttribute('required', 'required');
                if (labelWith) {
                    labelWith.style.background = '#E0F2FE';
                    labelWith.style.border = '2px solid #0284C7';
                    const strong = labelWith.querySelector('strong');
                    if (strong) strong.style.color = '#0369A1';
                }
                if (labelWithout) {
                    labelWithout.style.background = '#FFFFFF';
                    labelWithout.style.border = '1.5px solid #CBD5E1';
                    const strong = labelWithout.querySelector('strong');
                    if (strong) strong.style.color = '#1E293B';
                }
            } else {
                if (gstWrapper) gstWrapper.style.display = 'none';
                document.getElementById('modal-gst-number')?.removeAttribute('required');
                document.getElementById('modal-billing-name')?.removeAttribute('required');
                document.getElementById('modal-billing-address')?.removeAttribute('required');
                if (labelWithout) {
                    labelWithout.style.background = '#E0F2FE';
                    labelWithout.style.border = '2px solid #0284C7';
                    const strong = labelWithout.querySelector('strong');
                    if (strong) strong.style.color = '#0369A1';
                }
                if (labelWith) {
                    labelWith.style.background = '#FFFFFF';
                    labelWith.style.border = '1.5px solid #CBD5E1';
                    const strong = labelWith.querySelector('strong');
                    if (strong) strong.style.color = '#1E293B';
                }
            }
            recalculateBookingSummary();
        });
    });

    // -------------------------------------------------------------
    // -------------------------------------------------------------
    // 6.2 Government ID Proof File Upload & Pre-Flight Anti-Virus Scan
    // -------------------------------------------------------------
    const idFileInput = document.getElementById('modal-id-file');
    const idFilePrompt = document.getElementById('id-file-prompt');
    const idFileScanning = document.getElementById('id-file-scanning');
    const idFileSelected = document.getElementById('id-file-selected');
    const idFileNameEl = document.getElementById('id-file-name');
    const idFileSizeEl = document.getElementById('id-file-size');
    const btnRemoveIdFile = document.getElementById('btn-remove-id-file');
    let isIdFileVerifiedClean = false;

    // Advanced Pre-Flight Binary & Antivirus Threat Scanner
    async function scanFileForVirusesAndThreats(file) {
        // 1. Strict File Extension Whitelist & Anti-Double Extension Check
        const fileName = file.name.toLowerCase();
        const dangerousExts = [
            '.exe', '.bat', '.cmd', '.sh', '.php', '.php3', '.phtml', '.js', '.vbs', '.scr',
            '.pif', '.jar', '.dll', '.bin', '.apk', '.msi', '.com', '.vbe', '.wsf', '.hta',
            '.cpl', '.iso', '.img', '.svg', '.html', '.htm', '.py', '.pl', '.cgi', '.asp', '.aspx'
        ];

        for (const badExt of dangerousExts) {
            if (fileName.endsWith(badExt) || fileName.includes(badExt + '.')) {
                return { safe: false, reason: `Disallowed executable file extension (${badExt}).` };
            }
        }

        const validExtRegex = /\.(jpe?g|png|webp|pdf)$/i;
        if (!validExtRegex.test(fileName)) {
            return { safe: false, reason: 'Invalid file format. Only JPG, PNG, WEBP, and PDF documents are permitted.' };
        }

        // 2. File Size Constraint
        if (file.size > 8 * 1024 * 1024) {
            return { safe: false, reason: 'File exceeds maximum allowed size of 8MB.' };
        }
        if (file.size < 64) {
            return { safe: false, reason: 'File is empty or corrupted (size under 64 bytes).' };
        }

        // 3. Binary Magic Byte & Anti-Malware Header Inspection
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const buffer = e.target.result;
                    const bytes = new Uint8Array(buffer);
                    const headerHex = Array.from(bytes.slice(0, 16)).map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();

                    // DOS/Windows PE Executable Signature ('MZ' = 4D 5A)
                    if (bytes[0] === 0x4D && bytes[1] === 0x5A) {
                        return resolve({ safe: false, reason: 'Security Alert: Executable PE/DOS binary pattern detected.' });
                    }

                    // Linux ELF Executable Signature (\x7FELF = 7F 45 4C 46)
                    if (bytes[0] === 0x7F && bytes[1] === 0x45 && bytes[2] === 0x4C && bytes[3] === 0x46) {
                        return resolve({ safe: false, reason: 'Security Alert: Linux binary executable pattern detected.' });
                    }

                    // ZIP/JAR archive executable signature (PK\x03\x04 = 50 4B 03 04)
                    if (bytes[0] === 0x50 && bytes[1] === 0x4B && bytes[2] === 0x03 && bytes[3] === 0x04) {
                        return resolve({ safe: false, reason: 'Security Alert: Compressed ZIP/JAR archive detected instead of document.' });
                    }

                    // Validate Magic Header for Expected Types
                    let isMatch = false;
                    let detectedType = 'unknown';

                    if (headerHex.startsWith('FFD8FF')) {
                        isMatch = true;
                        detectedType = 'jpeg';
                    } else if (headerHex.startsWith('89504E470D0A1A0A')) {
                        isMatch = true;
                        detectedType = 'png';
                    } else if (headerHex.startsWith('52494646') && headerHex.substr(16, 8) === '57454250') {
                        isMatch = true;
                        detectedType = 'webp';
                    } else if (headerHex.startsWith('255044462D')) { // %PDF-
                        isMatch = true;
                        detectedType = 'pdf';
                    }

                    if (!isMatch) {
                        return resolve({ safe: false, reason: 'MIME Signature Mismatch: The file headers do not match legitimate JPG, PNG, WEBP, or PDF formats.' });
                    }

                    // 4. Text / Script Pattern Heuristics (Check for embedded webshells or malicious tags)
                    // Sample up to first 64KB for embedded scripts
                    const sampleChunk = bytes.slice(0, Math.min(bytes.length, 65536));
                    let textSample = '';
                    for (let i = 0; i < sampleChunk.length; i++) {
                        const code = sampleChunk[i];
                        if (code >= 32 && code <= 126) {
                            textSample += String.fromCharCode(code);
                        } else {
                            textSample += ' ';
                        }
                    }

                    // Anti-virus EICAR test string
                    if (textSample.includes('EICAR-STANDARD-ANTIVIRUS-TEST-FILE')) {
                        return resolve({ safe: false, reason: 'Virus Threat Detected: EICAR test signature matched.' });
                    }

                    // Suspicious web shells or scripts embedded in document
                    const maliciousScriptKeywords = [
                        '<?php', '<?=', '<script', 'eval(', 'base64_decode(', 'shell_exec(',
                        'passthru(', 'system(', 'powershell', 'cmd.exe', '/javascript', '/launch'
                    ];

                    for (const kw of maliciousScriptKeywords) {
                        if (textSample.toLowerCase().includes(kw)) {
                            return resolve({ safe: false, reason: `Suspicious code pattern detected (${kw}). Only genuine ID documents are accepted.` });
                        }
                    }

                    // All scans passed
                    resolve({ safe: true, type: detectedType });
                } catch (err) {
                    resolve({ safe: false, reason: 'Could not read file binary buffer: ' + err.message });
                }
            };

            reader.onerror = function() {
                resolve({ safe: false, reason: 'Error reading file on device.' });
            };

            reader.readAsArrayBuffer(file);
        });
    }

    if (idFileInput) {
        idFileInput.addEventListener('change', async function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];

                // Show scanning indicator
                isIdFileVerifiedClean = false;
                if (idFilePrompt) idFilePrompt.style.display = 'none';
                if (idFileSelected) idFileSelected.style.display = 'none';
                if (idFileScanning) idFileScanning.style.display = 'flex';

                // Artificial micro-delay to ensure smooth UX and allow background worker to inspect
                await new Promise(r => setTimeout(r, 450));

                const scanResult = await scanFileForVirusesAndThreats(file);

                if (idFileScanning) idFileScanning.style.display = 'none';

                if (!scanResult.safe) {
                    alert(`🛡️ Anti-Virus & Security Alert:\n\n${scanResult.reason}\n\nThe selected file was rejected for sanctuary safety. Please upload a genuine photo or PDF of your Government ID card.`);
                    this.value = '';
                    isIdFileVerifiedClean = false;
                    if (idFilePrompt) idFilePrompt.style.display = 'flex';
                    if (idFileSelected) idFileSelected.style.display = 'none';
                    return;
                }

                // File verified clean & safe
                isIdFileVerifiedClean = true;
                const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
                if (idFileNameEl) idFileNameEl.innerText = file.name;
                const autoCompressActive = document.getElementById('modal-auto-compress')?.checked ?? true;
                if (idFileSizeEl) idFileSizeEl.innerText = `(${sizeMb > 0 ? sizeMb : '<0.1'} MB ${autoCompressActive ? '• ⚡ Auto-Compress' : ''})`;
                if (idFilePrompt) idFilePrompt.style.display = 'none';
                if (idFileSelected) idFileSelected.style.display = 'flex';
            } else {
                isIdFileVerifiedClean = false;
                if (idFilePrompt) idFilePrompt.style.display = 'flex';
                if (idFileScanning) idFileScanning.style.display = 'none';
                if (idFileSelected) idFileSelected.style.display = 'none';
            }
        });
    }

    if (btnRemoveIdFile) {
        btnRemoveIdFile.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (idFileInput) idFileInput.value = '';
            isIdFileVerifiedClean = false;
            if (idFilePrompt) idFilePrompt.style.display = 'flex';
            if (idFileScanning) idFileScanning.style.display = 'none';
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
                } else if (typeof payload[key] === 'boolean') {
                    formData.append(key, payload[key] ? '1' : '0');
                } else if (payload[key] !== null && payload[key] !== undefined) {
                    formData.append(key, payload[key]);
                }
            }
            if (idFileInput && idFileInput.files && idFileInput.files[0] && isIdFileVerifiedClean) {
                formData.append('id_proof_file', idFileInput.files[0]);
                const autoCompressCb = document.getElementById('modal-auto-compress');
                formData.append('auto_compress', (autoCompressCb && autoCompressCb.checked) ? '1' : '0');
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
        
        let villaSlug = modalVillaSelect?.value || 'treehouse';
        let villasList = [];
        let isMultiRoom = false;

        if (window.selectedMultiChaletCombo && window.selectedMultiChaletCombo.chalets && window.selectedMultiChaletCombo.chalets.length > 0) {
            villasList = window.selectedMultiChaletCombo.chalets.map(c => c.linked_room_slug || c.slug);
            villaSlug = villasList.join(', ');
            isMultiRoom = true;
        }

        const adultsCount = parseInt(modalAdultsInput?.value || "2", 10);
        const kidsCount = parseInt(modalKidsInput?.value || "0", 10);
        const guestsCount = adultsCount + kidsCount;

        const checkin = modalCheckin?.value || '';
        const checkout = modalCheckout?.value || '';

        // Duplex tier choice
        const tierRadio = document.querySelector('input[name="modal_tier"]:checked');
        const modalTier = tierRadio ? tierRadio.value : 'full';
        let duplexUnit = 'full';
        if (modalTier === 'single_room') {
            const wingRadio = document.querySelector('input[name="modal_duplex_wing"]:checked');
            duplexUnit = wingRadio ? wingRadio.value : 'left';
        }

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
                const price = parseFloat(card.getAttribute('data-dish-price') || "0");
                const mealTimeInp = card.querySelector('.dish-meal-time-val');
                const mealTime = mealTimeInp ? mealTimeInp.value : (card.getAttribute('data-default-meal') || cat || 'breakfast');
                if (qty > 0) {
                    foodItems.push({
                        id: card.getAttribute('data-dish-id'),
                        category: cat,
                        meal_time: mealTime,
                        heading: card.getAttribute('data-dish-name'),
                        subtitle: card.getAttribute('data-dish-subtitle') || '',
                        price: price,
                        quantity: qty,
                        subtotal: qty * price
                    });
                }
            });
        }

        // Billing & GST Preference (Always 5% GST; With or Without Company Address)
        const billingTypeRadio = document.querySelector('input[name="modal_billing_type"]:checked');
        const billingType = billingTypeRadio ? billingTypeRadio.value : 'gst_without_address';
        const isWithAddress = (billingType === 'gst_with_address');
        const gstNumber = isWithAddress ? (document.getElementById('modal-gst-number')?.value.trim().toUpperCase() || '') : '';
        const billingName = isWithAddress ? (document.getElementById('modal-billing-name')?.value.trim() || '') : '';
        const billingAddress = isWithAddress ? (document.getElementById('modal-billing-address')?.value.trim() || '') : '';

        const bookingFormEl = document.getElementById('luxury-booking-form');
        const gstRateAttr = parseFloat(bookingFormEl?.getAttribute('data-gst-rate') || '5');
        const gstRate = (isNaN(gstRateAttr) || gstRateAttr <= 0) ? 5 : gstRateAttr;

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
            villas: villasList,
            is_multi_room: isMultiRoom,
            tier: modalTier,
            duplex_unit: duplexUnit,
            adults: adultsCount,
            kids: kidsCount,
            guests: guestsCount,
            checkin: checkin,
            checkout: checkout,
            addons: addonsList.join(', '),
            notes: guestNotes,
            food_items: foodItems,
            food_skipped: isAllFoodSkipped || (foodItems.length === 0),
            billing_type: billingType,
            gst_number: gstNumber,
            billing_name: billingName,
            billing_address: billingAddress,
            gst_percentage: gstRate,
            create_account: createAccount,
            password: password
        };
    }

    function resetBookingModalState() {
        const form = document.getElementById('luxury-booking-form');
        const confirmBox = document.getElementById('booking-confirmation-state');
        const stickyBar = document.getElementById('modal-sticky-checkout-bar');
        const modalHeader = document.querySelector('.booking-modal-header');
        const submitDirectBtn = document.getElementById('btn-submit-booking-direct');
        const mscbReserveBtn = document.getElementById('btn-mscb-submit');

        if (form) form.style.display = 'block';
        if (confirmBox) confirmBox.style.display = 'none';
        if (stickyBar) stickyBar.style.display = '';
        if (modalHeader) modalHeader.style.display = '';

        if (submitDirectBtn) {
            submitDirectBtn.disabled = false;
            submitDirectBtn.innerHTML = '<i class="fa-solid fa-receipt"></i> <span>Confirm Reservation & Generate PDF Receipt</span>';
        }
        if (mscbReserveBtn) {
            mscbReserveBtn.disabled = false;
            mscbReserveBtn.classList.remove('btn-mscb-disabled');
            mscbReserveBtn.innerHTML = '<span>Confirm &amp; Reserve</span> <i class="fa-solid fa-arrow-right"></i>';
        }
    }
    window.resetBookingModalState = resetBookingModalState;

    function showBookingConfirmationState(res) {
        const form = document.getElementById('luxury-booking-form');
        const confirmBox = document.getElementById('booking-confirmation-state');
        const stickyBar = document.getElementById('modal-sticky-checkout-bar');
        const modalHeader = document.querySelector('.booking-modal-header');
        const modalContainer = document.querySelector('.booking-modal-container');
        const modalContent = document.querySelector('.booking-modal-content');

        if (!confirmBox) return;

        if (form) form.style.display = 'none';
        if (stickyBar) stickyBar.style.display = 'none';
        if (modalHeader) modalHeader.style.display = 'none';
        confirmBox.style.display = 'block';

        if (modalContainer) modalContainer.scrollTop = 0;
        if (modalContent) modalContent.scrollTop = 0;

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
            receiptBtn.setAttribute('href', res.receipt_url);
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
    const mscbReserveBtn = document.getElementById('btn-mscb-submit');

    if (mscbReserveBtn) {
        mscbReserveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const confirmBox = document.getElementById('booking-confirmation-state');
            if (confirmBox && confirmBox.style.display !== 'none') {
                const stickyBar = document.getElementById('modal-sticky-checkout-bar');
                if (stickyBar) stickyBar.style.display = 'none';
                return;
            }
            if (submitBookingDirectBtn && !submitBookingDirectBtn.disabled) {
                submitBookingDirectBtn.click();
            }
        });
    }

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

            if (!idFileInput || !idFileInput.files || !idFileInput.files[0] || !isIdFileVerifiedClean) {
                alert('⚠️ Mandatory Requirement:\nPlease upload a photo or PDF of your Government ID Proof (Aadhaar, Passport, Driving License, etc.) before confirming your reservation.');
                const box = document.getElementById('id-proof-upload-box');
                if (box) {
                    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    box.style.borderColor = '#DC2626';
                    box.style.background = '#FEF2F2';
                    setTimeout(() => {
                        box.style.borderColor = '#0284C7';
                        box.style.background = '#F0F9FF';
                    }, 3500);
                }
                return;
            }

            if (!isCurrentVillaAvailable) {
                showRealtimeConflictAlert(
                    'Chalet Already Reserved',
                    'We apologize, but this chalet has already been reserved for the selected dates (via Direct Website, MakeMyTrip, or Airbnb). Please select alternative dates.'
                );
                return;
            }

            if (payload.billing_type === 'gst_with_address') {
                if (!payload.gst_number || payload.gst_number.length < 8) {
                    alert('Please enter your valid 15-character GSTIN (GST Number) for your GST Tax Invoice.');
                    document.getElementById('modal-gst-number')?.focus();
                    return;
                }
                if (!payload.billing_name) {
                    alert('Please enter your Registered Billing Company / Name for the GST Invoice.');
                    document.getElementById('modal-billing-name')?.focus();
                    return;
                }
                if (!payload.billing_address) {
                    alert('Please enter your Registered Company / Billing Address for the GST Invoice.');
                    document.getElementById('modal-billing-address')?.focus();
                    return;
                }
            }

            if (payload.create_account && (!payload.password || payload.password.length < 4)) {
                alert('Please enter a password with at least 4 characters for your permanent account.');
                document.getElementById('modal-password')?.focus();
                return;
            }

            submitBookingDirectBtn.disabled = true;
            submitBookingDirectBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Recording Reservation...</span>';

            if (mscbReserveBtn) {
                mscbReserveBtn.disabled = true;
                mscbReserveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Recording...</span>';
            }

            const res = await saveBookingToDatabase(payload);

            if (res && res.success) {
                showBookingConfirmationState(res);
            } else {
                submitBookingDirectBtn.disabled = false;
                submitBookingDirectBtn.innerHTML = '<i class="fa-solid fa-receipt"></i> <span>Confirm Reservation & Generate PDF Receipt</span>';
                if (mscbReserveBtn) {
                    mscbReserveBtn.disabled = false;
                    mscbReserveBtn.innerHTML = '<span>Confirm &amp; Reserve</span> <i class="fa-solid fa-arrow-right"></i>';
                }
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

            if (!idFileInput || !idFileInput.files || !idFileInput.files[0] || !isIdFileVerifiedClean) {
                alert('⚠️ Mandatory Requirement:\nPlease upload a photo or PDF of your Government ID Proof (Aadhaar, Passport, Driving License, etc.) before connecting with the Concierge.');
                const box = document.getElementById('id-proof-upload-box');
                if (box) {
                    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    box.style.borderColor = '#DC2626';
                    box.style.background = '#FEF2F2';
                    setTimeout(() => {
                        box.style.borderColor = '#0284C7';
                        box.style.background = '#F0F9FF';
                    }, 3500);
                }
                return;
            }

            if (payload.billing_type === 'gst') {
                if (!payload.gst_number || payload.gst_number.length < 8) {
                    alert('Please enter your valid 15-character GSTIN (GST Number) for your GST Tax Invoice.');
                    document.getElementById('modal-gst-number')?.focus();
                    return;
                }
                if (!payload.billing_name) {
                    alert('Please enter your Registered Billing Company / Name for the GST Invoice.');
                    document.getElementById('modal-billing-name')?.focus();
                    return;
                }
            }

            whatsappSubmitBtn.disabled = true;
            whatsappSubmitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Connecting...</span>';
            if (mscbReserveBtn) {
                mscbReserveBtn.disabled = true;
                mscbReserveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Connecting...</span>';
            }

            const saveRes = await saveBookingToDatabase(payload);
            const refCode = saveRes?.reference_code || 'FF-' + Math.floor(1000 + Math.random() * 9000);

            // Construct WhatsApp message
            const selectedOption = modalVillaSelect?.options[modalVillaSelect.selectedIndex];
            let villaName = selectedOption?.getAttribute('data-name') || 'Sanctuary Suite';
            const structureType = selectedOption?.getAttribute('data-structure-type') || 'single_hut';
            if (structureType === 'duplex_hut') {
                const wingTitle = payload.duplex_unit === 'left' ? 'Left Suite (Wing A)' : (payload.duplex_unit === 'right' ? 'Right Suite (Wing B)' : 'Both Suites');
                villaName += (payload.tier === 'single_room') ? ` [Duplex: Single Room — ${wingTitle}]` : ' [Full Duplex: Both Suites]';
            }
            const total = document.getElementById('summary-total')?.innerText || '₹14,500';

            let foodSummary = 'Farm Dining Plan on Arrival';
            if (payload.food_items && payload.food_items.length > 0) {
                foodSummary = '\n' + payload.food_items.map(f => `  - ${f.heading} × ${f.quantity} [${(f.meal_time || f.category || 'MEAL').toUpperCase()}] (₹${(f.price * f.quantity).toLocaleString('en-IN')})`).join('\n');
            }

            let expNote = payload.addons ? `\n• *Experiences (On-Site Direct Pay)*:\n${payload.addons.split(',').map(a => `  - ${a.trim()} (Payable On-Site)`).join('\n')}` : '';
            let cityNote = payload.city_state ? `\n• *City/Origin*: ${payload.city_state}` : '';
            let idNote = payload.id_proof_type ? (`\n• *ID Proof*: ${payload.id_proof_type}` + (payload.has_id_file ? ' (📎 Document Attached)' : '')) : '';

            let billingNote = `\n• *Invoice Type*: ${payload.billing_type === 'gst_with_address' ? `Official B2B GST Tax Invoice (GSTIN: ${payload.gst_number})` : 'Standard GST Bill (5% GST Incl.)'}`;
            if (payload.billing_type === 'gst_with_address' && payload.billing_name) {
                billingNote += `\n• *Company*: ${payload.billing_name}`;
            }
            if (payload.billing_type === 'gst_with_address' && payload.billing_address) {
                billingNote += `\n• *Address*: ${payload.billing_address}`;
            }

            const message = `🌿 *RESERVATION REQUEST — FOOD FOREST KANTHALLOOR* 🌿\n\n` +
                `• *Booking Reference*: #${refCode}\n` +
                `• *Guest Name*: ${payload.name}\n` +
                `• *Contact*: ${payload.phone}\n` +
                `• *Suite*: ${villaName}\n` +
                `• *Check-in*: ${payload.checkin}\n` +
                `• *Check-out*: ${payload.checkout}\n` +
                `• *Occupancy*: ${payload.adults} Adults, ${payload.kids} Children` +
                `${cityNote}${idNote}${billingNote}\n` +
                `• *Curated Gastronomy*: ${foodSummary}` +
                `${expNote}\n` +
                `• *Total Payable*: ${total}\n\n` +
                `Kindly confirm availability. Luxury receipt is available at Ref #${refCode}.`;

            const encodedMessage = encodeURIComponent(message);
            const whatsappNumber = saveRes?.concierge_whatsapp || '919234567890';
            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
            window.open(whatsappUrl, '_blank');

            whatsappSubmitBtn.disabled = false;
            whatsappSubmitBtn.innerHTML = '<i class="fa-brands fa-whatsapp"></i> <span>Instant WhatsApp Concierge Confirmation</span>';
            if (mscbReserveBtn && (!saveRes || !saveRes.success)) {
                mscbReserveBtn.disabled = false;
                mscbReserveBtn.innerHTML = '<span>Confirm &amp; Reserve</span> <i class="fa-solid fa-arrow-right"></i>';
            }

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

