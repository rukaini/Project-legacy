<div class="modal fade attendance-qr-modal" id="attendanceQrDisplayModal" tabindex="-1" aria-labelledby="attendanceQrDisplayTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="attendance-qr-modal-kicker">Session attendance</p>
                    <h2 class="modal-title" id="attendanceQrDisplayTitle">Attendance QR</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="attendance-qr-session">
                    <h3 data-attendance-qr-session-title>Training Session</h3>
                    <div class="attendance-qr-session-meta">
                        <span><i class="bi bi-calendar3" aria-hidden="true"></i><span data-attendance-qr-session-date>Date not set</span></span>
                        <span><i class="bi bi-clock" aria-hidden="true"></i><span data-attendance-qr-session-time>Time not set</span></span>
                    </div>
                </div>
                <div class="attendance-qr-state-notice" data-attendance-qr-state-notice hidden>
                    <span class="attendance-qr-modal-state-dot" aria-hidden="true"></span>
                    <div>
                        <strong data-attendance-qr-state-label>Attendance QR status</strong>
                        <span data-attendance-qr-state-message></span>
                    </div>
                </div>
                <div class="attendance-qr-image-frame" data-attendance-qr-image-frame>
                    <div class="attendance-qr-loading" data-attendance-qr-loading><span class="spinner-border" aria-hidden="true"></span><span>Loading attendance QR…</span></div>
                    <img data-attendance-qr-image alt="Attendance QR code for the selected session" hidden>
                    <div class="attendance-qr-image-error" data-attendance-qr-image-error hidden>
                        <i class="bi bi-image" aria-hidden="true"></i>
                        <span>QR image could not be loaded.</span>
                    </div>
                </div>
                <p class="attendance-qr-instruction" data-attendance-qr-instruction>Scan this QR code to record your attendance.</p>
                <p class="attendance-qr-modal-expiry" data-attendance-qr-modal-expiry hidden></p>
                <div class="attendance-qr-modal-actions" data-attendance-qr-actions>
                    <a class="attendance-qr-modal-action" data-attendance-qr-open-image target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        Open QR Display
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
