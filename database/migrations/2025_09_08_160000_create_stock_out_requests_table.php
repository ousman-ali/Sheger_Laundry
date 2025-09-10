<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_out_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 30)->unique();
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('status', ['draft','submitted','approved','rejected','cancelled'])->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status','store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_out_requests');
    }
};
