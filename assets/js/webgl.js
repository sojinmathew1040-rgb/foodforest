/* -------------------------------------------------------------
   Food Forest — WebGL 3D Rooms Experience (Three.js & GSAP ScrollTrigger)
   ------------------------------------------------------------- */

document.addEventListener("DOMContentLoaded", () => {
    // Register ScrollTrigger plugin with GSAP
    gsap.registerPlugin(ScrollTrigger);

    // 1. WebGL Support and Responsive Check
    const container = document.getElementById("rooms-experience");
    const canvas = document.getElementById("rooms-webgl-canvas");
    const fallback = document.getElementById("webgl-fallback-container");

    function hasWebGL() {
        try {
            const tempCanvas = document.createElement('canvas');
            return !!(window.WebGLRenderingContext && (tempCanvas.getContext('webgl') || tempCanvas.getContext('experimental-webgl')));
        } catch (e) {
            return false;
        }
    }

    // Fallback to static layout on mobile/small screens or if WebGL is unsupported
    const isMobile = window.innerWidth < 1025;
    if (!hasWebGL() || isMobile) {
        if (canvas) canvas.style.display = "none";

        // Ensure static fallback cards are shown in document flow to prevent layout overlapping
        if (fallback) {
            fallback.style.display = "block";
            fallback.style.position = "relative";
            fallback.style.height = "auto";
            fallback.style.padding = "60px 0";
        }

        // Hide interactive 3D elements since they are only relevant to the WebGL experience
        const selector = document.querySelector(".room-selector-container");
        const textOverlay = document.querySelector(".rooms-text-overlay");
        const treeDetails = document.getElementById("treehouse-details");
        const mudDetails = document.getElementById("mudhouse-details");
        if (selector) selector.style.display = "none";
        if (textOverlay) textOverlay.style.display = "none";
        if (treeDetails) treeDetails.style.display = "none";
        if (mudDetails) mudDetails.style.display = "none";

        if (container) {
            container.style.height = "auto";
            container.style.overflow = "visible";
        }
        return; // Exit WebGL setup
    }

    // 2. Initialize Three.js Scene (Light Cream Theme)
    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0xF3EFE3, 0.03); // Fog matches warm cream color

    // Setup Camera
    const camera = new THREE.PerspectiveCamera(65, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.set(0, 0, 3.5);

    // Setup Renderer
    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        antialias: true,
        alpha: true,
        powerPreference: "high-performance"
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0xF3EFE3, 1); // Set background to warm cream

    // 3. Floating Fireflies Particle System (Removed)

    // 4. Room Mesh Groups Setup
    const textureLoader = new THREE.TextureLoader();

    // Self-healing fallback color texture if assets fail to load
    function createFallbackTexture(color) {
        const cCanvas = document.createElement('canvas');
        cCanvas.width = 256;
        cCanvas.height = 256;
        const cCtx = cCanvas.getContext('2d');
        cCtx.fillStyle = color;
        cCtx.fillRect(0, 0, 256, 256);
        return new THREE.CanvasTexture(cCanvas);
    }

    // Load Textures (with fallbacks)
    const tExtTexture = textureLoader.load('assets/images/treehouse_exterior.png', undefined, undefined, () => createFallbackTexture('#4a5d4e'));
    const tIntTexture = textureLoader.load('assets/images/treehouse_interior.png', undefined, undefined, () => createFallbackTexture('#2a3a2e'));
    const mExtTexture = textureLoader.load('assets/images/01 (4).jpeg', undefined, undefined, () => createFallbackTexture('#5d463b'));
    const mIntTexture = textureLoader.load('assets/images/01 (12).jpeg', undefined, undefined, () => createFallbackTexture('#382820'));

    // --- Tree House Group ---
    const treeGroup = new THREE.Group();
    scene.add(treeGroup);

    // Tree Exterior Plane (displays front of room)
    const treeExtGeometry = new THREE.PlaneGeometry(3.6, 2.4);
    const treeExtMaterial = new THREE.MeshBasicMaterial({
        map: tExtTexture,
        transparent: true,
        opacity: 1
    });
    const treeExtMesh = new THREE.Mesh(treeExtGeometry, treeExtMaterial);
    treeExtMesh.position.set(0, 0, 0);
    treeGroup.add(treeExtMesh);

    // Tree Interior Sphere (displays inside of room)
    const treeIntGeometry = new THREE.SphereGeometry(6, 48, 48);
    const treeIntMaterial = new THREE.MeshBasicMaterial({
        map: tIntTexture,
        transparent: true,
        opacity: 0,
        side: THREE.BackSide // Render inside faces
    });
    const treeIntMesh = new THREE.Mesh(treeIntGeometry, treeIntMaterial);
    treeIntMesh.position.set(0, 0, 0);
    treeIntMesh.rotation.y = Math.PI / 2;
    treeGroup.add(treeIntMesh);

    // --- Mud House Group ---
    const mudGroup = new THREE.Group();
    scene.add(mudGroup);

    // Mud Exterior Plane (displays front of room)
    const mudExtGeometry = new THREE.PlaneGeometry(3.6, 2.4);
    const mudExtMaterial = new THREE.MeshBasicMaterial({
        map: mExtTexture,
        transparent: true,
        opacity: 0
    });
    const mudMesh = new THREE.Mesh(mudExtGeometry, mudExtMaterial);
    mudMesh.position.set(0, 0, 0);
    mudGroup.add(mudMesh);

    // Mud Interior Sphere (displays inside of room)
    const mudIntGeometry = new THREE.SphereGeometry(6, 48, 48);
    const mudIntMaterial = new THREE.MeshBasicMaterial({
        map: mIntTexture,
        transparent: true,
        opacity: 0,
        side: THREE.BackSide // Render inside faces
    });
    const mudIntMesh = new THREE.Mesh(mudIntGeometry, mudIntMaterial);
    mudIntMesh.position.set(0, 0, 0);
    mudIntMesh.rotation.y = Math.PI / 2;
    mudGroup.add(mudIntMesh);

    // 5. Stays State Selection Configuration
    let activeRoom = "treehouse"; // Current selection state: "treehouse" or "mudhouse"
    const groupOpacities = {
        tree: 1,
        mud: 0
    };

    // Toggle active stay tabs and transition elements
    const selectBtns = document.querySelectorAll(".room-select-btn");

    selectBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            const selectedRoom = btn.getAttribute("data-room");
            if (selectedRoom === activeRoom) return;

            activeRoom = selectedRoom;

            // Toggle selector styling active states
            selectBtns.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            // Crossfade HTML informational slides
            const sTree = document.getElementById("slide-treehouse");
            const sMud = document.getElementById("slide-mudhouse");

            if (activeRoom === "treehouse") {
                if (sTree) sTree.classList.add("active");
                if (sMud) sMud.classList.remove("active");
                gsap.to("#slide-treehouse", { display: "flex", opacity: 1, duration: 0.5 });
                gsap.to("#slide-mudhouse", { display: "none", opacity: 0, duration: 0.5 });

                // Switch hotspot wrapper visibility
                const treeDetails = document.getElementById("treehouse-details");
                if (treeDetails) {
                    treeDetails.style.display = "block";
                    treeDetails.classList.add("active");
                }
                const mudDetails = document.getElementById("mudhouse-details");
                if (mudDetails) {
                    mudDetails.style.display = "none";
                    mudDetails.classList.remove("active");
                }

                // Crossfade Three.js group visibility
                gsap.to(groupOpacities, { tree: 1, mud: 0, duration: 0.8, ease: "power2.out" });
            } else {
                if (sTree) sTree.classList.remove("active");
                if (sMud) sMud.classList.add("active");
                gsap.to("#slide-mudhouse", { display: "flex", opacity: 1, duration: 0.5 });
                gsap.to("#slide-treehouse", { display: "none", opacity: 0, duration: 0.5 });

                const treeDetails = document.getElementById("treehouse-details");
                if (treeDetails) {
                    treeDetails.style.display = "none";
                    treeDetails.classList.remove("active");
                }
                const mudDetails = document.getElementById("mudhouse-details");
                if (mudDetails) {
                    mudDetails.style.display = "block";
                    mudDetails.classList.add("active");
                }

                gsap.to(groupOpacities, { tree: 0, mud: 1, duration: 0.8, ease: "power2.out" });
            }

            // Reset visible hotspots to align with current scroll progress
            const currentScrollTrigger = ScrollTrigger.getById("rooms-trigger");
            if (currentScrollTrigger) {
                updateHotspots(currentScrollTrigger.progress);
            }
        });
    });

    // 6. GSAP ScrollTrigger Setup (Reverted back to "before" camera Z-zooming)
    const webglState = {
        cameraZ: 3.5,
        extOpacity: 1,
        intOpacity: 0,
        intRotation: 0
    };

    // GSAP ScrollTrigger timeline mapped to the pinned rooms section
    const tl = gsap.timeline({
        scrollTrigger: {
            id: "rooms-trigger",
            trigger: "#rooms-experience",
            start: "top top",
            end: "+=300%",
            pin: true,
            scrub: true,
            onUpdate: (self) => {
                // Trigger hotspot reveals linked to scroll progress
                updateHotspots(self.progress);
            }
        }
    });

    // 1. Zoom into the room interior, fading out the exterior mesh and fading in the interior photosphere
    tl.to(webglState, {
        cameraZ: 0.0,
        extOpacity: 0,
        intOpacity: 1,
        duration: 1.0,
        ease: "power1.inOut"
    })
        // 2. 360-degree rotation showing the panoramic inside view and activating hotspots sequentially
        .to(webglState, {
            intRotation: Math.PI * 2,
            duration: 2.0,
            ease: "none"
        })
        // 3. Zoom back out at the end of the scroll trigger pin
        .to(webglState, {
            cameraZ: 3.5,
            extOpacity: 1,
            intOpacity: 0,
            duration: 0.5,
            ease: "power1.inOut"
        });

    // Detailed Hotspots activation mapping
    function updateHotspots(progress) {
        const hotspots = {
            treehouse: [
                document.getElementById("tree-detail-1"),
                document.getElementById("tree-detail-2"),
                document.getElementById("tree-detail-3"),
                document.getElementById("tree-detail-4")
            ],
            mudhouse: [
                document.getElementById("mud-detail-1"),
                document.getElementById("mud-detail-2"),
                document.getElementById("mud-detail-3"),
                document.getElementById("mud-detail-4")
            ]
        };

        // Reset all active classes
        hotspots.treehouse.forEach(el => { if (el) el.classList.remove("active"); });
        hotspots.mudhouse.forEach(el => { if (el) el.classList.remove("active"); });

        // Reveal active hotspot detailing boxes based on the current scroll phase inside the room
        const activeList = hotspots[activeRoom];
        if (progress >= 0.32 && progress < 0.46) {
            if (activeList[0]) activeList[0].classList.add("active");
        } else if (progress >= 0.46 && progress < 0.60) {
            if (activeList[1]) activeList[1].classList.add("active");
        } else if (progress >= 0.60 && progress < 0.74) {
            if (activeList[2]) activeList[2].classList.add("active");
        } else if (progress >= 0.74 && progress < 0.88) {
            if (activeList[3]) activeList[3].classList.add("active");
        }
    }

    // 7. Interactive Mouse Movement (Parallax Camera Offset)
    let targetX = 0;
    let targetY = 0;
    let currentX = 0;
    let currentY = 0;

    window.addEventListener("mousemove", (e) => {
        targetX = (e.clientX / window.innerWidth - 0.5) * 0.4;
        targetY = (e.clientY / window.innerHeight - 0.5) * 0.3;
    });

    // 8. Core Render Loop
    const clock = new THREE.Clock();

    function animate() {
        requestAnimationFrame(animate);

        const delta = clock.getDelta();
        const time = clock.getElapsedTime();

        // Floating particles (Removed)

        // Apply interactive mouse lerp offsets to camera
        currentX += (targetX - currentX) * 0.08;
        currentY += (targetY - currentY) * 0.08;

        camera.position.z = webglState.cameraZ;
        camera.rotation.set(-currentY, -currentX, 0);

        // Calculate and apply mesh opacity and visibility to prevent depth culling blocking
        const treeExtOpacity = webglState.extOpacity * groupOpacities.tree;
        const treeIntOpacity = webglState.intOpacity * groupOpacities.tree;

        treeExtMaterial.opacity = treeExtOpacity;
        treeIntMaterial.opacity = treeIntOpacity;

        treeExtMesh.visible = (treeExtOpacity > 0.01);
        treeIntMesh.visible = (treeIntOpacity > 0.01);
        treeIntMesh.rotation.y = Math.PI / 2 + webglState.intRotation;

        const mudExtOpacity = webglState.extOpacity * groupOpacities.mud;
        const mudIntOpacity = webglState.intOpacity * groupOpacities.mud;

        mudExtMaterial.opacity = mudExtOpacity;
        mudIntMaterial.opacity = mudIntOpacity;

        mudMesh.visible = (mudExtOpacity > 0.01);
        mudIntMesh.visible = (mudIntOpacity > 0.01);
        mudIntMesh.rotation.y = Math.PI / 2 + webglState.intRotation;

        renderer.render(scene, camera);
    }
    animate();

    // 9. Handle Window Resizes
    window.addEventListener("resize", () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
});
