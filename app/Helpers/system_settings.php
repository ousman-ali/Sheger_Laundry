<?php

use App\Models\SystemSetting;

if (!function_exists('system_setting')) {
    function system_setting(string $key, $default = null)
    {
        return SystemSetting::where('key', $key)->value('value') ?? $default;
    }
}
