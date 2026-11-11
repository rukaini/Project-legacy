<?php

namespace App\Services\Lms;

use Illuminate\Support\Facades\File;

class TrainerLmsSubmissionFileResolver
{
    public function resolve(?string $storedPath): ?string
    {
        $relativePath = $this->relativePath($storedPath);

        return $relativePath === null
            ? null
            : storage_path('app/public/'.$relativePath);
    }

    public function exists(?string $storedPath): bool
    {
        $path = $this->resolve($storedPath);

        return $path !== null && File::isFile($path);
    }

    public function safeFileName(?string $originalName, ?string $storedPath): string
    {
        $name = $originalName ?: basename(str_replace('\\', '/', (string) $storedPath));
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F"<>:|?*]/u', '_', $name) ?: 'submission-file';

        return trim($name, ". \t\n\r\0\x0B") ?: 'submission-file';
    }

    public function mimeType(string $resolvedPath, string $displayName): string
    {
        if (strtolower(pathinfo($displayName, PATHINFO_EXTENSION)) === 'pdf') {
            return 'application/pdf';
        }

        return File::mimeType($resolvedPath) ?: 'application/octet-stream';
    }

    private function relativePath(?string $storedPath): ?string
    {
        $normalized = str_replace('\\', '/', trim((string) $storedPath));

        if (
            $normalized === ''
            || str_contains($normalized, "\0")
            || str_contains($normalized, '://')
            || str_starts_with($normalized, '/')
            || preg_match('/^[A-Za-z]:/', $normalized)
        ) {
            return null;
        }

        foreach (['storage/app/public/', 'app/public/', 'public/storage/', 'storage/', 'public/'] as $prefix) {
            if (str_starts_with(strtolower($normalized), $prefix)) {
                $normalized = substr($normalized, strlen($prefix));
                break;
            }
        }

        $segments = explode('/', trim($normalized, '/'));
        if ($segments === [] || in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            return null;
        }

        return implode('/', $segments);
    }
}
