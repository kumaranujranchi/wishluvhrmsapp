<?php include 'includes/confirm_modal.php'; ?>
</div> <!-- End Main Content -->
</div> <!-- End App Container -->
<?php include 'includes/mobile_nav.php'; ?>

<!-- PWA Script -->
<script src="/assets/js/pwa.js"></script>

<!-- Init Lucide Icons -->
<script>
    lucide.createIcons();
</script>

<!-- Chatbot UI -->
<div class="chatbot-container">
    <div class="chat-bubble" id="chatBubble">
        <i data-lucide="message-square"></i>
    </div>

    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <img src="assets/logo.png" alt="Bot">
            <div class="bot-info">
                <h4>Smart Assistant</h4>
                <span>Online</span>
            </div>
            <div class="chat-close" id="chatClose">
                <i data-lucide="x" style="width: 20px;"></i>
            </div>
        </div>

        <div class="chat-body" id="chatBody">
            <!-- Messages will appear here -->
        </div>

        <div class="chat-footer">
            <input type="text" id="chatInput" placeholder="Type a message...">
            <button id="chatSend">
                <i data-lucide="send" style="width: 18px;"></i>
            </button>
        </div>
    </div>
</div>

<!-- Chatbot JS -->
<script src="assets/js/chatbot.js"></script>

</body>

</html>