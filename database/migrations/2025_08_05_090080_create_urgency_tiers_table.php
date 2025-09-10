<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urgency_tiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('label');
            $table->integer('duration_days');
            $table->decimal('multiplier', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urgency_tiers');
    }
};