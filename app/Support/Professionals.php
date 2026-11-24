<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Professionals (customer database, see the 2026_11_19 Customer migration). The same lists are in
 * the admin hub (App\Support\Professionals), so both sides use the same words.
 */
class Professionals
{
    /** key => [label, what it means, tone for partials.status, icon] */
    public const STATUSES = [
        'pending' => ['Waiting for approval', 'Sent in; the team has not decided yet.', 'pending', 'fa-solid fa-hourglass-half'],
        'active' => ['Active', 'Listed on Smart Market and can get bookings.', 'active', 'fa-solid fa-circle-check'],
        'rejected' => ['Not approved', 'The team turned the profile down. They can fix it and send it again.', 'rejected', 'fa-regular fa-circle-xmark'],
        'deactivated' => ['Deactivated', 'Switched off for now: hidden, no bookings. Can be activated again.', 'inactive', 'fa-solid fa-power-off'],
        'suspended' => ['Suspended', 'Paused by the team (e.g. unpaid tariff or complaints): hidden, no bookings.', 'suspended', 'fa-solid fa-pause'],
        'banned' => ['Banned', 'Removed for good: hidden and cannot send the profile again.', 'blocked', 'fa-solid fa-ban'],
    ];

    /** what the team can change a status to, from each status */
    public const MOVES = [
        'pending' => ['active', 'rejected', 'banned'],
        'active' => ['deactivated', 'suspended', 'banned'],
        'rejected' => ['active', 'banned'],
        'deactivated' => ['active', 'suspended', 'banned'],
        'suspended' => ['active', 'deactivated', 'banned'],
        'banned' => ['active'],
    ];

    /** button wording for a move */
    public const ACTIONS = [
        'active' => ['Approve & activate', 'fa-solid fa-check', 'go'],
        'rejected' => ['Reject', 'fa-regular fa-circle-xmark', 'danger'],
        'deactivated' => ['Deactivate', 'fa-solid fa-power-off', 'soft'],
        'suspended' => ['Suspend', 'fa-solid fa-pause', 'warn'],
        'banned' => ['Ban', 'fa-solid fa-ban', 'danger'],
    ];

    public const CATEGORIES = [
        'beauty' => ['Beauty & hair', 'fa-solid fa-spa'],
        'events' => ['Events & photography', 'fa-solid fa-camera'],
        'home' => ['Home repairs & building', 'fa-solid fa-screwdriver-wrench'],
        'cleaning' => ['Cleaning & laundry', 'fa-solid fa-broom'],
        'tech' => ['Tech & phones', 'fa-solid fa-laptop'],
        'fitness' => ['Health & fitness', 'fa-solid fa-dumbbell'],
        'tutoring' => ['Tutoring & lessons', 'fa-solid fa-chalkboard-user'],
        'transport' => ['Transport & moving', 'fa-solid fa-truck-moving'],
        'food' => ['Food & catering', 'fa-solid fa-utensils'],
        'fashion' => ['Fashion & tailoring', 'fa-solid fa-shirt'],
        'farming' => ['Farming & vets', 'fa-solid fa-seedling'],
        'other' => ['Other services', 'fa-solid fa-briefcase'],
    ];

    public const PLATFORMS = [
        'tiktok' => ['TikTok', 'fa-brands fa-tiktok'],
        'instagram' => ['Instagram', 'fa-brands fa-instagram'],
        'facebook' => ['Facebook', 'fa-brands fa-facebook'],
        'youtube' => ['YouTube', 'fa-brands fa-youtube'],
        'x' => ['X (Twitter)', 'fa-brands fa-x-twitter'],
        'linkedin' => ['LinkedIn', 'fa-brands fa-linkedin'],
        'website' => ['Website', 'fa-solid fa-globe'],
    ];

    public const WORKS_FROM = [
        'my_place' => 'At my place',
        'clients_place' => "At the client's place",
        'both' => 'Either',
        'online' => 'Online',
    ];

    public const JOB_STATUSES = [
        'new' => ['New', 'pending'], 'accepted' => ['Accepted', 'confirmed'], 'declined' => ['Declined', 'rejected'],
        'done' => ['Done', 'completed'], 'cancelled' => ['Cancelled', 'cancelled'],
    ];

    public const CYCLES = ['monthly' => ['Monthly', 1], 'quarterly' => ['Every 3 months', 3], 'yearly' => ['Yearly', 12]];

    /** what a customer sees for the professional's own status (Hub → Professional) */
    public const MINE = [
        'pending' => 'We are checking your profile. You will get a notification when it is approved.',
        'active' => 'Your profile is live: customers can find you and book you.',
        'rejected' => 'Your profile was not approved. Fix what the team asked for below and send it again.',
        'deactivated' => 'Your profile is switched off, so customers can\'t see or book you. Contact us to switch it back on.',
        'suspended' => 'Your profile is paused by the team, so customers can\'t see or book you. See the note below.',
        'banned' => 'Your professional account was banned. Contact us if you think this is a mistake.',
    ];

    public static function db()
    {
        return DB::connection('customer');
    }

    public static function ready(): bool
    {
        return (bool) rescue(fn () => Schema::connection('customer')->hasTable('professionals'), false, false);
    }

    public static function mediaUrl(?string $path): ?string
    {
        if (!$path) return null;
        return preg_match('#^https?://#', $path) ? $path : rtrim((string) config('app.media_url'), '/') . '/' . ltrim($path, '/');
    }

    /** the public page, with ?via= so a booking says where it came from */
    public static function publicUrl(string $account, ?string $via = null): string
    {
        return route('pros.show', array_filter(['account' => $account, 'via' => $via]));
    }

    /** hub.sub_divisions id => name */
    public static function subdivisions(): Collection
    {
        return rescue(fn () => DB::connection('hub')->table('sub_divisions')->where('status', 1)->orderBy('name')->pluck('name', 'id'), collect(), false);
    }
}
