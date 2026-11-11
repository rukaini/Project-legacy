
                    <div id="trainer-certificate-panel"
                         class="pf-tab-panel {{ $activeTab === 'certificate' ? 'show' : '' }}"
                         role="tabpanel"
                         aria-labelledby="trainer-certificate-tab"
                         data-panel="certificate"
                         @if ($activeTab !== 'certificate') hidden @endif>
                        <div class="pf-panel-title">My Certificates</div>
                        <div class="pf-panel-sub">
                            Upload your professional certificates and qualifications. The admin can view these when reviewing your profile.
                            You can upload multiple certificates.
                        </div>

                        @if (! $certificateTableExists)
                            <div class="pf-setup-notice">
                                <i class="bi bi-database-exclamation"></i>
                                <div class="pf-setup-title">Certificate table not set up yet</div>
                                <div class="pf-setup-sub">
                                    The <strong>trainer_certificate</strong> table does not exist in the database.
                                    Please run the provided SQL, then reload this page.
                                </div>
                            </div>
                        @else
                            {{-- Upload form --}}
                            <form method="POST" action="{{ route('trainer.profile.certificate') }}" enctype="multipart/form-data" class="pf-cert-upload-form" id="pfCertUploadForm">
                                @csrf
                                <div class="pf-cert-upload-row">
                                    <div class="pf-cert-title-field">
                                        <input type="text"
                                               name="title"
                                               value="{{ old('title') }}"
                                               placeholder="Certificate title (optional — e.g. TESL Certificate)"
                                               maxlength="255">
                                    </div>
                                    <label class="pf-cert-file-btn">
                                        <i class="bi bi-paperclip"></i> Choose file
                                        <input type="file" name="certificate" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" id="pfCertFileInput" hidden required>
                                    </label>
                                    <span class="pf-cert-file-name" id="pfCertFileName">No file chosen</span>
                                    <button type="submit" class="pf-btn">
                                        <i class="bi bi-upload"></i> Upload
                                    </button>
                                </div>
                                <div class="pf-cert-upload-hint">
                                    PDF, DOC, DOCX, JPG, PNG · Max 10 MB. Add a title to help you identify each certificate later.
                                </div>
                                @error('certificate')<div class="pf-error">{{ $message }}</div>@enderror
                                @error('title')<div class="pf-error">{{ $message }}</div>@enderror
                            </form>

                            {{-- Certificate list --}}
                            @if ($certificates->isEmpty())
                                <div class="pf-empty" role="status">
                                    <i class="bi bi-patch-check" aria-hidden="true"></i>
                                    <div class="pf-empty-title">No certificates uploaded yet</div>
                                    <div class="pf-empty-sub">Upload your first certificate using the form above.</div>
                                </div>
                            @else
                                <div class="pf-cert-count-row">
                                    <div class="pf-cert-count-label">
                                        <strong>{{ $certificates->count() }}</strong> certificate{{ $certificates->count() === 1 ? '' : 's' }} uploaded
                                    </div>
                                </div>

                                <div class="pf-cert-list">
                                    @foreach ($certificates as $cert)
                                        @php
                                            $certUrl = asset('storage/' . $cert->file_path);
                                            $certFilename = basename($cert->file_path);
                                            $certTitle = $cert->title ?: $certFilename;
                                            $certUploaded = $fmtDate($cert->uploaded_at) ?? 'recently';
                                        @endphp
                                        <div class="pf-cert-card">
                                            <div class="pf-cert-icon">
                                                <i class="bi {{ $iconForFile($cert->file_path) }}"></i>
                                            </div>
                                            <div class="pf-cert-info">
                                                <div class="pf-cert-title-text">{{ $certTitle }}</div>
                                                <div class="pf-cert-sub">
                                                    <span><i class="bi bi-file-earmark"></i> {{ $certFilename }}</span>
                                                    <span><i class="bi bi-clock-history"></i> Uploaded {{ $certUploaded }}</span>
                                                </div>
                                            </div>
                                            <div class="pf-cert-actions">
                                                <a href="{{ $certUrl }}" target="_blank" rel="noopener" class="pf-link-btn">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <form method="POST"
                                                      action="{{ route('trainer.profile.certificate.remove', $cert->cert_id) }}"
                                                      style="margin: 0;"
                                                      onsubmit="return confirm('Remove this certificate? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="pf-link-btn danger">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
