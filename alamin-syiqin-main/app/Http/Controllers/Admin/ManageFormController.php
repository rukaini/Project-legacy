<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreForm;
use App\Models\PostForm;
use App\Models\PdpcForm;
use App\Models\EvaluationDoc;

class ManageFormController extends Controller
{
    public function index()
    {
        // Get latest PRE form
        $preForm = PreForm::orderByDesc('formID')
            ->first();

        //PDPC form
        $pdpcForm = PdpcForm::orderByDesc('formID')
            ->first();

        //Feedback form
        $postForm = PostForm::orderByDesc('formID')
            ->first();

        // Get uploaded ev docs
        $documents = EvaluationDoc::latest('doc_id')
            ->get();

        return view(
            'admin.manage-form',
            compact(
                'preForm',
                'pdpcForm',
                'postForm',
                'documents'
            )
        );
    }
}
