<?php

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
        // Index for brand status filtering (used in every home page query)
        Schema::table('p_p_o_b_brands', function (Blueprint $table) {
            $table->index('status');
            $table->index(['status', 'order']); // Composite index for filtered ordering
        });

        // Index for category status filtering
        Schema::table('p_p_o_b_categories', function (Blueprint $table) {
            $table->index('status');
        });

        // Index for slider status filtering
        Schema::table('sliders', function (Blueprint $table) {
            $table->index('status');
        });

        // Index for FAQ status filtering
        Schema::table('faqs', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('p_p_o_b_brands', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'order']);
        });

        Schema::table('p_p_o_b_categories', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('sliders', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
