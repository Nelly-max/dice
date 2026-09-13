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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $count = ItemReservation::where(
            'updated_at',
            '<=',
            now()->subMinutes(10)
        )->delete();

        $this->info("Expired reservations removed: {$count}");

        return self::SUCCESS;
    }
}
