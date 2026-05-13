<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        Gate::before(function (User $user, string $ability) {
            if ($user->status !== 'active') {
                return false;
            }

            return null;
        });

        $abilities = collect(config('access.permissions', []))
            ->pluck('slug')
            ->filter()
            ->values()
            ->all();

        if (Schema::hasTable('permissions')) {
            $abilities = array_values(array_unique(array_merge(
                $abilities,
                Permission::query()->pluck('slug')->filter()->all()
            )));
        }

        foreach ($abilities as $ability) {
            Gate::define($ability, fn (User $user) => $user->hasPermission($ability));
        }
    }
}
