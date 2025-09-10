<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Treat Admin as super-admin: allow all abilities
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('Admin') ? true : null;
        });
    }
}
