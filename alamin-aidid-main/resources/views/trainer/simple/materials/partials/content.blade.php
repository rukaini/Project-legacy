<div class="sw-shell">
    @include('trainer.simple.partials.header')
    <main class="sw-page app-workspace-shell">
        <section class="sw-panel" aria-labelledby="upload-material-title">
            <div class="sw-panel-header"><div><h2 class="sw-panel-title" id="upload-material-title">Upload Material</h2><p class="sw-panel-sub">Add a file for participants of this training.</p></div></div>
            <div class="sw-upload-body">
                <form id="simpleMaterialUploadForm" method="POST" action="{{ route('trainer.simple.materials.store', $course->course_id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="sw-upload-row">
                        <input id="materialFile" type="file" name="material_file" class="sw-native-file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.gif,.mp4,.mov,.mp3,.wav">
                        <label for="materialFile" class="sw-file-select"><i class="bi bi-paperclip"></i><span data-file-name>Choose a file</span></label>
                        <button type="submit" class="sw-primary-btn"><i class="bi bi-cloud-arrow-up"></i> Upload</button>
                    </div>
                    <p class="sw-hint">PDF, Word, PowerPoint, Excel, image, MP4 video, or MP3 audio. Maximum 20 MB.</p>
                    @error('material_file')<div class="sw-error">{{ $message }}</div>@enderror
                </form>
            </div>
        </section>

        <section class="sw-panel" aria-labelledby="learning-materials-title">
            <div class="sw-panel-header"><div><h2 class="sw-panel-title" id="learning-materials-title">Learning Materials</h2><p class="sw-panel-sub">Files available for this training.</p></div><span class="sw-panel-count">{{ $materialTotal }} file(s)</span></div>
            <form action="{{ route('trainer.simple.materials', $course->course_id) }}" method="GET" class="sw-report-toolbar" data-simple-report-form data-results-id="simpleMaterialResults">
                <label class="sw-filter-field sw-search-field"><span class="sw-filter-label">Search Materials</span><span class="sw-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $materialFilters['search'] }}" placeholder="Search materials..." autocomplete="off" data-simple-search><button type="button" class="sw-search-clear" data-simple-search-clear aria-label="Clear material search" @if ($materialFilters['search'] === '') hidden @endif>&times;</button></span></label>
                <label class="sw-filter-field"><span class="sw-filter-label">Sort By</span><select name="sort" class="sw-select"><option value="newest" @selected($materialFilters['sort'] === 'newest')>Newest first</option><option value="oldest" @selected($materialFilters['sort'] === 'oldest')>Oldest first</option><option value="name-asc" @selected($materialFilters['sort'] === 'name-asc')>Name A–Z</option><option value="name-desc" @selected($materialFilters['sort'] === 'name-desc')>Name Z–A</option></select></label>
                <label class="sw-filter-field"><span class="sw-filter-label">File Type</span><select name="type" class="sw-select"><option value="">All Types</option>@foreach ($materialTypeOptions as $type)<option value="{{ $type }}" @selected($materialFilters['type'] === $type)>{{ $type }}</option>@endforeach</select></label>
            </form>
            @include('trainer.simple.materials.partials.results')
        </section>
    </main>
</div>
