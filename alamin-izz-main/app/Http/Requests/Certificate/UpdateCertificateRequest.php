<?php

namespace App\Http\Requests\Certificate;

/** Same rules as uploading, except a new PDF is optional — omit it to keep the current file. */
class UpdateCertificateRequest extends StoreCertificateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'certificate' => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }
}
