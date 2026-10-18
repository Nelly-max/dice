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

        return view('Hub.account', compact('customer'));
    }







public function updateAccount(Request $request)
{
    $authenticatedCustomer = Auth::guard('customer')->user();

    if (!$authenticatedCustomer) {
        abort(403);
    }

    $validated = $request->validate([
        'name' => 'required|string|max:50|regex:/^[A-Za-z0-9 ]+$/',
        'username' => 'required|string|max:15|regex:/^[A-Za-z0-9 ]+$/',
        'phone_number' => 'required|string|max:20',
        'email' => 'required|email|max:255',
        'gender' => 'required|in:male,female',
        'date_of_birth' => 'required|date|before_or_equal:' .
            now()->subYears(18)->toDateString(),
    ]);

    $customer = CustomerAccount::find($authenticatedCustomer->id);

    if (!$customer) {
        abort(404, 'Customer not found.');
    }

    $customer->name = $validated['name'];
    $customer->username = $validated['username'];
    $customer->phone_number = $validated['phone_number'];
    $customer->email = $validated['email'];
    $customer->gender = $validated['gender'];
    $customer->date_of_birth = $validated['date_of_birth'];

    $customer->save();

    return back()->with('success', 'Profile updated successfully.');
}

}
