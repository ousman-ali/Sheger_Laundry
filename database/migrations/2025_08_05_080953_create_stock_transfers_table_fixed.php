<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_store_id');
            $table->unsignedBigInteger('to_store_id');
            $table->datetime('transferred_at');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('from_store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');
            $table->foreign('to_store_id')
                ->references('id')
                ->on('stores')
                ->onDelete('restrict');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
