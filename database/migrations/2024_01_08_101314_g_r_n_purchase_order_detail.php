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
        Schema::create('grn_purchase_order_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('idgrn_purchase_order');
            $table->bigInteger('idproduct_master');
            $table->double('quantity', 12, 4);
            $table->string('expiry')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->mediumInteger('created_by')->nullable();
            $table->mediumInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_purchase_order_detail');
    }
};
