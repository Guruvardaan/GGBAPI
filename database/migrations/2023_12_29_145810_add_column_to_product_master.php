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
        Schema::table('product_master', function (Blueprint $table) {
            $table->string('manufacturer')->nullable();
            $table->string('shelf_life')->nullable();
            $table->string('unit')->nullable();
            $table->string('packaging_type')->nullable();
            $table->string('ingredients')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_master', function (Blueprint $table) {
            $table->dropColumn('manufacturer');
            $table->dropColumn('shelf_life');
            $table->dropColumn('unit');
            $table->dropColumn('packaging_type');
            $table->dropColumn('ingredients');
        });
    }
};
