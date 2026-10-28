@php
    $c = config('smartmarket.contact');
    $socials = array_filter(config('smartmarket.socials', []));
    $icons = ['instagram' => 'fa-brands fa-instagram', 'tiktok' => 'fa-brands fa-tiktok', 'youtube' => 'fa-brands fa-youtube', 'facebook' => 'fa-brands fa-facebook', 'x' => 'fa-brands fa-x-twitter'];
    $names = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'x' => 'X'];
@endphp

<p class="sm-lede">
    We're here to help with orders, payments, deliveries and your account.
</p>

<div class="sm-contact-grid">
    @if (!empty($c['whatsapp']))
        <a class="sm-contact-card" href="https://wa.me/{{ preg_replace('/\D+/', '', $c['whatsapp']) }}" target="_blank" rel="noopener">
            <span class="sm-contact-icon is-green"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
            <strong>WhatsApp</strong><small>Chat with support</small>
        </a>
    @endif
    @if (!empty($c['phone']))
        <a class="sm-contact-card" href="tel:{{ preg_replace('/\s+/', '', $c['phone']) }}">
            <span class="sm-contact-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
            <strong>Call us</strong><small>{{ $c['phone'] }}</small>
        </a>
    @endif
    @if (!empty($c['email']))
        <a class="sm-contact-card" href="mailto:{{ $c['email'] }}">
            <span class="sm-contact-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
            <strong>Email</strong><small>{{ $c['email'] }}</small>
        </a>
    @endif
    @foreach ($socials as $key => $url)
        <a class="sm-contact-card" href="{{ $url }}" target="_blank" rel="noopener">
            <span class="sm-contact-icon"><i class="{{ $icons[$key] ?? 'fa-solid fa-link' }}" aria-hidden="true"></i></span>
            <strong>{{ $names[$key] ?? ucfirst($key) }}</strong><small>Message us</small>
        </a>
    @endforeach
</div>

<h2>Help us help you faster</h2>
<ul>
    <li>Your <strong>order number</strong> (in My account → Orders).</li>
    <li>For payments, the <strong>M-Pesa message reference</strong>.</li>
    <li>For a damaged or wrong item, a <strong>photo</strong>.</li>
</ul>
<p>We will never ask for your password or M-Pesa PIN.</p>

@if (!empty($c['address']))
    <h2>Office</h2>
    <p>{{ $c['address'] }}</p>
@endif

<h2>Before you contact us</h2>
<p>Many questions are answered in <a href="{{ route('page', 'help') }}">Help &amp; FAQ</a>.</p>
