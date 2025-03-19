<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
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
            $this->isInsideLocalizedGroup = true;
            Route::middleware($middleware)->group(function () use ($routes) {
                Route::name('default_lang.')->group($routes);

                $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
                $localesMapping = array_keys(config('laravellocalization.localesMapping', []));
                $hideDefaultLocaleInURL = config('laravellocalization.hideDefaultLocaleInURL', false);

                $allowedLocales = implode('|', array_unique(array_merge($supportedLocales, $localesMapping)));
                Route::prefix('/{locale}')
                    ->where(['locale' => $allowedLocales])
                    ->group($routes);

                if($hideDefaultLocaleInURL){
                    Route::name('default_locale.')->group($routes);
                }
            });
            $this->isInsideLocalizedGroup = false;
        });

        Route::macro('transGet', function (string $routeKey, array $controller) {
            $this->ensureNotInsideLocalizedGroup();
            $this->registerTransRoute($routeKey, $controller, 'get');
        });

        Route::macro('transPost', function (string $routeKey, array $controller) {
            $this->ensureNotInsideLocalizedGroup();
            $this->registerTransRoute($routeKey, $controller, 'post');
        });

        Route::macro('transPut', function (string $routeKey, array $controller) {
            $this->ensureNotInsideLocalizedGroup();
            $this->registerTransRoute($routeKey, $controller, 'put');
        });

        Route::macro('transDelete', function (string $routeKey, array $controller) {
            $this->ensureNotInsideLocalizedGroup();
            $this->registerTransRoute($routeKey, $controller, 'delete');
        });
    }

    private function ensureNotInsideLocalizedGroup(): void
    {
        if (!empty($this->isInsideLocalizedGroup)) {
            throw new \RuntimeException("You cannot use transRoute* inside a Route::localized() group.");
        }
    }

    private function registerTransRoute(string $routeKey, array $controller, string $methodType): void
    {
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
        $localesMapping = array_keys(config('laravellocalization.localesMapping', []));
        $allowedLocales = array_unique(array_merge($supportedLocales, $localesMapping));

        $hideDefaultLocaleInURL = config('laravellocalization.hideDefaultLocaleInURL', false);

        foreach ($allowedLocales as $locale) {
            $routeFile = lang_path("$locale/routes.php");

            if (File::exists($routeFile)) {
                $routes = require $routeFile;

                if (isset($routes[$routeKey])) {
                    $route = ltrim($routes[$routeKey], '/');

                    if($hideDefaultLocaleInURL && $locale = App::getLocale()){
                        Route::$methodType($route, $controller)
                            ->name("translated_route_{$locale}_{$routeKey}");
                    }else{
                        Route::$methodType($locale . '/' . $route, $controller)
                            ->name("translated_route_{$locale}_{$routeKey}");
                    }


                }
            }
        }
    }
}
