<?php

namespace App\Services\SmartMarket;

use Illuminate\Support\Facades\Cache;

/**
 * The main Smart Market home page's pictures, uploaded from the hub (Home → Banners) into
 * {MEDIA_ROOT}/img/smartMarket/<folder> (hero sliders, leaflets, banners).
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
            // the folder's modified time is part of the key, so pictures added from the hub show at once
            "smartmarket.gallery.{$folder}." . (int) @filemtime(rtrim(config('app.media_root'), '/\\') . "/img/smartMarket/{$folder}"),
            now()->addSeconds(config('homemarket.gallery_cache_seconds', 300)),
            function () use ($folder) {
                $dir = rtrim(config('app.media_root'), '/\\') . "/img/smartMarket/{$folder}";
                $base = rtrim(config('app.media_url'), '/') . "/img/smartMarket/{$folder}/";

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
        return rtrim(config('app.media_url'), '/') . '/img/smartMarket/Category_icons/' . $file;
    }
}
