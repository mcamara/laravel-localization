<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Support\ServiceProvider;

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

		//define a constant that the rest of the package can use to conditionally use pieces of Laravel 4.1.x vs. 4.0.x
		$this->app['laravel-localization.4.1'] = version_compare(\Illuminate\Foundation\Application::VERSION, '4.1') > -1;
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
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