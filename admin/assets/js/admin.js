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
            modal.style.removeProperty("display");
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

    // 9. Universal Admin Scroll To Top Engine
    (function initAdminScrollTop() {
        let btn = document.getElementById("adm-scroll-top");
        if (!btn) {
            btn = document.createElement("button");
            btn.type = "button";
            btn.id = "adm-scroll-top";
            btn.className = "adm-scroll-top";
            btn.setAttribute("aria-label", "Scroll to top");
            btn.setAttribute("title", "Scroll to top");
            btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
            document.body.appendChild(btn);
        }

        const mainViewport = document.querySelector(".adm-main-viewport") || document.querySelector(".adm-content-body");

        function checkScroll() {
            const winY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            const vpY = mainViewport ? mainViewport.scrollTop : 0;
            const scrolled = Math.max(winY, vpY);

            if (scrolled > 260) {
                btn.classList.add("is-visible");
            } else {
                btn.classList.remove("is-visible");
            }
        }

        window.addEventListener("scroll", checkScroll, { passive: true });
        document.addEventListener("scroll", checkScroll, { passive: true });
        if (mainViewport) {
            mainViewport.addEventListener("scroll", checkScroll, { passive: true });
        }

        btn.addEventListener("click", function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: "smooth" });
            document.documentElement.scrollTo({ top: 0, behavior: "smooth" });
            document.body.scrollTo({ top: 0, behavior: "smooth" });
            if (mainViewport) {
                mainViewport.scrollTo({ top: 0, behavior: "smooth" });
            }
        });

        checkScroll();
    })();

    // =====================================================================
    // 10. Dedicated Admin AI Concierge & Helpdesk Chatbot Engine
    // =====================================================================
    (function initAdminChatbot() {
        const launcher = document.getElementById("adm-bot-launcher");
        const botWindow = document.getElementById("adm-bot-window");
        const btnClose = document.getElementById("adm-bot-btn-close");
        const btnExpand = document.getElementById("adm-bot-btn-expand");
        const btnClear = document.getElementById("adm-bot-btn-clear");
        const expandIcon = document.getElementById("adm-bot-expand-icon");
        const msgContainer = document.getElementById("adm-bot-messages");
        const ribbon = document.getElementById("adm-bot-suggestions-ribbon");
        const inputField = document.getElementById("adm-bot-input");
        const form = document.getElementById("adm-bot-form");
        const micBtn = document.getElementById("adm-bot-mic-btn");

        if (!launcher || !botWindow || !msgContainer) return;

        const STORAGE_CHAT_KEY = "foodforest_adm_bot_history_v1";
        const STORAGE_TOPIC_KEY = "foodforest_adm_bot_topic_v1";
        let currentTopic = localStorage.getItem(STORAGE_TOPIC_KEY) || "";

        const STARTER_PROMPTS = [
            { icon: "fa-solid fa-house-chimney", label: "Change Room & Villa Rates", query: "How do I change villa tariffs and extra guest charges?" },
            { icon: "fa-solid fa-building-columns", label: "Bank & UPI QR Setup", query: "How to set up Bank Account and UPI QR code for billing?" },
            { icon: "fa-solid fa-receipt", label: "Billing & A4 Invoices", query: "How does guest billing, custom charges, and A4 invoice printing work?" },
            { icon: "fa-solid fa-utensils", label: "Food Menu & Timings", query: "How do I add or edit food menu items and meal slots?" },
            { icon: "fa-solid fa-percent", label: "Multi-Tier GST Tax", query: "How to configure Multi-Tier GST tax rates for room and food?" },
            { icon: "fa-solid fa-calendar-days", label: "Block Dates in Calendar", query: "How do I block dates on the booking calendar?" },
            { icon: "fa-solid fa-arrows-rotate", label: "Airbnb / OTA iCal Sync", query: "How to sync calendar with Airbnb and Booking.com via iCal?" },
            { icon: "fa-solid fa-bell-concierge", label: "Food Ordering Switch", query: "How to enable or disable in-cottage food ordering for guests?" },
            { icon: "fa-solid fa-map-location-dot", label: "Sanctuary Map & Trails", query: "How do I edit sanctuary spots and walking trails on the map?" },
            { icon: "fa-solid fa-trash-can", label: "Clear Demo Data Safely", query: "How do I safely clear demo bookings before launching?" }
        ];

        // Functions for open / close / toggle
        window.openAdminBot = function() {
            botWindow.classList.add("is-open", "active");
            launcher.classList.add("is-active", "active");
            if (inputField) {
                setTimeout(() => inputField.focus(), 300);
            }
            scrollToBottom();
        };

        window.closeAdminBot = function() {
            botWindow.classList.remove("is-open", "active");
            launcher.classList.remove("is-active", "active");
        };

        window.toggleAdminBot = function() {
            if (botWindow.classList.contains("is-open") || botWindow.classList.contains("active")) {
                window.closeAdminBot();
            } else {
                window.openAdminBot();
            }
        };

        launcher.addEventListener("click", (e) => {
            e.preventDefault();
            window.toggleAdminBot();
        });

        if (btnClose) {
            btnClose.addEventListener("click", (e) => {
                e.preventDefault();
                window.closeAdminBot();
            });
        }

        if (btnExpand) {
            btnExpand.addEventListener("click", (e) => {
                e.preventDefault();
                botWindow.classList.toggle("adm-bot-maximized");
                botWindow.classList.toggle("expanded");
                const isMax = botWindow.classList.contains("adm-bot-maximized") || botWindow.classList.contains("expanded");
                if (expandIcon) {
                    expandIcon.className = isMax ? "fa-solid fa-compress" : "fa-solid fa-expand";
                }
            });
        }

        if (btnClear) {
            btnClear.addEventListener("click", (e) => {
                e.preventDefault();
                if (confirm("Reset conversation and return to the main help menu?")) {
                    localStorage.removeItem(STORAGE_CHAT_KEY);
                    localStorage.removeItem(STORAGE_TOPIC_KEY);
                    currentTopic = "";
                    renderWelcomeView();
                }
            });
        }

        // Voice Recognition (Speech to Text)
        if (micBtn && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
            micBtn.style.display = "inline-flex";
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognizer = new SpeechRec();
            recognizer.continuous = false;
            recognizer.interimResults = false;
            recognizer.lang = 'en-IN';

            recognizer.onstart = () => {
                micBtn.classList.add("is-listening");
                micBtn.title = "Listening to your voice...";
            };

            recognizer.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                if (inputField && transcript) {
                    inputField.value = transcript;
                    window.sendAdminBotMessage();
                }
            };

            recognizer.onerror = () => {
                micBtn.classList.remove("is-listening");
            };

            recognizer.onend = () => {
                micBtn.classList.remove("is-listening");
                micBtn.title = "Speak your question (Voice Input)";
            };

            micBtn.addEventListener("click", () => {
                try { recognizer.start(); } catch(e) {}
            });
        }

        function scrollToBottom() {
            setTimeout(() => {
                msgContainer.scrollTop = msgContainer.scrollHeight;
            }, 60);
        }

        function getChatHistory() {
            try {
                return JSON.parse(localStorage.getItem(STORAGE_CHAT_KEY)) || [];
            } catch(e) {
                return [];
            }
        }

        function saveChatHistory(history) {
            try {
                localStorage.setItem(STORAGE_CHAT_KEY, JSON.stringify(history));
            } catch(e) {}
        }

        function renderWelcomeView() {
            msgContainer.innerHTML = `
                <div class="adm-bot-welcome">
                    <div class="adm-bot-welcome-icon">
                        <i class="fa-solid fa-compass"></i>
                    </div>
                    <h4>Sanctuary Operational Helpdesk</h4>
                    <p>I am your dedicated 24/7 administrator assistant. Ask any question about configuring tariffs, bank &amp; UPI QR, billing folios, kitchen orders, calendar, or clarifying any step in detail.</p>
                </div>
            `;
            renderStarterChips();
        }

        function renderStarterChips() {
            if (!ribbon) return;
            ribbon.innerHTML = "";
            STARTER_PROMPTS.forEach(p => {
                const chip = document.createElement("button");
                chip.type = "button";
                chip.className = "adm-bot-chip";
                chip.innerHTML = `<i class="${p.icon}"></i> <span>${p.label}</span>`;
                chip.addEventListener("click", () => {
                    if (inputField) inputField.value = p.query;
                    window.sendAdminBotMessage();
                });
                ribbon.appendChild(chip);
            });
        }

        function appendMessage(role, htmlContent, followUps = []) {
            const row = document.createElement("div");
            row.className = `adm-bot-msg-row ${role === 'user' ? 'is-user' : 'is-bot'}`;

            const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            if (role === 'user') {
                row.innerHTML = `
                    <div class="adm-bot-bubble user">
                        <div class="adm-bot-bubble-content">${escapeHTML(htmlContent)}</div>
                        <div class="adm-bot-bubble-meta">${timeStr}</div>
                    </div>
                `;
            } else {
                row.innerHTML = `
                    <div class="adm-bot-msg-avatar">
                        <i class="fa-solid fa-seedling"></i>
                    </div>
                    <div class="adm-bot-bubble bot">
                        <div class="adm-bot-bubble-content">${htmlContent}</div>
                        <div class="adm-bot-bubble-meta">
                            <span>${timeStr} • Food Forest Concierge</span>
                        </div>
                    </div>
                `;

                // If there are follow-up doubts, render them right beneath this bot reply
                if (Array.isArray(followUps) && followUps.length > 0) {
                    const doubtsBox = document.createElement("div");
                    doubtsBox.className = "adm-bot-doubts-box";
                    followUps.forEach(doubtText => {
                        const doubtBtn = document.createElement("button");
                        doubtBtn.type = "button";
                        doubtBtn.className = "adm-bot-doubt-pill";
                        doubtBtn.innerHTML = `<i class="fa-solid fa-circle-question"></i> <span>${escapeHTML(doubtText)}</span>`;
                        doubtBtn.addEventListener("click", () => {
                            if (inputField) inputField.value = doubtText;
                            window.sendAdminBotMessage();
                        });
                        doubtsBox.appendChild(doubtBtn);
                    });
                    row.querySelector(".adm-bot-bubble.bot").appendChild(doubtsBox);
                }
            }

            msgContainer.appendChild(row);
            scrollToBottom();
        }

        function showTypingIndicator() {
            removeTypingIndicator();
            const ind = document.createElement("div");
            ind.id = "adm-bot-typing";
            ind.className = "adm-bot-msg-row is-bot is-typing";
            ind.innerHTML = `
                <div class="adm-bot-msg-avatar">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <div class="adm-bot-bubble bot typing">
                    <div class="adm-bot-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            `;
            msgContainer.appendChild(ind);
            scrollToBottom();
        }

        function removeTypingIndicator() {
            const existing = document.getElementById("adm-bot-typing");
            if (existing) existing.remove();
        }

        function escapeHTML(str) {
            const p = document.createElement("p");
            p.textContent = str;
            return p.innerHTML;
        }

        // Send user query to API
        window.sendAdminBotMessage = function() {
            if (!inputField) return;
            const text = inputField.value.trim();
            if (!text) return;

            inputField.value = "";
            appendMessage("user", text);

            // Record in history
            const history = getChatHistory();
            history.push({ role: "user", text: text, time: Date.now() });
            saveChatHistory(history);

            showTypingIndicator();

            fetch("api/admin_assistant.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    query: text,
                    context: {
                        current_topic: currentTopic,
                        page: window.location.pathname
                    }
                })
            })
            .then(res => {
                if (!res.ok) throw new Error("HTTP " + res.status);
                return res.json();
            })
            .then(data => {
                removeTypingIndicator();
                if (data && data.success) {
                    if (data.topic) {
                        currentTopic = data.topic;
                        localStorage.setItem(STORAGE_TOPIC_KEY, currentTopic);
                    }
                    appendMessage("bot", data.reply, data.follow_up_doubts || []);

                    // Update suggestions ribbon with relevant follow-ups
                    if (data.follow_up_doubts && data.follow_up_doubts.length > 0 && ribbon) {
                        ribbon.innerHTML = "";
                        data.follow_up_doubts.forEach(d => {
                            const pill = document.createElement("button");
                            pill.type = "button";
                            pill.className = "adm-bot-chip";
                            pill.innerHTML = `<i class="fa-solid fa-question"></i> <span>${escapeHTML(d)}</span>`;
                            pill.addEventListener("click", () => {
                                if (inputField) inputField.value = d;
                                window.sendAdminBotMessage();
                            });
                            ribbon.appendChild(pill);
                        });
                    }

                    // Save bot response in history
                    history.push({ role: "bot", html: data.reply, followUps: data.follow_up_doubts || [], time: Date.now() });
                    saveChatHistory(history);
                } else {
                    appendMessage("bot", `<p style="color:#F87171;">${data.reply || 'Could not complete request. Please try again.'}</p>`);
                }
            })
            .catch(err => {
                removeTypingIndicator();
                appendMessage("bot", `
                    <div style="color: #FCA5A5; font-size: 13px;">
                        <i class="fa-solid fa-circle-exclamation"></i> Connection issue connecting to the Sanctuary Assistant API. Please verify network or try again.
                    </div>
                `);
            });
        };

        // Initialize Chat State from History or Welcome
        const initialHistory = getChatHistory();
        if (initialHistory && initialHistory.length > 0) {
            renderStarterChips();
            initialHistory.forEach(item => {
                if (item.role === 'user') {
                    appendMessage('user', item.text);
                } else {
                    appendMessage('bot', item.html, item.followUps || []);
                }
            });
        } else {
            renderWelcomeView();
        }

        // Global key shortcut: Esc closes chatbot
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && botWindow.classList.contains("is-open")) {
                window.closeAdminBot();
            }
        });
    })();


