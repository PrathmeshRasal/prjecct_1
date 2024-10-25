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
        Schema::create('product_variant_size', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('product_unique');
            $table->string('product_height_inch');
            $table->string('product_height_mm');
            $table->string('product_width_inch');
            $table->string('product_width_mm');
            $table->string('product_length_inch');
            $table->string('product_length_mm');
            $table->string('product_weight');
            $table->timestamps();
            $table->foreign('product_unique')->references('product_unique')->on('product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_size');
    }
};
