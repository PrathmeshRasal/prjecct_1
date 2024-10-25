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
        Schema::create('product_specifications_value', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('product_unique'); // max 16,777,215
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('product_spec_id');
            $table->string('spec_key', 3000);
            $table->string('spec_value', 3000);
            $table->boolean('is_active')->default(0);
            $table->timestamps();
            // foreign key
            $table->foreign('product_unique')->references('product_unique')->on('product');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            $table->foreign('product_spec_id')->references('id')->on('product_specifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specifications_value');
    }
};
