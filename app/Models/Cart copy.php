//?php 

// app/Models/Cart.php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'subdivision_code',
        'stock_type',
        'stock_id',
        'quantity',
        'price',
        'product_name',
        'product_image',
        'business_name',
        'shipment_type'
    ];

    protected $casts = [
        'product_attributes' => 'array'
    ];

    // Get current user's cart
    public static function getCart()
    {
        if (auth()->check()) {
            return self::where('user_id', auth()->id())->get();
        } else {
            return self::where('session_id', session()->getId())->get();
        }
    }

    // Get cart count
    public static function countItems()
    {
        return self::getCart()->sum('quantity');
    }

    // Get cart total
    public static function getTotal()
    {
        return self::getCart()->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    // Group by shipment type
    public static function getGroupedCart()
    {
        $cart = self::getCart();
        return $cart->groupBy('shipment_type');
    }
}