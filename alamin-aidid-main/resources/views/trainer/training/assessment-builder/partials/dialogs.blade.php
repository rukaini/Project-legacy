<div id="customTemplateModal" class="lw-modal hidden" role="dialog" aria-modal="true" aria-labelledby="customTemplateTitle" tabindex="-1">
    <div class="lw-modal-box" style="max-width: 620px;">
        <div class="lw-modal-head">
            <div class="lw-modal-head-text">
                <h2 class="lw-modal-title" id="customTemplateTitle">Custom question mix</h2>
                <div class="lw-modal-sub">Build one mixed batch of up to 100 quiz questions.</div>
            </div>
            <button type="button" class="lw-modal-close" id="closeCustomTemplate" aria-label="Close custom template">&times;</button>
        </div>
        <div class="lw-modal-body">
            <div id="customMixRows" class="lw-custom-mix-rows"></div>
            <button type="button" id="addCustomMixRow" class="lw-inline-action">
                <i class="bi bi-plus-lg"></i> Add another type
            </button>
            <div class="lw-custom-mix-summary">
                <span>Total questions</span>
                <strong id="customMixTotal">10</strong>
            </div>
        </div>
        <div class="lw-modal-foot">
            <button type="button" id="cancelCustomTemplate" class="lw-btn ghost">Cancel</button>
            <button type="button" id="applyCustomTemplate" class="lw-btn primary">Add 10 Questions</button>
        </div>
    </div>
</div>

<div id="materialsConfirmModal" class="lw-modal hidden" role="dialog" aria-modal="true" aria-labelledby="materialsConfirmTitle" aria-describedby="materialsConfirmMessage">
    <div class="lw-modal-box" style="max-width: 500px;">
        <div class="lw-modal-head">
            <div class="lw-modal-head-text">
                <h2 class="lw-modal-title" id="materialsConfirmTitle">Confirm action</h2>
                <div class="lw-modal-sub">Assessment Builder</div>
            </div>
            <button type="button" class="lw-modal-close" data-confirm-choice="cancel" aria-label="Cancel">&times;</button>
        </div>
        <div class="lw-modal-body">
            <p class="lw-confirm-copy" id="materialsConfirmMessage"></p>
            <div class="lw-confirm-warning" id="materialsConfirmWarning" hidden>This action cannot be undone.</div>
        </div>
        <div class="lw-modal-foot" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="lw-btn ghost" data-confirm-choice="cancel">Cancel</button>
            <button type="button" class="lw-btn danger" data-confirm-choice="confirm">Confirm</button>
        </div>
    </div>
</div>
