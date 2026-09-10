<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Http\Requests\Main\AddCartItemRequest;
use App\Http\Requests\Main\CheckoutCartRequest;
use App\Http\Requests\Main\UpdateCartItemRequest;
use App\Actions\Main\StoreCartCheckoutAction;
use App\Models\Cart\CartItem;
use App\Models\PPOB\PPOBProduct;
use App\Services\CartService;
use App\Traits\WithReturnResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use WithReturnResponse;

    public function __construct(protected CartService $cartService) {}

    /**
     * Display the full cart page.
     */
    public function index(Request $request)
    {
        $cart = $this->cartService->currentCart($request);

        return inertia('main/Cart', $this->cartService->itemsForDisplay($cart));
    }

    /**
     * Lightweight JSON endpoint used by the mini cart / cart drawer so it can
     * load the current items without needing Inertia page props to exist on
     * every page it's mounted on.
     */
    public function items(Request $request)
    {
        $cart = $this->cartService->currentCart($request);

        return $this->responseWithSuccess($this->cartService->itemsForDisplay($cart));
    }

    /**
     * Add a product to the cart.
     */
    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $product = PPOBProduct::where('status', true)->findOrFail($request->input('product_id'));

        $cart = $this->cartService->currentCart($request);

        $this->cartService->addItem($cart, $product, (int) $request->input('quantity', 1));

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    /**
     * Update the quantity of a cart item.
     */
    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $this->cartService->updateQuantity($cartItem, (int) $request->input('quantity'));

        return back();
    }

    /**
     * Remove a single item from the cart.
     */
    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $this->cartService->removeItem($cartItem);

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    /**
     * Empty the whole cart.
     */
    public function clear(Request $request): RedirectResponse
    {
        $this->cartService->clear($this->cartService->currentCart($request));

        return back()->with('success', 'Keranjang dikosongkan.');
    }

    /**
     * Checkout every item currently in the cart into a single order.
     */
    public function checkout(CheckoutCartRequest $request, StoreCartCheckoutAction $action)
    {
        $cart = $this->cartService->currentCart($request);

        try {
            $order = DB::transaction(function () use ($cart, $request, $action) {
                return $action->handle($cart, $request->validated());
            });

            return to_route('transaction.show', ['order' => $order]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Make sure the current visitor actually owns this cart item before it can
     * be modified or deleted.
     */
    protected function authorizeCartItem(Request $request, CartItem $cartItem): void
    {
        $currentCart = $this->cartService->currentCart($request);

        abort_unless($cartItem->cart_id === $currentCart->id, 403);
    }
}
