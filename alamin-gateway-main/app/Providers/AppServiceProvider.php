<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('gateway-login', function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('password-recovery', function (Request $request): Limit {
            $identifier = Str::lower(trim((string) $request->input('identifier')));

            return Limit::perMinute(5)->by($identifier.'|'.$request->ip());
        });

        RateLimiter::for('administrator-password-reset', fn (Request $request): Limit => Limit::perMinute(10)
            ->by(hash('sha256', (string) $request->route('token')).'|'.$request->ip()));

        RateLimiter::for('password-change', function (Request $request): Limit {
            $identity = $request->session()->get('gateway.identity');
            $email = is_array($identity) ? Str::lower(trim((string) ($identity['email'] ?? ''))) : 'guest';

            return Limit::perMinute(5)->by(hash('sha256', $email).'|'.$request->ip());
        });

        RateLimiter::for('outsider-registration', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip()));

        RateLimiter::for('sso-redemption', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) $request->header('X-Alamin-SSO-Client', 'unknown').'|'.$request->ip()));
    }
}
