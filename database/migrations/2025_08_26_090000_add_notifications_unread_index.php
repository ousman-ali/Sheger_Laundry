<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE INDEX idx_notifications_user_unread ON notifications (user_id, is_read, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX idx_notifications_user_unread ON notifications');
    }
};
