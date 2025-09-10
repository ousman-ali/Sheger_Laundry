<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_out_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_out_request_id')->constrained('stock_out_requests')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
            $table->index(['stock_out_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_out_request_items');
    }
};
