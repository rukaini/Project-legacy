<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreFormController extends Controller
{
    // Version list
    public function index()
    {
        $forms = DB::table('pre_form')
            ->orderByDesc('version')
            ->get();

        foreach ($forms as $form) {

            $form->sections = DB::table('pre_section')
                ->where('formID', $form->formID)
                ->orderBy('display_order')
                ->get();

            foreach ($form->sections as $section) {

                $section->criteria = DB::table('pre_criteria')
                    ->where('sectionID', $section->sectionID)
                    ->orderBy('display_order')
                    ->get();
            }

            $form->is_used = $this->isFormUsed($form->formID);
            $form->section_count = $form->sections->count();
            $form->criteria_count = $form->sections->sum(fn($section) => $section->criteria->count());
            $form->max_mark = $form->criteria_count * $form->max_score;
        }

        return view('admin.pre-form', compact('forms'));
    }


    // Preview version
    public function preview($preForm)
    {
        $form = $this->getFormWithStructure($preForm);

        $criteriaCount = $form->sections->sum(fn($section) => $section->criteria->count());
        $maximumScore = $criteriaCount * $form->max_score;

        return view('admin.pre-form-preview', [
            'form' => $form,
            'maximumScore' => $maximumScore,
        ]);
    }


    // Edit version
    public function edit($preForm)
    {
        $form = $this->getFormWithStructure($preForm);

        $formUsed = $this->isFormUsed($form->formID);
        $sectionCount = $form->sections->count();
        $criteriaCount = $form->sections->sum(fn($section) => $section->criteria->count());
        $maxTotal = $criteriaCount * $form->max_score;

        return view('admin.pre-form-edit', [
            'preForm' => $form,
            'form' => $form,
            'formUsed' => $formUsed,
            'sectionCount' => $sectionCount,
            'criteriaCount' => $criteriaCount,
            'maxTotal' => $maxTotal,
        ]);
    }


    // Create first form
    public function storeForm(Request $request): RedirectResponse
    {
        $request->validate([
            'form_name' => 'required|string|max:255',
            'instruction' => 'nullable|string',
            'min_score' => 'required|integer|min:0',
            'max_score' => 'required|integer|gt:min_score',
        ]);

        if (DB::table('pre_form')->exists()) {

            return redirect()
                ->route('admin.pre.form')
                ->with('error', 'A Pre-Observation form already exists. Create a new version instead');
        }

        $formID = DB::table('pre_form')->insertGetId([
            'form_name' => $request->form_name,
            'version' => 1,
            'instruction' => $request->instruction,
            'min_score' => $request->min_score,
            'max_score' => $request->max_score,
            'status' => 'Active',
            'staffID' => Auth::guard('admin')->id(),
        ], 'formID');

        return redirect()
            ->route('admin.pre.form.edit', $formID)
            ->with('success', 'Form created successfully');
    }


    // Create new version
    public function createNewVersion($preForm): RedirectResponse
    {
        $form = $this->getFormWithStructure($preForm);

        $newFormID = null;

        DB::transaction(function () use ($form, &$newFormID) {

            DB::table('pre_form')
                ->where('status', 'Active')
                ->update([
                    'status' => 'Inactive',
                ]);

            $nextVersion = (DB::table('pre_form')->max('version') ?? 0) + 1;

            $newFormID = DB::table('pre_form')->insertGetId([
                'form_name' => $form->form_name,
                'version' => $nextVersion,
                'instruction' => $form->instruction,
                'min_score' => $form->min_score,
                'max_score' => $form->max_score,
                'status' => 'Active',
                'staffID' => Auth::guard('admin')->id(),
            ], 'formID');

            foreach ($form->sections as $oldSection) {

                $newSectionID = DB::table('pre_section')->insertGetId([
                    'formID' => $newFormID,
                    'section_name' => $oldSection->section_name,
                    'display_order' => $oldSection->display_order,
                ], 'sectionID');

                foreach ($oldSection->criteria as $oldCriteria) {

                    DB::table('pre_criteria')->insert([
                        'sectionID' => $newSectionID,
                        'criteria_label' => $oldCriteria->criteria_label,
                        'display_order' => $oldCriteria->display_order,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.pre.form.edit', $newFormID)
            ->with('success', 'New form version created successfully');
    }


    // Update form 
    public function updateForm(Request $request, $preForm): RedirectResponse
    {
        $form = $this->findFormOrFail($preForm);

        $formUsed = $this->isFormUsed($form->formID);

        if ($formUsed) {

            $request->validate([
                'form_name' => 'required|string|max:255',
                'instruction' => 'nullable|string',
            ]);

            DB::table('pre_form')
                ->where('formID', $form->formID)
                ->update([
                    'form_name' => $request->form_name,
                    'instruction' => $request->instruction,
                    'staffID' => Auth::guard('admin')->id(),
                ]);

            return redirect()
                ->route('admin.pre.form.edit', $form->formID)
                ->with('success', 'Form information updated successfully');
        }

        $request->validate([
            'form_name' => 'required|string|max:255',
            'instruction' => 'nullable|string',
            'min_score' => 'required|integer|min:0',
            'max_score' => 'required|integer|gt:min_score',
        ]);

        DB::table('pre_form')
            ->where('formID', $form->formID)
            ->update([
                'form_name' => $request->form_name,
                'instruction' => $request->instruction,
                'min_score' => $request->min_score,
                'max_score' => $request->max_score,
                'staffID' => Auth::guard('admin')->id(),
            ]);

        return redirect()
            ->route('admin.pre.form.edit', $form->formID)
            ->with('success', 'Form updated successfully');
    }


    // Delete unused version
    public function destroyForm($preForm): RedirectResponse
    {
        $form = $this->findFormOrFail($preForm);

        if ($this->isFormUsed($form->formID)) {

            return redirect()
                ->route('admin.pre.form')
                ->with('error', 'This version has data already and cannot be deleted');
        }

        $wasActive = $form->status === 'Active';

        DB::transaction(function () use ($form) {

            $sectionIDs = DB::table('pre_section')
                ->where('formID', $form->formID)
                ->pluck('sectionID');

            if ($sectionIDs->isNotEmpty()) {

                $criteriaIDs = DB::table('pre_criteria')
                    ->whereIn('sectionID', $sectionIDs)
                    ->pluck('criteriaID');

                if ($criteriaIDs->isNotEmpty()) {

                    DB::table('pre_score')
                        ->whereIn('criteriaID', $criteriaIDs)
                        ->delete();

                    DB::table('pre_criteria')
                        ->whereIn('criteriaID', $criteriaIDs)
                        ->delete();
                }

                DB::table('pre_section_comment')
                    ->whereIn('sectionID', $sectionIDs)
                    ->delete();

                DB::table('pre_section')
                    ->whereIn('sectionID', $sectionIDs)
                    ->delete();
            }

            DB::table('pre_form')
                ->where('formID', $form->formID)
                ->delete();
        });

        if ($wasActive) {

            DB::table('pre_form')
                ->where('status', 'Active')
                ->update([
                    'status' => 'Inactive',
                ]);

            $previousForm = DB::table('pre_form')
                ->orderByDesc('version')
                ->first();

            if ($previousForm) {

                DB::table('pre_form')
                    ->where('formID', $previousForm->formID)
                    ->update([
                        'status' => 'Active',
                    ]);
            }
        }

        return redirect()
            ->route('admin.pre.form')
            ->with('success', 'Form deleted successfully');
    }


    // Add section
    public function storeSection(Request $request): RedirectResponse
    {
        $request->validate([
            'formID' => 'required|exists:pre_form,formID',
            'section_name' => 'required|string|max:255',
        ]);

        $form = $this->findFormOrFail($request->formID);

        $this->ensureStructureEditable($form->formID);

        $lastOrder = DB::table('pre_section')
            ->where('formID', $form->formID)
            ->max('display_order');

        DB::table('pre_section')->insert([
            'formID' => $form->formID,
            'section_name' => $request->section_name,
            'display_order' => ($lastOrder ?? 0) + 1,
        ]);

        return redirect()
            ->route('admin.pre.form.edit', $form->formID)
            ->with('success', 'Section added successfully');
    }


    // Update section
    public function updateSection(Request $request, $sectionID): RedirectResponse
    {
        $section = $this->findSectionOrFail($sectionID);
        $form = $this->findFormOrFail($section->formID);

        $request->validate([
            'section_name' => 'required|string|max:255',
        ]);

        DB::table('pre_section')
            ->where('sectionID', $section->sectionID)
            ->update([
                'section_name' => $request->section_name,
            ]);

        return redirect()
            ->route('admin.pre.form.edit', $form->formID)
            ->with('success', 'Section updated successfully');
    }


    // Delete section
    public function destroySection($sectionID): RedirectResponse
    {
        $section = $this->findSectionOrFail($sectionID);
        $form = $this->findFormOrFail($section->formID);

        $this->ensureStructureEditable($form->formID);

        DB::transaction(function () use ($section) {

            $criteriaIDs = DB::table('pre_criteria')
                ->where('sectionID', $section->sectionID)
                ->pluck('criteriaID');

            if ($criteriaIDs->isNotEmpty()) {

                $hasScores = DB::table('pre_score')
                    ->whereIn('criteriaID', $criteriaIDs)
                    ->exists();

                if ($hasScores) {
                    throw new \RuntimeException(
                        'This section contains scores and cannot be deleted'
                    );
                }

                DB::table('pre_criteria')
                    ->where('sectionID', $section->sectionID)
                    ->delete();
            }

            DB::table('pre_section_comment')
                ->where('sectionID', $section->sectionID)
                ->delete();

            DB::table('pre_section')
                ->where('sectionID', $section->sectionID)
                ->delete();
        });

        $sections = DB::table('pre_section')
            ->where('formID', $form->formID)
            ->orderBy('display_order')
            ->get();

        foreach ($sections as $index => $item) {

            DB::table('pre_section')
                ->where('sectionID', $item->sectionID)
                ->update([
                    'display_order' => $index + 1,
                ]);
        }

        return redirect()
            ->route('admin.pre.form.edit', $form->formID)
            ->with('success', 'Section deleted successfully');
    }


    // Add criteria
    public function storeCriteria(Request $request): RedirectResponse
    {
        $request->validate([
            'sectionID' => 'required|exists:pre_section,sectionID',
            'criteria_label' => 'required|string|max:500',
        ]);

        $section = $this->findSectionOrFail($request->sectionID);
        $form = $this->findFormOrFail($section->formID);

        $this->ensureStructureEditable($form->formID);

        $lastOrder = DB::table('pre_criteria')
            ->where('sectionID', $section->sectionID)
            ->max('display_order');

        DB::table('pre_criteria')->insert([
            'sectionID' => $section->sectionID,
            'criteria_label' => $request->criteria_label,
            'display_order' => ($lastOrder ?? 0) + 1,
        ]);

        return redirect()
            ->route('admin.pre.form.edit', $form->formID)
            ->with('success', 'Criteria added successfully');
    }


    // Update criteria
    public function updateCriteria(Request $request, $criteriaID): RedirectResponse
    {
        $criteria = $this->findCriteriaOrFail($criteriaID);
        $section = $this->findSectionOrFail($criteria->sectionID);
        $form = $this->findFormOrFail($section->formID);

        $request->validate([
            'criteria_label' => 'required|string|max:500',
        ]);

        DB::table('pre_criteria')
            ->where('criteriaID', $criteria->criteriaID)
            ->update([
                'criteria_label' => $request->criteria_label,
            ]);

        return redirect()
            ->route('admin.pre.form.edit', $form->formID)
            ->with('success', 'Criteria updated successfully');
    }


    // Delete criteria
    public function destroyCriteria($criteriaID): RedirectResponse
    {
        $criteria = $this->findCriteriaOrFail($criteriaID);
        $section = $this->findSectionOrFail($criteria->sectionID);
        $form = $this->findFormOrFail($section->formID);

        $this->ensureStructureEditable($form->formID);

        DB::table('pre_criteria')
            ->where('criteriaID', $criteria->criteriaID)
            ->delete();

        $criteriaList = DB::table('pre_criteria')
            ->where('sectionID', $section->sectionID)
            ->orderBy('display_order')
            ->get();

        foreach ($criteriaList as $index => $item) {

            DB::table('pre_criteria')
                ->where('criteriaID', $item->criteriaID)
                ->update([
                    'display_order' => $index + 1,
                ]);
        }

        return redirect()
            ->route('admin.pre.form.edit', $form->formID)
            ->with('success', 'Criteria deleted successfully');
    }


    // Check version has been used
    private function isFormUsed($formID): bool
    {
        return DB::table('pre_response')
            ->where('formID', $formID)
            ->exists();
    }


    // Block changes for used version
    private function ensureStructureEditable($formID): void
    {
        abort_if(
            $this->isFormUsed($formID),
            403,
            'This form has data already. Create a new version to make changes.'
        );
    }


    // Get form with sections and criteria
    private function getFormWithStructure($formID)
    {
        $form = $this->findFormOrFail($formID);

        $form->sections = DB::table('pre_section')
            ->where('formID', $form->formID)
            ->orderBy('display_order')
            ->get();

        foreach ($form->sections as $section) {

            $section->criteria = DB::table('pre_criteria')
                ->where('sectionID', $section->sectionID)
                ->orderBy('display_order')
                ->get();
        }

        return $form;
    }


    // Find form
    private function findFormOrFail($formID)
    {
        $form = DB::table('pre_form')
            ->where('formID', $formID)
            ->first();

        abort_if(!$form, 404);

        return $form;
    }


    // Find section
    private function findSectionOrFail($sectionID)
    {
        $section = DB::table('pre_section')
            ->where('sectionID', $sectionID)
            ->first();

        abort_if(!$section, 404);

        return $section;
    }


    // Find criteria
    private function findCriteriaOrFail($criteriaID)
    {
        $criteria = DB::table('pre_criteria')
            ->where('criteriaID', $criteriaID)
            ->first();

        abort_if(!$criteria, 404);

        return $criteria;
    }
}
