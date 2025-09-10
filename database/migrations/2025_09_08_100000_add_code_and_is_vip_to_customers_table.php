<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique()->after('id');
            $table->boolean('is_vip')->default(false)->after('address');
            $table->index('is_vip');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['is_vip']);
            $table->dropUnique(['customers_code_unique']);
            $table->dropColumn(['code','is_vip']);
        });
    }
};
