<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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

        View::composer('*', function (ViewInstance $view): void {
            if (! str_ends_with($view->getPath(), 'resources/views/components/layout/sidebar.blade.php')) {
                return;
            }

            $user = auth()->user();

            $view->with(
                'hasMultipleAffiliations',
                $user !== null && $user->affiliations()->valid()->count() > 1,
            );
        });

        Carbon::setLocale(config('app.locale'));
        date_default_timezone_set(config('app.timezone'));

        require_once app_path('Helpers/DateHelper.php');
    }
}
