<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
        Gate::define('view-dashboard', fn (User $user) => $user->hasAnyRole([
            'super_admin',
            'production_branch_manager',
            'internal_outlet',
            'whole_marketer',
        ]));

        Gate::define('view-orders', fn (User $user) => $user->hasAnyRole([
            'super_admin',
            'production_branch_manager',
            'internal_outlet',
            'whole_marketer',
        ]));

        Gate::define('manage-order-approvals', fn (User $user) => $user->hasAnyRole([
            'super_admin',
            'production_branch_manager',
        ]));

        Gate::define('manage-branches', fn (User $user) => $user->hasAnyRole([
            'super_admin',
            'production_branch_manager',
        ]));

        Gate::define('manage-branch-master-data', fn (User $user) => $user->hasRole('super_admin'));
        Gate::define('manage-products', fn (User $user) => $user->hasAnyRole(['super_admin', 'production_branch_manager']));
        Gate::define('manage-categories', fn (User $user) => $user->hasRole('super_admin'));
        Gate::define('view-bookings', fn (User $user) => $user->hasAnyRole(['super_admin', 'production_branch_manager', 'whole_marketer']));
        Gate::define('view-reports', fn (User $user) => $user->hasAnyRole(['super_admin', 'production_branch_manager']));
        Gate::define('manage-users', fn (User $user) => $user->hasRole('super_admin'));
        Gate::define('manage-integration-settings', fn (User $user) => $user->hasRole('super_admin'));
    }
}
