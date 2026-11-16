<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Airbnb\StayController;
use App\Http\Controllers\Web\HomeCity\PropertyController;
use App\Services\Notification\CustomerNotifier;
use App\Services\Notification\SMS\TalkSasaService;
use App\Support\Bookings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Booking on Smart Market:
 *   Airbnb     "Request to book" on a stay        → a stay booking (dates, guests, total)
 *   Home City  "Book a viewing" on a property     → a viewing (date and time, which unit)
 * The host / lister confirms or declines it in the business app; the hub sees all of them.
 * Guests can book with a name and phone; a signed link (and, when signed in, Hub → Bookings)
 * brings them back to the booking.
 */
class BookingController extends Controller
{
    /* ============================== Airbnb ============================== */

    public function storeStay(Request $request, StayController $stays, TalkSasaService $sms, string $id)
    {
        if ($login = $this->mustLogIn()) return $login;

        $this->ensureReady();
        [$stay, $sample] = $stays->findStay($id);
        abort_unless($stay, 404);
        if ($sample || config('airbnb.preview')) {
            return back()->with('error', 'Booking for this stay is not open yet.');
        }

        $data = $this->validateContact($request) + $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:30',
        ], [
            'check_in.after_or_equal' => 'Check-in cannot be in the past.',
            'check_out.after' => 'Check-out must be after check-in.',
        ]);

        $in = Carbon::parse($data['check_in'])->startOfDay();
        $out = Carbon::parse($data['check_out'])->startOfDay();
        $nights = (int) $in->diffInDays($out);
        if ($nights > 60) throw ValidationException::withMessages(['check_out' => 'Stays of more than 60 nights are agreed with the host directly.']);

        $unitKey = (string) \Illuminate\Support\Str::after($stay->id, '-');
        $free = (int) $stay->units_free;
        if ($free < 1 || Bookings::overlapping((int) $stay->property_id, $unitKey, $in->toDateString(), $out->toDateString()) >= $free) {
            throw ValidationException::withMessages(['check_in' => 'Every ' . strtolower($stay->unit_name) . ' here is taken for those dates. Try other dates.']);
        }

        $price = (float) $stay->price;
        $sub = $price * $nights;
        $cleaning = (float) config('airbnb.cleaning_fee', 0);
        $service = round($sub * (float) config('airbnb.service_fee_percent', 0) / 100);

        $b = $this->create([
            'subdivision' => 'airbnb', 'kind' => 'stay',
            'listing_id' => $stay->listing_id ?? null, 'property_id' => $stay->property_id, 'unit_key' => $unitKey,
            'unit_label' => $stay->unit_name, 'property_title' => $stay->title,
            'business_account' => $this->airbnbAccount($stay), 'business_name' => $stay->host, 'business_phone' => $stay->host_phone,
            'check_in' => $in->toDateString(), 'check_out' => $out->toDateString(), 'guests' => (int) $data['guests'], 'nights' => $nights,
            'unit_price' => $price, 'cleaning_fee' => $cleaning, 'service_fee' => $service, 'amount' => $sub + $cleaning + $service,
        ], $data);

        $this->tellHost($sms, $b, 'New booking ' . $b->reference . ': ' . $b->customer_name . ' (' . $b->customer_phone . ') wants ' . $b->unit_label . ' at ' . $b->property_title
            . ', ' . Bookings::when($b) . ', ' . $b->guests . ' ' . ($b->guests == 1 ? 'guest' : 'guests') . '. Confirm it in your Smart Market business app.');

        return redirect()->to($this->link($b))->with('success', 'Booking requested. The host will confirm it soon.');
    }

    /* ============================= Home City ============================ */

    public function storeViewing(Request $request, PropertyController $properties, TalkSasaService $sms, $id)
    {
        if ($login = $this->mustLogIn()) return $login;

        $this->ensureReady();
        [$listing, $property] = $properties->bookingTarget($id);

        $data = $this->validateContact($request) + $request->validate([
            'viewing_date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addMonths(3)->toDateString(),
            'viewing_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'unit' => 'nullable|string|max:130',            // "<unit group key>|<unit id>" or "<key>|" for any of that type
        ], [
            'viewing_date.after_or_equal' => 'Pick a day from today on.',
            'viewing_date.before_or_equal' => 'Pick a day within the next three months.',
            'viewing_time.regex' => 'Pick a time.',
        ]);

        $at = Carbon::parse($data['viewing_date'] . ' ' . $data['viewing_time'], config('app.display_timezone', 'Africa/Nairobi'));
        if ($at->isPast()) throw ValidationException::withMessages(['viewing_time' => 'That time has already passed today.']);
        $hour = (int) $at->format('G');
        if ($hour < 7 || $hour >= 19) throw ValidationException::withMessages(['viewing_time' => 'Viewings are between 7:00 am and 7:00 pm.']);

        // the unit type (and unit) picked, checked against what is listed
        [$unitKey, $unitId] = array_pad(explode('|', (string) ($data['unit'] ?? ''), 2), 2, '');
        $group = collect($property->unit_groups)->firstWhere('key', $unitKey);
        $unit = $group && $unitId !== '' ? collect($group->vacant_units)->first(fn ($u) => (string) $u->id === $unitId) : null;
        $lister = $listing->lister;

        $b = $this->create([
            'subdivision' => 'homecity', 'kind' => 'viewing',
            'listing_id' => $listing->id, 'property_id' => $property->id, 'unit_key' => $group->key ?? null,
            'unit_label' => $group->label ?? null, 'unit_number' => $unit->name ?? null, 'property_title' => $property->title,
            'business_account' => $listing->lister_id, 'business_name' => $lister->business_name ?? $lister->name ?? null, 'business_phone' => $lister->phone ?? null,
            'viewing_at' => $at->toDateTimeString(), 'guests' => 1,   // Kenya wall-clock time, shown as is everywhere
            'unit_price' => $unit->price ?? ($group->min ?? 0), 'amount' => 0,
        ], $data);

        $this->tellHost($sms, $b, 'Viewing request ' . $b->reference . ': ' . $b->customer_name . ' (' . $b->customer_phone . ') would like to view ' . $b->property_title
            . ($b->unit_label ? ' (' . $b->unit_label . ($b->unit_number ? ' ' . $b->unit_number : '') . ')' : '') . ' on ' . $at->format('D j M, g:i A') . '. Confirm it in your Smart Market business app.');

        return redirect()->to($this->link($b))->with('success', 'Viewing requested. The lister will confirm the time.');
    }

    /* ============================ the booking ============================ */

    public function show(Request $request, string $reference)
    {
        $b = $this->mine($request, $reference);
        $events = Bookings::db()->table('booking_events')->where('booking_id', $b->id)->orderBy('id')->get();
        $layout = $b->subdivision === 'homecity' ? 'HomeCity.layouts.app' : 'Airbnb.layouts.app';
        $back = $b->subdivision === 'homecity'
            ? ($b->listing_id ? route('homecity.listing.view', $b->listing_id) : route('homecity.index'))
            : ($b->property_id && $b->unit_key ? route('airbnb.stay', $b->property_id . '-' . $b->unit_key) : route('airbnb.index'));
        $cancelUrl = URL::signedRoute('bookings.cancel', $b->reference);

        return view('Bookings.show', compact('b', 'events', 'layout', 'back', 'cancelUrl'));
    }

    public function cancel(Request $request, TalkSasaService $sms, string $reference)
    {
        $b = $this->mine($request, $reference);
        if (!Bookings::cancellable($b)) return back()->with('error', 'This booking can no longer be cancelled here. Please call the host.');

        $reason = trim((string) $request->input('reason')) ?: null;
        Bookings::db()->table('bookings')->where('id', $b->id)->update([
            'status' => 'cancelled', 'cancelled_by' => 'customer', 'cancel_reason' => $reason, 'cancelled_at' => now(), 'updated_at' => now(),
        ]);
        Bookings::log($b->id, 'cancelled', 'customer', $b->customer_id, $b->customer_name, $reason);
        $this->tellHost($sms, $b, 'Booking ' . $b->reference . ' (' . $b->property_title . ', ' . Bookings::when($b) . ') was cancelled by ' . $b->customer_name . '.');

        return redirect()->to($this->link($b))->with('success', 'Your booking is cancelled.');
    }

    /** Hub → Bookings: the signed-in customer's bookings */
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $status = array_key_exists($request->query('status'), Bookings::STATUSES) ? $request->query('status') : '';
        $ready = Bookings::ready();
        $key = Bookings::phoneKey($customer->phone_number);

        $bookings = $ready
            ? Bookings::db()->table('bookings')
                ->where(fn ($q) => $q->where('customer_id', $customer->id)->when(strlen($key) === 9, fn ($q) => $q->orWhere('customer_phone', 'like', '%' . $key)))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw("case when status in ('pending','confirmed') then 0 else 1 end")
                ->orderByDesc('id')
                ->paginate(15)->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);

        $links = collect($bookings->items())->mapWithKeys(fn ($b) => [$b->id => $this->link($b)]);

        return view('Hub.bookings', compact('bookings', 'status', 'links', 'ready'));
    }

    /* ================================================================== */

    /**
     * Booking a stay or a viewing needs a signed-in customer. A guest is sent to log in and
     * comes back to the page they booked from (AuthController keeps url.intended).
     */
    private function mustLogIn(): ?\Illuminate\Http\RedirectResponse
    {
        if (Auth::guard('customer')->check()) return null;

        session(['url.intended' => url()->previous()]);

        return redirect()->route('login')->with('error', 'Log in to book. You will come back to this page.');
    }

    private function ensureReady(): void
    {
        if (!Bookings::ready()) {
            abort(503, 'Booking is being set up. Please try again later.');
        }
    }

    private function validateContact(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+?254|0)?[17]\d{8}$/'],
            'email' => 'nullable|email|max:160',
            'notes' => 'nullable|string|max:600',
        ], ['phone.regex' => 'Enter a Kenyan mobile number, e.g. 0712 345 678.']);
        $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        if (strlen($data['phone']) === 9) $data['phone'] = '0' . $data['phone'];
        if (str_starts_with($data['phone'], '254')) $data['phone'] = '0' . substr($data['phone'], 3);
        return $data;
    }

    private function create(array $row, array $contact): object
    {
        $customer = Auth::guard('customer')->user();
        $row += [
            'reference' => Bookings::reference(),
            'customer_id' => $customer?->id,
            'customer_name' => trim($contact['name']),
            'customer_phone' => $contact['phone'],
            'customer_email' => $contact['email'] ?? null,
            'notes' => filled($contact['notes'] ?? null) ? trim($contact['notes']) : null,
            'status' => 'pending', 'payment_status' => 'unpaid',
            'created_at' => now(), 'updated_at' => now(),
        ];
        $id = Bookings::db()->table('bookings')->insertGetId($row);
        Bookings::log($id, 'requested', 'customer', $customer?->id, $row['customer_name']);

        $b = Bookings::db()->table('bookings')->where('id', $id)->first();
        if ($customer) {
            CustomerNotifier::send($customer->id, 'booking', 'Booking ' . $b->reference . ' requested',
                ($b->kind === 'viewing' ? 'Viewing of ' : 'Stay at ') . $b->property_title . ' · ' . Bookings::when($b), '/bookings/' . $b->reference, 'fa-regular fa-calendar-check');
        }
        return $b;
    }

    /** the stay's host account (listing lister_id) */
    private function airbnbAccount(object $stay): ?string
    {
        if (empty($stay->listing_id)) return null;
        return rescue(fn () => \Illuminate\Support\Facades\DB::connection('airbnb')->table('listings')->where('id', $stay->listing_id)->value('lister_id'), null, false);
    }

    private function tellHost(TalkSasaService $sms, object $b, string $text): void
    {
        if (!$b->business_phone) return;
        $r = rescue(fn () => $sms->send($b->business_phone, $text), ['success' => false], true);
        Bookings::log($b->id, 'sms', 'customer', null, 'Smart Market', (($r['success'] ?? false) ? 'Host told: ' : 'SMS to host failed: ') . $text);
    }

    /** the booking if this visitor may see it: a signed link, or signed in as the customer who booked */
    private function mine(Request $request, string $reference): object
    {
        $b = Bookings::ready() ? Bookings::db()->table('bookings')->where('reference', strtoupper($reference))->first() : null;
        abort_unless($b, 404);
        if ($request->hasValidSignature(false) || $request->hasValidSignature()) return $b;

        $customer = Auth::guard('customer')->user();
        $owns = $customer && ((int) $b->customer_id === (int) $customer->id
            || (strlen($k = Bookings::phoneKey($customer->phone_number)) === 9 && $k === Bookings::phoneKey($b->customer_phone)));
        if ($owns) return $b;

        abort($customer ? 404 : 403, 'Open this booking from the link you were given, or sign in.');
    }

    private function link(object $b): string
    {
        return URL::signedRoute('bookings.show', $b->reference);
    }
}
