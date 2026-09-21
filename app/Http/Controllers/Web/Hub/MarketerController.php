<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use App\Models\Customer\Marketer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MarketerController extends Controller
{
    /**
     * Show marketer registration form.
     */
    // public function marketer()
    // {
    //     return view('hub.marketer');
    // }
    public function marketer()
    {
        $customer = auth('customer')->user();

        $marketer = Marketer::where('customer_id', $customer->id)->first();

        return view('hub.marketer', compact(
            'customer',
            'marketer'
        ));
    }

    public function joinMarketing()
    {
        return view('hub.joinTeam');
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
                'required',
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
            'phone_number' => $request->phone_number,
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
            ->back()
            ->with('success', 'Marketing account created successfully.')
            ->with('referral_code', $marketer->referral_code)
            ->with('url_link', $marketer->url_link);
    }

    public function editMarketer()
    {
        $customerId = auth('customer')->id();

        $marketer = Marketer::where('customer_id', $customerId)
            ->firstOrFail();
            
        return view('hub.editMarketer', compact('marketer'));
    }

}