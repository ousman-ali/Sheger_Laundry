<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
    // Extend enum with new types used by NotificationService and Payments
    DB::statement("ALTER TABLE `notifications` MODIFY COLUMN `type` ENUM('low_stock','order_status','pickup_reminder','assignment','service_status','payment','payment_approval') NOT NULL");
    }

    public function down(): void
    {
        // Revert to original set (note: may fail if rows with new types exist)
    DB::statement("ALTER TABLE `notifications` MODIFY COLUMN `type` ENUM('low_stock','order_status','pickup_reminder') NOT NULL");
    }
};
