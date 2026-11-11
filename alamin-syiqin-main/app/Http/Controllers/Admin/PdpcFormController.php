<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PdpcFormController extends Controller
{
    // Version list
    public function index(): View
    {
        $forms = DB::table('pdpc_form')
            ->orderByDesc('version_no')
            ->get();

        foreach ($forms as $form) {

            $form = $this->attachHierarchy($form);

            $form->is_used = $this->isFormUsed($form->formID);
            $form->aspect_count = $form->aspects->count();
            $form->tums_count = $form->aspects->sum(fn($aspect) => $aspect->tums->count());

            $form->point_count = $form->aspects->sum(function ($aspect) {
                return $aspect->tums->sum(function ($tums) {
                    return $tums->tt->sum(fn($tt) => $tt->points->count());
                });
            });
        }

        return view('admin.pdpc-form', compact('forms'));
    }


    // Create first form
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'form_name' => ['required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string', 'max:2000'],
        ]);

        if (DB::table('pdpc_form')->exists()) {

            return redirect()
                ->route('admin.pdpc.form')
                ->with('error', 'PDPC form already exists. Create a new version instead.');
        }

        $formID = DB::table('pdpc_form')->insertGetId([
            'form_name' => $request->form_name,
            'instruction' => $request->instruction,
            'version_no' => 1,
            'status' => 'Active',
            'staffID' => Auth::guard('admin')->id(),
        ], 'formID');

        return redirect()
            ->route('admin.pdpc.form.edit', $formID)
            ->with('success', 'PDPC form created');
    }


    // Preview selected version
    public function preview($pdpcForm): View
    {
        $form = $this->getFormWithHierarchy($pdpcForm);

        return view('admin.pdpc-form-preview', [
            'form' => $form,
        ]);
    }


    // Edit selected version
    public function edit($pdpcForm): View
    {
        $form = $this->getFormWithHierarchy($pdpcForm);

        $formUsed = $this->isFormUsed($form->formID);

        $initialAspects = old(
            'aspects',
            $this->formAspects($form)
        );

        return view('admin.pdpc-form-edit', [
            'form' => $form,
            'formUsed' => $formUsed,
            'initialAspects' => $initialAspects,
        ]);
    }


    // Create new version
    public function createNewVersion($pdpcForm): RedirectResponse
    {
        $form = $this->getFormWithHierarchy($pdpcForm);

        $newFormID = null;

        DB::transaction(function () use ($form, &$newFormID) {

            DB::table('pdpc_form')
                ->where('status', 'Active')
                ->update([
                    'status' => 'Inactive',
                ]);

            $nextVersion = (DB::table('pdpc_form')->max('version_no') ?? 0) + 1;

            $newFormID = DB::table('pdpc_form')->insertGetId([
                'form_name' => $form->form_name,
                'instruction' => $form->instruction,
                'version_no' => $nextVersion,
                'status' => 'Active',
                'staffID' => Auth::guard('admin')->id(),
            ], 'formID');

            foreach ($form->aspects as $oldAspect) {

                $newAspectID = DB::table('pdpc_aspect')->insertGetId([
                    'formID' => $newFormID,
                    'aspect_code' => $oldAspect->aspect_code,
                    'aspect_name' => $oldAspect->aspect_name,
                    'display_order' => $oldAspect->display_order,
                ], 'aspectID');

                foreach ($oldAspect->tums as $oldTums) {

                    $newTumsID = DB::table('pdpc_tums')->insertGetId([
                        'aspectID' => $newAspectID,
                        'tums_code' => $oldTums->tums_code,
                        'tums_name' => $oldTums->tums_name,
                        'wajaran' => $oldTums->wajaran,
                        'display_order' => $oldTums->display_order,
                    ], 'tumsID');

                    foreach ($oldTums->tt as $oldTt) {

                        $newTtID = DB::table('pdpc_tt')->insertGetId([
                            'tumsID' => $newTumsID,
                            'display_order' => $oldTt->display_order,
                        ], 'ttID');

                        foreach ($oldTt->points as $oldPoint) {

                            DB::table('pdpc_tt_point')->insert([
                                'ttID' => $newTtID,
                                'point_text' => $oldPoint->point_text,
                                'display_order' => $oldPoint->display_order,
                            ]);
                        }
                    }

                    foreach ($oldTums->rubrics as $oldRubric) {

                        DB::table('pdpc_rubric')->insert([
                            'tumsID' => $newTumsID,
                            'score' => $oldRubric->score,
                            'description' => $oldRubric->description,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.pdpc.form.edit', $newFormID)
            ->with('success', 'New form version created successfully');
    }


    // Update selected version
    public function update(
        Request $request,
        $pdpcForm
    ): RedirectResponse {

        $form = $this->findFormOrFail($pdpcForm);

        $formUsed = $this->isFormUsed($form->formID);

        // Used form: wording only
        if ($formUsed) {

            $validated = $request->validate([
                'form_name' => ['required', 'string', 'max:255'],
                'instruction' => ['nullable', 'string', 'max:2000'],

                'aspects' => ['required', 'array'],
                'aspects.*.aspectID' => ['required', 'integer'],
                'aspects.*.aspect_name' => ['required', 'string', 'max:255'],

                'aspects.*.tums' => ['required', 'array'],
                'aspects.*.tums.*.tumsID' => ['required', 'integer'],
                'aspects.*.tums.*.tums_name' => ['required', 'string', 'max:500'],

                'aspects.*.tums.*.tt' => ['required', 'array'],
                'aspects.*.tums.*.tt.*.ttID' => ['required', 'integer'],
                'aspects.*.tums.*.tt.*.points' => ['required', 'array'],
                'aspects.*.tums.*.tt.*.points.*.pointID' => ['required', 'integer'],
                'aspects.*.tums.*.tt.*.points.*.point_text' => ['required', 'string', 'max:2000'],

                'aspects.*.tums.*.rubrics' => ['required', 'array:0,1,2,3,4'],
                'aspects.*.tums.*.rubrics.*' => ['required', 'string', 'max:2000'],
            ]);

            DB::transaction(function () use ($form, $validated) {

                // Update form wording
                DB::table('pdpc_form')
                    ->where('formID', $form->formID)
                    ->update([
                        'form_name' => $validated['form_name'],
                        'instruction' => $validated['instruction'] ?? null,
                        'staffID' => Auth::guard('admin')->id(),
                    ]);

                foreach ($validated['aspects'] as $aspectData) {

                    // Ensure aspect belongs to form
                    $aspect = DB::table('pdpc_aspect')
                        ->where('aspectID', $aspectData['aspectID'])
                        ->where('formID', $form->formID)
                        ->first();

                    abort_if(!$aspect, 404);

                    DB::table('pdpc_aspect')
                        ->where('aspectID', $aspect->aspectID)
                        ->update([
                            'aspect_name' => $aspectData['aspect_name'],
                        ]);

                    foreach ($aspectData['tums'] as $tumsData) {

                        // Ensure TUMS belongs to aspect
                        $tums = DB::table('pdpc_tums')
                            ->where('tumsID', $tumsData['tumsID'])
                            ->where('aspectID', $aspect->aspectID)
                            ->first();

                        abort_if(!$tums, 404);

                        DB::table('pdpc_tums')
                            ->where('tumsID', $tums->tumsID)
                            ->update([
                                'tums_name' => $tumsData['tums_name'],
                            ]);

                        foreach ($tumsData['tt'] as $ttData) {

                            // Ensure TT belongs to TUMS
                            $tt = DB::table('pdpc_tt')
                                ->where('ttID', $ttData['ttID'])
                                ->where('tumsID', $tums->tumsID)
                                ->first();

                            abort_if(!$tt, 404);

                            foreach ($ttData['points'] as $pointData) {

                                // Update TT point wording only
                                DB::table('pdpc_tt_point')
                                    ->where('pointID', $pointData['pointID'])
                                    ->where('ttID', $tt->ttID)
                                    ->update([
                                        'point_text' => $pointData['point_text'],
                                    ]);
                            }
                        }

                        // Update RTK wording only
                        foreach ($tumsData['rubrics'] as $score => $description) {

                            DB::table('pdpc_rubric')
                                ->where('tumsID', $tums->tumsID)
                                ->where('score', (int) $score)
                                ->update([
                                    'description' => trim($description),
                                ]);
                        }
                    }
                }
            });

            return redirect()
                ->route('admin.pdpc.form.edit', $form->formID)
                ->with('success', 'Form update successfully');
        }


        // Unused form: everything can still be changed
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($form, $validated) {

            DB::table('pdpc_form')
                ->where('formID', $form->formID)
                ->update([
                    'form_name' => $validated['form_name'],
                    'instruction' => $validated['instruction'] ?? null,
                    'staffID' => Auth::guard('admin')->id(),
                ]);

            $this->deleteHierarchy($form->formID);

            $this->saveHierarchy(
                $form->formID,
                $validated['aspects']
            );
        });

        return redirect()
            ->route('admin.pdpc.form.edit', $form->formID)
            ->with('success', 'Form updated successfully');
    }


    // Delete unused version
    public function destroy($pdpcForm): RedirectResponse
    {
        $form = $this->findFormOrFail($pdpcForm);

        if ($this->isFormUsed($form->formID)) {

            return redirect()
                ->route('admin.pdpc.form')
                ->with('error', 'This version has data already and cannot be deleted');
        }

        $wasActive = $form->status === 'Active';

        DB::transaction(function () use ($form) {

            $this->deleteHierarchy($form->formID);

            DB::table('pdpc_form')
                ->where('formID', $form->formID)
                ->delete();
        });

        if ($wasActive) {

            DB::table('pdpc_form')
                ->where('status', 'Active')
                ->update([
                    'status' => 'Inactive',
                ]);

            $previousForm = DB::table('pdpc_form')
                ->orderByDesc('version_no')
                ->first();

            if ($previousForm) {

                DB::table('pdpc_form')
                    ->where('formID', $previousForm->formID)
                    ->update([
                        'status' => 'Active',
                    ]);
            }
        }

        return redirect()
            ->route('admin.pdpc.form')
            ->with('success', 'Form deleted successfully');
    }


    // Save hierarchy
    private function saveHierarchy(
        $formID,
        array $aspects
    ): void {

        foreach (array_values($aspects) as $aspectIndex => $aspectData) {

            $aspectID = DB::table('pdpc_aspect')->insertGetId([
                'formID' => $formID,
                'aspect_code' => $aspectData['aspect_code'] ?? null,
                'aspect_name' => $aspectData['aspect_name'],
                'display_order' => $aspectIndex + 1,
            ], 'aspectID');

            foreach (array_values($aspectData['tums']) as $tumsIndex => $tumsData) {

                $tumsID = DB::table('pdpc_tums')->insertGetId([
                    'aspectID' => $aspectID,
                    'tums_code' => $tumsData['tums_code'] ?? null,
                    'tums_name' => $tumsData['tums_name'],
                    'wajaran' => $tumsData['wajaran'],
                    'display_order' => $tumsIndex + 1,
                ], 'tumsID');

                foreach (array_values($tumsData['tt']) as $ttIndex => $ttData) {

                    $ttID = DB::table('pdpc_tt')->insertGetId([
                        'tumsID' => $tumsID,
                        'display_order' => $ttIndex + 1,
                    ], 'ttID');

                    foreach (array_values($ttData['points']) as $pointIndex => $pointData) {

                        DB::table('pdpc_tt_point')->insert([
                            'ttID' => $ttID,
                            'point_text' => $pointData['point_text'],
                            'display_order' => $pointIndex + 1,
                        ]);
                    }
                }

                foreach ($tumsData['rubrics'] ?? [] as $score => $description) {

                    if ($description === null || trim($description) === '') {
                        continue;
                    }

                    DB::table('pdpc_rubric')->insert([
                        'tumsID' => $tumsID,
                        'score' => (int) $score,
                        'description' => trim($description),
                    ]);
                }
            }
        }
    }


    // Delete hierarchy safely
    private function deleteHierarchy($formID): void
    {
        $aspectIDs = DB::table('pdpc_aspect')
            ->where('formID', $formID)
            ->pluck('aspectID');

        if ($aspectIDs->isEmpty()) {
            return;
        }

        $tumsIDs = DB::table('pdpc_tums')
            ->whereIn('aspectID', $aspectIDs)
            ->pluck('tumsID');

        if ($tumsIDs->isNotEmpty()) {

            $ttIDs = DB::table('pdpc_tt')
                ->whereIn('tumsID', $tumsIDs)
                ->pluck('ttID');

            if ($ttIDs->isNotEmpty()) {

                $pointIDs = DB::table('pdpc_tt_point')
                    ->whereIn('ttID', $ttIDs)
                    ->pluck('pointID');

                if ($pointIDs->isNotEmpty()) {

                    DB::table('pdpc_score')
                        ->whereIn('pointID', $pointIDs)
                        ->delete();

                    DB::table('pdpc_tt_point')
                        ->whereIn('pointID', $pointIDs)
                        ->delete();
                }

                DB::table('pdpc_tt')
                    ->whereIn('ttID', $ttIDs)
                    ->delete();
            }

            DB::table('pdpc_rubric')
                ->whereIn('tumsID', $tumsIDs)
                ->delete();

            DB::table('pdpc_tums')
                ->whereIn('tumsID', $tumsIDs)
                ->delete();
        }

        DB::table('pdpc_aspect')
            ->whereIn('aspectID', $aspectIDs)
            ->delete();
    }


    // Check version usage
    private function isFormUsed($formID): bool
    {
        return DB::table('pdpc_response')
            ->where('formID', $formID)
            ->exists();
    }


    // Create form with hierarchy
    private function createFormWithHierarchy(
        int $version,
        string $formName,
        ?string $instruction,
        array $aspects
    ) {
        DB::table('pdpc_form')
            ->where('status', 'Active')
            ->update([
                'status' => 'Inactive',
            ]);

        $formID = DB::table('pdpc_form')->insertGetId([
            'form_name' => $formName,
            'instruction' => $instruction,
            'version_no' => $version,
            'status' => 'Active',
            'staffID' => Auth::guard('admin')->id(),
        ], 'formID');

        $this->saveHierarchy(
            $formID,
            $aspects
        );

        return $this->findFormOrFail($formID);
    }


    // Validation
    private function rules(): array
    {
        return [
            'form_name' => ['required', 'string', 'max:255'],
            'instruction' => ['nullable', 'string', 'max:2000'],

            'aspects' => ['required', 'array', 'min:1'],
            'aspects.*.aspect_code' => ['nullable', 'string', 'max:30'],
            'aspects.*.aspect_name' => ['required', 'string', 'max:255'],

            'aspects.*.tums' => ['required', 'array', 'min:1'],
            'aspects.*.tums.*.tums_code' => ['nullable', 'string', 'max:30'],
            'aspects.*.tums.*.tums_name' => ['required', 'string', 'max:500'],
            'aspects.*.tums.*.wajaran' => ['required', 'numeric', 'min:0', 'max:100'],

            'aspects.*.tums.*.tt' => ['required', 'array', 'min:1'],
            'aspects.*.tums.*.tt.*.points' => ['required', 'array', 'min:1'],
            'aspects.*.tums.*.tt.*.points.*.point_text' => ['required', 'string', 'max:2000'],

            'aspects.*.tums.*.rubrics' => ['required', 'array:0,1,2,3,4'],
            'aspects.*.tums.*.rubrics.0' => ['required', 'string', 'max:2000'],
            'aspects.*.tums.*.rubrics.1' => ['required', 'string', 'max:2000'],
            'aspects.*.tums.*.rubrics.2' => ['required', 'string', 'max:2000'],
            'aspects.*.tums.*.rubrics.3' => ['required', 'string', 'max:2000'],
            'aspects.*.tums.*.rubrics.4' => ['required', 'string', 'max:2000'],
        ];
    }


    // Convert DB hierarchy for edit
    private function formAspects($form): array
    {
        return $form->aspects->map(fn($aspect) => [

            'aspectID' => $aspect->aspectID,
            'aspect_code' => $aspect->aspect_code,
            'aspect_name' => $aspect->aspect_name,

            'tums' => $aspect->tums->map(fn($tums) => [

                'tumsID' => $tums->tumsID,
                'tums_code' => $tums->tums_code,
                'tums_name' => $tums->tums_name,
                'wajaran' => $tums->wajaran,

                'tt' => $tums->tt->map(fn($tt) => [

                    'ttID' => $tt->ttID,

                    'points' => $tt->points->map(fn($point) => [
                        'pointID' => $point->pointID,
                        'point_text' => $point->point_text,
                    ])->values()->all(),

                ])->values()->all(),

                'rubrics' => $tums->rubrics
                    ->pluck('description', 'score')
                    ->all(),

            ])->values()->all(),

        ])->values()->all();
    }


    // Get form with complete hierarchy
    private function getFormWithHierarchy($formID)
    {
        $form = $this->findFormOrFail($formID);

        return $this->attachHierarchy($form);
    }


    // Attach aspects, TUMS, TT, TT points and rubrics
    private function attachHierarchy($form)
    {
        $form->aspects = DB::table('pdpc_aspect')
            ->where('formID', $form->formID)
            ->orderBy('display_order')
            ->get();

        foreach ($form->aspects as $aspect) {

            $aspect->tums = DB::table('pdpc_tums')
                ->where('aspectID', $aspect->aspectID)
                ->orderBy('display_order')
                ->get();

            foreach ($aspect->tums as $tums) {

                $tums->tt = DB::table('pdpc_tt')
                    ->where('tumsID', $tums->tumsID)
                    ->orderBy('display_order')
                    ->get();

                foreach ($tums->tt as $tt) {

                    $tt->points = DB::table('pdpc_tt_point')
                        ->where('ttID', $tt->ttID)
                        ->orderBy('display_order')
                        ->get();
                }

                $tums->rubrics = DB::table('pdpc_rubric')
                    ->where('tumsID', $tums->tumsID)
                    ->orderBy('score')
                    ->get();
            }
        }

        return $form;
    }


    // Find form
    private function findFormOrFail($formID)
    {
        $form = DB::table('pdpc_form')
            ->where('formID', $formID)
            ->first();

        abort_if(!$form, 404);

        return $form;
    }
}
