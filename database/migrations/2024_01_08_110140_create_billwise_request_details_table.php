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
        Schema::create('billwise_request_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('idbillwise_requests');
            $table->bigInteger('idproduct_master');
            $table->bigInteger('idproduct_batch');
            $table->double('quantity', 12, 4);
            $table->double('quantity_sent', 12, 4);
            $table->double('quantity_received', 12, 4);
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
        Schema::dropIfExists('billwise_request_details');
    }
};
