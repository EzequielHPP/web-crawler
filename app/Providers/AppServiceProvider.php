<?php

namespace App\Providers;

use App\Helpers\VersionHelper;
use App\Http\Controllers\BaseController;
use App\Http\Services\ConciergeCall;
use App\Models\Navigation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use App\Validators\MyUriValidator;
use Illuminate\Routing\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // if is a cli request, don't run this
        if (php_sapi_name() === 'cli') {
            return;
        }
        if(env('APP_ENV', 'production') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
