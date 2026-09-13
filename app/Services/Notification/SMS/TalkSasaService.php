<?php

namespace App\Services\Notification\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TalkSasaService
{
    protected $apiKey;
    protected $senderId;
    protected $url;

    public function __construct()
    {
        $this->apiKey   = config('services.talksasa.api_key');
        $this->senderId = config('services.talksasa.sender_id');
        $this->url      = config('services.talksasa.base_url');
    }

    protected function formatPhone(string $phone): string
    {
        // Keep only digits
        $phone = preg_replace('/\D/', '', trim($phone));

        // 07xxxxxxxx -> 2547xxxxxxxx
        if (preg_match('/^0\d{9}$/', $phone)) {
            return '254' . substr($phone, 1);
        }

        // Already in 254xxxxxxxxx format
        if (preg_match('/^254\d{9}$/', $phone)) {
            return $phone;
        }

        // +254xxxxxxxxx -> 254xxxxxxxxx
        if (preg_match('/^\+254\d{9}$/', $phone)) {
            return ltrim($phone, '+');
        }

        return $phone;
    }

    public function send($phone, $message)
    {
        try {

            // 🔥 Flexible payload (easy to adjust when API spec is confirmed)
            $payload = [
                'api_key'   => $this->apiKey,
                'sender_id' => $this->senderId,
                'phone'     => $phone,
                'message'   => $message,
            ];

            // 🔥 Try JSON first (most modern v3 APIs expect this)
            
            $phone = $this->formatPhone($phone);

            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(15)
                ->post($this->url, [
                    'sender_id' => $this->senderId,
                    'recipient' => $phone,
                    'message'   => $message,
                ]);

            // 🔥 Log raw response for debugging (VERY IMPORTANT for your case)
            Log::info('TalkSasa SMS Response', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'json'   => $response->json(),
            ]);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'data'    => $response->json() ?? $response->body(),
            ];

        } catch (\Exception $e) {

            Log::error('TalkSasa SMS Failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}