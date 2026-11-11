<div id="filePreviewModal" class="lw-modal hidden" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="filePreviewTitle" data-file-preview-modal>
    <div class="lw-modal-box" role="document">
        <div class="lw-modal-head">
            <div>
                <h3 id="filePreviewTitle">Submitted File Preview</h3>
                <p>Preview opens inline. Some Office files may need download to view.</p>
            </div>
            <div class="lw-modal-actions">
                <a id="filePreviewDownloadLink" href="#" class="lw-btn lw-btn-ghost lw-btn-sm">
                    <i class="bi bi-download"></i> Download
                </a>
                <a id="filePreviewOpenLink" href="#" target="_blank" rel="noopener" class="lw-btn lw-btn-ghost lw-btn-sm">
                    <i class="bi bi-box-arrow-up-right"></i> Open in new tab
                </a>
                <button type="button" data-file-preview-close class="lw-btn lw-btn-primary lw-btn-sm">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
        </div>
        <div class="lw-modal-body">
            <div class="lw-preview-unavailable" data-file-preview-error hidden>
                <i class="bi bi-file-earmark-x"></i>
                <h4>File unavailable</h4>
                <p>This submission file could not be found in storage.</p>
            </div>
            <iframe id="filePreviewFrame" class="lw-modal-frame" src="" title="Submission file preview"></iframe>
        </div>
    </div>
</div>
