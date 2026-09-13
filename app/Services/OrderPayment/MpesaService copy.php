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
     * Format a Kenyan phone number.
     *
     * Accepts:
     * 0712345678
     * 712345678
     * +254712345678
     * 254712345678
     */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D/', '', trim($phone));

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7')) {
            $phone = '254' . $phone;
        }

        if (! preg_match('/^2547\d{8}$/', $phone)) {
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