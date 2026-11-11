@extends('trainer.layouts.app')

@section('title', 'Materials | Al Amin Edu Oasis')

@include('trainer.training.partials.material-authoring-styles')

@section('content')
<script>document.body.dataset.lwCourseId = '{{ $course->course_id }}';</script>

@php
    $pendingSubmissions = $pendingSubmissions ?? 0;

    // Module type → cartoon-style icon + gradient.
    // Add more types if your module_type ENUM grows.
    $typeStyle = [
        'introduction' => ['icon' => 'bi-flag-fill',           'gradient' => 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)'],
        'overview'     => ['icon' => 'bi-flag-fill',           'gradient' => 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)'],
        'lesson'       => ['icon' => 'bi-book-fill',           'gradient' => 'linear-gradient(135deg, #2563eb 0%, #38bdf8 100%)'],
        'chapter'      => ['icon' => 'bi-book-fill',           'gradient' => 'linear-gradient(135deg, #2563eb 0%, #38bdf8 100%)'],
        'video'        => ['icon' => 'bi-camera-video-fill',   'gradient' => 'linear-gradient(135deg, #db2777 0%, #f472b6 100%)'],
        'media'        => ['icon' => 'bi-camera-video-fill',   'gradient' => 'linear-gradient(135deg, #db2777 0%, #f472b6 100%)'],
        'reading'      => ['icon' => 'bi-journal-text',        'gradient' => 'linear-gradient(135deg, #059669 0%, #34d399 100%)'],
        'material'     => ['icon' => 'bi-journal-text',        'gradient' => 'linear-gradient(135deg, #059669 0%, #34d399 100%)'],
        'quiz'         => ['icon' => 'bi-patch-question-fill', 'gradient' => 'linear-gradient(135deg, #d97706 0%, #fbbf24 100%)'],
        'assessment'   => ['icon' => 'bi-patch-question-fill', 'gradient' => 'linear-gradient(135deg, #d97706 0%, #fbbf24 100%)'],
        'exam'         => ['icon' => 'bi-patch-question-fill', 'gradient' => 'linear-gradient(135deg, #d97706 0%, #fbbf24 100%)'],
        'submission'   => ['icon' => 'bi-upload',              'gradient' => 'linear-gradient(135deg, #0891b2 0%, #22d3ee 100%)'],
        'assignment'   => ['icon' => 'bi-upload',              'gradient' => 'linear-gradient(135deg, #0891b2 0%, #22d3ee 100%)'],
        'practice'     => ['icon' => 'bi-pencil-square',       'gradient' => 'linear-gradient(135deg, #c2410c 0%, #fb923c 100%)'],
        'activity'     => ['icon' => 'bi-pencil-square',       'gradient' => 'linear-gradient(135deg, #c2410c 0%, #fb923c 100%)'],
        'conclusion'   => ['icon' => 'bi-trophy-fill',         'gradient' => 'linear-gradient(135deg, #4338ca 0%, #6366f1 100%)'],
        'final'        => ['icon' => 'bi-trophy-fill',         'gradient' => 'linear-gradient(135deg, #4338ca 0%, #6366f1 100%)'],
        'discussion'   => ['icon' => 'bi-chat-dots-fill',      'gradient' => 'linear-gradient(135deg, #be185d 0%, #ec4899 100%)'],
    ];
    $defaultTypeStyle = ['icon' => 'bi-stack', 'gradient' => 'linear-gradient(135deg, #475569 0%, #94a3b8 100%)'];
@endphp

@include('trainer.training.partials.workspace-header', [
    'activeLmsTab' => 'materials',
    'lmsSubpageTitle' => null,
])

<main class="lw-page app-workspace-shell" id="lmsWorkspaceContent" data-lms-authoring-page="materials">

    <script type="application/json" id="materialsValidationToastJson">@json([
        'error' => $errors->any() ? $errors->first() : null,
    ])</script>

    <div class="lw-section-head">
        <div>
            <h2 class="lw-section-title">Curriculum Modules</h2>
            <p class="lw-section-sub">Click a module to expand and edit its content blocks. Drag handles to reorder.</p>
        </div>
        <a href="{{ route('trainer.courses.assessments.builder', $course->course_id) }}" class="lw-btn primary" data-create-assessment-link data-materials-soft-link>
            <i class="bi bi-clipboard-check"></i> Create Assessment
        </a>
    </div>

    {{-- ============ Module Grid (NEW cartoon-style cards) ============ --}}
    <div class="lw-module-grid"
         data-module-grid
         data-reorder-url="{{ route('trainer.courses.modules.reorder', $course->course_id) }}">
        @foreach ($modules as $module)
            @php
                $moduleBlocks   = $blocksByModule->get($module->module_id, collect());
                $blockCount     = $moduleBlocks->count();
                $style          = $typeStyle[strtolower($module->module_type ?? '')] ?? $defaultTypeStyle;
                $hasCustomCover = !empty($module->raw_cover_image);
            @endphp
            <div
                class="lw-module-card"
                data-module-card="{{ $module->module_id }}"
                data-modal-id="moduleModal{{ $module->module_id }}"
                onclick="openModuleModalFromButton(this)"
                onkeydown="openModuleModalFromKeyboard(event, this)"
                role="button"
                tabindex="0"
                aria-label="Edit module {{ $module->module_title }}"
            >
                <button type="button" class="lw-module-drag" title="Drag to reorder module" aria-label="Drag {{ $module->module_title }} to reorder" onclick="event.stopPropagation()">
                    <i class="bi bi-grip-vertical"></i>
                </button>
                <div class="lw-module-image">
                    <img src="{{ $module->cover_image }}"
                         alt="{{ $module->module_title }} cover"
                         class="lw-module-image-photo"
                         data-module-cover-image="{{ $module->module_id }}"
                         @if (!$hasCustomCover) hidden @endif
                         onerror="this.hidden=true;">
                    <div class="lw-module-image-deco" data-module-cover-default="{{ $module->module_id }}" data-bg="{{ $style['gradient'] }}" @if ($hasCustomCover) hidden @endif>
                        <i class="bi {{ $style['icon'] }}"></i>
                    </div>
                    <button type="button"
                            class="lw-module-edit-btn"
                            data-modal-id="moduleSettingsModal{{ $module->module_id }}"
                            onclick="event.stopPropagation(); openModuleModalFromButton(this)"
                            title="Edit module settings">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </div>
                <div class="lw-module-body">
                    <h3 class="lw-module-title" data-module-grid-title="{{ $module->module_id }}">{{ $module->module_title }}</h3>
                    <div class="lw-module-meta" data-module-metrics="{{ $module->module_id }}">
                        <i class="bi bi-stack"></i>
                        <span data-module-grid-count="{{ $module->module_id }}">{{ $blockCount }}</span>
                        block{{ $blockCount === 1 ? '' : 's' }}
                    </div>
                </div>
            </div>
        @endforeach
        <button type="button" onclick="openModuleModal('addModuleModal')" class="lw-module-add">
            <div class="lw-module-add-icon"><i class="bi bi-plus"></i></div>
            <div class="lw-module-add-text">Add New Module</div>
        </button>
    </div>

    {{-- ============ Add Module Modal ============ --}}
    <div id="addModuleModal" class="lw-modal hidden" role="dialog" aria-modal="true" aria-labelledby="addModuleModalTitle">
        <form method="POST" action="{{ route('trainer.courses.modules.store', $course->course_id) }}" enctype="multipart/form-data" class="lw-modal-box js-ajax-module-create" data-modal-id="addModuleModal">
            @csrf
            <div class="lw-modal-head">
                <div class="lw-modal-head-text">
                    <h2 class="lw-modal-title" id="addModuleModalTitle">Add New Module</h2>
                    <div class="lw-modal-sub">Module Card</div>
                </div>
                <button type="button" data-modal-id="addModuleModal" onclick="closeModalFromButton(this)" class="lw-modal-close">&times;</button>
            </div>
            <div class="lw-modal-body">
                <div class="lw-field">
                    <label for="addModuleTitle">Module Title</label>
                    <input id="addModuleTitle" type="text" name="module_title" placeholder="Example: Chapter 2" required maxlength="150">
                    <div class="lw-field-hint">Titles must be unique within this training.</div>
                </div>
                <div class="lw-field">
                    <label for="addModuleCover">Cover Image (optional)</label>
                    <input id="addModuleCover" type="file" name="cover_image_file" accept="image/*"
                           data-preview-id="addModuleCoverPreview"
                           data-preview-img-id="addModuleCoverImg"
                           onchange="lwHandleAddModuleCoverPick(this)">
                    <div class="lw-field-hint">JPG, PNG, or WebP. Max 5 MB. Leave empty to use the default cartoon cover.</div>
                    <div id="addModuleCoverPreview" class="lw-cover-newpreview-wrap">
                        <div class="lw-cover-newpreview-label">Cover preview</div>
                        <img id="addModuleCoverImg" src="" alt="" class="lw-cover-newpreview-img">
                    </div>
                </div>
            </div>
            <div class="lw-modal-foot">
                <button type="button" data-modal-id="addModuleModal" onclick="closeModalFromButton(this)" class="lw-btn ghost">Cancel</button>
                <button type="submit" class="lw-btn primary"><i class="bi bi-plus-lg"></i> Create Module</button>
            </div>
        </form>
    </div>

    {{-- ============ Module Settings (Edit) Modals ============ --}}
    @foreach ($modules as $module)
        @php
            $style           = $typeStyle[strtolower($module->module_type ?? '')] ?? $defaultTypeStyle;
            $hasCustomCover  = !empty($module->raw_cover_image);
            $newPreviewId    = 'newCoverPreview' . $module->module_id;
            $newPreviewImgId = 'newCoverPreviewImg' . $module->module_id;
            $fileInputId     = 'editCoverInput' . $module->module_id;
        @endphp
        <div id="moduleSettingsModal{{ $module->module_id }}" class="lw-modal hidden" role="dialog" aria-modal="true" aria-labelledby="moduleSettingsTitle{{ $module->module_id }}">
            <form method="POST"
                  action="{{ route('trainer.courses.modules.update', [$course->course_id, $module->module_id]) }}"
                  enctype="multipart/form-data"
                  class="lw-modal-box js-ajax-module-settings"
                  data-module-id="{{ $module->module_id }}"
                  data-modal-id="moduleSettingsModal{{ $module->module_id }}"
                  style="max-width: 580px;">
                @csrf
                @method('PATCH')
                <input type="hidden" name="delete_cover_image" value="0" id="deleteCoverFlag{{ $module->module_id }}">

                <div class="lw-modal-head">
                    <div class="lw-modal-head-text">
                        <h2 class="lw-modal-title" id="moduleSettingsTitle{{ $module->module_id }}">Edit Module</h2>
                        <div class="lw-modal-sub">{{ ucfirst($module->module_type ?? 'Module') }} settings</div>
                    </div>
                    <button type="button" data-modal-id="moduleSettingsModal{{ $module->module_id }}" onclick="closeModalFromButton(this)" class="lw-modal-close">&times;</button>
                </div>

                <div class="lw-modal-body">
                    <div class="lw-field">
                        <label for="editModuleTitle{{ $module->module_id }}">Module Title</label>
                        <input id="editModuleTitle{{ $module->module_id }}" type="text" name="module_title" value="{{ $module->module_title }}" required maxlength="150">
                        <div class="lw-field-hint">Must be unique within this training. Up to 150 characters.</div>
                    </div>

                    <div class="lw-field">
                        <label for="{{ $fileInputId }}">{{ $hasCustomCover ? 'Replace Cover Image' : 'Upload Cover Image' }}</label>
                        <input id="{{ $fileInputId }}" type="file" name="cover_image_file" accept="image/*"
                               data-preview-id="{{ $newPreviewId }}"
                               data-preview-img-id="{{ $newPreviewImgId }}"
                               data-module-id="{{ $module->module_id }}"
                               onchange="lwHandleCoverPick(this)">
                        <div class="lw-field-hint">JPG, PNG, or WebP. Max 5 MB. Leave empty to keep the current cover.</div>

                        <div id="{{ $newPreviewId }}" class="lw-cover-newpreview-wrap">
                            <div class="lw-cover-newpreview-label">New cover preview</div>
                            <img id="{{ $newPreviewImgId }}" src="" alt="" class="lw-cover-newpreview-img">
                        </div>
                    </div>

                    <div class="lw-field" id="coverResetField{{ $module->module_id }}" @if (!$hasCustomCover) hidden @endif>
                        <label>Cover Image</label>
                        <div id="coverResetRow{{ $module->module_id }}" class="lw-cover-reset-row">
                            <i class="bi bi-image"></i>
                            <span class="lw-cover-reset-text" id="coverResetText{{ $module->module_id }}">This module has a custom cover image.</span>
                            <button type="button" id="coverResetBtn{{ $module->module_id }}"
                                    data-module-id="{{ $module->module_id }}"
                                    onclick="lwToggleCoverReset(this.dataset.moduleId)"
                                    class="lw-cover-reset-btn">
                                <i class="bi bi-trash"></i> Remove image
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lw-modal-foot split">
                    <div>
                        <button type="button"
                                data-delete-module-url="{{ route('trainer.courses.modules.delete', [$course->course_id, $module->module_id]) }}"
                                data-module-id="{{ $module->module_id }}"
                                data-module-title="{{ $module->module_title }}"
                                data-block-count="{{ $blocksByModule->get($module->module_id, collect())->count() }}"
                                onclick="deleteModuleFromButton(this)"
                                class="lw-btn danger">
                            <i class="bi bi-trash"></i> Delete Module
                        </button>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" data-modal-id="moduleSettingsModal{{ $module->module_id }}" onclick="closeModalFromButton(this)" class="lw-btn ghost">Cancel</button>
                        <button type="submit" class="lw-btn primary"><i class="bi bi-check2"></i> Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    @endforeach

    {{-- ============ Module Editor Modals ============ --}}
    @foreach ($modules as $module)
        @php
            $moduleBlocks = $blocksByModule->get($module->module_id, collect());
            $saveFormId = 'saveModuleForm' . $module->module_id;
            $blockListId = 'blockList' . $module->module_id;
            $nextBlockIndex = $moduleBlocks->count();
        @endphp

        {{-- Hidden form referenced by the editor's inputs via form="" attribute --}}
        <form id="{{ $saveFormId }}"
              class="js-ajax-module-save"
              data-module-id="{{ $module->module_id }}"
              data-modal-id="moduleModal{{ $module->module_id }}"
              method="POST"
              action="{{ route('trainer.courses.modules.blocks.save', [$course->course_id, $module->module_id]) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PATCH')
        </form>

        <div id="moduleModal{{ $module->module_id }}" class="lw-editor hidden" role="dialog" aria-modal="true" aria-label="Edit module {{ $module->module_title }}" tabindex="-1">
            <div class="lw-editor-box">

                {{-- HEAD --}}
                <div class="lw-editor-head">
                    <div class="lw-editor-head-l">
                        <div class="lw-editor-icon"><i class="bi bi-pencil-square"></i></div>
                        <div class="lw-editor-title-wrap">
                            <input type="text"
                                   form="{{ $saveFormId }}"
                                   name="module_title"
                                   value="{{ $module->module_title }}"
                                   class="lw-editor-title-input"
                                   required
                                   maxlength="150"
                                   placeholder="Module title">
                            <div class="lw-editor-title-error" data-module-title-error="{{ $module->module_id }}" aria-live="polite"></div>
                            <div class="lw-editor-sub" data-block-count-text-for="{{ $module->module_id }}">Module · {{ $moduleBlocks->count() }} block{{ $moduleBlocks->count() === 1 ? '' : 's' }}</div>
                        </div>
                    </div>
                    <div class="lw-editor-actions">
                        <button type="submit" form="{{ $saveFormId }}" class="lw-btn primary">
                            <i class="bi bi-check2"></i> Save Module
                        </button>
                        <button type="button" data-modal-id="moduleModal{{ $module->module_id }}" onclick="closeModalFromButton(this)" class="lw-btn ghost">Close</button>
                    </div>
                </div>

                {{-- BODY --}}
                <div class="lw-editor-body">

                    {{-- Block list --}}
                    <div id="{{ $blockListId }}"
                         class="lw-block-list"
                         data-next-index="{{ $nextBlockIndex }}"
                         data-form-id="{{ $saveFormId }}"
                         data-module-id="{{ $module->module_id }}"
                         data-course-id="{{ $course->course_id }}">

                        @foreach ($moduleBlocks as $blockIndex => $block)
                            @include('trainer.training.partials.block', [
                                'block' => $block,
                                'blockIndex' => $blockIndex,
                                'saveFormId' => $saveFormId,
                                'courseId' => $course->course_id,
                            ])
                        @endforeach
                    </div>

                    {{-- Empty state (only shown when no blocks; JS toggles it) --}}
                    <div id="emptyState{{ $module->module_id }}" class="lw-empty-state" @if ($moduleBlocks->count() > 0) style="display:none;" @endif>
                        <div class="lw-empty-icon"><i class="bi bi-plus-lg"></i></div>
                        <h4 class="lw-empty-title">No blocks yet</h4>
                        <p class="lw-empty-sub">Use the toolbar below to add your first block.</p>
                    </div>

                    {{-- Stackable add panels --}}
                    <div class="lw-add-panels" id="addPanels{{ $module->module_id }}">

                        {{-- File panel --}}
                        <div id="filePanel{{ $module->module_id }}" class="lw-add-panel hidden">
                            <div class="lw-add-panel-head">
                                <div class="lw-add-panel-title file"><i class="bi bi-paperclip"></i> Add File Block</div>
                                <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="file" onclick="togglePanelFromButton(this)" class="lw-add-panel-close" title="Close">&times;</button>
                            </div>
                            <form class="js-ajax-block-add lw-add-panel-body"
                                  data-module-id="{{ $module->module_id }}"
                                  data-panel-type="file"
                                  method="POST"
                                  action="{{ route('trainer.courses.blocks.material.store', [$course->course_id, $module->module_id]) }}"
                                  enctype="multipart/form-data">
                                @csrf
                                <input type="text" name="material_title" placeholder="Material title (optional)">
                                <textarea name="material_description" rows="2" placeholder="Description (optional)"></textarea>
                                <input type="file" name="material_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.csv" required>
                                <div class="lw-add-panel-foot">
                                    <button type="submit" class="lw-btn primary sm"><i class="bi bi-plus-lg"></i> Add to Module</button>
                                </div>
                            </form>
                        </div>

                        {{-- Media panel --}}
                        <div id="mediaPanel{{ $module->module_id }}" class="lw-add-panel hidden">
                            <div class="lw-add-panel-head">
                                <div class="lw-add-panel-title media"><i class="bi bi-image"></i> Add Media Block</div>
                                <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="media" onclick="togglePanelFromButton(this)" class="lw-add-panel-close" title="Close">&times;</button>
                            </div>
                            <form class="js-ajax-block-add lw-add-panel-body"
                                  data-module-id="{{ $module->module_id }}"
                                  data-panel-type="media"
                                  method="POST"
                                  action="{{ route('trainer.courses.blocks.media.store', [$course->course_id, $module->module_id]) }}"
                                  enctype="multipart/form-data">
                                @csrf
                                <input type="text" name="material_title" placeholder="Media title (optional)">
                                <textarea name="material_description" rows="2" placeholder="Description (optional)"></textarea>
                                <input type="file" name="material_file" accept="image/*,video/*,audio/*" required>
                                <div class="lw-add-panel-foot">
                                    <button type="submit" class="lw-btn primary sm"><i class="bi bi-plus-lg"></i> Add to Module</button>
                                </div>
                            </form>
                        </div>

                        {{-- YouTube panel --}}
                        <div id="youtubePanel{{ $module->module_id }}" class="lw-add-panel hidden">
                            <div class="lw-add-panel-head">
                                <div class="lw-add-panel-title youtube"><i class="bi bi-youtube"></i> Add YouTube Block</div>
                                <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="youtube" onclick="togglePanelFromButton(this)" class="lw-add-panel-close" title="Close">&times;</button>
                            </div>
                            <form class="js-ajax-block-add lw-add-panel-body"
                                  data-module-id="{{ $module->module_id }}"
                                  data-panel-type="youtube"
                                  method="POST"
                                  action="{{ route('trainer.courses.blocks.youtube.store', [$course->course_id, $module->module_id]) }}">
                                @csrf
                                <input type="text" name="youtube_title" placeholder="Video title" required>
                                <input type="text" name="youtube_url" placeholder="Paste YouTube link here" required>
                                <textarea name="youtube_description" rows="2" placeholder="Description (optional)"></textarea>
                                <div class="lw-add-panel-foot">
                                    <button type="submit" class="lw-btn primary sm"><i class="bi bi-plus-lg"></i> Add to Module</button>
                                </div>
                            </form>
                        </div>

                        {{-- Quiz panel --}}
                        <div id="quizPanel{{ $module->module_id }}" class="lw-add-panel hidden">
                            <div class="lw-add-panel-head">
                                <div class="lw-add-panel-title quiz"><i class="bi bi-ui-checks-grid"></i> Select Quiz</div>
                                <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="quiz" onclick="togglePanelFromButton(this)" class="lw-add-panel-close" title="Close">&times;</button>
                            </div>
                            <form class="js-ajax-block-add lw-add-panel-body"
                                  data-module-id="{{ $module->module_id }}"
                                  data-panel-type="quiz"
                                  method="POST"
                                  action="{{ route('trainer.courses.blocks.assessment.store', [$course->course_id, $module->module_id]) }}">
                                @csrf
                                @include('trainer.training.partials.assessment-picker', [
                                    'pickerAssessments' => $quizAssessments,
                                    'pickerModule' => $module,
                                    'pickerLabel' => 'Select a pre-built quiz',
                                ])
                                @if ($quizAssessments->count() === 0)
                                    <div class="lw-add-panel-hint">No quizzes yet. Use "Create Assessment" first.</div>
                                @else
                                    <div class="lw-add-panel-hint" data-attachment-empty-hint
                                         @unless($quizAssessments->every(fn ($assessment) => (bool) ($assessment->attachment_module_id ?? null))) hidden @endunless>
                                        Every saved quiz is already attached to a module.
                                    </div>
                                @endif
                                <div class="lw-add-panel-foot">
                                    <button type="submit" class="lw-btn primary sm"
                                            @disabled($quizAssessments->isEmpty() || $quizAssessments->every(fn ($assessment) => (bool) ($assessment->attachment_module_id ?? null)))>
                                        <i class="bi bi-plus-lg"></i> Add to Module
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Submission panel --}}
                        <div id="submissionPanel{{ $module->module_id }}" class="lw-add-panel hidden">
                            <div class="lw-add-panel-head">
                                <div class="lw-add-panel-title submission"><i class="bi bi-upload"></i> Select Submission Task</div>
                                <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="submission" onclick="togglePanelFromButton(this)" class="lw-add-panel-close" title="Close">&times;</button>
                            </div>
                            <form class="js-ajax-block-add lw-add-panel-body"
                                  data-module-id="{{ $module->module_id }}"
                                  data-panel-type="submission"
                                  method="POST"
                                  action="{{ route('trainer.courses.blocks.assessment.store', [$course->course_id, $module->module_id]) }}">
                                @csrf
                                @include('trainer.training.partials.assessment-picker', [
                                    'pickerAssessments' => $submissionAssessments,
                                    'pickerModule' => $module,
                                    'pickerLabel' => 'Select a submission task',
                                ])
                                @if ($submissionAssessments->count() === 0)
                                    <div class="lw-add-panel-hint">No submission tasks yet. Use "Create Assessment" first.</div>
                                @else
                                    <div class="lw-add-panel-hint" data-attachment-empty-hint
                                         @unless($submissionAssessments->every(fn ($assessment) => (bool) ($assessment->attachment_module_id ?? null))) hidden @endunless>
                                        Every saved submission task is already attached to a module.
                                    </div>
                                @endif
                                <div class="lw-add-panel-foot">
                                    <button type="submit" class="lw-btn primary sm"
                                            @disabled($submissionAssessments->isEmpty() || $submissionAssessments->every(fn ($assessment) => (bool) ($assessment->attachment_module_id ?? null)))>
                                        <i class="bi bi-plus-lg"></i> Add to Module
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- FOOT (toolbar) --}}
                <div class="lw-editor-foot">
                    <div class="lw-toolbar-wrap">
                        <div class="lw-toolbar">
                            <span class="lw-toolbar-label">Add Block</span>
                            <button type="button" data-module-id="{{ $module->module_id }}" data-block-list-id="{{ $blockListId }}" data-block-type="title" onclick="addEditableBlockFromButton(this)" class="lw-tool-btn">
                                <i class="bi bi-type-h1"></i> Title
                            </button>
                            <button type="button" data-module-id="{{ $module->module_id }}" data-block-list-id="{{ $blockListId }}" data-block-type="text" onclick="addEditableBlockFromButton(this)" class="lw-tool-btn">
                                <i class="bi bi-text-paragraph"></i> Text
                            </button>
                            <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="media" onclick="togglePanelFromButton(this)" class="lw-tool-btn">
                                <i class="bi bi-image"></i> Media
                            </button>
                            <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="youtube" onclick="togglePanelFromButton(this)" class="lw-tool-btn">
                                <i class="bi bi-youtube"></i> YouTube
                            </button>
                            <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="file" onclick="togglePanelFromButton(this)" class="lw-tool-btn">
                                <i class="bi bi-paperclip"></i> File
                            </button>
                            <div class="lw-toolbar-sep"></div>
                            <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="quiz" onclick="togglePanelFromButton(this)" class="lw-tool-btn accent-quiz">
                                <i class="bi bi-ui-checks-grid"></i> Quiz
                            </button>
                            <button type="button" data-module-id="{{ $module->module_id }}" data-panel-type="submission" onclick="togglePanelFromButton(this)" class="lw-tool-btn accent-submission">
                                <i class="bi bi-upload"></i> Submission
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endforeach

    <div id="materialsConfirmModal" class="lw-modal hidden" role="dialog" aria-modal="true" aria-labelledby="materialsConfirmTitle" aria-describedby="materialsConfirmMessage">
        <div class="lw-modal-box" style="max-width: 500px;">
            <div class="lw-modal-head">
                <div class="lw-modal-head-text">
                    <h2 class="lw-modal-title" id="materialsConfirmTitle">Confirm action</h2>
                    <div class="lw-modal-sub">Materials Workspace</div>
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

</main>
@endsection

@include('trainer.training.partials.material-authoring-scripts')
