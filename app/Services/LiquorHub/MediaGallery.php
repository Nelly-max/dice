<?php

namespace App\Services\LiquorHub;

use Illuminate\Support\Facades\Cache;

/**
 * Lists the images the marketing team drops into
 * {MEDIA_ROOT}/img/liquorHub/<folder> (hero sliders, leaflets, banners).
 * The folder is read from disk at most once per cache period instead of on
 * every page view. Falls back to the built-in images in public/img/liquorHub/<folder>.
 */
class MediaGallery
{
    /**
     * @return array<int, array{url: string, alt: string}>
     */
    public function images(string $folder): array
    {
        return Cache::remember(
            "liquorhub.gallery.{$folder}",
            now()->addSeconds(config('liquorhub.gallery_cache_seconds')),
            function () use ($folder) {
                $dir = rtrim(config('app.media_root'), '/\\') . "/img/liquorHub/{$folder}";
                $base = rtrim(config('app.media_url'), '/') . "/img/liquorHub/{$folder}/";

                $files = glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];

                // Nothing uploaded yet: use the built-in images shipped in public/img/liquorHub/<folder>
                // (hero slides, leaflets, banners), so the page slides like Home Market's.
                if (!$files) {
                    $files = glob(public_path("img/liquorHub/{$folder}") . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
                    $base = asset("img/liquorHub/{$folder}") . '/';
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
        return rtrim(config('app.media_url'), '/') . '/img/liquorHub/Category_icons/' . $file;
    }
}
