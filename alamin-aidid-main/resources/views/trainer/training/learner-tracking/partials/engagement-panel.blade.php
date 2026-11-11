@php $records = $engagement['rows']; $selectedMaterial = $trackableBlocks->firstWhere('block_id', $selectedMaterialId); @endphp

<section class="lt-card">
    <header class="lt-card-head lt-result-head">
        <div>
            <p class="lt-eyebrow">{{ $pivot === 'module' ? 'By module' : 'By participant' }}</p>
            @if ($pivot === 'participant')
                <h2>{{ $selectedParticipant?->participant_name ?? 'No participant selected' }}</h2>
                <p>{{ $selectedParticipant ? ($selectedParticipant->participant_email ?: 'No email recorded') : 'Choose an enrolled participant.' }}</p>
            @else
                <h2>{{ $selectedMaterial?->content_title ?? $selectedModule?->module_title ?? 'No module selected' }}</h2>
                <p>{{ $selectedMaterial ? ($selectedModule?->module_title ?? 'Module') : 'All trackable materials in the selected module.' }}</p>
            @endif
        </div>
        @if ($pivot === 'participant' && $modules->isNotEmpty())
            <label class="lt-filter">Module
                <select data-tracking-filter="module" data-tracking-regions="engagement-results">
                    <option value="">All modules</option>
                    @foreach ($modules as $module)<option value="{{ $module->module_id }}" {{ (int) request('module') === (int) $module->module_id ? 'selected' : '' }}>{{ $module->module_title ?: 'Untitled Module' }}</option>@endforeach
                </select>
            </label>
        @endif
    </header>

    <div data-tracking-region="engagement-results">
        @if (! $tracking['available'])
            <div class="lt-empty"><i class="bi bi-database-slash"></i><h3>Material activity is not available from the connected tracking source</h3><p>{{ $tracking['reason'] }}</p></div>
        @elseif ($pivot === 'module' && $selectedModule && $trackableBlocks->where('module_id', $selectedModuleId)->isEmpty())
            <div class="lt-empty"><i class="bi bi-folder2-open"></i><h3>This module does not contain trackable materials</h3><p>Title, text, Quiz, and Submission blocks are not material-access records.</p></div>
        @elseif (! $selectedParticipant && $pivot === 'participant')
            <div class="lt-empty"><i class="bi bi-people"></i><h3>No participants are enrolled in this training</h3><p>There is no enrolled participant to report.</p></div>
        @elseif ($records->isEmpty())
            <div class="lt-empty"><i class="bi bi-search"></i><h3>No recorded material activity matches these filters</h3><p>Try another participant, module, material, or activity period.</p></div>
        @else
            <div class="lt-table-scroll">
            <table class="lt-table">
                <thead><tr>
                    @if ($pivot === 'module')<th>Participant</th>@else<th>Material</th>@endif
                    @if ($pivot === 'module' && ! $selectedMaterialId)<th>Material</th>@endif
                    <th>Module</th><th class="t-center">Status</th><th>First opened</th><th>Last accessed</th><th class="t-center">Opens</th><th class="t-center">Tracked time</th>
                </tr></thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            @if ($pivot === 'module')
                                <td><div class="lt-person-cell"><span class="lt-avatar">{{ strtoupper(substr($record->participant_name ?? 'U', 0, 1)) }}</span><span><strong>{{ $record->participant_name }}</strong><small>{{ $record->participant_email ?: 'No email recorded' }}</small></span></div></td>
                            @else
                                <td><strong>{{ $record->content_title }}</strong><small class="lt-cell-sub">{{ $record->content_type_label }}</small></td>
                            @endif
                            @if ($pivot === 'module' && ! $selectedMaterialId)<td><strong>{{ $record->content_title }}</strong><small class="lt-cell-sub">{{ $record->content_type_label }}</small></td>@endif
                            <td>{{ $record->module_title }}</td>
                            <td class="t-center"><span class="lt-badge {{ $record->accessed ? 'is-open' : 'is-neutral' }}">{{ $record->accessed ? 'Accessed' : 'Not accessed' }}</span></td>
                            <td>{{ $record->first_opened_at ? \Carbon\Carbon::parse($record->first_opened_at)->format('d M Y, h:i A') : '—' }}</td>
                            <td>{{ $record->last_accessed_at ? \Carbon\Carbon::parse($record->last_accessed_at)->format('d M Y, h:i A') : '—' }}</td>
                            <td class="t-center">{{ $record->accessed ? $record->open_count : '—' }}</td>
                            <td class="t-center">{{ $record->tracked_time ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @include('trainer.training.learner-tracking.partials.pagination', ['paginator' => $records, 'pageName' => 'engagement_page', 'trackingRegions' => 'engagement-results'])
        @endif
    </div>
</section>
