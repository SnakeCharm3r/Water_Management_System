<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Zone;
use App\Services\ZoneAccessService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(static function (User $user): ?bool {
            return $user->is_active && $user->hasAnyRole(['super-admin', 'system-admin']) ? true : null;
        });

        Gate::define('access-zone', static function (User $user, Zone $zone): bool {
            return app(ZoneAccessService::class)->canAccess($user, $zone);
        });
    }
}
