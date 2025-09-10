<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_counters', function (Blueprint $table) {
            $table->id();
            // Date key in Ymd format (e.g., 20250908)
            $table->string('date_key', 16)->unique();
            $table->unsignedInteger('last_seq')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_counters');
    }
};
