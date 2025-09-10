<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('store_id');
            $table->decimal('quantity', 10, 2);
            $table->timestamps();

            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('restrict');
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');

            $table->unique(['inventory_item_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock');
    }
}; 