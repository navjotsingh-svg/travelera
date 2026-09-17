<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublicUpload
{
    /**
     * Store an uploaded file under public/uploads/{folder} without needing a disk config.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/');
        $directory = public_path('uploads/'.$folder);

        File::ensureDirectoryExists($directory, 0755);

        $name = Str::random(40).'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $file->move($directory, $name);

        return asset('uploads/'.$folder.'/'.$name);
    }

    public static function delete(?string $urlOrPath): void
    {
        if (! filled($urlOrPath)) {
            return;
        }

        $path = $urlOrPath;

        if (str_starts_with($urlOrPath, 'http://') || str_starts_with($urlOrPath, 'https://') || str_starts_with($urlOrPath, '//')) {
            $path = parse_url($urlOrPath, PHP_URL_PATH) ?: '';
        }

        $path = ltrim((string) $path, '/');

        if (! str_starts_with($path, 'uploads/')) {
            return;
        }

        $full = public_path($path);

        if (is_file($full)) {
            File::delete($full);
        }
    }
}
