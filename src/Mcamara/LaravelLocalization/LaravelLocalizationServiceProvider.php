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
        tap(fn () => new LaravelLocalization(), function ($fn) {
            // the conditional check below is important
            // when you do caching routes via `php artisan route:trans:cache` if binding
            // via `bind` used you will get incorrect serialized translated routes in cache
            // files and that's why you'll get broken translatable route URLs in UI

            // again, if you don't use translatable routes, you may get rid of this check
            // and leave only 'bind()' here
            if ($this->runningInOctane()) {
                // the 3rd parameter is important to be passed to 'bind()'
                // otherwise the package's instance will be instantiated every time
                // you reference it and it won't get proper data for 'serialized translatable routes'
                // class variable, this will make impossible to use translatable routes properly
                // but overall the package will still work stable except generating the same URLs
                // for translatable routes independently of locale
                $this->app->bind(LaravelLocalization::class, $fn, true);
            } else {
                $this->app->singleton(LaravelLocalization::class, $fn);
            }
        });

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
     * Checks if we are up via Laravel Octane
     */
    private function runningInOctane(): bool
    {
        return ! $this->app->runningInConsole() && env('LARAVEL_OCTANE');
    }
}
