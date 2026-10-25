<?php

/*
|--------------------------------------------------------------------------
| Smart Market – public site details
|--------------------------------------------------------------------------
|
| Used by the main site (home, settings) and the information pages (About,
| Help, Contact, Terms, Privacy). Fill these in from .env; anything left
| empty is simply not shown, so the pages never display made-up details.
|
*/

return [

    'name' => 'Smart Market',

    'company' => env('SMARTMARKET_COMPANY', 'Smart Market Kenya'),

    'tagline' => 'Shops, services and homes near you, in one place.',

    'country' => 'Kenya',

    'contact' => [
        'email'    => env('SMARTMARKET_SUPPORT_EMAIL'),
        'phone'    => env('SMARTMARKET_SUPPORT_PHONE'),
        'whatsapp' => env('SMARTMARKET_WHATSAPP'),        // digits only, e.g. 2547XXXXXXXX
        'address'  => env('SMARTMARKET_ADDRESS'),
        'hours'    => env('SMARTMARKET_SUPPORT_HOURS', 'Monday to Saturday, 8:00 am – 8:00 pm'),
    ],

    // Data protection contact shown in the privacy policy (falls back to the support email).
    'privacy_email' => env('SMARTMARKET_PRIVACY_EMAIL'),

    'socials' => [
        'instagram' => env('SMARTMARKET_INSTAGRAM', 'https://www.instagram.com/smartmarket_ke/'),
        'tiktok'    => env('SMARTMARKET_TIKTOK'),
        'youtube'   => env('SMARTMARKET_YOUTUBE'),
        'facebook'  => env('SMARTMARKET_FACEBOOK'),
        'x'         => env('SMARTMARKET_X'),
    ],

    /*
    | Information pages: /{slug}. The order here is the order in menus.
    | Dates are shown as "Last updated" on the page.
    */
    'pages' => [
        'about'          => ['title' => 'About Smart Market',   'menu' => 'About',              'icon' => 'fa-solid fa-circle-info',     'updated' => '2026-10-23',
            'summary' => 'Smart Market brings shops, services and homes near you into one place in Kenya, with delivery from nearby shops and M-Pesa payments.'],
        'help'           => ['title' => 'Help & FAQ',           'menu' => 'Help & FAQ',         'icon' => 'fa-solid fa-circle-question', 'updated' => '2026-10-22',
            'summary' => 'Answers about ordering, delivery, payment with M-Pesa, your account, and joining Smart Market as a rider or marketer.'],
        'contact'        => ['title' => 'Contact & support',    'menu' => 'Contact & support',  'icon' => 'fa-solid fa-headset',         'updated' => '2026-10-22',
            'summary' => 'How to reach Smart Market support about an order, a payment or your account.'],
        'terms'          => ['title' => 'Terms & Conditions',   'menu' => 'Terms & Conditions', 'icon' => 'fa-solid fa-file-contract',   'updated' => '2026-10-23',
            'summary' => 'The simple terms for shopping on Smart Market: accounts, orders, payment, delivery and refunds.'],
        'privacy-policy' => ['title' => 'Privacy Policy',       'menu' => 'Privacy Policy',     'icon' => 'fa-solid fa-user-shield',     'updated' => '2026-10-23',
            'summary' => 'What information Smart Market collects, how we use it and the choices you have.'],
    ],

];
