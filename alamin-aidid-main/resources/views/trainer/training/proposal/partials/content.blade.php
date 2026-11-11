<div class="proposal-shell">

    <main class="proposal-page app-page-shell">

        <div class="proposal-head">
            <h1 class="proposal-title">Training Proposal</h1>
            <p class="proposal-subtitle">
                Propose a new training for Al Amin Edu Oasis. Each proposal is reviewed by the admin,
                who will approve or reject it with feedback. Track all your proposals below.
            </p>
        </div>

        @if (! $proposalTableExists)

            <div class="pp-card">
                <div class="pp-card-body">
                    <div class="setup-notice">
                        <i class="bi bi-database-exclamation"></i>
                        <div class="empty-title">Proposal table not set up yet</div>
                        <div class="empty-sub">
                            The <strong>course_proposal</strong> table does not exist in the database.
                            Please run the provided SQL in phpMyAdmin, then reload this page.
                        </div>
                    </div>
                </div>
            </div>

        @else

            @include('trainer.training.proposal.partials.stats')
            @include('trainer.training.proposal.partials.form')
            @include('trainer.training.proposal.partials.list')

        @endif

    </main>
</div>
