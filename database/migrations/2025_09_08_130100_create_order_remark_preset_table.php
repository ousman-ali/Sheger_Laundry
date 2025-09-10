<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_remark_preset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('remark_preset_id')->constrained('remark_presets')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['order_id','remark_preset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_remark_preset');
    }
};
