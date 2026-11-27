<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Professionals as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Professionals on Smart Market (public): the directory (/pros, filter by kind, county or market,
 * e.g. /pros?market=4 for the professionals related to Glam Room), a professional's page
 * (/pros/{account}, the link they put on TikTok, Instagram …) and booking them.
 * Only active professionals show; their tariff decides who is shown first.
 */
class ProsController extends Controller
{
    private function db()
    {
        return P::db();
    }

    /** active professionals, with featured tariffs first; `photo` is the account's profile photo */
    private function listed()
    {
        return $this->db()->table('professionals as p')
            ->leftJoin('professional_tariffs as t', 't.id', '=', 'p.tariff_id')
            ->leftJoin('customer_accounts as ca', 'ca.id', '=', 'p.customer_id')
            ->where('p.account_status', 'active');
    }

    public function index(Request $request)
    {
        abort_unless(P::ready(), 404);
        $category = array_key_exists($request->query('kind'), P::CATEGORIES) ? $request->query('kind') : '';
        $county = (int) $request->query('county');
        $market = (int) $request->query('market');
        $q = trim((string) $request->query('q', ''));

        $pros = $this->listed()
            ->when($category, fn ($w) => $w->where('p.category', $category))
            ->when($county, fn ($w) => $w->where('p.county_id', $county))
            ->when($market, fn ($w) => $w->whereIn('p.id', $this->db()->table('professional_subdivisions')->where('sub_division_id', $market)->select('professional_id')))
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('p.display_name', 'like', "%$q%")->orWhere('p.profession', 'like', "%$q%")->orWhere('p.area', 'like', "%$q%")))
            ->select('p.id', 'p.account', 'p.display_name', 'p.profession', 'p.category', 'ca.profile_image as photo', 'p.county_id', 'p.area', 'p.rate_from', 'p.rate_unit', 'p.works_from', 't.featured')
            ->selectSub($this->db()->table('professional_jobs')->selectRaw('count(*)')->whereColumn('professional_jobs.professional_id', 'p.id')->where('status', 'done'), 'done_jobs')
            ->orderByDesc('t.featured')->orderByDesc('done_jobs')->orderByDesc('p.id')
            ->paginate(24)->withQueryString();

        $hub = DB::connection('hub');
        $counties = rescue(fn () => $hub->table('physical_counties')->orderBy('name')->pluck('name', 'id'), collect(), false);
        $markets = P::subdivisions();

        return view('Pros.index', compact('pros', 'category', 'county', 'market', 'q', 'counties', 'markets'));
    }

    private function profile(string $account): object
    {
        return $this->listed()->where('p.account', $account)->select('p.*', 't.featured', 'ca.profile_image as photo')->first() ?? abort(404);
    }

    public function show(Request $request, string $account)
    {
        abort_unless(P::ready(), 404);
        $p = $this->profile($account);
        $db = $this->db();
        $hub = DB::connection('hub');
        $links = $db->table('professional_links')->where('professional_id', $p->id)->orderBy('id')->get();
        $services = $db->table('professional_services')->where('professional_id', $p->id)->where('is_active', true)->orderBy('sort')->orderBy('id')->get();
        $markets = P::subdivisions()->only($db->table('professional_subdivisions')->where('professional_id', $p->id)->pluck('sub_division_id')->all());
        $done = $db->table('professional_jobs')->where('professional_id', $p->id)->where('status', 'done')->count();
        $place = collect([$p->area,
            $p->town_id ? rescue(fn () => $hub->table('physical_towns')->where('id', $p->town_id)->value('name'), null, false) : null,
            $p->county_id ? rescue(fn () => $hub->table('physical_counties')->where('id', $p->county_id)->value('name'), null, false) : null])->filter()->implode(', ');
        $via = array_key_exists($request->query('via'), P::PLATFORMS) ? $request->query('via') : null;
        $customer = auth('customer')->user();

        return view('Pros.show', compact('p', 'links', 'services', 'markets', 'done', 'place', 'via', 'customer'));
    }

    public function book(Request $request, string $account)
    {
        abort_unless(P::ready(), 404);
        $p = $this->profile($account);
        $customer = auth('customer')->user();

        $data = $request->validate([
            'service_id' => ['nullable', 'integer', Rule::exists('customer.professional_services', 'id')->where('professional_id', $p->id)->where('is_active', true)],
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{9,16}$/'],
            'wanted_at' => ['required', 'date', 'after:now', 'before:+1 year'],
            'location' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'via' => ['nullable', Rule::in(array_keys(P::PLATFORMS))],
            'website' => ['nullable', 'max:0'],   // left empty by people, filled by bots
        ], [
            'name.required' => 'Enter your name.',
            'phone.required' => 'Enter a phone number the professional can call.',
            'phone.regex' => 'Enter a phone number like 0712 345 678.',
            'wanted_at.required' => 'Pick a day and time.',
            'wanted_at.after' => 'Pick a time that is still to come.',
        ]);

        $service = !empty($data['service_id']) ? $this->db()->table('professional_services')->where('id', $data['service_id'])->first() : null;
        $ref = 'JOB-' . strtoupper(Str::random(6));

        $this->db()->table('professional_jobs')->insert([
            'reference' => $ref, 'professional_id' => $p->id, 'service_id' => $service->id ?? null, 'service_name' => $service->name ?? null,
            'customer_id' => $customer?->id, 'customer_name' => trim($data['name']), 'customer_phone' => preg_replace('/[^0-9+]/', '', $data['phone']),
            'wanted_at' => $data['wanted_at'], 'location' => $data['location'] ?? null, 'note' => $data['note'] ?? null,
            'price' => $service->price ?? null, 'status' => 'new', 'source' => $data['via'] ?? 'smartmarket',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // the professional hears about it in their Hub (bell + Jobs)
        rescue(fn () => $this->db()->table('customer_notifications')->insert([
            'customer_id' => $p->customer_id, 'type' => 'professional_job_new', 'title' => 'New booking from ' . trim($data['name']),
            'body' => collect([$service->name ?? null, \Illuminate\Support\Carbon::parse($data['wanted_at'])->format('D j M, H:i')])->filter()->implode(' · '),
            'url' => route('hub.jobs'), 'icon' => 'fa-solid fa-calendar-check', 'created_at' => now(), 'updated_at' => now(),
        ]), null, false);

        return redirect()->route('pros.show', $p->account)->with('booked', $ref);
    }
}
