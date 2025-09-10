<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('urgency_tier_id')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('price_applied', 10, 2);
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'on_hold', 'cancelled']);
            $table->timestamps();

            $table->foreign('order_item_id')
                ->references('id')
                ->on('order_items')
                ->onDelete('cascade');
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->onDelete('restrict');
            $table->foreign('employee_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->foreign('urgency_tier_id')
                ->references('id')
                ->on('urgency_tiers')
                ->onDelete('restrict');

            $table->index(['order_item_id', 'service_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_services');
    }
};
