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
        Schema::create('inventory_threshold', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('idproduct_master');
            $table->bigInteger('idstore_warehouse');
            $table->double('threshold_quantity', 12, 4);
            $table->double('sent_quantity', 12, 4);
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
        Schema::dropIfExists('inventory_threshold');
    }
};