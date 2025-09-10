<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('created_by');
            $table->decimal('total_cost', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('vat_percentage', 5, 2);
            $table->dateTime('appointment_date')->nullable();
            $table->dateTime('pickup_date')->nullable();
            $table->decimal('penalty_amount', 10, 2)->default(0.00);
            $table->decimal('penalty_daily_rate', 10, 2)->nullable();
            $table->enum('status', ['received', 'processing', 'washing', 'drying_steaming', 'ironing', 'packaging', 'ready_for_pickup', 'delivered', 'cancelled']);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status', 'customer_id', 'created_by']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};