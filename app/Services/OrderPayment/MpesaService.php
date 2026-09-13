<?php

namespace App\Services\OrderPayment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    /**
     * Get the M-Pesa API base URL.
     */
    public function baseUrl(): string
    {
        return 'https://' . config('mpesa.env') . '.safaricom.co.ke';
    }

    /**
     * Generate or retrieve a cached M-Pesa access token.
     *
     * @throws \Exception
     */
    public function generateAccessToken(): string
    {
        return Cache::remember('mpesa_access_token', now()->addMinutes(58), function () {

            $url = $this->baseUrl() . '/oauth/v1/generate?grant_type=client_credentials';

            $http = Http::acceptJson();

            if (config('mpesa.env') === 'sandbox') {
                $http = $http->withoutVerifying();
            }

            $response = $http
                ->withBasicAuth(
                    config('mpesa.consumer_key'),
                    config('mpesa.consumer_secret')
                )
                ->get($url);

            if (! $response->successful()) {

                Log::error('Failed to generate M-Pesa access token', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                throw new \Exception('Unable to obtain M-Pesa access token.');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Handshake Step: Register URLs with Safaricom C2B API using exact keys.
     */
    public function registerUrls(): array
    {
        $token = $this->generateAccessToken();
        $url = $this->baseUrl() . '/mpesa/c2b/v1/registerurl';

        $c2bUrl = rtrim(config('mpesa.c2b_url'), '/');

        $http = Http::acceptJson()->withToken($token);

        if (config('mpesa.env') === 'sandbox') {
            $http = $http->withoutVerifying();
        }

        $response = $http->post($url, [
            'ShortCode'       => config('mpesa.shortcode'),
            'ResponseType'    => 'Completed', 
            'ConfirmationURL' => $c2bUrl . '/api/mpesa/c2b/confirmation',
            'ValidationURL'   => $c2bUrl . '/api/mpesa/c2b/validation',
        ]);

        if (! $response->successful()) {
            Log::error('M-Pesa C2B URL Registration Failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }

        return $response->json() ?? [];
    }

    /**
     * Sandbox Utility: Simulate a manual user entering your Paybill and Account.
     */
    public function simulateTransaction(string $phone, float|int|string $amount, string $accountNumber): array
    {
        $token = $this->generateAccessToken();
        $url = $this->baseUrl() . '/mpesa/c2b/v1/simulate';

        $http = Http::acceptJson()->withToken($token);

        if (config('mpesa.env') === 'sandbox') {
            $http = $http->withoutVerifying();
        }

        $response = $http->post($url, [
            'ShortCode'     => config('mpesa.shortcode'),
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => $this->formatAmount($amount),
            'Msisdn'        => $this->formatPhoneNumber($phone),
            'BillRefNumber' => trim($accountNumber),
        ]);

        if (! $response->successful()) {
            Log::error('M-Pesa C2B Simulation Failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }

        return $response->json() ?? [];
    }

    /**
     * Generate M-Pesa timestamp.
     */
    public function generateTimestamp(): string
    {
        return now()->format('YmdHis');
    }

    /**
     * Generate the M-Pesa password.
     */
    public function generatePassword(string $timestamp): string
    {
        return base64_encode(
            config('mpesa.shortcode') .
            config('mpesa.passkey') .
            $timestamp
        );
    }

    /**
     * Format Kenyan phone numbers safely to support 07... and 01... lines.
     */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D/', '', trim($phone));

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254' . $phone;
        }

        if (! preg_match('/^254(7|1)\d{8}$/', $phone)) {
            throw new \InvalidArgumentException('Invalid Kenyan phone number.');
        }

        return $phone;
    }

    /**
     * Format amount for M-Pesa.
     */
    public function formatAmount(float|int|string $amount): int
    {
        return (int) round($amount);
    }
}
