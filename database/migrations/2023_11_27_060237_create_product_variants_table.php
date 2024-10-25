<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('product_unique');
            $table->string('machine_type');
            $table->string('company_name');
            $table->string('country');
            $table->string('product_type');
            $table->string('product_model');
            $table->string('industry_name');
            $table->string('rating')->default(0);
            $table->timestamps();
            $table->foreign('product_unique')->references('product_unique')->on('product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
