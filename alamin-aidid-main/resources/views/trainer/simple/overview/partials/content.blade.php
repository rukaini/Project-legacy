<div class="sw-shell">

    @include('trainer.simple.overview.partials.header')

    <main class="sw-page app-workspace-shell">

        {{-- Single row, two continuous columns:
             LEFT  = Session Spotlight + Participants
             RIGHT = Readiness + About + Materials --}}
        <div class="row g-4">

            {{-- ============ LEFT COLUMN ============ --}}
            <div class="col-xl-8">

                @include('trainer.simple.overview.partials.session-spotlight')

                @include('trainer.simple.overview.partials.participants-panel')

            </div>

            {{-- ============ RIGHT COLUMN ============ --}}
            <div class="col-xl-4">

                @include('trainer.simple.overview.partials.sidebar')

            </div>

        </div>

    </main>
</div>
