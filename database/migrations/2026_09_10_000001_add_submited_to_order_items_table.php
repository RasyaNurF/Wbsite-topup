<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cart checkouts can span several brands at once (e.g. ML + HOK diamonds
     * in the same order), and each brand needs its own player ID/server, so
     * that data is stored per line item rather than on the parent order.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->json('submited')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('submited');
        });
    }
};
