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
        Schema::create('support_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_id');
            $table->integer('created_by');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('support_id')->references('id')->on('supports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_details');
    }
};
