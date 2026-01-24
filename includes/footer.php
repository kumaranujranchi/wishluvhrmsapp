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

<script>
    function toggleMobileChat() {
        const cw = document.getElementById('chatWindow');
        if (cw) cw.classList.toggle('active');
    }

    // Update live time in header
    function updateLiveTime() {
        const timeElement = document.getElementById('live-time');
        if (timeElement) {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            timeElement.textContent = `${hours}:${minutes}`;
        }
    }

    // Update time immediately and then every minute
    updateLiveTime();
    setInterval(updateLiveTime, 60000);
</script>

</body>


<!-- Global Pop-up Notification Modal -->
<div id="popupNoticeModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
    <div
        style="background: white; width: 100%; max-width: 500px; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: zoomIn 0.3s ease;">
        <!-- Header -->
        <div style="background: #e0e7ff; padding: 1.5rem; text-align: center; border-bottom: 1px solid #c7d2fe;">
            <div
                style="width: 50px; height: 50px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: #4f46e5;">
                <i data-lucide="bell-ring" style="width: 24px;"></i>
            </div>
            <h3 id="popupTitle" style="color: #3730a3; font-weight: 700; font-size: 1.25rem; margin: 0;"></h3>
            <span id="popupUrgency" class="badge" style="margin-top: 8px; display: inline-block;"></span>
        </div>

        <!-- Body -->
        <div style="padding: 2rem; max-height: 60vh; overflow-y: auto;">
            <div id="popupContent" style="color: #334155; line-height: 1.6; font-size: 1rem; white-space: pre-wrap;">
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;">
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">
                <i data-lucide="lock" style="width: 14px; vertical-align: middle;"></i>
                You must acknowledge this notice to continue.
            </p>
            <button id="markPopupReadBtn" class="btn-primary"
                style="width: 100%; justify-content: center; font-size: 1rem; padding: 0.8rem;">
                I have read and understood
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes zoomIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetchPopupNotice();
    });

    function fetchPopupNotice() {
        fetch('ajax/get_popup_notice.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'found') {
                    const notice = data.notice;
                    document.getElementById('popupTitle').textContent = notice.title;
                    document.getElementById('popupContent').textContent = notice.content;

                    // Urgency Badge
                    const urgency = notice.urgency || 'Normal';
                    const uBadge = document.getElementById('popupUrgency');
                    uBadge.textContent = urgency;

                    // Style based on urgency
                    if (urgency === 'High' || urgency === 'Urgent') {
                        uBadge.style.backgroundColor = '#fee2e2';
                        uBadge.style.color = '#991b1b';
                    } else {
                        uBadge.style.backgroundColor = '#dcfce7';
                        uBadge.style.color = '#166534';
                    }

                    // Show Modal
                    const modal = document.getElementById('popupNoticeModal');
                    modal.style.display = 'flex';

                    // Block Page Scroll
                    document.body.style.overflow = 'hidden';

                    // Refresh icons inside modal
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }

                    // Handle Mark as Read
                    document.getElementById('markPopupReadBtn').onclick = function () {
                        markPopupRead(notice.id);
                    };
                }
            })
            .catch(err => console.error('Error fetching popup:', err));
    }

    function markPopupRead(id) {
        const btn = document.getElementById('markPopupReadBtn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        fetch('ajax/mark_notice_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notice_id: id })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('popupNoticeModal').style.display = 'none';
                    document.body.style.overflow = 'auto'; // Restore scroll
                } else {
                    alert('Error: ' + (data.message || 'Could not mark as read.'));
                    btn.disabled = false;
                    btn.textContent = 'I have read and understood';
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.textContent = 'I have read and understood';
            });
    }
</script>

</html>