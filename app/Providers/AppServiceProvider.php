<?php

namespace App\Providers;

use App\Support\BorrowRequestMetrics;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const HOME = '/admin';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(BorrowRequestMetrics::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::listen(fn (QueryExecuted $query) => app(BorrowRequestMetrics::class)->recordQuery($query->time));

        Carbon::setLocale('pt_BR');

        Carbon::macro('local', function () {
            /** @var Carbon $this */
            return $this->setTimezone(config('app.timezone'));
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
