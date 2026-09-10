<?php

namespace App\Listeners;

use App\Models\Cart\Cart;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cookie;

class MergeGuestCartOnLogin
{
    public function __construct(protected CartService $cartService) {}

    public function handle(Login $event): void
    {
        $token = request()->cookie(CartService::COOKIE_NAME);

        if (! $token) {
            return;
        }

        $guestCart = Cart::where('session_id', $token)->whereNull('user_id')->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $event->user->id]);

        $this->cartService->mergeCarts($guestCart, $userCart);

        Cookie::queue(Cookie::forget(CartService::COOKIE_NAME));
    }
}
