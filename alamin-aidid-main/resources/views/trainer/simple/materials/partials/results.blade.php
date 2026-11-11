@php
    $fmtDate ??= fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d M Y') : null;
    $iconFor ??= function ($type) {
        return match (strtolower((string) $type)) {
            'pdf' => 'bi-file-earmark-pdf', 'presentation', 'slides' => 'bi-file-earmark-slides',
            'document' => 'bi-file-earmark-word', 'spreadsheet' => 'bi-file-earmark-spreadsheet',
            'image' => 'bi-file-earmark-image', 'video' => 'bi-file-earmark-play',
            'audio' => 'bi-file-earmark-music', default => 'bi-file-earmark',
        };
    };
@endphp
<div id="simpleMaterialResults" class="sw-results-region">
    <span class="sw-loading-label">Updating materials…</span>
    <div class="sw-report-error" data-simple-report-error hidden>Materials could not be refreshed. Please try again.</div>
    @if ($materials->isNotEmpty())
        <div class="sw-material-list">
            @foreach ($materials as $material)
                <article class="sw-material-row">
                    <div class="sw-material-icon"><i class="bi {{ $iconFor($material->material_type ?? '') }}"></i></div>
                    <div class="sw-material-main"><h3>{{ $material->original_file_name ?: 'Untitled file' }}</h3><div><span class="sw-type-badge">{{ $material->material_type ?: 'File' }}</span><span><i class="bi bi-clock-history"></i> Uploaded {{ $fmtDate($material->uploaded_at) ?? 'recently' }}</span></div></div>
                    <div class="sw-material-actions">
                        <a href="{{ route('trainer.simple.materials.view', $material->material_id) }}" target="_blank" rel="noopener" class="sw-row-action"><i class="bi bi-box-arrow-up-right"></i> View</a>
                        <form method="POST" action="{{ route('trainer.simple.materials.delete', $material->material_id) }}" onsubmit="return confirm('Delete this material? This cannot be undone.');">
                            @csrf
                            @foreach ($materialFilters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                            <button type="submit" class="sw-row-action danger"><i class="bi bi-trash3"></i> Delete</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        @include('trainer.simple.partials.paginator', ['paginator' => $materialPaginator, 'state' => $materialFilters, 'noun' => 'materials', 'reportPath' => route('trainer.simple.materials', $course->course_id)])
    @else
        <div class="sw-empty"><i class="bi {{ $materialTotal === 0 ? 'bi-folder2' : 'bi-search' }}"></i><div class="sw-empty-title">{{ $materialTotal === 0 ? 'No materials uploaded yet' : 'No materials match these filters' }}</div><div>{{ $materialTotal === 0 ? 'Use the upload form above to add your first file.' : 'Try changing the search, sort, or file type.' }}</div></div>
    @endif
</div>
