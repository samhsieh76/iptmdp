<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Extensions\ESMSUserProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(Gate $gate, Request $request) {
        $this->registerPolicies();
        if ($this->app->runningInConsole() || $this->app->runningUnitTests()) {
            return;
        }

        if ($request->is('api/*') && ($request->route() && $request->route()->getName() !== 'esms.login')) {
            return;
        }

        if (Schema::hasTable('permissions')) {
            foreach (Permission::get() as $permission) {
                $gate->define($permission->name, function () use ($permission) {
                    return Auth::user()->isSuperUser() || Auth::user()->HasPermission($permission->id);
                });
            }
        }

        Auth::provider('esms', function ($app, array $config) {
            return new ESMSUserProvider($app['hash'], $config['model']);
        });
    }
}
