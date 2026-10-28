<?php

namespace App\Services\HomeMarket;

use Illuminate\Support\Facades\Cache;

/**
 * Lists the images the marketing team drops into
 * {MEDIA_ROOT}/img/homeMarket/<folder> (hero sliders, leaflets, banners).
 * The folder is read from disk at most once per cache period instead of on
 * every page view.
 */
class MediaGallery
{
    /**
     * @return array<int, array{url: string, alt: string}>
     */
    public function images(string $folder): array
    {
        return Cache::remember(
            "homemarket.gallery.{$folder}",
            now()->addSeconds(config('homemarket.gallery_cache_seconds')),
            function () use ($folder) {
                $dir = rtrim(config('app.media_root'), '/\\') . "/img/homeMarket/{$folder}";
                $base = rtrim(config('app.media_url'), '/') . "/img/homeMarket/{$folder}/";

                $files = glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
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
        return rtrim(config('app.media_url'), '/') . '/img/homeMarket/Category_icons/' . $file;
    }
}
