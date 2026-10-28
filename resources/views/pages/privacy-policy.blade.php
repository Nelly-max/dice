@php
    $company = config('smartmarket.company', 'Smart Market Kenya');
    $privacyEmail = config('smartmarket.privacy_email') ?: config('smartmarket.contact.email');
    $contactPhone = config('smartmarket.contact.phone');
    $contactAddress = config('smartmarket.contact.address');
    $deliveryRadiusKm = config('homemarket.max_radius_m', 3000) / 1000;
@endphp

<p class="sm-lede">
    This Privacy Policy explains what personal data {{ $company }} ("Smart Market", "we", "us" or "our")
    collects when you use the Smart Market website and services, why we use it, how we protect it,
    who we share it with, and the choices and rights available to you.
</p>

<<<<<<< HEAD
<p>
    We aim to handle personal data responsibly and in accordance with applicable Kenyan data protection
    laws, including the Data Protection Act, 2019, where applicable.
</p>
=======
>>>>>>> 17e23ff28b57e0fd1ae7d9a48743b1140c30e506

<nav class="sm-toc" aria-label="On this page">
    <strong>On this page</strong>
    <ol>
        <li><a href="#who-we-are">Who we are</a></li>
        <li><a href="#what-we-collect">What we collect</a></li>
        <li><a href="#how-we-use-data">How we use your data</a></li>
        <li><a href="#sharing">How we share your data</a></li>
        <li><a href="#location">Location</a></li>
        <li><a href="#cookies">Cookies and browser storage</a></li>
        <li><a href="#retention">How long we keep your data</a></li>
        <li><a href="#security">Security</a></li>
        <li><a href="#your-rights">Your privacy rights</a></li>
        <li><a href="#children">Children and young people</a></li>
        <li><a href="#changes">Changes to this policy</a></li>
        <li><a href="#contact-us">Contact us</a></li>
    </ol>
</nav>

<h2 id="who-we-are">1. Who we are</h2>
<<<<<<< HEAD
<p>
    Smart Market is an online marketplace operating in Kenya. Through our services, you may order products
    from shops, including Home Market, find homes and other services, make payments using supported payment
    methods, and interact with participating businesses and service providers.
</p>
<p>
    Independent shops, service providers, riders and marketers may use Smart Market to provide services
    or conduct business. {{ $company }} is responsible for determining how and why personal data is
    processed for the activities described in this policy, where it acts as the data controller.
</p>
<p>
    A participating business or service provider may also have its own responsibilities for personal data
    it processes. The applicable responsibilities depend on the service and the parties involved.
=======
 
<p>
    Smart Market is a digital marketplace and business platform that connects customers, businesses, and service providers through a range of online services. Our goal is to make it easier for people to discover products, shop from participating businesses, access services, and conduct business through one convenient platform.
</p>
<p>
    Through Smart Market, customers can browse and order products from participating shops through Home Market, discover houses and other property-related opportunities through HomeCity, explore accommodation and property listings through our Airbnb services, access cooking gas products and services, and connect with participating businesses and service providers. The platform may also support deliveries, payments through supported payment methods, and other marketplace services as they become available.
</p>
<p>
    Smart Market also provides opportunities for businesses, business owners, independent service providers, riders, and marketers to participate in the marketplace, promote their offerings, manage business activities, provide delivery or other services, and connect with potential customers.
>>>>>>> 17e23ff28b57e0fd1ae7d9a48743b1140c30e506
</p>

<h2 id="what-we-collect">2. What we collect</h2>

<h3>Information you provide</h3>
<ul>
    <li>
        <strong>Account details:</strong> your name, username, email address, phone number and, where
        provided, gender, date of birth and other profile information.
    </li>
    <li>
        <strong>Authentication information:</strong> your password, which should be stored in hashed form
        rather than as readable text, and information needed to verify your account or reset your password.
    </li>
    <li>
        <strong>Profile information:</strong> photographs or other details you choose to upload.
    </li>
    <li>
        <strong>Delivery details:</strong> your selected delivery location, delivery address and delivery
        instructions or notes.
    </li>
    <li>
        <strong>Orders and transactions:</strong> the products or services ordered, the relevant business,
        quantities, prices, delivery charges, invoices and order status.
    </li>
    <li>
        <strong>Payment information:</strong> payment amounts, transaction references, payment status and
        the phone number or other details required to process or verify a payment. Payment processing may
        involve third-party payment providers.
    </li>
    <li>
        <strong>Riders and marketers:</strong> information provided when registering for or using these
        accounts, including relevant application details, referral codes, delivery activity, earnings and
        payout information.
    </li>
    <li>
        <strong>Support communications:</strong> messages, enquiries, complaints and other information you
        provide when contacting us.
    </li>
</ul>

<h3>Information collected through use of the services</h3>
<ul>
    <li>
        <strong>Location information:</strong> delivery coordinates or rider location information when
        the relevant location feature is used or permitted.
    </li>
    <li>
        <strong>Session and device information:</strong> information associated with your session, browser
        storage, security cookies and preferences needed to operate the website.
    </li>
    <li>
        <strong>Service activity:</strong> information about interactions with the website, such as cart
        activity, order progress and actions necessary to maintain account security and service reliability.
    </li>
</ul>
<p>
    We seek to collect personal data that is relevant and necessary for the purposes described in this
    policy. Where information is optional, you may choose not to provide it, although some features may
    then be unavailable.
</p>

<h2 id="how-we-use-data">3. How we use your data</h2>
<p>We may use your personal data for the following purposes:</p>
<ul>
    <li>Creating and managing customer, business, rider and marketer accounts.</li>
    <li>Authenticating users, maintaining sessions and protecting accounts against unauthorised access.</li>
    <li>Processing orders, coordinating deliveries and communicating order updates.</li>
    <li>Finding relevant shops, products, homes or services based on your selections and delivery location.</li>
    <li>Calculating applicable delivery charges and facilitating delivery tracking.</li>
    <li>Processing or verifying payments, refunds, invoices and relevant transaction records.</li>
    <li>Managing business referrals, rider assignments, earnings and payouts.</li>
    <li>Responding to enquiries, resolving complaints and providing customer support.</li>
    <li>Maintaining, troubleshooting and improving our services and security.</li>
    <li>Meeting applicable legal, regulatory, accounting and record-keeping obligations.</li>
    <li>Sending service-related notifications and, where appropriate, other communications permitted by law.</li>
</ul>
<p>
    We process personal data only where we have an appropriate legal basis, such as performing a contract,
    complying with a legal obligation, pursuing a legitimate interest where permitted, or obtaining consent
    where consent is required. The applicable basis depends on the purpose and circumstances of the processing.
</p>

<h2 id="sharing">4. How we share your data</h2>
<p>
    We do not treat your personal data as public information. However, providing our services may require
    sharing relevant information with the following parties:
</p>
<ul>
    <li>
        <strong>Participating businesses:</strong> the shop or service provider fulfilling your order may
        receive the information needed to prepare and complete it.
    </li>
    <li>
        <strong>Riders and delivery personnel:</strong> relevant order, delivery address, contact and
        location details may be shared to enable delivery.
    </li>
    <li>
        <strong>Payment providers:</strong> relevant transaction details may be shared to initiate,
        process, verify or reconcile payments.
    </li>
    <li>
        <strong>Service providers:</strong> authorised providers may process data for services such as
        hosting, communications, security and technical support.
    </li>
    <li>
        <strong>Professional advisers and authorities:</strong> information may be disclosed where
        reasonably necessary to obtain professional advice, comply with the law or respond to lawful requests.
    </li>
    <li>
        <strong>Business transfers:</strong> data may be transferred as part of a merger, restructuring
        or transfer of business, subject to applicable legal requirements and appropriate safeguards.
    </li>
</ul>
<p>
    We aim to limit disclosures to information reasonably necessary for the relevant purpose. Where
    third-party providers process personal data on our behalf, appropriate contractual and security
    safeguards should apply.
</p>
<p>
    Personal data may be processed or stored outside Kenya where relevant service providers or infrastructure
    are located abroad. Any such transfer should comply with applicable data protection requirements.
</p>

<h2 id="location">5. Location</h2>
<ul>
    <li>
        <strong>Customers:</strong> we use your selected delivery location to identify shops within the
        applicable delivery range (currently up to {{ $deliveryRadiusKm }} km, subject to service availability),
        support delivery-fee calculations and help complete your order. Your location may be retained in
        session or browser storage, depending on the feature used, so you do not have to select it again
        every time. You can change your selected location or clear locally stored location information.
    </li>
    <li>
        <strong>Riders:</strong> where rider location functionality is enabled, location information may
        be used while the rider is online or undertaking a delivery to support assignments, delivery
        coordination and tracking. Relevant location information may be shared with the customer for the
        associated delivery. The collection and sharing of live location depend on the feature's operation
        and permissions.
    </li>
</ul>
<p>
    You can manage browser or device location permissions through your device or browser settings.
    Disabling location access may affect features that depend on location information. Clearing browser
    storage may also remove a previously saved delivery location.
</p>

<h2 id="cookies">6. Cookies and browser storage</h2>
<p>
    We may use cookies and browser storage that are necessary to operate and secure Smart Market and
    remember certain preferences.
</p>
<ul>
    <li>
        <strong>Session and security cookies:</strong> help maintain sign-in sessions, support cart
        functionality and protect forms against misuse.
    </li>
    <li>
        <strong>Preference storage:</strong> may remember settings such as display preferences, menu
        layout or a selected delivery location.
    </li>
    <li>
        <strong>Security and reliability:</strong> relevant technical information may help identify
        errors, protect the website and maintain service availability.
    </li>
</ul>
<p>
    You can manage or clear cookies and browser storage through your browser settings. Blocking
    essential cookies or clearing session data may affect sign-in, cart functionality and other features.
    If we introduce non-essential cookies or similar technologies, we will provide any notices or
    consent choices required by applicable law.
</p>

<h2 id="retention">7. How long we keep your data</h2>
<p>
    We retain personal data for as long as reasonably necessary to fulfil the purposes for which it was
    collected, including providing services, maintaining accounts, resolving disputes, preventing fraud
    and meeting legal, tax, accounting or regulatory obligations.
</p>
<p>
    Retention periods vary depending on the type of information and the reason it is held. For example,
    transaction records may need to be retained after an account is closed where required by law.
    When personal data is no longer required, we will take appropriate steps to delete it, anonymise it
    or otherwise handle it in accordance with applicable requirements.
</p>

<h2 id="security">8. Security</h2>
<p>
    We use appropriate technical and organisational measures intended to protect personal data against
    unauthorised access, loss, alteration, disclosure or destruction. These measures may include encrypted
    connections, hashed passwords, access controls, account verification and security monitoring.
</p>
<p>
    No system or method of transmission can be guaranteed to be completely secure. Please keep your
    password confidential, use a secure device and contact us promptly if you believe your account
    has been compromised.
</p>

<h2 id="your-rights">9. Your privacy rights</h2>
<p>
    Subject to applicable law and any relevant limitations, you may have the right to:
</p>
<ul>
    <li>Be informed about how your personal data is used.</li>
    <li>Request access to personal data we hold about you.</li>
    <li>Request correction of inaccurate, incomplete or outdated personal data.</li>
    <li>Request deletion of personal data where the law permits it.</li>
    <li>Object to certain processing or request restriction of processing where applicable.</li>
    <li>Withdraw consent where processing is based on consent, without affecting the lawfulness of processing before withdrawal.</li>
    <li>Request a copy of your personal data or its transfer where a right to data portability applies.</li>
    <li>Raise a concern or complaint with the relevant data protection authority.</li>
</ul>
<p>
    To exercise a right or ask a question about your personal data, contact us using the details below.
    We may need to verify your identity before acting on a request. Some records may need to be retained
    where required by law or where another lawful basis applies.
</p>

<h2 id="children">10. Children and young people</h2>
<p>
    Smart Market is not intended to encourage children to provide personal data without appropriate
    authorisation. Young people under 18 should use the services only where legally permitted and, where
    required, with the involvement or consent of a parent or guardian.
</p>
<p>
    Age-restricted products and services are subject to applicable age restrictions. Where relevant,
    participating businesses or delivery personnel may request proof of age before completing a transaction.
</p>

<h2 id="changes">11. Changes to this policy</h2>
<p>
    We may update this Privacy Policy as our services, practices or legal obligations change. The latest
    version will be published on this page. Where appropriate or legally required, we will notify users
    of significant changes through the website, by email or through another suitable communication method.
</p>
<p>
    Please review this page periodically to remain informed about how we handle personal data.
</p>

<h2 id="contact-us">12. Contact us</h2>
<p>
    For questions, concerns or requests relating to this Privacy Policy or your personal data, contact us
    using the details below:
</p>
<ul>
    @if ($privacyEmail)
        <li>
            Email:
            <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>
        </li>
    @endif

    @if ($contactPhone)
        <li>
            Phone:
            <a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}">
                {{ $contactPhone }}
            </a>
        </li>
    @endif

    @if ($contactAddress)
        <li>Address: {{ $contactAddress }}</li>
    @endif

    <li>
        Or see <a href="{{ route('page', 'contact') }}">Contact &amp; support</a>.
    </li>
</ul>