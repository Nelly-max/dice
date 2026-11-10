<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use App\Models\Customer\Delivery;
use App\Models\Hub\Marketer;
use App\Models\Hub\Rider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hub "Finance": what a rider and / or marketer has earned and been paid.
 *
 * Rider
 *   earned   delivery fees of the rider's completed deliveries (orders.delivery_fee); Smart Market
 *            keeps config smartmarket.rider_deduction_percent (10%) of it
 *   payable  earned − deduction − paid − payouts on the way
 *   payouts  finance.commission_payouts (payee_type = rider) and finance.financial_records
 *            payout debits (party_type = rider) not already linked to one of those payouts
 *
 * Marketer
 *   earned   commission on the referred businesses' paid subscription invoices
 *            (MarketerController: 30% month 1, 20% months 2 and 3)
 *   pending  commission still to come: unpaid invoices and the months 2–3 (20%) not invoiced yet
 *   payable  earned − paid − payouts on the way
 *   payouts  finance.commission_payouts (payee_type = marketer)
 *
 * Payouts are created and paid from the admin hub (hub-claude); this page only reads them.
 */
class FinanceController extends Controller
{
    private const IN_PROGRESS = ['pending', 'approved', 'processing'];

    public function index(MarketerController $marketers)
    {
        $customer = auth('customer')->user();

        $rider = Rider::where('customer_id', $customer->id)->first();
        $marketer = Marketer::where('customer_id', $customer->id)->first();

        return view('Hub.finance', [
            'rider' => $rider ? $this->rider($rider) : null,
            'marketer' => $marketer ? $this->marketer($marketer, $marketers) : null,
            'tab' => in_array(request('tab'), ['rider', 'marketer'], true) ? request('tab') : null,
        ]);
    }

    /* ------------------------------------------------------------------ */

    private function rider(Rider $rider): array
    {
        $account = (string) $rider->rider_account;

        // completed deliveries and the delivery fee of each order
        try {
            $deliveries = Delivery::query()
                ->where('rider_account', $account)
                ->whereIn('status', ['delivered', 'completed'])
                ->latest('delivered_at')
                ->get(['id', 'delivery_number', 'order_id', 'pickup_name', 'customer_name', 'distance_km', 'status', 'delivered_at', 'completed_at', 'created_at']);

            $fees = $this->deliveryFees($deliveries->pluck('order_id')->unique()->all());

            $deliveries->each(function ($d) use ($fees) {
                $d->fee = (float) ($fees[$d->order_id] ?? 0);
                $d->done_at = $d->delivered_at ?? $d->completed_at ?? $d->created_at;
            });
        } catch (\Throwable $e) {
            report($e);
            $deliveries = collect();
        }

        $payouts = $this->payouts('rider', $rider->id, $account);

        // payout debits in the ledger that no commission payout already stands for
        try {
            $linked = $payouts->pluck('financial_record_id')->filter()->all();
            $records = DB::connection('finance')->table('financial_records')
                ->where('party_type', 'rider')
                ->where('party_account', $account)
                ->where('entry_type', 'payout')
                ->where('direction', 'debit')
                ->whereNotIn('status', ['cancelled', 'reversed'])
                ->when($linked, fn ($q) => $q->whereNotIn('id', $linked))
                ->get();

            $payouts = $payouts->concat($records->map(fn ($r) => (object) [
                'reference' => $r->reference,
                'description' => $r->description ?: 'Payout',
                'amount' => (float) $r->amount,
                'status' => $r->status === 'approved' ? 'paid' : $r->status,
                'receipt' => $r->source_reference,
                'date' => $r->occurred_at ?? $r->created_at,
            ]))->sortByDesc(fn ($p) => (string) $p->date)->values();
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $profile = DB::connection('finance')->table('rider_payment_profiles as p')
                ->leftJoin('payment_methods as m', 'm.id', '=', 'p.payment_method_id')
                ->where('p.rider_account', $account)
                ->first(['p.payment_number', 'p.account_number', 'p.account_name', 'p.status', 'm.name as method']);
        } catch (\Throwable $e) {
            report($e);
            $profile = null;
        }

        $earned = (float) $deliveries->sum('fee');
        $rate = (float) config('smartmarket.rider_deduction_percent', 10);
        $deduction = round($earned * $rate / 100, 2);
        $today = now()->startOfDay();

        return [
            'account' => $account,
            'deliveries' => $deliveries,
            'earned' => $earned,
            'count' => $deliveries->count(),
            'today' => (float) $deliveries->filter(fn ($d) => $d->done_at && \Illuminate\Support\Carbon::parse($d->done_at)->gte($today))->sum('fee'),
            'deductionRate' => $rate,
            'deduction' => $deduction,
            'payouts' => $payouts,
            'profile' => $profile,
            'payTo' => $profile
                ? trim(($profile->method ?? 'M-Pesa') . ' ' . ($profile->payment_number ?? '') . ($profile->account_number ? ' · ' . $profile->account_number : ''))
                : ($rider->phone ?? null),
        ] + $this->totals($earned - $deduction, $payouts);
    }

    private function marketer(Marketer $marketer, MarketerController $marketers): array
    {
        try {
            $referrals = $marketers->referralsWithCommission($marketer);
        } catch (\Throwable $e) {
            report($e);
            $referrals = collect();
        }

        $payouts = $this->payouts('marketer', $marketer->id, (string) $marketer->referral_code);
        $earned = (float) $referrals->sum('commission_earned');

        return [
            'code' => $marketer->referral_code,
            'referrals' => $referrals,
            'stats' => $marketers->referralStats($referrals),
            'commission' => $earned,
            'pending' => (float) $referrals->sum('commission_pending') + (float) $referrals->sum('commission_upcoming'),
            'payouts' => $payouts,
            'payTo' => $marketer->mpesa_number ?: $marketer->phone,
        ] + $this->totals($earned, $payouts);
    }

    /** finance.commission_payouts for a rider or marketer, newest first. */
    private function payouts(string $type, int $id, string $account): Collection
    {
        try {
            return DB::connection('finance')->table('commission_payouts')
                ->where('payee_type', $type)
                ->where(function ($q) use ($id, $account) {
                    $q->where('payee_id', $id);
                    if ($account !== '') $q->orWhere('payee_account', $account);
                })
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($p) => (object) [
                    'reference' => $p->reference,
                    'description' => $this->describePayout($p),
                    'amount' => (float) $p->amount,
                    'status' => $p->status,
                    'receipt' => $p->mpesa_receipt,
                    'date' => $p->paid_at ?? $p->approved_at ?? $p->created_at,
                    'failure' => $p->status === 'failed' ? $p->failure_reason : null,
                    'financial_record_id' => $p->financial_record_id,
                ]);
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }
    }

    private function describePayout(object $p): string
    {
        $what = match ($p->source_type) {
            'business_referral' => 'Referral commission',
            'subscription' => 'Subscription commission',
            'order' => 'Delivery earnings',
            default => 'Payout',
        };

        if ($p->period_start && $p->period_end) {
            $what .= ' · ' . date('j M', strtotime($p->period_start)) . ' – ' . date('j M Y', strtotime($p->period_end));
        } elseif ($p->business_account) {
            $what .= ' · ' . $p->business_account;
        }

        return $what;
    }

    /** paid, onTheWay, payable (what is owed after payouts already made or on the way) */
    private function totals(float $owed, Collection $payouts): array
    {
        $paid = (float) $payouts->where('status', 'paid')->sum('amount');
        $onTheWay = (float) $payouts->whereIn('status', self::IN_PROGRESS)->sum('amount');

        return [
            'paid' => $paid,
            'onTheWay' => $onTheWay,
            'payable' => max(0, round($owed - $paid - $onTheWay, 2)),
        ];
    }

    /** order id => delivery fee (orders.delivery_fee, through order_businesses). */
    private function deliveryFees(array $orderBusinessIds): array
    {
        if (!$orderBusinessIds) return [];

        return DB::connection('customer')->table('order_businesses as ob')
            ->join('orders as o', 'o.id', '=', 'ob.order_id')
            ->whereIn('ob.id', $orderBusinessIds)
            ->pluck('o.delivery_fee', 'ob.id')
            ->all();
    }
}
