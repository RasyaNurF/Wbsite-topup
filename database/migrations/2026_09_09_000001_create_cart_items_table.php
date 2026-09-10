<?php

use App\Models\Cart\Cart;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cart::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PPOBProduct::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            // Snapshot of the product price at the time it was added, so price
            // changes on the product don't silently change what's in the cart.
            $table->unsignedBigInteger('price');
            $table->timestamps();

            $table->unique(['cart_id', 'p_p_o_b_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
