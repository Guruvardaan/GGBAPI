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
        Schema::table('grn_purchase_order_detail', function (Blueprint $table) {
            $table->double('sent_quantity', 12, 4)->after('quantity');
            $table->tinyInteger('extra_product')->default(0);
            $table->tinyInteger('free_product')->default(0);
            $table->tinyInteger('expired_product')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grn_purchase_order_detail', function (Blueprint $table) {
            $table->dropColumn('sent_quantity');
            $table->dropColumn('extra_product');
            $table->dropColumn('free_product');
            $table->dropColumn('expired_product');
        });
    }
};
