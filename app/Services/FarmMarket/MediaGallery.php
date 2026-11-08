<?php

namespace App\Services\FarmMarket;

use Illuminate\Support\Facades\Cache;

/**
 * Lists the images the marketing team drops into
 * {MEDIA_ROOT}/img/farmMarket/<folder> (hero sliders, leaflets, banners).
 * The folder is read from disk at most once per cache period instead of on
 * every page view. Falls back to the built-in images in public/img/farmMarket/<folder>.
 */
class MediaGallery
{
    /**
     * @return array<int, array{url: string, alt: string}>
     */
    public function images(string $folder): array
    {
        return Cache::remember(
            "farmmarket.gallery.{$folder}",
            now()->addSeconds(config('farmmarket.gallery_cache_seconds')),
            function () use ($folder) {
                $dir = rtrim(config('app.media_root'), '/\\') . "/img/farmMarket/{$folder}";
                $base = rtrim(config('app.media_url'), '/') . "/img/farmMarket/{$folder}/";

                $files = glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];

                // Nothing uploaded yet: use the built-in images shipped in public/img/farmMarket/<folder>
                // (hero slides, leaflets, banners), so the page slides like Home Market's.
                if (!$files) {
                    $files = glob(public_path("img/farmMarket/{$folder}") . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
                    $base = asset("img/farmMarket/{$folder}") . '/';
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
        return rtrim(config('app.media_url'), '/') . '/img/farmMarket/Category_icons/' . $file;
    }
}
