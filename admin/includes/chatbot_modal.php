<?php
// =========================================================================
// Food Forest Sanctuary — Admin Assistant & Helpdesk Chatbot Widget
// Included universally via admin/includes/footer.php
// =========================================================================
?>

<!-- ========================================================================= -->
<!-- 1. FLOATING LAUNCHER BUTTON                                              -->
<!-- ========================================================================= -->
<button type="button" class="adm-bot-launcher" id="adm-bot-launcher" aria-label="Open Admin Concierge AI Assistant" title="Need help? Ask doubts about panel settings & get step-by-step procedures">
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
            <button type="button" class="adm-bot-header-btn" id="adm-bot-btn-clear" title="Clear conversation history" aria-label="Clear chat">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
            <!-- Maximize / Normal Height Toggle -->
            <button type="button" class="adm-bot-header-btn" id="adm-bot-btn-expand" title="Expand / Minimize Window" aria-label="Toggle size">
                <i class="fa-solid fa-expand" id="adm-bot-expand-icon"></i>
            </button>
            <!-- Close / Minimize Chatbot -->
            <button type="button" class="adm-bot-header-btn close" id="adm-bot-btn-close" title="Close Assistant" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Message Stream -->
    <div class="adm-bot-messages" id="adm-bot-messages">
        <!-- Default Welcome View will be populated here -->
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
