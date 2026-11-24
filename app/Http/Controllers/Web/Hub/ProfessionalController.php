<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use App\Models\Hub\Professional;
use App\Support\Professionals as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Hub → My account → Professional: create the profile (what you do, where, services, social media,
 * related markets, tariff), see whether the team approved it, share the booking link; and
 * Hub → Jobs: the bookings customers make, to accept, decline or mark done.
 * The admin hub approves, activates, deactivates, suspends or bans (Sales → Team → Professionals).
 */
class ProfessionalController extends Controller
{
    private function db()
    {
        return P::db();
    }

    private function notReady()
    {
        return redirect()->route('hub.account.index')->with('error', 'Professional profiles are not open yet. Please try again later.');
    }

    /** status page, or what being a professional means when there is no profile yet */
    public function index()
    {
        if (!P::ready()) return $this->notReady();
        $pro = Professional::mine();
        $tariffs = $this->tariffs();

        if (!$pro) {
            return view('Hub.professional', ['pro' => null, 'tariffs' => $tariffs]);
        }

        $db = $this->db();
        $links = $db->table('professional_links')->where('professional_id', $pro->id)->orderBy('id')->get();
        $services = $db->table('professional_services')->where('professional_id', $pro->id)->orderBy('sort')->orderBy('id')->get();
        $markets = P::subdivisions()->only($db->table('professional_subdivisions')->where('professional_id', $pro->id)->pluck('sub_division_id')->all());
        $jobCounts = $db->table('professional_jobs')->where('professional_id', $pro->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $tariff = $pro->tariff_id ? $db->table('professional_tariffs')->where('id', $pro->tariff_id)->first() : null;
        $paidUntil = $db->table('professional_subscriptions')->where('professional_id', $pro->id)->where('status', 'active')->where('ends_at', '>', now())->max('ends_at');

        return view('Hub.professional', compact('pro', 'tariffs', 'links', 'services', 'markets', 'jobCounts', 'tariff', 'paidUntil'));
    }

    private function tariffs()
    {
        return rescue(fn () => $this->db()->table('professional_tariffs')->where('is_active', true)->orderBy('amount')->get(), collect(), false);
    }

    /** create (no profile yet) or edit */
    public function form()
    {
        if (!P::ready()) return $this->notReady();
        $pro = Professional::mine();
        if ($pro && $pro->account_status === 'banned') {
            return redirect()->route('hub.account.professional.index')->with('error', 'This account was banned, so the profile can\'t be changed.');
        }

        $db = $this->db();
        $hub = DB::connection('hub');
        $customer = auth('customer')->user();
        $county = (int) old('county_id', $pro->county_id ?? 0);

        return view('Hub.professionalForm', [
            'pro' => $pro,
            'customer' => $customer,
            'tariffs' => $this->tariffs(),
            'markets' => P::subdivisions(),
            'counties' => rescue(fn () => $hub->table('physical_counties')->orderBy('name')->get(['id', 'name']), collect(), false),
            'towns' => $county ? rescue(fn () => $hub->table('physical_towns')->where('physical_county_id', $county)->orderBy('name')->get(['id', 'name']), collect(), false) : collect(),
            'links' => $pro ? $db->table('professional_links')->where('professional_id', $pro->id)->pluck('url', 'platform') : collect(),
            'services' => $pro ? $db->table('professional_services')->where('professional_id', $pro->id)->orderBy('sort')->orderBy('id')->get() : collect(),
            'picked' => $pro ? $db->table('professional_subdivisions')->where('professional_id', $pro->id)->pluck('sub_division_id')->map(fn ($id) => (int) $id)->all() : [],
        ]);
    }

    public function save(Request $request)
    {
        if (!P::ready()) return $this->notReady();
        $customer = auth('customer')->user();
        $pro = Professional::mine();
        if ($pro && $pro->account_status === 'banned') abort(403);

        $phone = ['regex:/^\+?[0-9 ()-]{9,16}$/'];
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'profession' => ['required', 'string', 'max:80'],
            'category' => ['required', Rule::in(array_keys(P::CATEGORIES))],
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => array_merge(['required', 'string'], $phone),
            'whatsapp' => array_merge(['nullable', 'string'], $phone),
            'email' => ['nullable', 'email', 'max:255'],
            'county_id' => ['required', 'integer', Rule::exists('hub.physical_counties', 'id')],
            'town_id' => ['nullable', 'integer', Rule::exists('hub.physical_towns', 'id')],
            'area' => ['nullable', 'string', 'max:120'],
            'works_from' => ['required', Rule::in(array_keys(P::WORKS_FROM))],
            'rate_from' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'rate_unit' => ['nullable', Rule::in(['job', 'hour', 'day', 'session'])],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'string', 'max:500'],
            'markets' => ['nullable', 'array', 'max:8'],
            'markets.*' => ['integer'],
            'services' => ['nullable', 'array', 'max:30'],
            'services.*.name' => ['nullable', 'string', 'max:120'],
            'services.*.description' => ['nullable', 'string', 'max:500'],
            'services.*.price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'services.*.duration' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'tariff_id' => ['required', 'integer', Rule::exists('customer.professional_tariffs', 'id')->where('is_active', true)],
        ], [
            'display_name.required' => 'Enter the name customers will see.',
            'profession.required' => 'Say what you do, e.g. Make-up artist.',
            'category.required' => 'Pick the kind of service.',
            'county_id.required' => 'Pick the county you work in.',
            'tariff_id.required' => 'Pick a tariff.',
            'phone.regex' => 'Enter a phone number like 0712 345 678.',
            'whatsapp.regex' => 'Enter a WhatsApp number like 0712 345 678.',
            'photo.max' => 'The photo can be up to 3 MB.',
        ]);

        // social links: only the known platforms, as full https addresses
        $links = [];
        foreach ((array) ($data['links'] ?? []) as $platform => $url) {
            $url = trim((string) $url);
            if ($url === '' || !isset(P::PLATFORMS[$platform])) continue;
            if (!preg_match('#^https?://#i', $url)) {
                // "@handle" for TikTok / Instagram / X becomes the profile address
                $handle = ltrim($url, '@');
                $url = match ($platform) {
                    'tiktok' => 'https://www.tiktok.com/@' . $handle,
                    'instagram' => 'https://www.instagram.com/' . $handle,
                    'x' => 'https://x.com/' . $handle,
                    default => 'https://' . $url,
                };
            }
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw ValidationException::withMessages(['links.' . $platform => 'The ' . P::PLATFORMS[$platform][0] . ' link doesn\'t look right.']);
            }
            $links[$platform] = $url;
        }

        $services = collect($data['services'] ?? [])->filter(fn ($s) => trim((string) ($s['name'] ?? '')) !== '')->values();
        $tariff = $this->db()->table('professional_tariffs')->where('id', $data['tariff_id'])->first();
        if ($tariff->max_services && $services->count() > $tariff->max_services) {
            throw ValidationException::withMessages(['services' => "The {$tariff->name} tariff allows up to {$tariff->max_services} services. Remove some or pick a bigger tariff."]);
        }

        $markets = array_values(array_intersect(array_map('intval', $data['markets'] ?? []), P::subdivisions()->keys()->map(fn ($k) => (int) $k)->all()));
        $clean = fn ($p) => $p === null || $p === '' ? null : preg_replace('/[^0-9+]/', '', $p);

        $row = [
            'display_name' => trim($data['display_name']), 'profession' => trim($data['profession']), 'category' => $data['category'],
            'bio' => $data['bio'] ?? null, 'phone' => $clean($data['phone']), 'whatsapp' => $clean($data['whatsapp'] ?? null), 'email' => $data['email'] ?? null,
            'county_id' => $data['county_id'], 'town_id' => $data['town_id'] ?? null, 'area' => $data['area'] ?? null,
            'works_from' => $data['works_from'], 'rate_from' => $data['rate_from'] ?? null, 'rate_unit' => $data['rate_unit'] ?? null,
            'years_experience' => $data['years_experience'] ?? null, 'tariff_id' => $tariff->id, 'updated_at' => now(),
        ];
        // a turned-down profile goes back to the team when it is changed
        if ($pro && $pro->account_status === 'rejected') {
            $row['account_status'] = 'pending';
            $row['status_changed_at'] = now();
        }

        $id = DB::connection('customer')->transaction(function () use ($pro, $row, $customer, $links, $services, $markets) {
            $db = $this->db();
            if ($pro) {
                $db->table('professionals')->where('id', $pro->id)->update($row);
                $id = $pro->id;
            } else {
                $id = $db->table('professionals')->insertGetId($row + [
                    'customer_id' => $customer->id, 'account' => $this->nextAccount(), 'account_status' => 'pending',
                    'status_changed_at' => now(), 'created_at' => now(),
                ]);
                // the first tariff period waits for payment
                $t = $db->table('professional_tariffs')->where('id', $row['tariff_id'])->first();
                $db->table('professional_subscriptions')->insert(['professional_id' => $id, 'tariff_id' => $t->id, 'amount' => $t->amount,
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }

            $db->table('professional_links')->where('professional_id', $id)->delete();
            foreach ($links as $platform => $url) {
                $db->table('professional_links')->insert(['professional_id' => $id, 'platform' => $platform, 'url' => $url, 'created_at' => now(), 'updated_at' => now()]);
            }

            $db->table('professional_subdivisions')->where('professional_id', $id)->delete();
            foreach ($markets as $sub) {
                $db->table('professional_subdivisions')->insert(['professional_id' => $id, 'sub_division_id' => $sub, 'created_at' => now(), 'updated_at' => now()]);
            }

            // services: replace the list; old ids that are still on a job keep the job's own name
            $db->table('professional_services')->where('professional_id', $id)->delete();
            foreach ($services as $i => $s) {
                $db->table('professional_services')->insert([
                    'professional_id' => $id, 'name' => trim($s['name']), 'description' => $s['description'] ?? null,
                    'price' => isset($s['price']) && $s['price'] !== '' ? (float) $s['price'] : null,
                    'duration_minutes' => isset($s['duration']) && $s['duration'] !== '' ? (int) $s['duration'] : null,
                    'is_active' => true, 'sort' => $i + 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $id;
        });

        if ($request->hasFile('photo')) {
            $this->savePhoto($request->file('photo'), $id);
        }

        $message = !$pro ? 'Your professional profile was sent. Our team will check it and let you know.'
            : (($row['account_status'] ?? null) === 'pending' ? 'Saved and sent back to our team to check.' : 'Your profile was saved.');
        return redirect()->route('hub.account.professional.index')->with('success', $message);
    }

    private function nextAccount(): string
    {
        $n = (int) $this->db()->table('professionals')->where('account', 'like', 'PRO%')->pluck('account')->map(fn ($a) => (int) substr($a, 3))->max();
        return 'PRO' . str_pad((string) ($n + 1), 4, '0', STR_PAD_LEFT);
    }

    /** MEDIA_ROOT/img/Professionals/{id}/, path kept in professionals.photo */
    private function savePhoto(\Illuminate\Http\UploadedFile $file, int $id): void
    {
        $rel = 'img/Professionals/' . $id;
        $dir = rtrim((string) config('app.media_root'), '/\\') . '/' . $rel;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $name = 'photo_' . now()->format('YmdHis') . '.' . strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($dir, $name);
        $old = $this->db()->table('professionals')->where('id', $id)->value('photo');
        $this->db()->table('professionals')->where('id', $id)->update(['photo' => $rel . '/' . $name, 'updated_at' => now()]);
        if ($old) \Illuminate\Support\Facades\File::delete(rtrim((string) config('app.media_root'), '/\\') . '/' . ltrim($old, '/'));
    }

    /* ------------------------------------------------------------------ */
    /*  Jobs                                                               */
    /* ------------------------------------------------------------------ */

    private function myPro(): ?Professional
    {
        return P::ready() ? Professional::mine() : null;
    }

    public function jobs(Request $request)
    {
        $pro = $this->myPro();
        if (!$pro) return redirect()->route('hub.account.professional.index');

        $status = array_key_exists($request->query('status'), P::JOB_STATUSES) ? $request->query('status') : '';
        $mine = fn () => $this->db()->table('professional_jobs')->where('professional_id', $pro->id);
        $counts = $mine()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $jobs = $mine()->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("case when status = 'new' then 0 when status = 'accepted' then 1 else 2 end")
            ->orderByDesc('id')->limit(300)->get();

        return view('Hub.jobs', compact('pro', 'jobs', 'counts', 'status'));
    }

    private function findJob(Professional $pro, int $id): object
    {
        return $this->db()->table('professional_jobs')->where('id', $id)->where('professional_id', $pro->id)->first() ?? abort(404);
    }

    public function job(int $id)
    {
        $pro = $this->myPro();
        if (!$pro) return redirect()->route('hub.account.professional.index');
        $job = $this->findJob($pro, $id);
        return view('Hub.job', compact('pro', 'job'));
    }

    /** accept / decline / done / cancel, with a message the customer sees */
    public function updateJob(Request $request, int $id)
    {
        $pro = $this->myPro();
        if (!$pro) return redirect()->route('hub.account.professional.index');
        $job = $this->findJob($pro, $id);

        $next = ['new' => ['accepted', 'declined'], 'accepted' => ['done', 'cancelled']][$job->status] ?? [];
        $data = $request->validate([
            'status' => ['required', Rule::in($next)],
            'reply' => [Rule::requiredIf(in_array($request->input('status'), ['declined', 'cancelled'], true)), 'nullable', 'string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ], ['reply.required' => 'Tell the customer why.', 'status.in' => 'That step isn\'t possible for this job any more.']);

        $upd = ['status' => $data['status'], 'updated_at' => now()];
        if (!empty($data['reply'])) $upd['reply'] = $data['reply'];
        if (isset($data['price']) && $data['price'] !== '') $upd['price'] = (float) $data['price'];
        if (in_array($data['status'], ['accepted', 'declined'], true)) $upd['responded_at'] = now();
        if ($data['status'] === 'done') $upd['completed_at'] = now();
        $this->db()->table('professional_jobs')->where('id', $id)->update($upd);

        // the customer hears back in their Hub when they booked while signed in
        if ($job->customer_id) {
            $titles = ['accepted' => 'accepted your booking', 'declined' => 'can\'t take your booking', 'done' => 'marked your booking done', 'cancelled' => 'cancelled your booking'];
            rescue(fn () => $this->db()->table('customer_notifications')->insert([
                'customer_id' => $job->customer_id, 'type' => 'professional_job_' . $data['status'],
                'title' => $pro->display_name . ' ' . $titles[$data['status']], 'body' => $data['reply'] ?? ($job->service_name ?: null),
                'url' => route('pros.show', $pro->account), 'icon' => 'fa-solid fa-briefcase', 'created_at' => now(), 'updated_at' => now(),
            ]), null, false);
        }

        return back()->with('success', 'Job ' . $job->reference . ' is now ' . strtolower(P::JOB_STATUSES[$data['status']][0]) . '.');
    }
}
