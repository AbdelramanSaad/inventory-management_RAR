<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register Gates
        Gate::define('admin-actions', function (User $user) {
            return $user->isAdmin();
        });
        
        Gate::define('manage-inventory', function (User $user) {
            return $user->isAdmin() || $user->isWarehouseManager();
        });
        
        Gate::define('view-inventory', function (User $user) {
            return $user->isAdmin() || $user->isWarehouseManager() || $user->isStaff();
        });
        
        Gate::define('view-warehouse', function (User $user, $warehouseId) {
            return $user->isAdmin() || $user->warehouse_id === $warehouseId;
        });
    }
}
