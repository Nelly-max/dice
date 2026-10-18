<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
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
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
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

        DB::connection('customer')
            ->table('customers')
            ->where('id', $customer->id)
            ->update([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Profile updated successfully.');
    }

}
