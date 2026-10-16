<?php
// =========================================================================
// Food Forest Sanctuary — Admin Assistant & Helpdesk Chatbot Widget
// Included universally via admin/includes/footer.php
// Multi-Language Support: English, Malayalam (മലയാളം), and Tamil (தமிழ்)
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
        <span class="adm-bot-launcher-title" id="adm-bot-launcher-title">Admin AI Guide</span>
        <span class="adm-bot-launcher-sub" id="adm-bot-launcher-sub">Ask Doubts &amp; Steps</span>
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
                    <span class="adm-bot-status-pill"><span class="dot"></span> <span id="adm-bot-status-text">Online</span></span>
                    <span id="adm-bot-header-sub">• Setup &amp; Procedures Guide</span>
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

    <!-- Language Selector Bar (English, Malayalam, Tamil) -->
    <div class="adm-bot-lang-bar">
        <span class="adm-bot-lang-label">
            <i class="fa-solid fa-language"></i> <span id="adm-bot-lang-label-text">Language</span>:
        </span>
        <div class="adm-bot-lang-pills" role="radiogroup" aria-label="Select Assistant Language">
            <button type="button" class="adm-bot-lang-btn active" data-lang="en" onclick="window.setAdminBotLang('en');" title="English">
                English
            </button>
            <button type="button" class="adm-bot-lang-btn" data-lang="ml" onclick="window.setAdminBotLang('ml');" title="മലയാളം (Malayalam)">
                മലയാളം
            </button>
            <button type="button" class="adm-bot-lang-btn" data-lang="ta" onclick="window.setAdminBotLang('ta');" title="தமிழ் (Tamil)">
                தமிழ்
            </button>
        </div>
    </div>

    <!-- Message Stream -->
    <div class="adm-bot-messages" id="adm-bot-messages">
        <!-- Messages will be populated dynamically -->
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
        <div class="adm-bot-footer-hint" id="adm-bot-footer-hint">
            <span>Press <kbd>Enter</kbd> to send • Supports English, മലയാളം &amp; தமிழ்</span>
        </div>
    </div>

</div>

<!-- Self-Contained Multi-Lingual Controller Script -->
<script>
(function() {
    const STORAGE_CHAT_KEY = "foodforest_adm_bot_history_v2";
    const STORAGE_TOPIC_KEY = "foodforest_adm_bot_topic_v2";
    const STORAGE_LANG_KEY = "foodforest_adm_bot_lang_v2";

    let currentTopic = localStorage.getItem(STORAGE_TOPIC_KEY) || "";
    let currentLang = localStorage.getItem(STORAGE_LANG_KEY) || "en";
    if (!["en", "ml", "ta"].includes(currentLang)) {
        currentLang = "en";
    }

    // Multi-Lingual UI Dictionary
    const I18N = {
        en: {
            launcherTitle: "Admin AI Guide",
            launcherSub: "Ask Doubts & Steps",
            headerTitle: "Sanctuary Admin AI Concierge",
            headerSub: "• Setup & Procedures Guide",
            statusOnline: "Online",
            langLabel: "Language",
            inputPlaceholder: "Ask any doubt about panel settings or steps...",
            micTitle: "Speak your question (Voice Input)",
            micListening: "Listening to your voice...",
            sendTitle: "Send Question",
            footerHint: "Press <kbd>Enter</kbd> to send • Supports English, മലയാളം &amp; தமிழ்",
            clearConfirm: "Reset conversation and return to the main help menu?",
            welcomeTitle: "Sanctuary Operational Helpdesk",
            welcomeDesc: "I am your dedicated 24/7 administrator assistant. Ask any question about configuring tariffs, bank & UPI QR, billing folios, kitchen orders, calendar, or clarifying any step in detail.",
            sessionExpiredTitle: "Admin Session Timeout:",
            sessionExpiredDesc: "Your administrator session has timed out. Please <a href='login.php' style='color:#C5A059; text-decoration:underline; font-weight:700;'>Log in again</a> or refresh the page to continue.",
            connError: "Connection issue with the Sanctuary Assistant API. Please check your network or try again.",
            starterChips: [
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
            ]
        },
        ml: {
            launcherTitle: "അഡ്മിൻ എഐ സഹായി",
            launcherSub: "സംശയങ്ങളും ഘട്ടങ്ങളും",
            headerTitle: "അഡ്മിൻ എഐ കൺസിയർജ്",
            headerSub: "• സെറ്റിംഗ്സ് & നടപടിക്രമങ്ങൾ",
            statusOnline: "ഓൺലൈൻ",
            langLabel: "ഭാഷ",
            inputPlaceholder: "സെറ്റിംഗ്സ് അല്ലെങ്കിൽ ഘട്ടങ്ങളെക്കുറിച്ചുള്ള സംശയങ്ങൾ ചോദിക്കൂ...",
            micTitle: "ശബ്ദത്തിലൂടെ ചോദിക്കൂ (Voice Input)",
            micListening: "നിങ്ങളുടെ ചോദ്യം കേൾക്കുന്നു...",
            sendTitle: "ചോദ്യം അയക്കുക",
            footerHint: "അയക്കാൻ <kbd>Enter</kbd> അമർത്തുക • ഇംഗ്ലീഷ്, മലയാളം &amp; തമിഴ് സപ്പോർട്ട് ചെയ്യുന്നു",
            clearConfirm: "സംഭാഷണം ഒഴിവാക്കി പ്രധാന മെനുവിലേക്ക് മടങ്ങണോ?",
            welcomeTitle: "അഡ്മിൻ സഹായ കേന്ദ്രം",
            welcomeDesc: "ഞാൻ നിങ്ങളുടെ 24/7 അഡ്മിനിസ്ട്രേറ്റർ സഹായിയാണ്. വില്ല വാടക, ബാങ്ക് വിവരങ്ങൾ, UPI QR കോഡ്, ബില്ലിംഗ്, കിച്ചൻ ഓർഡറുകൾ, കലണ്ടർ തീയതി ബ്ലോക്കിംഗ് എന്നിവയെക്കുറിച്ചുള്ള ഏത് സംശയങ്ങളും ഘട്ടങ്ങളും ചോദിക്കാം.",
            sessionExpiredTitle: "അഡ്മിൻ സെഷൻ കാലാവധി കഴിഞ്ഞു:",
            sessionExpiredDesc: "നിങ്ങളുടെ സെഷൻ കാലാവധി കഴിഞ്ഞിരിക്കുന്നു. ദയവായി <a href='login.php' style='color:#C5A059; text-decoration:underline; font-weight:700;'>വീണ്ടും ലോഗിൻ ചെയ്യുക</a> അല്ലെങ്കിൽ പേജ് റീഫ്രഷ് ചെയ്യുക.",
            connError: "സഹായി API യുമായി ബന്ധപ്പെടാൻ കഴിഞ്ഞില്ല. ദയവായി നെറ്റ്‌വർക്ക് പരിശോധിച്ച് വീണ്ടും ശ്രമിക്കുക.",
            starterChips: [
                { icon: "fa-solid fa-house-chimney", label: "റൂം വാടക മാറ്റുക", query: "വില്ല വാടകയും അധിക അതിഥി നിരക്കും എങ്ങനെ മാറ്റാം?" },
                { icon: "fa-solid fa-building-columns", label: "ബാങ്ക് & UPI QR സെറ്റപ്പ്", query: "ബില്ലിംഗിനായി ബാങ്ക് വിവരങ്ങളും UPI QR കോഡും എങ്ങനെ നൽകാം?" },
                { icon: "fa-solid fa-receipt", label: "ബില്ലിംഗ് & A4 ഇൻവോയ്സ്", query: "ഗസ്റ്റ് ബില്ലിംഗും A4 ഇൻവോയ്സ് പ്രിന്റിംഗും എങ്ങനെ പ്രവർത്തിക്കുന്നു?" },
                { icon: "fa-solid fa-utensils", label: "ഭക്ഷണ മെനു മാറ്റങ്ങൾ", query: "ഫുഡ് മെനു ഇനങ്ങളും സമയക്രമങ്ങളും എങ്ങനെ മാറ്റാം?" },
                { icon: "fa-solid fa-percent", label: "GST നികുതി ക്രമീകരണം", query: "റൂമിനും ഭക്ഷണത്തിനുമുള്ള GST നികുതി നിരക്കുകൾ എങ്ങനെ ക്രമീകരിക്കാം?" },
                { icon: "fa-solid fa-calendar-days", label: "കലണ്ടറിൽ തീയതി ബ്ലോക്ക് ചെയ്യുക", query: "ബുക്കിംഗ് കലണ്ടറിൽ തീയതികൾ എങ്ങനെ ബ്ലോക്ക് ചെയ്യാം?" },
                { icon: "fa-solid fa-arrows-rotate", label: "Airbnb / OTA കലണ്ടർ സിങ്ക്", query: "Airbnb, Booking.com എന്നിവയുമായി കലണ്ടർ എങ്ങനെ iCal വഴി സിങ്ക് ചെയ്യാം?" },
                { icon: "fa-solid fa-bell-concierge", label: "ഫുഡ് ഓർഡറിംഗ് സ്വിച്ച്", query: "അതിഥികൾക്ക് കോട്ടേജിൽ നിന്നുള്ള ഫുഡ് ഓർഡറിംഗ് എങ്ങനെ ഓൺ അല്ലെങ്കിൽ ഓഫ് ചെയ്യാം?" },
                { icon: "fa-solid fa-map-location-dot", label: "മാപ്പിലെ ലൊക്കേഷനുകൾ", query: "റിസോർട്ട് മാപ്പിലെ ലൊക്കേഷനുകളും വഴികളും എങ്ങനെ എഡിറ്റ് ചെയ്യാം?" },
                { icon: "fa-solid fa-trash-can", label: "ഡെമോ ഡാറ്റ നീക്കം ചെയ്യുക", query: "ലോഞ്ചിന് മുൻപ് ഡെമോ ബുക്കിംഗുകൾ എങ്ങനെ സുരക്ഷിതമായി ഒഴിവാക്കാം?" }
            ]
        },
        ta: {
            launcherTitle: "நிர்வாகி AI வழிகாட்டி",
            launcherSub: "சந்தேகங்கள் & படிகள்",
            headerTitle: "நிர்வாகி AI வழிகாட்டி மையம்",
            headerSub: "• அமைப்புகள் & செயல்முறை வழிகாட்டி",
            statusOnline: "ஆன்லைன்",
            langLabel: "மொழி",
            inputPlaceholder: "அமைப்புகள் அல்லது படிகள் பற்றிய சந்தேகங்களை கேட்கவும்...",
            micTitle: "குரல் மூலம் கேட்கவும் (Voice Input)",
            micListening: "உங்கள் குரலை கேட்கிறது...",
            sendTitle: "கேள்வியை அனுப்புக",
            footerHint: "அனுப்ப <kbd>Enter</kbd> அழுத்தவும் • ஆங்கிலம், மலையாளம் &amp; தமிழ் ஆதரிக்கப்படுகிறது",
            clearConfirm: "உரையாடலை மீட்டமைத்து முதன்மை மெனுவிற்கு திரும்ப வேண்டுமா?",
            welcomeTitle: "நிர்வாக உதவி மையம்",
            welcomeDesc: "நான் உங்கள் 24/7 நிர்வாக உதவியாளர். அறை கட்டணம், வங்கி & UPI QR குறியீடு, பில்லிங், சமையலறை ஆர்டர்கள், காலண்டர் தேதி தடுப்பு பற்றிய எந்தவொரு சந்தேகங்களையும் படிகளையும் நீங்கள் கேட்கலாம்.",
            sessionExpiredTitle: "நிர்வாக அமர்வு காலாவதியானது:",
            sessionExpiredDesc: "உங்கள் நிர்வாக அமர்வு காலாவதியானது. தொடர <a href='login.php' style='color:#C5A059; text-decoration:underline; font-weight:700;'>மீண்டும் உள்நுழையவும்</a> அல்லது பக்கத்தை புதுப்பிக்கவும்.",
            connError: "உதவியாளர் API உடன் இணைப்பதில் சிக்கல். நெட்வொர்க்கை சரிபார்த்து மீண்டும் முயற்சிக்கவும்.",
            starterChips: [
                { icon: "fa-solid fa-house-chimney", label: "அறை வாடகை மாற்றம்", query: "வில்லா கட்டணம் மற்றும் கூடுதல் விருந்தினர் கட்டணத்தை மாற்றுவது எப்படி?" },
                { icon: "fa-solid fa-building-columns", label: "வங்கி & UPI QR அமைப்பு", query: "பில்லிங்கிற்கு வங்கி விவரங்கள் மற்றும் UPI QR குறியீட்டை அமைப்பது எப்படி?" },
                { icon: "fa-solid fa-receipt", label: "பில்லிங் & A4 விலைப்பட்டியல்", query: "விருந்தினர் பில்லிங் மற்றும் A4 விலைப்பட்டியல் அச்சிடுவது எப்படி?" },
                { icon: "fa-solid fa-utensils", label: "உணவு பட்டியல் & நேரம்", query: "உணவு பொருட்கள் மற்றும் உணவு நேரங்களை சேர்ப்பது அல்லது மாற்றுவது எப்படி?" },
                { icon: "fa-solid fa-percent", label: "GST வரி அமைப்புகள்", query: "அறை மற்றும் உணவிற்கான GST வரி விகிதங்களை அமைப்பது எப்படி?" },
                { icon: "fa-solid fa-calendar-days", label: "நாட்காட்டியில் தேதியை முடக்கு", query: "முன்பதிவு காலண்டரில் குறிப்பிட்ட தேதிகளை தடுப்பது எப்படி?" },
                { icon: "fa-solid fa-arrows-rotate", label: "Airbnb / OTA காலண்டர் இணைப்பு", query: "iCal வழியாக Airbnb மற்றும் Booking.com உடன் நாட்காட்டியை இணைப்பது எப்படி?" },
                { icon: "fa-solid fa-bell-concierge", label: "உணவு ஆர்டர் சுவிட்ச்", query: "விருந்தினர்களுக்கான உணவு ஆர்டர் வசதியை ஆன் அல்லது ஆஃப் செய்வது எப்படி?" },
                { icon: "fa-solid fa-map-location-dot", label: "வரைபட இடங்கள் மாற்றம்", query: "ரிசார்ட் வரைபடத்தில் இடங்கள் மற்றும் நடைபாதைகளை மாற்றுவது எப்படி?" },
                { icon: "fa-solid fa-trash-can", label: "டெமோ தரவை நீக்குதல்", query: "துவங்குவதற்கு முன் மாதிரி முன்பதிவுகளை பாதுகாப்பாக நீக்குவது எப்படி?" }
            ]
        }
    };

    let activeRecognizer = null;

    function getElements() {
        return {
            launcher: document.getElementById("adm-bot-launcher"),
            launcherTitle: document.getElementById("adm-bot-launcher-title"),
            launcherSub: document.getElementById("adm-bot-launcher-sub"),
            win: document.getElementById("adm-bot-window"),
            headerTitle: document.getElementById("adm-bot-header-title"),
            headerSub: document.getElementById("adm-bot-header-sub"),
            statusText: document.getElementById("adm-bot-status-text"),
            langLabelText: document.getElementById("adm-bot-lang-label-text"),
            messages: document.getElementById("adm-bot-messages"),
            ribbon: document.getElementById("adm-bot-suggestions-ribbon"),
            input: document.getElementById("adm-bot-input"),
            footerHint: document.getElementById("adm-bot-footer-hint"),
            expandIcon: document.getElementById("adm-bot-expand-icon"),
            micBtn: document.getElementById("adm-bot-mic-btn"),
            sendBtn: document.getElementById("adm-bot-send-btn"),
            btnClear: document.getElementById("adm-bot-btn-clear")
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
        const strings = I18N[currentLang] || I18N.en;
        if (confirm(strings.clearConfirm)) {
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

    // Set and Switch Active Language
    window.setAdminBotLang = function(newLang) {
        if (!["en", "ml", "ta"].includes(newLang)) newLang = "en";
        currentLang = newLang;
        localStorage.setItem(STORAGE_LANG_KEY, currentLang);

        // Update active class on pills
        document.querySelectorAll(".adm-bot-lang-btn").forEach(btn => {
            if (btn.getAttribute("data-lang") === currentLang) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });

        // Apply translated UI strings
        const strings = I18N[currentLang] || I18N.en;
        const els = getElements();
        if (els.launcherTitle) els.launcherTitle.textContent = strings.launcherTitle;
        if (els.launcherSub) els.launcherSub.textContent = strings.launcherSub;
        if (els.headerTitle) els.headerTitle.textContent = strings.headerTitle;
        if (els.headerSub) els.headerSub.textContent = strings.headerSub;
        if (els.statusText) els.statusText.textContent = strings.statusOnline;
        if (els.input) els.input.placeholder = strings.inputPlaceholder;
        if (els.footerHint) els.footerHint.innerHTML = `<span>${strings.footerHint}</span>`;
        if (els.sendBtn) els.sendBtn.title = strings.sendTitle;
        if (els.micBtn) els.micBtn.title = strings.micTitle;

        // Update speech recognizer language
        if (activeRecognizer) {
            activeRecognizer.lang = (currentLang === 'ml' ? 'ml-IN' : (currentLang === 'ta' ? 'ta-IN' : 'en-IN'));
        }

        // Render starter prompt chips in the chosen language
        renderStarterChips();

        // If messages contain only the welcome screen, re-render it in new language
        const history = getChatHistory();
        if (!history || history.length === 0) {
            renderWelcome();
        }
    };

    function renderWelcome() {
        const { messages } = getElements();
        if (!messages) return;
        const strings = I18N[currentLang] || I18N.en;
        messages.innerHTML = `
            <div class="adm-bot-welcome">
                <div class="adm-bot-welcome-icon">
                    <i class="fa-solid fa-compass"></i>
                </div>
                <h4>${escapeHTML(strings.welcomeTitle)}</h4>
                <p>${escapeHTML(strings.welcomeDesc)}</p>
            </div>
        `;
        renderStarterChips();
    }

    function renderStarterChips() {
        const { ribbon, input } = getElements();
        if (!ribbon) return;
        ribbon.innerHTML = "";
        const strings = I18N[currentLang] || I18N.en;
        strings.starterChips.forEach(p => {
            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "adm-bot-chip";
            chip.innerHTML = `<i class="${p.icon}"></i> <span>${escapeHTML(p.label)}</span>`;
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
                lang: currentLang,
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

                // If API detected a specific language matching the query script, sync UI language selector
                if (data.lang && data.lang !== currentLang && ["en", "ml", "ta"].includes(data.lang)) {
                    window.setAdminBotLang(data.lang);
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
            const strings = I18N[currentLang] || I18N.en;
            if (err && err.message === "SESSION_EXPIRED") {
                appendMessage("bot", `
                    <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 10px; padding: 12px 14px; font-size: 13px; color: #FDE68A;">
                        <i class="fa-solid fa-lock" style="color: #F59E0B; margin-right: 6px;"></i>
                        <strong>${strings.sessionExpiredTitle}</strong> ${strings.sessionExpiredDesc}
                    </div>
                `);
            } else {
                appendMessage("bot", `
                    <div style="color: #FCA5A5; font-size: 13px;">
                        <i class="fa-solid fa-circle-exclamation"></i> ${strings.connError}
                    </div>
                `);
            }
        });
    };

    // Voice recognition support with language sensitivity
    function initVoice() {
        const { micBtn, input } = getElements();
        if (micBtn && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
            micBtn.style.display = "inline-flex";
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            activeRecognizer = new SpeechRec();
            activeRecognizer.continuous = false;
            activeRecognizer.interimResults = false;
            activeRecognizer.lang = (currentLang === 'ml' ? 'ml-IN' : (currentLang === 'ta' ? 'ta-IN' : 'en-IN'));

            activeRecognizer.onstart = () => {
                micBtn.classList.add("is-listening");
                const strings = I18N[currentLang] || I18N.en;
                micBtn.title = strings.micListening;
            };
            activeRecognizer.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                if (input && transcript) {
                    input.value = transcript;
                    window.sendAdminBotMessage();
                }
            };
            activeRecognizer.onerror = () => micBtn.classList.remove("is-listening");
            activeRecognizer.onend = () => {
                micBtn.classList.remove("is-listening");
                const strings = I18N[currentLang] || I18N.en;
                micBtn.title = strings.micTitle;
            };

            micBtn.addEventListener("click", () => {
                try {
                    activeRecognizer.lang = (currentLang === 'ml' ? 'ml-IN' : (currentLang === 'ta' ? 'ta-IN' : 'en-IN'));
                    activeRecognizer.start();
                } catch(e) {}
            });
        }
    }

    // Startup initialization
    function initBot() {
        initVoice();
        window.setAdminBotLang(currentLang);

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
