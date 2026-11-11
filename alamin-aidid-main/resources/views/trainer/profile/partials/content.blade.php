<div class="profile-shell">
    <main class="profile-page app-page-shell">
        <header class="profile-head">
            <h1 class="profile-title">My Profile</h1>
            <p class="profile-subtitle">View your trainer account details, manage your certificates, and access official documents.</p>
        </header>

        <div class="pf-card pf-profile-workspace">
            <div class="pf-tab-nav" role="tablist" aria-label="Trainer profile sections">
                <button type="button"
                        id="trainer-profile-tab"
                        class="pf-tab-btn {{ $activeTab === 'profile' ? 'active' : '' }}"
                        role="tab"
                        data-tab="profile"
                        aria-controls="trainer-profile-panel"
                        aria-selected="{{ $activeTab === 'profile' ? 'true' : 'false' }}"
                        tabindex="{{ $activeTab === 'profile' ? '0' : '-1' }}">
                    <i class="bi bi-person" aria-hidden="true"></i> Profile
                </button>
                <button type="button"
                        id="trainer-certificate-tab"
                        class="pf-tab-btn {{ $activeTab === 'certificate' ? 'active' : '' }}"
                        role="tab"
                        data-tab="certificate"
                        aria-controls="trainer-certificate-panel"
                        aria-selected="{{ $activeTab === 'certificate' ? 'true' : 'false' }}"
                        tabindex="{{ $activeTab === 'certificate' ? '0' : '-1' }}">
                    <i class="bi bi-patch-check" aria-hidden="true"></i> Certificates
                    <span class="pf-tab-count" aria-label="{{ $certificates->count() }} certificates">{{ $certificates->count() }}</span>
                </button>
                <button type="button"
                        id="trainer-documents-tab"
                        class="pf-tab-btn {{ $activeTab === 'documents' ? 'active' : '' }}"
                        role="tab"
                        data-tab="documents"
                        aria-controls="trainer-documents-panel"
                        aria-selected="{{ $activeTab === 'documents' ? 'true' : 'false' }}"
                        tabindex="{{ $activeTab === 'documents' ? '0' : '-1' }}">
                    <i class="bi bi-folder2-open" aria-hidden="true"></i> Documents
                    <span class="pf-tab-count" aria-label="{{ $documentCount }} documents">{{ $documentCount }}</span>
                </button>
            </div>

            @include('trainer.profile.partials.account-panel')
            @include('trainer.profile.partials.certificates-panel')
            @include('trainer.profile.partials.documents-panel')
        </div>
    </main>
</div>
