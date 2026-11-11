{{-- 
    Partial: training/partials/block.blade.php
    Renders ONE content block inside the Module Editor.
    Used by:
      - material.blade.php (loop over $moduleBlocks)
      - CourseController AJAX responses (storeUploadedBlock, storeYoutubeBlock,
        storeAssessmentBlock, replaceUploadedBlock)
    
    Expects:
      $block         (object with block_id, block_type, block_title, block_content, 
                      external_url, material_id, assessment_id, block_order,
                      original_file_name, file_path, file_url, ai_generated_title, 
                      ai_synopsis, assessment_title, assessment_type, available_from, 
                      available_until, due_date, accepted_format)
      $blockIndex    (int — used in blocks[N] form array)
      $saveFormId    (string — e.g. 'saveModuleForm5')
      $courseId      (int)
--}}

@php
    $mediaKind = \App\Services\Lms\TrainerLmsMaterialAuthoringService::mediaKind($block->original_file_name ?? null);
    $isImage = $mediaKind === 'image';
    $isVideo = $mediaKind === 'video';
    $isAudio = $mediaKind === 'audio';
    $fileAvailable = (bool) ($block->file_available ?? !empty($block->file_url));
    $deleteUrl = route('trainer.courses.blocks.delete', [$courseId, $block->block_id]);
    $assessmentAvailability = $block->assessment_availability ?? [
        'key' => 'always',
        'label' => 'Always available',
        'context' => 'No availability restrictions',
    ];
@endphp

@if ($block->block_type === 'title')
    <div class="lw-block lw-block--title" data-block-id="{{ $block->block_id }}" data-block-type="title">
        <div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
        <div class="lw-block-type-badge title"><i class="bi bi-type-h1"></i> Title</div>
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_id]" value="{{ $block->block_id }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_type]" value="title">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_content]" value="{{ $block->block_content }}">
        <input type="text"
               form="{{ $saveFormId }}"
               name="blocks[{{ $blockIndex }}][block_title]"
               value="{{ $block->block_title }}"
               placeholder="Type a title…"
               required
               data-module-required="1"
               data-message="Please fill the title block or remove it before saving."
               class="lw-block-title-input">
        <button type="button" class="lw-block-delete js-block-delete" data-delete-url="{{ $deleteUrl }}" title="Delete block">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

@elseif ($block->block_type === 'text')
    <div class="lw-block" data-block-id="{{ $block->block_id }}" data-block-type="text">
        <div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
        <div class="lw-block-type-badge text"><i class="bi bi-text-paragraph"></i> Text</div>
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_id]" value="{{ $block->block_id }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_type]" value="text">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_title]" value="{{ $block->block_title }}">
        <textarea form="{{ $saveFormId }}"
                  name="blocks[{{ $blockIndex }}][block_content]"
                  rows="3"
                  required
                  data-module-required="1"
                  data-message="Please fill the text block or remove it before saving."
                  placeholder="Write text content…">{{ $block->block_content }}</textarea>
        <button type="button" class="lw-block-delete js-block-delete" data-delete-url="{{ $deleteUrl }}" title="Delete block">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

@elseif ($block->block_type === 'material')
    <div class="lw-block" data-block-id="{{ $block->block_id }}" data-block-type="material">
        <div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
        <div class="lw-block-type-badge material"><i class="bi bi-file-earmark-text"></i> File</div>
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_id]" value="{{ $block->block_id }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_type]" value="material">
        <div class="lw-block-row">
            <div class="lw-block-icon-tag material"><i class="bi bi-file-earmark-text"></i></div>
            <div class="lw-block-row-body">
                <input type="text"
                       form="{{ $saveFormId }}"
                       name="blocks[{{ $blockIndex }}][block_title]"
                       value="{{ $block->block_title ?: ($block->ai_generated_title ?? '') }}"
                       required
                       data-module-required="1"
                       data-message="Please fill the file title or remove the block before saving."
                       placeholder="Material title">
                <input type="text"
                       form="{{ $saveFormId }}"
                       name="blocks[{{ $blockIndex }}][block_content]"
                       value="{{ $block->block_content ?: ($block->ai_synopsis ?? '') }}"
                       placeholder="Description (optional)">
                <div class="lw-block-meta">
                    <i class="bi bi-paperclip"></i>
                    <span>{{ $block->original_file_name ?? 'Uploaded file' }}</span>
                    @if ($fileAvailable && $block->file_url)
                        <span class="lw-block-meta-sep">·</span>
                        <a href="{{ $block->material_id ? route('trainer.courses.materials.download', [$courseId, $block->material_id]) : $block->file_url }}">
                            <i class="bi bi-download"></i> Download file
                        </a>
                    @elseif (!$fileAvailable)
                        <span class="lw-block-meta-sep">·</span>
                        <span><i class="bi bi-exclamation-triangle"></i> File unavailable</span>
                    @endif
                </div>
            </div>
        </div>
        @include('trainer.training.partials.file-replacement', [
            'block' => $block,
            'courseId' => $courseId,
        ])
        <button type="button" class="lw-block-delete js-block-delete" data-delete-url="{{ $deleteUrl }}" title="Delete block">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

@elseif ($block->block_type === 'media')
    <div class="lw-block" data-block-id="{{ $block->block_id }}" data-block-type="media">
        <div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
        <div class="lw-block-type-badge media"><i class="bi bi-image"></i> Media</div>
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_id]" value="{{ $block->block_id }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_type]" value="media">

        <div class="lw-block-media">
            @if ($fileAvailable && $isImage && $block->file_url)
                <img src="{{ $block->file_url }}"
                     alt="Media"
                     onerror="this.parentElement.innerHTML='<div class=&quot;lw-block-media-fallback&quot;>Image could not be loaded.</div>';">
            @elseif ($fileAvailable && $isVideo && $block->file_url)
                <video controls>
                    <source src="{{ $block->file_url }}">
                    Your browser does not support the video tag.
                </video>
            @elseif ($fileAvailable && $isAudio && $block->file_url)
                <audio controls>
                    <source src="{{ $block->file_url }}">
                    Your browser does not support the audio tag.
                </audio>
            @else
                <div class="lw-block-media-fallback">{{ $fileAvailable ? 'This media type cannot be previewed here.' : 'The uploaded media file could not be found.' }}</div>
            @endif
        </div>

        <input type="text"
               form="{{ $saveFormId }}"
               name="blocks[{{ $blockIndex }}][block_title]"
               value="{{ $block->block_title ?: ($block->ai_generated_title ?? '') }}"
               required
               data-module-required="1"
               data-message="Please fill the media title or remove the block before saving."
               placeholder="Media title">
        <textarea form="{{ $saveFormId }}"
                  name="blocks[{{ $blockIndex }}][block_content]"
                  rows="2"
                   placeholder="Media description (optional)">{{ $block->block_content ?: ($block->ai_synopsis ?? '') }}</textarea>

        @include('trainer.training.partials.file-replacement', [
            'block' => $block,
            'courseId' => $courseId,
        ])
        <button type="button" class="lw-block-delete js-block-delete" data-delete-url="{{ $deleteUrl }}" title="Delete block">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

@elseif ($block->block_type === 'youtube')
    <div class="lw-block" data-block-id="{{ $block->block_id }}" data-block-type="youtube">
        <div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
        <div class="lw-block-type-badge youtube"><i class="bi bi-youtube"></i> YouTube</div>
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_id]" value="{{ $block->block_id }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_type]" value="youtube">

        <div class="lw-block-media lw-block-media-yt">
            @if ($block->external_url)
                <iframe src="{{ $block->external_url }}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
            @else
                <div class="lw-block-media-fallback">YouTube link missing.</div>
            @endif
        </div>

        <input type="text"
               form="{{ $saveFormId }}"
               name="blocks[{{ $blockIndex }}][block_title]"
               value="{{ $block->block_title }}"
               required
               data-module-required="1"
               data-message="Please fill the YouTube title or remove the block."
               placeholder="Video title">
        <input type="text"
               form="{{ $saveFormId }}"
               name="blocks[{{ $blockIndex }}][external_url]"
               value="{{ $block->external_url }}"
               required
               data-module-required="1"
               data-message="Please fill the YouTube URL or remove the block."
               placeholder="https://www.youtube.com/watch?v=...">
        <textarea form="{{ $saveFormId }}"
                  name="blocks[{{ $blockIndex }}][block_content]"
                  rows="2"
                  placeholder="Video description (optional)">{{ $block->block_content }}</textarea>

        <button type="button" class="lw-block-delete js-block-delete" data-delete-url="{{ $deleteUrl }}" title="Delete block">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

@elseif ($block->block_type === 'quiz')
    <div class="lw-block lw-block--quiz" data-block-id="{{ $block->block_id }}" data-block-type="quiz" data-assessment-id="{{ $block->assessment_id }}">
        <div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_id]" value="{{ $block->block_id }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_type]" value="quiz">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_title]" value="{{ $block->assessment_title ?: $block->block_title }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_content]" value="{{ $block->block_content }}">

        <div class="lw-block-row">
            <div class="lw-block-icon-tag quiz"><i class="bi bi-ui-checks-grid"></i></div>
            <div class="lw-block-row-body">
                <div class="lw-block-assessment-topline">
                    <div class="lw-block-mini-label" style="color: var(--lw-primary);">Quiz</div>
                    <span class="lw-assessment-state-badge is-{{ $assessmentAvailability['key'] }}">{{ $assessmentAvailability['label'] }}</span>
                </div>
                <div class="lw-block-heading" data-assessment-title>{{ $block->assessment_title ?: $block->block_title }}</div>
                <div class="lw-block-meta">
                    <i class="bi bi-ui-checks-grid"></i>
                    {{ (int) ($block->assessment_question_count ?? 0) }} {{ (int) ($block->assessment_question_count ?? 0) === 1 ? 'question' : 'questions' }}
                    <span class="lw-block-meta-sep">·</span>
                    {{ (float) ($block->total_marks ?? 0) + 0 }} marks
                </div>
                <div class="lw-block-meta" data-assessment-meta>
                    <i class="bi bi-clock"></i>
                    {{ $assessmentAvailability['context'] }}
                </div>
                <a href="{{ route('trainer.courses.assessments.builder', ['course_id' => $courseId, 'type' => 'quiz', 'assessment' => $block->assessment_id]) }}" class="lw-inline-action" data-materials-soft-link>
                    <i class="bi bi-pencil-square"></i> Edit assessment
                </a>
            </div>
        </div>

        <button type="button" class="lw-block-delete js-block-delete" data-delete-url="{{ $deleteUrl }}" title="Delete block">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

@elseif ($block->block_type === 'submission')
    <div class="lw-block lw-block--submission" data-block-id="{{ $block->block_id }}" data-block-type="submission" data-assessment-id="{{ $block->assessment_id }}">
        <div class="lw-block-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></div>
        <div class="lw-block-type-badge submission"><i class="bi bi-upload"></i> Task Submission</div>
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_id]" value="{{ $block->block_id }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_type]" value="submission">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_title]" value="{{ $block->assessment_title ?: $block->block_title }}">
        <input type="hidden" form="{{ $saveFormId }}" name="blocks[{{ $blockIndex }}][block_content]" value="{{ $block->block_content }}">

        <div class="lw-block-row">
            <div class="lw-block-icon-tag submission"><i class="bi bi-upload"></i></div>
            <div class="lw-block-row-body">
                <div class="lw-block-assessment-topline">
                    <div class="lw-block-mini-label" style="color: var(--lw-rose);">Submission Task</div>
                    <span class="lw-assessment-state-badge is-{{ $assessmentAvailability['key'] }}">{{ $assessmentAvailability['label'] }}</span>
                </div>
                <div class="lw-block-heading" data-assessment-title>{{ $block->assessment_title ?: $block->block_title }}</div>
                <div class="lw-block-description" data-assessment-description @if (!$block->block_content) hidden @endif>{{ $block->block_content }}</div>
                <div class="lw-block-meta" data-assessment-meta>
                    <i class="bi bi-file-earmark-check"></i>
                    {{ $block->accepted_format ?: 'Any file format' }}
                    <span class="lw-block-meta-sep">·</span>
                    <i class="bi bi-clock"></i>
                    {{ $assessmentAvailability['context'] }}
                </div>
                <a href="{{ route('trainer.courses.assessments.builder', ['course_id' => $courseId, 'type' => 'submission', 'assessment' => $block->assessment_id]) }}" class="lw-inline-action" data-materials-soft-link>
                    <i class="bi bi-pencil-square"></i> Edit assessment
                </a>
            </div>
        </div>

        <button type="button" class="lw-block-delete js-block-delete" data-delete-url="{{ $deleteUrl }}" title="Delete block">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
@endif
