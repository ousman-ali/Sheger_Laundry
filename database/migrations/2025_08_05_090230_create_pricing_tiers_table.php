<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cloth_item_id');
            $table->unsignedBigInteger('service_id');
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->unique(['cloth_item_id', 'service_id']);
            $table->foreign('cloth_item_id')->references('id')->on('cloth_items')->onDelete('restrict');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};