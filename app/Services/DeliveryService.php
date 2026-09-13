<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    public function calculate($business, $destination)
    {
        // call routing API

        // return

        return [
            'distance' => 15.2,
            'duration' => 1420,
            'fee' => 540
        ];
    }
}
