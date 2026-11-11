<dialog id="logoutConfirmationDialog"
        class="logout-confirm-dialog"
        aria-labelledby="logoutConfirmationTitle"
        aria-describedby="logoutConfirmationMessage">
    <div class="logout-confirm-dialog__content">
        <h2 id="logoutConfirmationTitle">Log out</h2>
        <p id="logoutConfirmationMessage">Are you sure you want to log out?</p>
        <div class="logout-confirm-dialog__actions">
            <button type="button" class="logout-confirm-dialog__cancel" data-logout-confirm-cancel>Cancel</button>
            <button type="submit" form="aididLogoutForm" class="logout-confirm-dialog__submit">Logout</button>
        </div>
    </div>
</dialog>

<script>
    (() => {
        const dialog = document.getElementById('logoutConfirmationDialog');
        const opener = document.querySelector('[data-logout-confirm-open]');
        const cancelButton = dialog?.querySelector('[data-logout-confirm-cancel]');

        if (!dialog || !opener || !cancelButton) return;

        const closeDialog = () => {
            dialog.close();
        };

        opener.addEventListener('click', () => {
            dialog.showModal();
            cancelButton.focus();
        });

        cancelButton.addEventListener('click', closeDialog);
        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            closeDialog();
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) closeDialog();
        });
        dialog.addEventListener('close', () => opener.focus());
    })();
</script>
