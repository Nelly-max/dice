<?php

/**
 * One-off tidy of the migration files (run it ONCE from the project root):
 *
 *     php database/tidy-migrations.php
 *
 * 1. The renamed migrations (2026_10_30_HHMMSS_...) are already in place. This
 *    removes the old copy of each one, so no migration exists twice.
 * 2. Removes duplicate / empty migration files and empty folders.
 * 3. Renames the rows in every `migrations` table it can reach, so Laravel
 *    knows the renamed migrations have already run (nothing is re-run).
 *
 * Safe to run again: anything already done is skipped. Deletes itself when done.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$dir = __DIR__ . '/migrations';

/** [folder, old file, new file] */
$renamed = [
    ['Customer', '2026_06_06_052935_create_subdivision_shipment_table.php', '2026_10_30_120001_create_subdivision_shipment_table.php'],
    ['Customer', '2026_06_06_145633_create_shipment_types_table.php', '2026_10_30_120002_create_shipment_types_table.php'],
    ['Customer', '2026_06_06_152324_create_cart_table.php', '2026_10_30_120003_create_cart_table.php'],
    ['Customer', '2026_06_11_151118_create_item_reservations_table.php', '2026_10_30_120004_create_item_reservations_table.php'],
    ['Customer', '2026_06_17_152145_create_account_monthly_counter_table.php', '2026_10_30_120005_create_account_monthly_counter_table.php'],
    ['Customer', '2026_06_17_160829_create_customer_accounts_table.php', '2026_10_30_120006_create_customer_accounts_table.php'],
    ['Customer', '2026_07_15_160430_create_checkout_invoices_table.php', '2026_10_30_120007_create_checkout_invoices_table.php'],
    ['Customer', '2026_07_15_162209_create_checkout_items_table.php', '2026_10_30_120008_create_checkout_items_table.php'],
    ['Customer', '2026_07_15_171156_create_daily_county_sequences_table.php', '2026_10_30_120009_create_daily_county_sequences_table.php'],
    ['Customer', '2026_07_18_054344_create_mpesa_transactions_orders_table.php', '2026_10_30_120010_create_mpesa_transactions_orders_table.php'],
    ['Customer', '2026_07_18_153842_create_orders_table.php', '2026_10_30_120011_create_orders_table.php'],
    ['Customer', '2026_07_18_153935_create_order_businesses_table.php', '2026_10_30_120012_create_order_businesses_table.php'],
    ['Customer', '2026_07_18_153946_create_order_items_table.php', '2026_10_30_120013_create_order_items_table.php'],
    ['Customer', '2026_07_19_111818_create_order_customer_details_table.php', '2026_10_30_120014_create_order_customer_details_table.php'],
    ['Customer', '2026_07_25_150413_create_sales_table.php', '2026_10_30_120015_create_sales_table.php'],
    ['Customer', '2026_07_27_153754_create_riders_table.php', '2026_10_30_120016_create_riders_table.php'],
    ['Customer', '2026_07_27_155411_create_rider_personal_details_table.php', '2026_10_30_120017_create_rider_personal_details_table.php'],
    ['Customer', '2026_07_27_160647_create_rider_car_details_table.php', '2026_10_30_120018_create_rider_car_details_table.php'],
    ['Customer', '2026_07_27_161510_create_rider_locations_table.php', '2026_10_30_120019_create_rider_locations_table.php'],
    ['Customer', '2026_07_27_162404_create_deliveries_table.php', '2026_10_30_120020_create_deliveries_table.php'],
    ['Customer', '2026_08_01_143940_create_delivery_rider_requests_table.php', '2026_10_30_120021_create_delivery_rider_requests_table.php'],
    ['Customer', '2026_08_06_171329_make_rider_location_coordinates_nullable.php', '2026_10_30_120022_make_rider_location_coordinates_nullable.php'],
    ['Customer', '2026_09_06_151204_create_delivery_options_table.php', '2026_10_30_120023_create_delivery_options_table.php'],
    ['Customer', '2026_09_10_165034_create_marketers_table.php', '2026_10_30_120024_create_marketers_table.php'],
    ['Customer', '2026_09_22_052839_create_business_referrals_table.php', '2026_10_30_120025_create_business_referrals_table.php'],
    ['Customer', '2026_09_27_070328_create_sales_communications_table.php', '2026_10_30_120026_create_sales_communications_table.php'],
    ['Customer', '2026_11_12_120000_riders_comment_and_customer_notifications.php', '2026_10_30_120027_riders_comment_and_customer_notifications.php'],
    ['Finance', '2026_08_13_051805_create_rider_payment_profile_table.php', '2026_10_30_120101_create_rider_payment_profile_table.php']
];

/** files that are not needed at all (duplicates / empty stubs) */
$unused = [

];

/** empty folders */
$emptyDirs = ['Y'];

$path = fn (string $folder, string $file) => $dir . ($folder === '' ? '' : '/' . $folder) . '/' . $file;
$stem = fn (string $file) => substr($file, 0, -4);

// 1 + 2: files
$removed = 0;
foreach ($renamed as [$folder, $old, $new]) {
    if ($old !== $new && is_file($path($folder, $old)) && is_file($path($folder, $new))) {
        unlink($path($folder, $old));
        $removed++;
    }
}
foreach ($unused as $file) {
    if (is_file("$dir/$file")) {
        unlink("$dir/$file");
        $removed++;
    }
}
foreach ($emptyDirs as $folder) {
    if (is_dir("$dir/$folder") && count(scandir("$dir/$folder")) === 2) {
        rmdir("$dir/$folder");
    }
}
echo "Files removed: $removed\n";

// 3: migrations table(s)
$table = config('database.migrations.table', 'migrations');
$table = is_array($table) ? ($table['table'] ?? 'migrations') : $table;

foreach (array_keys(config('database.connections')) as $connection) {
    $cfg = config("database.connections.$connection");
    if (($cfg['driver'] ?? '') === 'sqlite' && !is_file((string) ($cfg['database'] ?? ''))) {
        continue;
    }
    try {
        if (!Schema::connection($connection)->hasTable($table)) {
            continue;
        }
        $changed = 0;
        foreach ($renamed as [, $old, $new]) {
            $changed += DB::connection($connection)->table($table)
                ->where('migration', $stem($old))
                ->update(['migration' => $stem($new)]);
        }
        foreach ($unused as $file) {
            $changed += DB::connection($connection)->table($table)
                ->where('migration', $stem(basename($file)))
                ->delete();
        }
        echo "[$connection] $changed migration rows updated\n";
    } catch (\Throwable $e) {
        echo "[$connection] skipped: " . strtok($e->getMessage(), "\n") . "\n";
    }
}

@unlink(__FILE__);
echo "Done.\n";
