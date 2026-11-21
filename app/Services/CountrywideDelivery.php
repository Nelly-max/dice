<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Countrywide (parcel) delivery at checkout. Set up in the hub (Products → Countrywide and each
 * store's Delivery card), stored in product_variables:
 *
 *   countrywide_markets         per sub-division: enabled + local_radius_km (road distance riders cover)
 *   parcel_zones                county_ids (hub physical_counties) + days_min/days_max
 *   parcel_rates                zone ↔ zone: price (first item) + extra_item (each further item)
 *   countrywide_shops           shops that send parcels
 *   countrywide_product_blocks  products of such a shop that must not go as a parcel
 *
 * Up to the radius (by road) a rider delivers at the distance bands, as before. Further than that,
 * a market with countrywide on quotes a parcel; a market with it off keeps rider delivery at any distance.
 */
class CountrywideDelivery
{
    private ?bool $ready = null;

    private function pv()
    {
        return DB::connection('product_variables');
    }

    public function ready(): bool
    {
        return $this->ready ??= (bool) rescue(fn () => Schema::connection('product_variables')->hasTable('countrywide_markets'), false, false);
    }

    /** the market's setting when countrywide is on, else null */
    public function market(?int $subdivisionId): ?object
    {
        if (!$subdivisionId || !$this->ready()) return null;
        $row = rescue(fn () => $this->pv()->table('countrywide_markets')->where('sub_division_id', $subdivisionId)->first(), null, false);
        return $row && $row->enabled ? $row : null;
    }

    /** this distance (road km) is beyond the riders of a countrywide market */
    public function beyondRiders(?object $market, float $km): bool
    {
        return $market !== null && $km > (float) $market->local_radius_km;
    }

    /**
     * A parcel quote for one shop's lines.
     *  ok:     ['ok' => true, 'fee', 'zone_from', 'zone_to', 'days_min', 'days_max', 'eta']
     *  not ok: ['ok' => false, 'reason' => shop_not_countrywide | products_not_countrywide | county_unknown
     *            | zone_missing | no_parcel_rate, 'products' => [names] (for products_not_countrywide)]
     *
     * @param Collection $lines the shop's cart lines (with stockable loaded)
     */
    public function quote(array $shipment, object $business, array $customer, Collection $lines): array
    {
        $sub = (int) $shipment['subdivision_id'];
        $account = (string) $shipment['business_account'];

        if (!$this->pv()->table('countrywide_shops')->where('sub_division_id', $sub)->where('business_account', $account)->exists()) {
            return ['ok' => false, 'reason' => 'shop_not_countrywide'];
        }

        // products the hub kept local; a line whose product can't be told is let through
        $blocked = $this->pv()->table('countrywide_product_blocks')->where('sub_division_id', $sub)->where('business_account', $account)
            ->pluck('product_id')->map(fn ($id) => (int) $id)->flip();
        $stuck = $lines->filter(fn ($l) => ($pid = $this->productId($l)) !== null && $blocked->has($pid));
        if ($stuck->isNotEmpty()) {
            return ['ok' => false, 'reason' => 'products_not_countrywide', 'products' => $stuck->map(fn ($l) => $l->product_name)->filter()->unique()->values()->all(),
                'cart_ids' => $stuck->pluck('id')->all()];
        }

        $fromCounty = $this->shopCounty($shipment['db_connection'], $business);
        $toCounty = $this->customerCounty($customer);
        if (!$fromCounty || !$toCounty) {
            return ['ok' => false, 'reason' => 'county_unknown'];
        }

        $zones = $this->zones();
        $from = $zones->first(fn ($z) => in_array($fromCounty, $z->county_ids, true));
        $to = $zones->first(fn ($z) => in_array($toCounty, $z->county_ids, true));
        if (!$from || !$to) {
            return ['ok' => false, 'reason' => 'zone_missing'];
        }

        $rate = $this->pv()->table('parcel_rates')
            ->where(fn ($q) => $q->where(['from_zone_id' => $from->id, 'to_zone_id' => $to->id])->orWhere(fn ($q) => $q->where(['from_zone_id' => $to->id, 'to_zone_id' => $from->id])))
            ->first();
        if (!$rate) {
            return ['ok' => false, 'reason' => 'no_parcel_rate'];
        }

        $items = max(1, (int) $lines->sum('quantity'));
        $fee = (float) $rate->price + ($items - 1) * (float) $rate->extra_item;
        $min = max((int) $from->days_min, (int) $to->days_min);
        $max = max((int) $from->days_max, (int) $to->days_max, $min);

        return [
            'ok' => true,
            'fee' => round($fee, 2),
            'zone_from' => $from->name,
            'zone_to' => $to->name,
            'days_min' => $min,
            'days_max' => $max,
            'eta' => $min === $max ? $max . ' ' . ($max === 1 ? 'day' : 'days') : "{$min}–{$max} days",
        ];
    }

    private function zones(): Collection
    {
        return rescue(fn () => $this->pv()->table('parcel_zones')->orderBy('sort')->get()->map(function ($z) {
            $z->county_ids = array_values(array_map('intval', json_decode((string) $z->county_ids, true) ?: []));
            return $z;
        }), collect(), false);
    }

    /** the product behind a cart line (retail_inventory.product_id / product_items.product_id) */
    private function productId($line): ?int
    {
        $s = $line->stockable;
        if (!$s) return null;
        $id = $s->product_id ?? null;
        return $id ? (int) $id : null;
    }

    /** hub physical_counties id of the shop: business.county_id, else from its map pin */
    private function shopCounty(string $connection, object $business): ?int
    {
        if (!empty($business->county_id)) return (int) $business->county_id;

        $id = rescue(fn () => DB::connection($connection)->table('business')->where('account', $business->account)->value('county_id'), null, false);
        if ($id) return (int) $id;

        return $this->countyId($this->geocodeCounty((float) $business->latitude, (float) $business->longitude));
    }

    /** hub physical_counties id of the delivery pin: the county the map gave, else Google's */
    private function customerCounty(array $customer): ?int
    {
        return $this->countyId($customer['county'] ?? null)
            ?? $this->countyId($this->geocodeCounty((float) $customer['latitude'], (float) $customer['longitude']));
    }

    /** "Nairobi County", "nairobi" … → physical_counties id */
    private function countyId(?string $name): ?int
    {
        $key = $this->norm($name);
        if ($key === '') return null;

        $all = Cache::remember('physical_counties:norm', now()->addHour(), fn () => rescue(
            fn () => DB::connection('hub')->table('physical_counties')->get(['id', 'name'])->mapWithKeys(fn ($c) => [$this->norm($c->name) => (int) $c->id])->all(), [], false));

        return $all[$key] ?? null;
    }

    private function norm(?string $name): string
    {
        $n = strtolower(trim((string) $name));
        $n = preg_replace('/\b(county|city county|province)\b/', '', $n);
        return preg_replace('/[^a-z]/', '', $n);
    }

    /** county name of a point from Google's Geocoding API (administrative_area_level_1), cached 30 days */
    private function geocodeCounty(float $lat, float $lng): ?string
    {
        if (!$lat && !$lng) return null;
        $key = 'geo_county:' . round($lat, 3) . ',' . round($lng, 3);

        if (($hit = Cache::get($key)) !== null) return $hit;

        // only a found county is cached, so a Google outage isn't remembered for a month
        try {
            $res = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "$lat,$lng",
                'result_type' => 'administrative_area_level_1',
                'key' => config('services.google_maps.server_key') ?: config('services.google_maps.api_key'),
            ]);
            foreach ($res->json('results.0.address_components') ?? [] as $c) {
                if (in_array('administrative_area_level_1', $c['types'] ?? [], true)) {
                    Cache::put($key, $c['long_name'], now()->addDays(30));
                    return $c['long_name'];
                }
            }
            Log::warning('Geocoding county failed', ['status' => $res->json('status'), 'message' => $res->json('error_message')]);
        } catch (\Throwable $e) {
            Log::warning('Geocoding unreachable', ['message' => $e->getMessage()]);
        }
        return null;
    }
}
