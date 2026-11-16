<?php

namespace App\Models\Water;

use App\Services\Water\WaterCatalog;
use Illuminate\Database\Eloquent\Model;

/**
 * A water shop's price list row: one container (e.g. 20 L hard bottle) with
 * - refill_price: the buyer's own empty bottle refilled (purified water refill)
 * - sale_price:   a new, filled bottle (bottled water)
 * Truck-sized containers (WaterContainer::isTruck) are clean water delivered by truck to estates.
 *
 * The cart stores what the buyer chose as the stockable type (config/stockables.php):
 *   water_refill  => WaterRefill  (refill_price)
 *   water_bottled => WaterBottled (sale_price)
 *   water_truck   => WaterTruck   (refill_price, else sale_price)
 * all three reading this table. There is no stock column: water is made to order.
 */
class Inventory extends Model
{
    /** Cart stockable types of the Water subdivision. */
    public const TYPES = ['water_refill', 'water_bottled', 'water_truck'];

    protected $connection = 'water';

    protected $table = 'inventory';

    protected $guarded = [];

    protected $casts = [
        'sale_price' => 'float',
        'refill_price' => 'float',
    ];

    /** Which option this class sells: refill | bottled | truck (null = the plain row). */
    public const OPTION = null;

    public function container()
    {
        return $this->belongsTo(WaterContainer::class, 'water_container_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_account', 'account');
    }

    /** Shops this buyer may see: WaterCatalog rules (subscribed + e-commerce, open, cart shop or radius). */
    public function scopeSellable($query)
    {
        return $query->whereIn('business_account', app(WaterCatalog::class)->visibleAccounts() ?: ['']);
    }

    /* ---------------- display ---------------- */

    /** container id => ['image' => url|null, 'brand' => name|null] (one query per page, see preloadPictures) */
    protected static array $pictures = [];

    /** Load the first company picture of each container in one query. */
    public static function preloadPictures(iterable $containerIds): void
    {
        $ids = collect($containerIds)->filter()->map(fn ($id) => (int) $id)->unique()
            ->reject(fn ($id) => array_key_exists($id, static::$pictures))->values();

        if ($ids->isEmpty()) return;

        try {
            $images = WaterCompanyImage::query()
                ->with('company:id,name')
                ->whereIn('water_container_id', $ids)
                ->orderBy('id')
                ->get()
                ->groupBy('water_container_id');
        } catch (\Throwable $e) {
            report($e);
            $images = collect();
        }

        foreach ($ids as $id) {
            $img = $images->get($id)?->first();
            static::$pictures[$id] = [
                'image' => $img ? static::mediaUrl($img->image_path) : null,
                'brand' => $img?->company?->name,
            ];
        }
    }

    /** First company picture of this container: ['image' => url|null, 'brand' => name|null]. */
    public function picture(): array
    {
        static::preloadPictures([$this->water_container_id]);

        return static::$pictures[(int) $this->water_container_id] ?? ['image' => null, 'brand' => null];
    }

    public static function mediaUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (preg_match('#^https?://#i', $path)) return $path;

        return rtrim((string) config('app.media_url'), '/') . '/' . ltrim($path, '/');
    }

    public function sizeLabel(): string
    {
        return $this->container?->sizeLabel() ?? '';
    }

    public function isTruck(): bool
    {
        return (bool) $this->container?->isTruck();
    }

    /** Price for an option (refill | bottled | truck). */
    public function priceFor(string $option): ?float
    {
        $p = match ($option) {
            'refill' => $this->refill_price,
            'bottled' => $this->sale_price,
            'truck' => $this->refill_price ?: $this->sale_price,
            default => null,
        };

        return $p !== null && $p > 0 ? (float) $p : null;
    }

    /** "Keringet 20 L refill", "Clean water truck · 10,000 L"... */
    public function nameFor(string $option): string
    {
        $size = $this->sizeLabel();
        $brand = $this->picture()['brand'];

        return trim(match ($option) {
            'truck' => 'Clean water truck · ' . $size,
            'bottled' => ($brand ?: 'Bottled water') . ' ' . $size . ($brand ? ' bottled water' : ''),
            default => ($brand ?: 'Purified water') . ' ' . $size . ' refill',
        });
    }

    /* ---------------- cart (config/stockables.php) ---------------- */

    public static function resolveById($id)
    {
        return static::query()->with(['business', 'container'])->find($id);
    }

    public function getBusinessAccount()
    {
        return $this->business_account;
    }

    public function getBusinessName()
    {
        return $this->business->name ?? 'Water shop';
    }

    public function getSubdivisionCode()
    {
        return 'water';
    }

    public function getCartDisplayName()
    {
        return $this->nameFor(static::OPTION ?? 'refill');
    }

    public function getCartPrice()
    {
        return $this->priceFor(static::OPTION ?? 'refill') ?? 0;
    }

    public function getCartImage($quantity = 1)
    {
        return $this->picture()['image'] ?? asset('img/placeholder.png');
    }

    /** Made to order: no stock count. Unavailable when this option has no price. */
    public function availableStock(): int
    {
        return $this->getCartPrice() > 0 ? (int) config('water.max_per_item', 99) : 0;
    }

    public function isAvailableForCart()
    {
        return $this->availableStock() > 0;
    }

    public function getCurrentDiscount()
    {
        return 0;
    }
}
