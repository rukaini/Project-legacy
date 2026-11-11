            {{-- Proposal form --}}
            <div class="pp-card">
                <div class="pp-card-header">
                    <div class="header-mark"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <h2 class="pp-card-title">Propose New Training</h2>
                        <div class="pp-card-sub">Fill in the details below. Fields marked with * are required.</div>
                    </div>
                </div>

                <div class="pp-card-body">
                    <form method="POST"
                          id="proposalSubmissionForm"
                          action="{{ route('trainer.proposals.store') }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="pp-form-grid">

                            <div class="pp-field span-2">
                                <label class="pp-label" for="proposed_title">
                                    Proposed Training Title <span class="req">*</span>
                                </label>
                                <input type="text"
                                       id="proposed_title"
                                       name="proposed_title"
                                       value="{{ old('proposed_title') }}"
                                       class="pp-input {{ $errors->has('proposed_title') ? 'is-invalid' : '' }}"
                                       placeholder="e.g. English Communication Skills for New Teachers"
                                       maxlength="255">
                                @error('proposed_title')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field">
                                <label class="pp-label" for="estimated_capacity">Estimated Capacity</label>
                                <input type="number"
                                       id="estimated_capacity"
                                       name="estimated_capacity"
                                       value="{{ old('estimated_capacity') }}"
                                       class="pp-input {{ $errors->has('estimated_capacity') ? 'is-invalid' : '' }}"
                                       placeholder="e.g. 30"
                                       min="1">
                                <div class="pp-hint">Approximate number of participants you expect.</div>
                                @error('estimated_capacity')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field">
                                <label class="pp-label" for="training_mode">
                                    Training Format <span class="req">*</span>
                                </label>
                                <select id="training_mode"
                                        name="training_mode"
                                        class="pp-select {{ $errors->has('training_mode') ? 'is-invalid' : '' }}">
                                    <option value="">— Select format —</option>
                                    <option value="LMS" {{ old('training_mode') === 'LMS' ? 'selected' : '' }}>
                                        LMS — serial training with modules &amp; quizzes
                                    </option>
                                    <option value="Simple" {{ old('training_mode') === 'Simple' ? 'selected' : '' }}>
                                        Simple — short one-time training session
                                    </option>
                                </select>
                                @error('training_mode')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field">
                                <label class="pp-label" for="delivery_mode">
                                    Delivery Mode <span class="req">*</span>
                                </label>
                                <select id="delivery_mode"
                                        name="delivery_mode"
                                        class="pp-select {{ $errors->has('delivery_mode') ? 'is-invalid' : '' }}">
                                    <option value="">— Select mode —</option>
                                    <option value="Online" {{ old('delivery_mode') === 'Online' ? 'selected' : '' }}>Online</option>
                                    <option value="Physical" {{ old('delivery_mode') === 'Physical' ? 'selected' : '' }}>Physical</option>
                                    <option value="Hybrid" {{ old('delivery_mode') === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                                </select>
                                @error('delivery_mode')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field">
                                <label class="pp-label" for="proposed_start_date">Proposed Start Date</label>
                                <input type="date"
                                       id="proposed_start_date"
                                       name="proposed_start_date"
                                       value="{{ old('proposed_start_date') }}"
                                       min="{{ now()->format('Y-m-d') }}"
                                       class="pp-input {{ $errors->has('proposed_start_date') ? 'is-invalid' : '' }}">
                                <div class="pp-hint">Cannot be earlier than today.</div>
                                @error('proposed_start_date')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field">
                                <label class="pp-label" for="proposed_end_date">Proposed End Date</label>
                                <input type="date"
                                       id="proposed_end_date"
                                       name="proposed_end_date"
                                       value="{{ old('proposed_end_date') }}"
                                       min="{{ now()->format('Y-m-d') }}"
                                       class="pp-input {{ $errors->has('proposed_end_date') ? 'is-invalid' : '' }}">
                                <div class="pp-hint">Leave empty for a single-day training. Must be on or after the start date.</div>
                                @error('proposed_end_date')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field span-2">
                                <label class="pp-label">Target Audience <span class="req">*</span></label>
                                <div class="pp-audience-group">
                                    <label class="pp-audience">
                                        <input type="checkbox" name="target_audience[]" value="Teacher"
                                            {{ in_array('Teacher', $oldAudience) ? 'checked' : '' }}>
                                        <span class="pp-audience-box"><i class="bi bi-mortarboard"></i> Teacher</span>
                                    </label>
                                    <label class="pp-audience">
                                        <input type="checkbox" name="target_audience[]" value="New Teacher"
                                            {{ in_array('New Teacher', $oldAudience) ? 'checked' : '' }}>
                                        <span class="pp-audience-box"><i class="bi bi-person-badge"></i> New Teacher</span>
                                    </label>
                                    <label class="pp-audience">
                                        <input type="checkbox" name="target_audience[]" value="Public"
                                            {{ in_array('Public', $oldAudience) ? 'checked' : '' }}>
                                        <span class="pp-audience-box"><i class="bi bi-people"></i> Public</span>
                                    </label>
                                </div>
                                <div class="pp-hint">Select every group this training is meant for.</div>
                                @error('target_audience')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field span-2">
                                <label class="pp-label" for="objective">
                                    Training Objective <span class="req">*</span>
                                </label>
                                <textarea id="objective"
                                          name="objective"
                                          class="pp-textarea {{ $errors->has('objective') ? 'is-invalid' : '' }}"
                                          placeholder="What should participants be able to do after this training?"
                                          maxlength="3000">{{ old('objective') }}</textarea>
                                @error('objective')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field span-2">
                                <label class="pp-label" for="description">Description / Justification</label>
                                <textarea id="description"
                                          name="description"
                                          class="pp-textarea {{ $errors->has('description') ? 'is-invalid' : '' }}"
                                          placeholder="Briefly explain the training content and why it is needed."
                                          maxlength="5000">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="pp-field span-2">
                                <label class="pp-label" for="proposal_document">Official Document</label>
                                <input type="file"
                                       id="proposal_document"
                                       name="proposal_document"
                                       class="pp-file"
                                       accept=".pdf,.doc,.docx">
                                <div class="pp-hint">Optional. Accepted: PDF, DOC, DOCX. Maximum size 10 MB.</div>
                                @error('proposal_document')
                                    <div class="pp-error">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="pp-form-foot">
                            <div class="pp-foot-note">
                                <i class="bi bi-info-circle"></i>
                                Submitted proposals start as <strong>&nbsp;Pending</strong>&nbsp; until the admin reviews them.
                            </div>
                            <button type="submit" class="pp-btn">
                                <i class="bi bi-send"></i>
                                Submit Proposal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
