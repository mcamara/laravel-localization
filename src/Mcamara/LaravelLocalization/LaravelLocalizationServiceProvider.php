<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelLocalizationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('laravellocalization.php'),
        ], 'config');

        $this->registerMacros();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['modules.handler', 'modules'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $packageConfigFile = __DIR__.'/../../config/config.php';

        $this->mergeConfigFrom(
            $packageConfigFile, 'laravellocalization'
        );

        $this->registerBindings();
    }

    /**
     * Registers app bindings and aliases.
     */
    protected function registerBindings()
    {
        $this->app->singleton(LaravelLocalization::class);

        $this->app->alias(LaravelLocalization::class, 'laravellocalization');
    }

    protected function registerMacros(): void
    {
        $localizationMacroName = config('laravellocalization.macro_name', 'localized');

        if (! Route::hasMacro($localizationMacroName)) {
            Route::macro($localizationMacroName, function (callable $routes, array $middleware = []) {
                Route::middleware($middleware)->group(function () use ($routes) {
                    // Default language group

                    if (config('laravellocalization.hideDefaultLocaleInURL', false)) {
                        Route::name('default_lang.')->group($routes);
                    }

                    // Localized group with a locale prefix
                    Route::prefix('/{locale}')->group($routes);
                });
            });
        }
    }
}
