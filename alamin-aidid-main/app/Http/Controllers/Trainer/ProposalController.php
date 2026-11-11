<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\Proposal\TrainerProposalReportService;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProposalController extends Controller
{
    public function proposals(Request $request, TrainerProposalReportService $proposalReport)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $report = $proposalReport->report(
            $trainerId,
            $proposalReport->normalizeFilters($request)
        );

        if ($request->ajax()) {
            return view('trainer.training.proposal.partials.results', $report);
        }

        return view('trainer.training.proposal', $report);
    }

    public function storeProposal(Request $request)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! Schema::hasTable('course_proposal')) {
            return redirect()
                ->route('trainer.proposals')
                ->with('error', 'The course_proposal table does not exist yet. Please create the table first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();

        $validated = $request->validate([
            'proposed_title' => ['required', 'string', 'max:255'],
            'training_mode' => ['required', 'in:LMS,Simple'],
            'delivery_mode' => ['required', 'in:Online,Physical,Hybrid'],
            'target_audience' => ['required', 'array', 'min:1'],
            'target_audience.*' => ['in:Teacher,New Teacher,Public'],
            'estimated_capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'proposed_start_date' => ['nullable', 'date'],
            'proposed_end_date' => ['nullable', 'date', 'after_or_equal:proposed_start_date'],
            'objective' => ['required', 'string', 'max:3000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'proposal_document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ], [
            'target_audience.required' => 'Please select at least one target audience.',
            'proposed_end_date.after_or_equal' => 'The end date must be the same as or after the start date.',
        ]);

        $documentPath = null;
        $documentName = null;

        if ($request->hasFile('proposal_document')) {
            $file = $request->file('proposal_document');
            $documentName = $file->getClientOriginalName();
            $documentPath = $file->store('proposal-documents/'.$trainerId, 'public');
        }

        $proposalId = DB::table('course_proposal')->insertGetId([
            'trainer_id' => $trainerId,
            'proposed_title' => $validated['proposed_title'],
            'target_audience' => json_encode(array_values($validated['target_audience'])),
            'objective' => $validated['objective'],
            'description' => $validated['description'] ?? null,
            'training_mode' => $validated['training_mode'],
            'delivery_mode' => $validated['delivery_mode'],
            'estimated_capacity' => $validated['estimated_capacity'] ?? null,
            'proposed_start_date' => $validated['proposed_start_date'] ?? null,
            'proposed_end_date' => $validated['proposed_end_date'] ?? null,
            'document_path' => $documentPath,
            'document_name' => $documentName,
            'status' => 'Pending',
            'admin_comment' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Activity log: trainer submitted a new training proposal
        TrainerActivityLogger::log(
            $trainerId,
            'proposal.submit',
            $validated['proposed_title'],
            null,
            'proposal',
            $proposalId
        );

        return redirect()
            ->route('trainer.proposals')
            ->with('success', 'Your training proposal has been submitted and is now pending admin review.');
    }

    public function deleteProposal(int $proposalId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! Schema::hasTable('course_proposal')) {
            return redirect()->route('trainer.proposals')
                ->with('error', 'The course_proposal table does not exist yet.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();

        $proposal = DB::table('course_proposal')
            ->where('proposal_id', $proposalId)
            ->where('trainer_id', $trainerId)
            ->first();

        if (! $proposal) {
            return redirect()->route('trainer.proposals')->with('error', 'Proposal not found.');
        }

        if (strtolower((string) $proposal->status) !== 'pending') {
            return redirect()->route('trainer.proposals')
                ->with('error', 'Only pending proposals can be withdrawn. This one has already been reviewed.');
        }

        if ($proposal->document_path && Storage::disk('public')->exists($proposal->document_path)) {
            Storage::disk('public')->delete($proposal->document_path);
        }

        DB::table('course_proposal')
            ->where('proposal_id', $proposalId)
            ->where('trainer_id', $trainerId)
            ->delete();

        // Activity log: trainer withdrew a pending proposal
        TrainerActivityLogger::log(
            $trainerId,
            'proposal.delete',
            $proposal->proposed_title,
            null,
            'proposal',
            $proposalId
        );

        return redirect()->route('trainer.proposals')
            ->with('success', 'Your pending proposal has been withdrawn.');
    }
}
