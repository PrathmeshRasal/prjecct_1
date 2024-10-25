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
        Schema::create('filter_specification_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('filter_specification_id');
            $table->foreign('filter_specification_id')->references('id')->on('filter_specifications');
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter_specification_values');
    }
};
