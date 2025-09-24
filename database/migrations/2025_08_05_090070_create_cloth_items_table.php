<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloth_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('item_code')->unique();
            $table->string('name')->unique();
            $table->unsignedBigInteger('unit_id');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('clothing_group_id')->nullable();
            $table->timestamps();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloth_items');
    }
};