<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Оригинал фото для окна кадрирования (только для вошедших в админку): с диска local, вне папки сайта. */
class MediaOriginalController extends Controller
{
    public function __invoke(Media $media): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($media->original_path && $disk->exists($media->original_path), 404);

        return $disk->response($media->original_path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
