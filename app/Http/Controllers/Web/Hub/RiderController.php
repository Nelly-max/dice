<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


use App\Models\Hub\Rider;
use App\Models\Hub\RiderApplication;
use App\Models\Hub\RiderPersonalDetail;
use App\Models\Hub\RiderCarDetail;
use App\Models\Customer\Delivery;
use App\Models\Customer\DeliveryRiderRequest;

use App\Models\Hub\RiderLocation;

use App\Models\Subdivision;

class RiderController extends Controller
{
    private function rider(): Rider
    {
        return Rider::where(
            'customer_id',
            Auth::guard('customer')->id()
        )->firstOrFail();
    }

    public function index()
    {
        
    }


public function riderAccount()
{
    $customerId = auth('customer')->id();

    $rider = Rider::where('customer_id', $customerId)
        ->first();

    return view('Hub.rider', compact('rider'));
}




    public function editRider()
    {
        $customerId = auth('customer')->id();

        /*
        |--------------------------------------------------------------------------
        | Get rider belonging to the logged-in customer
        |--------------------------------------------------------------------------
        */
        $rider = Rider::where('customer_id', $customerId)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Show edit page
        |--------------------------------------------------------------------------
        */
        return view('Hub.editRider', compact('rider'));
    }





public function updateRider(Request $request)
{
    $customerId = auth('customer')->id();

    /*
    |--------------------------------------------------------------------------
    | Get authenticated customer's rider
    |--------------------------------------------------------------------------
    */
    $rider = Rider::where('customer_id', $customerId)
        ->firstOrFail();


    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */
    $validated = $request->validate([

        'name' => [
            'required',
            'string',
            'max:50',
            'regex:/^[A-Za-z0-9 ]+$/',
        ],

        'phone' => [
            'required',
            'string',
            'max:20',
        ],

        'email' => [
            'required',
            'email',
            'max:255',
        ],

        'national_id' => [
            'required',
            'string',
            'max:20',
        ],

        'license_number' => [
            'nullable',
            'string',
            'max:50',
        ],

        'date_of_birth' => [
            'required',
            'date',
            'before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'after_or_equal:' . now()->subYears(80)->format('Y-m-d'),
        ],

        'mpesa_number' => [
            'required',
            'string',
            'max:20',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Update rider
    |--------------------------------------------------------------------------
    */
    $rider->update([

        'name' => $validated['name'],

        'phone' => $validated['phone'],

        'email' => $validated['email'],

        'national_id' => $validated['national_id'],

        'license_number' => $validated['license_number'] ?? null,

        'date_of_birth' => $validated['date_of_birth'],

        'mpesa_number' => $validated['mpesa_number'],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */
    return redirect()
        ->route('hub.account.rider')
        ->with('success', 'Rider details updated successfully.');
}

    


    public function viewApplication()
    {
        $customerId = auth('customer')->id();


        /*
        |--------------------------------------------------------------------------
        | Rider
        |--------------------------------------------------------------------------
        */
        $rider = Rider::where('customer_id', $customerId)
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Personal Documents
        |--------------------------------------------------------------------------
        */
        $personalDetails = RiderPersonalDetail::where('rider_id', $rider->id)
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Vehicle Details
        |--------------------------------------------------------------------------
        */
        $carDetails = RiderCarDetail::where('rider_id', $rider->id)
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Locality
        |--------------------------------------------------------------------------
        */
        $counties = DB::table('physical_counties')
            ->orderBy('name')
            ->get();

        $town = $rider->town_id
            ? DB::table('physical_towns')
                ->where('id', $rider->town_id)
                ->first()
            : null;

        $place = $rider->place_id
            ? DB::table('physical_places')
                ->where('id', $rider->place_id)
                ->first()
            : null;


        return view('Hub.viewRiderApplication', compact(
            'rider',
            'personalDetails',
            'carDetails',
            'counties',
            'town',
            'place'
        ));


    }


    public function riderApplication()
    {

        $subdivisions = Subdivision::where('status', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $counties = DB::table('physical_counties')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('Hub.riderApplication', compact('subdivisions', 'counties'));
    }

    public function apply(Request $request)
    {

        $validated = $request->validate([

            // Rider details
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'national_id' => 'required|string|max:30',
            'license_number' => 'nullable|string',
            'date_of_birth' => 'nullable|date',


            // Locality
            'county_id' => 'required',
            'town_id' => 'required',
            'place_id' => 'nullable',


            // Payment
            'mpesa_number' => 'required|string|max:20',


            // Vehicle
            'vehicle_type' => 'required|in:motorcycle,car,bicycle,van,truck',
            'vehicle_make' => 'required|string',
            'vehicle_model' => 'required|string',
            'plate_number' => 'required|string',


            // Documents
            'passport_photo' => 'nullable|image|max:2048',
            'id_front_photo' => 'required|image|max:2048',
            'id_back_photo' => 'required|image|max:2048',
            'license_photo' => 'nullable|image|max:2048',


            // Vehicle images
            'front_photo' => 'required|image|max:2048',
            'side_photo' => 'required|image|max:2048',
            'back_photo' => 'required|image|max:2048',
            'logbook_photo' => 'nullable|image|max:2048',

        ]);



        DB::connection('customer')->transaction(function () use ($request) {


            /*
            |--------------------------------------------------------------------------
            | Generate Rider Account
            |--------------------------------------------------------------------------
            */

            $riderAccount = $this->generateRiderAccount();



            /*
            |--------------------------------------------------------------------------
            | Create Rider
            |--------------------------------------------------------------------------
            */

            $customer = Auth::guard('customer')->user();

            $rider = Rider::create([

                'customer_id' => $customer->id,

                'rider_account' => $riderAccount,

                'national_id' => $request->national_id,

                'license_number' => $request->license_number,

                'name' => $request->name,

                'date_of_birth' => $request->date_of_birth,

                'phone' => $request->phone,

                'email' => $request->email,

                'mpesa_number' => $request->mpesa_number,

                'county_id' => $request->county_id,

                'town_id' => $request->town_id,

                'place_id' => $request->place_id,

                'account_status' => 'pending',

            ]);




            /*
            |--------------------------------------------------------------------------
            | Rider Media Root
            |--------------------------------------------------------------------------
            */


            $mediaFolder = config('app.media_root')
                . "/img/hub/riders/{$rider->rider_account}";




            /*
            |--------------------------------------------------------------------------
            | Personal Documents
            |--------------------------------------------------------------------------
            */


            $passport = null;

            $identificationFolder = $mediaFolder . "/identification";

            $passportFolder = $mediaFolder . "/passport";


            File::ensureDirectoryExists($identificationFolder);

            File::ensureDirectoryExists($passportFolder);



            /*
            |--------------------------------------------------------------------------
            | Passport
            |--------------------------------------------------------------------------
            */


            if($request->hasFile('passport_photo')) {


                $file = $request->file('passport_photo');

                $extension = $file->getClientOriginalExtension();


                $filename =
                    "{$rider->rider_account}_passport.{$extension}";


                $file->move(
                    $passportFolder,
                    $filename
                );


                $passport =
                    "img/hub/riders/{$rider->rider_account}/passport/{$filename}";

            }




            /*
            |--------------------------------------------------------------------------
            | ID Front
            |--------------------------------------------------------------------------
            */


            $file = $request->file('id_front_photo');

            $extension = $file->getClientOriginalExtension();


            $filename =
                "{$rider->rider_account}_id_front.{$extension}";


            $file->move(
                $identificationFolder,
                $filename
            );


            $idFront =
                "img/hub/riders/{$rider->rider_account}/identification/{$filename}";




            /*
            |--------------------------------------------------------------------------
            | ID Back
            |--------------------------------------------------------------------------
            */


            $file = $request->file('id_back_photo');

            $extension = $file->getClientOriginalExtension();


            $filename =
                "{$rider->rider_account}_id_back.{$extension}";


            $file->move(
                $identificationFolder,
                $filename
            );


            $idBack =
                "img/hub/riders/{$rider->rider_account}/identification/{$filename}";





            /*
            |--------------------------------------------------------------------------
            | Licence
            |--------------------------------------------------------------------------
            */


            $license = null;


            if($request->hasFile('license_photo')) {


                $file = $request->file('license_photo');

                $extension = $file->getClientOriginalExtension();


                $filename =
                    "{$rider->rider_account}_license.{$extension}";


                $file->move(
                    $identificationFolder,
                    $filename
                );


                $license =
                    "img/hub/riders/{$rider->rider_account}/identification/{$filename}";

            }





            RiderPersonalDetail::create([

                'rider_id' => $rider->id,

                'passport_photo' => $passport,

                'id_front_photo' => $idFront,

                'id_back_photo' => $idBack,

                'license_photo' => $license,

            ]);






            /*
            |--------------------------------------------------------------------------
            | Vehicle Images
            |--------------------------------------------------------------------------
            */


            $vehicleFolder = $mediaFolder . "/vehicle";


            File::ensureDirectoryExists($vehicleFolder);



            $vehicleImages = [];



            foreach([

                'front_photo',
                'side_photo',
                'back_photo'

            ] as $photo) {



                $file = $request->file($photo);


                $extension = $file->getClientOriginalExtension();


                $filename =
                    "{$rider->rider_account}_{$photo}.{$extension}";



                $file->move(
                    $vehicleFolder,
                    $filename
                );



                $vehicleImages[$photo] =
                    "img/hub/riders/{$rider->rider_account}/vehicle/{$filename}";


            }





            /*
            |--------------------------------------------------------------------------
            | Logbook
            |--------------------------------------------------------------------------
            */


            $logbook = null;



            if($request->hasFile('logbook_photo')) {


                $logbookFolder = $mediaFolder . "/logbook";


                File::ensureDirectoryExists($logbookFolder);



                $file = $request->file('logbook_photo');


                $extension = $file->getClientOriginalExtension();


                $filename =
                    "{$rider->rider_account}_logbook.{$extension}";


                $file->move(
                    $logbookFolder,
                    $filename
                );


                $logbook =
                    "img/hub/riders/{$rider->rider_account}/logbook/{$filename}";

            }





            /*
            |--------------------------------------------------------------------------
            | Save Vehicle Details
            |--------------------------------------------------------------------------
            */


            RiderCarDetail::create([


                'rider_id' => $rider->id,


                'vehicle_type' => $request->vehicle_type,


                'vehicle_make' => $request->vehicle_make,


                'vehicle_model' => $request->vehicle_model,


                'plate_number' => strtoupper($request->plate_number),


                'front_photo' => $vehicleImages['front_photo'],


                'side_photo' => $vehicleImages['side_photo'],


                'back_photo' => $vehicleImages['back_photo'],


                'logbook_photo' => $logbook,


            ]);



        });



        return redirect()
            ->back()
            ->with(
                'success',
                'Your rider application has been submitted successfully.'
            );

    }

    private function generateRiderAccount()
    {

        /*
        |--------------------------------------------------------------------------
        | Reserved Rider Prefixes
        |--------------------------------------------------------------------------
        */

        $prefixes = [

            'Q', 'R', 'G', 'N', 'M', 'T', 'K', 'P', 'S', 'V',

        ];



        /*
        |--------------------------------------------------------------------------
        | Suffix Letters
        |--------------------------------------------------------------------------
        */

        $suffixes = [

            'A', 'B', 'C', 'D', 'E', 'F', 'G', 
            'H', 'J', 'K', 'L', 'M', 'N', 'P', 
            'Q', 'R', 'S', 'T','U', 'V', 'W', 
            'X', 'Y', 'Z',

        ];



        /*
        |--------------------------------------------------------------------------
        | Get Latest Rider Account
        |--------------------------------------------------------------------------
        */

        $lastRider = Rider::whereNotNull('rider_account')
            ->orderBy('id', 'desc')
            ->first();



        /*
        |--------------------------------------------------------------------------
        | First Rider
        |--------------------------------------------------------------------------
        */

        if (!$lastRider) {

            return 'R001A';

        }



        preg_match(
            '/([A-Z])(\d{3})([A-Z])/',
            $lastRider->rider_account,
            $matches
        );


        $prefix = $matches[1];

        $number = (int)$matches[2];

        $suffix = $matches[3];



        /*
        |--------------------------------------------------------------------------
        | Increase Number
        |--------------------------------------------------------------------------
        */

        $number++;



        /*
        |--------------------------------------------------------------------------
        | If Number exceeds 999
        |--------------------------------------------------------------------------
        */

        if ($number > 999) {


            $number = 1;


            $suffixIndex = array_search(
                $suffix,
                $suffixes
            );


            /*
            |--------------------------------------------------------------------------
            | Move to next suffix
            |--------------------------------------------------------------------------
            */

            if(isset($suffixes[$suffixIndex + 1])){


                $suffix = $suffixes[$suffixIndex + 1];


            } 
            
            else {


                /*
                |--------------------------------------------------------------------------
                | Suffix finished
                | Move to next prefix automatically
                |--------------------------------------------------------------------------
                */

                $prefixIndex = array_search(
                    $prefix,
                    $prefixes
                );



                if(isset($prefixes[$prefixIndex + 1])){


                    $prefix = $prefixes[$prefixIndex + 1];

                    $suffix = 'A';


                }
                else {


                    throw new \Exception(
                        'All rider account prefixes have been exhausted.'
                    );

                }

            }

        }



        return $prefix
            . str_pad(
                $number,
                3,
                '0',
                STR_PAD_LEFT
            )
            . $suffix;

    }



    public function pendingRequest()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {

            return response()->json([
                'success' => false,
                'message' => 'No customer logged in'
            ]);

        }


        $rider = Rider::where(
            'customer_id',
            $customer->id
        )->first();


        if (!$rider) {

            return response()->json([
                'success' => false,
                'message' => 'Rider profile not found'
            ]);

        }


        $request = DeliveryRiderRequest::with('delivery')
            ->where('rider_id', $rider->id)
            ->where('status', 'pending')
            ->latest()
            ->first();


        if (!$request) {

            return response()->json([
                'success' => false
            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | Make sure the delivery exists
        |--------------------------------------------------------------------------
        */

        if (!$request->delivery) {

            return response()->json([
                'success' => false,
                'message' => 'Delivery not found'
            ]);

        }


        return response()->json([

            'success' => true,

            'request' => [

                /*
                |--------------------------------------------------------------------------
                | Request ID
                |--------------------------------------------------------------------------
                */

                'id' =>
                    $request->id,


                /*
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | Actual deliveries.id
                |--------------------------------------------------------------------------
                */

                'delivery_id' =>
                    $request->delivery_id,


                /*
                |--------------------------------------------------------------------------
                | Request status
                |--------------------------------------------------------------------------
                */

                'status' =>
                    $request->status,


                /*
                |--------------------------------------------------------------------------
                | Pickup details
                |--------------------------------------------------------------------------
                */

                'pickup_store' =>
                    $request->delivery->pickup_name,


                /*
                |--------------------------------------------------------------------------
                | Delivery distance
                |--------------------------------------------------------------------------
                */

                'distance' =>
                    $request->delivery->distance_km,


                /*
                |--------------------------------------------------------------------------
                | Delivery ETA
                |--------------------------------------------------------------------------
                */

                'eta' =>
                    $request->delivery->eta_minutes,


                /*
                |--------------------------------------------------------------------------
                | Expiry
                |--------------------------------------------------------------------------
                */

                'expires_at' =>
                    $request->expires_at

            ]

        ]);
    }


    public function acceptDelivery($id)
    {

        $customer = Auth::guard('customer')->user();


        if (!$customer) {

            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ]);

        }



        $rider = Rider::where('customer_id', $customer->id)
            ->first();



        if (!$rider) {

            return response()->json([
                'success' => false,
                'message' => 'Rider not found'
            ]);

        }



        DB::transaction(function () use ($id, $rider, &$delivery) {


            /*
            |--------------------------------------------------------------------------
            | Find request
            |--------------------------------------------------------------------------
            */

            $request = DeliveryRiderRequest::where('id', $id)
                ->where('rider_id', $rider->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();



            /*
            |--------------------------------------------------------------------------
            | Update rider request
            |--------------------------------------------------------------------------
            */

            $request->update([

                'status' => 'accepted',

                'responded_at' => now()

            ]);



            /*
            |--------------------------------------------------------------------------
            | Assign delivery
            |--------------------------------------------------------------------------
            */

            $delivery = Delivery::where('id', $request->delivery_id)
                ->lockForUpdate()
                ->firstOrFail();



            $delivery->update([

                'rider_account' => $rider->rider_account,

                'dispatch_status' => 'assigned',

                'status' => 'rider_assigned',

                'assigned_at' => now()

            ]);



        });



        return response()->json([

            'success' => true,

            'message' => 'Delivery accepted'

        ]);

    }


    /**
     * Get the rider's currently active delivery.
     *
     * A delivery is considered active when it belongs to this rider
     * and has not been delivered, completed, or canceled.
     */

    public function activeDelivery()
    {
        $rider = $this->rider();


        /*
        |--------------------------------------------------------------------------
        | Find rider's active delivery
        |--------------------------------------------------------------------------
        */

        $delivery = Delivery::where(
            'rider_account',
            $rider->rider_account
        )
        ->whereNotIn(
            'status',
            [
                'delivered',
                'completed',
                'canceled'
            ]
        )
        ->latest('assigned_at')
        ->first();


        /*
        |--------------------------------------------------------------------------
        | No active delivery
        |--------------------------------------------------------------------------
        */

        if (!$delivery) {

            return response()->json([

                'success' =>
                    true,

                'active' =>
                    false,

                'delivery' =>
                    null

            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | Active delivery
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'active' =>
                true,

            'delivery' => [

                'id' =>
                    $delivery->id,

                'status' =>
                    $delivery->status,

                'delivery_number' =>
                    $delivery->delivery_number,

            ],

            'navigate_url' =>
                route(
                    'rider.navigate',
                    [
                        'delivery' =>
                            $delivery->id
                    ]
                )

        ]);

    }


    public function arrivedAtShop(Request $request, Delivery $delivery)
    {
        /*
        |--------------------------------------------------------------------------
        | Configuration
        |--------------------------------------------------------------------------
        */

        $arrivalRadiusMeters = 100;


        /*
        |--------------------------------------------------------------------------
        | Authenticated Rider
        |--------------------------------------------------------------------------
        */

        $rider = $this->rider();


        if (!$rider) {

            return response()->json([
                'success' => false,
                'message' => 'Rider account not found.'
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        if (
            $delivery->rider_account !==
            $rider->rider_account
        ) {

            return response()->json([
                'success' => false,
                'message' => 'This delivery is not assigned to you.'
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Delivery State Guard
        |--------------------------------------------------------------------------
        |
        | According to the deliveries migration:
        |
        | rider_assigned
        |      ↓
        | rider_arrived_shop
        |
        */

        if (
            $delivery->status !==
            'rider_assigned'
        ) {

            /*
            * Idempotent response if the rider
            * has already been marked as arrived.
            */

            if (
                $delivery->status ===
                'rider_arrived_shop'
            ) {

                return response()->json([
                    'success' => true,
                    'message' => 'Rider is already at the shop.',
                    'status' => $delivery->status,
                    'arrived_shop_at' =>
                        $delivery->arrived_shop_at
                            ? $delivery->arrived_shop_at
                                ->toIso8601String()
                            : null,
                ]);
            }


            return response()->json([
                'success' => false,
                'message' =>
                    'This delivery cannot be marked as arrived.',
                'current_status' =>
                    $delivery->status
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Pickup Coordinates
        |--------------------------------------------------------------------------
        */

        $pickupLatitude =
            (float) $delivery->pickup_latitude;

        $pickupLongitude =
            (float) $delivery->pickup_longitude;


        if (
            !is_finite($pickupLatitude) ||
            !is_finite($pickupLongitude)
        ) {

            Log::error(
                "Invalid pickup coordinates for Delivery ID {$delivery->id}.",
                [
                    'pickup_latitude' =>
                        $delivery->pickup_latitude,

                    'pickup_longitude' =>
                        $delivery->pickup_longitude,
                ]
            );


            return response()->json([
                'success' => false,
                'message' =>
                    'Shop location is unavailable.'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Latest Rider Location
        |--------------------------------------------------------------------------
        */

        $riderLocation =
            RiderLocation::where(
                'rider_id',
                $rider->id
            )
            ->latest('last_seen')
            ->first();


        if (!$riderLocation) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Rider location is unavailable. Please wait for GPS to update.'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Rider Coordinates
        |--------------------------------------------------------------------------
        */

        $riderLatitude =
            (float) $riderLocation->latitude;

        $riderLongitude =
            (float) $riderLocation->longitude;


        if (
            !is_finite($riderLatitude) ||
            !is_finite($riderLongitude)
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Valid rider location is unavailable.'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate Distance
        |--------------------------------------------------------------------------
        |
        | Haversine formula.
        |
        */

        $earthRadiusMeters = 6371000;


        $latitudeDifference =
            deg2rad(
                $pickupLatitude -
                $riderLatitude
            );


        $longitudeDifference =
            deg2rad(
                $pickupLongitude -
                $riderLongitude
            );


        $riderLatitudeRadians =
            deg2rad(
                $riderLatitude
            );


        $pickupLatitudeRadians =
            deg2rad(
                $pickupLatitude
            );


        $a =
            sin(
                $latitudeDifference / 2
            ) ** 2
            +
            cos(
                $riderLatitudeRadians
            )
            *
            cos(
                $pickupLatitudeRadians
            )
            *
            sin(
                $longitudeDifference / 2
            ) ** 2;


        $c =
            2 *
            atan2(
                sqrt($a),
                sqrt(1 - $a)
            );


        $distanceMeters =
            $earthRadiusMeters * $c;


        $distanceMeters =
            round(
                $distanceMeters,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Arrival Radius Guard
        |--------------------------------------------------------------------------
        */

        if (
            $distanceMeters >
            $arrivalRadiusMeters
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not close enough to the shop yet.',
                'distance' =>
                    round($distanceMeters),
                'required_distance' =>
                    $arrivalRadiusMeters,
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Register Arrival
        |--------------------------------------------------------------------------
        */

        try {

            DB::connection('customer')
                ->transaction(function () use ($delivery) {

                    $delivery->refresh();


                    /*
                    * Protect against simultaneous requests.
                    */

                    if (
                        $delivery->status !==
                        'rider_assigned'
                    ) {
                        return;
                    }


                    $delivery->update([

                        'status' =>
                            'rider_arrived_shop',

                        'arrived_shop_at' =>
                            now(),

                    ]);
                });


            /*
            |--------------------------------------------------------------------------
            | Reload Persisted Delivery
            |--------------------------------------------------------------------------
            */

            $delivery->refresh();


            /*
            |--------------------------------------------------------------------------
            | Final State Check
            |--------------------------------------------------------------------------
            */

            if (
                $delivery->status !==
                'rider_arrived_shop'
            ) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'The delivery status changed before arrival could be registered.',
                    'current_status' =>
                        $delivery->status,
                ], 409);
            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' =>
                    true,

                'message' =>
                    'Rider has arrived at the shop.',

                'status' =>
                    $delivery->status,

                'arrived_shop_at' =>
                    $delivery->arrived_shop_at
                        ? $delivery->arrived_shop_at
                            ->toIso8601String()
                        : null,

                'distance' =>
                    round($distanceMeters),

            ]);


        } catch (\Throwable $e) {

            Log::error(
                "Failed to register rider arrival for Delivery ID {$delivery->id}.",
                [
                    'error' =>
                        $e->getMessage(),

                    'rider_id' =>
                        $rider->id,

                    'rider_account' =>
                        $rider->rider_account,

                    'delivery_id' =>
                        $delivery->id,
                ]
            );


            return response()->json([
                'success' => false,
                'message' =>
                    'An error occurred while updating arrival status.'
            ], 500);
        }
    }


    public function pickupDelivery(Request $request, Delivery $delivery)
    {
        /*
        |--------------------------------------------------------------------------
        | Get authenticated rider
        |--------------------------------------------------------------------------
        */
        $rider = $this->rider();


        /*
        |--------------------------------------------------------------------------
        | Make sure this delivery belongs to this rider
        |--------------------------------------------------------------------------
        */
        if (
            $delivery->rider_account !==
            $rider->rider_account
        ) {

            return response()->json([
                'success' => false,
                'message' => 'This delivery is not assigned to you.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Delivery must be dispatched
        |--------------------------------------------------------------------------
        */
        if (
            strtolower($delivery->status) !==
            'dispatched'
        ) {

            return response()->json([
                'success' => false,
                'message' => 'This delivery cannot be picked up in its current status.',
                'status' => $delivery->status,
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Optional rider location
        |--------------------------------------------------------------------------
        */
        $latitude =
            $request->input('latitude');

        $longitude =
            $request->input('longitude');


        /*
        |--------------------------------------------------------------------------
        | Update delivery
        |--------------------------------------------------------------------------
        |
        | dispatched
        |      ↓
        | picked_up
        |
        */
        DB::transaction(function () use (
            $delivery,
            $latitude,
            $longitude
        ) {

            $delivery->status =
                'picked_up';

            $delivery->picked_up_at =
                now();


            /*
            * Store the rider's current location
            * only if it was supplied.
            *
            * This is optional and does not require
            * additional columns in deliveries.
            */
            $delivery->save();
        });


        /*
        |--------------------------------------------------------------------------
        | Refresh model
        |--------------------------------------------------------------------------
        */
        $delivery->refresh();


        /*
        |--------------------------------------------------------------------------
        | Return updated delivery state
        |--------------------------------------------------------------------------
        */
        return response()->json([

            'success' => true,

            'message' =>
                'Delivery picked up successfully.',

            'status' =>
                $delivery->status,

            'delivery' => [

                'id' =>
                    $delivery->id,

                'delivery_number' =>
                    $delivery->delivery_number,

                'status' =>
                    $delivery->status,

                'picked_up_at' =>
                    optional(
                        $delivery->picked_up_at
                    )->toISOString(),

                'dropoff_latitude' =>
                    $delivery->dropoff_latitude,

                'dropoff_longitude' =>
                    $delivery->dropoff_longitude,

            ],
        ]);
    }

    
    public function startDelivery(Request $request, Delivery $delivery)
    {
        $rider = $this->rider();

        /*
        * Make sure this delivery belongs to
        * the currently authenticated rider.
        */
        if ($delivery->rider_account !== $rider->rider_account) {
            return response()->json([
                'success' => false,
                'message' => 'This delivery is not assigned to you.',
            ], 403);
        }

        /*
        * Delivery can only be started after
        * the rider has picked it up.
        */
        if ($delivery->status !== 'picked_up') {
            return response()->json([
                'success' => false,
                'message' => 'Delivery can only be started after pickup.',
                'status' => $delivery->status,
            ], 422);
        }

        /*
        * Update delivery status.
        */
        $delivery->status = 'on_transit';
        $delivery->save();

        return response()->json([
            'success' => true,
            'message' => 'Delivery started successfully.',
            'status' => $delivery->status,

            'delivery' => [
                'id' => $delivery->id,
                'delivery_number' => $delivery->delivery_number,

                'dropoff_latitude' =>
                    $delivery->dropoff_latitude,

                'dropoff_longitude' =>
                    $delivery->dropoff_longitude,

                'customer_name' =>
                    $delivery->customer_name,

                'customer_phone' =>
                    $delivery->customer_phone,
            ],
        ]);
    }
}
