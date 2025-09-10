<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_service_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_service_id')->constrained('order_item_services')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->enum('status', ['assigned','in_progress','completed','on_hold','cancelled'])->default('assigned');
            $table->timestamps();

            $table->unique(['order_item_service_id','employee_id'], 'order_service_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_service_assignments');
    }
};
