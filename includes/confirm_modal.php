<!-- Custom Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-body modal-content-centered">
            <div class="modal-confirm-icon">
                <i data-lucide="alert-triangle" style="width:24px; height:24px;"></i>
            </div>
            <h3 class="modal-title" style="margin-bottom: 0.5rem;">Delete Item?</h3>
            <p class="modal-text">Are you sure you want to delete this? This action cannot be undone.</p>

            <div style="display: flex; gap: 1rem; width: 100%; justify-content: center;">
                <button type="button" class="btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(url) {
        const modal = document.getElementById('confirmModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        // Set the href of the delete button to the specific deletion URL
        confirmBtn.href = url;

        modal.classList.add('show');
        return false; // Prevent default link behavior
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.remove('show');
    }

    // Close on click outside
    document.getElementById('confirmModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeConfirmModal();
        }
    });
</script>