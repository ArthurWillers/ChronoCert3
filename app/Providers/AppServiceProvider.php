<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
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
        Model::preventLazyLoading(! app()->isProduction());

        Blade::anonymousComponentPath(resource_path('views/components/ui'));
        Blade::anonymousComponentPath(resource_path('views/components/form'));
        Blade::anonymousComponentPath(resource_path('views/components/layout'));
        Blade::anonymousComponentPath(resource_path('views/components/nav'));

        Carbon::setLocale(config('app.locale'));
        date_default_timezone_set(config('app.timezone'));

        require_once app_path('Helpers/DateHelper.php');
    }
}
