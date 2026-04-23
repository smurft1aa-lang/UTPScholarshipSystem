document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('chatbotToggleBtn');
    const closeBtn = document.getElementById('chatbotCloseBtn');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const messagesContainer = document.getElementById('chatbotMessages');
    const inputField = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSendBtn');

    // Conversation history to send context back to Gemini
    let chatHistory = [];

    // Pre-populate system greeting naturally so it doesn't need to be in the history sent to the model
    // but the user sees it.
    
    // UI Toggles
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            chatbotWindow.classList.remove('hidden');
            toggleBtn.style.transform = 'scale(0)';
            setTimeout(() => inputField.focus(), 300);
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            chatbotWindow.classList.add('hidden');
            toggleBtn.style.transform = 'scale(1)';
        });
    }

    // Sending Messages
    function sendMessage() {
        const text = inputField.value.trim();
        if (!text) return;

        // 1. Add user message to UI
        appendMessage(text, 'user');
        inputField.value = '';
        inputField.disabled = true;
        sendBtn.disabled = true;

        // 2. Add to history
        chatHistory.push({
            role: 'user',
            parts: [{ text: text }]
        });

        // 3. Show typing indicator
        const typingId = 'typing-' + Date.now();
        showTypingIndicator(typingId);

        // 4. Determine CSRF
        let csrfToken = '';
        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        if (metaCsrf) {
            csrfToken = metaCsrf.getAttribute('content');
        } else {
            const formCsrf = document.querySelector('input[name="csrf_token"]');
            if (formCsrf) csrfToken = formCsrf.value;
        }

        // 5. Send to API
        fetch('/api/chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: csrfToken,
                history: chatHistory
            })
        })
        .then(response => response.json())
        .then(data => {
            removeTypingIndicator(typingId);
            
            if (data.new_csrf_token) {
                // Update CSRF
                if (metaCsrf) metaCsrf.setAttribute('content', data.new_csrf_token);
                document.querySelectorAll('input[name="csrf_token"]').forEach(el => el.value = data.new_csrf_token);
            }

            if (data.success && data.reply) {
                appendMessage(data.reply, 'bot');
                chatHistory.push({
                    role: 'model',
                    parts: [{ text: data.reply }]
                });
            } else {
                appendMessage(data.error || "Sorry, I couldn't understand that.", 'bot');
                // Remove the last user message from memory so it doesn't break future context
                chatHistory.pop();
            }
        })
        .catch(err => {
            console.error('Chat API Error:', err);
            removeTypingIndicator(typingId);
            appendMessage("Network error. Please try again.", 'bot');
            chatHistory.pop();
        })
        .finally(() => {
            inputField.disabled = false;
            sendBtn.disabled = false;
            inputField.focus();
        });
    }

    sendBtn.addEventListener('click', sendMessage);

    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    // Helpers
    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message ' + sender;
        
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        
        // Simple markdown parsing for the bot (bold and bullet points)
        if (sender === 'bot') {
            let formattedText = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n\*/g, '<br>•')
                .replace(/\n-/g, '<br>•')
                .replace(/\n/g, '<br>');
            bubble.innerHTML = formattedText;
        } else {
            bubble.textContent = text;
        }

        msgDiv.appendChild(bubble);
        messagesContainer.appendChild(msgDiv);
        scrollToBottom();
    }

    function showTypingIndicator(id) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message bot';
        msgDiv.id = id;
        
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
        
        msgDiv.appendChild(indicator);
        messagesContainer.appendChild(msgDiv);
        scrollToBottom();
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});
