<?php

namespace Stacss\LaravelUpd\Providers;

use Illuminate\Support\ServiceProvider;
use Stacss\LaravelUpd\InvoiceRenderer;
use Stacss\LaravelUpd\ReconciliationRenderer;
use Stacss\LaravelUpd\Support\MoneyToWordsRu;
use Stacss\LaravelUpd\UpdRenderer;

class UpdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/upd.php', 'upd');

        $this->app->singleton(UpdRenderer::class, function () {
            return new UpdRenderer();
        });
        $this->app->singleton(ReconciliationRenderer::class, function () {
            return new ReconciliationRenderer();
        });
        $this->app->singleton(MoneyToWordsRu::class, function () {
            return new MoneyToWordsRu();
        });
        $this->app->singleton(InvoiceRenderer::class, function ($app) {
            return new InvoiceRenderer(
                calculator: $app->make(\Stacss\LaravelUpd\VatCalculator::class),
                moneyToWords: $app->make(MoneyToWordsRu::class),
            );
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
