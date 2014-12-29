<?php namespace Mcamara\LaravelLocalization;

use App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository as Config;
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
        $this->getConfig()->package('mcamara/laravel-localization', __DIR__.'/../config');

		App::singleton('laravellocalization', 'Mcamara\LaravelLocalization\LaravelLocalization');

        $this->registerFilters();
	}

    private function registerFilters()
    {
        Route::filter('LaravelLocalizationRedirectFilter', function()
        {
            $currentLocale = $this->getLocalization()->getCurrentLocale();
            $defaultLocale = $this->getLocalization()->getDefaultLocale();
            $params = explode('/', Request::path());
            if (count($params) > 0)
            {
                $localeCode = $params[0];
                $locales = $this->getLocalization()->getSupportedLocales();
                $hideDefaultLocale = $this->getLocalization()->hideDefaultLocaleInURL();
                $redirection = false;

                if ( ! empty($locales[$localeCode]))
                {
                    if ($localeCode === $defaultLocale && $hideDefaultLocale)
                    {
                        $redirection = $this->getLocalization()->getNonLocalizedURL();
                    }
                }
                else if ($currentLocale !== $defaultLocale || !$hideDefaultLocale)
                {
                    // If the current url does not contain any locale
                    // The system redirect the user to the very same url "localized"
                    // we use the current locale to redirect him
                    $redirection = $this->getLocalization()->getLocalizedURL();
                }

                if ($redirection)
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
            $routeName = $this->getLocalization()->getRouteNameFromAPath(App::make('router')->current()->uri());

            $this->getLocalization()->setRouteName($routeName);
            return;
        });
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

    /**
     * @return LaravelLocalization
     */
    private function getLocalization()
    {
        return App::make('laravellocalization');
    }

    /**
     * @return Config
     */
    private function getConfig()
    {
        return App::make('config');
    }
}
