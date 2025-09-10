<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_item_remark_preset', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('remark_preset_id');
            $table->timestamps();

            $table->unique(['order_item_id', 'remark_preset_id'], 'order_item_preset_unique');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('remark_preset_id')->references('id')->on('remark_presets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_remark_preset');
    }
};
