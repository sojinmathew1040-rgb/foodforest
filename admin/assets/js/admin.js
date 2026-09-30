/* =========================================================================
   Food Forest Sanctuary — Dedicated Admin Javascript
   ========================================================================= */

document.addEventListener("DOMContentLoaded", () => {

    // 0. Dark / White Theme Engine
    const THEME_STORAGE_KEY = "foodforest_adm_theme";
    const themeToggleBtn = document.getElementById("adm-theme-toggle-btn");
    const themeLabel = document.getElementById("adm-theme-toggle-label");

    function getActiveTheme() {
        return document.documentElement.getAttribute("data-theme") || localStorage.getItem(THEME_STORAGE_KEY) || "dark";
    }

    function applyAdminTheme(theme, saveToServer = true) {
        if (theme === "auto") {
            theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
        }
        document.documentElement.setAttribute("data-theme", theme);
        document.documentElement.classList.remove("theme-dark", "theme-light");
        document.documentElement.classList.add("theme-" + theme);
        if (document.body) {
            document.body.classList.remove("theme-dark", "theme-light");
            document.body.classList.add("theme-" + theme);
        }

        try {
            localStorage.setItem(THEME_STORAGE_KEY, theme);
        } catch(e) {}

        // Update topbar toggle label & state
        if (themeLabel) {
            themeLabel.textContent = theme === "light" ? "Light" : "Dark";
        }
        if (themeToggleBtn) {
            themeToggleBtn.setAttribute("data-current-theme", theme);
            themeToggleBtn.title = theme === "light" ? "Switch to Dark Mode (Obsidian)" : "Switch to White Mode (Ivory)";
        }

        // Update settings page radio buttons & preview cards
        document.querySelectorAll("input[name='admin_theme'], input[name='theme_choice']").forEach(radio => {
            radio.checked = (radio.value === theme);
        });
        document.querySelectorAll(".adm-theme-card-option").forEach(card => {
            if (card.getAttribute("data-theme-val") === theme) {
                card.classList.add("is-selected");
            } else {
                card.classList.remove("is-selected");
            }
        });

        // Persist to server via background AJAX
        if (saveToServer) {
            try {
                fetch("api_theme.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ theme: theme })
                }).catch(() => {});
            } catch(e) {}
        }
    }

    window.setAdminTheme = function(theme, saveToServer) {
        applyAdminTheme(theme, saveToServer !== false);
    };

    window.toggleAdminTheme = function() {
        const current = getActiveTheme();
        const nextTheme = (current === "light") ? "dark" : "light";
        applyAdminTheme(nextTheme, true);
        if (typeof window.showAdmToast === "function") {
            window.showAdmToast(nextTheme === "light" ? "☀️ White (Light) Theme Activated" : "🌙 Dark Obsidian Theme Activated", "success");
        }
    };

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", (e) => {
            e.preventDefault();
            window.toggleAdminTheme();
        });
    }

    // Attach click handlers to any theme choice cards in settings
    document.querySelectorAll(".adm-theme-card-option").forEach(card => {
        card.addEventListener("click", function() {
            const val = this.getAttribute("data-theme-val");
            if (val) {
                window.setAdminTheme(val, true);
                if (typeof window.showAdmToast === "function") {
                    window.showAdmToast(val === "light" ? "☀️ White Theme Selected" : "🌙 Dark Theme Selected", "success");
                }
            }
        });
    });

    // Sync active theme state on init
    const initialTheme = getActiveTheme();
    applyAdminTheme(initialTheme, false);

    // 1. Sidebar Minimize / Expand & Mobile Drawer Toggle
    const mobileToggle = document.getElementById("adm-mobile-toggle");
    const sidebar = document.getElementById("adm-sidebar");
    const sidebarToggleBtn = document.getElementById("adm-sidebar-toggle-btn");
    const appLayout = document.querySelector(".adm-app-layout");
    const COLLAPSE_KEY = "foodforest_adm_sidebar_collapsed";

    function setSidebarCollapsed(collapsed) {
        if (!sidebar) return;
        if (collapsed) {
            sidebar.classList.add("is-collapsed");
            if (appLayout) appLayout.classList.add("sidebar-collapsed");
            if (sidebarToggleBtn) {
                const icon = sidebarToggleBtn.querySelector("i");
                if (icon) {
                    icon.className = "fa-solid fa-angles-right";
                }
                sidebarToggleBtn.title = "Expand Menu";
                sidebarToggleBtn.setAttribute("aria-label", "Expand Menu");
            }
            try { localStorage.setItem(COLLAPSE_KEY, "1"); } catch(e){}
        } else {
            sidebar.classList.remove("is-collapsed");
            if (appLayout) appLayout.classList.remove("sidebar-collapsed");
            if (sidebarToggleBtn) {
                const icon = sidebarToggleBtn.querySelector("i");
                if (icon) {
                    icon.className = "fa-solid fa-angles-left";
                }
                sidebarToggleBtn.title = "Minimize Menu";
                sidebarToggleBtn.setAttribute("aria-label", "Minimize Menu");
            }
            try { localStorage.setItem(COLLAPSE_KEY, "0"); } catch(e){}
        }
    }

    // Restore saved minimize preference on desktop
    try {
        if (localStorage.getItem(COLLAPSE_KEY) === "1" && window.innerWidth > 992) {
            setSidebarCollapsed(true);
        }
    } catch(e){}

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isCollapsed = sidebar.classList.contains("is-collapsed");
            setSidebarCollapsed(!isCollapsed);
        });
    }

    const mobileBackdrop = document.getElementById("adm-mobile-sidebar-backdrop");
    const mobileCloseBtn = document.getElementById("adm-sidebar-close-mob");
    const mobMoreBtn = document.getElementById("btn-mob-more-menu");

    function openMobileDrawer() {
        if (!sidebar) return;
        sidebar.classList.add("is-open");
        if (mobileBackdrop) mobileBackdrop.classList.add("is-active");
        document.body.style.overflow = "hidden";
    }

    function closeMobileDrawer() {
        if (!sidebar) return;
        sidebar.classList.remove("is-open");
        if (mobileBackdrop) mobileBackdrop.classList.remove("is-active");
        document.body.style.overflow = "";
    }

    if (mobileToggle) {
        mobileToggle.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (sidebar.classList.contains("is-open")) {
                closeMobileDrawer();
            } else {
                openMobileDrawer();
            }
        });
    }

    if (mobileCloseBtn) {
        mobileCloseBtn.addEventListener("click", (e) => {
            e.preventDefault();
            closeMobileDrawer();
        });
    }

    if (mobMoreBtn) {
        mobMoreBtn.addEventListener("click", (e) => {
            e.preventDefault();
            openMobileDrawer();
        });
    }

    if (mobileBackdrop) {
        mobileBackdrop.addEventListener("click", () => {
            closeMobileDrawer();
        });
    }

    // Auto-close mobile drawer when tapping any sidebar link
    if (sidebar) {
        sidebar.querySelectorAll(".adm-nav-link").forEach(link => {
            link.addEventListener("click", () => {
                if (window.innerWidth <= 992) {
                    closeMobileDrawer();
                }
            });
        });
    }

    // 2. Password Visibility Toggle (for Login & Settings)
    document.querySelectorAll(".adm-toggle-pwd").forEach(btn => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-target");
            const input = document.getElementById(targetId);
            if (!input) return;

            const icon = btn.querySelector("i");
            if (input.type === "password") {
                input.type = "text";
                if (icon) {
                    icon.classList.remove("fa-eye");
                    icon.classList.add("fa-eye-slash");
                }
            } else {
                input.type = "password";
                if (icon) {
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                }
            }
        });
    });

    // 3. Modal Opening & Closing Engine
    window.openAdmModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add("is-open");
            document.body.style.overflow = "hidden";
        }
    };

    window.closeAdmModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove("is-open");
            document.body.style.overflow = "";
        }
    };

    // Close modal on click of backdrop or close button
    document.querySelectorAll(".adm-modal-backdrop").forEach(backdrop => {
        backdrop.addEventListener("click", (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove("is-open");
                document.body.style.overflow = "";
            }
        });
    });

    document.querySelectorAll("[data-close-modal]").forEach(btn => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-close-modal");
            closeAdmModal(targetId);
        });
    });

    // 4. Live Table Search Filter
    const searchInput = document.getElementById("adm-table-search");
    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            const query = e.target.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll(".adm-data-table tbody tr");
            
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    }

    // 5. Status Filter Dropdown
    const statusFilter = document.getElementById("adm-status-filter");
    if (statusFilter) {
        statusFilter.addEventListener("change", (e) => {
            const selectedStatus = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll(".adm-data-table tbody tr");

            tableRows.forEach(row => {
                const rowStatus = row.getAttribute("data-status");
                if (!selectedStatus || selectedStatus === "all" || rowStatus === selectedStatus) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    }

    // 6. WhatsApp Luxury Concierge Generator
    window.openWhatsAppConcierge = function(booking) {
        // Strip non-digits from phone number
        let phone = (booking.phone || "").replace(/\D/g, "");
        if (phone.length === 10) {
            phone = "91" + phone;
        }

        const msg = `🌿 *FOOD FOREST SANCTUARY — KANTHALLOOR*\n*Official Reservation Concierge Confirmation*\n\nDear ${booking.guest_name},\n\nWe are delighted to welcome you to our misty mountain sanctuary in Kanthalloor, Kerala.\n\n*Reservation Summary:*\n• *Booking Ref:* ${booking.reference_code}\n• *Sanctuary Stay:* ${booking.villa_title}\n• *Check-in:* ${booking.checkin_date} (from 02:00 PM)\n• *Check-out:* ${booking.checkout_date} (until 11:00 AM)\n• *Guests:* ${booking.guests_count} Guests\n• *Estimated Total:* ₹${Number(booking.total_amount).toLocaleString('en-IN')}\n\n*Included:* All organic estate-grown farm meals & forest walkthrough.\n\nPlease let us know if you have any dietary preferences or require private transfer assistance from Coimbatore or Kochi.\n\nWarm regards,\n*Master Concierge*\nFood Forest Eco Sanctuary`;

        const encodedMsg = encodeURIComponent(msg);
        const url = `https://wa.me/${phone}?text=${encodedMsg}`;
        window.open(url, "_blank");
    };

    // 7. Toast Notification Trigger
    window.showAdmToast = function(message, type = "success") {
        let toast = document.getElementById("adm-toast-container");
        if (!toast) {
            toast = document.createElement("div");
            toast.id = "adm-toast-container";
            toast.className = "adm-toast";
            document.body.appendChild(toast);
        }

        const icon = type === "success" ? "fa-circle-check" : "fa-circle-exclamation";
        const color = type === "success" ? "#48BB78" : "#FC8181";

        toast.innerHTML = `<i class="fa-solid ${icon}" style="color: ${color}; font-size: 18px;"></i> <span>${message}</span>`;
        toast.classList.add("is-visible");

        setTimeout(() => {
            toast.classList.remove("is-visible");
        }, 4000);
    };

    // 8. Settings Cards Click Engine
    document.querySelectorAll(".adm-setting-card-btn").forEach(card => {
        card.addEventListener("click", function(e) {
            const href = this.getAttribute("href");
            if (href && href !== "#" && !href.startsWith("javascript:")) {
                // If this is a page navigation link, allow direct navigation
                return;
            }
            const tabKey = this.getAttribute("data-tab");
            if (tabKey && typeof window.switchSettingsTab === "function") {
                if (e && e.preventDefault) e.preventDefault();
                window.switchSettingsTab(tabKey, this, e, true);
            }
        });
    });

    // Auto-activate tab from URL parameter if on edit section page with panels
    if (document.getElementById("adm-panels-container")) {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get("section") || urlParams.get("tab") || "estate";
        if (typeof window.switchSettingsTab === "function") {
            window.switchSettingsTab(activeTab, null, null, false);
        }
    }

});

