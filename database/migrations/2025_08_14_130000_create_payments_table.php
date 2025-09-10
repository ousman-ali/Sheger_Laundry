<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 10, 2);
            $table->string('method', 50)->nullable();
            $table->enum('status', ['pending','completed','refunded','rejected'])->default('completed');
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            // Waiver & approval workflow
            $table->boolean('waived_penalty')->default(false);
            $table->text('waiver_reason')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
