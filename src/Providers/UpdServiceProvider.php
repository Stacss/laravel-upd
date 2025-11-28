<?php

namespace Stacss\LaravelUpd\Providers;

use Illuminate\Support\ServiceProvider;
use Stacss\LaravelUpd\UpdRenderer;

class UpdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/upd.php', 'upd');

        $this->app->singleton(UpdRenderer::class, function () {
            return new UpdRenderer();
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'laravel-upd');

        $this->publishes([
            __DIR__ . '/../../config/upd.php' => config_path('upd.php'),
        ], 'upd-config');

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/laravel-upd'),
        ], 'upd-views');
    }
}
