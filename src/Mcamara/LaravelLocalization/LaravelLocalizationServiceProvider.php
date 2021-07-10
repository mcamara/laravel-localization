<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Middleware as Mcamara;

class LaravelLocalizationServiceProvider extends ServiceProvider
{

    /**
     * Middlewares to Register
     *
     * @var array
     */
    protected $middlewareToAdd = [
        'localize' => Mcamara\LaravelLocalizationRoutes::class,
        'localizationRedirect' => Mcamara\LaravelLocalizationRedirectFilter::class,
        'localeSessionRedirect' => Mcamara\LocaleSessionRedirect::class,
        'localeCookieRedirect' => Mcamara\LocaleCookieRedirect::class,
        'localeViewPath' => Mcamara\LaravelLocalizationViewPath::class
    ];

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

        $this->registerMiddlewareGroups();
    }

    /**
     * register Middlewares
     */
    protected function registerMiddlewareGroups()
    {
        $versionGreaterThan54 = version_compare(app()->version(), '5.4.0', '>=');
        foreach ($this->middlewareToAdd as $name => $class) {
            if ($versionGreaterThan54) {
                Route::aliasMiddleware($name, $class);
            } else {
                Route::middleware($name, $class);
            }
        }
    }


    /**
     * Registers app bindings and aliases.
     */
    protected function registerBindings()
    {
        $this->app->singleton(LaravelLocalization::class, function () {
            return new LaravelLocalization();
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
}
