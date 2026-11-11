<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SharedTrainingSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProposalController extends Controller
{
    public function proposals(Request $request)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $proposals = collect();
        $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

        if (Schema::hasTable('course_proposal')) {
            $rows = DB::table('course_proposal')
                ->leftJoin('trainer', 'course_proposal.trainer_id', '=', 'trainer.'.SharedTrainingSchema::trainerInternalColumn())
                ->select('course_proposal.*')
                ->selectRaw(SharedTrainingSchema::trainerNameExpression().' AS trainer_name')
                ->orderByDesc('course_proposal.created_at')
                ->get();

            $proposals = $rows->map(function ($proposal) {
                $statusRaw = strtolower(trim((string) ($proposal->status ?? '')));
                $proposal->status_key = str_contains($statusRaw, 'approve')
                    ? 'approved'
                    : (str_contains($statusRaw, 'reject') ? 'rejected' : 'pending');

                $audience = [];
                if (! empty($proposal->target_audience)) {
                    $decoded = json_decode($proposal->target_audience, true);
                    if (is_array($decoded)) {
                        $audience = $decoded;
                    }
                }
                $proposal->target_audience_list = $audience;

                $proposal->document_url = ! empty($proposal->document_path)
                    ? asset('storage/'.$proposal->document_path)
                    : null;

                return $proposal;
            });

            $stats = [
                'total' => $proposals->count(),
                'pending' => $proposals->where('status_key', 'pending')->count(),
                'approved' => $proposals->where('status_key', 'approved')->count(),
                'rejected' => $proposals->where('status_key', 'rejected')->count(),
            ];
        }

        return view('admin.proposals', compact('proposals', 'stats'));
    }

    public function approveProposal(Request $request, int $proposalId)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $proposal = $this->reviewProposal(
            $proposalId,
            'Approved',
            $request->input('admin_comment') ?: null
        );

        if (! $proposal) {
            return redirect()->route('admin.proposals')->with('error', 'Proposal not found.');
        }

        return redirect()->route('admin.proposals')
            ->with('success', 'Proposal approved: '.$proposal->proposed_title);
    }

    public function rejectProposal(Request $request, int $proposalId)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $validated = $request->validate([
            'admin_comment' => ['required', 'string', 'max:2000'],
        ], [
            'admin_comment.required' => 'Please give a reason when rejecting a proposal.',
        ]);

        $proposal = $this->reviewProposal(
            $proposalId,
            'Rejected',
            $validated['admin_comment']
        );

        if (! $proposal) {
            return redirect()->route('admin.proposals')->with('error', 'Proposal not found.');
        }

        return redirect()->route('admin.proposals')
            ->with('success', 'Proposal rejected: '.$proposal->proposed_title);
    }

    private function reviewProposal(int $proposalId, string $decision, ?string $comment): ?object
    {
        return DB::transaction(function () use ($proposalId, $decision, $comment): ?object {
            $proposal = DB::table('course_proposal')
                ->where('proposal_id', $proposalId)
                ->lockForUpdate()
                ->first();

            if (! $proposal) {
                return null;
            }

            DB::table('course_proposal')
                ->where('proposal_id', $proposalId)
                ->update([
                    'status' => $decision,
                    'admin_comment' => $comment,
                    'reviewed_by' => (string) session('user_id'),
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            return $proposal;
        });
    }
}
