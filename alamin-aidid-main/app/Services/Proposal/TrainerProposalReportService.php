<?php

namespace App\Services\Proposal;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TrainerProposalReportService
{
    private const ROW_OPTIONS = [10, 25, 50];

    private const STATUS_OPTIONS = ['all', 'pending', 'approved', 'rejected'];

    private const PERIOD_OPTIONS = ['all', '30', '90', '365', 'custom'];

    public function normalizeFilters(Request $request): array
    {
        $status = strtolower(trim($request->string('status')->toString()));
        $period = strtolower(trim($request->string('submitted_period')->toString()));
        $dateFrom = $this->validDate($request->string('submitted_from')->toString());
        $dateTo = $this->validDate($request->string('submitted_to')->toString());
        $customDateValid = $period === 'custom'
            && $dateFrom !== ''
            && $dateTo !== ''
            && $dateFrom <= $dateTo;

        if ($period !== 'custom' || ! $customDateValid) {
            $dateFrom = '';
            $dateTo = '';
        }
        if ($period === 'custom' && ! $customDateValid) {
            $period = 'all';
        }

        $rows = $request->integer('rows', 10);

        return [
            'status' => in_array($status, self::STATUS_OPTIONS, true) ? $status : 'all',
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 255),
            'submitted_period' => in_array($period, self::PERIOD_OPTIONS, true) ? $period : 'all',
            'submitted_from' => $dateFrom,
            'submitted_to' => $dateTo,
            'custom_date_valid' => $customDateValid,
            'rows' => in_array($rows, self::ROW_OPTIONS, true) ? $rows : 10,
            'page' => max(1, $request->integer('page', 1)),
            'proposal_id' => max(0, $request->integer('proposal_id')),
        ];
    }

    public function report(string $trainerId, array $filters): array
    {
        if (trim($trainerId) === '' || ! Schema::hasTable('course_proposal')) {
            return $this->emptyReport($filters);
        }

        $stats = $this->stats($trainerId);
        $query = $this->applyFilters($this->ownedQuery($trainerId), $filters);
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $filters['rows']));
        $filters['page'] = min($filters['page'], $lastPage);

        $summaries = $query
            ->select([
                'proposal_id',
                'proposed_title',
                'training_mode',
                'delivery_mode',
                'proposed_start_date',
                'proposed_end_date',
                'status',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('proposal_id')
            ->forPage($filters['page'], $filters['rows'])
            ->get()
            ->map(fn (object $proposal) => $this->decorateSummary($proposal));

        $paginator = new LengthAwarePaginator(
            $summaries,
            $total,
            $filters['rows'],
            $filters['page'],
            ['path' => route('trainer.proposals'), 'pageName' => 'page']
        );

        $selectedProposal = null;
        if ($filters['proposal_id'] > 0 && $summaries->contains('proposal_id', $filters['proposal_id'])) {
            $selectedProposal = $this->selectedDetail($trainerId, $filters['proposal_id']);
        }
        if (! $selectedProposal) {
            $filters['proposal_id'] = 0;
        }

        return [
            'stats' => $stats,
            'proposalPaginator' => $paginator,
            'selectedProposal' => $selectedProposal,
            'proposalFilters' => $filters,
            'proposalTableExists' => true,
            'nonSearchFiltersActive' => $this->nonSearchFiltersActive($filters),
        ];
    }

    private function ownedQuery(string $trainerId): Builder
    {
        return DB::table('course_proposal')->where('trainer_id', $trainerId);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if ($filters['status'] === 'approved' || $filters['status'] === 'rejected') {
            $query->whereRaw('LOWER(status) = ?', [$filters['status']]);
        } elseif ($filters['status'] === 'pending') {
            $query->where(function (Builder $statusQuery): void {
                $statusQuery
                    ->whereNull('status')
                    ->orWhereNotIn(DB::raw('LOWER(status)'), ['approved', 'rejected']);
            });
        }

        if ($filters['search'] !== '') {
            $query->whereRaw('LOWER(proposed_title) LIKE ?', ['%'.mb_strtolower($filters['search']).'%']);
        }

        if ($filters['submitted_period'] === 'custom' && $filters['custom_date_valid']) {
            $query
                ->whereDate('created_at', '>=', $filters['submitted_from'])
                ->whereDate('created_at', '<=', $filters['submitted_to']);
        } elseif ($filters['submitted_period'] !== 'all') {
            $boundary = match ($filters['submitted_period']) {
                '30' => Carbon::today()->subDays(29)->startOfDay(),
                '90' => Carbon::today()->subMonths(3)->startOfDay(),
                '365' => Carbon::today()->subYear()->startOfDay(),
            };
            $query->where('created_at', '>=', $boundary);
        }

        return $query;
    }

    private function stats(string $trainerId): array
    {
        $row = $this->ownedQuery($trainerId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        $total = (int) ($row->total ?? 0);
        $approved = (int) ($row->approved ?? 0);
        $rejected = (int) ($row->rejected ?? 0);

        return [
            'total' => $total,
            'pending' => max(0, $total - $approved - $rejected),
            'approved' => $approved,
            'rejected' => $rejected,
        ];
    }

    private function decorateSummary(object $proposal): object
    {
        $proposal->proposal_id = (int) $proposal->proposal_id;
        $proposal->status_key = $this->statusKey($proposal->status ?? null);

        return $proposal;
    }

    private function selectedDetail(string $trainerId, int $proposalId): ?object
    {
        $proposal = $this->ownedQuery($trainerId)
            ->where('proposal_id', $proposalId)
            ->select([
                'proposal_id',
                'proposed_title',
                'target_audience',
                'objective',
                'description',
                'training_mode',
                'delivery_mode',
                'estimated_capacity',
                'proposed_start_date',
                'proposed_end_date',
                'document_path',
                'document_name',
                'status',
                'admin_comment',
                'reviewed_by',
                'reviewed_at',
                'created_at',
            ])
            ->first();

        if (! $proposal) {
            return null;
        }

        $audience = json_decode($proposal->target_audience ?? '[]', true);
        $proposal->proposal_id = (int) $proposal->proposal_id;
        $proposal->target_audience_list = is_array($audience) ? array_values($audience) : [];
        $proposal->document_url = ! empty($proposal->document_path)
            ? asset('storage/'.ltrim(str_replace('\\', '/', $proposal->document_path), '/'))
            : null;
        $proposal->status_key = $this->statusKey($proposal->status ?? null);

        return $proposal;
    }

    private function statusKey(mixed $status): string
    {
        $normalized = strtolower((string) $status);

        return in_array($normalized, ['approved', 'rejected'], true) ? $normalized : 'pending';
    }

    private function nonSearchFiltersActive(array $filters): bool
    {
        return $filters['status'] !== 'all'
            || $filters['submitted_period'] !== 'all'
            || $filters['submitted_from'] !== ''
            || $filters['submitted_to'] !== '';
    }

    private function validDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function emptyReport(array $filters): array
    {
        $filters['page'] = 1;
        $filters['proposal_id'] = 0;

        return [
            'stats' => ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0],
            'proposalPaginator' => new LengthAwarePaginator(
                [],
                0,
                $filters['rows'],
                1,
                ['path' => route('trainer.proposals'), 'pageName' => 'page']
            ),
            'selectedProposal' => null,
            'proposalFilters' => $filters,
            'proposalTableExists' => false,
            'nonSearchFiltersActive' => false,
        ];
    }
}
