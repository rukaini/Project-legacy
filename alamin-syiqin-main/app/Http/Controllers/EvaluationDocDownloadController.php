<?php

namespace App\Http\Controllers;

use App\Models\EvaluationDoc;

class EvaluationDocDownloadController extends Controller
{
    public function observer()
    {
        $documents = EvaluationDoc::orderBy('doc_id', 'desc')->get();

        return view('evaluation-doc.download', [
            'documents' => $documents,
            'role' => 'observer',
        ]);
    }


    public function external()
    {
        $documents = EvaluationDoc::orderBy('doc_id', 'desc')->get();

        return view('evaluation-doc.download', [
            'documents' => $documents,
            'role' => 'external',
        ]);
    }

    public function download($doc_id)
    {
        $document = EvaluationDoc::findOrFail(
            $doc_id
        );

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
}
