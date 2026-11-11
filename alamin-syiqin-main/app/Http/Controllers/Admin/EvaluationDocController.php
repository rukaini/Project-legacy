<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class EvaluationDocController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'form_name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'file' => [
                'required',
                'file',
                'mimes:pdf,xls,xlsx',
                'max:10240',
            ],
        ]);


        $file = $request->file('file');

        $originalName = $file->getClientOriginalName();

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );


        $path = $file->store(
            'evaluation-documents',
            'public'
        );


        $fileType = $extension === 'pdf'
            ? 'PDF'
            : 'EXCEL';


        EvaluationDoc::create([
            'form_name' => $request->form_name,
            'description' => $request->description,
            'file_name' => $originalName,
            'file_path' => $path,
            'file_type' => $fileType,

            // Logged-in Admin
            'staffID' => Auth::guard('admin')->id(),
        ]);


        return redirect()
            ->route('admin.manage.form')
            ->with(
                'success',
                'Evaluation document uploaded successfully'
            );
    }


    public function download($doc_id)
    {
        $document = EvaluationDoc::findOrFail($doc_id);

        $filePath = storage_path(
            'app/public/' . $document->file_path
        );

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download(
            $filePath,
            $document->file_name
        );
    }


    public function destroy($doc_id)
    {
        $document = EvaluationDoc::findOrFail(
            $doc_id
        );


        if (
            $document->file_path &&
            Storage::disk('public')->exists(
                $document->file_path
            )
        ) {
            Storage::disk('public')->delete(
                $document->file_path
            );
        }


        $document->delete();


        return redirect()
            ->route('admin.manage.form')
            ->with(
                'success',
                'Document deleted successfully'
            );
    }
}
