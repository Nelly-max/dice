<?php

/*
|--------------------------------------------------------------------------
| Airbnb (short stays)
|--------------------------------------------------------------------------
|
| There is no Airbnb database yet, so the pages read their stays from here.
| The stays below are SAMPLE data to show how the pages work. While
| 'preview' is true the pages say so and booking is switched off.
|
| Each property lists its unit TYPES (studio, 1 bedroom …) with how many
| units of that type it has and how many are free. The site shows one card
| per unit type per property, never one card per unit. A unit type's id is
| the stay id in /airbnb/stay/{id}.
|
| Replace 'properties' with real data (or point StayController at a database)
| and set 'preview' => false when bookings open. (An older flat 'stays' list,
| one stay per entry, is still read if 'properties' is missing.)
|
| latitude / longitude are used for "stays within 500 m … 5 km of me".
| Images are paths under public/ (asset()) or full URLs.
|
*/

return [

    'preview' => env('AIRBNB_PREVIEW', true),

    'cleaning_fee' => 1000,     // KES, added once per booking

    'service_fee_percent' => 5,

    'types' => [
        'studio' => ['Studios', 'fa-solid fa-couch'],
        'one-bed' => ['1 bedroom', 'fa-solid fa-bed'],
        'two-bed' => ['2+ bedrooms', 'fa-solid fa-house-chimney'],
        'villa' => ['Villas & houses', 'fa-solid fa-house-chimney-window'],
        'room' => ['Private rooms', 'fa-solid fa-door-closed'],
    ],

    'properties' => [
        [
            'id' => 1, 'title' => 'Yaya Court Apartments', 'area' => 'Kilimani, Nairobi',
            'latitude' => -1.2921, 'longitude' => 36.7870,
            'host' => 'Wanjiru', 'superhost' => true, 'rating' => 4.8, 'reviews' => 152,
            'amenities' => ['Wi-Fi', 'Kitchen', 'Smart TV', 'Hot shower', 'Parking', 'Workspace', 'Lift'],
            'description' => 'Quiet, sunny apartments a short walk from Yaya Centre, with fast Wi-Fi, full kitchens and secure parking.',
            'units' => [
                ['id' => 1, 'type' => 'studio', 'name' => 'Studio', 'price' => 4500, 'guests' => 2, 'bedrooms' => 0, 'beds' => 1, 'baths' => 1, 'count' => 4, 'available' => 2,
                 'images' => ['img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/HOMECITY_KITCHEN_001.png', 'img/homeCity/HOMECITY_BATHROOM_001.jpeg', 'img/homeCity/HOMECITY_MAIN_IMG_001.jpg']],
                ['id' => 2, 'type' => 'one-bed', 'name' => '1 bedroom apartment', 'price' => 6500, 'guests' => 3, 'bedrooms' => 1, 'beds' => 2, 'baths' => 1, 'count' => 3, 'available' => 1,
                 'images' => ['img/homeCity/HOMECITY_MAIN_IMG_001.jpg', 'img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/HOMECITY_KITCHEN_001.png', 'img/homeCity/HOMECITY_BATHROOM_001.jpeg']],
                ['id' => 3, 'type' => 'two-bed', 'name' => '2 bedroom apartment', 'price' => 9000, 'guests' => 5, 'bedrooms' => 2, 'beds' => 3, 'baths' => 2, 'count' => 2, 'available' => 0,
                 'images' => ['img/homeCity/H001VE001.png', 'img/homeCity/HOMECITY_KITCHEN_001.png', 'img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/HOMECITY_BATHROOM_001.jpeg']],
            ],
        ],
        [
            'id' => 2, 'title' => 'Westlands Skyview', 'area' => 'Westlands, Nairobi',
            'latitude' => -1.2674, 'longitude' => 36.8108,
            'host' => 'Brian', 'superhost' => true, 'rating' => 4.9, 'reviews' => 112,
            'amenities' => ['Wi-Fi', 'Gym', 'Pool', 'Kitchen', 'Lift', 'Backup power'],
            'description' => 'Modern apartments on the upper floors with balconies over the city, a gym and a rooftop pool.',
            'units' => [
                ['id' => 4, 'type' => 'one-bed', 'name' => '1 bedroom with city view', 'price' => 7000, 'guests' => 2, 'bedrooms' => 1, 'beds' => 1, 'baths' => 1, 'count' => 6, 'available' => 3,
                 'images' => ['img/homeCity/HOMECITY_MAIN_IMG_001.jpg', 'img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/HOMECITY_KITCHEN_001.png', 'img/homeCity/HOMECITY_BATHROOM_001.jpeg']],
                ['id' => 5, 'type' => 'two-bed', 'name' => '2 bedroom penthouse', 'price' => 14000, 'guests' => 4, 'bedrooms' => 2, 'beds' => 2, 'baths' => 2, 'count' => 1, 'available' => 1,
                 'images' => ['img/homeCity/H001VE001.png', 'img/homeCity/HOMECITY_MAIN_IMG_001.jpg', 'img/homeCity/HOMECITY_KITCHEN_001.png', 'img/homeCity/HOMECITY_BEDROOM_001.jpg']],
            ],
        ],
        [
            'id' => 3, 'title' => 'Karen Garden Villas', 'area' => 'Karen, Nairobi',
            'latitude' => -1.3197, 'longitude' => 36.7076,
            'host' => 'Muthoni', 'superhost' => true, 'rating' => 5.0, 'reviews' => 21,
            'amenities' => ['Pool', 'Garden', 'Wi-Fi', 'BBQ', 'Parking', 'Security'],
            'description' => 'Private villas on a large garden with a pool, ideal for families and small groups.',
            'units' => [
                ['id' => 6, 'type' => 'villa', 'name' => '4 bedroom villa', 'price' => 22000, 'guests' => 8, 'bedrooms' => 4, 'beds' => 5, 'baths' => 4, 'count' => 2, 'available' => 1,
                 'images' => ['img/homeCity/HOMECITY_MAIN_IMG_001.jpg', 'img/homeCity/H001VE001.png', 'img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/HOMECITY_KITCHEN_001.png']],
            ],
        ],
        [
            'id' => 4, 'title' => 'South B Shared Homes', 'area' => 'South B, Nairobi',
            'latitude' => -1.3110, 'longitude' => 36.8350,
            'host' => 'Kevin', 'superhost' => false, 'rating' => 4.5, 'reviews' => 47,
            'amenities' => ['Wi-Fi', 'Shared kitchen', 'Hot shower', 'Laundry'],
            'description' => 'Tidy private rooms close to the CBD and the airport road, good for short work trips.',
            'units' => [
                ['id' => 7, 'type' => 'room', 'name' => 'Private room', 'price' => 2500, 'guests' => 1, 'bedrooms' => 1, 'beds' => 1, 'baths' => 1, 'count' => 5, 'available' => 4,
                 'images' => ['img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/HOMECITY_BATHROOM_001.jpeg', 'img/homeCity/HOMECITY_KITCHEN_001.png']],
                ['id' => 8, 'type' => 'studio', 'name' => 'Studio', 'price' => 3800, 'guests' => 2, 'bedrooms' => 0, 'beds' => 1, 'baths' => 1, 'count' => 2, 'available' => 2,
                 'images' => ['img/homeCity/HOMECITY_BATHROOM_001.jpeg', 'img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/HOMECITY_KITCHEN_001.png']],
            ],
        ],
        [
            'id' => 5, 'title' => 'Runda Park Townhouses', 'area' => 'Runda, Nairobi',
            'latitude' => -1.2180, 'longitude' => 36.8100,
            'host' => 'Otieno', 'superhost' => true, 'rating' => 4.9, 'reviews' => 17,
            'amenities' => ['Garden', 'Wi-Fi', 'Parking', 'Washer', 'Security', 'Workspace'],
            'description' => 'Quiet townhouses in a gated community, each with a private garden.',
            'units' => [
                ['id' => 9, 'type' => 'two-bed', 'name' => '3 bedroom townhouse', 'price' => 15000, 'guests' => 6, 'bedrooms' => 3, 'beds' => 4, 'baths' => 3, 'count' => 3, 'available' => 2,
                 'images' => ['img/homeCity/H001VE001.png', 'img/homeCity/HOMECITY_MAIN_IMG_001.jpg', 'img/homeCity/HOMECITY_KITCHEN_001.png']],
            ],
        ],
    ],
];
