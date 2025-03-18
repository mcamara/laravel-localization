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

        if (Route::hasMacro($localizationMacroName)) {
            return;
        }

        Route::macro($localizationMacroName, function (callable $routes, array $middleware = []) {
            Route::middleware($middleware)->group(function () use ($routes) {
                Route::name('default_lang.')->group($routes);

                $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
                $localesMapping = array_keys(config('laravellocalization.localesMapping', []));
                $hideDefaultLocaleInURL = config('laravellocalization.hideDefaultLocaleInURL', false);

                $allowedLocales = implode('|', array_unique(array_merge($supportedLocales, $localesMapping)));
                Route::prefix('/{locale}')
                    ->where(['locale' => $allowedLocales])
                    ->group($routes);

                //@toDo translatedRoutes need to be defined inhere aswell

                if($hideDefaultLocaleInURL){
                    Route::name('default_locale.')->group($routes);
                }
            });
        });
    }
}
