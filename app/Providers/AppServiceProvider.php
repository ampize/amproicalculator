<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ResourceResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ResourceResolver', function ($app) {
            return new ResourceResolver(config("resourceNamespaces"));
        });
    }
}
