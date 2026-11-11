@php
    $audienceDisplay = fn ($value) => \App\Support\ParticipantTypeLabel::display($value);
    $detailStart = $formatProposalDate($proposal->proposed_start_date ?? null);
    $detailEnd = $formatProposalDate($proposal->proposed_end_date ?? null);
    $detailDates = $detailStart && $detailEnd && $detailStart !== $detailEnd
        ? $detailStart.' – '.$detailEnd
        : ($detailStart ?: ($detailEnd ?: 'Not specified'));
@endphp

<div class="pp-proposal-detail" id="proposal-detail-{{ $proposal->proposal_id }}" data-expanded-proposal="{{ $proposal->proposal_id }}">
    <div class="pp-detail-grid">
        <section class="pp-detail-section" aria-labelledby="proposal-training-{{ $proposal->proposal_id }}">
            <h4 id="proposal-training-{{ $proposal->proposal_id }}">Proposed Training</h4>
            <dl class="pp-detail-facts">
                <div><dt>Training Format</dt><dd>{{ $proposal->training_mode ?: 'Not specified' }}</dd></div>
                <div><dt>Delivery Mode</dt><dd>{{ $proposal->delivery_mode ?: 'Not specified' }}</dd></div>
                <div><dt>Estimated Capacity</dt><dd>{{ $proposal->estimated_capacity ? $proposal->estimated_capacity.' participants' : 'Not specified' }}</dd></div>
                <div class="span-2"><dt>Proposed Dates</dt><dd>{{ $detailDates }}</dd></div>
            </dl>
        </section>

        @if (! empty($proposal->target_audience_list))
            <section class="pp-detail-section" aria-labelledby="proposal-audience-{{ $proposal->proposal_id }}">
                <h4 id="proposal-audience-{{ $proposal->proposal_id }}">Target Audience</h4>
                <div class="pp-detail-pills">
                    @foreach ($proposal->target_audience_list as $audience)
                        <span><i class="bi bi-people" aria-hidden="true"></i>{{ $audienceDisplay($audience) }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($proposal->objective))
            <section class="pp-detail-section span-2" aria-labelledby="proposal-objective-{{ $proposal->proposal_id }}">
                <h4 id="proposal-objective-{{ $proposal->proposal_id }}">Training Objective</h4>
                <p>{{ $proposal->objective }}</p>
            </section>
        @endif

        @if (! empty($proposal->description))
            <section class="pp-detail-section span-2" aria-labelledby="proposal-description-{{ $proposal->proposal_id }}">
                <h4 id="proposal-description-{{ $proposal->proposal_id }}">Description / Justification</h4>
                <p>{{ $proposal->description }}</p>
            </section>
        @endif

        @if (! empty($proposal->document_url))
            <section class="pp-detail-section" aria-labelledby="proposal-attachment-{{ $proposal->proposal_id }}">
                <h4 id="proposal-attachment-{{ $proposal->proposal_id }}">Attachment</h4>
                <a href="{{ $proposal->document_url }}" target="_blank" rel="noopener" class="proposal-doc-link">
                    <i class="bi bi-paperclip" aria-hidden="true"></i>{{ $proposal->document_name ?: 'View attached document' }}
                </a>
            </section>
        @endif

        <section class="pp-detail-section" aria-labelledby="proposal-submission-{{ $proposal->proposal_id }}">
            <h4 id="proposal-submission-{{ $proposal->proposal_id }}">Submission</h4>
            <p>{{ $formatProposalDate($proposal->created_at ?? null, true) ?? 'Date unavailable' }}</p>
        </section>

        <section class="pp-detail-section span-2 pp-review-section {{ $proposal->status_key }}" aria-labelledby="proposal-review-{{ $proposal->proposal_id }}">
            <h4 id="proposal-review-{{ $proposal->proposal_id }}">Admin Review</h4>
            <dl class="pp-detail-facts">
                <div><dt>Status</dt><dd>{{ $proposal->status_key === 'pending' ? 'Pending Review' : ucfirst($proposal->status_key) }}</dd></div>
                @if (! empty($proposal->reviewed_at))
                    <div><dt>Reviewed</dt><dd>{{ $formatProposalDate($proposal->reviewed_at, true) }}</dd></div>
                @endif
            </dl>
            @if (! empty($proposal->admin_comment))
                <div class="pp-admin-comment"><strong>Admin remarks</strong><p>{{ $proposal->admin_comment }}</p></div>
            @endif
        </section>
    </div>

    @if ($proposal->status_key === 'pending')
        <div class="pp-detail-footer">
            <form method="POST"
                  action="{{ route('trainer.proposals.delete', $proposal->proposal_id) }}"
                  onsubmit="return confirm('Withdraw this pending proposal? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="pp-btn-ghost"><i class="bi bi-trash3" aria-hidden="true"></i> Withdraw</button>
            </form>
        </div>
    @endif
</div>
