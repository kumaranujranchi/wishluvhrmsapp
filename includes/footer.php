<?php include 'includes/confirm_modal.php'; ?>
</div> <!-- End Main Content -->
</div> <!-- End App Container -->
<?php include 'includes/mobile_nav.php'; ?>

<!-- PWA Script -->
<script src="assets/js/pwa.js"></script>

<!-- Chatbot UI -->
<div class="chatbot-container">
    <div class="chat-bubble" id="chatBubble">
        <i data-lucide="message-circle-more" style="width: 30px; height: 30px;"></i>
        <div class="online-indicator"></div>
    </div>
    <div class="chat-helper-text" id="chatHelperText">I am here to help!</div>

    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <img src="assets/logo.png" alt="Wishluv Logo"
                style="width: 40px; height: 40px; border-radius: 10px; background: white; padding: 5px;">
            <div class="bot-info">
                <h4>Smart Assistant</h4>
                <span><span class="online-dot"></span> Online</span>
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

<!-- Init Lucide Icons AFTER UI is loaded -->
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<!-- Chatbot JS -->
<script src="assets/js/chatbot.js"></script>

</body>

</html>