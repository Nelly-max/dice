<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

/**
 * Public information pages: /about, /help, /contact, /terms, /privacy-policy.
 * The list (titles, menu labels, icons, dates) lives in config/smartmarket.php;
 * each page's text is resources/views/pages/{slug}.blade.php.
 */
class PageController extends Controller
{
    public function show(string $page)
    {
        $pages = config('smartmarket.pages');
        abort_unless(isset($pages[$page]) && view()->exists("pages.{$page}"), 404);

        $meta = $pages[$page];

        return view('pages.show', [
            'slug'    => $page,
            'page'    => $meta,
            'pages'   => $pages,
            'updated' => isset($meta['updated']) ? Carbon::parse($meta['updated']) : null,
        ]);
    }
}
