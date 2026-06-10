public function add(Request $request)
{
    $request->validate([
        'stockable_id' => 'required',
        'stockable_type' => 'required',
        'quantity' => 'required|integer|min:1',
    ]);

    // ==============================
    // GUEST USER → LOCAL STORAGE ONLY
    // ==============================
    if (!Auth::check()) {
        return response()->json([
            'success' => false,
            'guest_mode' => true,
            'message' => 'Stored locally in browser cart'
        ]);
    }

    // ==============================
    // LOGGED IN → DATABASE CART
    // ==============================
    $userId = Auth::id();

    $cart = Cart::where([
        'user_id' => $userId,
        'stockable_id' => $request->stockable_id,
        'stockable_type' => $request->stockable_type,
        'business_account' => $request->business_account,
    ])->first();

    if ($cart) {
        $cart->quantity += $request->quantity;
        $cart->save();
    } else {
        Cart::create([
            'user_id' => $userId,
            'business_account' => $request->business_account,
            'subdivision_code' => $request->subdivision_code,
            'sub_division_id' => $request->sub_division_id,
            'stockable_id' => $request->stockable_id,
            'stockable_type' => $request->stockable_type,
            'quantity' => $request->quantity,
            'shipment_type' => $request->shipment_type ?? 'quick',
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Added to cart',
        'cart_count' => Cart::where('user_id', $userId)->sum('quantity')
    ]);
}












function addToLocalCart(product) {

    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    let existing = cart.find(item =>
        item.stockable_id === product.stockable_id &&
        item.stockable_type === product.stockable_type &&
        item.business_account === product.business_account
    );

    if (existing) {
        existing.quantity += product.quantity;
    } else {
        cart.push(product);
    }

    localStorage.setItem('cart', JSON.stringify(cart));

    showNotification('Added to local cart', 'success');
}

public function syncLocalCart(Request $request)
{
    $userId = Auth::id();

    $items = $request->input('cart', []);

    foreach ($items as $item) {

        Cart::updateOrCreate([
            'user_id' => $userId,
            'stockable_id' => $item['stockable_id'],
            'stockable_type' => $item['stockable_type'],
            'business_account' => $item['business_account'],
        ], [
            'quantity' => DB::raw('quantity + ' . (int)$item['quantity']),
            'shipment_type' => $item['shipment_type'],
            'subdivision_code' => $item['subdivision_code'],
        ]);
    }

    return response()->json([
        'success' => true
    ]);
}