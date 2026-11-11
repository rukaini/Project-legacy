@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

@vite(['resources/js/assessment-builder.js', 'resources/js/module-save-coordinator.js'])
<script>
    /* ============================================================ Shared state ============================================================ */
    let questionIndex = 0;
    const assessmentBuilderCache = {};
    const csrfToken = '{{ csrf_token() }}';
    const moduleOriginalStates = {};
    const dirtyModules = new Set();
    const editModalStates = {};
    const assessmentState = {
        type: 'quiz',
        mode: 'new',
        selectedAssessmentId: null,
        loadingAssessmentId: null,
        loadingVisible: false,
        dirty: false,
        hasLearnerRecords: false,
        questions: [],
        activeQuestionId: null,
        importState: 'guide',
        templateState: 'closed',
        requestController: null,
        requestToken: 0,
        templateBusyUntil: 0,
    };
    const assessmentCatalog = new Map();
    let lwAssessmentQuestionSortable = null;
    let lwLastQuestionAddAt = 0;
    let assessmentLoadingTimer = null;
    let lmsAuthoringCurrentUrl = window.location.href;
    const lwCourseId = parseInt(document.body.dataset.lwCourseId || '0', 10);
    const modalTriggers = {};
    const modulePanelAppendSlots = new WeakMap();

    /* ============================================================ Toast ============================================================ */
    function showToast(message, type) {
        if (typeof window.showAppToast !== 'function') return null;
        return window.showAppToast(type, message);
    }

    let materialsConfirmationResolver = null;
    let materialsConfirmationTrigger = null;

    function visibleAuthoringDialogs() {
        return Array.from(document.querySelectorAll('.lw-modal, .lw-editor')).filter(function (dialog) {
            return !dialog.classList.contains('hidden');
        });
    }

    function acquireAuthoringScrollLock() {
        document.documentElement.classList.add('lw-authoring-scroll-locked');
        document.body.classList.add('lw-authoring-scroll-locked');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
    }

    function releaseAuthoringScrollLock(force) {
        if (!force && visibleAuthoringDialogs().length > 0) return;
        document.documentElement.classList.remove('lw-authoring-scroll-locked');
        document.body.classList.remove('lw-authoring-scroll-locked');
        document.documentElement.style.removeProperty('overflow');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('position');
        document.body.style.removeProperty('top');
        document.body.style.removeProperty('width');
        document.body.style.removeProperty('padding-right');
    }

    function requestMaterialsConfirmation(options) {
        const modal = document.getElementById('materialsConfirmModal');
        if (!modal) return Promise.resolve(false);
        const title = document.getElementById('materialsConfirmTitle');
        const message = document.getElementById('materialsConfirmMessage');
        const warning = document.getElementById('materialsConfirmWarning');
        const confirmButton = modal.querySelector('[data-confirm-choice="confirm"]');
        if (title) title.textContent = options.title || 'Confirm action';
        if (message) message.textContent = options.message || '';
        if (warning) warning.hidden = !options.destructive;
        if (confirmButton) {
            confirmButton.innerHTML = options.confirmLabel || 'Confirm';
            confirmButton.classList.toggle('danger', Boolean(options.destructive));
        }
        materialsConfirmationTrigger = document.activeElement;
        modal.classList.remove('hidden');
        acquireAuthoringScrollLock();
        if (confirmButton) confirmButton.focus();

        return new Promise(function (resolve) {
            materialsConfirmationResolver = resolve;
        });
    }

    function settleMaterialsConfirmation(confirmed) {
        const modal = document.getElementById('materialsConfirmModal');
        if (modal) modal.classList.add('hidden');
        releaseAuthoringScrollLock(false);
        if (materialsConfirmationTrigger && typeof materialsConfirmationTrigger.focus === 'function') {
            materialsConfirmationTrigger.focus();
        }
        materialsConfirmationTrigger = null;
        const resolver = materialsConfirmationResolver;
        materialsConfirmationResolver = null;
        if (resolver) resolver(Boolean(confirmed));
    }

    function fetchJson(url, options) {
        return fetch(url, options).then(function (response) {
            return response.text().then(function (rawBody) {
                let body = {};
                try {
                    body = rawBody ? JSON.parse(rawBody) : {};
                } catch (error) {
                    body = { message: response.ok ? 'Unexpected response.' : 'The server returned an unexpected response.' };
                }
                return { ok: response.ok, status: response.status, body: body };
            });
        });
    }

    function setBusy(button, busyLabel) {
        if (!button) return function () {};
        const originalLabel = button.innerHTML;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + busyLabel;
        return function () {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.innerHTML = originalLabel;
        };
    }

    function setFormInlineError(form, message) {
        if (!form) return;
        let error = form.querySelector('.lw-inline-error');
        if (!error) {
            error = document.createElement('p');
            error.className = 'lw-inline-error';
            error.setAttribute('role', 'alert');
            const footer = form.querySelector('.lw-add-panel-foot');
            if (footer) footer.before(error); else form.appendChild(error);
        }
        error.textContent = message || '';
    }

    function openModuleModalFromKeyboard(event, element) {
        if (event.target !== element) return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openModuleModalFromButton(element);
        }
    }

    /* ============================================================ Add Module modal — dirty tracking ============================================================ */
    function isAddModuleId(id) { return id === 'addModuleModal'; }
    const addModuleState = { title: '' };

    function captureAddModuleState() {
        const modal = document.getElementById('addModuleModal');
        if (!modal) return;
        const titleInput = modal.querySelector('[name="module_title"]');
        addModuleState.title = titleInput ? titleInput.value : '';
        // Reset preview to clean state
        const fileInput = modal.querySelector('[name="cover_image_file"]');
        if (fileInput) fileInput.value = '';
        const wrap = document.getElementById('addModuleCoverPreview');
        if (wrap) wrap.classList.remove('show');
    }
    function isAddModuleDirty() {
        const modal = document.getElementById('addModuleModal');
        if (!modal) return false;
        const titleInput = modal.querySelector('[name="module_title"]');
        if (titleInput && titleInput.value !== addModuleState.title) return true;
        const fileInput = modal.querySelector('[name="cover_image_file"]');
        if (fileInput && fileInput.files && fileInput.files.length > 0) return true;
        return false;
    }
    function discardAddModuleChanges() {
        const modal = document.getElementById('addModuleModal');
        if (!modal) return;
        const titleInput = modal.querySelector('[name="module_title"]');
        if (titleInput) titleInput.value = addModuleState.title;
        const fileInput = modal.querySelector('[name="cover_image_file"]');
        if (fileInput) fileInput.value = '';
        const wrap = document.getElementById('addModuleCoverPreview');
        if (wrap) wrap.classList.remove('show');
    }
    function lwHandleAddModuleCoverPick(input) {
        lwPreviewModuleCover(input, input.dataset.previewId, input.dataset.previewImgId);
    }

    /* ============================================================ Edit settings modal ============================================================ */
    function isEditSettingsId(id) { return id.indexOf('moduleSettingsModal') === 0; }
    function editIdFromSettingsId(id) { return id.replace('moduleSettingsModal', ''); }
    function captureEditState(moduleId) {
        const modal = document.getElementById('moduleSettingsModal' + moduleId);
        if (!modal) return;
        const titleInput = modal.querySelector('input[name="module_title"]');
        editModalStates[moduleId] = { title: titleInput ? titleInput.value : '' };
        const fileInput = modal.querySelector('input[name="cover_image_file"]');
        if (fileInput) fileInput.value = '';
        const wrap = document.getElementById('newCoverPreview' + moduleId);
        if (wrap) wrap.classList.remove('show');
        lwCancelCoverReset(moduleId);
    }
    function isEditDirty(moduleId) {
        const modal = document.getElementById('moduleSettingsModal' + moduleId);
        if (!modal) return false;
        const state = editModalStates[moduleId];
        if (!state) return false;
        const titleInput = modal.querySelector('input[name="module_title"]');
        if (titleInput && titleInput.value !== state.title) return true;
        const fileInput = modal.querySelector('input[name="cover_image_file"]');
        if (fileInput && fileInput.files && fileInput.files.length > 0) return true;
        const flag = document.getElementById('deleteCoverFlag' + moduleId);
        if (flag && flag.value === '1') return true;
        return false;
    }
    function discardEditChanges(moduleId) {
        const modal = document.getElementById('moduleSettingsModal' + moduleId);
        if (!modal) return;
        const state = editModalStates[moduleId];
        const titleInput = modal.querySelector('input[name="module_title"]');
        if (titleInput && state) titleInput.value = state.title;
        const fileInput = modal.querySelector('input[name="cover_image_file"]');
        if (fileInput) fileInput.value = '';
        const wrap = document.getElementById('newCoverPreview' + moduleId);
        if (wrap) wrap.classList.remove('show');
        lwCancelCoverReset(moduleId);
    }

    /* ============================================================ Assessment dirty tracking (snapshot-based) ============================================================ */
    let lwAssessmentBaseline = '';
    function snapshotAssessmentState() {
        const form = document.getElementById('assessmentForm');
        if (!form) return '';
        const parts = [];
        const topFields = ['title', 'type', 'instruction_text', 'available_from', 'available_until', 'accepted_format', 'time_limit_minutes', 'weight_percent', 'max_attempts'];
        topFields.forEach(function (n) {
            const el = form.querySelector('[name="' + n + '"]');
            parts.push(n + ':' + (el ? el.value : ''));
        });
        const cards = document.querySelectorAll('#questionList .lw-assess-question');
        cards.forEach(function (card, i) {
            const qType = card.dataset.questionType || '';
            const idx = card.dataset.questionIndex;
            parts.push('Q' + i + '.t:' + qType);
            ['question_text', 'marks'].forEach(function (key) {
                const el = card.querySelector('[name="questions[' + idx + '][' + key + ']"]');
                parts.push('Q' + i + '.' + key + ':' + (el ? el.value : ''));
            });
            const file = card.querySelector('input[type="file"]');
            parts.push('Q' + i + '.file:' + (file && file.files && file.files.length > 0 ? '1' : '0'));
            const removeImage = card.querySelector('[name$="[remove_question_image]"]');
            parts.push('Q' + i + '.removeImage:' + (removeImage ? removeImage.value : '0'));
            if (qType === 'mcq' || qType === 'checkbox') {
                card.querySelectorAll('.lw-choice-row').forEach(function (row, ri) {
                    const t = row.querySelector('.choice-text');
                    const c = row.querySelector('.choice-correct');
                    parts.push('Q' + i + '.c' + ri + ':' + (t ? t.value : '') + (c && c.checked ? 'y' : 'n'));
                });
            }
            if (qType === 'true_false' || qType === 'short_answer') {
                const a = card.querySelector('[name="questions[' + idx + '][correct_answer]"]');
                parts.push('Q' + i + '.ans:' + (a ? a.value : ''));
            }
            if (qType === 'essay') {
                const r = card.querySelector('[name="questions[' + idx + '][grading_rubric]"]');
                parts.push('Q' + i + '.rub:' + (r ? r.value : ''));
            }
        });
        return parts.join('||');
    }
    function captureAssessmentState() { lwAssessmentBaseline = snapshotAssessmentState(); assessmentState.dirty = false; }
    function isAssessmentDirty() {
        assessmentState.dirty = snapshotAssessmentState() !== lwAssessmentBaseline;
        return assessmentState.dirty;
    }
    function setupAssessmentDirtyTracking() { /* no-op: now using snapshot comparison */ }

    /* ============================================================ Module editor helpers (snapshot-based) ============================================================ */
    function isModuleEditorId(id) { return id.indexOf('moduleModal') === 0 && id.indexOf('moduleSettingsModal') !== 0; }
    function moduleIdFromEditorId(id) { return id.replace('moduleModal', ''); }
    function snapshotModuleState(moduleId) {
        const blockList = document.getElementById('blockList' + moduleId);
        if (!blockList) return '';
        const formId = 'saveModuleForm' + moduleId;
        const parts = [];
        const titleInput = document.querySelector('[form="' + formId + '"][name="module_title"]');
        parts.push('title:' + (titleInput ? titleInput.value : ''));
        blockList.querySelectorAll('.lw-block').forEach(function (block, i) {
            const bid = block.dataset.blockId || 'NEW';
            const flags = (block.dataset.markedDelete === '1' ? 'D' : '') + (block.dataset.pending === '1' ? 'P' : '');
            parts.push('B' + i + ':' + bid + flags);
            block.querySelectorAll('input, textarea, select').forEach(function (input) {
                if (input.name && input.name !== '_token' && input.name !== '_method' && input.type !== 'file') {
                    parts.push(input.name + '=' + (input.value || ''));
                }
            });
        });
        return parts.join('||');
    }
    function captureOriginalState(moduleId) {
        const blockList = document.getElementById('blockList' + moduleId);
        if (!blockList) return;
        const titleInput = document.querySelector('[form="saveModuleForm' + moduleId + '"][name="module_title"]');
        const state = { moduleTitle: titleInput ? titleInput.value : '', blockOrder: [], inputValues: {}, snapshot: '' };
        blockList.querySelectorAll('.lw-block[data-block-id]').forEach(function (b) { state.blockOrder.push(b.dataset.blockId); });
        blockList.querySelectorAll('input, textarea, select').forEach(function (input) { if (input.name) state.inputValues[input.name] = input.value; });
        state.snapshot = snapshotModuleState(moduleId);
        moduleOriginalStates[moduleId] = state;
        dirtyModules.delete(String(moduleId));
    }
    function setupDirtyTracking(moduleId) { /* no-op: now using snapshot comparison */ }
    function markModuleDirty(moduleId) { /* no-op kept for legacy callers */ }
    function isModuleDirty(moduleId) {
        const state = moduleOriginalStates[moduleId];
        if (!state) return false;
        return snapshotModuleState(moduleId) !== state.snapshot;
    }
    function refreshModuleView(moduleId) {
        const blockList = document.getElementById('blockList' + moduleId);
        if (!blockList) return;
        const workspaceState = window.deriveModuleEmptyState(
            blockList.querySelectorAll('.lw-block'),
            document.querySelectorAll('#addPanels' + moduleId + ' .lw-add-panel:not(.hidden)')
        );
        const total = workspaceState.realBlockCount;
        const emptyState = document.getElementById('emptyState' + moduleId);
        if (emptyState) emptyState.style.display = workspaceState.emptyVisible ? '' : 'none';
        const subEl = document.querySelector('[data-block-count-text-for="' + moduleId + '"]');
        if (subEl) subEl.textContent = 'Module · ' + total + ' block' + (total === 1 ? '' : 's');
        updateModuleGridCount(moduleId, total);
        const deleteButton = document.querySelector('[data-delete-module-url][data-module-id="' + moduleId + '"]');
        if (deleteButton) deleteButton.dataset.blockCount = String(total);
        refreshGlobalBlockTotalFromDom();
        recomputeAllAssessmentPickerStates();
    }
    function updateModuleGridCount(moduleId, count) {
        document.querySelectorAll('[data-module-grid-count="' + moduleId + '"]').forEach(function (el) { el.textContent = count; });
        const metrics = document.querySelector('[data-module-metrics="' + moduleId + '"]');
        if (!metrics) return;
        metrics.innerHTML =
            '<i class="bi bi-stack"></i>' +
            '<span data-module-grid-count="' + moduleId + '">' + count + '</span> ' +
            'block' + (count === 1 ? '' : 's');
    }

    function refreshGlobalBlockTotalFromDom() {
        const total = document.querySelector('[data-block-total]');
        if (!total) return;
        const count = Array.from(document.querySelectorAll('.lw-block-list'))
            .reduce(function (sum, blockList) {
                return sum + window.countStagedVisibleBlocks(
                    blockList.querySelectorAll('.lw-block')
                );
            }, 0);
        total.textContent = String(count);
    }

    function adjustGlobalBlockTotal() {
        refreshGlobalBlockTotalFromDom();
    }

    function stagedAssessmentBlockDescriptors() {
        return Array.from(document.querySelectorAll('.lw-block[data-assessment-id]'))
            .map(function (block) {
                const blockList = block.closest('.lw-block-list');
                const moduleId = blockList ? String(blockList.dataset.moduleId || '') : '';
                const titleInput = moduleId
                    ? document.querySelector('[form="saveModuleForm' + moduleId + '"][name="module_title"]')
                    : null;
                const titleFallback = moduleId
                    ? document.querySelector('[data-module-grid-title="' + moduleId + '"]')
                    : null;

                return {
                    assessmentId: String(block.dataset.assessmentId || ''),
                    moduleId: moduleId,
                    moduleTitle: (titleInput ? titleInput.value.trim() : '')
                        || (titleFallback ? titleFallback.textContent.trim() : ''),
                    markedDelete: block.dataset.markedDelete || '',
                };
            });
    }

    function recomputeAssessmentPickerState(assessmentId) {
        const attachment = window.deriveStagedAssessmentAttachment(
            stagedAssessmentBlockDescriptors(),
            assessmentId
        );
        setAssessmentEmbeddedState(assessmentId, attachment.embedded, attachment);
    }

    function recomputeAllAssessmentPickerStates() {
        const assessmentIds = new Set(
            Array.from(document.querySelectorAll('[data-assessment-option]'))
                .map(function (option) { return String(option.dataset.assessmentOption || ''); })
                .filter(Boolean)
        );
        assessmentIds.forEach(recomputeAssessmentPickerState);
    }

    function setAssessmentEmbeddedState(assessmentId, embedded, attachment) {
        if (!assessmentId) return;
        document.querySelectorAll(
            '[data-assessment-option="' + assessmentId + '"]'
        ).forEach(function (input) {
            const item = input.closest('[data-assessment-picker-item]');
            input.disabled = embedded;
            if (embedded) input.checked = false;
            if (!item) return;
            item.classList.toggle('is-attached', embedded);
            const label = item.querySelector('[data-assessment-attachment-label]');
            if (!label) return;
            if (!embedded) {
                label.textContent = 'Not attached';
            } else {
                const currentModuleId = String(item.dataset.currentModuleId || '');
                const attachedModuleId = String(attachment?.moduleId || '');
                label.textContent = attachedModuleId && attachedModuleId === currentModuleId
                    ? 'Already in this module'
                    : 'Attached to: ' + (attachment?.moduleTitle || 'another module');
            }
            const form = item.closest('form');
            if (form) {
                const selectable = Array.from(form.querySelectorAll('[data-assessment-option]'))
                    .some(function (candidate) { return !candidate.disabled; });
                const submit = form.querySelector('button[type="submit"]');
                const emptyHint = form.querySelector('[data-attachment-empty-hint]');
                if (submit) submit.disabled = !selectable;
                if (emptyHint) emptyHint.hidden = selectable;
            }
        });
    }

    function handleDeletedBlockResult(result) {
        adjustGlobalBlockTotal(-1);
        if (result && result.body && result.body.assessment_id) {
            recomputeAssessmentPickerState(result.body.assessment_id);
        }
    }

    /* ============================================================ Open / Close ============================================================ */
    function openModuleModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modalTriggers[id] = document.activeElement;
        if (isModuleEditorId(id)) {
            const moduleId = moduleIdFromEditorId(id);
            captureOriginalState(moduleId);
            setupDirtyTracking(moduleId);
            refreshModuleView(moduleId);
        } else if (isEditSettingsId(id)) {
            captureEditState(editIdFromSettingsId(id));
        } else if (isAddModuleId(id)) {
            captureAddModuleState();
        }
        modal.classList.remove('hidden');
        acquireAuthoringScrollLock();
        setTimeout(function () {
            const focusTarget = isModuleEditorId(id)
                ? modal
                : modal.querySelector(
                    'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])'
                );
            if (focusTarget) focusTarget.focus({ preventScroll: true });
        }, 30);
    }
    function openModuleModalFromButton(button) { openModuleModal(button.dataset.modalId); }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        if (isModuleEditorId(id)) {
            const moduleId = moduleIdFromEditorId(id);
            if (isModuleDirty(moduleId)) {
                requestMaterialsConfirmation({
                    title: 'Discard unsaved changes?',
                    message: 'Your module title, block edits, and unsaved ordering changes will be discarded.',
                    confirmLabel: 'Discard Changes',
                    destructive: true
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    discardModuleChanges(moduleId).then(function (discarded) {
                        if (discarded) finalizeClose(id, moduleId);
                    });
                });
                return;
            }
            finalizeClose(id, moduleId); return;
        }
        if (isEditSettingsId(id)) {
            const moduleId = editIdFromSettingsId(id);
            if (isEditDirty(moduleId)) {
                requestMaterialsConfirmation({
                    title: 'Discard unsaved changes?',
                    message: 'Your module settings changes will be discarded.',
                    confirmLabel: 'Discard Changes',
                    destructive: true
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    discardEditChanges(moduleId);
                    finalizeClose(id, null);
                });
                return;
            }
            finalizeClose(id, null); return;
        }
        if (isAddModuleId(id)) {
            if (isAddModuleDirty()) {
                requestMaterialsConfirmation({
                    title: 'Discard new module?',
                    message: 'The unsaved module title and selected cover will be discarded.',
                    confirmLabel: 'Discard Changes',
                    destructive: true
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    discardAddModuleChanges();
                    finalizeClose(id, null);
                });
                return;
            }
            finalizeClose(id, null); return;
        }
        finalizeClose(id, null);
    }
    function closeModalFromButton(button) { closeModal(button.dataset.modalId); }

    function finalizeClose(id, moduleId) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('hidden');
        releaseAuthoringScrollLock(false);
        const trigger = modalTriggers[id];
        if (trigger && typeof trigger.focus === 'function') {
            trigger.focus();
        }
        delete modalTriggers[id];
        if (moduleId) {
            dirtyModules.delete(String(moduleId));
            document.querySelectorAll('#addPanels' + moduleId + ' .lw-add-panel').forEach(function (panel) {
                panel.classList.add('hidden');
                const form = panel.querySelector('form');
                if (form) {
                    const blockList = document.getElementById('blockList' + moduleId);
                    const slot = modulePanelAppendSlots.get(form);
                    if (blockList && slot) window.releaseModuleBlockAppendSlot(blockList, slot);
                    modulePanelAppendSlots.delete(form);
                    form.reset();
                }
            });
            document.querySelectorAll('.lw-tool-btn[data-module-id="' + moduleId + '"].active').forEach(function (btn) { btn.classList.remove('active'); });
        }
    }

    function discardModuleChanges(moduleId) {
        const blockList = document.getElementById('blockList' + moduleId);
        if (!blockList) return Promise.resolve(true);
        blockList.querySelectorAll('.lw-block:not([data-block-id])').forEach(function (b) { b.remove(); });
        const pendingBlocks = Array.from(blockList.querySelectorAll('.lw-block[data-pending="1"]'));
        const deletePromises = pendingBlocks.map(function (block) {
            const deleteBtn = block.querySelector('.js-block-delete');
            let url = deleteBtn ? deleteBtn.dataset.deleteUrl : null;
            if (!url && block.dataset.blockId) {
                url = '/trainer/courses/' + lwCourseId + '/blocks/' + block.dataset.blockId;
            }
            if (!url) {
                block.remove();
                return Promise.resolve({ ok: true });
            }
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('_method', 'DELETE');
            return fetchJson(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(function (result) {
                if (!result.ok || !result.body.success) return result;
                block.remove();
                handleDeletedBlockResult(result);
                return result;
            }).catch(function () {
                return { ok: false, body: { message: 'Network error.' } };
            });
        });
        return Promise.all(deletePromises).then(function (results) {
            const failedDelete = results.find(function (result) {
                return result && result.ok === false;
            });
            if (failedDelete) {
                showToast(
                    (failedDelete.body && failedDelete.body.message)
                        || 'A newly added block could not be discarded. The editor remains open.',
                    'error'
                );
                return false;
            }
            blockList.querySelectorAll('.lw-block[data-marked-delete="1"]').forEach(function (block) { clearMarkDeleteStyling(block); });
            const state = moduleOriginalStates[moduleId];
            if (state) {
                blockList.querySelectorAll('input, textarea, select').forEach(function (input) {
                    if (input.name && state.inputValues[input.name] !== undefined) input.value = state.inputValues[input.name];
                });
                const titleInput = document.querySelector('[form="saveModuleForm' + moduleId + '"][name="module_title"]');
                if (titleInput) titleInput.value = state.moduleTitle;
                state.blockOrder.forEach(function (blockId) {
                    const block = blockList.querySelector('.lw-block[data-block-id="' + blockId + '"]');
                    if (block) blockList.appendChild(block);
                });
            }
            refreshModuleView(moduleId);
            return true;
        });
    }

    /* ============================================================ Panels / save / sortable / delete-block / add-block / inline ============================================================ */
    function togglePanelFromButton(button) { togglePanel(button.dataset.moduleId, button.dataset.panelType); }
    function togglePanel(moduleId, type) {
        const panel = document.getElementById(type + 'Panel' + moduleId);
        if (!panel) return;
        const toolbarBtn = document.querySelector('.lw-tool-btn[data-module-id="' + moduleId + '"][data-panel-type="' + type + '"]');
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            if (toolbarBtn) toolbarBtn.classList.add('active');
            const form = panel.querySelector('form.js-ajax-block-add');
            const blockList = document.getElementById('blockList' + moduleId);
            if (form && blockList && !modulePanelAppendSlots.has(form)) {
                modulePanelAppendSlots.set(
                    form,
                    window.reserveModuleBlockAppendSlot(blockList, document.createElement('span'))
                );
            }
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            panel.classList.add('hidden');
            if (toolbarBtn) toolbarBtn.classList.remove('active');
            const form = panel.querySelector('form');
            if (form) {
                const blockList = document.getElementById('blockList' + moduleId);
                const slot = modulePanelAppendSlots.get(form);
                if (blockList && slot) window.releaseModuleBlockAppendSlot(blockList, slot);
                modulePanelAppendSlots.delete(form);
                form.reset();
            }
        }
        refreshModuleView(moduleId);
    }

    function validateModuleSave(form, moduleId) {
        const titleInput = document.querySelector('[form="' + form.id + '"][name="module_title"]');
        const titleError = document.querySelector('[data-module-title-error="' + moduleId + '"]');
        if (titleError) titleError.textContent = '';
        if (!titleInput || !titleInput.value.trim()) {
            if (titleError) titleError.textContent = 'Module title is required.';
            showToast('Module title is required.', 'error');
            if (titleInput) titleInput.focus();
            return false;
        }
        const openPanels = document.querySelectorAll('#addPanels' + moduleId + ' .lw-add-panel:not(.hidden)');
        if (openPanels.length > 0) {
            showToast('Please finish or close the open block panel before saving.', 'error');
            openPanels[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        renumberModuleBlockInputs(form);
        const requiredFields = document.querySelectorAll('[form="' + form.id + '"][data-module-required="1"]');
        for (const field of requiredFields) {
            const block = field.closest('.lw-block');
            if (block && block.dataset.markedDelete === '1') continue;
            if (!field.value.trim()) {
                showToast(field.dataset.message || 'Please fill all block fields before saving.', 'error');
                field.focus(); return false;
            }
        }
        return true;
    }

    function renumberModuleBlockInputs(form) {
        const blockList = document.querySelector('[data-form-id="' + form.id + '"]');
        if (!blockList) return;
        const items = blockList.querySelectorAll('.lw-block');
        items.forEach(function (item, newIndex) {
            item.querySelectorAll('[name^="blocks["]').forEach(function (field) {
                field.name = field.name.replace(/blocks\[\d+\]/, 'blocks[' + newIndex + ']');
            });
        });
    }

    function attachAjaxModuleCreate() {
        const form = document.querySelector('form.js-ajax-module-create');
        if (!form || form.dataset.ajaxReady === '1') return;
        form.dataset.ajaxReady = '1';
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const titleInput = form.querySelector('[name="module_title"]');
            if (!titleInput || !titleInput.value.trim()) {
                showToast('Module title is required.', 'error');
                if (titleInput) titleInput.focus();
                return;
            }
            const submitButton = form.querySelector('button[type="submit"]');
            const restoreButton = setBusy(submitButton, 'Creating…');
            fetchJson(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (result) {
                if (!result.ok || !result.body.success) {
                    showToast(result.body.message || 'The module could not be created.', 'error');
                    return;
                }
                return fetch(result.body.refresh_url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).then(function (response) {
                    if (!response.ok) throw new Error('Module workspace refresh failed.');
                    return response.text();
                }).then(function (html) {
                    const parsed = new DOMParser().parseFromString(html, 'text/html');
                    const moduleId = String(result.body.module_id);
                    const currentGrid = document.querySelector('[data-module-grid]');
                    const newCard = parsed.querySelector('[data-module-card="' + moduleId + '"]');
                    const addCard = currentGrid ? currentGrid.querySelector('.lw-module-add') : null;
                    if (!currentGrid || !newCard || !addCard) throw new Error('New module markup was unavailable.');
                    currentGrid.insertBefore(document.importNode(newCard, true), addCard);
                    ['saveModuleForm' + moduleId, 'moduleSettingsModal' + moduleId, 'moduleModal' + moduleId]
                        .forEach(function (id) {
                            const source = parsed.getElementById(id);
                            const anchor = document.getElementById('materialsConfirmModal');
                            if (source && anchor) anchor.before(document.importNode(source, true));
                        });
                    document.querySelectorAll('[data-bg]').forEach(function (element) {
                        element.style.background = element.dataset.bg;
                    });
                    initializeSortableBlockLists();
                    initializeSortableModuleGrid();
                    initializeAccessibleBlocks();
                    attachAjaxModuleSave();
                    attachAjaxModuleSettings();
                    attachAjaxBlockAdd();
                    attachFileReplacement();
                    attachDialogBackdropHandlers();
                    form.reset();
                    discardAddModuleChanges();
                    finalizeClose(form.dataset.modalId, null);
                    showToast(result.body.message || 'Module created successfully.', 'success');
                });
            }).catch(function () {
                showToast('The module could not be created or refreshed. Please try again.', 'error');
            }).finally(restoreButton);
        });
    }

    function attachAjaxModuleSettings() {
        document.querySelectorAll('form.js-ajax-module-settings').forEach(function (form) {
            if (form.dataset.ajaxReady === '1') return;
            form.dataset.ajaxReady = '1';
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const moduleId = form.dataset.moduleId;
                const submitButton = form.querySelector('button[type="submit"]');
                const restoreButton = setBusy(submitButton, 'Saving…');
                fetchJson(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                }).then(function (result) {
                    if (!result.ok || !result.body.success) {
                        showToast(result.body.message || 'The module could not be updated.', 'error');
                        return;
                    }

                    const data = result.body;
                    document.querySelectorAll('[data-module-grid-title="' + moduleId + '"]').forEach(function (element) {
                        element.textContent = data.module_title;
                    });
                    const editorTitle = document.querySelector(
                        '[form="saveModuleForm' + moduleId + '"][name="module_title"]'
                    );
                    if (editorTitle) editorTitle.value = data.module_title;
                    const card = document.querySelector('[data-module-card="' + moduleId + '"]');
                    if (card) card.setAttribute('aria-label', 'Edit module ' + data.module_title);

                    const coverImage = document.querySelector('[data-module-cover-image="' + moduleId + '"]');
                    const defaultCover = document.querySelector('[data-module-cover-default="' + moduleId + '"]');
                    if (coverImage && data.cover_url) {
                        coverImage.src = data.cover_url;
                        coverImage.alt = data.module_title + ' cover';
                        coverImage.hidden = !data.has_custom_cover;
                    }
                    if (defaultCover) defaultCover.hidden = Boolean(data.has_custom_cover);

                    const resetField = document.getElementById('coverResetField' + moduleId);
                    if (resetField) resetField.hidden = !data.has_custom_cover;
                    form.reset();
                    const titleInput = form.querySelector('[name="module_title"]');
                    if (titleInput) titleInput.value = data.module_title;
                    const deleteFlag = form.querySelector('[name="delete_cover_image"]');
                    if (deleteFlag) deleteFlag.value = '0';
                    captureEditState(moduleId);
                    finalizeClose(form.dataset.modalId, null);
                    showToast(data.message || 'Module updated.', 'success');
                }).catch(function () {
                    showToast('Network error. Your module settings were not changed.', 'error');
                }).finally(restoreButton);
            });
        });
    }

    function deleteModuleFromButton(button) {
        const blockCount = Number(button.dataset.blockCount || 0);
        const moduleTitle = button.dataset.moduleTitle || 'This module';
        const detail = blockCount > 0
            ? ' contains ' + blockCount + ' content block' + (blockCount === 1 ? '' : 's') + '.\nDeleting it will also remove those content blocks. Linked assessments and learner records are preserved.'
            : ' is empty and will be permanently removed.';
        requestMaterialsConfirmation({
            title: 'Delete module?',
            message: '“' + moduleTitle + '”' + detail,
            confirmLabel: 'Delete Module',
            destructive: true
        }).then(function (confirmed) {
            if (confirmed) performDeleteModule(button);
        });
    }

    function performDeleteModule(button) {
        const moduleId = button.dataset.moduleId;
        const restoreButton = setBusy(button, 'Deleting…');
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'DELETE');
        fetchJson(button.dataset.deleteModuleUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        }).then(function (result) {
            if (!result.ok || !result.body.success) {
                showToast(result.body.message || 'The module could not be deleted.', 'error');
                return;
            }

            finalizeClose('moduleSettingsModal' + moduleId, null);
            const card = document.querySelector('[data-module-card="' + moduleId + '"]');
            const settingsModal = document.getElementById('moduleSettingsModal' + moduleId);
            const editorModal = document.getElementById('moduleModal' + moduleId);
            const saveForm = document.getElementById('saveModuleForm' + moduleId);
            if (card) card.remove();
            if (settingsModal) settingsModal.remove();
            if (editorModal) editorModal.remove();
            if (saveForm) saveForm.remove();
            const total = document.querySelector('[data-module-total]');
            if (total) total.textContent = Math.max(0, Number(total.textContent || 0) - 1);
            showToast(result.body.message || 'Module deleted.', 'success');
        }).catch(function () {
            showToast('Network error. The module was not deleted.', 'error');
        }).finally(restoreButton);
    }

    function initializeSortableBlockLists() {
        document.querySelectorAll('.lw-block-list').forEach(function (blockList) {
            if (typeof Sortable === 'undefined' || blockList.dataset.sortableReady === '1') return;
            const mid = blockList.dataset.moduleId;
            new Sortable(blockList, { animation: 150, handle: '.lw-block-handle', draggable: '.lw-block', ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen', onUpdate: function () { markModuleDirty(mid); } });
            blockList.dataset.sortableReady = '1';
        });
    }

    function initializeSortableModuleGrid() {
        const grid = document.querySelector('[data-module-grid]');
        if (!grid || typeof Sortable === 'undefined' || grid.dataset.sortableReady === '1') return;
        let originalOrder = [];
        new Sortable(grid, {
            animation: 150,
            handle: '.lw-module-drag',
            draggable: '.lw-module-card',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onStart: function () {
                originalOrder = Array.from(grid.querySelectorAll('[data-module-card]'))
                    .map(function (card) { return card.dataset.moduleCard; });
            },
            onEnd: function () {
                const moduleIds = Array.from(grid.querySelectorAll('[data-module-card]'))
                    .map(function (card) { return Number(card.dataset.moduleCard); });
                fetchJson(grid.dataset.reorderUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-HTTP-Method-Override': 'PATCH',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ module_ids: moduleIds })
                }).then(function (result) {
                    if (!result.ok || !result.body.success) {
                        throw new Error(result.body.message || 'Module order could not be saved.');
                    }
                    showToast(result.body.message || 'Module order saved.', 'success');
                }).catch(function (error) {
                    originalOrder.forEach(function (moduleId) {
                        const card = grid.querySelector('[data-module-card="' + moduleId + '"]');
                        const addCard = grid.querySelector('.lw-module-add');
                        if (card && addCard) grid.insertBefore(card, addCard);
                    });
                    showToast(error.message || 'Module order could not be saved.', 'error');
                });
            }
        });
        grid.dataset.sortableReady = '1';
    }

    function addEditableBlockFromButton(button) {
        const now = performance.now();
        const previousAdd = Number(button.dataset.lastAddAt || 0);
        if (previousAdd > 0 && now - previousAdd < 400) return;
        button.dataset.lastAddAt = String(now);
        const blockList = document.getElementById(button.dataset.blockListId);
        const type = button.dataset.blockType;
        const formId = blockList.dataset.formId;
        const moduleId = button.dataset.moduleId;
        const index = parseInt(blockList.dataset.nextIndex, 10);
        blockList.dataset.nextIndex = index + 1;
        let wrapper;
        if (type === 'title') wrapper = buildTitleBlockLw(formId, index);
        else if (type === 'text') wrapper = buildTextBlockLw(formId, index);
        else return;
        blockList.appendChild(wrapper);
        refreshModuleView(moduleId); initializeAccessibleBlocks(); markModuleDirty(moduleId);
        const focusTarget = wrapper.querySelector('input[type="text"], textarea');
        if (focusTarget) setTimeout(function () { focusTarget.focus(); }, 50);
    }
    function buildTitleBlockLw(formId, index) {
        const w = document.createElement('div');
        w.className = 'lw-block lw-block--title';
        w.dataset.blockType = 'title';
        w.innerHTML =
            '<div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>' +
            '<div class="lw-block-type-badge title"><i class="bi bi-type-h1"></i> Title</div>' +
            '<input type="hidden" form="' + formId + '" name="blocks[' + index + '][block_type]" value="title">' +
            '<input type="hidden" form="' + formId + '" name="blocks[' + index + '][block_content]" value="">' +
            '<input type="text" form="' + formId + '" name="blocks[' + index + '][block_title]" placeholder="Type a title…" required data-module-required="1" data-message="Please fill the title block or remove it before saving." class="lw-block-title-input">' +
            '<button type="button" class="lw-block-delete js-block-delete-local" title="Remove block"><i class="bi bi-x-lg"></i></button>';
        return w;
    }
    function buildTextBlockLw(formId, index) {
        const w = document.createElement('div');
        w.className = 'lw-block';
        w.dataset.blockType = 'text';
        w.innerHTML =
            '<div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>' +
            '<div class="lw-block-type-badge text"><i class="bi bi-text-paragraph"></i> Text</div>' +
            '<input type="hidden" form="' + formId + '" name="blocks[' + index + '][block_type]" value="text">' +
            '<input type="hidden" form="' + formId + '" name="blocks[' + index + '][block_title]" value="">' +
            '<textarea form="' + formId + '" name="blocks[' + index + '][block_content]" rows="3" required data-module-required="1" data-message="Please fill the text block or remove it before saving." placeholder="Write text content…"></textarea>' +
            '<button type="button" class="lw-block-delete js-block-delete-local" title="Remove block"><i class="bi bi-x-lg"></i></button>';
        return w;
    }

    function attachAjaxBlockDelete() {
        document.addEventListener('click', function (e) {
            const serverBtn = e.target.closest('.js-block-delete');
            if (serverBtn) { e.preventDefault(); handleMarkForDelete(serverBtn); return; }
            const localBtn = e.target.closest('.js-block-delete-local');
            if (localBtn) {
                e.preventDefault();
                const block = localBtn.closest('.lw-block');
                const blockList = block.closest('.lw-block-list');
                const moduleId = blockList ? blockList.dataset.moduleId : null;
                block.remove();
                if (moduleId) { refreshModuleView(moduleId); markModuleDirty(moduleId); }
            }
        });
    }
    function handleMarkForDelete(button) {
        requestMaterialsConfirmation({
            title: 'Delete block?',
            message: 'This block will be removed when you click Save Module. Close the editor without saving to keep it.',
            confirmLabel: 'Delete Block',
            destructive: true
        }).then(function (confirmed) {
            if (confirmed) markBlockForDelete(button);
        });
    }
    function markBlockForDelete(button) {
        const block = button.closest('.lw-block');
        if (!block) return;
        const blockList = block.closest('.lw-block-list');
        const moduleId = blockList ? blockList.dataset.moduleId : null;
        block.dataset.markedDelete = '1';
        block.style.display = 'none';
        if (moduleId) { refreshModuleView(moduleId); markModuleDirty(moduleId); }
    }
    function clearMarkDeleteStyling(block) { delete block.dataset.markedDelete; block.style.display = ''; }

    function attachAjaxBlockAdd() {
        document.querySelectorAll('form.js-ajax-block-add').forEach(function (form) {
            if (form.dataset.ajaxReady === '1') return;
            form.dataset.ajaxReady = '1';
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const moduleId = form.dataset.moduleId;
                const panelType = form.dataset.panelType;
                const submitBtn = form.querySelector('button[type="submit"]');
                const blockList = document.getElementById('blockList' + moduleId);
                const appendSlot = blockList
                    ? (modulePanelAppendSlots.get(form) || window.reserveModuleBlockAppendSlot(
                        blockList,
                        document.createElement('span')
                    ))
                    : null;
                if (appendSlot) modulePanelAppendSlots.set(form, appendSlot);
                const selectedAssessment = form.querySelector('[data-assessment-option]:checked');
                const stagedRemovedAssessment = selectedAssessment && blockList
                    ? blockList.querySelector(
                        '.lw-block[data-assessment-id="' + selectedAssessment.value + '"][data-marked-delete="1"]'
                    )
                    : null;
                if (stagedRemovedAssessment) {
                    clearMarkDeleteStyling(stagedRemovedAssessment);
                    if (appendSlot) {
                        window.commitModuleBlockAppendSlot(
                            blockList,
                            appendSlot,
                            stagedRemovedAssessment
                        );
                        modulePanelAppendSlots.delete(form);
                    } else {
                        blockList.appendChild(stagedRemovedAssessment);
                    }
                    form.reset();
                    refreshModuleView(moduleId);
                    togglePanel(moduleId, panelType);
                    showToast('Assessment restored to this module.', 'success');
                    return;
                }
                let origLabel = '';
                setFormInlineError(form, '');
                if (submitBtn) { origLabel = submitBtn.innerHTML; submitBtn.disabled = true; submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Adding…'; }
                const formData = new FormData(form);
                if (blockList && appendSlot) {
                    formData.set(
                        'block_order',
                        String(window.moduleBlockAppendPosition(blockList, appendSlot))
                    );
                }
                fetchJson(form.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (res) {
                    if (res.ok && res.body && res.body.success && res.body.block_html) {
                        if (blockList) {
                            const wrap = document.createElement('div');
                            wrap.innerHTML = res.body.block_html.trim();
                            const newBlock = wrap.firstElementChild;
                            if (newBlock) {
                                newBlock.setAttribute('data-pending', '1');
                                newBlock.setAttribute('data-counted-global', '1');
                                window.commitModuleBlockAppendSlot(
                                    blockList,
                                    appendSlot,
                                    newBlock
                                );
                                modulePanelAppendSlots.delete(form);
                                initializeAccessibleBlocks();
                                refreshModuleView(moduleId);
                                adjustGlobalBlockTotal(1);
                                if (res.body.assessment_id) {
                                    recomputeAssessmentPickerState(res.body.assessment_id);
                                }
                                markModuleDirty(moduleId);
                            }
                        }
                        togglePanel(moduleId, panelType);
                        showToast(res.body.message || 'Block added.', 'success');
                    } else {
                        const message = (res.body && res.body.message) || 'Could not add block.';
                        setFormInlineError(form, message);
                        showToast(message, 'error');
                    }
                })
                .catch(function (err) {
                    const message = 'Network error: ' + (err.message || 'unable to add block');
                    setFormInlineError(form, message);
                    showToast(message, 'error');
                })
                .finally(function () {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origLabel || 'Add to Module'; }
                });
            });
        });
    }

    function initializeAccessibleBlocks() {
        document.querySelectorAll('.lw-block-list').forEach(function (blockList) {
            blockList.querySelectorAll('.lw-block').forEach(function (block, index) {
                block.tabIndex = 0;
                block.setAttribute('role', 'group');
                block.setAttribute(
                    'aria-label',
                    'Content block ' + (index + 1) + '. Use Alt plus Up or Down Arrow to reorder.'
                );
            });
        });
    }

    function attachKeyboardBlockOrdering() {
        document.addEventListener('keydown', function (event) {
            const block = event.target.closest('.lw-block');
            if (!block || !event.altKey || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
            const list = block.closest('.lw-block-list');
            if (!list) return;
            const sibling = event.key === 'ArrowUp'
                ? block.previousElementSibling
                : block.nextElementSibling;
            if (!sibling) return;
            event.preventDefault();
            if (event.key === 'ArrowUp') list.insertBefore(block, sibling);
            else list.insertBefore(sibling, block);
            const form = document.getElementById(list.dataset.formId);
            if (form) renumberModuleBlockInputs(form);
            initializeAccessibleBlocks();
            block.focus();
            showToast('Block order updated. Save the module to keep this order.', 'success');
        });
    }

    function attachFileReplacement() {
        document.addEventListener('click', function (event) {
            const toggle = event.target.closest('.js-file-replace-toggle');
            if (toggle) {
                const form = toggle.parentElement.querySelector('.js-ajax-file-replace');
                if (!form) return;
                const willOpen = form.classList.contains('hidden');
                form.classList.toggle('hidden', !willOpen);
                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (willOpen) {
                    const input = form.querySelector('input[type="file"]');
                    if (input) input.focus();
                }
                return;
            }

            const cancel = event.target.closest('.js-file-replace-cancel');
            if (cancel) {
                const form = cancel.closest('.js-ajax-file-replace');
                const toggleButton = form.parentElement.querySelector('.js-file-replace-toggle');
                form.reset();
                form.classList.add('hidden');
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', 'false');
                    toggleButton.focus();
                }
            }
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form.js-ajax-file-replace');
            if (!form) return;
            event.preventDefault();
            const block = form.closest('.lw-block');
            const moduleId = form.dataset.moduleId;
            const submitButton = form.querySelector('button[type="submit"]');
            const restoreButton = setBusy(submitButton, 'Replacing…');
            const editableValues = {};
            block.querySelectorAll('[name*="[block_title]"], [name*="[block_content]"]').forEach(function (field) {
                editableValues[field.name.replace(/blocks\[\d+\]/, 'blocks[INDEX]')] = field.value;
            });

            fetchJson(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(function (result) {
                if (!result.ok || !result.body.success || !result.body.block_html) {
                    const error = form.querySelector('.lw-inline-error');
                    if (error) error.textContent = result.body.message || 'The file could not be replaced.';
                    showToast(result.body.message || 'The file could not be replaced.', 'error');
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.innerHTML = result.body.block_html.trim();
                const replacement = wrapper.firstElementChild;
                if (!replacement) throw new Error('Replacement block markup was empty.');
                replacement.querySelectorAll('[name*="[block_title]"], [name*="[block_content]"]').forEach(function (field) {
                    const key = field.name.replace(/blocks\[\d+\]/, 'blocks[INDEX]');
                    if (Object.prototype.hasOwnProperty.call(editableValues, key)) {
                        field.value = editableValues[key];
                    }
                });
                block.replaceWith(replacement);
                initializeAccessibleBlocks();
                const moduleForm = document.getElementById('saveModuleForm' + moduleId);
                if (moduleForm) renumberModuleBlockInputs(moduleForm);
                showToast(result.body.message || 'File replaced.', 'success');
            }).catch(function () {
                showToast('Network error. The original file is unchanged.', 'error');
            }).finally(restoreButton);
        });
    }

    function attachAjaxModuleSave() {
        document.querySelectorAll('form.js-ajax-module-save').forEach(function (form) {
            if (form.dataset.ajaxReady === '1') return;
            form.dataset.ajaxReady = '1';
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const moduleId = form.dataset.moduleId;
                if (!validateModuleSave(form, moduleId)) return;
                const submitBtn = document.querySelector('button[type="submit"][form="' + form.id + '"]');
                let originalLabel = '';
                if (submitBtn) { originalLabel = submitBtn.innerHTML; submitBtn.disabled = true; submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…'; }
                function restoreBtn() { if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = originalLabel || '<i class="bi bi-check2"></i> Save Module'; } }
                const markedBlocks = Array.from(document.querySelectorAll('#blockList' + moduleId + ' .lw-block[data-marked-delete="1"]'));
                const deletionTasks = markedBlocks.map(function (block) {
                    const btn = block.querySelector('.js-block-delete');
                    let url = btn ? btn.dataset.deleteUrl : null;
                    // Fallback: if the rendered HTML lacked a data-delete-url but the block has
                    // a server id, build the URL from it. Old block partials sometimes omit it.
                    if (!url && block.dataset.blockId) {
                        url = '/trainer/courses/' + lwCourseId + '/blocks/' + block.dataset.blockId;
                    }
                    return function () {
                        if (!url) { return Promise.resolve({ ok: true, body: { success: true }, block: block }); }
                        const fd = new FormData(); fd.append('_token', csrfToken); fd.append('_method', 'DELETE');
                        return fetchJson(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
                            .then(function (result) {
                                result.block = block;
                                return result;
                            })
                            .catch(function (err) { return { ok: false, body: { message: err.message || 'network error' }, block: block }; });
                    };
                });
                let batchSaveSucceeded = false;

                window.coordinateDeleteThenSave({
                    deletionTasks: deletionTasks,
                    isDeletionSuccessful: function (result) { return result.ok && result.body && result.body.success; },
                    finalizeDeletion: function (result) {
                        if (result.block) result.block.remove();
                        handleDeletedBlockResult(result);
                    },
                    createSavePayload: function () {
                        renumberModuleBlockInputs(form); refreshModuleView(moduleId);
                        const allBlocks = Array.from(
                            document.querySelectorAll(
                                '#blockList' + moduleId + ' .lw-block'
                            )
                        );

                        const survivingBlockIndexes = allBlocks
                            .map(function (block, index) {
                                return block.dataset.markedDelete === '1'
                                    ? null
                                    : index;
                            })
                            .filter(function (index) {
                                return index !== null;
                            });

                        const remappedEntries = window.remapSurvivingBlockEntries(
                            Array.from(new FormData(form).entries()),
                            survivingBlockIndexes
                        );

                        const formData = new FormData();

                        remappedEntries.forEach(function (entry) {
                            formData.append(entry[0], entry[1]);
                        });

                        return formData;
                    },
                    saveBatch: function (formData) {
                        return fetchJson(form.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
                    },
                    isSaveSuccessful: function (result) {
                        batchSaveSucceeded = Boolean(
                            result.ok &&
                            result.body &&
                            result.body.success
                        );

                        return batchSaveSucceeded;
                    },
                }).then(function (result) {
                    if (!result.ok && result.phase === 'delete') {
                        const failed = result.failedDeletion;
                        const failedMessage =
                            (failed.body && failed.body.message) ||
                            'One or more marked blocks could not be removed.';

                        showToast(
                            'Module saved, but block removal was incomplete. ' +
                            failedMessage +
                            ' Some marked blocks may already have been removed. ' +
                            'Refresh the page before retrying.',
                            'error'
                        );

                        return;
                    }

                    const res = result.saveResult;

                    if (!result.ok && result.phase === 'save') {
                        const titleError = document.querySelector('[data-module-title-error="' + moduleId + '"]');
                        if (titleError && res.body && res.body.errors && res.body.errors.module_title) {
                            titleError.textContent = res.body.errors.module_title[0];
                        }
                        showToast((res.body && res.body.message) || 'Save failed. Please try again.', 'error');
                        return;
                    }

                    if (result.ok && result.phase === 'complete') {
                        renumberModuleBlockInputs(form);

                        const deletedBlockIds = new Set(
                            result.deletionResults
                                .map(function (deletionResult) {
                                    const deletedBlock = deletionResult.block;

                                    if (!deletedBlock) {
                                        return null;
                                    }

                                    const blockIdInput = deletedBlock.querySelector(
                                        'input[name$="[block_id]"]'
                                    );

                                    const blockId =
                                        deletedBlock.dataset.blockId ||
                                        (blockIdInput ? blockIdInput.value : null);

                                    return blockId ? String(blockId) : null;
                                })
                                .filter(function (blockId) {
                                    return blockId !== null;
                                })
                        );

                        showToast(res.body.message || 'Module saved.', 'success');
                        if (moduleId && res.body.module_title) {
                            document.querySelectorAll('[data-module-grid-title="' + moduleId + '"]').forEach(function (el) { el.textContent = res.body.module_title; });
                            const ti = form.querySelector('input[name="module_title"]'); if (ti) ti.value = res.body.module_title;
                            const deleteButton = document.querySelector('[data-delete-module-url][data-module-id="' + moduleId + '"]');
                            if (deleteButton) deleteButton.dataset.moduleTitle = res.body.module_title;
                        }
                        if (Array.isArray(res.body.blocks)) {
                            const blockList = document.querySelector('[data-form-id="' + form.id + '"]');
                            if (blockList) {
                                const newlyPersistedLocalBlocks = blockList.querySelectorAll(
                                    '.lw-block:not([data-block-id]):not([data-marked-delete="1"])'
                                ).length;
                                const domBlocks = blockList.querySelectorAll('.lw-block');
                                const survivingServerBlocks = res.body.blocks.filter(
                                    function (serverBlock) {
                                        return !deletedBlockIds.has(
                                            String(serverBlock.block_id)
                                        );
                                    }
                                );
                                survivingServerBlocks.forEach(function (server, idx) {
                                    const block = domBlocks[idx]; if (!block) return;
                                    block.dataset.blockId = String(server.block_id);
                                    let bid = block.querySelector('input[name$="[block_id]"]');
                                    if (!bid) { bid = document.createElement('input'); bid.type = 'hidden'; bid.setAttribute('form', form.id); block.appendChild(bid); }
                                    bid.name = 'blocks[' + idx + '][block_id]';
                                    bid.value = String(server.block_id);
                                    const localDel = block.querySelector('.js-block-delete-local');
                                    if (localDel) { localDel.classList.remove('js-block-delete-local'); localDel.classList.add('js-block-delete'); if (server.delete_url) localDel.dataset.deleteUrl = server.delete_url; }
                                    block.removeAttribute('data-pending');
                                });
                                updateModuleGridCount(moduleId, Number(res.body.block_count ?? domBlocks.length));
                                const deleteButton = document.querySelector('[data-delete-module-url][data-module-id="' + moduleId + '"]');
                                if (deleteButton) deleteButton.dataset.blockCount = String(res.body.block_count ?? domBlocks.length);
                                if (newlyPersistedLocalBlocks > 0) {
                                    adjustGlobalBlockTotal(newlyPersistedLocalBlocks);
                                }
                            }
                        }
                        dirtyModules.delete(String(moduleId));
                        const titleError = document.querySelector('[data-module-title-error="' + moduleId + '"]');
                        if (titleError) titleError.textContent = '';
                        refreshModuleView(moduleId); captureOriginalState(moduleId);
                    }
                })
                .catch(function (err) {
                    if (batchSaveSucceeded) {
                        showToast(
                            'Module saved, but block removal could not be completed. ' +
                            'Some marked blocks may already have been removed. ' +
                            'Refresh the page before retrying.',
                            'error'
                        );

                        return;
                    }

                    showToast(
                        'Network error: ' +
                        (err.message || 'unable to save'),
                        'error'
                    );
                })
                .finally(restoreBtn);
            });
        });
    }

    /* ============================================================ ASSESSMENT BUILDER ============================================================ */

    function hydrateAssessmentCatalog() {
        const select = document.getElementById('editQuizSelect');
        if (!select || assessmentCatalog.size > 0) return;
        Array.from(select.options).forEach(function (option) {
            if (!option.value || !option.dataset.assessmentType) return;
            assessmentCatalog.set(String(option.value), {
                assessment_id: String(option.value),
                title: option.textContent.trim(),
                type: option.dataset.assessmentType === 'submission' ? 'submission' : 'quiz',
                load_url: option.dataset.loadUrl || '',
                update_url: option.dataset.updateUrl || '',
            });
        });
    }

    function upsertAssessmentInWorkspace(assessment) {
        if (!assessment || !assessment.assessment_id) return;
        const assessmentId = String(assessment.assessment_id);
        const type = assessment.type === 'submission' ? 'submission' : 'quiz';
        assessmentBuilderCache[assessmentId] = assessment;

        assessmentCatalog.set(assessmentId, {
            assessment_id: assessmentId,
            title: assessment.title || 'Untitled assessment',
            type: type,
            load_url: assessment.load_url || '',
            update_url: assessment.update_url || '',
        });
        filterExistingAssessments(assessmentState.type, assessmentId);

        document.querySelectorAll('select[name="assessment_id"]').forEach(function (select) {
            const panel = select.closest('.lw-add-panel');
            const panelType = panel && panel.id.indexOf('submissionPanel') === 0
                ? 'submission'
                : 'quiz';
            let option = select.querySelector('option[data-assessment-option="' + assessmentId + '"]');
            if (panelType !== type) {
                if (option) option.remove();
                return;
            }
            if (!option) {
                option = document.createElement('option');
                option.value = assessmentId;
                option.dataset.assessmentOption = assessmentId;
                select.appendChild(option);
            }
            option.dataset.baseTitle = assessment.title;
            option.textContent = assessment.title;
            const hint = select.closest('form').querySelector('.lw-add-panel-hint');
            if (hint) hint.hidden = true;
        });

        const embeddedBlocks = document.querySelectorAll(
            '.lw-block[data-assessment-id="' + assessmentId + '"]'
        );
        embeddedBlocks.forEach(function (block) {
            const title = block.querySelector('[data-assessment-title]');
            const description = block.querySelector('[data-assessment-description]');
            const meta = block.querySelector('[data-assessment-meta]');
            const titleInput = block.querySelector('[name$="[block_title]"]');
            const contentInput = block.querySelector('[name$="[block_content]"]');
            if (title) title.textContent = assessment.title;
            if (titleInput) titleInput.value = assessment.title || '';
            if (contentInput) contentInput.value = assessment.instruction_text || '';
            if (description) {
                description.textContent = assessment.instruction_text || '';
                description.hidden = !assessment.instruction_text;
            }
            if (meta) {
                meta.innerHTML = '';
                if (type === 'submission') {
                    const formatIcon = document.createElement('i');
                    formatIcon.className = 'bi bi-file-earmark-check';
                    meta.appendChild(formatIcon);
                    meta.appendChild(document.createTextNode(
                        ' ' + (assessment.accepted_format || 'Any file format')
                    ));
                }
                if (assessment.available_until_input) {
                    if (type === 'submission') {
                        const separator = document.createElement('span');
                        separator.className = 'lw-block-meta-sep';
                        separator.textContent = '·';
                        meta.appendChild(separator);
                    }
                    const clock = document.createElement('i');
                    clock.className = 'bi bi-clock';
                    meta.appendChild(clock);
                    const date = new Date(assessment.available_until_input);
                    const label = type === 'submission' ? ' Due ' : ' Closes: ';
                    meta.appendChild(document.createTextNode(
                        label + date.toLocaleString([], {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })
                    ));
                }
                meta.hidden = type === 'quiz' && !assessment.available_until_input;
            }
        });

        recomputeAssessmentPickerState(assessmentId);
    }

    function requestAssessmentDiscard(context) {
        return requestMaterialsConfirmation({
            title: 'Discard unsaved changes?',
            message: (context ? context + ' ' : '') + 'Your unsaved assessment changes will be discarded.',
            confirmLabel: 'Discard Changes',
            destructive: true
        });
    }

    function setAssessmentLoading(assessmentId) {
        if (assessmentLoadingTimer) clearTimeout(assessmentLoadingTimer);
        assessmentLoadingTimer = null;
        assessmentState.loadingAssessmentId = assessmentId ? String(assessmentId) : null;
        assessmentState.loadingVisible = false;
        const spinner = document.getElementById('assessmentLoadSpinner');
        if (spinner) spinner.hidden = true;
        const save = document.getElementById('saveAssessmentButton');
        if (save) save.disabled = Boolean(assessmentState.loadingAssessmentId);
        if (assessmentState.loadingAssessmentId) {
            const token = assessmentState.requestToken;
            assessmentLoadingTimer = setTimeout(function () {
                if (token !== assessmentState.requestToken || !assessmentState.loadingAssessmentId) return;
                assessmentState.loadingVisible = true;
                if (spinner) spinner.hidden = false;
            }, 150);
        }
    }

    function cancelAssessmentLoad() {
        if (assessmentState.requestController) assessmentState.requestController.abort();
        assessmentState.requestController = null;
        assessmentState.requestToken += 1;
        setAssessmentLoading(null);
    }

    function setAssessmentAttemptBoundary(locked) {
        const boundary = document.getElementById('assessmentAttemptBoundary');
        if (boundary) boundary.hidden = !locked;
        document.querySelectorAll('#addQuestionPanel button, #quizTemplatesSection button').forEach(function (button) {
            button.disabled = locked;
            button.title = locked ? 'Question structure is locked because this quiz has learner attempts.' : '';
        });
        document.querySelectorAll('#questionList .lw-assess-question').forEach(function (card) {
            card.classList.toggle('is-locked', locked);
            card.querySelectorAll('input:not([type="hidden"]), textarea').forEach(function (field) {
                if (['file', 'radio', 'checkbox'].includes(field.type)) field.disabled = locked;
                else field.readOnly = locked;
            });
            card.querySelectorAll('button:not(.lw-question-toggle)').forEach(function (button) {
                button.disabled = locked;
            });
        });
        document.querySelectorAll('#quizScoringSection input:not([type="hidden"])').forEach(function (field) {
            field.readOnly = locked;
            field.setAttribute('aria-readonly', locked ? 'true' : 'false');
            field.title = locked ? 'Scoring settings are locked because this quiz has learner attempts.' : '';
        });
        const scoringSection = document.getElementById('quizScoringSection');
        if (scoringSection) scoringSection.classList.toggle('is-locked', locked);
        if (lwAssessmentQuestionSortable && typeof lwAssessmentQuestionSortable.option === 'function') {
            lwAssessmentQuestionSortable.option('disabled', locked);
        }
    }

    function filterExistingAssessments(type, preferredAssessmentId) {
        const select = document.getElementById('editQuizSelect');
        if (!select) return;
        const preferredId = preferredAssessmentId !== undefined && preferredAssessmentId !== null
            ? String(preferredAssessmentId)
            : null;
        const matching = Array.from(assessmentCatalog.values()).filter(function (assessment) {
            return assessment.type === type;
        });
        select.replaceChildren();
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.disabled = true;
        placeholder.textContent = type === 'quiz'
            ? '— choose a saved quiz —'
            : '— choose a saved submission task —';
        select.appendChild(placeholder);
        matching.forEach(function (assessment) {
            const option = document.createElement('option');
            option.value = assessment.assessment_id;
            option.textContent = assessment.title;
            option.dataset.assessmentType = assessment.type;
            option.dataset.loadUrl = assessment.load_url;
            option.dataset.updateUrl = assessment.update_url;
            select.appendChild(option);
        });
        select.value = preferredId && matching.some(function (assessment) {
            return assessment.assessment_id === preferredId;
        }) ? preferredId : '';
        const empty = document.getElementById('assessmentExistingEmpty');
        if (empty) {
            empty.textContent = type === 'quiz' ? 'No saved quizzes yet.' : 'No saved submission tasks yet.';
            empty.hidden = matching.length > 0 || assessmentState.loadingAssessmentId !== null;
        }
        select.disabled = matching.length === 0;
    }

    async function selectAssessmentType(type, force) {
        if (!['quiz', 'submission'].includes(type)) return false;
        if (!force && assessmentState.type === type) return true;
        if (!force && assessmentState.type !== type && isAssessmentDirty()) {
            const confirmed = await requestAssessmentDiscard('Switch assessment type?');
            if (!confirmed) return false;
        }
        initializeNewAssessment(type);
        syncAssessmentPageUrl();
        return true;
    }

    function updateAvailabilityContext() {
        const context = document.getElementById('assessmentAvailabilityState');
        const from = document.getElementById('assessAvailFrom');
        const until = document.getElementById('assessAvailUntil');
        if (!context || !from || !until) return;
        const now = new Date();
        const fromDate = from.value ? new Date(from.value) : null;
        const untilDate = until.value ? new Date(until.value) : null;
        const format = function (date) {
            return date.toLocaleString([], { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        };
        if (!fromDate && !untilDate) context.textContent = 'Always available';
        else if (fromDate && now < fromDate) context.textContent = 'Opens ' + format(fromDate);
        else if (untilDate && now > untilDate) context.textContent = 'Closed ' + format(untilDate);
        else if (untilDate) context.textContent = 'Available until ' + format(untilDate);
        else context.textContent = 'Available now';
    }

    function updateQuestionJumpState(card) {
        updateCanonicalQuestionFromCard(card);
        const state = updateQuestionCardState(card);
        const jump = document.querySelector('[data-question-jump-index="' + card.dataset.questionIndex + '"]');
        if (jump) {
            const status = jump.querySelector('.lw-question-jump-btn-status');
            if (status) {
                status.textContent = state.complete ? 'Complete' : 'Needs attention';
                status.classList.toggle('is-complete', state.complete);
            }
        }
        const incomplete = document.getElementById('assessSummaryIncomplete');
        if (incomplete) {
            incomplete.textContent = window.AssessmentBuilderUtils
                .deriveQuestionCollectionState(assessmentState).incompleteCount;
        }
    }

    function initializeQuestionSortable() {
        const list = document.getElementById('questionList');
        if (!list || typeof Sortable === 'undefined' || lwAssessmentQuestionSortable) return;
        lwAssessmentQuestionSortable = new Sortable(list, {
            animation: 150,
            handle: '.lw-question-drag',
            draggable: '.lw-assess-question',
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                renumberQuestionCards();
                syncCanonicalQuestionOrderFromCards();
                refreshQuestionNavigator();
                updateAssessmentSummary();
                assessmentState.dirty = true;
            }
        });
    }

    let lwImportRows = [];
    function openQuestionImport() {
        if (assessmentState.type !== 'quiz' || assessmentState.hasLearnerRecords) return;
        lwImportRows = [];
        const input = document.getElementById('questionCsvInput');
        const preview = document.getElementById('questionImportPreview');
        const add = document.getElementById('addValidImportedQuestions');
        if (input) input.value = '';
        if (preview) preview.innerHTML = '<p>Select a CSV file to validate it before adding questions.</p>';
        if (add) {
            add.disabled = true;
            add.textContent = 'Add Valid Questions';
        }
        openModuleModal('assessmentImportModal');
    }

    function closeQuestionImport() {
        finalizeClose('assessmentImportModal', null);
    }

    function importPreviewField(rowIndex, field, value, type) {
        const escaped = escapeAttribute(value ?? '');
        if (field === 'type') {
            const canonicalType = window.AssessmentBuilderUtils.normalizeQuestionType(value);
            const options = Object.entries(window.AssessmentBuilderUtils.QUESTION_TYPE_LABELS).map(function (entry) {
                return '<option value="' + entry[0] + '"' + (entry[0] === canonicalType ? ' selected' : '') + '>' + entry[1] + '</option>';
            }).join('');
            const unsupported = !canonicalType && String(value || '').trim()
                ? '<option value="' + escaped + '" selected>Unsupported: ' + escapeHtml(value) + '</option>'
                : '';
            return '<select data-import-row="' + rowIndex + '" data-import-field="type" aria-label="Question type">' + unsupported + options + '</select>';
        }
        if (field === 'correct_answer' && type === 'true_false') {
            return '<select data-import-row="' + rowIndex + '" data-import-field="correct_answer" aria-label="Correct answer">' +
                '<option value="">Choose...</option>' +
                '<option value="True"' + (value === 'True' ? ' selected' : '') + '>True</option>' +
                '<option value="False"' + (value === 'False' ? ' selected' : '') + '>False</option>' +
            '</select>';
        }
        const inputType = field === 'marks' ? 'number' : 'text';
        return '<input type="' + inputType + '" data-import-row="' + rowIndex + '" data-import-field="' + field + '" value="' + escaped + '"' +
            (field === 'marks' ? ' min="0" step="0.5"' : '') + '>';
    }

    function importRowHtml(row, rowIndex) {
        const source = row.source || {};
        const type = window.AssessmentBuilderUtils.normalizeQuestionType(source.type) || source.type || '';
        const answerField = type === 'short_answer' ? 'accepted_answer' : 'correct_answer';
        const answerValue = source[answerField] || source.correct_answer || '';
        return '<tr data-import-preview-row="' + rowIndex + '">' +
            '<td>' + row.row + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'type', type || source.type || '', type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'question', source.question || '', type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'marks', source.marks ?? '', type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'option_a', source.option_a || '', type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'option_b', source.option_b || '', type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'option_c', source.option_c || '', type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'option_d', source.option_d || '', type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, answerField, answerValue, type) + '</td>' +
            '<td>' + importPreviewField(rowIndex, 'grading_rubric', source.grading_rubric || '', type) + '</td>' +
            '<td class="lw-import-row-status ' + (row.valid ? 'is-valid' : 'is-invalid') + '">' + (row.valid ? 'Valid' : 'Needs attention') + '</td>' +
            '<td class="lw-import-row-issues">' + escapeHtml((row.issues || []).join(' ')) + '</td>' +
        '</tr>';
    }

    function updateImportPreviewSummary() {
        const state = window.AssessmentBuilderUtils.deriveAssessmentImportState(lwImportRows);
        const detected = document.getElementById('questionImportDetected');
        const valid = document.getElementById('questionImportValid');
        const invalid = document.getElementById('questionImportInvalid');
        const add = document.getElementById('addValidImportedQuestions');
        if (detected) detected.textContent = state.total + ' detected';
        if (valid) valid.textContent = state.validCount + ' valid';
        if (invalid) invalid.textContent = state.invalidCount + ' need attention';
        if (add) {
            add.disabled = state.validCount === 0;
            add.textContent = state.validCount > 0
                ? 'Add ' + state.validCount + ' Valid Question' + (state.validCount === 1 ? '' : 's')
                : 'Add Valid Questions';
        }
        return state;
    }

    function renderQuestionImportPreview(result) {
        const preview = document.getElementById('questionImportPreview');
        const add = document.getElementById('addValidImportedQuestions');
        if (!preview || !add) return;
        lwImportRows = Array.isArray(result.rows) ? result.rows : [];
        const mappings = (result.headerMappings || []).filter(function (mapping) {
            return mapping.imported && mapping.canonical && mapping.recognized !== false;
        });
        const mapping = mappings.length > 0
            ? '<details class="lw-import-mapping"><summary>Column mapping</summary><div>' + mappings.map(function (item) {
                return '<span><strong>' + escapeHtml(item.imported) + '</strong><i class="bi bi-arrow-right"></i>' + escapeHtml(item.canonical) + '</span>';
            }).join('') + '</div></details>'
            : '';
        const table = lwImportRows.length > 0
            ? '<div class="lw-import-table-wrap"><table class="lw-import-table"><thead><tr>' +
                '<th>Row</th><th>Type</th><th>Question</th><th>Marks</th><th>Option A</th><th>Option B</th><th>Option C</th><th>Option D</th><th>Correct / Expected</th><th>Rubric</th><th>Status</th><th>Issues</th>' +
                '</tr></thead><tbody>' + lwImportRows.map(importRowHtml).join('') + '</tbody></table></div>'
            : '';
        preview.innerHTML = '<div class="lw-import-preview-summary">' +
            '<span id="questionImportDetected">0 detected</span>' +
            '<span id="questionImportValid">0 valid</span>' +
            '<span id="questionImportInvalid">0 need attention</span>' +
            '</div>' +
            mapping +
            table +
            ((result.errors || []).length > 0
                ? '<ul class="lw-import-errors">' + result.errors.map(function (error) { return '<li>' + escapeHtml(error) + '</li>'; }).join('') + '</ul>'
                : '<p>All questions are valid and ready to add. You can still edit any preview cell.</p>');
        updateImportPreviewSummary();
    }

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = String(value || '');
        return element.innerHTML;
    }

    function escapeAttribute(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;');
    }

    function initializeAssessmentBuilder() {
        const workspace = document.getElementById('assessmentWorkspace');
        if (!workspace || workspace.dataset.builderReady === '1') return;
        workspace.dataset.builderReady = '1';
        initializeQuestionSortable();

        workspace.addEventListener('click', function (event) {
            const typeChoice = event.target.closest('[data-assessment-type-choice]');
            if (typeChoice) { selectAssessmentType(typeChoice.dataset.assessmentTypeChoice); return; }
            const toggle = event.target.closest('.lw-question-toggle');
            if (toggle) {
                const card = toggle.closest('.lw-assess-question');
                setQuestionExpanded(card, card.classList.contains('is-collapsed'));
                return;
            }
            const duplicate = event.target.closest('.lw-question-duplicate');
            if (duplicate) { duplicateQuestionCard(duplicate.closest('.lw-assess-question')); return; }
            const insertToggle = event.target.closest('.lw-question-insert-toggle');
            if (insertToggle) {
                insertToggle.parentElement.querySelector('.lw-question-insert-menu').classList.toggle('hidden');
                return;
            }
            const insert = event.target.closest('[data-insert-question-type]');
            if (insert) {
                const card = insert.closest('.lw-assess-question');
                addQuestion(insert.dataset.insertQuestionType, null, false, card);
            }
        });
        workspace.addEventListener('input', function (event) {
            const card = event.target.closest('.lw-assess-question');
            if (card) {
                updateQuestionJumpState(card);
                if (event.target.matches('[name$="[marks]"]')) updateAssessmentSummary();
            }
            if (event.target.matches('[name="available_from"], [name="available_until"]')) updateAvailabilityContext();
        });
        workspace.addEventListener('change', function (event) {
            const card = event.target.closest('.lw-assess-question');
            if (card) {
                updateQuestionJumpState(card);
                updateAssessmentSummary();
            }
            if (event.target.matches('[name="available_from"], [name="available_until"]')) updateAvailabilityContext();
        });

        document.getElementById('assessmentStartNew').addEventListener('click', function () { resetAssessmentBuilder(false); });
        document.getElementById('editQuizSelect').addEventListener('change', function () { loadAssessmentForEdit(this); });
        document.getElementById('customTemplateButton').addEventListener('click', function () {
            if (assessmentState.hasLearnerRecords) {
                showToast('Questions are locked because learner attempts already exist.', 'error');
                return;
            }
            assessmentState.templateState = 'custom';
            resetCustomQuestionMix();
            openModuleModal('customTemplateModal', this);
        });
        document.getElementById('closeCustomTemplate').addEventListener('click', function () { closeModal('customTemplateModal'); });
        document.getElementById('cancelCustomTemplate').addEventListener('click', function () { closeModal('customTemplateModal'); });
        document.getElementById('applyCustomTemplate').addEventListener('click', async function () {
            const result = await applyCustomQuestionMix();
            if (!result.success) return;
            closeModal('customTemplateModal');
        });
        document.getElementById('addCustomMixRow').addEventListener('click', function () {
            appendCustomMixRow();
        });
        document.getElementById('customMixRows').addEventListener('input', updateCustomQuestionMix);
        document.getElementById('customMixRows').addEventListener('change', updateCustomQuestionMix);
        document.getElementById('customMixRows').addEventListener('click', function (event) {
            const remove = event.target.closest('[data-remove-custom-mix-row]');
            if (!remove) return;
            const rows = this.querySelectorAll('[data-custom-mix-row]');
            if (rows.length <= 1) return;
            remove.closest('[data-custom-mix-row]').remove();
            updateCustomQuestionMix();
        });
        document.getElementById('clearAllQuestions').addEventListener('click', async function () {
            const collection = window.AssessmentBuilderUtils.deriveQuestionCollectionState(assessmentState);
            if (collection.count === 0 || assessmentState.hasLearnerRecords) return;
            const confirmed = await requestMaterialsConfirmation({
                title: 'Remove all questions?',
                message: 'Remove all ' + collection.count + ' questions? This change will be applied when you save the quiz.',
                confirmLabel: 'Remove All',
                destructive: true
            });
            if (!confirmed) return;
            document.getElementById('questionList').replaceChildren();
            Object.assign(assessmentState, window.AssessmentBuilderUtils.clearQuestionsFromState(assessmentState));
            refreshQuestionNavigator();
            updateAssessmentSummary();
            showToast('All questions removed.', 'success');
        });
        document.getElementById('openQuestionImport').addEventListener('click', openQuestionImport);
        document.getElementById('closeQuestionImport').addEventListener('click', closeQuestionImport);
        document.getElementById('cancelQuestionImport').addEventListener('click', closeQuestionImport);
        document.getElementById('questionCsvInput').addEventListener('change', async function () {
            if (!this.files || !this.files[0]) {
                renderQuestionImportPreview({ questions: [], errors: ['Choose a CSV file to continue.'], total: 0 });
                return;
            }
            const selectedFile = this.files[0];
            if (!/\.csv$/i.test(selectedFile.name)) {
                this.value = '';
                const excel = /\.xlsx?$/i.test(selectedFile.name);
                const message = excel
                    ? 'Excel files are not supported by this importer yet. Save the spreadsheet as CSV, or download the CSV template.'
                    : 'This file does not match the Assessment Question import format. Choose a CSV file.';
                renderQuestionImportPreview({ questions: [], errors: [message], total: 0, rows: [] });
                showToast(message, 'error');
                return;
            }
            try {
                renderQuestionImportPreview(window.AssessmentBuilderUtils.validateAssessmentCsv(await selectedFile.text()));
            } catch (error) {
                renderQuestionImportPreview({ questions: [], errors: ['The CSV could not be read.'], total: 0 });
                showToast('The CSV could not be read.', 'error');
            }
        });
        document.getElementById('questionImportPreview').addEventListener('input', function (event) {
            const field = event.target.closest('[data-import-row][data-import-field]');
            if (!field) return;
            const rowIndex = Number(field.dataset.importRow);
            lwImportRows = window.AssessmentBuilderUtils.updateAssessmentImportRow(
                lwImportRows,
                rowIndex,
                field.dataset.importField,
                field.value
            );
            const oldRow = this.querySelector('[data-import-preview-row="' + rowIndex + '"]');
            if (!oldRow) return;
            const updatedRow = lwImportRows[rowIndex];
            if (field.dataset.importField === 'type') {
                const holder = document.createElement('tbody');
                holder.innerHTML = importRowHtml(updatedRow, rowIndex);
                oldRow.replaceWith(holder.firstElementChild);
            } else {
                const status = oldRow.querySelector('.lw-import-row-status');
                const issues = oldRow.querySelector('.lw-import-row-issues');
                if (status) {
                    status.textContent = updatedRow.valid ? 'Valid' : 'Needs attention';
                    status.classList.toggle('is-valid', updatedRow.valid);
                    status.classList.toggle('is-invalid', !updatedRow.valid);
                }
                if (issues) issues.textContent = (updatedRow.issues || []).join(' ');
            }
            updateImportPreviewSummary();
        });
        document.getElementById('addValidImportedQuestions').addEventListener('click', function () {
            const importState = window.AssessmentBuilderUtils.deriveAssessmentImportState(lwImportRows);
            const count = importState.validCount;
            if (assessmentState.questions.length + count > 100) {
                showToast('A quiz can contain at most 100 questions. Remove questions or import a smaller CSV.', 'error');
                return;
            }
            const result = addQuestionsBatch(importState.validQuestions, { expandLast: false, skipDirty: false });
            if (!result.success) return;
            closeQuestionImport();
            showToast(count + ' question' + (count === 1 ? '' : 's') + ' imported.', 'success');
        });
        document.getElementById('downloadQuestionTemplate').addEventListener('click', function () {
            const blob = new Blob([window.AssessmentBuilderUtils.assessmentCsvTemplate()], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'assessment-questions-template.csv';
            link.click();
            URL.revokeObjectURL(url);
        });
    }

    function assessmentBeforeUnload(event) {
        if (!document.getElementById('assessmentWorkspace') || !isAssessmentDirty()) return;
        event.preventDefault();
        event.returnValue = '';
    }

    function initializeAssessmentPageNavigation() {
        const workspace = document.getElementById('assessmentWorkspace');
        if (!workspace || workspace.dataset.navigationReady === '1') return;
        workspace.dataset.navigationReady = '1';
        window.removeEventListener('beforeunload', assessmentBeforeUnload);
        window.addEventListener('beforeunload', assessmentBeforeUnload);
    }

    function syncAssessmentPageUrl() {
        if (!document.getElementById('assessmentWorkspace')) return;
        const url = new URL(window.location.href);
        url.searchParams.set('type', assessmentState.type);
        if (assessmentState.mode === 'edit' && assessmentState.selectedAssessmentId) {
            url.searchParams.set('assessment', assessmentState.selectedAssessmentId);
        } else {
            url.searchParams.delete('assessment');
        }
        window.history.replaceState({}, '', url);
        lmsAuthoringCurrentUrl = window.location.href;
    }

    function initializeAssessmentPageState() {
        hydrateAssessmentCatalog();
        const params = new URLSearchParams(window.location.search);
        const requestedId = String(params.get('assessment') || '');
        const catalogAssessment = requestedId ? assessmentCatalog.get(requestedId) : null;
        const requestedType = catalogAssessment
            ? catalogAssessment.type
            : (params.get('type') === 'submission' ? 'submission' : 'quiz');

        initializeNewAssessment(requestedType);
        if (!catalogAssessment) {
            syncAssessmentPageUrl();
            return;
        }

        const select = document.getElementById('editQuizSelect');
        if (!select) return;
        select.value = requestedId;
        loadAssessmentForEdit(select);
    }

    function attachAjaxAssessmentForm() {
        const form = document.getElementById('assessmentForm');
        if (!form || form.dataset.ajaxReady === '1') return;
        form.dataset.ajaxReady = '1';
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            renumberQuestionCards();
            document.querySelectorAll('.choice-editor').forEach(function (editor) {
                if (editor.querySelector('.lw-choice-list')) {
                    renumberChoiceRows(editor);
                    syncChoiceData(editor);
                }
            });
            if (!validateAssessmentBeforeSubmit(form)) return;

            let savePayload = new FormData(form);
            if (
                assessmentState.type === 'quiz'
                && assessmentState.mode === 'edit'
                && assessmentState.hasLearnerRecords
            ) {
                const metadataPayload = new FormData();
                window.AssessmentBuilderUtils
                    .attemptedQuizMetadataEntries(savePayload.entries())
                    .forEach(function (entry) {
                        metadataPayload.append(entry[0], entry[1]);
                    });
                savePayload = metadataPayload;
            }

            const errorBox = document.getElementById('assessmentFormError');
            if (errorBox) {
                errorBox.textContent = '';
                errorBox.classList.add('hidden');
            }
            const submitButton = document.getElementById('saveAssessmentButton');
            const restoreButton = setBusy(submitButton, 'Saving…');
            fetchJson(form.action, {
                method: 'POST',
                body: savePayload,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(function (result) {
                if (!result.ok || !result.body.success) {
                    const message = result.body.message || 'The assessment could not be saved.';
                    if (errorBox) {
                        errorBox.textContent = message;
                        errorBox.classList.remove('hidden');
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    const questionMatch = message.match(/Question\s+(\d+)/i);
                    const invalidQuestionKey = result.body.errors
                        ? Object.keys(result.body.errors).find(function (key) { return /^questions\.\d+\./.test(key); })
                        : null;
                    const invalidIndexMatch = invalidQuestionKey ? invalidQuestionKey.match(/^questions\.(\d+)\./) : null;
                    if (questionMatch) scrollToQuestion(Number(questionMatch[1]) - 1);
                    else if (invalidIndexMatch) scrollToQuestion(Number(invalidIndexMatch[1]));
                    showToast(message, 'error');
                    return;
                }
                const assessment = result.body.assessment;
                upsertAssessmentInWorkspace(assessment);
                assessmentState.mode = 'edit';
                assessmentState.selectedAssessmentId = String(assessment.assessment_id);
                form.action = assessment.update_url;
                const method = document.getElementById('assessmentMethod');
                if (method) {
                    method.disabled = false;
                    method.value = 'PATCH';
                }
                const editSelect = document.getElementById('editQuizSelect');
                if (editSelect) editSelect.value = assessmentState.selectedAssessmentId;
                assessmentBuilderCache[assessmentState.selectedAssessmentId] = assessment;
                populateAssessmentBuilder(assessment);
                updateModeLabel();
                captureAssessmentState();
                showToast(result.body.message || 'Assessment saved.', 'success');
            }).catch(function () {
                const message = 'Network error. Your assessment was not saved.';
                if (errorBox) {
                    errorBox.textContent = message;
                    errorBox.classList.remove('hidden');
                }
                showToast(message, 'error');
            }).finally(restoreButton);
        });

    }

    function getNowLocalForInput() {
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        return new Date(now.getTime() - offset).toISOString().slice(0, 16);
    }
    function lwSyncDateConstraints() {
        const from = document.getElementById('assessAvailFrom');
        const until = document.getElementById('assessAvailUntil');
        const nowLocal = getNowLocalForInput();
        if (from) { if (assessmentState.mode === 'new') from.min = nowLocal; else from.removeAttribute('min'); }
        if (until) {
            if (from && from.value) {
                until.min = from.value;
            } else if (assessmentState.mode === 'new') { until.min = nowLocal; }
            else { until.removeAttribute('min'); }
        }
    }

    function adjustMarks(button, delta) {
        const wrap = button.closest('.lw-stepper');
        const input = wrap ? wrap.querySelector('.lw-stepper-input') : null;
        if (!input) return;
        const current = parseFloat(input.value) || 0;
        const next = Math.max(0, current + delta);
        input.value = next;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        assessmentState.dirty = true;
    }

    function lwHandleQuestionImagePick(input) {
        const previewId = input.dataset.previewId;
        if (!previewId) return;
        const preview = document.getElementById(previewId);
        if (!preview) return;
        const card = input.closest('.lw-assess-question');
        const error = card ? card.querySelector('.lw-question-image-error') : null;
        const name = card ? card.querySelector('.lw-question-image-name') : null;
        if (error) error.textContent = '';
        const removeFlag = card ? card.querySelector('[name$="[remove_question_image]"]') : null;
        if (removeFlag) removeFlag.remove();
        if (!input.files || !input.files[0]) { preview.classList.remove('show'); preview.innerHTML = ''; return; }
        const selected = input.files[0];
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type)) {
            input.value = '';
            if (error) error.textContent = 'Question images must be JPG, PNG, or WebP files.';
            if (name) name.textContent = '';
            showToast('Question images must be JPG, PNG, or WebP files.', 'error');
            return;
        }
        if (selected.size > 5 * 1024 * 1024) {
            input.value = '';
            if (error) error.textContent = 'Question images must be 5 MB or smaller.';
            if (name) name.textContent = '';
            showToast('Question images must be 5 MB or smaller.', 'error');
            return;
        }
        if (name) name.textContent = selected.name;
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = '';
            const image = document.createElement('img');
            image.src = e.target.result;
            image.alt = 'Question image preview';
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'lw-inline-action danger';
            removeButton.innerHTML = '<i class="bi bi-trash"></i> Remove image';
            removeButton.addEventListener('click', function () {
                removeQuestionImage(removeButton);
            });
            preview.appendChild(image);
            preview.appendChild(removeButton);
            preview.classList.add('show');
        };
        reader.readAsDataURL(input.files[0]);
    }

    function removeQuestionImage(button) {
        const preview = button.closest('.lw-question-image-preview');
        const card = button.closest('.lw-assess-question');
        if (!preview || !card) return;
        const fileInput = card.querySelector('input[type="file"][name$="[question_image]"]');
        if (fileInput) fileInput.value = '';
        const name = card.querySelector('.lw-question-image-name');
        if (name) name.textContent = '';
        const index = card.dataset.questionIndex;
        let removeFlag = card.querySelector('[name="questions[' + index + '][remove_question_image]"]');
        if (!removeFlag) {
            removeFlag = document.createElement('input');
            removeFlag.type = 'hidden';
            removeFlag.name = 'questions[' + index + '][remove_question_image]';
            card.querySelector('.lw-assess-question-body').appendChild(removeFlag);
        }
        removeFlag.value = '1';
        preview.innerHTML = '';
        preview.classList.remove('show');
        assessmentState.dirty = true;
    }

    function updateAssessmentSummary() {
        const list = document.getElementById('questionList');
        if (!list) return;
        const cards = list.querySelectorAll('.lw-assess-question');
        const summary = document.getElementById('assessTotalsSummary');
        cards.forEach(function (card) {
            updateCanonicalQuestionFromCard(card);
            updateQuestionCardState(card);
        });
        const collection = window.AssessmentBuilderUtils.deriveQuestionCollectionState(assessmentState);
        const count = collection.count;
        const totalMarks = collection.totalMarks;
        const incomplete = collection.incompleteCount;
        const countEl = document.getElementById('assessSummaryCount');
        const marksEl = document.getElementById('assessSummaryMarks');
        const incompleteEl = document.getElementById('assessSummaryIncomplete');
        const totalText = (totalMarks % 1 === 0) ? totalMarks : totalMarks.toFixed(2);
        if (countEl) countEl.textContent = count;
        if (marksEl) marksEl.textContent = totalText;
        if (incompleteEl) incompleteEl.textContent = incomplete;
        const quiz = collection.rendered;
        if (summary) summary.hidden = !quiz;
        const hidden = document.getElementById('hiddenTotalMarks');
        if (hidden) hidden.value = totalMarks;
        const display = document.getElementById('totalMarksDisplay');
        if (display) display.textContent = totalText;
        const empty = document.getElementById('questionEmptyState');
        if (empty) empty.hidden = !collection.empty;
        const header = document.getElementById('questionSectionHeader');
        if (header) header.hidden = !quiz || count === 0;
        const headerCount = document.getElementById('questionSectionCount');
        if (headerCount) headerCount.textContent = count + ' question' + (count === 1 ? '' : 's');
        const clear = document.getElementById('clearAllQuestions');
        if (clear) {
            clear.hidden = !collection.clearAllVisible;
            clear.disabled = assessmentState.hasLearnerRecords;
        }
    }

    function renderAssessmentWorkspace() {
        const visibility = window.AssessmentBuilderUtils
            ? window.AssessmentBuilderUtils.deriveAssessmentVisibility(assessmentState)
            : { quiz: assessmentState.type === 'quiz', submission: assessmentState.type !== 'quiz' };
        const type = assessmentState.type;
        const isQuiz = visibility.quiz;
        const workspace = document.getElementById('assessmentWorkspace');
        if (workspace) workspace.classList.remove('is-initial-quiz', 'is-initial-submission');
        const typeInput = document.getElementById('assessmentType');
        if (typeInput) typeInput.value = type;
        document.querySelectorAll('[data-assessment-type-choice]').forEach(function (button) {
            const selected = button.dataset.assessmentTypeChoice === type;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        document.querySelectorAll('[data-assessment-shared-section]').forEach(function (section) {
            section.hidden = false;
        });
        document.querySelectorAll('[data-assessment-quiz-section]').forEach(function (section) {
            section.hidden = !visibility.quiz;
        });
        document.querySelectorAll('[data-assessment-submission-section]').forEach(function (section) {
            section.hidden = !visibility.submission;
        });
        const sidebar = document.getElementById('questionSidebar');
        if (sidebar) sidebar.hidden = !visibility.questionRail;
        const body = document.querySelector('#assessmentWorkspace .lw-assess-body');
        if (body) body.classList.toggle('submission-mode', !isQuiz);
        const newLabel = document.getElementById('assessmentStartNewLabel');
        if (newLabel) newLabel.textContent = isQuiz ? 'New Quiz' : 'New Submission';
        filterExistingAssessments(type, assessmentState.selectedAssessmentId);
        const boundaryVisible = isQuiz
            && assessmentState.mode === 'edit'
            && Boolean(assessmentState.selectedAssessmentId)
            && !assessmentState.loadingAssessmentId
            && assessmentState.hasLearnerRecords;
        setAssessmentAttemptBoundary(boundaryVisible);
        const save = document.getElementById('saveAssessmentButton');
        if (save) save.disabled = Boolean(assessmentState.loadingAssessmentId);
        updateModeLabel();
        updateAvailabilityContext();
        updateAssessmentSummary();
    }

    function initializeNewAssessment(type) {
        cancelAssessmentLoad();
        Object.assign(
            assessmentState,
            window.AssessmentBuilderUtils.startNewAssessment(assessmentState, type)
        );
        assessmentState.templateBusyUntil = 0;
        assessmentState.requestController = null;
        lwLastQuestionAddAt = 0;
        const form = document.getElementById('assessmentForm');
        const method = document.getElementById('assessmentMethod');
        form.action = form.dataset.storeUrl;
        method.disabled = true; method.value = 'PATCH';
        form.reset();
        document.getElementById('assessmentType').value = assessmentState.type;
        document.getElementById('questionList').innerHTML = '';
        questionIndex = 0;
        lwImportRows = [];
        const importInput = document.getElementById('questionCsvInput');
        if (importInput) importInput.value = '';
        const custom = document.getElementById('customTemplateModal');
        if (custom) custom.classList.add('hidden');
        const errorBox = document.getElementById('assessmentFormError');
        if (errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }
        renderAssessmentWorkspace();
        refreshQuestionNavigator(); updateAssessmentSummary();
        lwSyncDateConstraints();
        captureAssessmentState();
    }

    function updateModeLabel() {
        const label = document.getElementById('modeLabel');
        if (!label) return;
        if (assessmentState.mode === 'edit' && assessmentState.selectedAssessmentId) {
            const select = document.getElementById('editQuizSelect');
            const opt = select ? select.options[select.selectedIndex] : null;
            const name = opt && opt.value ? opt.textContent : 'saved assessment';
            label.textContent = 'Editing: ' + name;
        } else {
            label.textContent = assessmentState.type === 'submission'
                ? 'Creating new submission task'
                : 'Creating new quiz';
        }
    }

    function resetAssessmentBuilder(skipConfirm) {
        const reset = function () {
            initializeNewAssessment(assessmentState.type);
            syncAssessmentPageUrl();
        };
        if (!skipConfirm && isAssessmentDirty()) {
            requestAssessmentDiscard('Start a new assessment?').then(function (confirmed) {
                if (confirmed) reset();
            });
            return;
        }
        reset();
    }

    function populateAssessmentBuilder(data) {
        if (!data || !data.assessment_id) return;
        assessmentState.type = data.type === 'submission' ? 'submission' : 'quiz';
        assessmentState.mode = 'edit';
        assessmentState.selectedAssessmentId = String(data.assessment_id);
        assessmentState.hasLearnerRecords = assessmentState.type === 'quiz' && Boolean(data.has_learner_records);
        assessmentState.questions = [];
        const form = document.getElementById('assessmentForm');
        const method = document.getElementById('assessmentMethod');
        const errorBox = document.getElementById('assessmentFormError');
        if (errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }
        form.action = data.update_url;
        method.disabled = false; method.value = 'PATCH';
        form.querySelector('[name="title"]').value = data.title || '';
        form.querySelector('[name="type"]').value = assessmentState.type;
        form.querySelector('[name="instruction_text"]').value = data.instruction_text || '';
        form.querySelector('[name="available_from"]').value = data.available_from_input || '';
        form.querySelector('[name="available_until"]').value = data.available_until_input || '';
        form.querySelector('[name="time_limit_minutes"]').value = data.time_limit_minutes || '';
        const wp = form.querySelector('[name="weight_percent"]');
        if (wp) wp.value = (data.weight_percent !== undefined && data.weight_percent !== null) ? data.weight_percent : '';
        const hidden = document.getElementById('hiddenTotalMarks');
        if (hidden) hidden.value = data.total_marks || 0;
        const ma = form.querySelector('[name="max_attempts"]'); if (ma) ma.value = data.max_attempts || '';
        const af = form.querySelector('[name="accepted_format"]'); if (af) af.value = data.accepted_format || '';
        document.getElementById('questionList').innerHTML = '';
        questionIndex = 0;
        renderAssessmentWorkspace();
        if (Array.isArray(data.questions) && data.questions.length > 0) {
            addQuestionsBatch(data.questions, { expandLast: false, skipDirty: true, loadingExisting: true });
            const first = document.querySelector('#questionList .lw-assess-question');
            if (first) setQuestionExpanded(first, true);
        }
        renderAssessmentWorkspace();
        refreshQuestionNavigator(); updateAssessmentSummary();
        updateAvailabilityContext();
        lwSyncDateConstraints();
        updateModeLabel();
        syncAssessmentPageUrl();
    }

    async function loadAssessmentForEdit(select) {
        if (!select.value) return;
        const opt = select.options[select.selectedIndex];
        const requestedId = String(select.value);
        const aType = opt.dataset.assessmentType || assessmentState.type;
        if (aType !== assessmentState.type) return;
        if (isAssessmentDirty()) {
            const confirmed = await requestAssessmentDiscard('Load another assessment?');
            if (!confirmed) {
                select.value = assessmentState.selectedAssessmentId || '';
                return;
            }
        }
        cancelAssessmentLoad();
        Object.assign(
            assessmentState,
            window.AssessmentBuilderUtils.beginAssessmentLoad(assessmentState, requestedId)
        );
        setAssessmentAttemptBoundary(false);
        assessmentState.requestController = new AbortController();
        const token = assessmentState.requestToken;
        setAssessmentLoading(requestedId);
        try {
            const response = await fetch(opt.dataset.loadUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: assessmentState.requestController.signal
            });
            const body = await response.json();
            if (token !== assessmentState.requestToken) return;
            if (!response.ok || !body.success) throw new Error(body.message || 'Assessment could not be loaded.');
            if ((body.assessment.type === 'submission' ? 'submission' : 'quiz') !== assessmentState.type) {
                throw new Error('The selected assessment type does not match this workspace.');
            }
            assessmentBuilderCache[requestedId] = body.assessment;
            Object.assign(
                assessmentState,
                window.AssessmentBuilderUtils.assessmentLoadSucceeded(
                    assessmentState,
                    body.assessment,
                    token
                )
            );
            populateAssessmentBuilder(body.assessment);
            captureAssessmentState();
        } catch (error) {
            if (error.name !== 'AbortError' && token === assessmentState.requestToken) {
                initializeNewAssessment(assessmentState.type);
                select.value = '';
                syncAssessmentPageUrl();
                showToast(error.message || 'Assessment could not be loaded.', 'error');
            }
        } finally {
            if (token === assessmentState.requestToken) {
                assessmentState.requestController = null;
                setAssessmentLoading(null);
                renderAssessmentWorkspace();
            }
        }
    }

    function discardAssessmentChanges() {
        if (assessmentState.mode === 'edit' && assessmentState.selectedAssessmentId) {
            const data = assessmentBuilderCache[String(assessmentState.selectedAssessmentId)];
            if (data) populateAssessmentBuilder(data);
        } else { initializeNewAssessment(assessmentState.type); }
        captureAssessmentState();
    }

    const customMixTypes = ['mcq', 'checkbox', 'true_false', 'short_answer', 'essay'];

    function customMixRowMarkup(type, count) {
        const options = customMixTypes.map(function (value) {
            const label = window.AssessmentBuilderUtils.QUESTION_TYPE_LABELS[value];
            return '<option value="' + value + '"' + (value === type ? ' selected' : '') + '>' + label + '</option>';
        }).join('');
        return '<div class="lw-custom-mix-row" data-custom-mix-row>' +
            '<div class="lw-field"><label>Question Type</label><select data-custom-mix-type>' + options + '</select></div>' +
            '<div class="lw-field"><label>Count</label><input type="number" min="1" max="100" step="1" value="' + count + '" data-custom-mix-count></div>' +
            '<button type="button" class="lw-question-icon-btn" data-remove-custom-mix-row aria-label="Remove mix row" title="Remove row"><i class="bi bi-trash"></i></button>' +
        '</div>';
    }

    function currentCustomMixRows() {
        return Array.from(document.querySelectorAll('#customMixRows [data-custom-mix-row]')).map(function (row) {
            return {
                question_type: row.querySelector('[data-custom-mix-type]').value,
                count: row.querySelector('[data-custom-mix-count]').value
            };
        });
    }

    function updateCustomQuestionMix() {
        const rows = Array.from(document.querySelectorAll('#customMixRows [data-custom-mix-row]'));
        const selected = rows.map(function (row) { return row.querySelector('[data-custom-mix-type]').value; });
        rows.forEach(function (row) {
            const own = row.querySelector('[data-custom-mix-type]').value;
            row.querySelectorAll('option').forEach(function (option) {
                option.disabled = option.value !== own && selected.includes(option.value);
            });
            const remove = row.querySelector('[data-remove-custom-mix-row]');
            if (remove) remove.disabled = rows.length <= 1;
        });
        const mix = window.AssessmentBuilderUtils.deriveCustomQuestionMix(
            currentCustomMixRows(),
            assessmentState.questions.length
        );
        const total = document.getElementById('customMixTotal');
        const add = document.getElementById('applyCustomTemplate');
        const addRow = document.getElementById('addCustomMixRow');
        if (total) total.textContent = mix.total;
        if (add) {
            add.disabled = mix.total < 1 || mix.errors.some(function (error) {
                return error !== 'question_limit';
            });
            add.textContent = 'Add ' + mix.total + ' Question' + (mix.total === 1 ? '' : 's');
        }
        if (addRow) addRow.disabled = rows.length >= customMixTypes.length;
        return mix;
    }

    function appendCustomMixRow(type, count) {
        const container = document.getElementById('customMixRows');
        if (!container) return;
        const used = currentCustomMixRows().map(function (row) { return row.question_type; });
        const selectedType = type || customMixTypes.find(function (candidate) { return !used.includes(candidate); });
        if (!selectedType) return;
        container.insertAdjacentHTML('beforeend', customMixRowMarkup(selectedType, count || 10));
        updateCustomQuestionMix();
    }

    function resetCustomQuestionMix() {
        const container = document.getElementById('customMixRows');
        if (!container) return;
        container.innerHTML = customMixRowMarkup('mcq', 10);
        updateCustomQuestionMix();
    }

    async function applyCustomQuestionMix() {
        if (assessmentState.hasLearnerRecords || assessmentState.type !== 'quiz') {
            showToast('Questions are locked because learner attempts already exist.', 'error');
            return { success: false, addedCount: 0 };
        }
        const now = performance.now();
        if (now < assessmentState.templateBusyUntil) return { success: false, addedCount: 0 };
        const mix = window.AssessmentBuilderUtils.deriveCustomQuestionMix(
            currentCustomMixRows(),
            assessmentState.questions.length
        );
        if (mix.errors.includes('question_limit')) {
            showToast('A quiz can contain at most 100 questions. You can add up to ' + mix.remaining + ' more.', 'error');
            return { success: false, addedCount: 0 };
        }
        if (!mix.valid) {
            showToast(mix.errors[0] || 'Add at least one valid question mix row.', 'error');
            return { success: false, addedCount: 0 };
        }
        assessmentState.templateBusyUntil = now + 350;
        const questions = window.AssessmentBuilderUtils.questionsForCustomMix(mix.rows);
        return applyQuestionBatch(questions);
    }

    function applyQuestionBatch(questions) {
        if (!questions.length) return { success: false, addedCount: 0 };
        if (assessmentState.questions.length + questions.length > 100) {
            const remaining = Math.max(0, 100 - assessmentState.questions.length);
            showToast('A quiz can contain at most 100 questions. You can add up to ' + remaining + ' more.', 'error');
            return { success: false, addedCount: 0 };
        }
        const result = addQuestionsBatch(questions, { expandLast: false, skipDirty: false });
        if (result.success) {
            showToast(result.addedCount + ' question' + (result.addedCount === 1 ? '' : 's') + ' added.', 'success');
        }
        return result;
    }

    async function applyTemplate(name) {
        if (assessmentState.hasLearnerRecords || assessmentState.type !== 'quiz') {
            showToast('Questions are locked because learner attempts already exist.', 'error');
            return { success: false, addedCount: 0 };
        }
        const now = performance.now();
        if (now < assessmentState.templateBusyUntil) return { success: false, addedCount: 0 };
        const questions = window.AssessmentBuilderUtils.questionsForTemplate(name);
        if (questions.length === 0) return { success: false, addedCount: 0 };
        if (assessmentState.questions.length + questions.length > 100) {
            showToast('A quiz can contain at most 100 questions. You can add up to ' + Math.max(0, 100 - assessmentState.questions.length) + ' more.', 'error');
            return { success: false, addedCount: 0 };
        }
        assessmentState.templateBusyUntil = now + 350;
        return applyQuestionBatch(questions);
    }

    /* ============ Filled-state helpers (used by delete + template confirm gating) ============ */
    function isQuestionFilled(card) {
        if (!card) return false;
        const text = card.querySelector('[name$="[question_text]"]');
        if (text && text.value.trim()) return true;
        const choices = card.querySelectorAll('.lw-choice-text');
        for (const c of choices) {
            const v = c.value.trim();
            if (v && !/^Option\s+\d+$/i.test(v)) return true;
        }
        const ca = card.querySelectorAll('[name$="[correct_answer]"], [name$="[grading_rubric]"]');
        for (const el of ca) { if (el.value && el.value.trim()) return true; }
        const tfChecked = card.querySelector('.choice-correct:checked');
        if (tfChecked) return true;
        const fileInput = card.querySelector('input[type="file"]');
        if (fileInput && fileInput.files && fileInput.files.length > 0) return true;
        const marks = card.querySelector('.lw-stepper-input');
        if (marks && parseFloat(marks.value) !== 0) return true;
        return false;
    }
    /* ============ Question building (lw- styled) ============ */
    async function removeQuestionCard(button) {
        const card = button.closest('.lw-assess-question');
        if (!card) return;
        if (assessmentState.hasLearnerRecords) return;
        if (isQuestionFilled(card)) {
            const confirmed = await requestMaterialsConfirmation({
                title: 'Delete question?',
                message: 'This populated question and its answers will be removed from the unsaved quiz.',
                confirmLabel: 'Delete Question',
                destructive: true
            });
            if (!confirmed) return;
            assessmentState.dirty = true;
        }
        const index = Array.from(card.parentElement.children).indexOf(card);
        Object.assign(assessmentState, window.AssessmentBuilderUtils.removeQuestionFromState(assessmentState, index));
        card.remove();
        renumberQuestionCards(); refreshQuestionNavigator(); updateAssessmentSummary();
    }
    function renumberQuestionCards() {
        const cards = document.querySelectorAll('#questionList .lw-assess-question');
        cards.forEach(function (card, visibleIndex) {
            const newIndex = visibleIndex;
            card.dataset.questionIndex = newIndex;
            card.id = 'questionCard' + newIndex;
            card.querySelectorAll('[name^="questions["]').forEach(function (field) {
                field.name = field.name.replace(/questions\[\d+\]/, 'questions[' + newIndex + ']');
            });
            const orderInput = card.querySelector('.question-order-input');
            if (orderInput) orderInput.value = visibleIndex + 1;
            card.querySelectorAll('input[type="radio"][name^="correct_choice_"]').forEach(function (r) { r.name = 'correct_choice_' + newIndex; });
            card.querySelectorAll('input[type="checkbox"][name^="correct_choice_"]').forEach(function (c) { c.name = 'correct_choice_' + newIndex + '[]'; });
            const editor = card.querySelector('.choice-editor');
            if (editor) {
                editor.dataset.questionIndex = newIndex;
                if (card.dataset.questionType === 'mcq' || card.dataset.questionType === 'checkbox') {
                    renumberChoiceRows(editor);
                    syncChoiceData(editor);
                }
            }
            const qnum = card.querySelector('.lw-assess-question-num');
            if (qnum) qnum.textContent = 'Q ' + (visibleIndex + 1);
            const preview = card.querySelector('.lw-question-image-preview');
            if (preview) { preview.id = 'qimgPreview_' + newIndex; const fi = card.querySelector('input[type="file"][data-preview-id]'); if (fi) fi.dataset.previewId = 'qimgPreview_' + newIndex; }
        });
        questionIndex = cards.length;
    }
    function scrollToQuestion(index) {
        const card = document.getElementById('questionCard' + index);
        if (card) {
            setQuestionExpanded(card, true);
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            card.classList.add('ring-highlight');
            setTimeout(function () { card.classList.remove('ring-highlight'); }, 900);
        }
    }
    function refreshQuestionNavigator() {
        const nav = document.getElementById('questionNavigator');
        if (!nav) return;
        const cards = document.querySelectorAll('#questionList .lw-assess-question');
        const collection = window.AssessmentBuilderUtils.deriveQuestionCollectionState(assessmentState);
        nav.innerHTML = '';
        if (collection.count === 0) { nav.innerHTML = '<p class="lw-question-jump-empty">No questions yet.</p>'; return; }
        cards.forEach(function (card, visibleIndex) {
            const index = card.dataset.questionIndex;
            const type = (card.dataset.questionType || 'question').replace('_', ' ');
            const qnum = card.querySelector('.lw-assess-question-num');
            if (qnum) qnum.textContent = 'Q ' + (visibleIndex + 1);
            const orderInput = card.querySelector('.question-order-input');
            if (orderInput) orderInput.value = visibleIndex + 1;
            const status = updateQuestionCardState(card);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lw-question-jump-btn';
            btn.dataset.questionJumpIndex = index;
            btn.innerHTML = '<span class="lw-question-jump-btn-num">Q ' + (visibleIndex + 1) + '</span><span class="lw-question-jump-btn-type">' + type + '</span><span class="lw-question-jump-btn-status ' + (status.complete ? 'is-complete' : '') + '">' + (status.complete ? 'Complete' : 'Needs attention') + '</span>';
            btn.onclick = function () { scrollToQuestion(index); };
            nav.appendChild(btn);
        });
    }

    function questionDataFromCard(card) {
        const index = card.dataset.questionIndex;
        const type = card.dataset.questionType;
        const text = card.querySelector('[name="questions[' + index + '][question_text]"]');
        const marks = card.querySelector('[name="questions[' + index + '][marks]"]');
        const data = {
            question_type: type,
            question_text: text ? text.value : '',
            marks: marks ? marks.value : 0,
            options: [],
            correct_answer: '',
            correct_answers: [],
            grading_rubric: ''
        };
        if (type === 'mcq' || type === 'checkbox') {
            card.querySelectorAll('.lw-choice-row').forEach(function (row) {
                const value = row.querySelector('.choice-text').value.trim();
                data.options.push(value);
                if (row.querySelector('.choice-correct').checked) data.correct_answers.push(value);
            });
            data.correct_answer = data.correct_answers[0] || '';
        } else if (type === 'true_false' || type === 'short_answer') {
            const answer = card.querySelector('[name="questions[' + index + '][correct_answer]"]');
            data.correct_answer = answer ? answer.value : '';
            if (type === 'true_false') data.options = ['True', 'False'];
        } else if (type === 'essay') {
            const rubric = card.querySelector('[name="questions[' + index + '][grading_rubric]"]');
            data.grading_rubric = rubric ? rubric.value : '';
        }
        return data;
    }

    function updateCanonicalQuestionFromCard(card) {
        if (!card || assessmentState.type !== 'quiz') return;
        const list = card.parentElement;
        if (!list) return;
        const position = Array.from(list.children).indexOf(card);
        if (position < 0 || position >= assessmentState.questions.length) return;
        assessmentState.questions[position] = window.AssessmentBuilderUtils.normalizeQuestion(questionDataFromCard(card));
    }

    function syncCanonicalQuestionOrderFromCards() {
        if (assessmentState.type !== 'quiz') {
            assessmentState.questions = [];
            return;
        }
        assessmentState.questions = Array.from(
            document.querySelectorAll('#questionList .lw-assess-question')
        ).map(function (card) {
            return window.AssessmentBuilderUtils.normalizeQuestion(questionDataFromCard(card));
        });
    }

    function questionValidationState(card) {
        const data = questionDataFromCard(card);
        return window.AssessmentBuilderUtils.validateQuestion(data);
    }

    function updateQuestionCardState(card) {
        const state = questionValidationState(card);
        const status = card.querySelector('.lw-question-status');
        const preview = card.querySelector('.lw-question-preview');
        const text = card.querySelector('[name$="[question_text]"]');
        if (status) {
            status.textContent = state.complete ? 'Complete' : 'Needs attention';
            status.classList.toggle('is-complete', state.complete);
        }
        if (preview) preview.textContent = text && text.value.trim() ? text.value.trim() : 'New question';
        card.classList.toggle('is-complete', state.complete);
        const guidance = card.querySelector('.lw-question-guidance');
        if (guidance) {
            guidance.hidden = state.complete;
            guidance.innerHTML = state.complete
                ? ''
                : '<ul>' + state.errors.map(function (error) { return '<li>' + escapeHtml(error) + '</li>'; }).join('') + '</ul>';
        }
        return state;
    }

    function setQuestionExpanded(card, expanded) {
        if (!card) return;
        if (expanded) {
            document.querySelectorAll('#questionList .lw-assess-question').forEach(function (candidate) {
                if (candidate !== card) setQuestionExpanded(candidate, false);
            });
        }
        card.classList.toggle('is-collapsed', !expanded);
        const button = card.querySelector('.lw-question-toggle');
        if (button) {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            button.title = expanded ? 'Collapse question' : 'Expand question';
            button.innerHTML = '<i class="bi ' + (expanded ? 'bi-chevron-up' : 'bi-chevron-down') + '"></i>';
        }
    }

    function duplicateQuestionCard(card) {
        if (!card || assessmentState.hasLearnerRecords) return;
        const duplicate = questionDataFromCard(card);
        const duplicatedCard = addQuestion(duplicate.question_type, duplicate);
        if (duplicatedCard) {
            showToast('Question duplicated. Add an image separately if needed.', 'success');
            duplicatedCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function addQuestionsBatch(questions, options) {
        options = options || {};
        if (!options.loadingExisting && assessmentState.questions.length + questions.length > 100) {
            showToast('A quiz can contain at most 100 questions.', 'error');
            return { success: false, addedCount: 0, cards: [] };
        }
        const created = [];
        questions.forEach(function (question) {
            const data = question || {};
            const card = addQuestion(
                data.question_type || 'mcq',
                data,
                options.skipDirty,
                null,
                true,
                false,
                options.loadingExisting
            );
            if (card) created.push(card);
        });
        renumberQuestionCards();
        refreshQuestionNavigator();
        updateAssessmentSummary();
        if (options.expandLast && created.length > 0) setQuestionExpanded(created.at(-1), true);
        if (!options.skipDirty && created.length > 0) assessmentState.dirty = true;
        return { success: created.length === questions.length && created.length > 0, addedCount: created.length, cards: created };
    }

    function addQuestion(type, existingData, skipDirty, insertAfterCard, deferRefresh, expandQuestion, allowLockedRender) {
        if ((assessmentState.hasLearnerRecords && !allowLockedRender) || assessmentState.type !== 'quiz') return null;
        type = window.AssessmentBuilderUtils.normalizeQuestionType(type);
        if (!type) {
            showToast('Choose a supported question type.', 'error');
            return null;
        }
        if (!skipDirty && assessmentState.questions.length >= 100) {
            showToast('A quiz can contain at most 100 questions.', 'error');
            return null;
        }
        if (!existingData && !deferRefresh) {
            const now = performance.now();
            if (lwLastQuestionAddAt && now - lwLastQuestionAddAt < 300) return null;
            lwLastQuestionAddAt = now;
        }
        existingData = existingData
            ? window.AssessmentBuilderUtils.normalizeQuestion(existingData)
            : null;
        const list = document.getElementById('questionList');
        const index = questionIndex; const number = questionIndex + 1;
        questionIndex += 1;
        let typeLabel = 'Multiple Choice', typeClass = 'mcq';
        let extraBody = '';
        if (type === 'mcq') extraBody = buildChoiceEditor(index, 'radio', 4);
        else if (type === 'checkbox') { typeLabel = 'Checkbox'; typeClass = 'checkbox'; extraBody = buildChoiceEditor(index, 'checkbox', 4); }
        else if (type === 'true_false') { typeLabel = 'True / False'; typeClass = 'true_false'; extraBody = buildTrueFalseEditor(index); }
        else if (type === 'short_answer') { typeLabel = 'Short Answer'; typeClass = 'short_answer'; extraBody = buildShortAnswerEditor(index); }
        else if (type === 'essay') { typeLabel = 'Essay'; typeClass = 'essay'; extraBody = buildEssayEditor(index); }
        const imgPreviewId = 'qimgPreview_' + index;
        const card = document.createElement('div');
        card.className = 'lw-assess-question is-collapsed';
        card.id = 'questionCard' + index;
        card.dataset.questionIndex = index;
        card.dataset.questionType = type;
        card.innerHTML =
            '<div class="lw-assess-question-head">' +
                '<span class="lw-question-drag" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>' +
                '<span class="lw-assess-question-num">Q ' + number + '</span>' +
                '<span class="lw-assess-question-type-badge ' + typeClass + '">' + typeLabel + '</span>' +
                '<span class="lw-question-preview">New question</span>' +
                '<span class="lw-question-status">Needs attention</span>' +
                '<div class="lw-question-marks-wrap">' +
                    '<span class="lw-question-marks-label">Marks</span>' +
                    '<div class="lw-stepper">' +
                        '<button type="button" class="lw-stepper-btn" onclick="adjustMarks(this, -1)">−</button>' +
                        '<input type="number" name="questions[' + index + '][marks]" value="0" min="0" step="0.5" class="lw-stepper-input">' +
                        '<button type="button" class="lw-stepper-btn" onclick="adjustMarks(this, 1)">+</button>' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="lw-question-icon-btn lw-question-duplicate" title="Duplicate question" aria-label="Duplicate question"><i class="bi bi-copy"></i></button>' +
                '<button type="button" onclick="removeQuestionCard(this)" class="lw-question-remove-btn" title="Remove question"><i class="bi bi-trash"></i></button>' +
                '<button type="button" class="lw-question-icon-btn lw-question-toggle" aria-expanded="false" title="Expand question"><i class="bi bi-chevron-down"></i></button>' +
            '</div>' +
            '<div class="lw-assess-question-body">' +
                '<input type="hidden" name="questions[' + index + '][question_type]" value="' + type + '">' +
                '<input type="hidden" name="questions[' + index + '][question_order]" class="question-order-input" value="' + number + '">' +
                '<div class="lw-field" style="margin-bottom: 0;">' +
                    '<label>Question Text</label>' +
                    '<textarea name="questions[' + index + '][question_text]" rows="2" required placeholder="Type your question here..."></textarea>' +
                '</div>' +
                '<div class="lw-field" style="margin-bottom: 0;">' +
                    '<label>Question Image (optional)</label>' +
                    '<input type="file" name="questions[' + index + '][question_image]" accept="image/jpeg,image/png,image/webp" data-preview-id="' + imgPreviewId + '" onchange="lwHandleQuestionImagePick(this)">' +
                    '<div class="lw-field-hint">Add an image learners should reference.</div>' +
                    '<div class="lw-question-image-name" aria-live="polite"></div>' +
                    '<div class="lw-editor-title-error lw-question-image-error" aria-live="polite"></div>' +
                    '<div id="' + imgPreviewId + '" class="lw-question-image-preview"></div>' +
                '</div>' +
                extraBody +
                '<div class="lw-question-guidance" role="status"></div>' +
                '<div class="lw-question-insert">' +
                    '<button type="button" class="lw-btn ghost sm lw-question-insert-toggle"><i class="bi bi-plus-lg"></i> Add question below</button>' +
                    '<div class="lw-question-insert-menu hidden">' +
                        '<button type="button" data-insert-question-type="mcq">Multiple Choice</button>' +
                        '<button type="button" data-insert-question-type="checkbox">Checkbox</button>' +
                        '<button type="button" data-insert-question-type="true_false">True / False</button>' +
                        '<button type="button" data-insert-question-type="short_answer">Short Answer</button>' +
                        '<button type="button" data-insert-question-type="essay">Essay</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        if (insertAfterCard && insertAfterCard.parentNode === list) {
            insertAfterCard.insertAdjacentElement('afterend', card);
        } else list.appendChild(card);
        if (existingData) fillQuestionCard(card, existingData);
        const insertAt = insertAfterCard && insertAfterCard.parentNode === list
            ? Array.from(list.children).indexOf(card)
            : assessmentState.questions.length;
        const dirtyBeforeAdd = assessmentState.dirty;
        const stateResult = window.AssessmentBuilderUtils.addQuestionsToState(
            assessmentState,
            [questionDataFromCard(card)],
            insertAt
        );
        if (!stateResult.success) {
            card.remove();
            return null;
        }
        Object.assign(assessmentState, stateResult.state);
        if (skipDirty) assessmentState.dirty = dirtyBeforeAdd;
        updateQuestionCardState(card);
        if (expandQuestion !== false) setQuestionExpanded(card, true);
        if (!deferRefresh) { renumberQuestionCards(); refreshQuestionNavigator(); updateAssessmentSummary(); }
        if (!skipDirty) {
            assessmentState.dirty = true;
            if (!deferRefresh) {
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                const text = card.querySelector('[name$="[question_text]"]');
                if (text) text.focus({ preventScroll: true });
            }
        }
        return card;
    }

    function fillQuestionCard(card, data) {
        const index = card.dataset.questionIndex;
        if (data.question_id) {
            const questionId = document.createElement('input');
            questionId.type = 'hidden';
            questionId.name = 'questions[' + index + '][question_id]';
            questionId.value = data.question_id;
            card.querySelector('.lw-assess-question-body').appendChild(questionId);
        }
        const questionText = card.querySelector('[name="questions[' + index + '][question_text]"]');
        const marks = card.querySelector('[name="questions[' + index + '][marks]"]');
        if (questionText) questionText.value = data.question_text || '';
        if (marks) marks.value = (data.marks !== undefined && data.marks !== null) ? data.marks : 0;
        const existingImage = data.question_image_url || '';
        if (existingImage) {
            const preview = card.querySelector('.lw-question-image-preview');
            const imageName = card.querySelector('.lw-question-image-name');
            if (imageName) imageName.textContent = 'Existing image attached';
            if (preview) {
                preview.innerHTML = '';
                const image = document.createElement('img');
                image.src = existingImage;
                image.alt = 'Question image';
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'lw-inline-action danger';
                removeButton.innerHTML = '<i class="bi bi-trash"></i> Remove image';
                removeButton.addEventListener('click', function () {
                    removeQuestionImage(removeButton);
                });
                preview.appendChild(image);
                preview.appendChild(removeButton);
                preview.classList.add('show');
            }
        }
        if (['mcq', 'checkbox'].includes(data.question_type)) {
            let options = data.options || []; let correctAnswers = data.correct_answers || [];
            const editor = card.querySelector('.choice-editor');
            const list = editor ? editor.querySelector('.lw-choice-list') : null;
            if (list && options.length > 0) {
                list.innerHTML = '';
                options.forEach(function (optionText, choiceIndex) {
                    list.insertAdjacentHTML('beforeend', buildChoiceRow(index, data.question_type === 'checkbox' ? 'checkbox' : 'radio', choiceIndex));
                    const row = list.lastElementChild;
                    row.querySelector('.choice-text').value = optionText;
                    row.querySelector('.choice-correct').checked = correctAnswers.includes(optionText);
                });
                renumberChoiceRows(editor); syncChoiceData(editor);
            }
        }
        if (data.question_type === 'true_false') {
            const value = data.correct_answer || '';
            const radio = card.querySelector('.choice-correct[value="' + value + '"]');
            if (radio) { radio.checked = true; syncTrueFalseData(radio); }
        }
        if (data.question_type === 'short_answer') {
            const answer = card.querySelector('[name="questions[' + index + '][correct_answer]"]');
            if (answer) answer.value = data.correct_answer || '';
        }
        if (data.question_type === 'essay') {
            const rubric = card.querySelector('[name="questions[' + index + '][grading_rubric]"]');
            if (rubric) rubric.value = data.grading_rubric || '';
        }
    }

    function buildChoiceEditor(index, mode, defaultCount) {
        let choicesHtml = '';
        for (let i = 0; i < defaultCount; i++) choicesHtml += buildChoiceRow(index, mode, i);
        return '<div class="choice-editor" data-question-index="' + index + '" data-mode="' + mode + '">' +
            '<div class="lw-choice-list choice-list">' + choicesHtml + '</div>' +
            '<button type="button" onclick="addChoiceRow(this)" class="lw-choice-add"><i class="bi bi-plus-lg"></i> Add Choice</button>' +
            '<input type="hidden" name="questions[' + index + '][options_json]" class="options-json">' +
            '<input type="hidden" name="questions[' + index + '][correct_answer]" class="correct-answer">' +
            '<input type="hidden" name="questions[' + index + '][correct_answers_json]" class="correct-answers-json">' +
        '</div>';
    }
    function buildChoiceRow(index, mode, choiceIndex) {
        const inputType = mode === 'checkbox' ? 'checkbox' : 'radio';
        const optionText = 'Option ' + (choiceIndex + 1);
        const name = mode === 'checkbox' ? 'correct_choice_' + index + '[]' : 'correct_choice_' + index;
        return '<div class="lw-choice-row choice-row">' +
            '<input type="' + inputType + '" name="' + name + '" class="choice-correct lw-choice-correct" onchange="syncChoiceData(this)">' +
            '<input type="text" value="" placeholder="' + optionText + '" class="choice-text lw-choice-text" oninput="syncChoiceData(this)">' +
            '<button type="button" onclick="removeChoiceRow(this)" class="lw-choice-remove" title="Remove choice">×</button>' +
        '</div>';
    }
    function addChoiceRow(button) {
        const editor = button.closest('.choice-editor');
        const index = editor.dataset.questionIndex;
        const mode = editor.dataset.mode;
        const count = editor.querySelectorAll('.lw-choice-row').length;
        editor.querySelector('.lw-choice-list').insertAdjacentHTML('beforeend', buildChoiceRow(index, mode, count));
        renumberChoiceRows(editor); syncChoiceData(editor);
        const card = editor.closest('.lw-assess-question');
        if (card) updateQuestionJumpState(card);
        assessmentState.dirty = true;
    }
    function removeChoiceRow(button) {
        const editor = button.closest('.choice-editor');
        if (editor.querySelectorAll('.lw-choice-row').length <= 2) {
            showToast('Multiple Choice and Checkbox questions need at least two choices.', 'error');
            return;
        }
        button.closest('.lw-choice-row').remove();
        renumberChoiceRows(editor); syncChoiceData(editor);
        const card = editor.closest('.lw-assess-question');
        if (card) updateQuestionJumpState(card);
        assessmentState.dirty = true;
    }
    function renumberChoiceRows(editor) {
        const rows = editor.querySelectorAll('.lw-choice-row');
        rows.forEach(function (row, visibleIndex) {
            const textInput = row.querySelector('.choice-text');
            if (textInput) textInput.placeholder = 'Option ' + (visibleIndex + 1);
        });
    }
    function syncChoiceData(element) {
        const editor = element.closest('.choice-editor') || element;
        const rows = editor.querySelectorAll('.lw-choice-row');
        const options = []; const correctAnswers = [];
        rows.forEach(function (row) {
            const text = row.querySelector('.choice-text').value.trim();
            const correct = row.querySelector('.choice-correct').checked;
            options.push(text);
            if (correct) correctAnswers.push(text);
        });
        editor.querySelector('.options-json').value = JSON.stringify(options);
        const singleCorrect = correctAnswers.length > 0 ? correctAnswers[0] : '';
        editor.querySelector('.correct-answer').value = singleCorrect;
        const multiCorrect = editor.querySelector('.correct-answers-json');
        if (multiCorrect) multiCorrect.value = JSON.stringify(correctAnswers);
    }

    function buildTrueFalseEditor(index) {
        return '<div class="choice-editor lw-tf-options" data-question-index="' + index + '" data-mode="radio">' +
            '<label class="lw-tf-label"><input type="radio" name="correct_choice_' + index + '" class="choice-correct lw-choice-correct" value="True" onchange="syncTrueFalseData(this)"><span class="lw-tf-text">True</span></label>' +
            '<label class="lw-tf-label"><input type="radio" name="correct_choice_' + index + '" class="choice-correct lw-choice-correct" value="False" onchange="syncTrueFalseData(this)"><span class="lw-tf-text">False</span></label>' +
            '<input type="hidden" name="questions[' + index + '][options_json]" value="[&quot;True&quot;,&quot;False&quot;]">' +
            '<input type="hidden" name="questions[' + index + '][correct_answer]" class="true-false-answer">' +
        '</div>';
    }
    function syncTrueFalseData(radio) {
        const editor = radio.closest('.choice-editor');
        editor.querySelector('.true-false-answer').value = radio.value;
    }
    function buildShortAnswerEditor(index) {
        return '<div class="lw-answer-block">' +
            '<label class="lw-answer-block-label">Expected Answer / Keywords</label>' +
            '<textarea name="questions[' + index + '][correct_answer]" rows="2" required placeholder="Example: firewall, packet filter, network security"></textarea>' +
            '<div class="lw-answer-block-hint"><i class="bi bi-info-circle"></i> Auto-graded by keyword match: if the learner\'s answer contains ANY of these keywords (case-insensitive), they earn full marks. Separate keywords with comma or newline.</div>' +
        '</div>';
    }
    function buildEssayEditor(index) {
        return '<div class="lw-answer-block">' +
            '<label class="lw-answer-block-label">Essay Rubric</label>' +
            '<textarea name="questions[' + index + '][grading_rubric]" rows="3" required placeholder="Rubric or marking guide..."></textarea>' +
            '<div class="lw-answer-block-hint"><i class="bi bi-pencil"></i> Cannot be auto-graded. You will manually score each submission using this rubric, up to the question\'s mark value.</div>' +
        '</div>';
    }

    function updateTotalMarksFromQuestions() {
        const total = window.AssessmentBuilderUtils
            .deriveQuestionCollectionState(assessmentState).totalMarks;
        const hidden = document.getElementById('hiddenTotalMarks');
        if (hidden) hidden.value = total;
        const display = document.getElementById('totalMarksDisplay');
        if (display) display.textContent = (total % 1 === 0) ? total : total.toFixed(2);
    }

    /* ============ Validation ============ */
    function validateAssessmentBeforeSubmit(form) {
        const type = document.getElementById('assessmentType').value;
        const title = form.querySelector('[name="title"]');
        if (!title || !title.value.trim()) { showToast('Please enter an assessment title.', 'error'); if (title) title.focus(); return false; }
        title.value = title.value.trim();
        const from = form.querySelector('[name="available_from"]');
        const until = form.querySelector('[name="available_until"]');
        if (from && until && from.value && until.value && until.value <= from.value) { showToast('Available Until must be after Available From.', 'error'); until.focus(); return false; }

        if (type === 'submission') {
            const instructions = form.querySelector('[name="instruction_text"]');
            if (!instructions || !instructions.value.trim()) {
                showToast('Add clear instructions for the submission task.', 'error');
                if (instructions) instructions.focus();
                return false;
            }
            // Submission Task: there are no questions, so strip the question payload
            // and clear quiz-specific fields. Available Until acts as the deadline.
            const list = document.getElementById('questionList');
            if (list) list.innerHTML = '';
            ['total_marks', 'time_limit_minutes', 'passing_grade', 'weight_percent', 'max_attempts'].forEach(function (n) {
                const el = form.querySelector('[name="' + n + '"]');
                if (el) el.value = '';
            });
            return true;
        }

        // Attempted quizzes use a metadata-only patch. Their question and
        // scoring controls stay readable but are not part of this save.
        if (assessmentState.mode === 'edit' && assessmentState.hasLearnerRecords) {
            return true;
        }

        // Quiz: auto-set total_marks from question marks
        updateTotalMarksFromQuestions();

        const cards = document.querySelectorAll('#questionList .lw-assess-question');
        if (cards.length === 0) {
            if (assessmentState.mode === 'edit' && !assessmentState.hasLearnerRecords) return true;
            showToast('Please add at least one quiz question.', 'error');
            return false;
        }
        cards.forEach(function (card) {
            const editor = card.querySelector('.choice-editor .lw-choice-list')
                ? card.querySelector('.choice-editor')
                : null;
            if (editor) syncChoiceData(editor);
            updateQuestionJumpState(card);
        });
        const invalidCards = Array.from(cards).filter(function (card) {
            return !questionValidationState(card).complete;
        });
        if (invalidCards.length > 0) {
            refreshQuestionNavigator();
            const firstInvalid = invalidCards[0];
            scrollToQuestion(Number(firstInvalid.dataset.questionIndex));
            showToast(
                invalidCards.length + ' question' + (invalidCards.length === 1 ? '' : 's') +
                ' need attention before this quiz can be saved.',
                'error'
            );
            return false;
        }
        for (let visibleIndex = 0; visibleIndex < cards.length; visibleIndex++) {
            const card = cards[visibleIndex];
            const index = card.dataset.questionIndex;
            const questionType = card.dataset.questionType;
            const questionText = card.querySelector('[name="questions[' + index + '][question_text]"]');
            const marks = card.querySelector('[name="questions[' + index + '][marks]"]');
            if (!questionText || !questionText.value.trim()) { showToast('Please fill Question ' + (visibleIndex + 1) + ' text.', 'error'); if (questionText) questionText.focus(); scrollToQuestion(index); return false; }
            if (!marks || !Number.isFinite(Number(marks.value))) { showToast('Question ' + (visibleIndex + 1) + ' marks must be a valid number.', 'error'); if (marks) marks.focus(); scrollToQuestion(index); return false; }
            if (Number(marks.value) < 0) { showToast('Question ' + (visibleIndex + 1) + ' marks cannot be negative.', 'error'); marks.focus(); scrollToQuestion(index); return false; }
            if (questionType === 'mcq' || questionType === 'checkbox') {
                const editor = card.querySelector('.choice-editor');
                if (editor) syncChoiceData(editor);
                const rows = card.querySelectorAll('.lw-choice-row');
                let filled = 0, correct = 0;
                const distinctChoices = new Set();
                rows.forEach(function (row) {
                    const choiceText = row.querySelector('.choice-text').value.trim();
                    if (choiceText) {
                        filled++;
                        distinctChoices.add(choiceText);
                    }
                    if (row.querySelector('.choice-correct').checked) correct++;
                });
                if (filled < 2 || distinctChoices.size < 2) { showToast('Question ' + (visibleIndex + 1) + ' needs at least 2 different choices.', 'error'); scrollToQuestion(index); return false; }
                if ((questionType === 'mcq' && correct !== 1) || (questionType === 'checkbox' && correct < 1)) { showToast('Please choose the correct answer for Question ' + (visibleIndex + 1) + '.', 'error'); scrollToQuestion(index); return false; }
            }
            if (questionType === 'true_false') {
                const answer = card.querySelector('[name="questions[' + index + '][correct_answer]"]');
                if (!answer || !answer.value.trim()) { showToast('Please choose True or False for Question ' + (visibleIndex + 1) + '.', 'error'); scrollToQuestion(index); return false; }
            }
            if (questionType === 'short_answer') {
                const answer = card.querySelector('[name="questions[' + index + '][correct_answer]"]');
                if (!answer || !answer.value.trim()) { showToast('Please fill at least one keyword for Question ' + (visibleIndex + 1) + '.', 'error'); if (answer) answer.focus(); scrollToQuestion(index); return false; }
                const keywords = answer.value.split(/[\n,;]+/).map(function (k) { return k.trim(); }).filter(function (k) { return k.length > 0; });
                if (keywords.length < 1) { showToast('Please fill at least one valid keyword for Question ' + (visibleIndex + 1) + '.', 'error'); answer.focus(); return false; }
                answer.value = keywords.join(', ');
            }
            if (questionType === 'essay') {
                const rubric = card.querySelector('[name="questions[' + index + '][grading_rubric]"]');
                if (!rubric || !rubric.value.trim()) { showToast('Please fill the essay rubric for Question ' + (visibleIndex + 1) + '.', 'error'); if (rubric) rubric.focus(); scrollToQuestion(index); return false; }
            }
        }
        return true;
    }

    /* ============ Reset Cover helpers (simplified — no Undo button) ============ */
    function lwToggleCoverReset(moduleId) {
        const flag = document.getElementById('deleteCoverFlag' + moduleId);
        const btn = document.getElementById('coverResetBtn' + moduleId);
        if (!flag || !btn) return;
        if (flag.value === '1') return; // already queued; ignore extra clicks
        requestMaterialsConfirmation({
            title: 'Remove cover image?',
            message: 'The custom image will be deleted when you click Save Changes. Cancel the settings modal to keep it.',
            confirmLabel: 'Remove Image',
            destructive: true
        }).then(function (confirmed) {
            if (confirmed) lwQueueCoverReset(moduleId);
        });
    }
    function lwQueueCoverReset(moduleId) {
        const flag = document.getElementById('deleteCoverFlag' + moduleId);
        const btn = document.getElementById('coverResetBtn' + moduleId);
        if (!flag || !btn) return;
        flag.value = '1';
        btn.disabled = true;
        btn.style.opacity = '0.45';
        btn.style.cursor = 'not-allowed';
    }
    function lwCancelCoverReset(moduleId) {
        const flag = document.getElementById('deleteCoverFlag' + moduleId);
        const btn = document.getElementById('coverResetBtn' + moduleId);
        if (flag) flag.value = '0';
        if (btn) {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor = '';
        }
    }

    /* ============ Cover preview helpers (edit modal) ============ */
    function lwHandleCoverPick(input) {
        lwPreviewModuleCover(input, input.dataset.previewId, input.dataset.previewImgId);
        // If user picks a new file while reset is pending, cancel the reset (the new file wins).
        lwCancelCoverReset(input.dataset.moduleId);
    }
    function lwPreviewModuleCover(input, wrapperId, imgId) {
        const wrap = document.getElementById(wrapperId);
        const img = document.getElementById(imgId);
        if (!wrap || !img) return;
        if (!input.files || !input.files[0]) { wrap.classList.remove('show'); img.src = ''; return; }
        const reader = new FileReader();
        reader.onload = function (e) { img.src = e.target.result; wrap.classList.add('show'); };
        reader.readAsDataURL(input.files[0]);
    }

    function attachDialogBackdropHandlers() {
        document.querySelectorAll('.lw-modal, .lw-editor').forEach(function (dialog) {
            if (dialog.dataset.backdropReady === '1') return;
            dialog.dataset.backdropReady = '1';
            dialog.addEventListener('mousedown', function (event) {
                if (event.target !== dialog) return;
                if (dialog.id === 'materialsConfirmModal') {
                    settleMaterialsConfirmation(false);
                    return;
                }
                closeModal(dialog.id);
            });
        });
    }

    function initializeCurrentAuthoringContent() {
        initializeSortableBlockLists();
        initializeSortableModuleGrid();
        initializeAccessibleBlocks();
        attachKeyboardBlockOrdering();
        attachAjaxModuleSave();
        attachAjaxModuleCreate();
        attachAjaxModuleSettings();
        attachAjaxBlockAdd();
        attachAjaxBlockDelete();
        attachFileReplacement();
        document.querySelectorAll('#materialsConfirmModal [data-confirm-choice]').forEach(function (button) {
            if (button.dataset.confirmReady === '1') return;
            button.dataset.confirmReady = '1';
            button.addEventListener('click', function () {
                settleMaterialsConfirmation(button.dataset.confirmChoice === 'confirm');
            });
        });
        attachDialogBackdropHandlers();

        const validationToastElement = document.getElementById('materialsValidationToastJson');
        if (validationToastElement) {
            const validationToast = JSON.parse(validationToastElement.textContent || '{}');
            if (validationToast.error) showToast(validationToast.error, 'error');
        }

        document.querySelectorAll('[data-bg]').forEach(function (el) { el.style.background = el.dataset.bg; });
        if (document.getElementById('assessmentWorkspace')) {
            cancelAssessmentLoad();
            initializeAssessmentBuilder();
            attachAjaxAssessmentForm();
            initializeAssessmentPageNavigation();
            initializeAssessmentPageState();
        } else {
            window.removeEventListener('beforeunload', assessmentBeforeUnload);
        }
    }

    function destroyTransientAuthoringUi() {
        cancelAssessmentLoad();
        if (lwAssessmentQuestionSortable && typeof lwAssessmentQuestionSortable.destroy === 'function') {
            lwAssessmentQuestionSortable.destroy();
        }
        lwAssessmentQuestionSortable = null;
        visibleAuthoringDialogs().forEach(function (dialog) {
            dialog.classList.add('hidden');
        });
        if (materialsConfirmationResolver) {
            const resolver = materialsConfirmationResolver;
            materialsConfirmationResolver = null;
            resolver(false);
        }
        materialsConfirmationTrigger = null;
        Object.keys(modalTriggers).forEach(function (key) { delete modalTriggers[key]; });
        releaseAuthoringScrollLock(true);
    }

    async function softNavigateMaterialsWorkspace(url, pushHistory, historyTraversal) {
        const current = document.getElementById('lmsWorkspaceContent');
        if (!current) {
            window.location.assign(url);
            return;
        }
        if (document.getElementById('assessmentWorkspace') && isAssessmentDirty()) {
            const confirmed = await requestAssessmentDiscard('Leave Assessment Builder?');
            if (!confirmed) {
                if (historyTraversal) window.history.pushState({ lmsAuthoring: true }, '', lmsAuthoringCurrentUrl);
                return;
            }
            captureAssessmentState();
        }
        const openModuleEditor = visibleAuthoringDialogs().find(function (dialog) {
            return isModuleEditorId(dialog.id);
        });
        if (openModuleEditor) {
            const moduleId = moduleIdFromEditorId(openModuleEditor.id);
            if (isModuleDirty(moduleId)) {
                const confirmed = await requestMaterialsConfirmation({
                    title: 'Discard unsaved module changes?',
                    message: 'Open the assessment after discarding the unsaved module title, block edits, and ordering changes.',
                    confirmLabel: 'Discard and Continue',
                    destructive: true
                });
                if (!confirmed) return;
                const discarded = await discardModuleChanges(moduleId);
                if (!discarded) return;
            }
        }
        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Workspace could not be loaded.');
            const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = parsed.getElementById('lmsWorkspaceContent');
            if (!replacement) throw new Error('Workspace content was missing.');
            const currentBreadcrumb = document.querySelector('[data-lms-workspace-header] .lw-breadcrumb');
            const destinationBreadcrumb = parsed.querySelector('[data-lms-workspace-header] .lw-breadcrumb');
            destroyTransientAuthoringUi();
            current.replaceWith(document.importNode(replacement, true));
            if (currentBreadcrumb && destinationBreadcrumb) {
                currentBreadcrumb.replaceWith(document.importNode(destinationBreadcrumb, true));
            }
            document.title = parsed.title || document.title;
            if (pushHistory) window.history.pushState({ lmsAuthoring: true }, '', url);
            lmsAuthoringCurrentUrl = window.location.href;
            window.scrollTo({ top: 0, behavior: 'auto' });
            initializeCurrentAuthoringContent();
        } catch (error) {
            destroyTransientAuthoringUi();
            window.location.assign(url);
        }
    }

    function initializeMaterialsSoftNavigation() {
        if (document.documentElement.dataset.materialsSoftNavigationReady === '1') return;
        document.documentElement.dataset.materialsSoftNavigationReady = '1';
        document.addEventListener('click', function (event) {
            const link = event.target.closest('a[href]');
            if (!link || !window.AssessmentBuilderUtils.shouldHandleMaterialsSoftNavigation(event, link)) return;
            if (link.matches('[data-materials-soft-link]')) {
                event.preventDefault();
                softNavigateMaterialsWorkspace(link.href, true);
                return;
            }
            if (!document.getElementById('assessmentWorkspace') || !isAssessmentDirty()) return;
            event.preventDefault();
            requestAssessmentDiscard('Leave Assessment Builder?').then(function (confirmed) {
                if (!confirmed) return;
                captureAssessmentState();
                window.location.assign(link.href);
            });
        });
        window.addEventListener('popstate', function () {
            const currentPage = document.getElementById('lmsWorkspaceContent')?.dataset.lmsAuthoringPage;
            if (!['materials', 'assessment'].includes(currentPage)) return;
            softNavigateMaterialsWorkspace(window.location.href, false, true);
        });
    }

    /* ============ DOMContentLoaded ============ */
    document.addEventListener('DOMContentLoaded', function () {
        initializeMaterialsSoftNavigation();
        initializeCurrentAuthoringContent();

        if (document.documentElement.dataset.materialsDialogKeyboardReady === '1') return;
        document.documentElement.dataset.materialsDialogKeyboardReady = '1';
        document.addEventListener('keydown', function (event) {
            const openDialog = Array.from(
                document.querySelectorAll('.lw-modal, .lw-editor')
            ).filter(function (dialog) {
                return !dialog.classList.contains('hidden');
            }).at(-1);
            if (!openDialog) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                if (openDialog.id === 'materialsConfirmModal') {
                    settleMaterialsConfirmation(false);
                    return;
                }
                closeModal(openDialog.id);
                return;
            }
            if (event.key !== 'Tab') return;
            const focusable = Array.from(openDialog.querySelectorAll(
                'button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), a[href]'
            )).filter(function (element) {
                return !element.hidden && element.offsetParent !== null;
            });
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    });
</script>
@endpush
