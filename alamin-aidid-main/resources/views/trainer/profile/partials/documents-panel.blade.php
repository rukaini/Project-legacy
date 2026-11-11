
                    <div id="trainer-documents-panel"
                         class="pf-tab-panel {{ $activeTab === 'documents' ? 'show' : '' }}"
                         role="tabpanel"
                         aria-labelledby="trainer-documents-tab"
                         data-panel="documents"
                         @if ($activeTab !== 'documents') hidden @endif>
                        <div class="pf-panel-title">Official Documents</div>
                        <div class="pf-panel-sub">Documents sent to you by the admin (memos, invitation letters, announcements, etc).</div>

                        @if (! $documentTableExists)
                            <div class="pf-setup-notice">
                                <i class="bi bi-database-exclamation"></i>
                                <div class="pf-setup-title">Document table not set up yet</div>
                                <div class="pf-setup-sub">
                                    The <strong>trainer_document</strong> table does not exist in the database.
                                    Please run the provided SQL, then reload this page.
                                </div>
                            </div>
                        @elseif ($documents->isEmpty())
                            <div class="pf-empty" role="status">
                                <i class="bi bi-folder2" aria-hidden="true"></i>
                                <div class="pf-empty-title">No documents yet</div>
                                <div class="pf-empty-sub">Official documents sent to you by the admin will appear here.</div>
                            </div>
                        @else
                            <div class="pf-doc-controls">
                                <div class="pf-doc-search">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                    <input type="search" id="docSearch" placeholder="Search by title or type..." aria-label="Search official documents">
                                </div>
                                <select id="docSort" class="pf-doc-sort" aria-label="Sort official documents">
                                    <option value="newest">Newest first</option>
                                    <option value="oldest">Oldest first</option>
                                    <option value="type">Type (A–Z)</option>
                                    <option value="unread">Unread first</option>
                                </select>
                            </div>

                            <div id="docList">
                                @foreach ($documents as $doc)
                                    <div class="pf-doc-card {{ empty($doc->is_read) ? 'is-unread' : '' }}"
                                         data-title="{{ strtolower($doc->title ?? '') }}"
                                         data-type="{{ strtolower($doc->document_type ?: 'document') }}"
                                         data-desc="{{ strtolower($doc->description ?? '') }}"
                                         data-ts="{{ $doc->created_at }}"
                                         data-read="{{ empty($doc->is_read) ? 0 : 1 }}">
                                        <div class="pf-doc-icon"><i class="bi bi-file-earmark-text"></i></div>
                                        <div class="pf-doc-main">
                                            <div class="pf-doc-title">
                                                @if (empty($doc->is_read))<span class="pf-doc-unread" title="Unread"></span>@endif{{ $doc->title }}
                                            </div>
                                            @if (!empty($doc->description))
                                                <div class="pf-doc-desc">{{ $doc->description }}</div>
                                            @endif
                                            <div class="pf-doc-foot">
                                                <span class="pf-doc-date">
                                                    <i class="bi bi-clock-history"></i>
                                                    Received {{ $fmtDate($doc->created_at) ?? 'recently' }}
                                                </span>
                                                <a href="{{ route('trainer.profile.document.view', $doc->document_id) }}"
                                                   target="_blank"
                                                   rel="noopener"
                                                   class="pf-doc-link js-doc-view"
                                                   data-doc-id="{{ $doc->document_id }}">
                                                    <i class="bi bi-download"></i> View / Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="pf-empty pf-doc-noresult" id="docNoResult" role="status">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <div class="pf-empty-title">No documents match your search</div>
                                <div class="pf-empty-sub">Try a different keyword or clear the search.</div>
                            </div>

                            <div class="pf-pager" id="docPager"></div>
                        @endif
                    </div>
