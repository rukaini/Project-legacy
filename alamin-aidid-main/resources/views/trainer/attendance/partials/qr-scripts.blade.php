<script>
(() => {
    'use strict';
    if (window.__attendanceQrDisplayInitialized) return;
    window.__attendanceQrDisplayInitialized = true;

    const modalSelector = '#attendanceQrDisplayModal';

    document.addEventListener('show.bs.modal', (event) => {
        const modal = event.target;
        const opener = event.relatedTarget;
        if (!modal?.matches(modalSelector) || !opener?.matches('[data-attendance-qr-open]')) return;

        modal.querySelector('[data-attendance-qr-session-title]').textContent = opener.dataset.sessionTitle || 'Training Session';
        modal.querySelector('[data-attendance-qr-session-date]').textContent = opener.dataset.sessionDate || 'Date not set';
        modal.querySelector('[data-attendance-qr-session-time]').textContent = opener.dataset.sessionTime || 'Time not set';

        const state = opener.dataset.qrState || 'active';
        const stateLabel = opener.dataset.qrLabel || 'Attendance QR status';
        const stateMessage = opener.dataset.qrMessage || '';
        const notice = modal.querySelector('[data-attendance-qr-state-notice]');
        notice.classList.remove('upcoming', 'active', 'expired', 'missing');
        notice.classList.add(state);
        notice.querySelector('[data-attendance-qr-state-label]').textContent = stateLabel;
        notice.querySelector('[data-attendance-qr-state-message]').textContent = stateMessage;
        notice.hidden = false;

        const hasQr = state !== 'missing';
        const instruction = modal.querySelector('[data-attendance-qr-instruction]');
        instruction.textContent = opener.dataset.qrInstruction || 'Scan this QR code to record your attendance.';
        instruction.hidden = !hasQr;

        const expiry = opener.dataset.qrExpiry || '';
        const expiryElement = modal.querySelector('[data-attendance-qr-modal-expiry]');
        expiryElement.textContent = expiry;
        expiryElement.hidden = expiry.length === 0;

        const imageUrl = opener.dataset.qrSrc || '';
        const presentationUrl = opener.dataset.qrPresentationUrl || '';
        const imageFrame = modal.querySelector('[data-attendance-qr-image-frame]');
        const actions = modal.querySelector('[data-attendance-qr-actions]');
        imageFrame.hidden = !hasQr;
        actions.hidden = !hasQr || imageUrl.length === 0 || presentationUrl.length === 0;
        const openImage = modal.querySelector('[data-attendance-qr-open-image]');
        if (presentationUrl) {
            openImage.href = presentationUrl;
            openImage.hidden = false;
        } else {
            openImage.removeAttribute('href');
            openImage.hidden = true;
        }

        const image = modal.querySelector('[data-attendance-qr-image]');
        const loading = modal.querySelector('[data-attendance-qr-loading]');
        const error = modal.querySelector('[data-attendance-qr-image-error]');
        image.hidden = true;
        error.hidden = true;
        loading.hidden = false;
        image.removeAttribute('src');
        image.onload = () => { loading.hidden = true; image.hidden = false; };
        image.onerror = () => { loading.hidden = true; image.hidden = true; error.hidden = false; };

        if (!hasQr) {
            loading.hidden = true;
        } else if (imageUrl) {
            image.src = imageUrl;
        } else {
            loading.hidden = true;
            error.hidden = false;
        }
    });

})();
</script>
