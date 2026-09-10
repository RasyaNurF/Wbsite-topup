<?php

namespace App\Models\Order;

use App\Models\PPOB\PPOBProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
