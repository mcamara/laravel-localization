<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Support\ServiceProvider;
use Route;
use Request;
use Redirect;
use Session;

class LaravelLocalizationServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
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
     * Register the package resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $userConfigFile    = app()->configPath().'/laravel-localization/config.php';
        $packageConfigFile = __DIR__ . '/../../config/config.php';
        $config            = $this->app['files']->getRequire($packageConfigFile);

        if (file_exists($userConfigFile)) {
            $userConfig = $this->app['files']->getRequire($userConfigFile);
            $config     = array_replace_recursive($config, $userConfig);
        }

        $this->app['config']->set('laravel-localization', $config);
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerResources();

        $this->app[ 'laravellocalization' ] = $this->app->share(
            function ()
            {
                return new LaravelLocalization();
            }
        );
    }

}
