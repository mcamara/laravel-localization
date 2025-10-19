<?php

namespace Mcamara\LaravelLocalization;

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

        $this->correctLivewireRoutes();
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

        $this->registerCommands();
    }

    /**
     * Registers app bindings and aliases.
     */
    protected function registerBindings()
    {
        $this->app->singleton(LaravelLocalization::class);

        $this->app->alias(LaravelLocalization::class, 'laravellocalization');
    }

    /**
     * Registers route caching commands.
     */
    protected function registerCommands()
    {
        $this->app->singleton('laravellocalizationroutecache.cache', Commands\RouteTranslationsCacheCommand::class);
        $this->app->singleton('laravellocalizationroutecache.clear', Commands\RouteTranslationsClearCommand::class);
        $this->app->singleton('laravellocalizationroutecache.list', Commands\RouteTranslationsListCommand::class);

        $this->commands([
            'laravellocalizationroutecache.cache',
            'laravellocalizationroutecache.clear',
            'laravellocalizationroutecache.list',
        ]);
    }

    /**
     * Integrates Laravel Livewire to ensure that Livewire routes
     * respect the current localization.
     */
    protected function correctLivewireRoutes()
    {
        // 1. Check if Livewire is available through the service container
        if (! $this->app->bound('livewire')) {
            return;
        }

        // 2. Get the Livewire instance from the container and apply localization
        $livewire = $this->app->make('livewire');
        
        if (method_exists($livewire, 'setUpdateRoute')) {
            $livewire::setUpdateRoute(function ($handle) {
                return \Illuminate\Support\Facades\Route::post('/livewire/update', $handle)
                    ->middleware('web')
                    ->prefix(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::setLocale());
            });
        }
    }

}
