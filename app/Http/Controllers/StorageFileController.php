<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    /**
     * Fallback for hosts where public/storage symlink is missing or returns 403.
     * Only used when Apache/Nginx does not already serve the file.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        abort_if($path === '' || str_contains($path, '..'), 404);

        $full = storage_path('app/public/'.$path);

        abort_unless(is_file($full), 404);

        return response()->file($full, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
