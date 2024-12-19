<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelLocalizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('laravellocalization.php'),
        ], 'config');

        $this->registerMacros();
    }

    public function provides(): array
    {
        return ['modules.handler', 'modules'];
    }

    public function register(): void
    {
        $packageConfigFile = __DIR__.'/../../config/config.php';

        $this->mergeConfigFrom(
            $packageConfigFile, 'laravellocalization'
        );

        $this->registerBindings();
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(LaravelLocalization::class);

        $this->app->alias(LaravelLocalization::class, 'laravellocalization');
    }

    protected function registerMacros(): void
    {
        $localizationMacroName = config('laravellocalization.macro_name', 'localized');

        if (Route::hasMacro($localizationMacroName)) {
            return;
        }

        Route::macro($localizationMacroName, function (callable $routes, array $middleware = []) {
            Route::middleware($middleware)->group(function () use ($routes) {
                Route::name('default_lang.')->group($routes);

                $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
                $localesMapping = array_keys(config('laravellocalization.localesMapping', []));
                $allowedLocales = implode('|', array_unique(array_merge($supportedLocales, $localesMapping)));
                Route::prefix('/{locale}')
                    ->where(['locale' => $allowedLocales])
                    ->group($routes);
            });
        });
    }
}
