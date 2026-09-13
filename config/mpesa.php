<?php

return [

    'env' => env('MPESA_ENVIRONMENT', 'sandbox'),

    'consumer_key' => env('MPESA_CONSUMER_KEY'),

    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    'shortcode' => env('MPESA_C2B_SHORTCODE'), 

    'passkey' => env('MPESA_STK_PASSKEY'),

    'callback_url' => env('MPESA_CALLBACK_URL'),

    'c2b_url' => env('MPESA_C2B_URL'),

];
