<main class="adm-wrap adm-wrap--wide app-page-shell" data-admin-page-shell data-admin-width="wide">
    <div class="adm-head">
        <div class="adm-head-row">
            <div class="adm-head-icon"><i class="bi bi-collection" aria-hidden="true"></i></div>
            <div>
                <h1 class="adm-title">Directory</h1>
                <p class="adm-subtitle">Browse participants, trainers, trainings, and sessions across the training centre.</p>
            </div>
        </div>
    </div>

    <section class="dir-directory-shell" data-directory-workspace>
        <div class="dir-section-tabs" role="tablist" aria-label="Directory sections">
            <button type="button" class="dir-section-tab active" id="directory-tab-participants" data-tab="participants" role="tab" aria-controls="tab-participants" aria-selected="true">
                <i class="bi bi-people" aria-hidden="true"></i><span>Participants</span><span class="dir-tab-count">{{ $participants->count() }}</span>
            </button>
            <button type="button" class="dir-section-tab" id="directory-tab-trainers" data-tab="trainers" role="tab" aria-controls="tab-trainers" aria-selected="false" tabindex="-1">
                <i class="bi bi-person-badge" aria-hidden="true"></i><span>Trainers</span><span class="dir-tab-count">{{ $trainers->count() }}</span>
            </button>
            <button type="button" class="dir-section-tab" id="directory-tab-courses" data-tab="courses" role="tab" aria-controls="tab-courses" aria-selected="false" tabindex="-1">
                <i class="bi bi-mortarboard" aria-hidden="true"></i><span>Trainings</span><span class="dir-tab-count">{{ $courses->count() }}</span>
            </button>
            <button type="button" class="dir-section-tab" id="directory-tab-sessions" data-tab="sessions" role="tab" aria-controls="tab-sessions" aria-selected="false" tabindex="-1">
                <i class="bi bi-calendar3" aria-hidden="true"></i><span>Sessions</span><span class="dir-tab-count">{{ $sessions->count() }}</span>
            </button>
        </div>

        <div class="dir-tabpanel active" id="tab-participants" role="tabpanel" aria-labelledby="directory-tab-participants" data-directory-panel data-directory-noun="participants" data-empty-title="No participants recorded" data-empty-message="Participant records will appear here when they become available." data-no-results-title="No matching participants" data-no-results-message="Try changing the search term or participant filters.">
            <div class="dir-toolbar" role="group" aria-label="Participant directory controls">
                <div class="dir-control dir-search-field">
                    <label for="directoryParticipantSearch">Search</label>
                    <div class="dir-search-control">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="directoryParticipantSearch" class="dir-search" placeholder="Search participant name or classification..." autocomplete="off">
                        <button type="button" class="dir-search-clear" data-directory-search-clear aria-label="Clear participant search" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="dir-control">
                    <label for="directoryParticipantType">Classification</label>
                    <select id="directoryParticipantType" class="dir-filter" data-key="type">
                        <option value="all">All classifications</option>
                        <option value="Teacher">Teacher</option>
                        <option value="Guru Baru">New Teacher</option>
                        <option value="Outsider">Public</option>
                    </select>
                </div>
                <div class="dir-control">
                    <label for="directoryParticipantTraining">Training</label>
                    <select id="directoryParticipantTraining" class="dir-filter" data-key="courses" data-match="contains">
                        <option value="all">Any training</option>
                        @foreach ($courseOptions as $cid => $cname)
                            <option value="{{ $cid }}">{{ $cname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="dir-control">
                    <label for="directoryParticipantSession">Session</label>
                    <select id="directoryParticipantSession" class="dir-filter" data-key="sessions" data-match="contains">
                        <option value="all">Any session</option>
                        @foreach ($sessionOptions as $sid => $slabel)
                            <option value="{{ $sid }}">{{ $slabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="dir-control dir-sort-control">
                    <label for="directoryParticipantSort">Sort</label>
                    <select id="directoryParticipantSort" class="dir-sort">
                        <option value="name-asc">Name A–Z</option>
                        <option value="name-desc">Name Z–A</option>
                    </select>
                </div>
                <button type="button" class="dir-reset" disabled><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset</button>
            </div>

            <div class="dir-results" data-directory-results>
                <div class="dir-table-frame" data-directory-table-frame>
                    <div class="dir-table-scroll">
                        <table class="dir-table" data-directory-table>
                            <thead><tr><th>Name</th><th>Classification</th><th>Enrolled Trainings</th><th class="dir-action-heading">Action</th></tr></thead>
                            <tbody>
                                @foreach ($participants as $p)
                                    <tr class="dir-row" tabindex="0" data-inspectable data-search="{{ $p->name }} {{ $p->type }}" data-sort-name="{{ mb_strtolower($p->name) }}" data-type="{{ $p->type_value }}" data-courses=",{{ implode(',', $p->course_ids) }}," data-sessions=",{{ implode(',', $p->session_ids) }},">
                                        <td><span class="dir-identity"><span class="dir-avatar" aria-hidden="true">{{ strtoupper(mb_substr($p->name, 0, 1)) }}</span><span><strong>{{ $p->name }}</strong><small>Participant</small></span></span></td>
                                        <td><span class="dir-badge {{ $p->type === 'Teacher' ? 'blue' : ($p->type === 'New Teacher' ? 'indigo' : 'amber') }}">{{ $p->type }}</span></td>
                                        <td><span class="dir-count-value">{{ $p->courses }}</span> {{ (int) $p->courses === 1 ? 'training' : 'trainings' }}</td>
                                        <td class="dir-action-cell"><button type="button" class="dir-view" data-d-kind="participant" data-d-name="{{ $p->name }}" data-d-sub="{{ $p->type }}" data-d-courses="{{ $p->courses }}" data-d-sessions="{{ count($p->session_ids) }}" data-d-details="{{ json_encode($p->details) }}">View<span class="visually-hidden"> {{ $p->name }}</span></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="dir-empty-state" data-directory-empty role="status" hidden><span class="dir-empty-icon" aria-hidden="true"><i class="bi bi-people"></i></span><h3 data-directory-empty-title></h3><p data-directory-empty-message></p></div>
                <div class="dir-pager" data-directory-pager></div>
            </div>
        </div>

        <div class="dir-tabpanel" id="tab-trainers" role="tabpanel" aria-labelledby="directory-tab-trainers" data-directory-panel data-directory-noun="trainers" data-empty-title="No trainers recorded" data-empty-message="Trainer records will appear here when they become available." data-no-results-title="No matching trainers" data-no-results-message="Try changing the search term or trainer filters." hidden>
            <div class="dir-toolbar" role="group" aria-label="Trainer directory controls">
                <div class="dir-control dir-search-field"><label for="directoryTrainerSearch">Search</label><div class="dir-search-control"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="directoryTrainerSearch" class="dir-search" placeholder="Search name, expertise, or status..." autocomplete="off"><button type="button" class="dir-search-clear" data-directory-search-clear aria-label="Clear trainer search" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button></div></div>
                <div class="dir-control"><label for="directoryTrainerStatus">Status</label><select id="directoryTrainerStatus" class="dir-filter" data-key="status"><option value="all">All statuses</option>@foreach ($trainerStatuses as $st)<option value="{{ $st }}">{{ ucfirst($st) }}</option>@endforeach</select></div>
                <div class="dir-control"><label for="directoryTrainerTraining">Training</label><select id="directoryTrainerTraining" class="dir-filter" data-key="courses" data-match="contains"><option value="all">Any training</option>@foreach ($courseOptions as $cid => $cname)<option value="{{ $cid }}">{{ $cname }}</option>@endforeach</select></div>
                <div class="dir-control"><label for="directoryTrainerSession">Session</label><select id="directoryTrainerSession" class="dir-filter" data-key="sessions" data-match="contains"><option value="all">Any session</option>@foreach ($sessionOptions as $sid => $slabel)<option value="{{ $sid }}">{{ $slabel }}</option>@endforeach</select></div>
                <div class="dir-control dir-sort-control"><label for="directoryTrainerSort">Sort</label><select id="directoryTrainerSort" class="dir-sort"><option value="name-asc">Name A–Z</option><option value="name-desc">Name Z–A</option></select></div>
                <button type="button" class="dir-reset" disabled><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset</button>
            </div>
            <div class="dir-results" data-directory-results>
                <div class="dir-table-frame" data-directory-table-frame><div class="dir-table-scroll"><table class="dir-table" data-directory-table>
                    <thead><tr><th>Name</th><th>Expertise</th><th>Status</th><th>Trainings</th><th class="dir-action-heading">Action</th></tr></thead>
                    <tbody>@foreach ($trainers as $t)<tr class="dir-row" tabindex="0" data-inspectable data-search="{{ $t->trainer_name }} {{ $t->expertise }} {{ $t->status }}" data-sort-name="{{ mb_strtolower($t->trainer_name) }}" data-status="{{ $t->status }}" data-courses=",{{ implode(',', $t->course_ids) }}," data-sessions=",{{ implode(',', $t->session_ids) }},">
                        <td><span class="dir-identity"><span class="dir-avatar trainer" aria-hidden="true">{{ strtoupper(mb_substr($t->trainer_name, 0, 1)) }}</span><span><strong>{{ $t->trainer_name }}</strong><small>Trainer</small></span></span></td>
                        <td><span class="dir-expertise" title="{{ $t->expertise ?: 'Not recorded' }}">{{ $t->expertise ?: 'Not recorded' }}</span></td>
                        <td><span class="dir-badge {{ strtolower((string) $t->status) === 'active' ? 'green' : 'slate' }}">{{ $t->status ?: 'Unknown' }}</span></td>
                        <td><span class="dir-count-value">{{ $t->course_count }}</span> {{ (int) $t->course_count === 1 ? 'training' : 'trainings' }}</td>
                        <td class="dir-action-cell"><button type="button" class="dir-view" data-d-kind="trainer" data-d-name="{{ $t->trainer_name }}" data-d-sub="{{ $t->expertise }}" data-d-courses="{{ $t->course_count }}" data-d-sessions="{{ count($t->session_ids) }}" data-d-details="{{ json_encode($t->details) }}" data-d-certificates="{{ json_encode($t->certificates) }}">View<span class="visually-hidden"> {{ $t->trainer_name }}</span></button></td>
                    </tr>@endforeach</tbody>
                </table></div></div>
                <div class="dir-empty-state" data-directory-empty role="status" hidden><span class="dir-empty-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span><h3 data-directory-empty-title></h3><p data-directory-empty-message></p></div>
                <div class="dir-pager" data-directory-pager></div>
            </div>
        </div>

        <div class="dir-tabpanel" id="tab-courses" role="tabpanel" aria-labelledby="directory-tab-courses" data-directory-panel data-directory-noun="trainings" data-empty-title="No trainings recorded" data-empty-message="Training records will appear here when they become available." data-no-results-title="No matching trainings" data-no-results-message="Try changing the search term or training filters." hidden>
            <div class="dir-toolbar" role="group" aria-label="Training directory controls">
                <div class="dir-control dir-search-field"><label for="directoryTrainingSearch">Search</label><div class="dir-search-control"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="directoryTrainingSearch" class="dir-search" placeholder="Search title, category, or trainer..." autocomplete="off"><button type="button" class="dir-search-clear" data-directory-search-clear aria-label="Clear training search" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button></div></div>
                <div class="dir-control"><label for="directoryTrainingCategory">Category</label><select id="directoryTrainingCategory" class="dir-filter" data-key="category"><option value="all">All categories</option>@foreach ($courseCategories as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach</select></div>
                <div class="dir-control"><label for="directoryTrainingMode">Mode</label><select id="directoryTrainingMode" class="dir-filter" data-key="mode"><option value="all">All modes</option><option value="LMS">LMS</option><option value="Simple">Simple</option></select></div>
                <div class="dir-control"><label for="directoryTrainingTrainer">Trainer</label><select id="directoryTrainingTrainer" class="dir-filter" data-key="trainers" data-match="contains"><option value="all">Any trainer</option>@foreach ($trainerOptions as $tid => $tname)<option value="{{ $tid }}">{{ $tname }}</option>@endforeach</select></div>
                <div class="dir-control dir-sort-control"><label for="directoryTrainingSort">Sort</label><select id="directoryTrainingSort" class="dir-sort"><option value="name-asc">Title A–Z</option><option value="name-desc">Title Z–A</option></select></div>
                <button type="button" class="dir-reset" disabled><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset</button>
            </div>
            <div class="dir-results" data-directory-results>
                <div class="dir-table-frame" data-directory-table-frame><div class="dir-table-scroll"><table class="dir-table" data-directory-table>
                    <thead><tr><th>Training</th><th>Category</th><th>Mode</th><th>Trainer(s)</th><th>Session Schedule</th><th>Participants</th></tr></thead>
                    <tbody>@foreach ($courses as $c)<tr class="dir-row" data-search="{{ $c->course_name }} {{ $c->course_category }} {{ implode(' ', $c->trainer_names) }}" data-sort-name="{{ mb_strtolower($c->course_name) }}" data-category="{{ $c->course_category }}" data-mode="{{ $c->is_lms ? 'LMS' : 'Simple' }}" data-trainers=",{{ implode(',', $c->trainer_ids) }},">
                        <td><a href="{{ route('admin.courses', ['course' => $c->course_id]) }}" class="dir-training-link">{{ $c->course_name }}<i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></td>
                        <td>{{ $c->course_category ?: 'Not recorded' }}</td>
                        <td><span class="dir-badge {{ $c->is_lms ? 'indigo' : 'blue' }}">{{ $c->is_lms ? 'LMS' : 'Simple' }}</span></td>
                        <td>{{ $c->trainer_names ? implode(', ', $c->trainer_names) : 'Unassigned' }}</td>
                        <td><span class="dir-schedule-value"><strong>{{ (int) $c->held_sessions }} / {{ (int) $c->total_sessions }}</strong><small>elapsed / total by session date</small></span></td>
                        <td><span class="dir-count-value">{{ (int) $c->enrolled }}</span></td>
                    </tr>@endforeach</tbody>
                </table></div></div>
                <div class="dir-empty-state" data-directory-empty role="status" hidden><span class="dir-empty-icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span><h3 data-directory-empty-title></h3><p data-directory-empty-message></p></div>
                <div class="dir-pager" data-directory-pager></div>
            </div>
        </div>

        <div class="dir-tabpanel" id="tab-sessions" role="tabpanel" aria-labelledby="directory-tab-sessions" data-directory-panel data-directory-noun="sessions" data-empty-title="No sessions recorded" data-empty-message="Session records will appear here when they become available." data-no-results-title="No matching sessions" data-no-results-message="Try changing the search term or session filters." hidden>
            <div class="dir-toolbar" role="group" aria-label="Session directory controls">
                <div class="dir-control dir-search-field"><label for="directorySessionSearch">Search</label><div class="dir-search-control"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="directorySessionSearch" class="dir-search" placeholder="Search session, training, or trainer..." autocomplete="off"><button type="button" class="dir-search-clear" data-directory-search-clear aria-label="Clear session search" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button></div></div>
                <div class="dir-control"><label for="directorySessionTraining">Training</label><select id="directorySessionTraining" class="dir-filter" data-key="course"><option value="all">All trainings</option>@foreach ($courseOptions as $cid => $cname)<option value="{{ $cid }}">{{ $cname }}</option>@endforeach</select></div>
                <div class="dir-control"><label for="directorySessionTrainer">Trainer</label><select id="directorySessionTrainer" class="dir-filter" data-key="trainers" data-match="contains"><option value="all">Any trainer</option>@foreach ($trainerOptions as $tid => $tname)<option value="{{ $tid }}">{{ $tname }}</option>@endforeach</select></div>
                <div class="dir-control"><label for="directorySessionType">Delivery Type</label><select id="directorySessionType" class="dir-filter" data-key="type"><option value="all">All types</option>@foreach ($sessionTypes as $sty)<option value="{{ $sty }}">{{ $sty }}</option>@endforeach</select></div>
                <div class="dir-control dir-sort-control"><label for="directorySessionSort">Sort</label><select id="directorySessionSort" class="dir-sort"><option value="date-desc">Newest date</option><option value="date-asc">Oldest date</option><option value="name-asc">Session A–Z</option></select></div>
                <button type="button" class="dir-reset" disabled><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset</button>
            </div>
            <div class="dir-results" data-directory-results>
                <div class="dir-table-frame" data-directory-table-frame><div class="dir-table-scroll"><table class="dir-table" data-directory-table>
                    <thead><tr><th>Session</th><th>Training</th><th>Date</th><th>Type</th><th>Trainer(s)</th></tr></thead>
                    <tbody>@foreach ($sessions as $s)@php($trainerList = !empty($s->trainer_names) ? implode(', ', $s->trainer_names) : '')<tr class="dir-row" data-search="{{ $s->session_title }} {{ $s->course_name }} {{ $trainerList }}" data-sort-name="{{ mb_strtolower($s->session_title ?: 'Training session') }}" data-sort-date="{{ $s->date ? \Carbon\Carbon::parse($s->date)->timestamp : 0 }}" data-course="{{ $s->course_id }}" data-trainers=",{{ implode(',', $s->trainer_ids) }}," data-type="{{ $s->session_type }}">
                        <td><span class="dir-session-title"><strong>{{ $s->session_title ?: 'Training session' }}</strong>@if (data_get($s, 'location'))<small><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ data_get($s, 'location') }}</small>@endif</span></td>
                        <td>{{ $s->course_name ?: 'Not recorded' }}</td>
                        <td><span class="dir-date-value"><strong>{{ $s->date ? \Carbon\Carbon::parse($s->date)->format('d M Y') : 'Not recorded' }}</strong>@if (data_get($s, 'start_time'))<small>{{ \Carbon\Carbon::parse(data_get($s, 'start_time'))->format('g:i A') }}@if (data_get($s, 'end_time')) – {{ \Carbon\Carbon::parse(data_get($s, 'end_time'))->format('g:i A') }}@endif</small>@endif</span></td>
                        <td><span class="dir-badge {{ strtolower((string) $s->session_type) === 'online' ? 'blue' : (strtolower((string) $s->session_type) === 'physical' ? 'green' : 'slate') }}">{{ $s->session_type ?: 'Unknown' }}</span></td>
                        <td>{{ $trainerList !== '' ? $trainerList : 'Unassigned' }}</td>
                    </tr>@endforeach</tbody>
                </table></div></div>
                <div class="dir-empty-state" data-directory-empty role="status" hidden><span class="dir-empty-icon" aria-hidden="true"><i class="bi bi-calendar-x"></i></span><h3 data-directory-empty-title></h3><p data-directory-empty-message></p></div>
                <div class="dir-pager" data-directory-pager></div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade dir-detail-modal" id="viewModal" tabindex="-1" aria-labelledby="vmName" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="vm-banner" id="vmBanner">
                <button type="button" class="vm-close" data-bs-dismiss="modal" aria-label="Close directory details"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                <div class="vm-top">
                    <div class="vm-avatar" id="vmAvatar" aria-hidden="true">?</div>
                    <div class="vm-headtxt"><h2 class="vm-name" id="vmName"></h2><div class="vm-sub" id="vmSub"></div><span class="vm-tag" id="vmTag" hidden></span></div>
                </div>
            </div>
            <div class="vm-stats" id="vmStats"></div>
            <div class="vm-body">
                <h3 class="vm-section-title">Profile details</h3>
                <div class="vm-grid" id="vmGrid"></div>
                <section class="vm-certificates" id="vmCertificatesSection" aria-labelledby="vmCertificatesTitle" hidden>
                    <h3 class="vm-section-title" id="vmCertificatesTitle">Certificates</h3>
                    <div class="vm-certificate-list" id="vmCertificates"></div>
                </section>
            </div>
        </div>
    </div>
</div>
