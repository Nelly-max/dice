<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use App\Models\Customer\Cart;
use App\Models\Customer\CustomerAccount; 

use Exception;

class AuthController extends Controller
{

    public function showRegistrationForm(Request $request)
    {
        $this->rememberReturnUrl($request);

        return view('Auth.register');
    }

    public function showLoginForm(Request $request)
    {
        $this->rememberReturnUrl($request);

        return view('Auth.login');
    }

    /**
     * Remember the page the customer came from so login sends them back there.
     *
     * Order of preference:
     *  1. ?redirect=/some/page on the login/register link
     *  2. a target set by the auth middleware (e.g. they opened checkout while logged out)
     *  3. the page they were on (Referer), refreshed on every visit so an
     *     abandoned login attempt does not send them somewhere old
     * Auth pages, other sites and file URLs are never used.
     */
    private function rememberReturnUrl(Request $request): void
    {
        $explicit = $this->safeReturnUrl($request->query('redirect'), $request);

        if ($explicit) {
            session(['url.intended' => $explicit, 'url.intended_from_login' => true]);
            return;
        }

        // set by the auth middleware, not by us: keep it
        if (session()->has('url.intended') && !session('url.intended_from_login')) {
            return;
        }

        $previous = $this->safeReturnUrl($request->headers->get('referer'), $request);

        if ($previous) {
            session(['url.intended' => $previous, 'url.intended_from_login' => true]);
        }
    }

    private function safeReturnUrl(?string $url, Request $request): ?string
    {
        if (!is_string($url) || $url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        // same site only
        if (isset($parts['host']) && strcasecmp($parts['host'], $request->getHost()) !== 0) {
            return null;
        }

        $path = '/' . ltrim($parts['path'] ?? '/', '/');

        // never return to auth pages, background endpoints or files
        if (preg_match('#^/(account/(login|register|logout|new-pass|password-reset|update-password)|password/|logout)#i', $path)
            || preg_match('#^/(cart/count|delivery-location|broadcasting|storage|build)(/|$)#i', $path)
            || preg_match('#\.(js|css|map|json|png|jpe?g|gif|svg|webp|ico|woff2?)$#i', $path)) {
            return null;
        }

        return url($path . (isset($parts['query']) ? '?' . $parts['query'] : ''));
    }

    /**
     * Handle an incoming customer registration request.
     */



public function register(Request $request)
{
    $validated = $request->validate([
        'name'         => ['required', 'string', 'max:255'],
        'email'        => ['required', 'string', 'email', 'max:255', 'unique:customer.customer_accounts,email'],
        'phone_number' => ['required', 'string', 'max:20'],
        'gender'       => ['nullable', 'string', 'in:male,female'],
        // optional, but anyone giving a date of birth must be at least 10 years old
        'date_of_birth' => ['nullable', 'date', 'after:1900-01-01', 'before_or_equal:' . now()->subYears(10)->toDateString()],
        'password'     => ['required', 'string', 'min:8', 'confirmed'],
    ], [
        'date_of_birth.before_or_equal' => 'You must be at least 10 years old to register.',
        'date_of_birth.after'           => 'Please enter a valid date of birth.',
        'date_of_birth.date'            => 'Please enter a valid date of birth.',
    ]);

    try {
        $customer = DB::connection('customer')->transaction(function () use ($validated) {
            $connection = DB::connection('customer');
            $now = now();

            // Year code: 2026 = 01, 2027 = 02, etc.
            $yearCode = str_pad($now->year - 2025, 2, '0', STR_PAD_LEFT);

            // Month representation letters.
            $monthCodes = [
                1  => 'S', // January
                2  => 'Q', // February
                3  => 'R', // March
                4  => 'H', // April
                5  => 'T', // May
                6  => 'U', // June
                7  => 'V', // July
                8  => 'W', // August
                9  => 'X', // September
                10 => 'N', // October
                11 => 'Y', // November
                12 => 'P', // December
            ];

            $monthCode = $monthCodes[$now->month];

            // Create the monthly counter if it does not exist.
            $connection->table('account_monthly_counter')->insertOrIgnore([
                'year'          => $now->year,
                'month'         => $now->month,
                'last_sequence' => 0,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // Lock this month's counter row.
            $counter = $connection->table('account_monthly_counter')
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                throw new \RuntimeException('Unable to retrieve monthly account counter.');
            }

            $sequence = $counter->last_sequence + 1;

            if ($sequence > 999) {
                throw new \RuntimeException('Monthly account registration limit reached.');
            }

            // Increment the counter inside the transaction.
            $connection->table('account_monthly_counter')
                ->where('id', $counter->id)
                ->update([
                    'last_sequence' => $sequence,
                    'updated_at'    => $now,
                ]);

            // Format: S001A02
            $account = $monthCode
                . str_pad($sequence, 3, '0', STR_PAD_LEFT)
                . 'A'
                . $yearCode;

            return CustomerAccount::create([
                'account'      => $account,
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'gender'       => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'password'     => Hash::make($validated['password']),
                'status'       => 'active',
            ]);
        });

        return redirect()->route('login')
            ->with('success', "Account created successfully! Your Account Number is: {$customer->account}");

    } catch (\Exception $e) {
        Log::error('Customer registration failed', [
            'error' => $e->getMessage(),
        ]);

        return back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->withErrors([
                'error' => 'Account registration failed. Please try again.',
            ]);
    }
}


    /**
     * Handle an incoming customer authentication attempt.
     */


    public function login(Request $request)
    {
        $validated = $request->validate([
            'login_email' => ['required', 'string', 'max:255'],
            'password'    => ['required', 'string', 'max:1024'],
            'remember'    => ['nullable', 'boolean'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        */

        $loginIdentifier = trim($validated['login_email']);

        $customer = CustomerAccount::query()
            ->where(function ($query) use ($loginIdentifier) {
                $query->where('email', $loginIdentifier)
                    ->orWhere('account', $loginIdentifier);
            })
            ->first();

        $throttleKey = Str::transliterate($loginIdentifier)
            . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'login_email' => [
                    "Too many login attempts. Please try again in {$seconds} seconds.",
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Find Customer
        |--------------------------------------------------------------------------
        */

        $customer = CustomerAccount::query()
            ->where('email', $loginIdentifier)
            ->orWhere('account', $loginIdentifier)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Verify Credentials
        |--------------------------------------------------------------------------
        */

        if (
            !$customer ||
            !Hash::check($validated['password'], $customer->password)
        ) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'login_email' => [
                    'The provided credentials do not match our records.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Account Status
        |--------------------------------------------------------------------------
        */

        if ($customer->status !== 'active') {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'login_email' => [
                    'Your account is inactive.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear Failed Attempts
        |--------------------------------------------------------------------------
        */

        RateLimiter::clear($throttleKey);

        /*
        |--------------------------------------------------------------------------
        | Preserve Guest Session ID for Cart Migration
        |--------------------------------------------------------------------------
        */

        $sessionId = $request->session()->getId();

        /*
        |--------------------------------------------------------------------------
        | Authenticate Customer
        |--------------------------------------------------------------------------
        */

        Auth::guard('customer')->login(
            $customer,
            $request->boolean('remember')
        );

        /*
        |--------------------------------------------------------------------------
        | Regenerate Session
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | Merge Guest Cart into Customer Cart
        |--------------------------------------------------------------------------
        */

        $guestItems = Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();

        foreach ($guestItems as $guestItem) {
            $existing = Cart::where('user_id', $customer->id)
                ->where('stockable_id', $guestItem->stockable_id)
                ->where('stockable_type', $guestItem->stockable_type)
                ->where('business_account', $guestItem->business_account)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
                $guestItem->delete();
            } else {
                $guestItem->update([
                    'user_id'    => $customer->id,
                    'session_id' => null,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        // back to the page they came from (see rememberReturnUrl), otherwise home
        $request->session()->forget('url.intended_from_login');

        return redirect()
            ->intended(route('home'))
            ->with('success', 'Welcome back, ' . $customer->name . '!');
    }




    /**
     * Log the customer out of the application session matrix safely.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // back to the Smart Market home page; the toast confirms it
        return redirect()->route('home')
            ->with('success', 'You have been logged out.');
    }

    
    public function showOtp(Request $request)
    {

        return view('Auth.resetPassKey');
    }



    public function requestPasswordReset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->email));

        $customer = CustomerAccount::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'active')
            ->first();

        if (!$customer) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'No active customer account was found with this email address.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Rate Limit OTP Requests
        |--------------------------------------------------------------------------
        */

        $key = 'password-reset-send:' . sha1($email);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Too many requests. Please try again later.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Six-Character OTP
        |--------------------------------------------------------------------------
        */

        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $otp = '';

        for ($i = 0; $i < 6; $i++) {
            $otp .= $characters[random_int(0, strlen($characters) - 1)];
        }

        /*
        |--------------------------------------------------------------------------
        | Store Hashed OTP in Cache for Two Minutes
        |--------------------------------------------------------------------------
        */

        $cacheKey = 'password-reset-otp:' . sha1($email);

        $expiresAt = now()->addMinutes(2);

        Cache::put(
            $cacheKey,
            Hash::make($otp),
            $expiresAt
        );

        session([
            'password_reset_user_id' => $customer->id,
            'password_reset_email' => $customer->email,
            'password_reset_expires' => $expiresAt->toIso8601String(),
        ]);

        session()->forget('password_reset_verified');

        /*
        |--------------------------------------------------------------------------
        | Send Verification Email
        |--------------------------------------------------------------------------
        */

        try {
            Mail::raw(
                "Your Smart Market password reset verification code is: {$otp}\n\n"
                . "This code expires in 2 minutes.\n\n"
                . "If you did not request a password reset, please ignore this email.\n\n"
                . "Smart Market Support",
                function ($message) use ($customer) {
                    $message
                        ->from('support@smartmarket.co.ke', 'Smart Market Support')
                        ->to($customer->email)
                        ->subject('Smart Market Password Reset Verification Code');
                }
            );

            RateLimiter::hit($key, 60);
        } catch (\Throwable $e) {
            report($e);

            Cache::forget($cacheKey);

            session()->forget([
                'password_reset_user_id',
                'password_reset_email',
                'password_reset_expires',
                'password_reset_verified',
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Unable to send the verification code. Please try again later.',
                ]);
        }

        return redirect()
            ->route('otp.page')
            ->with(
                'success',
                'A verification code has been sent to your email. It expires in 2 minutes.'
            );
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => [
                'required',
                'string',
                'size:6',
                'regex:/^[A-HJ-NP-Z2-9]{6}$/',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check Reset Session
        |--------------------------------------------------------------------------
        */

        $email = session('password_reset_email');
        $expires = session('password_reset_expires');

        if (!$email || !$expires) {
            return redirect()
                ->route('account.resetpassword')
                ->withErrors([
                    'email' => 'Your password reset session has expired. Please try again.',
                ]);
        }

        $cacheKey = 'password-reset-otp:' . sha1($email);
        $hashedOtp = Cache::get($cacheKey);

        if (!$hashedOtp) {
            return redirect()
                ->route('account.resetpassword')
                ->withErrors([
                    'email' => 'Your verification code has expired. Please request a new one.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Expiration (2 Minutes)
        |--------------------------------------------------------------------------
        */

        if (now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($expires))) {
            Cache::forget($cacheKey);

            session()->forget([
                'password_reset_email',
                'password_reset_expires',
                'password_reset_verified',
            ]);

            return redirect()
                ->route('account.resetpassword')
                ->withErrors([
                    'email' => 'The verification code has expired. Please request a new one.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Code
        |--------------------------------------------------------------------------
        */

        if (!Hash::check(
            strtoupper($request->input('otp')),
            $hashedOtp
        )) {
            return back()->withErrors([
                'otp' => 'Invalid verification code.',
            ])->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Code Verified
        |--------------------------------------------------------------------------
        */

        session([
            'password_reset_verified' => true,
        ]);

        Cache::forget($cacheKey);

        session()->forget([
            'password_reset_expires',
        ]);

        return redirect()->route('account.newpassword');
    }



    public function resendOtp(Request $request)
    {
        $email = session('password_reset_email');
        $expires = session('password_reset_expires');

        if (!$email || !$expires) {
            return redirect()
                ->route('account.resetpassword')
                ->withErrors([
                    'email' => 'Your password reset session has expired. Please start again.',
                ]);
        }

        // Only allow another code after the current code expires.
        if (now()->lt(\Carbon\Carbon::parse($expires))) {
            return back()->withErrors([
                'otp' => 'Your current code has not expired yet. Please wait for the timer to finish.',
            ]);
        }

        // Confirm the customer account still exists and is active.
        $customer = CustomerAccount::whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->where('status', 'active')
            ->first();

        if (!$customer) {
            session()->forget([
                'password_reset_email',
                'password_reset_expires',
                'password_reset_verified',
            ]);

            Cache::forget('password-reset-otp:' . sha1($email));

            return redirect()
                ->route('account.resetpassword')
                ->withErrors([
                    'email' => 'The account could not be found. Please try again.',
                ]);
        }

        // Rate limit sending and resending.
        $key = 'password-reset-send:' . sha1($email);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'otp' => 'Too many requests. Please try again later.',
            ]);
        }

        // Generate a new 6-character alphanumeric code.
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $otp = '';

        for ($i = 0; $i < 6; $i++) {
            $otp .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $cacheKey = 'password-reset-otp:' . sha1($email);

        // Replace the old code and start a fresh 2-minute expiry.
        Cache::put(
            $cacheKey,
            Hash::make($otp),
            now()->addMinutes(2)
        );

        session([
            'password_reset_expires' => now()->addMinutes(2)->toIso8601String(),
        ]);

        // A new code must be verified before password reset.
        session()->forget('password_reset_verified');

        try {
            Mail::raw(
                "Your Smart Market password reset verification code is: {$otp}\n\n"
                . "This code will expire in 2 minutes.\n\n"
                . "If you did not request a password reset, please ignore this email.\n\n"
                . "smartmarket_ke.",
                function ($message) use ($customer) {
                    $message
                        ->from('support@smartmarket.co.ke', 'Smart Market Support')
                        ->to($customer->email)
                        ->subject('Smart Market Password Reset Verification Code');
                }
            );

            RateLimiter::hit($key, 60);
        } catch (\Throwable $e) {
            report($e);

            // Do not leave an unsent code active.
            Cache::forget($cacheKey);

            session()->forget([
                'password_reset_expires',
                'password_reset_verified',
            ]);

            return back()->withErrors([
                'otp' => 'We could not send a new code. Please try again.',
            ]);
        }

        return back()->with(
            'success',
            'A new verification code has been sent to your email. It expires in 2 minutes.'
        );
    }



    public function updatePassword(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Confirm OTP Was Verified
        |--------------------------------------------------------------------------
        */

        if (
            !session('password_reset_verified') ||
            !session('password_reset_user_id') ||
            !session('password_reset_email')
        ) {
            return redirect()
                ->route('account.resetpassword')
                ->withErrors([
                    'email' => 'Please verify your reset code first.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Password
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Customer Account
        |--------------------------------------------------------------------------
        */

        $email = strtolower(trim(session('password_reset_email')));

        $customer = CustomerAccount::query()
            ->whereKey(session('password_reset_user_id'))
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'active')
            ->first();

        if (!$customer) {
            session()->forget([
                'password_reset_user_id',
                'password_reset_email',
                'password_reset_expires',
                'password_reset_verified',
            ]);

            Cache::forget('password-reset-otp:' . sha1($email));

            return redirect()
                ->route('account.resetpassword')
                ->withErrors([
                    'email' => 'Your customer account could not be found. Please start again.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Customer Password
        |--------------------------------------------------------------------------
        */

        $customer->password = Hash::make($request->password);
        $customer->save();

        /*
        |--------------------------------------------------------------------------
        | Clear Password Reset Data
        |--------------------------------------------------------------------------
        */

        Cache::forget('password-reset-otp:' . sha1($email));

        session()->forget([
            'password_reset_user_id',
            'password_reset_email',
            'password_reset_otp',
            'password_reset_expires',
            'password_reset_verified',
        ]);

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your password has been updated successfully. You can now log in.'
            );
    }

}