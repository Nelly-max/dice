$properties = [
    [
        'id' => 1,
        'title' => 'Hills View Estate',
        'extra' => 'For Rent',
        'types' => ['1br', '2br'],
        'location' => 'Nairobi, Roysambu',
        'priceRange' => ['Ksh 20,000', 'Ksh 25,000'],
        'images' => ['H001VE001.png'],
        'bookmarkActive' => true,
    ],
];

return view('your-view', compact('properties'));
