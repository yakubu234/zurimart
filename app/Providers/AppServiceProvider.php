<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
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
        foreach (['created', 'updated', 'deleted'] as $action) {
            Event::listen("eloquent.{$action}: *", function (string $eventName, array $models) use ($action): void {
                $model = $models[0] ?? null;

                if ($model instanceof Model) {
                    app(AuditTrailService::class)->record($action, $model);
                }
            });
        }

        Event::listen(Login::class, function (Login $event): void {
            app(AuditTrailService::class)->recordActivity(
                'logged_in',
                $event->user,
                "Logged in user: {$event->user->name}"
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                app(AuditTrailService::class)->recordActivity(
                    'logged_out',
                    $event->user,
                    "Logged out user: {$event->user->name}"
                );
            }
        });

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
