<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;

trait RefreshMysql
{
    public static function refreshMysqlFresh(): void
    {
        // Same as refreshMysql but static-friendly for class-level setup
        Artisan::call('migrate:fresh', ['--seed' => false, '--force' => true]);
    }

    protected function refreshMysql(): void
    {
        // Drop and recreate the testing database schema cleanly
        // Ensures no leftover tables cause 1050/1146 errors across tests
        Artisan::call('migrate:fresh', ['--seed' => false, '--force' => true]);
    }
}
