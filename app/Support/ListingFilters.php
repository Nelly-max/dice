<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The filter chips and the grid / list switch on the Home City and Airbnb results pages.
 *
 * Every item (a Home City card or an Airbnb stay) carries:
 *   ftype_key, ftype_name, ftype_order   its property type (Apartment, Bungalow,
 *                                        Commercial plot, Hostel …, from the database)
 *   funit_key, funit_name                its unit type (Bedsitter, 2 Bedroom, 50x100 plot …)
 *
 *   ?pt=<type key>   keep one property type; its unit types then show as a second row
 *   ?ut=<unit key>   keep one unit type
 *
 * A row is only shown when it has two or more choices. When there is a single
 * property type (e.g. the Hostels page) the unit types show straight away.
 */
class ListingFilters
{
    public const VIEWS = ['grid', 'list'];
    public const VIEW_COOKIE = 'listing_view';

    /**
     * @return array{items: Collection, types: Collection, units: Collection, pt: ?string, ut: ?string,
     *               showTypes: bool, showUnits: bool, typeName: ?string, unitName: ?string}
     */
    public static function apply(Collection $items, Request $request): array
    {
        $types = $items
            ->groupBy(fn ($i) => (string) ($i->ftype_key ?? 'other'))
            ->map(fn ($group, $key) => (object) [
                'key'   => (string) $key,
                'name'  => $group->first()->ftype_name ?? 'Other',
                'order' => $group->first()->ftype_order ?? 999,
                'count' => $group->count(),
            ])
            ->sortBy([['order', 'asc'], ['name', 'asc']])
            ->values();

        $pt = (string) $request->query('pt', '');
        $pt = $types->contains('key', $pt) ? $pt : null;

        $scope = $pt ? $items->filter(fn ($i) => (string) ($i->ftype_key ?? 'other') === $pt)->values() : $items;

        $units = $scope
            ->groupBy(fn ($i) => (string) ($i->funit_key ?? 'other'))
            ->map(fn ($group, $key) => (object) [
                'key'   => (string) $key,
                'name'  => $group->first()->funit_name ?? 'Other',
                'count' => $group->count(),
            ])
            ->sort(fn ($a, $b) => strnatcasecmp($a->name, $b->name))
            ->values();

        $ut = (string) $request->query('ut', '');
        $ut = $units->contains('key', $ut) ? $ut : null;

        $showTypes = $types->count() > 1;
        $showUnits = ($pt !== null || !$showTypes) && $units->count() > 1;

        return [
            'items'     => $ut ? $scope->filter(fn ($i) => (string) ($i->funit_key ?? 'other') === $ut)->values() : $scope,
            'types'     => $types,
            'units'     => $units,
            'pt'        => $pt,
            'ut'        => $ut,
            'showTypes' => $showTypes,
            'showUnits' => $showUnits,
            'typeName'  => $pt ? $types->firstWhere('key', $pt)->name : null,
            'unitName'  => $ut ? $units->firstWhere('key', $ut)->name : null,
        ];
    }

    /** grid (cards) or list (rows): ?view= first, then the visitor's last choice. */
    public static function view(Request $request): string
    {
        $view = (string) $request->query('view', '');
        if (in_array($view, self::VIEWS, true)) return $view;

        return $request->cookie(self::VIEW_COOKIE) === 'list' ? 'list' : 'grid';
    }

    /** Remember ?view= for the next results page (a year). */
    public static function rememberView(Request $request): void
    {
        $view = (string) $request->query('view', '');
        if (in_array($view, self::VIEWS, true)) {
            cookie()->queue(cookie(self::VIEW_COOKIE, $view, 60 * 24 * 365));
        }
    }
}
