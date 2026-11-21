<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use App\Models\Hub\Marketer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Hub → Leads (marketers only): businesses a marketer would like to bring onto Smart Market.
 * They land in the same list the admin hub works from (Sales → Leads), tagged with the marketer
 * (customer.business_leads.marketer_id, source "Marketer"); the admin team follows them up.
 * A marketer sees only the leads they added.
 */
class LeadController extends Controller
{
    public const STATUSES = [
        'new' => ['New', 'fa-solid fa-star', 'Just added'],
        'contacted' => ['Contacted', 'fa-regular fa-paper-plane', 'Our team reached out'],
        'interested' => ['Interested', 'fa-regular fa-thumbs-up', 'Wants to join'],
        'onboarded' => ['Onboarded', 'fa-solid fa-circle-check', 'Selling on Smart Market'],
        'not_interested' => ['Not interested', 'fa-regular fa-circle-xmark', 'Did not want to join'],
    ];

    private function db()
    {
        return DB::connection('customer');
    }

    /** the signed-in customer's marketer account, or null */
    private function marketer(): ?Marketer
    {
        $customer = auth('customer')->user();
        return $customer ? Marketer::where('customer_id', $customer->id)->first() : null;
    }

    private function noMarketer()
    {
        return redirect()->route('hub.account.marketer.index')
            ->with('error', 'Only marketers can add leads. Join the marketing team first.');
    }

    public function index(Request $request)
    {
        $marketer = $this->marketer();
        if (!$marketer) return $this->noMarketer();

        $status = array_key_exists($request->query('status'), self::STATUSES) ? $request->query('status') : '';
        $mine = fn () => $this->db()->table('business_leads')->where('marketer_id', $marketer->id);

        $counts = rescue(fn () => $mine()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'), collect(), false);
        $leads = rescue(fn () => $mine()->when($status, fn ($q) => $q->where('status', $status))->orderByDesc('id')->limit(300)->get(), collect(), false);
        $this->decorate($leads);

        return view('Hub.leads', compact('marketer', 'leads', 'counts', 'status'));
    }

    public function create()
    {
        if (!$this->marketer()) return $this->noMarketer();

        $hub = DB::connection('hub');
        $county = (int) old('county_id');
        $town = (int) old('town_id');

        return view('Hub.leadForm', [
            'markets' => rescue(fn () => $hub->table('sub_divisions')->where('status', 1)->orderBy('name')->get(['id', 'name']), collect(), false),
            'counties' => rescue(fn () => $hub->table('physical_counties')->orderBy('name')->get(['id', 'name']), collect(), false),
            // after a failed save, the town / area lists of what was picked
            'towns' => $county ? rescue(fn () => $hub->table('physical_towns')->where('physical_county_id', $county)->orderBy('name')->get(['id', 'name']), collect(), false) : collect(),
            'places' => $town ? rescue(fn () => $hub->table('physical_places')->where('physical_town_id', $town)->orderBy('name')->get(['id', 'name']), collect(), false) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $marketer = $this->marketer();
        if (!$marketer) return $this->noMarketer();

        $phone = ['regex:/^\+?[0-9 ()-]{9,16}$/'];
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => array_merge(['required', 'string'], $phone),
            'phone_2' => array_merge(['nullable', 'string'], $phone),
            'email' => ['nullable', 'email', 'max:255'],
            'sub_division_id' => ['nullable', 'integer', Rule::exists('hub.sub_divisions', 'id')],
            'business_type' => ['nullable', 'string', 'max:100'],
            'county_id' => ['required', 'integer', Rule::exists('hub.physical_counties', 'id')],
            'town_id' => ['nullable', 'integer', Rule::exists('hub.physical_towns', 'id')],
            'place_id' => ['nullable', 'integer', Rule::exists('hub.physical_places', 'id')],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Enter the business name.',
            'phone.required' => 'Enter a phone number for the business.',
            'phone.regex' => 'Enter a phone number like 0712 345 678.',
            'phone_2.regex' => 'Enter a phone number like 0712 345 678.',
            'county_id.required' => 'Pick the county the business is in.',
        ]);

        $clean = fn ($p) => $p === null || $p === '' ? null : preg_replace('/[^0-9+]/', '', $p);
        $last9 = fn ($p) => $p ? substr(preg_replace('/\D/', '', $p), -9) : null;   // 0712… and +254712… are the same line
        $data['phone'] = $clean($data['phone']);
        $data['phone_2'] = $clean($data['phone_2'] ?? null);
        if ($data['phone_2'] && $last9($data['phone_2']) === $last9($data['phone'])) {
            throw ValidationException::withMessages(['phone_2' => 'The second number is the same as the first.']);
        }

        // one lead per business: the same number already on the list (by anyone) is not added again
        $taken = $this->db()->table('business_leads')
            ->where(fn ($q) => $q->where('phone', 'like', '%' . $last9($data['phone']))->orWhere('phone_2', 'like', '%' . $last9($data['phone'])))
            ->first(['id', 'name', 'marketer_id']);
        if ($taken) {
            throw ValidationException::withMessages(['phone' => $taken->marketer_id === $marketer->id
                ? 'You already added this number (' . $taken->name . ').'
                : 'This business is already on our leads list, so it can\'t be added again.']);
        }

        foreach ($data as $k => $v) if (is_string($v)) $data[$k] = trim($v) === '' ? null : trim($v);

        $id = $this->db()->table('business_leads')->insertGetId($data + [
            'status' => 'new', 'source' => 'Marketer', 'marketer_id' => $marketer->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return redirect()->route('hub.leads.show', $id)->with('success', $data['name'] . ' was added to your leads. Our team will follow it up.');
    }

    public function show(int $lead)
    {
        $marketer = $this->marketer();
        if (!$marketer) return $this->noMarketer();

        $row = $this->db()->table('business_leads')->where('id', $lead)->where('marketer_id', $marketer->id)->first() ?? abort(404);
        $this->decorate(collect([$row]));

        return view('Hub.lead', ['lead' => $row]);
    }

    /** market / county / town / area names on lead rows */
    private function decorate(Collection $leads): void
    {
        if ($leads->isEmpty()) return;
        $hub = DB::connection('hub');
        $names = fn (string $table, string $col) => rescue(fn () => $hub->table($table)->whereIn('id', $leads->pluck($col)->filter()->unique()->values())->pluck('name', 'id'), collect(), false);
        $markets = $names('sub_divisions', 'sub_division_id');
        $counties = $names('physical_counties', 'county_id');
        $towns = $names('physical_towns', 'town_id');
        $places = $names('physical_places', 'place_id');

        foreach ($leads as $l) {
            $l->market_name = $markets[$l->sub_division_id] ?? null;
            $l->county_name = $counties[$l->county_id] ?? null;
            $l->town_name = $towns[$l->town_id] ?? null;
            $l->place_name = $places[$l->place_id] ?? null;
            $l->where = collect([$l->place_name, $l->town_name, $l->county_name])->filter()->implode(', ');
        }
    }
}
