<?php

namespace App\Console\Commands;

use App\Services\OrderPayment\MpesaService;
use Illuminate\Console\Command;

/**
 * Tells Safaricom where to send Paybill (C2B) validation and confirmation calls:
 * {MPESA_CALLBACK_BASE}/pay/hook/c2b/validate|confirm/{MPESA_CALLBACK_SECRET}.
 * Run once per shortcode, and again after changing the base address or the secret.
 * (Was a public web route, POST /payment/mpesa/c2b/register: anyone could call it.)
 */
class MpesaRegisterUrls extends Command
{
    protected $signature = 'mpesa:register-urls';

    protected $description = 'Register the Paybill (C2B) callback addresses with Safaricom';

    public function handle(MpesaService $mpesa): int
    {
        $this->line('Confirmation: ' . preg_replace('#/[^/]+$#', '/***', $mpesa->callbackUrl('c2b/confirm')));
        $this->line('Validation:   ' . preg_replace('#/[^/]+$#', '/***', $mpesa->callbackUrl('c2b/validate')));

        $response = $mpesa->registerUrls();

        $this->line(json_encode($response, JSON_PRETTY_PRINT));

        return ($response['ResponseCode'] ?? $response['ResponseDescription'] ?? null) !== null
            ? self::SUCCESS
            : self::FAILURE;
    }
}
