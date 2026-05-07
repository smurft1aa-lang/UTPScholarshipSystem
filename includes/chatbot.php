<?php
// Secure strictly authenticated
if (!isLoggedIn())
    return;
?>
<link rel="stylesheet" href="/assets/css/chatbot.css">

<!-- Chatbot Toggle Button -->
<button id="chatbotToggleBtn" class="chatbot-toggle" aria-label="Open Chat Support">
    <span class="chatbot-icon">💬</span>
</button>

<!-- Chatbot Window -->
<div id="chatbotWindow" class="chatbot-window hidden">
    <div class="chatbot-header">
        <div class="chatbot-header-info">
            <strong>UTP Virtual Assistant</strong>
            <span class="chatbot-status">Online 🟢</span>
        </div>
        <button id="chatbotCloseBtn" class="chatbot-close-btn" aria-label="Close Chat">✕</button>
    </div>

    <div id="chatbotMessages" class="chatbot-messages">
        <div class="chat-message bot">
            <div class="chat-bubble">
                Hi! I'm your UTP virtual assistant. Ask me anything about scholarships and requirements!
            </div>
        </div>
    </div>

    <div class="chatbot-input-area">
        <input type="text" id="chatbotInput" placeholder="Type your question..." autocomplete="off">
        <button id="chatbotSendBtn" class="chatbot-send-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
        </button>
    </div>
</div>




<script src="/assets/js/chatbot.js"></script>