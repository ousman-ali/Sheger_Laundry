<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'parent_payment_id')) {
                $table->unsignedBigInteger('parent_payment_id')->nullable()->after('payment_ledger_id');
                $table->index('parent_payment_id');
            }
            if (!Schema::hasColumn('payments', 'refund_reason')) {
                $table->text('refund_reason')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('payments', 'external_ref')) {
                $table->string('external_ref', 100)->nullable()->after('refund_reason');
            }
            if (!Schema::hasColumn('payments', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->unique()->after('external_ref');
            }
            if (!Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('idempotency_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('payments', 'idempotency_key')) {
                $table->dropUnique('payments_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('payments', 'external_ref')) {
                $table->dropColumn('external_ref');
            }
            if (Schema::hasColumn('payments', 'refund_reason')) {
                $table->dropColumn('refund_reason');
            }
            if (Schema::hasColumn('payments', 'parent_payment_id')) {
                $table->dropIndex(['parent_payment_id']);
                $table->dropColumn('parent_payment_id');
            }
        });
    }
};
