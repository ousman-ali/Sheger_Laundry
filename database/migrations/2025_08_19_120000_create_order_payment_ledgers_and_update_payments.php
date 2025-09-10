<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_payment_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_received', 14, 2)->default(0);
            $table->string('currency', 8)->default('ETB');
            $table->enum('status', ['pending','partial','paid'])->default('pending');
            $table->timestamps();
            $table->unique('order_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_ledger_id')) {
                $table->foreignId('payment_ledger_id')->nullable()->after('order_id')->constrained('order_payment_ledgers')->nullOnDelete();
                $table->index(['payment_ledger_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_ledger_id')) {
                $table->dropConstrainedForeignId('payment_ledger_id');
            }
        });
        Schema::dropIfExists('order_payment_ledgers');
    }
};
