<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Hub\Marketer;
use App\Models\Hub\BusinessReferral;

class MarketerController extends Controller
{
    /**
     * Show marketer registration form.
     */
    // public function marketer()
    // {
    //     return view('Hub.marketer');
    // }
    public function marketer()
    {
        $customer = auth('customer')->user();

        $marketer = Marketer::where('customer_id', $customer->id)->first();

        return view('Hub.marketer', compact(
            'customer',
            'marketer'
        ));
    }

    public function joinMarketing()
    {
        return view('Hub.joinTeam');
    }

    /**
     * Store marketer information.
     */
    public function storeMarketer(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect()
                ->back()
                ->with('error', 'You must be logged in to register as a marketer.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone_number' => [
                'required',
                'string',
                'max:30',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'gender' => [
                'nullable',
                'in:Male,Female',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'mpesa_number' => [
                'required',
                'string',
                'max:30',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check if customer is already a marketer
        |--------------------------------------------------------------------------
        */

        $existingMarketer = Marketer::where(
            'customer_id',
            $customer->id
        )->first();

        if ($existingMarketer) {
            return redirect()
                ->back()
                ->with('error', 'You are already registered as a marketer.');
        }

        /*
        |--------------------------------------------------------------------------
        | Generate unique referral code
        |--------------------------------------------------------------------------
        */

        do {
            $referralCode = 'M' . strtoupper(Str::random(7));
        } while (
            Marketer::where(
                'referral_code',
                $referralCode
            )->exists()
        );

        /*
        |--------------------------------------------------------------------------
        | Create marketer
        |--------------------------------------------------------------------------
        */

        $marketer = Marketer::create([
            'customer_id' => $customer->id,

            'name' => $request->name,
            'phone' => $request->phone_number,
            'email' => $request->email,

            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,

            'mpesa_number' => $request->mpesa_number,

            /*
             * System generated
             */
            'referral_code' => $referralCode,

            /*
             * Every marketer gets 10%
             */
            'discount_percentage' => 10,

            'status' => 'active',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Generate referral URL
        |--------------------------------------------------------------------------
        |
        | The marketer ID is included only after the marketer has been
        | successfully created because the ID is auto-generated.
        |
        */

        $urlLink = 'https://business.smartmarket.co.ke/account/register?ref='
            . $marketer->referral_code;

        /*
        |--------------------------------------------------------------------------
        | Save referral URL
        |--------------------------------------------------------------------------
        */

        $marketer->update([
            'url_link' => $urlLink,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Return success
        |--------------------------------------------------------------------------
        */
    return redirect()
        ->route('hub.account.marketer.index')
        ->with('success', 'Marketing account created successfully.')
        ->with('referral_code', $marketer->referral_code)
        ->with('url_link', $marketer->url_link);
    }

    public function editMarketer()
    {
        $customerId = auth('customer')->id();

        $marketer = Marketer::where('customer_id', $customerId)
            ->firstOrFail();

        return view('Hub.editMarketer', compact('marketer'));
    }








    /**
     * The marketer's referrals: each business that signed up with their code, its
     * subdivision, plan and subscription payments, and the marketer's commission
     * (config smartmarket.marketer_commission_percent of every paid subscription invoice).
     */
    public function referrals()
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return redirect()->route('login');
        }

        $marketer = Marketer::where('customer_id', $customer->id)->first();
        $rate = (float) config('smartmarket.marketer_commission_percent', 30);

        if (!$marketer) {
            return view('Hub.referrals', [
                'marketer' => null,
                'referrals' => collect(),
                'rate' => $rate,
                'totals' => ['earned' => 0, 'pending' => 0, 'paid' => 0],
                'stats' => $this->referralStats(collect()),
                'show' => 'all',
            ]);
        }

        $all = BusinessReferral::where('marketer_id', $marketer->id)
            ->latest()
            ->get()
            ->each(fn ($referral) => $this->describe($referral, $rate, false));

        $totals = [
            'earned' => $all->sum('commission_earned'),
            'pending' => $all->sum('commission_pending'),
            'paid' => $all->sum('paid_total'),
        ];

        $stats = $this->referralStats($all);

        // table filter: all | week | pending | active
        $show = in_array(request('show'), ['week', 'pending', 'active'], true) ? request('show') : 'all';
        $referrals = match ($show) {
            'week' => $all->filter(fn ($r) => $r->created_at && $r->created_at->gte(now()->startOfWeek()))->values(),
            'pending' => $all->reject(fn ($r) => $r->is_active)->values(),
            'active' => $all->filter(fn ($r) => $r->is_active)->values(),
            default => $all,
        };

        return view('Hub.referrals', compact('referrals', 'marketer', 'rate', 'totals', 'stats', 'show'));
    }

    /**
     * The marketer's referrals with their commission worked out (Hub finance page):
     * commission_earned on paid invoices, commission_pending on unpaid ones.
     */
    public function referralsWithCommission(Marketer $marketer)
    {
        $rate = (float) config('smartmarket.marketer_commission_percent', 30);

        return BusinessReferral::where('marketer_id', $marketer->id)
            ->latest()
            ->get()
            ->each(fn ($referral) => $this->describe($referral, $rate, false));
    }

    /**
     * Referral counts for the Referrals and Finance pages: total, this week (from Monday),
     * active (the business has paid / its subscription is active), pending, and performance
     * = active ÷ total in % (null without referrals).
     */
    public function referralStats($referrals): array
    {
        $total = $referrals->count();
        $active = $referrals->where('is_active', true)->count();

        return [
            'total' => $total,
            'week' => $referrals->filter(fn ($r) => $r->created_at && $r->created_at->gte(now()->startOfWeek()))->count(),
            'active' => $active,
            'pending' => $total - $active,
            'performance' => $total ? (int) round($active / $total * 100) : null,
        ];
    }

    /** One referral: the business, its subscription invoices and the commission on each. */
    public function referral(int $referral)
    {
        $customer = auth('customer')->user();
        $marketer = Marketer::where('customer_id', $customer->id)->firstOrFail();
        $rate = (float) config('smartmarket.marketer_commission_percent', 30);

        $referral = BusinessReferral::where('marketer_id', $marketer->id)->findOrFail($referral);
        $this->describe($referral, $rate, true);

        return view('Hub.referral', compact('referral', 'marketer', 'rate'));
    }

    /**
     * Fill in a referral from the business's own subdivision database: subdivision name,
     * business name, plan, subscription status, paid / unpaid invoices and commission.
     */
    private function describe(BusinessReferral $referral, float $rate, bool $withInvoices): void
    {
        static $subdivisions = null;

        $subdivisions ??= DB::connection('hub')
            ->table('sub_divisions')
            ->where('status', 'active')
            ->get(['name', 'db_connection']);

        $referral->subdivision_name = null;
        $referral->business_name = null;
        $referral->plan = null;
        $referral->subscription_status = null;
        $referral->paid_total = 0.0;
        $referral->unpaid_total = 0.0;
        $referral->commission_earned = 0.0;
        $referral->commission_pending = 0.0;
        $referral->commission_upcoming = 0.0;
        $referral->last_payment_at = null;
        $referral->invoices = collect();

        foreach ($subdivisions as $subdivision) {
            try {
                $db = DB::connection($subdivision->db_connection);

                $business = $db->table('business')
                    ->where('account', $referral->business_account)
                    ->first(['id', 'account', 'name', 'main_branch_account']);

                if (!$business) {
                    continue;
                }

                $referral->subdivision_name = $subdivision->name;
                $referral->business_name = $business->name;

                $subscription = $db->table('business_subscription as bs')
                    ->leftJoin('tariffs as t', 't.id', '=', 'bs.tariff_id')
                    ->where(function ($q) use ($business) {
                        $q->where('bs.business_id', $business->id)
                            ->orWhere('bs.main_branch_account', $business->main_branch_account ?: $business->account);
                    })
                    ->orderByDesc('bs.id')
                    ->first(['bs.id', 'bs.status', 't.name as tariff_name']);

                if ($subscription) {
                    $referral->plan = $subscription->tariff_name;
                    $referral->subscription_status = $subscription->status;

                    // only the first three consecutive months earn commission (and are shown):
                    // month 1 at 30%, months 2 and 3 at 20% (config smartmarket.marketer_commission_months)
                    $rates = array_values(config('smartmarket.marketer_commission_months', [30, 20, 20]));

                    $invoices = $db->table('subscription_invoices as si')
                        ->leftJoin('tariffs as t', 't.id', '=', 'si.tariff_id')
                        ->where('si.business_subscription_id', $subscription->id)
                        ->orderByRaw('COALESCE(si.starts_at, si.created_at) asc')
                        ->orderBy('si.id')
                        ->limit(count($rates))
                        ->get(['si.id', 'si.invoiced_amount', 'si.status', 'si.starts_at', 'si.expires_at', 'si.created_at', 't.name as tariff_name'])
                        ->values()
                        ->map(function ($invoice, $i) use ($rates) {
                            $invoice->month = $i + 1;
                            $invoice->rate = (float) $rates[$i];
                            $invoice->amount = (float) $invoice->invoiced_amount;
                            $invoice->commission = round($invoice->amount * $invoice->rate / 100, 2);
                            return $invoice;
                        });

                    $paid = $invoices->where('status', 'paid');
                    $unpaid = $invoices->where('status', '!=', 'paid');

                    $referral->paid_total = (float) $paid->sum('amount');
                    $referral->unpaid_total = (float) $unpaid->sum('amount');
                    $referral->commission_earned = (float) $paid->sum('commission');
                    $referral->commission_pending = (float) $unpaid->sum('commission');
                    $referral->last_payment_at = optional($paid->last())->created_at;

                    // months of the first three not invoiced yet (months 2 and 3 at 20%), while the
                    // subscription is active: worked out on the latest invoice's amount
                    if ($invoices->isNotEmpty() && $subscription->status === 'active') {
                        $last = (float) $invoices->last()->amount;
                        foreach (array_slice($rates, $invoices->count()) as $r) {
                            $referral->commission_upcoming += round($last * (float) $r / 100, 2);
                        }
                    }

                    if ($withInvoices) {
                        $referral->invoices = $invoices->values();
                    }
                }

                break;
            } catch (\Throwable $e) {
                // a subdivision database that can't be reached: try the next one
                continue;
            }
        }

        // activated: the business has paid, or its subscription / the referral is active
        $referral->is_active = $referral->paid_total > 0
            || $referral->subscription_status === 'active'
            || in_array(strtolower((string) $referral->status), ['active', 'activated', 'approved', 'completed'], true);
    }
}