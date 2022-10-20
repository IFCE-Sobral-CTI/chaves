<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Rule;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(Rule $rule)
    {
        $this->registerPolicies();

        if (Schema::hasTable('rules')) {
            $rules = $rule->with('permissions')->get();

            foreach($rules as $rule) {
                Gate::define($rule->control, function(User $user) use ($rule) {
                    return $user->hasRule($rule);
                });
            }

            Gate::before(function($user, $ability) {
                return $user->isAdmin();
            });
        }
    }
}
