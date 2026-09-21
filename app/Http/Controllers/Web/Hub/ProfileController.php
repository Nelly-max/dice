<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
