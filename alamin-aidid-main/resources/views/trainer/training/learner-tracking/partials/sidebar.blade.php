<aside class="lt-navigator" data-tracking-navigator data-tracking-region="navigator">
    <div class="lt-nav-expanded">
    <div class="lt-nav-head">
        <div class="lt-nav-title-row">
            <div>
                <span class="lt-label">
                    {{ $tab === 'quiz' ? 'Quizzes' : ($tab === 'engagement' && $pivot === 'module' ? 'Modules & materials' : 'Participants') }}
                </span>
                <span class="lt-nav-count" data-tracking-region="navigator-count">
                    {{ $tab === 'quiz' ? $quizzes->count() : ($tab === 'engagement' && $pivot === 'module' ? $modules->count() : $participants->count()) }}
                </span>
            </div>
            <button type="button" class="lt-collapse" data-tracking-collapse aria-label="Hide navigator" title="Hide navigator" aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>
        </div>

        @if ($tab === 'engagement')
            <div class="lt-pivot" aria-label="Engagement grouping">
                <a href="{{ $trackingQuery(['pivot' => 'participant', 'search' => $pivot === 'module' ? null : $search, 'engagement_page' => 1]) }}" class="{{ $pivot === 'participant' ? 'is-active' : '' }}" data-tracking-link data-tracking-action="pivot" data-tracking-regions="navigator main" data-tracking-active-group="pivot">By participant</a>
                <a href="{{ $trackingQuery(['pivot' => 'module', 'search' => $pivot === 'participant' ? null : $search, 'engagement_page' => 1]) }}" class="{{ $pivot === 'module' ? 'is-active' : '' }}" data-tracking-link data-tracking-action="pivot" data-tracking-regions="navigator main" data-tracking-active-group="pivot">By module</a>
            </div>
        @endif

            <label class="lt-search">
                <i class="bi bi-search"></i>
                @php $searchSubject = $tab === 'quiz' ? 'quizzes' : ($tab === 'engagement' && $pivot === 'module' ? 'modules or materials' : 'participants'); @endphp
                <span class="sr-only">Search {{ $searchSubject }}</span>
                <input type="search" value="{{ $search }}" placeholder="Search {{ $searchSubject }}" data-tracking-search="search" data-tracking-action="navigator-search" data-tracking-regions="navigator-list" autocomplete="off">
                <button type="button" class="lt-search-clear {{ $search === '' ? 'is-hidden' : '' }}" data-search-clear aria-label="Clear search"><i class="bi bi-x"></i></button>
            </label>
    </div>

    <div class="lt-nav-list" data-tracking-region="navigator-list">
        @if ($tab === 'quiz')
            @forelse ($quizzes as $quiz)
                <a href="{{ $trackingQuery(['quiz' => $quiz->assessment_id, 'quiz_page' => 1, 'attempt' => null]) }}"
                   class="lt-nav-item {{ (int) $selectedQuizId === (int) $quiz->assessment_id ? 'is-active' : '' }}" data-tracking-link data-tracking-action="selection" data-tracking-regions="main" data-tracking-selection>
                    <span class="lt-nav-icon"><i class="bi bi-ui-checks-grid"></i></span>
                    <span class="lt-nav-copy">
                        <strong>{{ $quiz->title ?: 'Untitled Quiz' }}</strong>
                        <small>{{ $quiz->attempted_participant_count }}/{{ $quiz->participant_count }} attempted · {{ $quiz->availability['context'] }}</small>
                    </span>
                    <span class="lt-badge is-{{ $quiz->availability['key'] }}">{{ $quiz->availability['label'] }}</span>
                </a>
            @empty
                <div class="lt-nav-empty"><i class="bi {{ $search ? 'bi-search' : 'bi-ui-checks-grid' }}"></i><h3>{{ $search ? 'No quizzes found' : 'No quizzes available' }}</h3><p>{{ $search ? 'Try a different quiz title.' : 'Quizzes created for this training will appear here.' }}</p></div>
            @endforelse
            @if ($quizzes->count() >= 100)
                <p class="lt-nav-limit">Showing the first 100 matches. Refine your search to narrow the list.</p>
            @endif
        @elseif ($tab === 'engagement' && $pivot === 'module')
            @forelse ($modules as $module)
                @php $moduleBlocks = $trackableBlocks->where('module_id', $module->module_id); @endphp
                <details class="lt-module" {{ (int) $selectedModuleId === (int) $module->module_id ? 'open' : '' }}>
                    <summary>
                        <span class="lt-nav-icon"><i class="bi bi-folder2"></i></span>
                        <span class="lt-nav-copy"><strong>{{ $module->module_title ?: 'Untitled Module' }}</strong><small>{{ $moduleBlocks->count() }} trackable material{{ $moduleBlocks->count() === 1 ? '' : 's' }}</small></span>
                        <i class="bi bi-chevron-down"></i>
                    </summary>
                    <div class="lt-module-items">
                        @if ($moduleBlocks->isEmpty())
                            <p>No trackable materials in this module.</p>
                        @else
                            <a href="{{ $trackingQuery(['module' => $module->module_id, 'material' => null, 'engagement_page' => 1]) }}" class="{{ (int) $selectedModuleId === (int) $module->module_id && ! $selectedMaterialId ? 'is-active' : '' }}" data-tracking-link data-tracking-action="selection" data-tracking-regions="main" data-tracking-selection>View all in module</a>
                            @foreach ($moduleBlocks as $block)
                                <a href="{{ $trackingQuery(['module' => $module->module_id, 'material' => $block->block_id, 'engagement_page' => 1]) }}" class="{{ (int) $selectedMaterialId === (int) $block->block_id ? 'is-active' : '' }}" data-tracking-link data-tracking-action="selection" data-tracking-regions="main" data-tracking-selection>{{ $block->content_title }}</a>
                            @endforeach
                        @endif
                    </div>
                </details>
            @empty
                <div class="lt-nav-empty"><i class="bi {{ $search ? 'bi-search' : 'bi-folder2-open' }}"></i><h3>{{ $search ? 'No modules or materials found' : 'No modules available' }}</h3><p>{{ $search ? 'Try a different search.' : 'Modules from Materials will appear here.' }}</p></div>
            @endforelse
            @if ($modules->count() >= 100)
                <p class="lt-nav-limit">Showing the first 100 matches. Refine your search to narrow the list.</p>
            @endif
        @else
            @forelse ($participants as $participant)
                <a href="{{ $trackingQuery(['participant' => $participant->enrollment_id, 'activity_page' => 1, 'engagement_page' => 1]) }}"
                   class="lt-nav-item {{ (int) $selectedParticipantId === (int) $participant->enrollment_id ? 'is-active' : '' }}" data-tracking-link data-tracking-action="selection" data-tracking-regions="main" data-tracking-selection>
                    <span class="lt-avatar">{{ strtoupper(substr($participant->participant_name ?? 'U', 0, 1)) }}</span>
                    <span class="lt-nav-copy"><strong>{{ $participant->participant_name }}</strong><small>{{ $participant->participant_email ?: 'No email recorded' }}</small></span>
                </a>
            @empty
                <div class="lt-nav-empty"><i class="bi {{ $search ? 'bi-search' : 'bi-people' }}"></i><h3>{{ $search ? 'No participants found' : 'No enrolled participants' }}</h3><p>{{ $search ? 'Try a different name or email.' : 'No participants are enrolled in this training.' }}</p></div>
            @endforelse
            @if ($participants->count() >= 100)
                <p class="lt-nav-limit">Showing the first 100 matches. Refine your search to narrow the list.</p>
            @endif
        @endif
    </div>
    </div>
    <button type="button" class="lt-nav-show" data-tracking-collapse aria-label="Show navigator" title="Show navigator" aria-expanded="false">
        <i class="bi bi-list-ul"></i>
    </button>
</aside>
