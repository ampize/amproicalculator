<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\ResourceResolver;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Blade::setEscapedContentTags('[[', ']]');
        Blade::setContentTags('[[[', ']]]');
    }
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
