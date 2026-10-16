<?php
// =========================================================================
// Food Forest Sanctuary — Admin Assistant & Helpdesk Chatbot Widget
// Included universally via admin/includes/footer.php
// Fully Self-Contained, Reactive & Cache-Resistant Component
// =========================================================================
?>

<!-- ========================================================================= -->
<!-- 1. FLOATING LAUNCHER BUTTON                                              -->
<!-- ========================================================================= -->
<button type="button" class="adm-bot-launcher" id="adm-bot-launcher" onclick="window.toggleAdminBot();" aria-label="Open Admin Concierge AI Assistant" title="Need help? Ask doubts about panel settings & get step-by-step procedures">
    <div class="adm-bot-launcher-pulse"></div>
    <div class="adm-bot-launcher-icon">
        <i class="fa-solid fa-headset"></i>
    </div>
    <div class="adm-bot-launcher-text">
        <span class="adm-bot-launcher-title">Admin AI Guide</span>
        <span class="adm-bot-launcher-sub">Ask Doubts &amp; Steps</span>
    </div>
    <span class="adm-bot-status-dot" title="AI Concierge is Online &amp; Ready"></span>
</button>

<!-- ========================================================================= -->
<!-- 2. LUXURY CHATBOT CONVERSATION WINDOW                                    -->
<!-- ========================================================================= -->
<div class="adm-bot-window" id="adm-bot-window" role="dialog" aria-modal="false" aria-labelledby="adm-bot-header-title">
    
    <!-- Top Header -->
    <div class="adm-bot-header">
        <div class="adm-bot-header-info">
            <div class="adm-bot-avatar-wrap">
                <div class="adm-bot-avatar">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <span class="adm-bot-online-badge"></span>
            </div>
            <div>
                <h3 class="adm-bot-title" id="adm-bot-header-title">
                    Sanctuary Admin AI Concierge
                </h3>
                <div class="adm-bot-subtitle">
                    <span class="adm-bot-status-pill"><span class="dot"></span> Online</span>
                    <span>• Setup &amp; Procedures Guide</span>
                </div>
            </div>
        </div>

        <div class="adm-bot-header-actions">
            <!-- Clear Chat History -->
            <button type="button" class="adm-bot-header-btn" id="adm-bot-btn-clear" onclick="window.clearAdminBotChat();" title="Clear conversation history" aria-label="Clear chat">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
            <!-- Maximize / Normal Height Toggle -->
            <button type="button" class="adm-bot-header-btn" id="adm-bot-btn-expand" onclick="window.toggleAdminBotSize();" title="Expand / Minimize Window" aria-label="Toggle size">
                <i class="fa-solid fa-expand" id="adm-bot-expand-icon"></i>
            </button>
            <!-- Close / Minimize Chatbot -->
            <button type="button" class="adm-bot-header-btn close" id="adm-bot-btn-close" onclick="window.closeAdminBot();" title="Close Assistant" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Message Stream -->
    <div class="adm-bot-messages" id="adm-bot-messages">
        <!-- Messages will be populated here -->
    </div>

    <!-- Quick Suggestions Ribbon -->
    <div class="adm-bot-suggestions-ribbon" id="adm-bot-suggestions-ribbon">
        <!-- Populated dynamically with prompt pills -->
    </div>

    <!-- Interactive Input Bar -->
    <div class="adm-bot-input-area">
        <form id="adm-bot-form" autocomplete="off" onsubmit="event.preventDefault(); window.sendAdminBotMessage();">
            <div class="adm-bot-input-wrap">
                <input 
                    type="text" 
                    id="adm-bot-input" 
                    class="adm-bot-input" 
                    placeholder="Ask any doubt about panel settings or steps..." 
                    maxlength="500"
                    aria-label="Ask a question about panel settings"
                >
                
                <!-- Voice Input Microphone Button (Auto-hidden if unsupported) -->
                <button type="button" class="adm-bot-mic-btn" id="adm-bot-mic-btn" title="Speak your question (Voice Input)" aria-label="Voice input" style="display:none;">
                    <i class="fa-solid fa-microphone"></i>
                </button>

                <!-- Send Button -->
                <button type="submit" class="adm-bot-send-btn" id="adm-bot-send-btn" title="Send Question" aria-label="Send">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </form>
        <div class="adm-bot-footer-hint">
            <span>Press <kbd>Enter</kbd> to send • Supports English &amp; Malayalam / Manglish</span>
        </div>
    </div>

</div>

<!-- Self-Contained Controller Script (Guarantees Instant Functionality Even If Cached) -->
<script>
(function() {
    const STORAGE_CHAT_KEY = "foodforest_adm_bot_history_v2";
    const STORAGE_TOPIC_KEY = "foodforest_adm_bot_topic_v2";
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

    function getElements() {
        return {
            launcher: document.getElementById("adm-bot-launcher"),
            win: document.getElementById("adm-bot-window"),
            messages: document.getElementById("adm-bot-messages"),
            ribbon: document.getElementById("adm-bot-suggestions-ribbon"),
            input: document.getElementById("adm-bot-input"),
            expandIcon: document.getElementById("adm-bot-expand-icon"),
            micBtn: document.getElementById("adm-bot-mic-btn")
        };
    }

    function scrollToBottom() {
        const { messages } = getElements();
        if (messages) {
            setTimeout(() => {
                messages.scrollTop = messages.scrollHeight;
            }, 60);
        }
    }

    window.openAdminBot = function() {
        const { win, launcher, input } = getElements();
        if (!win) return;
        win.classList.add("is-open", "active");
        if (launcher) launcher.classList.add("is-active", "active");
        if (input) setTimeout(() => input.focus(), 200);
        scrollToBottom();
    };

    window.closeAdminBot = function() {
        const { win, launcher } = getElements();
        if (!win) return;
        win.classList.remove("is-open", "active");
        if (launcher) launcher.classList.remove("is-active", "active");
    };

    window.toggleAdminBot = function() {
        const { win } = getElements();
        if (!win) return;
        if (win.classList.contains("is-open") || win.classList.contains("active")) {
            window.closeAdminBot();
        } else {
            window.openAdminBot();
        }
    };

    window.toggleAdminBotSize = function() {
        const { win, expandIcon } = getElements();
        if (!win) return;
        win.classList.toggle("adm-bot-maximized");
        win.classList.toggle("expanded");
        const isMax = win.classList.contains("adm-bot-maximized") || win.classList.contains("expanded");
        if (expandIcon) {
            expandIcon.className = isMax ? "fa-solid fa-compress" : "fa-solid fa-expand";
        }
    };

    window.clearAdminBotChat = function() {
        if (confirm("Reset conversation and return to the main help menu?")) {
            localStorage.removeItem(STORAGE_CHAT_KEY);
            localStorage.removeItem(STORAGE_TOPIC_KEY);
            currentTopic = "";
            renderWelcome();
        }
    };

    function escapeHTML(str) {
        const p = document.createElement("p");
        p.textContent = str;
        return p.innerHTML;
    }

    function renderWelcome() {
        const { messages } = getElements();
        if (!messages) return;
        messages.innerHTML = `
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
        const { ribbon, input } = getElements();
        if (!ribbon) return;
        ribbon.innerHTML = "";
        STARTER_PROMPTS.forEach(p => {
            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "adm-bot-chip";
            chip.innerHTML = `<i class="${p.icon}"></i> <span>${p.label}</span>`;
            chip.addEventListener("click", () => {
                if (input) input.value = p.query;
                window.sendAdminBotMessage();
            });
            ribbon.appendChild(chip);
        });
    }

    function appendMessage(role, htmlContent, followUps = []) {
        const { messages, input } = getElements();
        if (!messages) return;

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

            if (Array.isArray(followUps) && followUps.length > 0) {
                const doubtsBox = document.createElement("div");
                doubtsBox.className = "adm-bot-doubts-box";
                followUps.forEach(doubtText => {
                    const doubtBtn = document.createElement("button");
                    doubtBtn.type = "button";
                    doubtBtn.className = "adm-bot-doubt-pill";
                    doubtBtn.innerHTML = `<i class="fa-solid fa-circle-question"></i> <span>${escapeHTML(doubtText)}</span>`;
                    doubtBtn.addEventListener("click", () => {
                        if (input) input.value = doubtText;
                        window.sendAdminBotMessage();
                    });
                    doubtsBox.appendChild(doubtBtn);
                });
                row.querySelector(".adm-bot-bubble.bot").appendChild(doubtsBox);
            }
        }

        messages.appendChild(row);
        scrollToBottom();
    }

    function showTypingIndicator() {
        removeTypingIndicator();
        const { messages } = getElements();
        if (!messages) return;
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
        messages.appendChild(ind);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const existing = document.getElementById("adm-bot-typing");
        if (existing) existing.remove();
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

    window.sendAdminBotMessage = function() {
        const { input, ribbon } = getElements();
        if (!input) return;
        const text = input.value.trim();
        if (!text) return;

        input.value = "";
        appendMessage("user", text);

        const history = getChatHistory();
        history.push({ role: "user", text: text, time: Date.now() });
        saveChatHistory(history);

        showTypingIndicator();

        const apiUrl = new URL('api/admin_assistant.php', window.location.href).href;

        fetch(apiUrl, {
            method: "POST",
            headers: { 
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
                query: text,
                context: {
                    current_topic: currentTopic,
                    page: window.location.pathname
                }
            })
        })
        .then(res => {
            if (!res.ok) {
                if (res.status === 401) {
                    throw new Error("SESSION_EXPIRED");
                }
                throw new Error("HTTP " + res.status);
            }
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

                if (data.follow_up_doubts && data.follow_up_doubts.length > 0 && ribbon) {
                    ribbon.innerHTML = "";
                    data.follow_up_doubts.forEach(d => {
                        const pill = document.createElement("button");
                        pill.type = "button";
                        pill.className = "adm-bot-chip";
                        pill.innerHTML = `<i class="fa-solid fa-question"></i> <span>${escapeHTML(d)}</span>`;
                        pill.addEventListener("click", () => {
                            if (input) input.value = d;
                            window.sendAdminBotMessage();
                        });
                        ribbon.appendChild(pill);
                    });
                }

                history.push({ role: "bot", html: data.reply, followUps: data.follow_up_doubts || [], time: Date.now() });
                saveChatHistory(history);
            } else {
                appendMessage("bot", `<p style="color:#F87171;">${data.reply || 'Could not complete request. Please try again.'}</p>`);
            }
        })
        .catch(err => {
            removeTypingIndicator();
            if (err && err.message === "SESSION_EXPIRED") {
                appendMessage("bot", `
                    <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 10px; padding: 12px 14px; font-size: 13px; color: #FDE68A;">
                        <i class="fa-solid fa-lock" style="color: #F59E0B; margin-right: 6px;"></i>
                        <strong>Admin Session Timeout:</strong> Your session has expired due to inactivity. Please <a href="login.php" style="color:#C5A059; text-decoration:underline; font-weight:700;">Log in again</a> or refresh the page to continue.
                    </div>
                `);
            } else {
                appendMessage("bot", `
                    <div style="color: #FCA5A5; font-size: 13px;">
                        <i class="fa-solid fa-circle-exclamation"></i> Connection issue with the Sanctuary Assistant API. Please check your network or try again.
                    </div>
                `);
            }
        });
    };

    // Voice recognition support
    function initVoice() {
        const { micBtn, input } = getElements();
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
                if (input && transcript) {
                    input.value = transcript;
                    window.sendAdminBotMessage();
                }
            };
            recognizer.onerror = () => micBtn.classList.remove("is-listening");
            recognizer.onend = () => {
                micBtn.classList.remove("is-listening");
                micBtn.title = "Speak your question (Voice Input)";
            };

            micBtn.addEventListener("click", () => {
                try { recognizer.start(); } catch(e) {}
            });
        }
    }

    // Startup initialization
    function initBot() {
        initVoice();
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
            renderWelcome();
        }

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                const { win } = getElements();
                if (win && (win.classList.contains("is-open") || win.classList.contains("active"))) {
                    window.closeAdminBot();
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBot);
    } else {
        initBot();
    }
})();
</script>
