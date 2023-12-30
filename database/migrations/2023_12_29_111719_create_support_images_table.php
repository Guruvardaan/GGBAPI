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
        Schema::create('support_images', function (Blueprint $table) {
            $table->id();
            $table->integer('support_id')->default(1);
            $table->unsignedBigInteger('support_detail_id');
            $table->string('image')->nullable();
            $table->timestamps();
            $table->foreign('support_detail_id')->references('id')->on('support_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_images');
    }
};
