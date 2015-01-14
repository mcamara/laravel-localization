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
        $packageConfigFile = __DIR__.'/../../config/config.php';
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

        $app = $this->app;
        Route::filter('LaravelLocalizationRedirectFilter', function () use ( $app )
        {
            $currentLocale = $app[ 'laravellocalization' ]->getCurrentLocale();
            $defaultLocale = $app[ 'laravellocalization' ]->getDefaultLocale();
            $params = explode('/', Request::path());
            if ( count($params) > 0 )
            {
                $localeCode = $params[ 0 ];
                $locales = $app[ 'laravellocalization' ]->getSupportedLocales();
                $hideDefaultLocale = $app[ 'laravellocalization' ]->hideDefaultLocaleInURL();
                $redirection = false;

                if ( !empty( $locales[ $localeCode ] ) )
                {
                    if ( $localeCode === $defaultLocale && $hideDefaultLocale )
                    {
                        $redirection = $app[ 'laravellocalization' ]->getNonLocalizedURL();
                    }
                } else if ( $currentLocale !== $defaultLocale || !$hideDefaultLocale )
                {
                    // If the current url does not contain any locale
                    // The system redirect the user to the very same url "localized"
                    // we use the current locale to redirect him
                    $redirection = $app[ 'laravellocalization' ]->getLocalizedURL();
                }

                if ( $redirection )
                {
                    // Save any flashed data for redirect
                    Session::reflash();

                    return Redirect::to($redirection, 307)->header('Vary', 'Accept-Language');
                }
            }
        });

        /**
         *    This filter would set the translated route name
         */
        Route::filter('LaravelLocalizationRoutes', function ()
        {
            $app = $this->app;
            $routeName = $app[ 'laravellocalization' ]->getRouteNameFromAPath($app[ 'router' ]->current()->uri());

            $app[ 'laravellocalization' ]->setRouteName($routeName);

            return;
        });

//        $app[ 'config' ]->package('mcamara/laravel-localization', __DIR__ . '/../config');

        $app[ 'laravellocalization' ] = $app->share(
            function () use ( $app )
            {
                return new LaravelLocalization(
                    $app[ 'config' ],
                    $app[ 'view' ],
                    $app[ 'translator' ],
                    $app[ 'router' ],
                    $app
                );
            }
        );
    }

}
