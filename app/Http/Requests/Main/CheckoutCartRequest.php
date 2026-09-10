<?php

namespace App\Http\Requests\Main;

use App\Services\CartService;
use App\Services\GameProService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutCartRequest extends FormRequest
{
    /**
     * Every distinct brand currently in the cart, keyed by brand id. Built
     * once and reused by both rules() and passedValidation() so we only
     * query the cart once per request.
     */
    protected ?\Illuminate\Support\Collection $brands = null;

    protected function brands(CartService $cartService): \Illuminate\Support\Collection
    {
        if ($this->brands !== null) {
            return $this->brands;
        }

        $cart = $cartService->currentCart($this);
        $cart->load('items.product.brand');

        return $this->brands = $cart->items
            ->pluck('product.brand')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Each brand currently in the cart needs a player ID (and a server ID
     * too, if that brand is configured with `type: id+server`), submitted as
     * `account_id.{brand_id}` / `server_id.{brand_id}`.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CartService $cartService): array
    {
        $rules = [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|numeric',
            'payment_type' => 'required|in:automatic,manual',
            'payment_method' => 'required|string|max:255',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ];

        foreach ($this->brands($cartService) as $brand) {
            $type = $brand->settings['type'] ?? 'id';

            $rules["account_id.{$brand->id}"] = 'required|string';
            $rules["server_id.{$brand->id}"] = $type === 'id+server'
                ? 'required|string'
                : 'nullable|string';
        }

        return $rules;
    }

    /**
     * Custom error messages so the frontend can show which brand's ID is
     * missing/invalid instead of a generic "the account id field is required".
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->brands ?? [] as $brand) {
            $label = $brand->settings['label_id'] ?? 'ID';
            $messages["account_id.{$brand->id}.required"] = "Mohon isi {$label} untuk {$brand->name}.";
            $messages["server_id.{$brand->id}.required"] = "Mohon isi server untuk {$brand->name}.";
        }

        return $messages;
    }

    /**
     * Validate Mobile Legends style accounts against the game provider,
     * mirroring the single-product checkout's behaviour.
     */
    protected function passedValidation(): void
    {
        $cartService = app(CartService::class);
        $gameProService = app(GameProService::class);

        foreach ($this->brands($cartService) as $brand) {
            if (! Str::contains(strtolower($brand->name), 'mobile legend')) {
                continue;
            }

            $accountId = $this->input("account_id.{$brand->id}");
            $serverId = $this->input("server_id.{$brand->id}");

            $resolve = $gameProService->resolveAccount(
                game: 'mobilelegend',
                uid: $accountId,
                server: $serverId,
            );

            if (! $resolve['status']) {
                throw ValidationException::withMessages([
                    "account_id.{$brand->id}" => "ID/Server {$brand->name} tidak valid.",
                ]);
            }
        }
    }
}
