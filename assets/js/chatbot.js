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
                addMessage('bot', "Hello! I am your HR Smart Assistant. How can I help you today? You can ask about your leave balance, attendance, or holidays.");
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

    function addMessage(type, text) {
        const div = document.createElement('div');
        div.className = `message ${type}-msg`;
        div.innerText = text;
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
