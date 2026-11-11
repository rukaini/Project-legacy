<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostFormController extends Controller
{
    // Show version list
    public function index()
    {
        $forms = DB::table('post_form')
            ->orderByDesc('version')
            ->get();

        foreach ($forms as $form) {

            $form->sections = DB::table('post_section')
                ->where('formID', $form->formID)
                ->orderBy('display_order')
                ->get();

            foreach ($form->sections as $section) {

                $section->fields = DB::table('post_field')
                    ->where('sectionID', $section->sectionID)
                    ->orderBy('display_order')
                    ->get();

                foreach ($section->fields as $field) {

                    $field->options = DB::table('post_field_option')
                        ->where('fieldID', $field->fieldID)
                        ->orderBy('display_order')
                        ->get();
                }
            }

            $form->is_used = $this->isFormUsed($form->formID);
            $form->section_count = $form->sections->count();
            $form->field_count = $form->sections->sum(fn($section) => $section->fields->count());
        }

        return view('admin.post-form', compact('forms'));
    }


    // Preview version
    public function show($postForm)
    {
        $form = $this->getFormWithStructure($postForm);

        return view('admin.post-form-preview', [
            'form' => $form,
        ]);
    }


    // Edit/manage version
    public function edit($postForm)
    {
        $form = $this->getFormWithStructure($postForm);

        $formUsed = $this->isFormUsed($form->formID);

        return view('admin.post-form-edit', [
            'form' => $form,
            'formUsed' => $formUsed,
        ]);
    }


    // Create first version
    public function storeForm(Request $request)
    {
        $request->validate([
            'form_name' => 'required|string|max:255',
            'instruction' => 'nullable|string',
        ]);

        if (DB::table('post_form')->exists()) {

            return redirect()
                ->route('admin.post.form')
                ->with('error', 'Form already exists. Create a new version instead');
        }

        $formID = DB::table('post_form')->insertGetId([
            'form_name' => $request->form_name,
            'version' => 1,
            'instruction' => $request->instruction,
            'status' => 'Active',
            'staffID' => Auth::guard('admin')->id(),
        ], 'formID');

        return redirect()
            ->route('admin.post.form.edit', $formID)
            ->with('success', 'Feedback form created successfully');
    }


    // Create new version
    public function createNewVersion($postForm)
    {
        $form = $this->getFormWithStructure($postForm);

        $newFormID = null;

        DB::transaction(function () use ($form, &$newFormID) {

            DB::table('post_form')
                ->where('status', 'Active')
                ->update([
                    'status' => 'Inactive',
                ]);

            $nextVersion = (DB::table('post_form')->max('version') ?? 0) + 1;

            $newFormID = DB::table('post_form')->insertGetId([
                'form_name' => $form->form_name,
                'version' => $nextVersion,
                'instruction' => $form->instruction,
                'status' => 'Active',
                'staffID' => Auth::guard('admin')->id(),
            ], 'formID');

            foreach ($form->sections as $oldSection) {

                $newSectionID = DB::table('post_section')->insertGetId([
                    'formID' => $newFormID,
                    'section_name' => $oldSection->section_name,
                    'display_order' => $oldSection->display_order,
                ], 'sectionID');

                foreach ($oldSection->fields as $oldField) {

                    $newFieldID = DB::table('post_field')->insertGetId([
                        'sectionID' => $newSectionID,
                        'field_label' => $oldField->field_label,
                        'field_type' => $oldField->field_type,
                        'display_order' => $oldField->display_order,
                        'is_required' => $oldField->is_required,
                    ], 'fieldID');

                    foreach ($oldField->options as $oldOption) {

                        DB::table('post_field_option')->insert([
                            'fieldID' => $newFieldID,
                            'option_label' => $oldOption->option_label,
                            'display_order' => $oldOption->display_order,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.post.form.edit', $newFormID)
            ->with('success', 'New Feedback form version created successfully');
    }


    // Update form information
    public function updateForm(Request $request, $postForm)
    {
        $request->validate([
            'form_name' => 'required|string|max:255',
            'instruction' => 'nullable|string',
        ]);

        $form = $this->findFormOrFail($postForm);

        DB::table('post_form')
            ->where('formID', $form->formID)
            ->update([
                'form_name' => $request->form_name,
                'instruction' => $request->instruction,
                'staffID' => Auth::guard('admin')->id(),
            ]);

        return redirect()
            ->route('admin.post.form.edit', $form->formID)
            ->with('success', 'Form information updated successfully');
    }


    // Delete unused version
    public function destroyForm($postForm)
    {
        $form = $this->getFormWithStructure($postForm);

        if ($this->isFormUsed($form->formID)) {

            return redirect()
                ->route('admin.post.form')
                ->with('error', 'This version has data already and cannot be deleted');
        }

        $wasActive = $form->status === 'Active';

        DB::transaction(function () use ($form) {

            foreach ($form->sections as $section) {

                foreach ($section->fields as $field) {

                    DB::table('post_field_option')
                        ->where('fieldID', $field->fieldID)
                        ->delete();

                    DB::table('post_field')
                        ->where('fieldID', $field->fieldID)
                        ->delete();
                }

                DB::table('post_section')
                    ->where('sectionID', $section->sectionID)
                    ->delete();
            }

            DB::table('post_form')
                ->where('formID', $form->formID)
                ->delete();
        });

        if ($wasActive) {

            DB::table('post_form')
                ->where('status', 'Active')
                ->update([
                    'status' => 'Inactive',
                ]);

            $previousForm = DB::table('post_form')
                ->orderByDesc('version')
                ->first();

            if ($previousForm) {

                DB::table('post_form')
                    ->where('formID', $previousForm->formID)
                    ->update([
                        'status' => 'Active',
                    ]);
            }
        }

        return redirect()
            ->route('admin.post.form')
            ->with('success', 'Form version deleted successfully');
    }


    // Add section
    public function storeSection(Request $request)
    {
        $request->validate([
            'formID' => 'required|exists:post_form,formID',
            'section_name' => 'required|string|max:255',
        ]);

        $form = $this->findFormOrFail($request->formID);

        $this->ensureStructureEditable($form->formID);

        $lastOrder = DB::table('post_section')
            ->where('formID', $form->formID)
            ->max('display_order');

        DB::table('post_section')->insert([
            'formID' => $form->formID,
            'section_name' => $request->section_name,
            'display_order' => ($lastOrder ?? 0) + 1,
        ]);

        return redirect()
            ->route('admin.post.form.edit', $form->formID)
            ->with('success', 'Section added successfully');
    }


    // Update section wording
    public function updateSection(Request $request, $sectionID)
    {
        $section = $this->findSectionOrFail($sectionID);
        $form = $this->findFormOrFail($section->formID);

        $request->validate([
            'section_name' => 'required|string|max:255',
        ]);

        DB::table('post_section')
            ->where('sectionID', $section->sectionID)
            ->update([
                'section_name' => $request->section_name,
            ]);

        return redirect()
            ->route('admin.post.form.edit', $form->formID)
            ->with('success', 'Section updated successfully');
    }


    // Delete section
    public function destroySection($sectionID)
    {
        $section = $this->findSectionOrFail($sectionID);
        $form = $this->findFormOrFail($section->formID);

        $this->ensureStructureEditable($form->formID);

        DB::transaction(function () use ($section) {

            $fields = DB::table('post_field')
                ->where('sectionID', $section->sectionID)
                ->get();

            foreach ($fields as $field) {

                DB::table('post_field_option')
                    ->where('fieldID', $field->fieldID)
                    ->delete();

                DB::table('post_field')
                    ->where('fieldID', $field->fieldID)
                    ->delete();
            }

            DB::table('post_section')
                ->where('sectionID', $section->sectionID)
                ->delete();
        });

        $sections = DB::table('post_section')
            ->where('formID', $form->formID)
            ->orderBy('display_order')
            ->get();

        foreach ($sections as $index => $item) {

            DB::table('post_section')
                ->where('sectionID', $item->sectionID)
                ->update([
                    'display_order' => $index + 1,
                ]);
        }

        return redirect()
            ->route('admin.post.form.edit', $form->formID)
            ->with('success', 'Section deleted successfully');
    }


    // Add field
    public function storeField(Request $request)
    {
        $request->validate([
            'sectionID' => 'required|exists:post_section,sectionID',
            'field_label' => 'required|string|max:500',
            'field_type' => 'required|in:display,text,textarea,checkbox,radio',
            'is_required' => 'required|boolean',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string|max:255',
        ]);

        $section = $this->findSectionOrFail($request->sectionID);
        $form = $this->findFormOrFail($section->formID);

        $this->ensureStructureEditable($form->formID);

        $lastOrder = DB::table('post_field')
            ->where('sectionID', $section->sectionID)
            ->max('display_order');

        $fieldID = DB::table('post_field')->insertGetId([
            'sectionID' => $section->sectionID,
            'field_label' => $request->field_label,
            'field_type' => $request->field_type,
            'display_order' => ($lastOrder ?? 0) + 1,
            'is_required' => $request->field_type === 'display'
                ? 0
                : $request->is_required,
        ], 'fieldID');

        $this->saveOptions($request, $fieldID);

        return redirect()
            ->route('admin.post.form.edit', $form->formID)
            ->with('success', 'Field added successfully');
    }


    // Update field
    public function updateField(Request $request, $fieldID)
    {
        $field = $this->findFieldOrFail($fieldID);
        $section = $this->findSectionOrFail($field->sectionID);
        $form = $this->findFormOrFail($section->formID);

        $formUsed = $this->isFormUsed($form->formID);

        if ($formUsed) {

            $request->validate([
                'field_label' => 'required|string|max:500',
            ]);

            DB::table('post_field')
                ->where('fieldID', $field->fieldID)
                ->update([
                    'field_label' => $request->field_label,
                ]);
        } else {

            $request->validate([
                'field_label' => 'required|string|max:500',
                'field_type' => 'required|in:display,text,textarea,checkbox,radio',
                'is_required' => 'required|boolean',
                'options' => 'nullable|array',
                'options.*' => 'nullable|string|max:255',
            ]);

            DB::table('post_field')
                ->where('fieldID', $field->fieldID)
                ->update([
                    'field_label' => $request->field_label,
                    'field_type' => $request->field_type,
                    'is_required' => $request->field_type === 'display'
                        ? 0
                        : $request->is_required,
                ]);

            DB::table('post_field_option')
                ->where('fieldID', $field->fieldID)
                ->delete();

            $this->saveOptions($request, $field->fieldID);
        }

        return redirect()
            ->route('admin.post.form.edit', $form->formID)
            ->with('success', 'Field updated successfully');
    }


    // Delete field
    public function destroyField($fieldID)
    {
        $field = $this->findFieldOrFail($fieldID);
        $section = $this->findSectionOrFail($field->sectionID);
        $form = $this->findFormOrFail($section->formID);

        $this->ensureStructureEditable($form->formID);

        $sectionID = $field->sectionID;

        DB::table('post_field_option')
            ->where('fieldID', $field->fieldID)
            ->delete();

        DB::table('post_field')
            ->where('fieldID', $field->fieldID)
            ->delete();

        $fields = DB::table('post_field')
            ->where('sectionID', $sectionID)
            ->orderBy('display_order')
            ->get();

        foreach ($fields as $index => $item) {

            DB::table('post_field')
                ->where('fieldID', $item->fieldID)
                ->update([
                    'display_order' => $index + 1,
                ]);
        }

        return redirect()
            ->route('admin.post.form.edit', $form->formID)
            ->with('success', 'Field deleted successfully');
    }


    // Save checkbox/radio options
    private function saveOptions(Request $request, $fieldID)
    {
        if (!in_array($request->field_type, ['checkbox', 'radio'])) {
            return;
        }

        if (!$request->has('options')) {
            return;
        }

        foreach ($request->options as $index => $option) {

            if (is_null($option) || trim($option) === '') {
                continue;
            }

            DB::table('post_field_option')->insert([
                'fieldID' => $fieldID,
                'option_label' => trim($option),
                'display_order' => $index + 1,
            ]);
        }
    }


    // Check whether version has been used
    private function isFormUsed($formID): bool
    {
        return DB::table('post_response')
            ->where('formID', $formID)
            ->exists();
    }


    // Block structural changes for used version
    private function ensureStructureEditable($formID): void
    {
        abort_if(
            $this->isFormUsed($formID),
            403,
            'This form has already been used. Create a new version to make changes'
        );
    }


    // Get form with sections, fields and options
    private function getFormWithStructure($formID)
    {
        $form = $this->findFormOrFail($formID);

        $form->sections = DB::table('post_section')
            ->where('formID', $form->formID)
            ->orderBy('display_order')
            ->get();

        foreach ($form->sections as $section) {

            $section->fields = DB::table('post_field')
                ->where('sectionID', $section->sectionID)
                ->orderBy('display_order')
                ->get();

            foreach ($section->fields as $field) {

                $field->options = DB::table('post_field_option')
                    ->where('fieldID', $field->fieldID)
                    ->orderBy('display_order')
                    ->get();
            }
        }

        return $form;
    }


    // Find form
    private function findFormOrFail($formID)
    {
        $form = DB::table('post_form')
            ->where('formID', $formID)
            ->first();

        abort_if(!$form, 404);

        return $form;
    }


    // Find section
    private function findSectionOrFail($sectionID)
    {
        $section = DB::table('post_section')
            ->where('sectionID', $sectionID)
            ->first();

        abort_if(!$section, 404);

        return $section;
    }


    // Find field
    private function findFieldOrFail($fieldID)
    {
        $field = DB::table('post_field')
            ->where('fieldID', $fieldID)
            ->first();

        abort_if(!$field, 404);

        return $field;
    }
}
