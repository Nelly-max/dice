<?php

namespace App\Http\Controllers\Web\HomeCity;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\HomeCity\Listing;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Display all properties on the homepage
     */

    
    public function index(Request $request)
    {
        $cards = $this->cards();

        // results pages: grid or list, kept for the next visit (App\Support\ListingFilters)
        \App\Support\ListingFilters::rememberView($request);

        // live suggestions for the header search (resources/js/hmsearch.js)
        if ($request->wantsJson()) {
            return response()->json($this->suggest($cards, (string) $request->query('q', '')));
        }

        return view('HomeCity.home', [
            'listings' => $cards
        ]);
    }

    /**
     * Near me: every listing, nearest to the visitor's saved location first, with the
     * 500 m … 5 km chips. Without a location the page asks for one (map sheet).
     */
    public function near(Request $request)
    {
        \App\Support\ListingFilters::rememberView($request);

        return view('HomeCity.home', ['listings' => $this->cards(), 'mode' => 'near']);
    }

    /**
     * Listers: everyone with listings on the site (active subscription with listing or
     * premium_listing), nearest first when the visitor has a location, like the gas
     * "Shops near you" page. Each opens the lister's profile (lister()).
     */
    public function listers(Request $request)
    {
        $here = app(\App\Services\DeliveryLocationService::class)->get($request);
        $byLister = collect($this->cards())->groupBy('lister_id');
        $profiles = $this->listerProfiles($byLister->keys()->all());
        $accounts = $this->listableAccounts();

        $listers = $byLister->map(function ($items, $account) use ($profiles, $here, $accounts) {
            $p = $profiles[$account] ?? null;
            if (!$p) return null;

            $km = $here ? \App\Support\Distance::km($here['latitude'], $here['longitude'], $p->latitude, $p->longitude) : null;
            $prices = $items->pluck('min_price')->filter(fn ($v) => $v > 0);

            return (object) [
                'account'   => $account,
                'name'      => $p->name,
                'initial'   => $p->initial,
                'logo'      => $p->logo,
                'area'      => $p->area,
                'premium'   => (bool) ($accounts[$account] ?? 0),
                'listings'  => $items->pluck('listing_id')->unique()->count(),
                'vacant'    => (int) $items->sum('units_vacant'),
                'kinds'     => $items->pluck('ftype_name')->filter()->unique()->take(3)->values(),
                'from'      => (float) ($prices->min() ?? 0),
                'distance_km' => $km,
                'url'       => route('homecity.lister', $account),
            ];
        })->filter();

        $listers = $here
            ? $listers->sortBy(fn ($l) => $l->distance_km ?? INF)->values()
            : $listers->sortBy([['premium', 'desc'], ['name', 'asc']])->values();

        return view('HomeCity.listers', ['listers' => $listers, 'hasLocation' => (bool) $here]);
    }

    /** A lister's profile: who they are, how to reach them, and all their listings (filters, grid / list). */
    public function lister(Request $request, string $account)
    {
        $cards = collect($this->cards())->where('lister_id', $account)->values();
        $profile = $this->listerProfiles([$account])[$account] ?? null;

        abort_if($cards->isEmpty() || !$profile, 404);

        \App\Support\ListingFilters::rememberView($request);

        return view('HomeCity.home', ['listings' => $cards, 'mode' => 'lister', 'lister' => $profile]);
    }

    /**
     * Lister details from the homecity `business` table (+ place names from the hub),
     * keyed by account. Cached for 2 minutes.
     *
     * @return array<string, object>
     */
    private function listerProfiles(array $accounts): array
    {
        $accounts = array_values(array_filter(array_map('strval', $accounts)));
        if (!$accounts) return [];

        sort($accounts);

        return Cache::remember('homecity.listers.' . md5(implode('|', $accounts)), 120, function () use ($accounts) {
            try {
                $rows = DB::connection('homecity')->table('business')
                    ->whereIn('account', $accounts)
                    ->get(['account', 'name', 'phone', 'email', 'logo', 'latitude', 'longitude', 'county_id', 'town_id', 'place_id']);
            } catch (\Throwable $e) {
                report($e);
                return [];
            }

            $names = function (string $table, string $column) use ($rows) {
                $ids = $rows->pluck($column)->filter()->unique()->all();
                if (!$ids) return collect();
                try { return DB::connection('hub')->table($table)->whereIn('id', $ids)->pluck('name', 'id'); }
                catch (\Throwable $e) { return collect(); }
            };
            $counties = $names('physical_counties', 'county_id');
            $towns = $names('physical_towns', 'town_id');
            $places = $names('physical_places', 'place_id');

            return $rows->mapWithKeys(fn ($b) => [(string) $b->account => (object) [
                'account'   => (string) $b->account,
                'name'      => trim((string) $b->name) ?: 'Lister',
                'initial'   => mb_strtoupper(mb_substr(trim((string) $b->name) ?: 'L', 0, 1)),
                'phone'     => $b->phone,
                'email'     => $b->email,
                'logo'      => $b->logo ? self::mediaUrl($b->logo) : null,
                'latitude'  => is_numeric($b->latitude) ? (float) $b->latitude : null,
                'longitude' => is_numeric($b->longitude) ? (float) $b->longitude : null,
                'area'      => collect([$places[$b->place_id] ?? null, $towns[$b->town_id] ?? null, $counties[$b->county_id] ?? null])->filter()->implode(', '),
            ]])->all();
        });
    }

    /**
     * Business accounts allowed on the site: an ACTIVE subscription whose tariff has
     * listing or premium_listing. Value = 1 for premium_listing (shown first).
     *
     * @return array<string, int>
     */
    private function listableAccounts(): array
    {
        return Cache::remember('homecity.listable_accounts', 120, function () {
            try {
                return DB::connection('homecity')
                    ->table('business as b')
                    ->join('business_subscription as bs', function ($join) {
                        $join->on(function ($on) {
                            $on->on('bs.main_branch_account', '=', 'b.main_branch_account')
                                ->orOn('bs.business_id', '=', 'b.id');
                        })->where('bs.status', 'active');
                    })
                    ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
                    ->where(fn ($q) => $q->where('t.listing', 1)->orWhere('t.premium_listing', 1))
                    ->groupBy('b.account')
                    ->selectRaw('b.account, MAX(COALESCE(t.premium_listing, 0)) as priority')
                    ->pluck('priority', 'account')
                    ->map(fn ($p) => (int) $p)
                    ->all();
            } catch (\Throwable $e) {
                report($e);     // nothing is shown rather than unpaid listings
                return [];
            }
        });
    }

    /** All cards (one per unit type per property), priority listings first. Cached for 2 minutes. */
    private function cards()
    {
        return Cache::remember('homecity.cards.v3', 120, fn () => $this->buildCards());
    }

    /** Header search suggestions in the shape hmsearch.js expects. */
    private function suggest($cards, string $q): array
    {
        $q = trim(mb_strtolower($q));
        if (mb_strlen($q) < 2) return ['products' => [], 'categories' => [], 'shops' => []];

        $words = preg_split('/\s+/', $q);
        $here = app(\App\Services\DeliveryLocationService::class)->get(request());

        $hits = collect($cards)->filter(function ($c) use ($words) {
            $text = mb_strtolower(implode(' ', [$c->display_title, $c->unit_type, $c->location, $c->type_label, $c->kind]));
            return collect($words)->every(fn ($w) => str_contains($text, $w));
        });

        $types = collect($cards)->pluck('unit_type')
            ->map(fn ($t) => preg_replace('/\s*·.*$/u', '', $t))
            ->unique(fn ($t) => mb_strtolower($t))
            ->filter(fn ($t) => str_contains(mb_strtolower($t), $q))
            ->take(4)
            ->map(fn ($t) => ['label' => $t, 'url' => route('homecity.index', ['q' => $t])])
            ->values();

        return [
            'categories' => $types,
            'shops'      => [],
            'products'   => $hits->take(8)->map(function ($c) use ($here) {
                $km = $here ? \App\Support\Distance::km($here['latitude'], $here['longitude'], $c->latitude, $c->longitude) : null;
                return [
                    'url'     => route('homecity.listing.view', [$c->listing_id, 'unit' => $c->group]),
                    'image'   => $c->display_image,
                    'name'    => $c->display_title,
                    'variant' => $c->unit_type . ' · ' . $c->vacancy_text,
                    'shop'    => $c->location,
                    'km'      => $km !== null ? round($km, 1) : null,
                    'price'   => $c->min_price,
                ];
            })->values(),
        ];
    }

    /** "3 vacant units" / "1 vacant unit" / "No vacant units" (for sale: "for sale" / "Sold out"). */
    public static function vacancyText(int $vacant, bool $sale = false): string
    {
        if ($vacant < 1) return $sale ? 'Sold out' : 'No vacant units';

        return $sale
            ? $vacant . ' ' . \Illuminate\Support\Str::plural('unit', $vacant) . ' for sale'
            : $vacant . ' vacant ' . \Illuminate\Support\Str::plural('unit', $vacant);
    }

    /** id => name of a type table (house_property_types …), in the order they were added. */
    private function typeNames(string $table): array
    {
        try {
            return DB::connection('homecity')->table($table)->orderBy('id')->pluck('name', 'id')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * The property type a card is filtered by on the results pages (App\Support\ListingFilters):
     * homes by house_property_types, land by land_property_types, warehouses by
     * warehouse_property_types (all from the database); hostels, shops, offices and
     * containers by their kind. [key, name, order]
     */
    private static function filterType(string $kind, $property, array $names): array
    {
        $fromTable = function (string $prefix, string $table, string $column, string $fallback, int $base) use ($property, $names) {
            $id = $property->getAttribute($column);
            $list = $names[$table] ?? [];

            if ($id && isset($list[$id])) {
                $position = array_search($id, array_keys($list), true);
                return [$prefix . $id, \Illuminate\Support\Str::ucfirst(trim($list[$id])), $base + (int) $position];
            }

            return [$prefix . 'x', $fallback, $base + 99];
        };

        return match ($kind) {
            'house', 'sale' => $fromTable('h', 'house', 'house_property_type_id', 'Other homes', 100),
            'land'          => $fromTable('l', 'land', 'land_property_type_id', 'Land', 400),
            'warehouse'     => $fromTable('w', 'warehouse', 'warehouse_property_type_id', 'Warehouse', 500),
            'hostel'        => ['hostel', 'Hostel', 250],
            'shop'          => ['shop', 'Shop', 300],
            'office'        => ['office', 'Office', 310],
            'container'     => ['container', 'Container', 600],
            default         => ['other', 'Other', 900],
        };
    }

    private function buildCards()
    {
        $accounts = $this->listableAccounts();

        $typeNames = [
            'house'     => $this->typeNames('house_property_types'),
            'land'      => $this->typeNames('land_property_types'),
            'warehouse' => $this->typeNames('warehouse_property_types'),
        ];

        $listings = Listing::on('homecity')
            ->where('status', 'active')
            ->whereIn('lister_id', array_keys($accounts) ?: ['__none__'])
            ->with(self::UNIT_RELATIONS)
            ->get();

        /*
        | One card per unit TYPE per property (e.g. "2 Bedroom" at Sunrise Court),
        | never one card per unit. A property can have several listings (for
        | example one per unit type); a listing that names a house unit type only
        | gives that type's card, and the same property + type is shown once.
        */
        return $listings->flatMap(function ($listing) use ($accounts, $typeNames) {

            $property = $listing->property;
            if (!$property) return [];

            $onlyType = $listing->getAttribute('house_unit_type_id');

            $typeLabel = match ($property->listing_type) {
                'onSale'  => 'For Sale',
                'onLease' => 'For Lease',
                'forRent' => 'For Rent',
                default   => 'Listing',
            };

            $location = $property->location ?? null;

            return $this->unitGroups($property)
                ->when($onlyType, fn ($groups) => $groups->filter(
                    fn ($g) => !in_array($g->kind, ['house', 'sale'], true) || (string) $g->type_id === (string) $onlyType
                ))
                ->map(fn ($g) => (object) ([
                    'priority'      => $accounts[$listing->lister_id] ?? 0,
                    'vacancy_text'  => self::vacancyText($g->vacant, $g->kind === 'sale' || $typeLabel === 'For Sale'),
                    'key'           => $property->id . '-' . $g->key,
                    'listing_id'    => $listing->id,
                    'lister_id'     => (string) $listing->lister_id,
                    'property_id'   => $property->id,
                    'group'         => $g->key,
                    'kind'          => $g->kind,
                    'display_title' => $property->title,
                    'icon'          => $g->icon,
                    'unit_type'     => $g->label,
                    'display_image' => $g->image,
                    'min_price'     => $g->min,
                    'max_price'     => $g->max,
                    'price_suffix'  => $g->suffix,
                    'price_display' => $g->price_display,
                    'units_total'   => $g->total,
                    'units_vacant'  => $g->vacant,
                    'location'      => $property->formatted_location,
                    'type_label'    => $g->kind === 'sale' ? 'For Sale' : $typeLabel,
                    // for the "within 500 m … 5 km" filter on the Home City page
                    'latitude'      => is_numeric($location->latitude ?? null) ? (float) $location->latitude : null,
                    'longitude'     => is_numeric($location->longitude ?? null) ? (float) $location->longitude : null,
                    // results page filters: unit type ("2 Bedroom", "50x100 plot" …)
                    'funit_key'     => \Illuminate\Support\Str::slug($g->label) ?: 'unit',
                    'funit_name'    => $g->label,
                ] + array_combine(['ftype_key', 'ftype_name', 'ftype_order'], self::filterType($g->kind, $property, $typeNames))))
                ->values();
        })
            ->unique('key')
            ->sortByDesc('priority')      // premium_listing tariffs first (stable for the rest)
            ->values();
    }

    /** Relations needed to group a property's units (index + viewListing). */
    private const UNIT_RELATIONS = [
        'property.location',

        // Residential
        'property.houseUnits.type.images',
        'property.houseOnSaleUnits.type.images',
        'property.houseOnSaleTypeImages',

        // Land
        'property.landUnits.measureUnit',
        'property.landImages',

        // Shops & offices
        'property.officeUnits',
        'property.officeImages',
        'property.shopUnits',
        'property.shopImages',

        // Containers
        'property.containers',
        'property.containerImages',

        // Warehouses
        'property.warehouseUnits.images',
        'property.warehouseType',

        // Hostels
        'property.hostelUnits',
        'property.hostelImages',
    ];

    /** Status of one unit → is it free to take? */
    private static function isVacant($unit): bool
    {
        $status = strtolower(trim((string) ($unit->status ?? '')));

        return in_array($status, ['', 'vacant', 'available', 'open', 'unoccupied', 'empty', 'active', 'for sale', 'onsale'], true);
    }

    private static function mediaUrl(?string $path): string
    {
        $path = $path ? ltrim($path, '/') : '';

        if ($path === '') return asset('img/default-property.jpg');
        if (str_starts_with($path, 'http')) return $path;

        return rtrim((string) env('MEDIA_URL'), '/') . '/' . $path;
    }

    /**
     * A property's units grouped by unit type:
     *   house / sale  → house unit type ("Bedsitter", "2 Bedroom" …)
     *   land          → plot size ("50x100", "1/8 acre" …)
     *   hostel        → beds + period ("4 beds · per month")
     *   container     → size ("20ft")
     *   office, shop, warehouse → one group per property
     *
     * Each group: key, kind, type_id, icon, label, total, vacant, min, max, suffix, price_display, image (URL).
     */
    private function unitGroups($property)
    {
        $icons = [
            'house'     => 'fa-solid fa-house-chimney',
            'sale'      => 'fa-solid fa-house-circle-check',
            'land'      => 'fa-solid fa-expand',
            'office'    => 'fa-regular fa-building',
            'shop'      => 'fa-solid fa-store',
            'container' => 'fa-solid fa-box',
            'warehouse' => 'fa-solid fa-warehouse',
            'hostel'    => 'fa-solid fa-bed',
        ];

        $fallbackImage = $property->featured_image ?? null;
        $rows = collect();

        // houses to rent / for sale: one group per house unit type
        foreach (['house' => $property->houseUnits, 'sale' => $property->houseOnSaleUnits] as $kind => $units) {
            foreach (collect($units)->groupBy('house_unit_type_id') as $typeId => $group) {
                $type = $group->first()->type ?? null;
                $image = $kind === 'sale'
                    ? (optional(collect($property->houseOnSaleTypeImages ?? [])->where('house_unit_type_id', $typeId)->first())->image_path
                        ?? $type?->images?->first()?->image_path)
                    : $type?->images?->first()?->image_path;

                $rows->push([$kind, $kind . '-' . $typeId, $typeId, $type->name ?? 'Unit', $group, '', $image]);
            }
        }

        // land: one group per plot size
        foreach (collect($property->landUnits)->groupBy(fn ($u) => trim($u->size . ' ' . strtolower(optional($u->measureUnit)->name ?? ''))) as $label => $group) {
            $measureId = $group->first()->land_measure_unit_id ?? null;
            $landImages = collect($property->landImages ?? []);
            $image = optional($landImages->where('land_measure_unit_id', $measureId)->first())->image_path
                ?? optional($landImages->first())->image_path;

            $rows->push(['land', 'land-' . \Illuminate\Support\Str::slug($label ?: 'plot'), null, $label !== '' ? $label . ' plot' : 'Plot', $group, '', $image]);
        }

        // hostels: one group per beds + period
        foreach (collect($property->hostelUnits)->groupBy(fn ($u) => (int) $u->total_beds . '-' . strtolower((string) $u->duration)) as $key => $group) {
            $u = $group->first();
            $beds = (int) $u->total_beds;
            $label = $beds > 0 ? $beds . '-bed room' : 'Hostel room';
            $suffix = $u->duration ? 'per ' . strtolower($u->duration) : '';

            $rows->push(['hostel', 'hostel-' . $key, null, $label, $group, $suffix, collect($property->hostelImages ?? [])->first()?->image_path]);
        }

        // containers: one group per size; units_available is a count per row
        foreach (collect($property->containers)->groupBy('size') as $size => $group) {
            $image = optional(collect($property->containerImages)->where('container_size', $size)->first())->image_path
                ?? optional(collect($property->containerImages)->first())->image_path;

            $rows->push(['container', 'container-' . \Illuminate\Support\Str::slug($size ?: 'unit'), null, trim($size . ' container'), $group, '', $image]);
        }

        // offices, shops: one group each
        foreach (['office' => [$property->officeUnits, $property->officeImages], 'shop' => [$property->shopUnits, $property->shopImages]] as $kind => [$units, $images]) {
            $units = collect($units);
            if ($units->isEmpty()) continue;

            $first = $units->first();
            $measure = $first->measure_unit ?? 'sqft';
            $sizes = $units->pluck('size')->filter()->unique();
            $noun = $kind === 'office' ? 'Office' : 'Shop';
            $label = $sizes->count() === 1
                ? $noun . ' · ' . rtrim(rtrim(number_format((float) $sizes->first(), 2), '0'), '.') . ' ' . $measure
                : $noun . ' space';
            $suffix = ($first->rental_mode ?? null) === 'by_size' ? 'per ' . $measure : '';

            $rows->push([$kind, $kind, null, $label, $units, $suffix, collect($images)->first()?->image_path]);
        }

        // warehouses: one group
        $warehouses = collect($property->warehouseUnits);
        if ($warehouses->isNotEmpty()) {
            $first = $warehouses->first();
            $measure = $first->measure_unit ?? 'sqft';
            $label = $first->mode === 'space' && $first->total_space
                ? 'Warehouse · ' . rtrim(rtrim(number_format((float) $first->total_space, 2), '0'), '.') . ' ' . $measure
                : 'Warehouse';
            $suffix = $first->mode === 'space' ? 'per ' . $measure : '';

            $rows->push(['warehouse', 'warehouse', null, $label, $warehouses, $suffix, $first->images?->first()?->image_path]);
        }

        return $rows->map(function ($row) use ($icons, $fallbackImage) {
            [$kind, $key, $typeId, $label, $units, $suffix, $image] = $row;

            $prices = $units->map(fn ($u) => (float) ($u->price ?? $u->unit_price ?? 0))->filter(fn ($p) => $p > 0);
            $min = $prices->min() ?? 0;
            $max = $prices->max() ?? 0;

            if ($kind === 'container') {
                $total = (int) $units->sum(fn ($u) => max(1, (int) ($u->units_available ?? 0)));
                $vacant = (int) $units->sum(fn ($u) => max(0, (int) ($u->units_available ?? 0)));
            } else {
                $total = $units->count();
                $vacant = $units->filter(fn ($u) => self::isVacant($u))->count();
            }

            $suffixText = $suffix !== '' ? ' ' . $suffix : '';

            // each vacant unit, for the listing page (containers are counted, not numbered)
            $vacantUnits = $kind === 'container' ? collect() : $units
                ->filter(fn ($u) => self::isVacant($u))
                ->map(function ($u) use ($kind) {
                    $measure = $u->measure_unit ?? 'sqft';
                    $size = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.') . ' ' . $measure;

                    $name = match ($kind) {
                        'hostel'    => $u->room_number ?? $u->unit_number ?? null,
                        'land'      => $u->plot_number ?? $u->unit_number ?? null,
                        'warehouse' => $u->unit_number ?? null,
                        default     => $u->unit_number ?? null,
                    };

                    $detail = match ($kind) {
                        'office', 'shop' => ($u->rental_mode ?? null) === 'by_size' && !empty($u->remaining_space)
                            ? $size($u->remaining_space) . ' left'
                            : (!empty($u->size) ? $size($u->size) : null),
                        'warehouse' => ($u->mode ?? null) === 'space'
                            ? (!empty($u->total_space) ? $size($u->total_space) : null)
                            : (!empty($u->unit_count) ? $u->unit_count . ' units' : null),
                        default => null,
                    };

                    return (object) [
                        'id'     => $u->id ?? null,
                        'name'   => $name ? (string) $name : null,
                        'detail' => $detail,
                        'price'  => (float) ($u->price ?? $u->unit_price ?? 0),
                    ];
                })
                ->values()
                ->map(function ($u, $i) use ($kind) {
                    // no unit number stored: "Unit 1", "Room 2", "Plot 3" …
                    $u->name ??= match ($kind) { 'hostel' => 'Room', 'land' => 'Plot', 'office' => 'Office', 'shop' => 'Shop', 'warehouse' => 'Space', default => 'Unit' } . ' ' . ($i + 1);
                    return $u;
                });

            return (object) [
                'key'           => $key,
                'kind'          => $kind,
                'type_id'       => $typeId,
                'icon'          => $icons[$kind] ?? 'fa-solid fa-building',
                'label'         => $label,
                'total'         => $total,
                'vacant'        => $vacant,
                'min'           => $min,
                'max'           => $max,
                'suffix'        => $suffix,
                'price_display' => $min <= 0
                    ? 'Price on request'
                    : 'KES ' . number_format($min) . ($max > $min ? ' – ' . number_format($max) : '') . $suffixText,
                'image'         => self::mediaUrl($image ?? $fallbackImage),
                'vacant_units'  => $vacantUnits,
            ];
        })->values();
    }

    /**
     * Display a single property with all related info
     */
 /**
 * The listing + property a viewing is booked for (BookingController): only listings that are
 * shown on Home City, with the unit groups ("2 Bedroom · 3 vacant") the viewing form offers.
 */
public function bookingTarget($id): array
{
    $listing = Listing::on('homecity')->with(['property.location.county', 'property.location.town', 'property.location.place', 'lister'])->findOrFail($id);
    abort_unless($listing->status === 'active' && array_key_exists($listing->lister_id, $this->listableAccounts()), 404);
    $property = $listing->property;
    abort_unless($property, 404);
    $property->unit_groups = $this->unitGroups($property);
    return [$listing, $property];
}

public function viewListing($id)
{
    $listing = Listing::on('homecity')->with([
        'property.location.county',
        'property.location.town',
        'property.location.place',

        // Residential
        'property.houseUnits.type.images',
        'property.houseOnSaleUnits.type.images',

        // Land
        'property.landImages',

        // Commercial
        'property.officeImages',
        'property.shopImages',

        // Units
        'property.houseUnits',
        'property.houseOnSaleUnits',
        'property.houseOnSaleTypeImages',

        'property.landUnits.measureUnit',
        'property.hostelUnits',
        'property.containers',
        'property.warehouseUnits',

        // Warehouse images (IMPORTANT)
        'property.warehouseUnits.images',

        'property.containerImages',

        'lister'
    ])->findOrFail($id);

    // only listings of businesses with an active subscription that includes listing
    abort_unless($listing->status === 'active' && array_key_exists($listing->lister_id, $this->listableAccounts()), 404);

    $property = $listing->property;

    $baseUrl = rtrim(env('MEDIA_URL'), '/');

    /*
    |------------------------------------------------------------
    | GALLERY BUILDER (FIXED & UNIFIED)
    |------------------------------------------------------------
    */
    $allImages = collect();

    // HOUSE (RENT)
    foreach ($property->houseUnits ?? [] as $unit) {
        foreach ($unit->type?->images ?? [] as $img) {
            $allImages->push([
                'path'  => $img->image_path,
                'label' => $img->label,
            ]);
        }
    }

    // HOUSE (SALE)
    foreach ($property->houseOnSaleUnits ?? [] as $unit) {
        $images = $property->houseOnSaleTypeImages
            ->where('house_unit_type_id', $unit->house_unit_type_id);

        foreach ($images as $img) {
            $allImages->push([
                'path'  => $img->image_path,
                'label' => $img->label,
            ]);
        }
    }

    // LAND (PROPERTY LEVEL)
    foreach ($property->landImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // HOSTEL (PROPERTY LEVEL)
    foreach ($property->hostelImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // OFFICE
    foreach ($property->officeImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // SHOP
    foreach ($property->shopImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // CONTAINER
    foreach ($property->containerImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // WAREHOUSE (UNIT LEVEL)
    foreach ($property->warehouseUnits ?? [] as $unit) {
        foreach ($unit->images ?? [] as $img) {
            $allImages->push([
                'path'  => $img->image_path,
                'label' => $img->label,
            ]);
        }
    }

    /*
    |------------------------------------------------------------
    | FORMAT IMAGES (SAFE URL BUILD)
    |------------------------------------------------------------
    */
    $allImages = $allImages
        ->filter(fn ($img) => !empty($img['path']))
        ->map(function ($img) use ($baseUrl) {

            $path = ltrim($img['path'], '/');

            return [
                'url' => str_starts_with($path, 'http')
                    ? $path
                    : $baseUrl . '/' . $path,
                'label' => $img['label'] ?? null,
            ];
        })
        ->unique('url')
        ->values();

    $property->display_image = $allImages->first()['url']
        ?? asset('img/default-property.jpg');

    $property->gallery_list = $allImages;

    /*
    |------------------------------------------------------------
    | UNIT MAPPING (UNCHANGED LOGIC, CLEANED NULL SAFETY)
    |------------------------------------------------------------
    */
    $mapUnits = function ($items, $type) use ($property) {

        return collect($items ?? [])->map(function ($u) use ($type, $property) {

            $price = $u->price ?? $u->unit_price ?? 0;

            $status = strtolower($u->status ?? 'vacant');

            $css = match ($status) {
                'vacant', 'available' => 'vacant',
                'booked' => 'booked',
                default => 'occupied'
            };

            $name = match ($type) {
                'house', 'sale' => $u->unit_number ?? 'Unit',
                'hostel' => $u->room_number ?? 'Room',
                'container' => $u->size ?? 'Container',

                'warehouse' => $u->mode === 'space'
                    ? ($u->total_space . ' ' . ($u->measure_unit ?? 'sqft'))
                    : ($u->unit_count . ' Units'),

                'office', 'shop' => !empty($u->size)
                    ? $u->size . ' ' . ($u->measure_unit ?? 'sqft')
                    : ($u->unit_number ?? 'Unit'),

                'land' => $u->size ?? 'Land',
                default => 'Unit',
            };

            $displayType = match ($type) {
                'house', 'sale' => optional($u->type)->name ?? 'House',
                'land' => optional($u->measureUnit)->name ?? 'Land',
                'warehouse' => 'Warehouse',
                'office' => 'Office',
                'shop' => 'Shop',
                'hostel' => 'Hostel Room',
                'container' => 'Container',
                default => ucfirst($type),
            };

            $suffix = match ($type) {
                'hostel' => ' per ' . strtolower($u->duration ?? 'month'),
                'warehouse' => $u->mode === 'space'
                    ? ' per ' . strtolower($u->measure_unit ?? 'sqft')
                    : '',
                default => ''
            };

            return (object)[
                'id' => $u->id,
                'display_name' => $name,
                'display_type' => $displayType,
                'display_price' => number_format($price),
                'price' => $price,
                'status' => $status,
                'css_class' => $css,
                'price_suffix' => $suffix,
                'type' => $type,
                'display_size' => $u->size ?? null,
                'display_remaining' => $u->remaining_space ?? null,
            ];
        });
    };

    /*
    |------------------------------------------------------------
    | ALL UNITS
    |------------------------------------------------------------
    */
    $property->all_units = collect()
        ->concat($mapUnits($property->houseUnits, 'house'))
        ->concat($mapUnits($property->houseOnSaleUnits, 'sale'))
        ->concat($mapUnits($property->landUnits, 'land'))
        ->concat($mapUnits($property->hostelUnits, 'hostel'))
        ->concat($mapUnits($property->containers, 'container'))
        ->concat($mapUnits($property->warehouseUnits, 'warehouse'))
        ->concat($mapUnits($property->officeUnits, 'office'))
        ->concat($mapUnits($property->shopUnits, 'shop'));

    /*
    |------------------------------------------------------------
    | PRICE RANGE
    |------------------------------------------------------------
    */
    $property->min_price = $property->all_units->min('price') ?? 0;
    $property->max_price = $property->all_units->max('price') ?? 0;

    $property->display_price = ($property->min_price > 0)
        ? (
            $property->min_price == $property->max_price
                ? 'Ksh ' . number_format($property->min_price)
                : 'Ksh ' . number_format($property->min_price) . ' - ' . number_format($property->max_price)
        )
        : 'Price on Request';

    /*
    |------------------------------------------------------------
    | LABELS
    |------------------------------------------------------------
    */
    $property->type_label = match ($property->listing_type) {
        'onSale'  => 'For Sale',
        'onLease' => 'For Lease',
        'forRent' => 'For Rent',
        default   => 'Listing',
    };

    $property->location_label = $property->formatted_location;

    /*
    |------------------------------------------------------------
    | UNIT TYPES: one card per type on the Units tab ("2 Bedroom · 3 of 8 vacant")
    |------------------------------------------------------------
    */
    $property->unit_groups = $this->unitGroups($property);

    return view('HomeCity.Properties.viewProperty', compact('listing', 'property'));
}

}
