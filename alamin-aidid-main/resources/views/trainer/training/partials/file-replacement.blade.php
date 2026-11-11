<div class="lw-file-replace">
    <button
        type="button"
        class="lw-inline-action js-file-replace-toggle"
        aria-expanded="false"
    >
        <i class="bi bi-arrow-repeat"></i>
        Replace file
    </button>

    <form
        method="POST"
        action="{{ route('trainer.courses.blocks.file.replace', [$courseId, $block->block_id]) }}"
        enctype="multipart/form-data"
        class="lw-file-replace-form js-ajax-file-replace hidden"
        data-module-id="{{ $block->module_id }}"
    >
        @csrf
        @method('PATCH')
        <label>
            <span>Choose replacement</span>
            <input
                type="file"
                name="material_file"
                required
                @if ($block->block_type === 'media')
                    accept="image/*,video/*,audio/*"
                @else
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.csv"
                @endif
            >
        </label>
        <div class="lw-file-replace-actions">
            <button type="button" class="lw-btn ghost sm js-file-replace-cancel">
                Cancel
            </button>
            <button type="submit" class="lw-btn primary sm">
                Replace
            </button>
        </div>
        <p class="lw-inline-error" aria-live="polite"></p>
    </form>
</div>
