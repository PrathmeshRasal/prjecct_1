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
        Schema::create('machine_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mach_detail_id');
            $table->string('image');
            $table->timestamps();

            $table->foreign('mach_detail_id')->references(columns: 'id')->on('machin_details');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_images');
    }
};
