<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Exceptions\LaravelLocalisationException;

class LaravelLocalizationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(ConfigRepository $config)
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('laravellocalization.php'),
        ], 'config');

        // read macro name from config?
        $localizationMacroName = $config->get('laravellocalization.macro_name', 'localized');

        if (Route::hasMacro($localizationMacroName)) {
            throw new LaravelLocalisationException("The macro '{$localizationMacroName}' is already defined. Please choose another name in your configuration file (macro_name).");
        }

        Route::macro($localizationMacroName, function (callable $routes, array $middleware = []) use($config) {
            Route::middleware($middleware)->group(function () use ($routes, $config) {
                // Default language group

                if($config->get('laravellocalization.hideDefaultLocaleInURL', false)){
                    Route::name('default_lang.')->group($routes);
                }

                // Localized group with a locale prefix
                Route::prefix('/{locale}')->group($routes);
            });
        });
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

}
