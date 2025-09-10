<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extend enum with additional types used across the app
        // Keep this forward-only; do not modify previously-run migrations.
        DB::statement(
            "ALTER TABLE `notifications` MODIFY COLUMN `type` " .
            "ENUM('low_stock','order_status','pickup_reminder','assignment','service_status','payment','payment_approval') NOT NULL"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Best-effort safe rollback: map unknown types to 'order_status' then shrink enum.
        DB::statement(
            "UPDATE `notifications` SET `type`='order_status' " .
            "WHERE `type` NOT IN ('low_stock','order_status','pickup_reminder')"
        );

        DB::statement(
            "ALTER TABLE `notifications` MODIFY COLUMN `type` " .
            "ENUM('low_stock','order_status','pickup_reminder') NOT NULL"
        );
    }
};
