<?php

namespace App\Models\Cart;

use App\Models\PPOB\PPOBProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'p_p_o_b_product_id',
        'quantity',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PPOBProduct::class, 'p_p_o_b_product_id');
    }

    public function getSubtotalAttribute(): int
    {
        return $this->price * $this->quantity;
    }
}
