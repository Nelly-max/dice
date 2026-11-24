<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use App\Models\Customer\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Customer account dashboard.
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $rider = \App\Models\Hub\Rider::where('customer_id', $customer->id)->first();
        $marketer = \App\Models\Hub\Marketer::where('customer_id', $customer->id)->first();
        $professional = \App\Models\Hub\Professional::mine();

        $wallets = [
            'rider' => $rider ? $this->disbursed('rider', $rider->id, (string) $rider->rider_account) : null,
            'marketer' => $marketer ? $this->disbursed('marketer', $marketer->id, (string) $marketer->referral_code) : null,
        ];

        return view('Hub.account', compact('customer', 'rider', 'marketer', 'professional', 'wallets'));
    }

    /**
     * Money approved for a rider or marketer (finance.commission_payouts): everything approved,
     * being sent or paid. "paid" is what reached their M-Pesa, "onTheWay" is approved but not
     * sent yet. Pending (not yet checked), failed and cancelled payouts are not counted.
     */
    private function disbursed(string $type, int $id, string $account): array
    {
        $out = ['approved' => 0.0, 'paid' => 0.0, 'onTheWay' => 0.0, 'lastPaidAt' => null];

        try {
            $rows = DB::connection('finance')->table('commission_payouts')
                ->where('payee_type', $type)
                ->where(function ($q) use ($id, $account) {
                    $q->where('payee_id', $id);
                    if ($account !== '') $q->orWhere('payee_account', $account);
                })
                ->whereIn('status', ['approved', 'processing', 'paid'])
                ->get(['amount', 'status', 'paid_at']);
        } catch (\Throwable $e) {
            report($e);
            return $out;
        }

        foreach ($rows as $r) {
            $amount = (float) $r->amount;
            $out['approved'] += $amount;
            if ($r->status === 'paid') {
                $out['paid'] += $amount;
                if ($r->paid_at && (!$out['lastPaidAt'] || $r->paid_at > $out['lastPaidAt'])) $out['lastPaidAt'] = $r->paid_at;
            } else {
                $out['onTheWay'] += $amount;
            }
        }

        return $out;
    }








public function updateAccount(Request $request)
{
    $authenticatedCustomer = Auth::guard('customer')->user();

    if (!$authenticatedCustomer) {
        abort(403);
    }

    $validated = $request->validate([
        'name' => 'required|string|max:50|regex:/^[A-Za-z0-9 ]+$/',
        'username' => 'nullable|string|max:15|regex:/^[A-Za-z0-9 ]+$/',
        'phone_number' => 'required|string|max:20',
        'email' => 'required|email|max:255',
        'gender' => 'nullable|in:male,female',
        'date_of_birth' => [
            'nullable',
            'date',
            'before_or_equal:' . now()->subYears(18)->toDateString(),
        ],
        // the profile photo, picked on the same page (optional)
        'profile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ], [
        'profile.image' => 'The profile photo must be an image.',
        'profile.mimes' => 'The profile photo must be a JPG, PNG or WebP.',
        'profile.max' => 'The profile photo can be at most 5 MB.',
    ]);

    $customer = CustomerAccount::find($authenticatedCustomer->id);

    if (!$customer) {
        abort(404, 'Customer not found.');
    }

    $customer->name = $validated['name'];
    $customer->username = $validated['username'] ?? null;
    $customer->phone_number = $validated['phone_number'];
    $customer->email = $validated['email'];
    $customer->gender = $validated['gender'] ?? null;
    $customer->date_of_birth = $validated['date_of_birth'] ?? null;

    if ($request->hasFile('profile')) {
        $customer->profile_image = $this->storeProfilePhoto($request->file('profile'), $customer);
    }

    $customer->save();

    return back()->with('success', 'Profile updated successfully.');
}

/**
 * The photo on the account header: tap it, pick a picture, it is saved straight away.
 * Route: POST /hub/account/photo (hub.account.photo)
 */
public function updatePhoto(Request $request)
{
    $customer = CustomerAccount::find(Auth::guard('customer')->id());
    abort_unless($customer, 403);

    $request->validate([
        'profile' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ], [
        'profile.required' => 'Pick a photo first.',
        'profile.image' => 'The profile photo must be an image.',
        'profile.mimes' => 'The profile photo must be a JPG, PNG or WebP.',
        'profile.max' => 'The profile photo can be at most 5 MB.',
    ]);

    $customer->profile_image = $this->storeProfilePhoto($request->file('profile'), $customer);
    $customer->save();

    return back()->with('success', 'Your profile photo has been updated.');
}

/**
 * Save a new profile photo under MEDIA_ROOT/img/Customer/Profiles/{account}/ and remove the
 * customer's previous one from there (never the shared default picture).
 * Returns the path stored in customer_accounts.profile_image.
 */
private function storeProfilePhoto(\Illuminate\Http\UploadedFile $file, CustomerAccount $customer): string
{
    $account = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $customer->account) ?: 'customer-' . $customer->id;
    $relativeDir = "img/Customer/Profiles/{$account}";
    $dir = rtrim(config('app.media_root'), '/\\') . '/' . $relativeDir;
    \Illuminate\Support\Facades\File::ensureDirectoryExists($dir);

    $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
    $filename = 'profile_' . now()->format('YmdHis') . '.' . $extension;
    $file->move($dir, $filename);

    // the old photo, only when it is this customer's own file
    $old = (string) $customer->getOriginal('profile_image');
    if ($old !== '' && str_starts_with(ltrim($old, '/'), $relativeDir . '/')) {
        \Illuminate\Support\Facades\File::delete(rtrim(config('app.media_root'), '/\\') . '/' . ltrim($old, '/'));
    }

    return "{$relativeDir}/{$filename}";
}


}
