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
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('product_unique'); // max 16,777,215
            $table->unsignedBigInteger('product_variant_id');
            $table->string('specification_heading', 300);
            $table->boolean('is_active')->default(0);
            $table->timestamps();
            // foreign key
            $table->foreign('product_unique')->references('product_unique')->on('product');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};