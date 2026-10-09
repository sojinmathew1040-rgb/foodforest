/* -------------------------------------------------------------
   Food Forest — Ultra-Luxury 3D Architectural & 360° Gimbal Tour
   (Scroll: Canopy Exterior ➔ Window Zoom Fly-In ➔ 360° Center Room Gimbal Sweep)
   ------------------------------------------------------------- */

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("rooms-experience");
    const canvas = document.getElementById("rooms-webgl-canvas");
    const fallback = document.getElementById("webgl-fallback-container");

    if (!container || !canvas) {
        return; // Skip on pages without 3D rooms section (e.g. gallery.php, booking.php)
    }

    if (typeof gsap === "undefined" || typeof ScrollTrigger === "undefined" || typeof THREE === "undefined") {
        console.warn("GSAP, ScrollTrigger or THREE not loaded");
        if (fallback) fallback.style.display = "block";
        if (canvas) canvas.style.display = "none";
        return;
    }
    gsap.registerPlugin(ScrollTrigger);

    // 1. WebGL & Device Support Check
    function hasWebGL() {
        try {
            const testCanvas = document.createElement('canvas');
            return !!(window.WebGLRenderingContext && (testCanvas.getContext('webgl') || testCanvas.getContext('experimental-webgl')));
        } catch (e) {
            return false;
        }
    }

    if (!hasWebGL()) {
        if (canvas) canvas.style.display = "none";
        const tourOverlay = document.querySelector(".tour-overlay-container");
        const tourVignette = document.querySelector(".tour-vignette");
        if (tourOverlay) tourOverlay.style.display = "none";
        if (tourVignette) tourVignette.style.display = "none";
        if (fallback) fallback.style.display = "block";
        if (container) {
            container.style.height = "auto";
            container.style.minHeight = "auto";
        }
        return; // Fallback only if device lacks WebGL
    }

    // 2. Initialize Three.js Scene
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0A150F);

    // Gimbal Camera located at center of room (0, 0, 0)
    // Starts at 65° for exterior plane, smoothly transitions to wide-angle 85° inside room
    const camera = new THREE.PerspectiveCamera(65, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 0, 0.01);

    // Dynamic FOV & 2-Axis Look Around Controls
    let targetFOV = 65;
    let baseInteriorFOV = (window.innerWidth < 768) ? 82 : 85;
    let userZoomOffset = 0; // Negative = wider angle (up to 95°+), Positive = zoomed in
    let currentRotX = 0;
    let targetRotX = 0;
    let prevPointerY = 0;
    let touchStartDist = 0;
    const tourControls = document.getElementById("tour-quick-controls");

    const isMobileDevice = window.innerWidth < 1025 || ('ontouchstart' in window);
    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        antialias: !isMobileDevice,
        alpha: false,
        powerPreference: "high-performance"
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, isMobileDevice ? 1.5 : 2));

    // 3. Stay Configurations & High-Resolution Architectural Assets
    const STAY_DATA = {
        treehouse: {
            badge: "FOOD FOREST IMMERSIVE ARCHITECTURAL TOUR",
            title: "The Canopy Treehouse",
            subtitle: "Scroll down to fly from the misty forest canopy directly inside the 360° suite.",
            exteriorImg: "assets/images/treehouse_exterior_front.jpg",
            interiorImg: "assets/images/treehouse_360_pano.jpg",
            stages: [
                {
                    pill: '<i class="fa-solid fa-tree"></i> 30FT ELEVATED CANOPY',
                    heading: "Front Exterior & Forest Suspension",
                    text: "Suspended 30 feet above the forest floor within ancient trees. Crafted with wild teak timber, an open cantilevered deck, and expansive curved glass."
                },
                {
                    pill: '<i class="fa-solid fa-mountain-sun"></i> 01 • 180° VALLEY GLASSWORK',
                    heading: "Floor-to-Ceiling Curved Bay Window",
                    text: "An expansive architectural curved window framing floating clouds, high-altitude tea valleys, and morning mountain mist."
                },
                {
                    pill: '<i class="fa-solid fa-wind"></i> 02 • MISTY CANOPY DECK',
                    heading: "Private Cantilevered Timber Balcony",
                    text: "Step directly outside into the clouds. An open timber deck perched 30 feet high in ancient trees for birdsong and organic mountain tea."
                },
                {
                    pill: '<i class="fa-solid fa-bed"></i> 03 • WILD TEAK BED SUITE',
                    heading: "Handcrafted Artisan King Bed",
                    text: "Hand-hewn from natural wild teak, dressed in 100% breathable organic linen, accompanied by handcrafted bedside lanterns and radial wooden ceiling beams."
                },
                {
                    pill: '<i class="fa-solid fa-fire"></i> 04 • THE FOREST HEARTH',
                    heading: "Hand-Cut Stone Fireplace & Lounge",
                    text: "Warm authentic stone fireplace with crackling hearth wood, curved luxury sofa, and library nook to relax on crisp mountain evenings."
                }
            ],
            progressLabels: [
                "Canopy Exterior",
                "Panoramic Bay",
                "Forest Deck",
                "Teak Suite",
                "Stone Hearth"
            ],
            ctaVilla: "treehouse",
            ctaLabel: "Reserve Canopy Treehouse"
        },
        mudhouse: {
            badge: "FOOD FOREST IMMERSIVE ARCHITECTURAL TOUR",
            title: "The Earthen Mudhouse",
            subtitle: "Scroll down to journey from the terraced flower garden into the hand-sculpted mudhouse suite.",
            exteriorImg: "assets/images/mudhouse_exterior.png",
            interiorImg: "assets/images/mudhouse_interior.png",
            stages: [
                {
                    pill: '<i class="fa-solid fa-house-chimney"></i> ORGANIC COB ARCHITECTURE',
                    heading: "Cob Exterior & Terraced Orchard Garden",
                    text: "Handcrafted from unbaked clay, straw, and river silt with terracotta tiled eaves, overlooking misty mountain apple orchards and organic flower gardens."
                },
                {
                    pill: '<i class="fa-solid fa-mountain-sun"></i> 01 • HIGH GLASS FAÇADE',
                    heading: "Floor-to-Ceiling Mountain Vista Glass",
                    text: "Dramatic cathedral-height glass portal framing panoramic views of Kanthalloor's cloud-swept peaks, rolling pine forests, and stone garden walkways."
                },
                {
                    pill: '<i class="fa-solid fa-couch"></i> 02 • TRADITIONAL DAYBED ALCOVE',
                    heading: "Sculpted Earthen Alcove & Teak Pillars",
                    text: "An organic sunlit daybed recessed into sculpted clay walls with antique carved teak columns, designed for afternoon reading and tranquil tea sessions."
                },
                {
                    pill: '<i class="fa-solid fa-bed"></i> 03 • HANDCRAFTED LIVING & SLEEP SUITE',
                    heading: "Artisan Wood Furniture & Terracotta Floors",
                    text: "Handmade terracotta floor tiles, antique wood coffee tables, woven carpets, and natural linen luxury bedding under soaring exposed timber roof trusses."
                },
                {
                    pill: '<i class="fa-solid fa-fire"></i> 04 • CLAY HEARTH & BRASS LANTERNS',
                    heading: "Artisan Stone Fireplace & Ambient Glow",
                    text: "Authentic hearth with hand-cut stone surround, brass lantern lighting, and curated botanical ceramics creating an enchanting evening sanctuary."
                }
            ],
            progressLabels: [
                "Garden Exterior",
                "Glass Façade",
                "Daybed Alcove",
                "Living Suite",
                "Clay Hearth"
            ],
            ctaVilla: "mudhouse",
            ctaLabel: "Reserve Earthen Mudhouse"
        }
    };

    if (window.ESTATE_DYNAMIC_STAY) {
        if (window.ESTATE_DYNAMIC_STAY.treehouse) {
            Object.assign(STAY_DATA.treehouse, window.ESTATE_DYNAMIC_STAY.treehouse);
        }
        if (window.ESTATE_DYNAMIC_STAY.mudhouse) {
            Object.assign(STAY_DATA.mudhouse, window.ESTATE_DYNAMIC_STAY.mudhouse);
        }
    }

    let activeStay = (window.ESTATE_DYNAMIC_STAY && window.ESTATE_DYNAMIC_STAY.defaultStay) ? window.ESTATE_DYNAMIC_STAY.defaultStay : 'treehouse';
    const textureLoader = new THREE.TextureLoader();

    // Dynamic Exterior Plane Geometry Adjustment to preserve natural photo aspect ratio and avoid excessive zoom/crop
    function updateExteriorPlaneGeometry(stayKey = activeStay) {
        if (!extMesh) return;

        const tex = (textures[stayKey] && textures[stayKey].exterior) ? textures[stayKey].exterior : null;
        let imgAspect = 16 / 9;

        if (tex && tex.image && tex.image.width && tex.image.height && tex.image.width > 0 && tex.image.height > 0) {
            imgAspect = tex.image.width / tex.image.height;
        }

        // Camera visible frustum at exterior plane distance (Z = -6.5)
        const dist = Math.abs(extMesh.position.z - camera.position.z);
        const vFovRad = (camera.fov * Math.PI) / 180;
        const frustumH = 2 * dist * Math.tan(vFovRad / 2);
        const frustumW = frustumH * camera.aspect;

        let planeW, planeH;

        // Fit the photo so wide compositions are completely visible without harsh cropping
        if (camera.aspect >= imgAspect) {
            // Viewport is wider than or equal to image aspect:
            // Fit height to viewport frustum, compute width proportionally
            planeH = frustumH;
            planeW = planeH * imgAspect;
        } else {
            // Viewport is narrower than image aspect (e.g. mobile or wide photo on desktop):
            // Fit width to viewport frustum, compute height proportionally
            planeW = frustumW;
            planeH = planeW / imgAspect;
        }

        if (extMesh.geometry) {
            extMesh.geometry.dispose();
        }
        extMesh.geometry = new THREE.PlaneGeometry(planeW, planeH);
    }

    // Dynamic 360 Sphere Starting Alignment
    // Base alignment is +Math.PI / 2 which maps u=0 (the exact start/left edge of the uploaded panorama) directly in front of the camera (0, 0, -1)
    function updateSphereOrientation(stayKey = activeStay) {
        if (!sphereMesh) return;
        const stayOffset = (STAY_DATA[stayKey] && typeof STAY_DATA[stayKey].panoOffset === 'number')
            ? STAY_DATA[stayKey].panoOffset
            : 0;
        sphereMesh.rotation.y = (Math.PI / 2) + stayOffset;
    }

    // Configure 360 Interior Texture Aspect Ratio
    // Prevents vertical distortion/stretching when users upload non-2:1 smartphone panoramas (e.g. 2.8:1)
    function configureInteriorTexture(tex) {
        if (!tex || !tex.image) return;
        const imgW = tex.image.width || 0;
        const imgH = tex.image.height || 0;
        if (imgW > 0 && imgH > 0) {
            const aspect = imgW / imgH;
            if (aspect > 2.15) {
                const scaleY = 2.0 / aspect;
                tex.repeat.set(1, scaleY);
                tex.offset.set(0, (1 - scaleY) / 2);
                tex.wrapS = THREE.RepeatWrapping;
                tex.wrapT = THREE.ClampToEdgeWrapping;
            } else if (aspect < 1.85) {
                const scaleX = aspect / 2.0;
                tex.repeat.set(scaleX, 1);
                tex.offset.set((1 - scaleX) / 2, 0);
                tex.wrapS = THREE.ClampToEdgeWrapping;
                tex.wrapT = THREE.ClampToEdgeWrapping;
            } else {
                tex.repeat.set(1, 1);
                tex.offset.set(0, 0);
                tex.wrapS = THREE.RepeatWrapping;
                tex.wrapT = THREE.ClampToEdgeWrapping;
            }
            tex.needsUpdate = true;
        }
    }

    // Pre-load textures for both stays so switching is instantaneous
    const textures = {
        treehouse: {
            exterior: textureLoader.load(STAY_DATA.treehouse.exteriorImg, (tex) => {
                tex.minFilter = THREE.LinearFilter;
                tex.magFilter = THREE.LinearFilter;
                tex.generateMipmaps = false;
                if (activeStay === 'treehouse') {
                    updateExteriorPlaneGeometry('treehouse');
                }
                renderer.render(scene, camera);
            }),
            interior: textureLoader.load(STAY_DATA.treehouse.interiorImg, (tex) => {
                tex.minFilter = THREE.LinearFilter;
                tex.magFilter = THREE.LinearFilter;
                tex.generateMipmaps = false;
                configureInteriorTexture(tex);
                renderer.render(scene, camera);
            })
        },
        mudhouse: {
            exterior: textureLoader.load(STAY_DATA.mudhouse.exteriorImg, (tex) => {
                tex.minFilter = THREE.LinearFilter;
                tex.magFilter = THREE.LinearFilter;
                tex.generateMipmaps = false;
                if (activeStay === 'mudhouse') {
                    updateExteriorPlaneGeometry('mudhouse');
                }
                renderer.render(scene, camera);
            }),
            interior: textureLoader.load(STAY_DATA.mudhouse.interiorImg, (tex) => {
                tex.minFilter = THREE.LinearFilter;
                tex.magFilter = THREE.LinearFilter;
                tex.generateMipmaps = false;
                configureInteriorTexture(tex);
                renderer.render(scene, camera);
            })
        }
    };

    // 3A. Front Exterior Canopy Layer (Placed in front of camera at Z = -6.5)
    const extGeo = new THREE.PlaneGeometry(16, 9);
    const initialExtTex = (textures[activeStay] && textures[activeStay].exterior) ? textures[activeStay].exterior : textures.treehouse.exterior;
    const extMat = new THREE.MeshBasicMaterial({
        map: initialExtTex,
        transparent: true,
        opacity: 1.0,
        side: THREE.DoubleSide,
        depthWrite: false
    });
    const extMesh = new THREE.Mesh(extGeo, extMat);
    extMesh.position.set(0, 0, -6.5);
    scene.add(extMesh);
    updateExteriorPlaneGeometry(activeStay);

    // 3B. 360° Center Room Inverted Sphere (Interior Suite)
    const sphereGeo = new THREE.SphereGeometry(100, 64, 40);
    sphereGeo.scale(-1, 1, 1); // Invert faces so viewer stands inside looking out

    const initialIntTex = (textures[activeStay] && textures[activeStay].interior) ? textures[activeStay].interior : textures.treehouse.interior;
    const sphereMat = new THREE.MeshBasicMaterial({
        map: initialIntTex,
        transparent: true,
        opacity: 0.0,
        depthWrite: false
    });
    const sphereMesh = new THREE.Mesh(sphereGeo, sphereMat);
    updateSphereOrientation(activeStay);
    scene.add(sphereMesh);

    // 3C. Function to switch between Treehouse and Mudhouse
    function switchStay(stayKey) {
        if (!STAY_DATA[stayKey] || stayKey === activeStay) return;
        activeStay = stayKey;

        // 1. Update Tab Buttons
        document.querySelectorAll(".stay-tab-btn").forEach(btn => {
            if (btn.getAttribute("data-stay") === stayKey) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });

        // 2. Update Header
        const data = STAY_DATA[stayKey];
        const badgeEl = document.getElementById("tour-concept-badge");
        const titleEl = document.getElementById("tour-main-title");
        const subEl = document.getElementById("tour-main-subtitle");
        if (badgeEl) badgeEl.textContent = data.badge;
        if (titleEl) titleEl.textContent = data.title;
        if (subEl) subEl.textContent = data.subtitle;

        // 3. Update Floating Stage Cards
        for (let i = 1; i <= 5; i++) {
            const pillEl = document.getElementById(`stage${i}-pill`);
            const headEl = document.getElementById(`stage${i}-heading`);
            const textEl = document.getElementById(`stage${i}-text`);
            const stageInfo = data.stages[i - 1];
            if (pillEl && stageInfo) pillEl.innerHTML = stageInfo.pill;
            if (headEl && stageInfo) headEl.textContent = stageInfo.heading;
            if (textEl && stageInfo) textEl.textContent = stageInfo.text;
        }

        // 4. Update CTA Button
        const ctaBtn = document.getElementById("tour-cta-btn");
        const ctaLabel = document.getElementById("tour-cta-label");
        if (ctaBtn) {
            ctaBtn.setAttribute("data-villa", data.ctaVilla);
        }
        if (ctaLabel) {
            ctaLabel.textContent = data.ctaLabel;
        }

        // 5. Update Bottom Progress Step Labels
        for (let i = 1; i <= 5; i++) {
            const progLabelEl = document.getElementById(`prog-label-${i}`);
            if (progLabelEl && data.progressLabels[i - 1]) {
                progLabelEl.textContent = data.progressLabels[i - 1];
            }
        }

        // 6. Crossfade / Switch Three.js Textures
        if (textures[stayKey]) {
            extMat.map = textures[stayKey].exterior;
            extMat.needsUpdate = true;
            sphereMat.map = textures[stayKey].interior;
            sphereMat.needsUpdate = true;
            configureInteriorTexture(textures[stayKey].interior);
            updateExteriorPlaneGeometry(stayKey);
            updateSphereOrientation(stayKey);
        }

        renderer.render(scene, camera);
    }

    // 3C. Floating Warm Gold Mist / Dust Particles
    const particleCount = 75;
    const particleGeometry = new THREE.BufferGeometry();
    const particlePositions = new Float32Array(particleCount * 3);
    for (let i = 0; i < particleCount * 3; i += 3) {
        particlePositions[i] = (Math.random() - 0.5) * 28;
        particlePositions[i + 1] = (Math.random() - 0.5) * 18;
        particlePositions[i + 2] = (Math.random() - 0.5) * 28;
    }
    particleGeometry.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
    const particleMaterial = new THREE.PointsMaterial({
        color: 0xC5A059,
        size: 0.07,
        transparent: true,
        opacity: 0.5,
        blending: THREE.AdditiveBlending
    });
    const particles = new THREE.Points(particleGeometry, particleMaterial);
    scene.add(particles);

    // 4. UI Elements for Stage Tracking
    const stage1 = document.getElementById("tour-stage-1");
    const stage2 = document.getElementById("tour-stage-2");
    const stage3 = document.getElementById("tour-stage-3");
    const stage4 = document.getElementById("tour-stage-4");
    const stage5 = document.getElementById("tour-stage-5");

    const prog1 = document.getElementById("prog-step-1");
    const prog2 = document.getElementById("prog-step-2");
    const prog3 = document.getElementById("prog-step-3");
    const prog4 = document.getElementById("prog-step-4");
    const prog5 = document.getElementById("prog-step-5");

    const stages = [stage1, stage2, stage3, stage4, stage5];
    const progressSteps = [prog1, prog2, prog3, prog4, prog5];
    const mobileDots = document.querySelectorAll(".mobile-dot");
    let currentStepNum = 1;

    function setTourProgressStep(stepNum) {
        currentStepNum = stepNum;

        // Desktop step pills
        progressSteps.forEach((el, idx) => {
            if (!el) return;
            if (idx + 1 === stepNum) {
                el.classList.add("active");
            } else {
                el.classList.remove("active");
            }
        });

        // Mobile stepper dots
        mobileDots.forEach((dot, idx) => {
            if (idx + 1 === stepNum) {
                dot.classList.add("active");
            } else {
                dot.classList.remove("active");
            }
        });

        // Mobile step label
        const mobileLabel = document.getElementById("mobile-step-label");
        const activeStayData = STAY_DATA[activeStay];
        if (mobileLabel && activeStayData && activeStayData.progressLabels[stepNum - 1]) {
            mobileLabel.textContent = `0${stepNum} ${activeStayData.progressLabels[stepNum - 1]}`;
        }
    }

    function showCard(stepNum) {
        stages.forEach((card, idx) => {
            if (!card) return;
            if (idx + 1 === stepNum) {
                card.classList.add("is-visible");
            } else {
                card.classList.remove("is-visible");
            }
        });
    }

    // 5. Scroll-Driven Progression (Exterior ➔ Zoom Fly-In ➔ 360° Gimbal Rotation)
    let currentRotY = 0;
    let targetRotY = 0;
    let isUserDragging = false;
    let isInside = false;

    function applyScrollProgress(p) {
        const progress = Math.max(0, Math.min(1, p));

        // --- PHASE 1 & 2: Exterior Front & Zoom Fly-In (0.00 to 0.25) ---
        if (progress <= 0.25) {
            isInside = false;
            const t = progress / 0.25; // 0 to 1

            // Camera is looking forward at exterior window
            targetRotY = 0;
            targetRotX = 0;
            targetFOV = 65;
            if (tourControls) {
                tourControls.style.opacity = '0';
                tourControls.style.pointerEvents = 'none';
            }

            // Smooth, cinematic forward glide without harsh cropping or extreme zoom
            const scaleVal = 1.0 + Math.pow(t, 1.4) * 0.75;
            extMesh.scale.set(scaleVal, scaleVal, 1);

            // Crossfade into 360 interior smoothly as window entrance is reached
            if (t < 0.20) {
                extMat.opacity = 1.0;
                sphereMat.opacity = 0.0;
                setTourProgressStep(1);
                showCard(1);
            } else {
                const fade = (t - 0.20) / 0.80; // 0 to 1
                // Smooth sine/cosine crossfade for cinematic transparency blending
                extMat.opacity = Math.cos(fade * Math.PI * 0.5);
                sphereMat.opacity = Math.sin(fade * Math.PI * 0.5);

                if (t > 0.70) {
                    setTourProgressStep(2);
                    showCard(2);
                } else {
                    setTourProgressStep(1);
                    showCard(1);
                }
            }
        }
        // --- PHASE 3: Inside Suite 360° Gimbal Rotation (0.25 to 1.00) ---
        else {
            isInside = true;
            extMat.opacity = 0.0;
            sphereMat.opacity = 1.0;
            targetFOV = baseInteriorFOV + userZoomOffset;
            if (tourControls) {
                tourControls.style.opacity = '1';
                tourControls.style.pointerEvents = 'auto';
            }

            // Map progress 0.25 -> 1.00 into a full 360° circular sweep (0 to -2*PI)
            const tRot = (progress - 0.25) / 0.75; // 0 to 1
            if (!isUserDragging) {
                targetRotY = -tRot * Math.PI * 2;
            }

            // Sync Stage Cards & Progress Steps based on rotation progression
            if (tRot < 0.22) {
                // 01 • Curved Panoramic Bay Window
                setTourProgressStep(2);
                showCard(2);
            } else if (tRot >= 0.22 && tRot < 0.48) {
                // 02 • Canopy Timber Balcony Deck
                setTourProgressStep(3);
                showCard(3);
            } else if (tRot >= 0.48 && tRot < 0.74) {
                // 03 • Artisan King Teak Bed Suite
                setTourProgressStep(4);
                showCard(4);
            } else {
                // 04 • Mountain Stone Hearth & Fireplace Lounge
                setTourProgressStep(5);
                showCard(5);
            }
        }
    }

    // 6. Connect GSAP ScrollTrigger
    const scrollTriggerInstance = ScrollTrigger.create({
        id: "treehouse-scroll-walkthrough",
        trigger: "#rooms-experience",
        start: "top top",
        end: "+=380%",
        pin: true,
        scrub: 0.6,
        onToggle: (self) => {
            if (self.isActive) {
                document.body.classList.add("in-walkthrough");
            } else {
                document.body.classList.remove("in-walkthrough");
            }
        },
        onUpdate: (self) => {
            applyScrollProgress(self.progress);
        }
    });

    // 7. Interactive Mouse / Touch Drag to Look Around Inside Room (2-Axis: Pitch + Yaw)
    let prevPointerX = 0;

    canvas.addEventListener("mousedown", (e) => {
        if (!isInside) return;
        isUserDragging = true;
        prevPointerX = e.clientX;
        prevPointerY = e.clientY;
        canvas.style.cursor = "grabbing";
    });

    window.addEventListener("mousemove", (e) => {
        if (!isUserDragging || !isInside) return;
        const deltaX = e.clientX - prevPointerX;
        const deltaY = e.clientY - prevPointerY;
        prevPointerX = e.clientX;
        prevPointerY = e.clientY;
        targetRotY += deltaX * 0.005;
        targetRotX -= deltaY * 0.004; // Look up & down smoothly
        targetRotX = Math.max(-0.95, Math.min(0.95, targetRotX)); // Clamp vertical tilt to ±55°
    });

    window.addEventListener("mouseup", () => {
        if (isUserDragging) {
            isUserDragging = false;
            canvas.style.cursor = isInside ? "grab" : "default";
        }
    });

    // Touch support for phones, tablets and touch laptops with pinch-to-zoom
    canvas.addEventListener("touchstart", (e) => {
        if (!isInside) return;
        if (e.touches.length === 1) {
            isUserDragging = true;
            prevPointerX = e.touches[0].clientX;
            prevPointerY = e.touches[0].clientY;
        } else if (e.touches.length === 2) {
            isUserDragging = false;
            touchStartDist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
        }
    }, { passive: true });

    window.addEventListener("touchmove", (e) => {
        if (!isInside) return;
        if (e.touches.length === 1 && isUserDragging) {
            const deltaX = e.touches[0].clientX - prevPointerX;
            const deltaY = e.touches[0].clientY - prevPointerY;
            prevPointerX = e.touches[0].clientX;
            prevPointerY = e.touches[0].clientY;
            targetRotY += deltaX * 0.005;
            targetRotX -= deltaY * 0.004;
            targetRotX = Math.max(-0.95, Math.min(0.95, targetRotX));
        } else if (e.touches.length === 2 && touchStartDist > 0) {
            const dist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            const diff = dist - touchStartDist;
            if (Math.abs(diff) > 8) {
                userZoomOffset -= (diff > 0 ? 2 : -2);
                userZoomOffset = Math.max(-18, Math.min(20, userZoomOffset));
                targetFOV = baseInteriorFOV + userZoomOffset;
                touchStartDist = dist;
            }
        }
    }, { passive: true });

    window.addEventListener("touchend", () => {
        isUserDragging = false;
        touchStartDist = 0;
    });

    // Quick Tour Floating Controls (Wide / Zoom In / Reset)
    const btnZoomOut = document.getElementById("tour-btn-zoom-out");
    const btnZoomIn = document.getElementById("tour-btn-zoom-in");
    const btnResetView = document.getElementById("tour-btn-reset-view");

    if (btnZoomOut) {
        btnZoomOut.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            userZoomOffset = Math.min(22, userZoomOffset + 6); // Wider view
            targetFOV = Math.min(98, baseInteriorFOV + userZoomOffset);
        });
    }
    if (btnZoomIn) {
        btnZoomIn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            userZoomOffset = Math.max(-20, userZoomOffset - 6); // Closer view
            targetFOV = Math.max(55, baseInteriorFOV + userZoomOffset);
        });
    }
    if (btnResetView) {
        btnResetView.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            userZoomOffset = 0;
            targetFOV = baseInteriorFOV;
            targetRotX = 0;
        });
    }


    // 8. Progress Navigation Handlers (Desktop & Mobile Stepper)
    const stepTargetProgress = [0.05, 0.28, 0.48, 0.72, 0.95];

    function goToStep(stepNum) {
        if (stepNum < 1) stepNum = 1;
        if (stepNum > 5) stepNum = 5;
        currentStepNum = stepNum;
        const targetP = stepTargetProgress[stepNum - 1];
        const st = ScrollTrigger.getById("treehouse-scroll-walkthrough");
        if (st) {
            const targetScroll = st.start + (st.end - st.start) * targetP;
            window.scrollTo({ top: targetScroll, behavior: "smooth" });
        } else {
            applyScrollProgress(targetP);
        }
    }

    // Desktop step pills click
    progressSteps.forEach((btn, idx) => {
        if (!btn) return;
        btn.addEventListener("click", () => {
            goToStep(idx + 1);
        });
    });

    // Mobile Stepper Arrow & Dot Buttons
    const prevBtn = document.getElementById("mobile-tour-prev");
    const nextBtn = document.getElementById("mobile-tour-next");
    if (prevBtn) {
        prevBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            goToStep(currentStepNum - 1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            goToStep(currentStepNum + 1);
        });
    }

    mobileDots.forEach((dot) => {
        dot.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            const s = parseInt(dot.getAttribute("data-step"), 10);
            if (!isNaN(s)) {
                goToStep(s);
            }
        });
    });

    // 8B. Stay Concept Tab Button Handlers (Treehouse <-> Mudhouse)
    const stayTabs = document.querySelectorAll(".stay-tab-btn");
    stayTabs.forEach(tab => {
        tab.addEventListener("click", (e) => {
            e.preventDefault();
            const stayKey = tab.getAttribute("data-stay");
            if (stayKey) {
                switchStay(stayKey);
            }
        });
    });

    // Initial State Setup
    applyScrollProgress(0);

    // 9. Visibility Observer to Pause Render Loop when Out of View
    let isSectionInView = true;
    if ("IntersectionObserver" in window) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                isSectionInView = entry.isIntersecting;
            });
        }, { threshold: 0.05 });
        if (container) obs.observe(container);
    }

    // Smooth Hydraulic Gimbal Render Loop
    function tick() {
        if (isSectionInView) {
            // Smooth hydraulic camera yaw
            currentRotY += (targetRotY - currentRotY) * 0.08;
            camera.rotation.y = currentRotY;

            // Smooth hydraulic camera pitch (look up/down)
            currentRotX += (targetRotX - currentRotX) * 0.08;
            camera.rotation.x = currentRotX + (isInside ? Math.sin(Date.now() * 0.0012) * 0.003 : Math.sin(Date.now() * 0.0012) * 0.01);

            // Smooth FOV zoom transition
            const fovDiff = targetFOV - camera.fov;
            if (Math.abs(fovDiff) > 0.05) {
                camera.fov += fovDiff * 0.08;
                camera.updateProjectionMatrix();
            }

            if (particles) {
                particles.rotation.y += 0.0003;
            }

            renderer.render(scene, camera);
        }
        requestAnimationFrame(tick);
    }
    tick();

    // 10. Handle Window Resize & Orientation Change
    function handleResize() {
        const isMobile = window.innerWidth < 1025 || ('ontouchstart' in window);
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, isMobile ? 1.5 : 2));
        updateExteriorPlaneGeometry(activeStay);
        if (typeof ScrollTrigger !== "undefined") {
            ScrollTrigger.refresh();
        }
    }

    window.addEventListener("resize", handleResize);
    window.addEventListener("orientationchange", () => {
        setTimeout(handleResize, 200);
    });
});
