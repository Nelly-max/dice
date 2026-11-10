<?php

namespace App\Services\GlamRoom;

use Illuminate\Support\Facades\Cache;

/**
 * Lists the images the marketing team drops into
 * {MEDIA_ROOT}/img/glamRoom/<folder> (hero sliders, leaflets, banners).
 * The folder is read from disk at most once per cache period instead of on
 * every page view. Falls back to the built-in images in public/img/glamRoom/<folder>.
 */
class MediaGallery
{
    /**
     * @return array<int, array{url: string, alt: string}>
     */
    public function images(string $folder): array
    {
        return Cache::remember(
            "glamroom.gallery.{$folder}",
            now()->addSeconds(config('glamroom.gallery_cache_seconds')),
            function () use ($folder) {
                $dir = rtrim(config('app.media_root'), '/\\') . "/img/glamRoom/{$folder}";
                $base = rtrim(config('app.media_url'), '/') . "/img/glamRoom/{$folder}/";

                $files = glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];

                // Nothing uploaded yet: use the built-in images shipped in public/img/glamRoom/<folder>
                // (hero slides, leaflets, banners), so the page slides like Home Market's.
                if (!$files) {
                    $files = glob(public_path("img/glamRoom/{$folder}") . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
                    $base = asset("img/glamRoom/{$folder}") . '/';
                }

                natsort($files);

                return array_values(array_map(fn ($file) => [
                    'url' => $base . rawurlencode(basename($file)),
                    'alt' => pathinfo($file, PATHINFO_FILENAME),
                ], $files));
            }
        );
    }

    public function categoryIcon(string $file): string
    {
        return rtrim(config('app.media_url'), '/') . '/img/glamRoom/Category_icons/' . $file;
    }
}
