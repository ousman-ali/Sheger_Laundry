<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->unsignedBigInteger('entered_unit_id')->nullable()->after('unit_id');
            $table->decimal('entered_quantity', 15, 4)->nullable()->after('quantity');
            $table->decimal('canonical_quantity', 15, 4)->nullable()->after('entered_quantity');
            $table->foreign('entered_unit_id')->references('id')->on('units')->onDelete('set null');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->unsignedBigInteger('entered_unit_id')->nullable()->after('unit_id');
            $table->decimal('entered_quantity', 15, 4)->nullable()->after('quantity');
            $table->decimal('canonical_quantity', 15, 4)->nullable()->after('entered_quantity');
            $table->foreign('entered_unit_id')->references('id')->on('units')->onDelete('set null');
        });

        Schema::table('stock_usage', function (Blueprint $table) {
            $table->unsignedBigInteger('entered_unit_id')->nullable()->after('unit_id');
            $table->decimal('entered_quantity', 15, 4)->nullable()->after('quantity_used');
            $table->decimal('canonical_quantity', 15, 4)->nullable()->after('entered_quantity');
            $table->foreign('entered_unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'entered_unit_id')) {
                $table->dropForeign(['entered_unit_id']);
                $table->dropColumn(['entered_unit_id', 'entered_quantity', 'canonical_quantity']);
            }
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfer_items', 'entered_unit_id')) {
                $table->dropForeign(['entered_unit_id']);
                $table->dropColumn(['entered_unit_id', 'entered_quantity', 'canonical_quantity']);
            }
        });

        Schema::table('stock_usage', function (Blueprint $table) {
            if (Schema::hasColumn('stock_usage', 'entered_unit_id')) {
                $table->dropForeign(['entered_unit_id']);
                $table->dropColumn(['entered_unit_id', 'entered_quantity', 'canonical_quantity']);
            }
        });
    }
};
