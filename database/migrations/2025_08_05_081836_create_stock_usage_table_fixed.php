<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_usage', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('quantity_used', 10, 2);
            $table->enum('operation_type', ['washing', 'drying', 'ironing', 'packaging', 'other']);
            $table->datetime('usage_date');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('restrict');
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('restrict');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_usage');
    }
};
