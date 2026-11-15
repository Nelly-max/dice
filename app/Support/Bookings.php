<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Airbnb stays and Home City viewings (customer DB: bookings, booking_events; the migration
 * is in the admin hub, database/migrations/Customer/2026_11_13_120000_create_bookings_tables.php).
 * The hub and the business app carry the same states in their own App\Support\Bookings.
 */
class Bookings
{
    /** key => [label, icon, colour] */
    public const STATUSES = [
        'pending' => ['Waiting for the host', 'fa-regular fa-clock', '#f59e0b'],
        'confirmed' => ['Confirmed', 'fa-solid fa-circle-check', '#2563eb'],
        'completed' => ['Completed', 'fa-solid fa-flag-checkered', '#16a34a'],
        'declined' => ['Not accepted', 'fa-regular fa-circle-xmark', '#e11d48'],
        'cancelled' => ['Cancelled', 'fa-solid fa-ban', '#94a3b8'],
        'no_show' => ['Missed', 'fa-regular fa-eye-slash', '#a16207'],
    ];

    /** what holds a place (dates overlapping these count against the units free) */
    public const HOLDING = ['pending', 'confirmed'];

    public const PAYMENT = ['unpaid' => 'Not paid', 'partial' => 'Part paid', 'paid' => 'Paid', 'refunded' => 'Refunded'];

    public static function db()
    {
        return DB::connection('customer');
    }

    public static function ready(): bool
    {
        try { return self::db()->getSchemaBuilder()->hasTable('bookings'); } catch (\Throwable) { return false; }
    }

    public static function reference(): string
    {
        do {
            $ref = 'BK-' . Str::upper(Str::random(6));
            $ref = strtr($ref, ['0' => '7', 'O' => 'Q', 'I' => 'J', 'L' => 'M', '1' => '9']);   // nothing that reads two ways
        } while (self::db()->table('bookings')->where('reference', $ref)->exists());
        return $ref;
    }

    /** stays at this place + unit type that overlap [in, out) and still hold a unit */
    public static function overlapping(int $propertyId, string $unitKey, string $in, string $out, ?int $ignoreId = null): int
    {
        return self::db()->table('bookings')
            ->where('subdivision', 'airbnb')->where('property_id', $propertyId)->where('unit_key', $unitKey)
            ->whereIn('status', self::HOLDING)
            ->where('check_in', '<', $out)->where('check_out', '>', $in)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->count();
    }

    public static function when(object $b): string
    {
        if ($b->kind === 'viewing') {
            return $b->viewing_at ? Carbon::parse($b->viewing_at)->format('D j M Y, g:i A') : 'Date to agree';
        }
        if (!$b->check_in) return '—';
        $in = Carbon::parse($b->check_in);
        $out = $b->check_out ? Carbon::parse($b->check_out) : null;
        $range = $out
            ? ($in->isSameMonth($out) ? $in->format('D j') . ' – ' . $out->format('D j M Y') : $in->format('D j M') . ' – ' . $out->format('D j M Y'))
            : $in->format('D j M Y');
        return $range . ($b->nights ? ' · ' . $b->nights . ' ' . ($b->nights == 1 ? 'night' : 'nights') : '');
    }

    /** the customer may cancel until the stay / viewing starts */
    public static function cancellable(object $b): bool
    {
        if (!in_array($b->status, self::HOLDING, true)) return false;
        $start = $b->kind === 'viewing' ? $b->viewing_at : $b->check_in;
        return !$start || Carbon::parse($start)->endOfDay()->isFuture();
    }

    public static function log(int $bookingId, string $action, string $byType, $byId = null, ?string $byName = null, ?string $note = null): void
    {
        rescue(fn () => self::db()->table('booking_events')->insert([
            'booking_id' => $bookingId, 'action' => $action, 'by_type' => $byType, 'by_id' => $byId !== null ? (string) $byId : null,
            'by_name' => $byName, 'note' => $note, 'created_at' => now(), 'updated_at' => now(),
        ]), null, false);
    }

    /** 07… / +254… / 254… → 2547… (for comparing numbers) */
    public static function phoneKey(?string $phone): string
    {
        return substr(preg_replace('/\D/', '', (string) $phone), -9);
    }
}
