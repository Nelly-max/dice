<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Customer\Cart;

class PurgeExpiredCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-expired-items';

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
        $count = Cart::where(
            'updated_at',
            '<=',
            now()->subDay()
        )->delete();

        $this->info("Deleted {$count} expired item(s).");

        return self::SUCCESS;
    }
}
