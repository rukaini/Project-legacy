                <section class="sw-panel mb-4" aria-labelledby="simple-training-team-title">
                    <div class="sw-panel-header">
                        <h2 class="sw-panel-title" id="simple-training-team-title">Training Team</h2>
                    </div>
                    <div class="sw-team">
                        @foreach ($trainingTeam as $teamMember)
                            <div class="sw-team-member">
                                <span class="sw-team-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($teamMember->trainer_name ?? 'T'), 0, 1)) }}</span>
                                <div class="sw-team-name">
                                    {{ $teamMember->trainer_name ?? 'Trainer' }}
                                    @if (!empty($teamMember->is_current))<span class="sw-team-you">You</span>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="sw-readiness mb-4">
                    <div class="sw-readiness-head">
                        <div class="sw-readiness-title">Training Readiness</div>
                        <div class="sw-readiness-summary">
                            <div class="sw-readiness-badge {{ $allReady ? 'ready' : 'partial' }}">
                                <i class="bi {{ $allReady ? 'bi-check2-circle' : 'bi-hourglass-split' }}"></i>
                            </div>
                            <div>
                                <div class="sw-readiness-status">
                                    {{ $allReady ? 'Ready to run' : $readyCount . ' of 3 ready' }}
                                </div>
                                <div class="sw-readiness-note">
                                    {{ $allReady ? 'Everything is set up for this training.' : 'Some setup items still need attention.' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sw-check-item">
                        <div class="sw-check-icon {{ $hasParticipants ? 'ok' : 'no' }}">
                            <i class="bi {{ $hasParticipants ? 'bi-check-lg' : 'bi-exclamation' }}"></i>
                        </div>
                        <div>
                            <div class="sw-check-label">Participants enrolled</div>
                            <div class="sw-check-sub">
                                {{ $hasParticipants ? $totalParticipants . ' participant(s) joined.' : 'No participant has enrolled yet.' }}
                            </div>
                        </div>
                    </div>

                    <div class="sw-check-item">
                        <div class="sw-check-icon {{ $hasMaterials ? 'ok' : 'no' }}">
                            <i class="bi {{ $hasMaterials ? 'bi-check-lg' : 'bi-exclamation' }}"></i>
                        </div>
                        <div>
                            <div class="sw-check-label">Learning materials prepared</div>
                            <div class="sw-check-sub">
                                {{ $hasMaterials ? $materialCount . ' file(s) uploaded.' : 'No material uploaded yet.' }}
                            </div>
                        </div>
                    </div>

                    <div class="sw-check-item">
                        <div class="sw-check-icon {{ $hasSessions ? 'ok' : 'no' }}">
                            <i class="bi {{ $hasSessions ? 'bi-check-lg' : 'bi-exclamation' }}"></i>
                        </div>
                        <div>
                            <div class="sw-check-label">Training session scheduled</div>
                            <div class="sw-check-sub">
                                {{ $hasSessions ? $sessions->count() . ' session(s) scheduled.' : 'No session scheduled yet.' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sw-panel mb-4">
                    <div class="sw-panel-header">
                        <h2 class="sw-panel-title">About This Training</h2>
                    </div>
                    <div class="sw-about-body">
                        @if (!empty($course->description))
                            <p class="sw-about-text">{{ $course->description }}</p>
                        @else
                            <p class="sw-about-text">No training description has been added yet.</p>
                        @endif
                    </div>
                </div>

                <div class="sw-materials-card">
                    <div class="sw-materials-icon"><i class="bi bi-folder2-open"></i></div>
                    <div class="sw-materials-title">Learning Materials</div>
                    <div class="sw-materials-value">{{ $materialCount }}</div>
                    <div class="sw-materials-text">File(s) uploaded and ready for participants.</div>
                    <a href="{{ $materialsUrl }}" class="sw-materials-btn">
                        <span><i class="bi bi-cloud-arrow-up me-2"></i> Manage Materials</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
