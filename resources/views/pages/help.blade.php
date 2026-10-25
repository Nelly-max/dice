@php
    $radiusKm = config('homemarket.max_radius_m', 3000) / 1000;
    $faq = [
        'Ordering' => [
            ['Why do I need to set a delivery location first?', "We only show shops that can deliver to you. Set your location once (tap \"Deliver to\" at the top) and we show shops within {$radiusKm} km, nearest first."],
            ["Why can't I add items from another shop?", 'A cart holds items from one shop at a time so a single rider can deliver everything together. Finish or clear your current cart to shop somewhere else.'],
            ['Why did items disappear from my cart?', 'Items are held for you for a limited time. If the cart is left inactive, they are released so other customers can buy them. Just add them again.'],
            ['Can I change or cancel an order?', 'Contact support as soon as possible with your order number. Once the shop has prepared or sent your order, it may no longer be possible to change it.'],
        ],
        'Delivery' => [
            ['How far do you deliver?', "Shops deliver up to {$radiusKm} km from your delivery location."],
            ['How much is delivery?', 'The delivery fee depends on the distance and is shown at checkout before you pay.'],
            ['How long does delivery take?', 'Most orders arrive quickly, but times are estimates and can change with traffic, weather or demand.'],
            ['Where can I follow my order?', 'Open My account → Orders to see every order and its status.'],
        ],
        'Payment' => [
            ['How do I pay?', 'With M-Pesa. At checkout you get a payment request on your phone; enter your M-Pesa PIN to confirm. We never ask for your PIN anywhere else.'],
            ['I paid but my order is not confirmed.', 'M-Pesa confirmations can take a moment. If your order is still not confirmed after a few minutes, contact support with the M-Pesa message reference.'],
            ['How do refunds work?', 'Approved refunds are sent back to the M-Pesa number that paid. See section 8 of our Terms.'],
        ],
        'Your account' => [
            ['I forgot my password.', 'On the login page tap "Forgot password". We email you a one-time code to set a new password.'],
            ['How do I change my details?', 'Go to My account and edit your name, phone, email, gender or date of birth.'],
            ['How do I switch between light and dark mode?', 'In Settings choose Light, Dark or Device. Device follows your phone or computer setting.'],
            ['How do I delete my account?', 'Contact support and ask for your account to be closed. Some order records must be kept by law.'],
        ],
        'Riders and marketers' => [
            ['How do I become a rider?', 'Sign in, open My account → Join the team and fill in the rider application. We will review it and let you know.'],
            ['How do marketers earn?', 'Marketers share referral codes. Customers get a discount when they use a code, and the marketer earns on those orders.'],
        ],
    ];
@endphp

<p class="sm-lede">Quick answers to common questions. Can't find what you need? <a href="{{ route('page', 'contact') }}">Contact support</a>.</p>

@foreach ($faq as $section => $items)
    <h2>{{ $section }}</h2>
    <div class="sm-faq">
        @foreach ($items as [$q, $a])
            <details>
                <summary>{{ $q }}</summary>
                <p>{{ $a }}</p>
            </details>
        @endforeach
    </div>
@endforeach
