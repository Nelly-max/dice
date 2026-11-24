<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Customer\ItemReservation;

class PurgeExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservation:purge-expired-reservation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release cart holds that ran out';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        // a hold that ran out: the quantity goes back to the shop (the item stays in the cart)
        $count = ItemReservation::where('expires_at', '<=', now())
            ->orWhere('updated_at', '<=', now()->subMinutes(\App\Services\Cart\CartStock::holdMinutes()))
            ->delete();

        $this->info("Expired reservations removed: {$count}");

        return self::SUCCESS;
    }
}
