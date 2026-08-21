<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\MedicationOrder;
use App\Models\Pharmacy;
use App\Observers\MedicationOrderObserver;
use App\Policies\PharmacyPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Model::unguard();
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();

        Gate::policy(Pharmacy::class, PharmacyPolicy::class);

        MedicationOrder::observe(MedicationOrderObserver::class);

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // 1. Brute-force protection on all login / 2FA-verify endpoints (5/min per IP)
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // 2. OTP email requests: verification + password reset (+ registration)
        //    - 1/min per email   → protects a single mailbox from spam
        //    - 5/min per IP      → prevents mass OTP-email bombing via rotating emails
        RateLimiter::for('otp-request', fn (Request $request) => [
            Limit::perMinute(1)->by($request->input('email') ?: $request->ip()),
            Limit::perMinute(5)->by($request->ip()),
        ]);

        // 3. Public search/catalog reads — anti-scraping (30/min per user, fallback IP)
        RateLimiter::for('public-search', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
    }
}
