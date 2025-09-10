<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_item_penalties')) {
            Schema::create('order_item_penalties', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('order_item_service_id')->nullable();
                $table->decimal('amount', 10, 2);
                $table->boolean('waived')->default(false);
                $table->text('waiver_reason')->nullable();
                $table->boolean('requires_approval')->default(false);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('order_item_service_id')->references('id')->on('order_item_services')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

                $table->index(['order_id']);
                $table->index(['order_item_service_id']);
                $table->index(['waived']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_penalties');
    }
};
