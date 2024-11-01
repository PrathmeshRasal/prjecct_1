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
        Schema::create('machin_details', function (Blueprint $table) {
            $table->id();
            $table->string('description',1000)->nullable();
            $table->string('mach_no')->nullable();
            $table->string('mach_name')->nullable();
            $table->integer('servicer_id')->nullable();
            $table->integer('status')->default(1);
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machin_details');
    }
};
