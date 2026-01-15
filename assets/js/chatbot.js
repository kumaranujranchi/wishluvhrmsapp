/* Chatbot Logic */
document.addEventListener('DOMContentLoaded', () => {
    const bubble = document.getElementById('chatBubble');
    const window = document.getElementById('chatWindow');
    const closeBtn = document.getElementById('chatClose');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    const body = document.getElementById('chatBody');

    // Toggle Chat
    bubble.addEventListener('click', () => {
        window.classList.toggle('active');
        if (window.classList.contains('active')) {
            input.focus();
            if (body.children.length === 0) {
                addMessage('bot', "Hello! main Wishluv Smart Assistant hoon. Aapki HR se judi help kaise kar sakti hoon? Aap mujhse apni leave balance, attendance, ya holidays ke baare mein pooch sakte hain.");
            }
        }
    });

    closeBtn.addEventListener('click', () => {
        window.classList.remove('active');
    });

    // Send Message
    const sendMessage = () => {
        const text = input.value.trim();
        if (!text) return;

        addMessage('user', text);
        input.value = '';
        
        showTyping();

        fetch('ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `message=${encodeURIComponent(text)}`
        })
        .then(res => res.json())
        .then(data => {
            hideTyping();
            addMessage('bot', data.response);
        })
        .catch(err => {
            hideTyping();
            addMessage('bot', "Sorry, I encountered an error. Please try again later.");
            console.error(err);
        });
    };

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function formatMessage(text) {
        if (!text) return "";

        // 1. Escape basic HTML for security (XSS prevention)
        let formatted = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // 2. Bold: **text** -> <b>text</b>
        // Using a more robust regex that handles potential multi-line and greedy matching
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');

        // 3. Italic: _text_ or *text* -> <i>text</i>
        // We do this AFTER bold to avoid partial matches
        formatted = formatted.replace(/\*(.*?)\*/g, '<i>$1</i>');
        formatted = formatted.replace(/_(.*?)_/g, '<i>$1</i>');

        // 4. Bullet points: Start of line with - or *
        // Handle both - and * and space
        formatted = formatted.replace(/^[\s]*[-*][\s]+(.*)/gm, '• $1');

        // 5. Newlines: \n -> <br>
        formatted = formatted.replace(/\n/g, '<br>');

        return formatted;
    }

    function addMessage(type, text) {
        const div = document.createElement('div');
        div.className = `message ${type}-msg`;
        div.innerHTML = formatMessage(text);
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }

    function showTyping() {
        const loader = document.createElement('div');
        loader.className = 'typing-indicator';
        loader.id = 'typingIndicator';
        loader.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
        body.appendChild(loader);
        body.scrollTop = body.scrollHeight;
    }

    function hideTyping() {
        const loader = document.getElementById('typingIndicator');
        if (loader) loader.remove();
    }
});
