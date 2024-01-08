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
        Schema::create('grn_purchase_order', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('idvendor');
            $table->bigInteger('idstore_warehouse');
            $table->double('total_quantity', 12, 4);
            $table->text('note1')->nullable();
            $table->text('note2')->nullable();
            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
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
        Schema::dropIfExists('grn_purchase_order');
    }
};
