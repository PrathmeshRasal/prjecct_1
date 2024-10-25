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
        Schema::create('user_product_found_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('product_unique')->unique();
            $table->unsignedBigInteger('is_found_count')->default(0);
            $table->unsignedBigInteger('is_not_found_count')->default(0);
            $table->timestamps();
            $table->foreign('product_unique')->references('product_unique')->on('product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_product_found_feedbacks');
    }
};
