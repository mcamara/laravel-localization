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
		$this->package('mcamara/laravel-localization');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

        Route::filter('LaravelLocalizationRedirectFilter', function()
        {
            global $app;
            $currentLocale = $app['laravellocalization']->getCurrentLocale();
            $defaultLocale = $app['laravellocalization']->getDefault();
            $params = explode('/', Request::path());
            if (count($params) > 0)
            {
                $localeCode = $params[0];
                $locales = $app['laravellocalization']->getSupportedLocales();
                $hideDefaultLocale = $app['laravellocalization']->hideDefaultLocaleInURL();
                $redirection = false;
                
                if (!empty($locales[$localeCode]))
                {
                    if ($localeCode === $defaultLocale && $hideDefaultLocale)
                    {
                        $redirection = $app['laravellocalization']->getNonLocalizedURL();
                    }
                }
                else if ($currentLocale !== $defaultLocale || !$hideDefaultLocale)
                {
                    // If the current url does not contain any locale
                    // The system redirect the user to the very same url "localized"
                    // we use the current locale to redirect him
                    $redirection = $app['laravellocalization']->getLocalizedURL();
                }
                    
                if($redirection)
                {
                    // Save any flashed data for redirect
                    Session::reflash();
                    return Redirect::to($redirection, 307)->header('Vary','Accept-Language');
                }
            }
        });

        /**
         * 	This filter would set the translated route name
         */
        Route::filter('LaravelLocalizationRoutes', function()
        {
            global $app;
            $router = $app['router'];
            $routeName = $app['laravellocalization']->getRouteNameFromAPath($router->current()->uri());

            $app['laravellocalization']->setRouteName($routeName);
            return;
        });

		$this->app['config']->package('mcamara/laravel-localization', __DIR__.'/../config');

		$this->app['laravellocalization'] = $this->app->share(function($app)
		{
			return new LaravelLocalization($app['config'], $app['view'], $app['translator']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
