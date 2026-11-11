<div id="trainer-profile-panel"
     class="pf-tab-panel {{ $activeTab === 'profile' ? 'show' : '' }}"
     role="tabpanel"
     aria-labelledby="trainer-profile-tab"
     data-panel="profile"
     @if ($activeTab !== 'profile') hidden @endif>
    <section class="pf-identity" aria-labelledby="trainer-identity-title">
        <h2 class="pf-identity-name" id="trainer-identity-title">{{ $trainerName }}</h2>
        <p class="pf-identity-email">{{ $trainer->trainer_email ?: 'Not available' }}</p>
    </section>

    @include('trainer.profile.partials.summary')

    <section class="pf-profile-section" aria-labelledby="trainer-account-details-title">
        <div class="pf-section-head">
            <div>
                <h2 class="pf-section-title" id="trainer-account-details-title">Account Details</h2>
                <p class="pf-section-subtitle">Your registered trainer information.</p>
            </div>
        </div>

        <dl class="pf-detail-grid">
            <div class="pf-detail-item">
                <dt class="pf-detail-label"><i class="bi bi-person" aria-hidden="true"></i> Full Name</dt>
                <dd class="pf-detail-value">{{ $trainer->trainer_name ?: 'Not available' }}</dd>
            </div>

            <div class="pf-detail-item">
                <dt class="pf-detail-label"><i class="bi bi-envelope" aria-hidden="true"></i> Email</dt>
                <dd class="pf-detail-value">{{ $trainer->trainer_email ?: 'Not available' }}</dd>
            </div>

            <div class="pf-detail-item">
                <dt class="pf-detail-label"><i class="bi bi-telephone" aria-hidden="true"></i> Phone Number</dt>
                <dd class="pf-detail-value {{ empty($trainer->trainer_phoneNo) ? 'muted' : '' }}">
                    {{ $trainer->trainer_phoneNo ?: 'Not set' }}
                </dd>
            </div>

            <div class="pf-detail-item pf-detail-item--tail">
                <dt class="pf-detail-label"><i class="bi bi-card-text" aria-hidden="true"></i> Trainer IC</dt>
                <dd class="pf-detail-value {{ empty($trainer->trainerIC) ? 'muted' : '' }}">
                    {{ $trainer->trainerIC ?: 'Not set' }}
                </dd>
            </div>

            <div class="pf-detail-item pf-detail-item--tail">
                <dt class="pf-detail-label"><i class="bi bi-award" aria-hidden="true"></i> Area of Expertise</dt>
                <dd class="pf-detail-value {{ empty($trainer->expertise) ? 'muted' : '' }}">
                    {{ $trainer->expertise ?: 'Not set' }}
                </dd>
            </div>
        </dl>
    </section>
</div>
