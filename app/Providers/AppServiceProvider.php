<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\MedicationOrder;
use App\Models\Pharmacy;
use App\Observers\MedicationOrderObserver;
use App\Policies\PharmacyPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Broadcast;
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
        Model::unguard();
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();

        Gate::policy(Pharmacy::class, PharmacyPolicy::class);
        Broadcast::routes(['middleware' => ['api', 'auth:sanctum']]);

        MedicationOrder::observe(MedicationOrderObserver::class);
    }
}
