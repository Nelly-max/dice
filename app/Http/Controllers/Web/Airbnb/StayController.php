<?php

namespace App\Http\Controllers\Web\Airbnb;

use App\Http\Controllers\Controller;
use App\Services\DeliveryLocationService;
use App\Support\Distance;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Airbnb (short stays).
 *
 * Reads the `airbnb` database the business app writes to: listings → houses → house_units
 * (grouped into one stay per unit TYPE per house, never one per unit), photos per house +
 * unit type, location, amenities. Only listings of businesses with an ACTIVE subscription
 * whose tariff has the listing feature (tariffs.listing = 1) are shown; priority_listing
 * tariffs come first and are marked "Featured".
 *
 * If that database cannot be reached the sample stays in config/airbnb.php are shown with
 * a "sample" notice. Every stay is shown; a radius only applies once the visitor picks one.
 */
class StayController extends Controller
{
    public function index(Request $request, DeliveryLocationService $locations)
    {
        $here = $locations->get($request);
        [$all, $sample] = $this->stays($here);

        $radius = $here ? Distance::radiusFromRequest($request->query('radius')) : null;

        $counts = ['any' => $all->count()];
        foreach (Distance::RADII as $m) {
            $counts[$m] = $all->filter(fn ($s) => $s->distance_km !== null && $s->distance_km <= $m / 1000)->count();
        }

        $stays = $all
            ->when($radius, fn ($c) => $c->filter(fn ($s) => $s->distance_km !== null && $s->distance_km <= $radius / 1000))
            ->when($here, fn ($c) => $c->sortBy(fn ($s) => $s->distance_km ?? INF))
            ->values();

        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));
        $types = config('airbnb.types', []);

        $results = $stays
            ->when(isset($types[$type]), fn ($c) => $c->where('type', $type))
            ->when($q !== '', fn ($c) => $this->search($c, $q))
            ->values();

        // live suggestions for the header search (resources/js/hmsearch.js)
        if ($request->wantsJson()) {
            return response()->json($this->suggest($stays, $q));
        }

        return view('Airbnb.home', [
            'stays' => $stays,
            'results' => $results,
            'filtering' => isset($types[$type]) || $q !== '',
            'type' => $type,
            'q' => $q,
            'types' => $types,
            'sample' => $sample,
            'radiusBar' => [
                'hasLocation' => (bool) $here,
                'radius' => $radius,
                'counts' => $counts,
                'noun' => 'stay',
            ],
            'totalStays' => $all->count(),
        ]);
    }

    public function show(Request $request, DeliveryLocationService $locations, string $id)
    {
        [$all, $sample] = $this->stays($locations->get($request));
        $stay = $all->first(fn ($s) => (string) $s->id === $id);

        abort_unless($stay, 404);

        $similar = $all->where('property_id', '!=', $stay->property_id)->where('type', $stay->type)->values();
        $nearby = $all->where('property_id', '!=', $stay->property_id)
            ->sortBy(fn ($s) => Distance::km($stay->latitude, $stay->longitude, $s->latitude, $s->longitude) ?? INF)
            ->take(8)->values();

        return view('Airbnb.stay', compact('stay', 'similar', 'nearby', 'sample'));
    }

    /* ------------------------------------------------------------------ */

    private function search(Collection $stays, string $q): Collection
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($q)));
        $types = config('airbnb.types', []);

        return $stays->filter(function ($s) use ($words, $types) {
            $text = mb_strtolower(implode(' ', [$s->title, $s->unit_name, $s->area, $types[$s->type][0] ?? '', $s->house_type ?? '']));
            return collect($words)->every(fn ($w) => str_contains($text, $w));
        });
    }

    /** Header search suggestions in the shape hmsearch.js expects. */
    private function suggest(Collection $stays, string $q): array
    {
        if (mb_strlen(trim($q)) < 2) return ['products' => [], 'categories' => [], 'shops' => []];

        $types = collect(config('airbnb.types', []))
            ->filter(fn ($t) => str_contains(mb_strtolower($t[0]), mb_strtolower($q)))
            ->map(fn ($t, $key) => ['label' => $t[0], 'url' => route('airbnb.index', ['type' => $key])])
            ->values();

        return [
            'categories' => $types,
            'shops' => [],
            'products' => $this->search($stays, $q)->take(8)->map(fn ($s) => [
                'url' => route('airbnb.stay', $s->id),
                'image' => $s->image,
                'name' => $s->title,
                'variant' => trim(($s->unit_name ?? '') . ' · per night', ' ·'),
                'shop' => $s->area,
                'km' => $s->distance_km !== null ? round($s->distance_km, 1) : null,
                'price' => $s->price,
            ])->values(),
        ];
    }

    /**
     * @return array{0: Collection, 1: bool}  [stays, true when they are the config samples]
     */
    private function stays(?array $here): array
    {
        try {
            $stays = Cache::remember('airbnb.stays', 120, fn () => $this->fromDatabase());
            $sample = false;
        } catch (\Throwable $e) {
            report($e);
            $stays = $this->fromConfig();
            $sample = true;
        }

        $stays = collect($stays)->map(function ($s) use ($here) {
            $s = clone $s;
            $s->distance_km = $here ? Distance::km($here['latitude'], $here['longitude'], $s->latitude, $s->longitude) : null;
            return $s;
        });

        // the other unit types at the same house, for the stay page
        $stays = $stays->map(function ($stay) use ($stays) {
            $stay->siblings = $stays->where('property_id', $stay->property_id)->where('id', '!=', $stay->id)->values();
            return $stay;
        });

        return [$stays->values(), $sample];
    }

    /** The tile key (config('airbnb.types')) for a unit type name. */
    private static function typeKey(string $name, ?string $houseType = null): string
    {
        $n = mb_strtolower($name . ' ' . $houseType);

        return match (true) {
            str_contains($n, 'studio') => 'studio',
            (bool) preg_match('/villa|cottage|maisonette|bungalow|mansion|townhouse/', $n) => 'villa',
            (bool) preg_match('/\b(1|one)\s*-?\s*bed/', $n) => 'one-bed',
            (bool) preg_match('/\b([2-9]|two|three|four|five)\s*-?\s*bed/', $n) => 'two-bed',
            default => 'room',
        };
    }

    private static function bedroomsFrom(string $name): int
    {
        $n = mb_strtolower($name);
        if (preg_match('/(\d+)\s*-?\s*bed/', $n, $m)) return (int) $m[1];
        foreach (['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5] as $word => $num) {
            if (str_contains($n, $word . ' bed')) return $num;
        }
        return 0;
    }

    private static function isVacant(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['', 'vacant', 'available', 'open'], true);
    }

    /** Stays from the airbnb database: one per unit type per listed house. */
    private function fromDatabase(): array
    {
        $db = DB::connection('airbnb');

        // businesses with an active subscription whose tariff includes listing
        $accounts = $db->table('business as b')
            ->join('business_subscription as bs', function ($join) {
                $join->on(function ($on) {
                    $on->on('bs.main_branch_account', '=', 'b.main_branch_account')
                        ->orOn('bs.business_id', '=', 'b.id');
                })->where('bs.status', 'active');
            })
            ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
            ->where('t.listing', 1)
            ->groupBy('b.account', 'b.name', 'b.phone')
            ->selectRaw('b.account, b.name, b.phone, MAX(COALESCE(t.priority_listing, 0)) as priority')
            ->get()
            ->keyBy('account');

        if ($accounts->isEmpty()) return [];

        $listings = $db->table('listings as l')
            ->join('houses as h', 'h.id', '=', 'l.house_id')
            ->leftJoin('house_types as ht', 'ht.id', '=', 'h.house_type_id')
            ->leftJoin('house_location as hl', 'hl.house_id', '=', 'h.id')
            ->where('l.status', 'active')
            ->whereIn('l.lister_id', $accounts->keys()->all())
            ->select('l.id as listing_id', 'l.lister_id', 'l.description as listing_description',
                'h.id as house_id', 'h.title', 'h.description', 'ht.name as house_type',
                'hl.latitude', 'hl.longitude', 'hl.county_id', 'hl.town_id', 'hl.place_id', 'hl.street')
            ->get()
            ->unique('house_id');

        if ($listings->isEmpty()) return [];

        $houseIds = $listings->pluck('house_id')->all();

        $units = $db->table('house_units as u')
            ->leftJoin('house_unit_types as ut', 'ut.id', '=', 'u.house_unit_type_id')
            ->whereIn('u.house_id', $houseIds)
            ->select('u.id', 'u.house_id', 'u.house_unit_type_id', 'u.unit_number', 'u.price', 'u.status', 'ut.name as type_name')
            ->get()
            ->groupBy('house_id');

        $images = $db->table('house_unit_type_images')
            ->whereIn('house_id', $houseIds)
            ->when(true, function ($q) use ($db) {
                // soft deletes, when the column exists
                try { if ($db->getSchemaBuilder()->hasColumn('house_unit_type_images', 'deleted_at')) $q->whereNull('deleted_at'); } catch (\Throwable $e) {}
            })
            ->orderBy('id')
            ->get(['house_id', 'house_unit_type_id', 'image_path', 'label'])
            ->groupBy('house_id');

        $amenities = collect();
        try {
            $amenities = $db->table('house_amenity as ha')
                ->join('amenities as a', 'a.id', '=', 'ha.amenity_id')
                ->whereIn('ha.house_id', $houseIds)
                ->get(['ha.house_id', 'a.name'])
                ->groupBy('house_id');
        } catch (\Throwable $e) { /* no amenities table yet */ }

        // place names from the hub (physical_counties / physical_towns / physical_places)
        $names = function (string $table, string $column) use ($listings) {
            $ids = $listings->pluck($column)->filter()->unique()->all();
            if (!$ids) return collect();
            try { return DB::connection('hub')->table($table)->whereIn('id', $ids)->pluck('name', 'id'); }
            catch (\Throwable $e) { return collect(); }
        };
        $counties = $names('physical_counties', 'county_id');
        $towns = $names('physical_towns', 'town_id');
        $places = $names('physical_places', 'place_id');

        $media = rtrim((string) env('MEDIA_URL', config('app.media_url')), '/');
        $url = fn (?string $path) => !$path ? null : (str_starts_with($path, 'http') ? $path : $media . '/' . ltrim($path, '/'));

        $stays = [];
        foreach ($listings as $l) {
            $business = $accounts[$l->lister_id] ?? null;
            $houseImages = collect($images[$l->house_id] ?? []);
            $area = collect([$places[$l->place_id] ?? null, $towns[$l->town_id] ?? null, $counties[$l->county_id] ?? null])->filter()->implode(', ');

            foreach (collect($units[$l->house_id] ?? [])->groupBy('house_unit_type_id') as $typeId => $group) {
                $typeName = $group->first()->type_name ?: 'Room';
                $prices = $group->pluck('price')->map(fn ($p) => (float) $p)->filter(fn ($p) => $p > 0);
                $vacant = $group->filter(fn ($u) => self::isVacant($u->status));
                $typeImages = $houseImages->where('house_unit_type_id', $typeId)->pluck('image_path');
                $gallery = $typeImages->concat($houseImages->pluck('image_path'))->filter()->unique()->map($url)->values()->all();

                $stays[] = (object) [
                    'id'          => $l->house_id . '-' . $typeId,
                    'property_id' => $l->house_id,
                    'listing_id'  => $l->listing_id,
                    'type'        => self::typeKey($typeName, $l->house_type),
                    'house_type'  => $l->house_type,
                    'title'       => $l->title ?: 'Stay',
                    'unit_name'   => Str::ucfirst($typeName),
                    'area'        => $area ?: ($l->street ?: ''),
                    'latitude'    => is_numeric($l->latitude) ? (float) $l->latitude : null,
                    'longitude'   => is_numeric($l->longitude) ? (float) $l->longitude : null,
                    'price'       => $prices->min() ?? 0,
                    'price_max'   => $prices->max() ?? 0,
                    'guests'      => null,
                    'bedrooms'    => self::bedroomsFrom($typeName),
                    'beds'        => null,
                    'baths'       => null,
                    'units_total' => $group->count(),
                    'units_free'  => $vacant->count(),
                    'vacant_units'=> $vacant->pluck('unit_number')->filter()->values()->all(),
                    'rating'      => null,
                    'reviews'     => 0,
                    'host'        => $business->name ?? 'Host',
                    'host_phone'  => $business->phone ?? null,
                    'superhost'   => false,
                    'featured'    => (bool) ($business->priority ?? 0),
                    'amenities'   => collect($amenities[$l->house_id] ?? [])->pluck('name')->unique()->values()->all(),
                    'description' => $l->listing_description ?: ($l->description ?: ''),
                    'images'      => $gallery,
                    'image'       => $gallery[0] ?? asset('img/default-property.jpg'),
                ];
            }
        }

        // featured (priority listing) first
        return collect($stays)->sortByDesc('featured')->values()->all();
    }

    /** The sample stays in config/airbnb.php (properties → unit types). */
    private function fromConfig(): array
    {
        $properties = config('airbnb.properties');

        if (!$properties) {
            // old format: every stay is its own property with a single unit
            $properties = array_map(fn (array $s) => array_merge($s, [
                'units' => [array_merge($s, ['name' => $s['title'], 'count' => 1, 'available' => 1])],
            ]), config('airbnb.stays', []));
        }

        $url = fn ($img) => str_starts_with($img, 'http') ? $img : asset($img);

        return collect($properties)->flatMap(function (array $p) use ($url) {
            return collect($p['units'] ?? [])->map(function (array $u) use ($p, $url) {
                $images = array_map($url, $u['images'] ?? $p['images'] ?? []);

                return (object) [
                    'id'          => (string) $u['id'],
                    'property_id' => $p['id'] ?? null,
                    'listing_id'  => null,
                    'type'        => $u['type'] ?? 'room',
                    'house_type'  => null,
                    'title'       => $p['title'],
                    'unit_name'   => $u['name'] ?? null,
                    'area'        => $p['area'] ?? '',
                    'latitude'    => $p['latitude'] ?? null,
                    'longitude'   => $p['longitude'] ?? null,
                    'price'       => $u['price'] ?? 0,
                    'price_max'   => $u['price'] ?? 0,
                    'guests'      => $u['guests'] ?? null,
                    'bedrooms'    => $u['bedrooms'] ?? 0,
                    'beds'        => $u['beds'] ?? null,
                    'baths'       => $u['baths'] ?? null,
                    'units_total' => (int) ($u['count'] ?? 1),
                    'units_free'  => (int) ($u['available'] ?? $u['count'] ?? 1),
                    'vacant_units'=> [],
                    'rating'      => $p['rating'] ?? null,
                    'reviews'     => $p['reviews'] ?? 0,
                    'host'        => $p['host'] ?? 'Host',
                    'host_phone'  => null,
                    'superhost'   => !empty($p['superhost']),
                    'featured'    => false,
                    'amenities'   => $u['amenities'] ?? $p['amenities'] ?? [],
                    'description' => $u['description'] ?? $p['description'] ?? '',
                    'images'      => $images,
                    'image'       => $images[0] ?? asset('img/default-property.jpg'),
                ];
            });
        })->values()->all();
    }
}
