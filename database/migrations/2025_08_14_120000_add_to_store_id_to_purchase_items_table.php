<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'to_store_id')) {
                $table->unsignedBigInteger('to_store_id')->nullable()->after('total_price');
                $table->foreign('to_store_id')
                    ->references('id')->on('stores')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'to_store_id')) {
                $table->dropForeign(['to_store_id']);
                $table->dropColumn('to_store_id');
            }
        });
    }
};
