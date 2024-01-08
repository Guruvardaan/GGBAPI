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
        Schema::create('direct_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('idstore_warehouse_from')->nullable();
            $table->date('dispatch_date')->nullable();
            $table->bigInteger('dispatched_by')->nullable();
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
        Schema::dropIfExists('direct_transfer_requests');
    }
};
