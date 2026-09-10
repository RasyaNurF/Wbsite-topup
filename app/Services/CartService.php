<?php

namespace App\Services;

use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Name of the cookie used to keep track of a guest's cart across requests.
     */
    public const COOKIE_NAME = 'cart_token';

    /**
     * Get the cart for the current request, creating one if needed.
     *
     * Logged in users always get the cart tied to their account. Guests are
     * tracked via a long-lived, random cookie so the cart survives even if
     * their PHP session expires.
     */
    public function currentCart(Request $request): Cart
    {
        if ($user = $request->user()) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        $token = $request->cookie(self::COOKIE_NAME);

        if (! $token) {
            $token = (string) Str::uuid();
            Cookie::queue(self::COOKIE_NAME, $token, 60 * 24 * 30); // 30 days
        }

        return Cart::firstOrCreate([
            'session_id' => $token,
            'user_id' => null,
        ]);
    }

    /**
     * Add a product to the cart, or increase its quantity if it's already there.
     */
    public function addItem(Cart $cart, PPOBProduct $product, int $quantity = 1): CartItem
    {
        $item = $cart->items()->where('p_p_o_b_product_id', $product->id)->first();

        if ($item) {
            $item->update(['quantity' => $item->quantity + $quantity]);

            return $item;
        }

        return $cart->items()->create([
            'p_p_o_b_product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->sell_price,
        ]);
    }

    /**
     * Set the exact quantity of an item. Removes it if quantity <= 0.
     */
    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $quantity]);
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * Merge a guest cart into a user's cart, e.g. right after login/register.
     * Quantities are summed for products that exist in both carts.
     */
    public function mergeCarts(Cart $guestCart, Cart $userCart): void
    {
        if ($guestCart->is($userCart)) {
            return;
        }

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()->where('p_p_o_b_product_id', $guestItem->p_p_o_b_product_id)->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $guestItem->quantity]);
            } else {
                $userCart->items()->create([
                    'p_p_o_b_product_id' => $guestItem->p_p_o_b_product_id,
                    'quantity' => $guestItem->quantity,
                    'price' => $guestItem->price,
                ]);
            }
        }

        $guestCart->delete();
    }

    /**
     * Lightweight summary (count + total) safe to share on every Inertia response.
     */
    public function summary(Request $request): array
    {
        $cart = $this->currentCart($request);

        return [
            'count' => (int) $cart->items()->sum('quantity'),
            'total' => (int) $cart->items()->selectRaw('COALESCE(SUM(quantity * price), 0) as total')->value('total'),
        ];
    }

    /**
     * Cart items formatted for the frontend, with the product/brand data the
     * cart page and mini cart need to render each row.
     */
    public function itemsForDisplay(Cart $cart): array
    {
        $cart->load(['items.product.brand']);

        $items = $cart->items->map(function (CartItem $item) {
            $product = $item->product;

            if (! $product) {
                return null;
            }

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->price * $item->quantity,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sell_price' => $product->sell_price,
                    'status' => $product->status,
                    'image' => $product->getFirstMediaUrl('image') ?: $product->brand?->getFirstMediaUrl('image'),
                    'brand' => $product->brand ? [
                        'name' => $product->brand->name,
                        'slug' => $product->brand->slug,
                    ] : null,
                ],
            ];
        })->filter()->values()->all();

        return [
            'items' => $items,
            'total' => array_sum(array_column($items, 'subtotal')),
        ];
    }
}
