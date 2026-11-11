<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningMaterialController extends Controller
{
    public function show(LearningMaterial $material, \Illuminate\Http\Request $request): StreamedResponse
    {
        abort_if(! $material->file_path, 404);
        abort_unless(Storage::disk('public')->exists($material->file_path), 404, 'File not found.');

        $fileName = $material->original_file_name ?? basename($material->file_path);
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $inline = ! $request->boolean('dl') && $ext === 'pdf';

        $headers = $inline
            ? ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $fileName . '"']
            : [];

        return $inline
            ? Storage::disk('public')->response($material->file_path, $fileName, $headers)
            : Storage::disk('public')->download($material->file_path, $fileName);
    }
}
