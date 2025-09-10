<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('quantity', 10, 2);
            $table->timestamps();

            $table->foreign('stock_transfer_id')
                ->references('id')
                ->on('stock_transfers')
                ->onDelete('cascade');
            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('restrict');
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
}; 