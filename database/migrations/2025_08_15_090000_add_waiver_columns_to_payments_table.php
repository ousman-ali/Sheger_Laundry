<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return; // created by initial migration
        }
        $addedApprovedBy = false;
        Schema::table('payments', function (Blueprint $table) use (&$addedApprovedBy) {
            if (!Schema::hasColumn('payments', 'waived_penalty')) {
                $table->boolean('waived_penalty')->default(false)->after('created_by');
            }
            if (!Schema::hasColumn('payments', 'waiver_reason')) {
                $table->text('waiver_reason')->nullable()->after('waived_penalty');
            }
            if (!Schema::hasColumn('payments', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('waiver_reason');
            }
            if (!Schema::hasColumn('payments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('requires_approval');
                $addedApprovedBy = true;
            }
            if (!Schema::hasColumn('payments', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
        });
        if ($addedApprovedBy) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'approved_by')) {
                try { $table->dropForeign(['approved_by']); } catch (\Throwable $e) {}
            }
            foreach (['approved_at','approved_by','requires_approval','waiver_reason','waived_penalty'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
