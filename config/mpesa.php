<?php

return [

    'env' => env('MPESA_ENVIRONMENT', 'sandbox'),

    'consumer_key' => env('MPESA_CONSUMER_KEY'),

    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    'shortcode' => env('MPESA_C2B_SHORTCODE'),

    'passkey' => env('MPESA_STK_PASSKEY'),

    /*
    |--------------------------------------------------------------------------
    | Where Safaricom sends its results (callbacks)
    |--------------------------------------------------------------------------
    | Public https address of this app, e.g. https://smartmarket.co.ke. The full
    | callback addresses are built from it (App\Services\OrderPayment\MpesaService):
    |
    |   STK result       {callback_base}/pay/hook/stk/{callback_secret}
    |   Paybill check    {callback_base}/pay/hook/c2b/validate/{callback_secret}
    |   Paybill paid     {callback_base}/pay/hook/c2b/confirm/{callback_secret}
    |
    | Daraja refuses callback addresses that contain words like "mpesa",
    | "safaricom", "sql", "query", "exe" or "cmd", hence /pay/hook/.
    | Falls back to APP_URL.
    */
    'callback_base' => env('MPESA_CALLBACK_BASE', env('APP_URL')),

    // a long random string only Safaricom is given (inside the callback address).
    // Generate one with:  php -r "echo bin2hex(random_bytes(24));"
    'callback_secret' => env('MPESA_CALLBACK_SECRET'),

    /*
    | Only accept callbacks from Safaricom's servers. Their published callback IPs
    | (they can change: a rejected call is logged with its IP, so a new one shows up
    | in the log; add it with MPESA_ALLOWED_IPS="1.2.3.4,5.6.7.8").
    | Behind a proxy / load balancer / Cloudflare the app must trust that proxy
    | (bootstrap/app.php trustProxies) or every callback looks like it came from the proxy.
    | Set MPESA_VERIFY_IP=false only on a local machine.
    */
    'verify_ip' => (bool) env('MPESA_VERIFY_IP', true),

    'allowed_ips' => array_values(array_filter(array_map('trim', array_merge([
        '196.201.214.200',
        '196.201.214.206',
        '196.201.213.114',
        '196.201.214.207',
        '196.201.214.208',
        '196.201.213.44',
        '196.201.212.127',
        '196.201.212.138',
        '196.201.212.129',
        '196.201.212.136',
        '196.201.212.74',
        '196.201.212.69',
    ], explode(',', (string) env('MPESA_ALLOWED_IPS', '')))))),

];
