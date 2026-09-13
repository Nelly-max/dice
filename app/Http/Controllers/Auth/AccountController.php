<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Cart;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\Customer\CustomerAccount; 

use Illuminate\Support\Str;
use Exception;

class AccountController extends Controller
{

    public function showLoginForm(Request $request)
    {
        if (!session()->has('url.intended')) {
            session(['url.intended' => url()->previous()]);
        }

        return view('Auth.register');
    }

    /**
     * Handle an incoming customer registration request.
     */
    public function register(Request $request)
    {
        // Removed the 'unique' database constraints from email and phone_number
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255','unique:customer.customer_accounts,email'],
            'phone_number' => ['required', 'string', 'max:20'],
            'gender'       => ['string', 'in:male,female'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            // Save the customer using our Eloquent model
            $customer = CustomerAccount::create([
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'gender'       => $validated['gender'],
                'password'     => Hash::make($validated['password']),
                'status'       => 'active',
            ]);

            return redirect()->route('login')
                ->with('success', "Account created successfully! Your Account Number is: {$customer->account}");

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Database operation failed: ' . $e->getMessage()]);
        }
    }


    /**
     * Handle an incoming customer authentication attempt.
     */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login_identifier' => ['required', 'string'],
            'password'         => ['required', 'string'],
        ]);

        $customer = CustomerAccount::where('email', $credentials['login_identifier'])
            ->orWhere('account', $credentials['login_identifier'])
            ->first();

        if (!$customer || !Hash::check($credentials['password'], $customer->password)) {
            return back()
                ->withInput($request->only('login_identifier', 'remember'))
                ->withErrors([
                    'login_identifier' => 'The provided credentials do not match our records.',
                ]);
        }

        if ($customer->status !== 'active') {
            return back()
                ->withInput($request->only('login_identifier'))
                ->withErrors([
                    'login_identifier' => 'Your account is inactive.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Capture Guest Session ID
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
        | Move Guest Cart to Customer
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

                $existing->increment(
                    'quantity',
                    $guestItem->quantity
                );

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
        | Prevent Session Fixation
        |--------------------------------------------------------------------------
        */
        $request->session()->regenerate();

        return redirect()->intended(route('login'))
            ->with(
                'success',
                'Welcome back, ' . $customer->name . '!'
            );
    }




    /**
     * Log the customer out of the application session matrix safely.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been successfully logged out.');
    }
}