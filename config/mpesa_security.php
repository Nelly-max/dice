<?php

/*
|--------------------------------------------------------------------------
| M-Pesa callback protection (customer payments)
|--------------------------------------------------------------------------
|
| callback_secret: append ?key=<this value> to MPESA_CALLBACK_URL (and the C2B
|   validation / confirmation URLs). Requests without it are rejected.
|   Generate:  php -r "echo bin2hex(random_bytes(32));"
| enforce_ip: only accept callbacks from Safaricom's addresses. On by default
|   in production. Behind a proxy / tunnel set TRUSTED_PROXIES.
|
*/

return [
    'environment'     => env('MPESA_ENVIRONMENT', env('MPESA_ENV', 'sandbox')),

    'callback_secret' => env('MPESA_CALLBACK_SECRET', ''),

    'trusted_proxies' => env('TRUSTED_PROXIES', ''),

    'enforce_ip'      => filter_var(
        env('MPESA_IP_ENFORCE', env('MPESA_ENVIRONMENT', env('MPESA_ENV', 'sandbox')) === 'production'),
        FILTER_VALIDATE_BOOLEAN
    ),

    // Safaricom Daraja callback source ranges
    // Safaricom's Daraja callback ranges (same list as the business app)
    'allowed_ips'     => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MPESA_ALLOWED_IPS', '196.201.212.0/24,196.201.213.0/24,196.201.214.0/24'))
    ))),
];
