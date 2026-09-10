<?php

use App\Models\Order\Order;
use App\Models\PPOB\PPOBProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An order created directly from a product page still uses the single
     * `p_p_o_b_product_id` column on `orders`. An order checked out from the
     * cart instead has its line items stored here, with `p_p_o_b_product_id`
     * left null on the parent order.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Order::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PPOBProduct::class)->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            // Snapshot of the product price at checkout time.
            $table->unsignedBigInteger('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
