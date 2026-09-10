<?php

namespace App\Providers;

use App\Models\Import;
use App\Models\Report;
use App\Models\ReportTemplate;
use App\Policies\ImportPolicy;
use App\Policies\ReportPolicy;
use App\Policies\ReportTemplatePolicy;
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
        Gate::policy(Import::class, ImportPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(ReportTemplate::class, ReportTemplatePolicy::class);
    }
}
