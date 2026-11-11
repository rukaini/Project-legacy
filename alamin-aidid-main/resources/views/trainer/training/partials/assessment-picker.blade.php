<div class="lw-assessment-picker" role="group" aria-label="{{ $pickerLabel }}">
    @foreach ($pickerAssessments as $assessment)
        @php
            $attachedModuleId = $assessment->attachment_module_id ?? null;
            $attachedHere = (string) $attachedModuleId === (string) $pickerModule->module_id;
            $availability = $assessment->availability;
        @endphp
        <label class="lw-assessment-picker-item {{ $attachedModuleId ? 'is-attached' : '' }}"
               data-assessment-picker-item="{{ $assessment->assessment_id }}"
               data-current-module-id="{{ $pickerModule->module_id }}"
               data-current-module-title="{{ $pickerModule->module_title }}">
            <input type="radio"
                   name="assessment_id"
                   value="{{ $assessment->assessment_id }}"
                   data-assessment-option="{{ $assessment->assessment_id }}"
                   @disabled($attachedModuleId)
                   required>
            <span class="lw-assessment-picker-copy">
                <span class="lw-assessment-picker-heading">
                    <strong>{{ $assessment->title }}</strong>
                    <span class="lw-assessment-state-badge is-{{ $availability['key'] }}">{{ $availability['label'] }}</span>
                </span>
                <span class="lw-assessment-picker-meta" data-assessment-attachment-label>
                    @if ($attachedHere)
                        Already in this module
                    @elseif ($attachedModuleId)
                        Attached to: {{ $assessment->attachment_module_title }}
                    @else
                        Not attached
                    @endif
                </span>
            </span>
        </label>
    @endforeach
</div>
