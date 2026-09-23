/* =========================================================================
   Food Forest Sanctuary — Dedicated Admin Javascript
   ========================================================================= */

document.addEventListener("DOMContentLoaded", () => {

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

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener("click", () => {
            sidebar.classList.toggle("is-open");
        });

        // Close when clicking outside
        document.addEventListener("click", (e) => {
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target) && sidebar.classList.contains("is-open")) {
                sidebar.classList.remove("is-open");
            }
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

