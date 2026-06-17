/* -------------------------------------------------------------
   Food Forest — Main Interactive Javascript (GSAP, ScrollTrigger, Lenis)
   ------------------------------------------------------------- */

document.addEventListener("DOMContentLoaded", () => {
    // Register ScrollTrigger plugin with GSAP
    gsap.registerPlugin(ScrollTrigger);

    // 1. Initialize Lenis Smooth Scrolling
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        direction: 'vertical',
        gestureDirection: 'vertical',
        smooth: true,
        mouseMultiplier: 1,
        smoothTouch: false,
        touchMultiplier: 2,
    });

    // Sync ScrollTrigger with Lenis and tick Lenis via GSAP ticker for synchronized updates
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);

    // 2. Custom Character Split Text Engine
    function initSplitText() {
        document.querySelectorAll('.split-text').forEach(el => {
            const text = el.innerText;
            el.innerHTML = '';
            
            // Handle newlines if present
            const lines = text.split('\n');
            lines.forEach((line, lineIdx) => {
                const lineSpan = document.createElement('span');
                lineSpan.style.display = 'block';
                lineSpan.style.overflow = 'hidden';
                
                line.split('').forEach(char => {
                    const span = document.createElement('span');
                    span.className = 'char';
                    span.innerText = char === ' ' ? '\u00A0' : char;
                    lineSpan.appendChild(span);
                });
                
                el.appendChild(lineSpan);
                if (lineIdx < lines.length - 1) {
                    el.appendChild(document.createElement('br'));
                }
            });
        });
    }
    initSplitText();

    // 3. Cinematic Hero Entrance Animation (Preloader Removed)
    function startIntroAnimation() {
        const introTl = gsap.timeline();
        
        introTl.call(() => {
                   // Enable scroll after loading has finished
                   document.body.style.overflowY = 'auto';
                   // Initialize main scroll triggers
                   initScrollAnimations();
                   // Refresh ScrollTrigger to recalculate layout measurements
                   ScrollTrigger.refresh();
               })
               // Hero Entrance
               .fromTo('.hero-bg-container', { scale: 1.2 }, { scale: 1, duration: 2.2, ease: 'power3.out' })
               .fromTo('.hero-tagline', { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=1.8')
               .fromTo('.hero-title .char', { yPercent: 100 }, { yPercent: 0, stagger: 0.015, duration: 1.0, ease: 'power4.out' }, '-=1.6')
               .fromTo('.hero-desc', { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=1.2')
               .fromTo('.hero-cta', { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=1.0')
               .fromTo('.hero-scroll-indicator', { opacity: 0 }, { opacity: 0.7, duration: 0.5 }, '-=0.6');
    }
    
    // Prevent scrolling until hero image is ready
    document.body.style.overflowY = 'hidden';

    const heroImg = document.querySelector('.hero-bg-img');
    const heroLoader = document.querySelector('.hero-loader-overlay');
    let heroInitialized = false;

    function initHeroReveal() {
        if (heroInitialized) return;
        heroInitialized = true;

        if (heroLoader) {
            heroLoader.style.opacity = '0';
            setTimeout(() => {
                heroLoader.style.display = 'none';
            }, 800);
        }

        if (heroImg) {
            heroImg.classList.add('loaded');
        }

        // Delay slightly for the blur-up fade-in to settle
        setTimeout(startIntroAnimation, 200);
    }

    if (heroImg) {
        if (heroImg.complete) {
            initHeroReveal();
        } else {
            heroImg.addEventListener('load', initHeroReveal);
            // Safety fallback timeout (2.5 seconds max)
            setTimeout(initHeroReveal, 2500);
        }
    } else {
        initHeroReveal();
    }

    // 5. Proximity-Based Magnetic Gravity Effect
    const magnetics = document.querySelectorAll(".magnetic, a, button, .nav-item, .btn-primary, .btn-book-now, .social-icon, .room-select-btn");
    
    window.addEventListener("mousemove", (e) => {
        const mouseX = e.clientX;
        const mouseY = e.clientY;
        
        magnetics.forEach(el => {
            // Skip elements that are not currently displayed (like mobile links or hidden toggles)
            if (el.offsetParent === null) return;
            
            const rect = el.getBoundingClientRect();
            const strength = parseFloat(el.getAttribute("data-strength")) || (el.tagName === 'A' && !el.classList.contains('btn-book-now') ? 8 : 16);
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            
            const dx = mouseX - cx;
            const dy = mouseY - cy;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            // Magnet threshold radius (70px)
            const threshold = 70;
            
            if (distance < threshold) {
                const ratio = (threshold - distance) / threshold;
                gsap.to(el, {
                    x: dx * ratio * (strength / 20),
                    y: dy * ratio * (strength / 20),
                    duration: 0.3,
                    ease: "power2.out"
                });
            } else {
                gsap.to(el, {
                    x: 0,
                    y: 0,
                    duration: 0.6,
                    ease: "elastic.out(1.1, 0.4)"
                });
            }
        });
    });

    // 6. Header Scroll Style Toggle
    const header = document.querySelector(".main-header");
    ScrollTrigger.create({
        start: "top -50",
        onUpdate: (self) => {
            if (self.direction === 1) {
                // Scrolling down - hide header slightly or make translucent
                header.classList.add("scrolled");
            } else {
                // Scrolling up - show translucent header
                header.classList.add("scrolled");
            }
            if (window.scrollY < 50) {
                header.classList.remove("scrolled");
            }
        }
    });

    // 7. Mobile Navigation Toggle
    const mobToggle = document.querySelector(".mobile-nav-toggle");
    const mobMenu = document.querySelector(".mobile-menu");
    
    if (mobToggle && mobMenu) {
        mobToggle.addEventListener("click", () => {
            mobToggle.classList.toggle("active");
            mobMenu.classList.toggle("active");
            
            if (mobMenu.classList.contains("active")) {
                // Pin scroll when menu is open
                lenis.stop();
            } else {
                lenis.start();
            }
        });

        // Close menu on click of links
        document.querySelectorAll(".mobile-link").forEach(link => {
            link.addEventListener("click", () => {
                mobToggle.classList.remove("active");
                mobMenu.classList.remove("active");
                lenis.start();
            });
        });
    }

    // 8. Testimonials Carousel Slider
    const testimonialWrapper = document.getElementById("testimonials-wrapper");
    const prevBtn = document.getElementById("btn-prev-testimonial");
    const nextBtn = document.getElementById("btn-next-testimonial");
    let testIdx = 0;
    const totalTestimonials = document.querySelectorAll(".testimonial-card").length;

    if (testimonialWrapper && prevBtn && nextBtn) {
        function updateTestimonialSlider() {
            gsap.to(testimonialWrapper, {
                xPercent: -100 * testIdx,
                duration: 0.8,
                ease: "power3.out"
            });
        }

        prevBtn.addEventListener("click", () => {
            testIdx = (testIdx > 0) ? testIdx - 1 : totalTestimonials - 1;
            updateTestimonialSlider();
        });

        nextBtn.addEventListener("click", () => {
            testIdx = (testIdx < totalTestimonials - 1) ? testIdx + 1 : 0;
            updateTestimonialSlider();
        });
    }

    // 9. Scroll Reveal Animations (ScrollTrigger)
    function initScrollAnimations() {
        // Split-text animations on scroll
        document.querySelectorAll('.welcome-section .section-title.split-text, .experiences-section .section-title.split-text, .why-mudhouse-section .section-title.split-text, .seasons-section .section-title.split-text, .dining-section .section-title.split-text, .testimonials-section .section-title.split-text, .blog-section .section-title.split-text').forEach(title => {
            gsap.fromTo(title.querySelectorAll('.char'), 
                { yPercent: 100 }, 
                {
                    yPercent: 0,
                    stagger: 0.012,
                    duration: 0.8,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: title,
                        start: 'top 85%',
                        toggleActions: 'play none none none'
                    }
                }
            );
        });

        // Parallax image scrolling
        document.querySelectorAll('[data-speed]').forEach(el => {
            const speed = parseFloat(el.getAttribute('data-speed')) || 0.1;
            gsap.to(el, {
                yPercent: speed * 100,
                ease: 'none',
                scrollTrigger: {
                    trigger: el,
                    scrub: true,
                    start: 'top bottom',
                    end: 'bottom top'
                }
            });
        });

        // Standard Scroll Reveal items (cards, details)
        document.querySelectorAll('.scroll-reveal').forEach(el => {
            gsap.fromTo(el,
                { opacity: 0, y: 50 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: el,
                        start: "top 88%",
                        toggleActions: "play none none none"
                    }
                }
            );
        });
    }
});
