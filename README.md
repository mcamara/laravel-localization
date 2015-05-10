# Laravel Localization

[![Latest Stable Version](https://poser.pugx.org/mcamara/laravel-localization/version.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Total Downloads](https://poser.pugx.org/mcamara/laravel-localization/d/total.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Build Status](https://travis-ci.org/mcamara/laravel-localization.png)](https://travis-ci.org/mcamara/laravel-localization)

Easy i18n localization for Laravel, an useful tool to combine with Laravel localization classes.


## Table of Contents

- <a href="#installation">Installation</a>
    - <a href="#composer">Composer</a>
    - <a href="#manually">Manually</a>
    - <a href="#laravel">Laravel</a>
- <a href="#usage">Usage</a>
    - <a href="#middleware">Middleware</a>
    - <a href="#sessions">Sessions</a>
- <a href="#helpers">Helpers</a>
- <a href="#translated-routes">Translated Routes</a>
- <a href="#config">Config</a>
    - <a href="#config-files">Config files</a>
    - <a href="#service-providers">Service providers</a>
- <a href="#changelog">Changelog</a>
- <a href="#license">License</a>

## Laravel compatibility

Laravel 5 is released!!

 Laravel  | laravel-localization
:---------|:----------
 4.0.x    | 0.13.x
 4.1.x    | 0.13.x
 4.2.x    | 0.15.x
 5.0.x    | 1.0.x

## Installation

### Composer

Add Laravel Localization to your `composer.json` file.

    "mcamara/laravel-localization": "1.0.*"

Run `composer install` to get the latest version of the package.

### Manually

It's recommended that you use Composer, however you can download and install from this repository.

### Laravel

Laravel Localization comes with a service provider for Laravel. You'll need to add it to your `composer.json` as mentioned in the above steps, then register the service provider with your application.

Open `config/app.php` and find the `providers` key. Add `LaravelLocalizationServiceProvider` to the array.

```php
	...
	'Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider'
	...
```

You can also add an alias to the list of class aliases in the same file.

```php
	...
	'LaravelLocalization'	=> 'Mcamara\LaravelLocalization\Facades\LaravelLocalization'
	...
```

## Usage

Laravel Localization uses the URL given for the request. In order to achieve this purpose, a route group should be added into the `routes.php` file. It will filter all pages that must be localized.

```php
	// app/Http/routes.php

	Route::group(['prefix' => LaravelLocalization::setLocale()], function()
	{
		/** ADD ALL LOCALIZED ROUTES INSIDE THIS GROUP **/
		Route::get('/', function()
		{
			return View::make('hello');
		});

		Route::get('test',function(){
			return View::make('test');
		});
	});

	/** OTHER PAGES THAT SHOULD NOT BE LOCALIZED **/

```

Once this route group is added to the routes file, a user can access all locales added into `supportedLocales` ('en' and 'es' by default, look at the config section to change that option). For example, a user can now access two different locales, using the following addresses:

```
	http://url-to-laravel/en
	http://url-to-laravel/es
	http://url-to-laravel
```

If the locale is not present in the url or it is not defined in `supportedLocales`, the system will use the application default locale or the user's browser default locale (if defined in config file).

Once the locale is defined, the locale variable will be stored in a session (if the middleware is enabled), so it is not necessary to write the /lang/ section in the url after defining it once, using the last known locale for the user. If the user accesses to a different locale this session value would be changed, translating any other page he visits with the last chosen locale.

Template files and all locale files should follow the [Lang class](http://laravel.com/docs/5.0/localization).

### Middleware

Moreover, this package includes a middleware object to redirect all "non-localized" routes to the corresponding "localized".

So, if a user navigates to http://url-to-laravel/test and the system has this middleware active and 'en' as the current locale for this user, it would redirect (301) him automatically to http://url-to-laravel/en/test. This is mainly used to avoid duplicate content and improve SEO performance.

To do so, you have to register the middleware in the `app/Http/Kernel.php` file like this:

```php
	<?php namespace App\Http;

	use Illuminate\Foundation\Http\Kernel as HttpKernel;

	class Kernel extends HttpKernel {
		/**
		 * The application's route middleware.
		 *
		 * @var array
		 */
		protected $routeMiddleware = [
			/**** OTHER MIDDLEWARE ****/
			'localize' => 'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes',
			'localizationRedirect' => 'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter',
			'localeSessionRedirect' => 'Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect'
			// REDIRECTION MIDDLEWARE
		];

	}
```


```php
	// app/Http/routes.php

	Route::group(
	[
		'prefix' => LaravelLocalization::setLocale(),
		'middleware' => [ 'localeSessionRedirect', 'localizationRedirect' ]
	],
	function()
	{
		/** ADD ALL LOCALIZED ROUTES INSIDE THIS GROUP **/
		Route::get('/', function()
		{
			return View::make('hello');
		});

		Route::get('test',function(){
			return View::make('test');
		});
	});

	/** OTHER PAGES THAT SHOULD NOT BE LOCALIZED **/

```

In order to activate it, you just have to attach this middleware to the routes you want to be accessible localized.

If you want to hide the default locale but always show other locales in the url, switch the `hideDefaultLocaleInURL` config value to true. Once it's true, if the default locale is en (english) all URLs containing /en/ would be redirected to the same url without this fragment '/' but maintaining the locale as en (English).

**IMPORTANT** - When `hideDefaultLocaleInURL` is set to true, the unlocalized root is treated as the applications default locale `app.locale`.  Because of this language negotiation using the Accept-Language header will **NEVER** occur when `hideDefaultLocaleInURL` is true.

## Helpers

This package comes with some useful functions, like:

### Get URL for an specific locale

```php
	/**
	 * Returns an URL adapted to $locale
	 *
	 * @param  string|boolean 	$locale	   	Locale to adapt, false to remove locale
	 * @param  string|false		$url		URL to adapt in the current language. If not passed, the current url would be taken.
	 * @param  array 			$attributes	Attributes to add to the route, if empty, the system would try to extract them from the url.
	 *
	 * @throws UnsupportedLocaleException
	 *
	 * @return string|false				URL translated, False if url does not exist
	 */
	public function getLocalizedURL($locale = null, $url = null, $attributes = array())

	//Should be called in a view like this:
	{{ LaravelLocalization::getLocalizedURL(optional string $locale, optional string $url, optional array $attributes) }}
```

It returns a URL localized to the desired locale.

### Get Clean routes

```php
	/**
	 * It returns an URL without locale (if it has it)
	 * Convenience function wrapping getLocalizedURL(false)
	 *
	 * @param  string|false 	$url	  URL to clean, if false, current url would be taken
	 *
	 * @return string		   URL with no locale in path
	 */
	public function getNonLocalizedURL($url = null)

	//Should be called in a view like this:
	{{ LaravelLocalization::getNonLocalizedURL(optional string $url) }}
```

It returns a URL clean of any localization.


### Get URL for an specific translation key

```php
	/**
	 * Returns an URL adapted to the route name and the locale given
	 *
     * @throws UnsupportedLocaleException
     *
	 * @param  string|boolean 	$locale 			Locale to adapt
	 * @param  string 			$transKeyName  		Translation key name of the url to adapt
	 * @param  array 			$attributes  		Attributes for the route (only needed if transKeyName needs them)
	 *
	 * @return string|false 	URL translated
	 */
	public function getURLFromRouteNameTranslated($locale, $transKeyName, $attributes = array())

	//Should be called in a view like this:
	{{ LaravelLocalization::getURLFromRouteNameTranslated(string $locale, optional array $transKeyNames, optional array $attributes) }}
```

It returns a route, localized to the desired locale using the locale passed. If the translation key does not exist in the locale given, this function will return false.

### Get Supported Locales

```php
	/**
     * Return an array of all supported Locales
     *
     * @return array
     */
     public function getSupportedLocales()

	//Should be called like this:
	{{ LaravelLocalization::getSupportedLocales() }}
```

This function will return all supported locales and their properties as an array.

### Get Supported Locales Keys

```php
	/**
	 * Returns supported languages language key
	 *
	 * @return array 	keys of supported languages
	 */
	public function getSupportedLanguagesKeys()

	//Should be called like this:
	{{ LaravelLocalization::getSupportedLanguagesKeys() }}
```

This function will return an array with all the keys for the supported locales.

### Set Locale

```php
	/**
     * Set and return current locale
     *
     * @param  string $locale	        Locale to set the App to (optional)
     *
     * @return string 			        Returns locale (if route has any) or null (if route does not have a locale)
     */
    public function setLocale($locale = null)

	//Should be called in a view like this:
	{{ LaravelLocalization::setLocale(optional string $locale) }}
```

This function will change the application's current locale.
If the locale is not passed, the locale will be determined via a cookie (if stored previously), the session (if stored previously), browser Accept-Language header or the default application locale (depending on your config file).

The function has to be called in the prefix of any route that should be translated (see Filters sections for further information).

### Get Current Locale

```php
    /**
     * Returns current language
     *
     * @return string current language
     */
    public function getCurrentLocale()

	//Should be called in a view like this:
	{{ LaravelLocalization::getCurrentLocale() }}
```

This function will return the key of the current locale.

### Get Current Locale Name

```php
	/**
	 * Returns current locale name
	 *
	 * @return string current locale name
	 */
	public function getCurrentLocaleName()

	//Should be called in a view like this:
	{{ LaravelLocalization::getCurrentLocaleName() }}
```

This function will return current locale's name as string (English/Spanish/Arabic/ ..etc).


### Get Current Locale Direction

```php
	/**
	 * Returns current locale direction
	 *
	 * @return string current locale direction
	 */
	public function getCurrentLocaleDirection()

	//Should be called in a view like this:
	{{ LaravelLocalization::getCurrentLocaleDirection() }}
```

This function will return current locale's direction as string (ltr/rtl).


### Get Current Locale Script

```php
	/**
	 * Returns current locale script
	 *
	 * @return string current locale script
	 */
	public function getCurrentLocaleScript()

	//Should be called in a view like this:
	{{ LaravelLocalization::getCurrentLocaleScript() }}
```

This function will return the [ISO 15924](http://www.unicode.org/iso15924) code for the current locale script as a string; "Latn", "Cyrl", "Arab", etc.


## Creating a language selector

If you're supporting multiple locales in your project you will probably want to provide the users with a way to change language. Below is a simple example of blade template code you can use to create your own language selector.

```
<ul class="language_bar_chooser">
	@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
        <li>
            <a rel="alternate" hreflang="{{$localeCode}}" href="{{LaravelLocalization::getLocalizedURL($localeCode) }}">
                {{{ $properties['native'] }}}
            </a>
        </li>
	@endforeach
</ul>
```


## Translated Routes

You can adapt your URLs depending on the language you want to show them. For example, http://url/en/about and http://url/es/acerca (acerca is about in spanish) or http://url/en/view/5 and http://url/es/ver/5 (view == ver in spanish) would be redirected to the same controller using the proper filter and setting up the translation files as follows:

As it is a middleware, first you have to register in on your `app/Http/Kernel.php` file like this:


```php
	<?php namespace App\Http;

	use Illuminate\Foundation\Http\Kernel as HttpKernel;

	class Kernel extends HttpKernel {
		/**
		 * The application's route middleware.
		 *
		 * @var array
		 */
		protected $routeMiddleware = [
			/**** OTHER MIDDLEWARE ****/
			'localize' => 'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes',
			// TRANSLATE ROUTES MIDDLEWARE
		];

	}
```

```php
	// app/Http/routes.php

	Route::group(
	[
		'prefix' => LaravelLocalization::setLocale(),
		'middleware' => [ 'localize' ] // Route translate middleware
	],
	function()
	{
      /** ADD ALL LOCALIZED ROUTES INSIDE THIS GROUP **/
      Route::get('/', function()
      {
      	// This routes is useless to translate
      	return View::make('hello');
      });

      Route::get(LaravelLocalization::transRoute('routes.about'),function(){
          return View::make('about');
      });
      Route::get(LaravelLocalization::transRoute('routes.view'),function($id){
          return View::make('view',['id'=>$id]);
      });
	});

	/** OTHER PAGES THAT SHOULD NOT BE LOCALIZED **/
```
In the routes file you just have to add the `LaravelLocalizationRoutes` filter and the `LaravelLocalization::transRoute` function to every route you want to translate using the translation key.

Then you have to create the translation files and add there every key you want to translate. I suggest to create a routes.php file inside your resources/lang/language_abbreviation folder. For the previous example, I have created two translations files, these two files would look like:
```php
	// resources/lang/en/routes.php
    return [
      "about" 		=> 	"about",
      "view" 		=> 	"view/{id}", //we add a route parameter
      // other translated routes
	];
```
```php
	// resources/lang/es/routes.php
    return [
      "about" 		=> 	"acerca",
      "view" 		=> 	"ver/{id}", //we add a route parameter
      // other translated routes
	];
```

Once files are saved, you can access to http://url/en/about , http://url/es/acerca , http://url/en/view/5 and http://url/es/ver/5 without any problem. The `getLanguageBar` function would work as desired and it will translate the routes to all translated languages (don't forget to add any new route to the translation file).

## Events

You can capture the URL parameters during translation if you wish to translate them too. To do so, just create an event listener for the `routes.translation` event like so:

```php
Event::listen('routes.translation', function($locale, $attributes)
{
	// Do your magic

    return $attributes;
});
```

Be sure to pass the locale and the attributes as parameters to the closure. You may also use Event Subscribers, see: [http://laravel.com/docs/events#event-subscribers](http://laravel.com/docs/events#event-subscribers)

## Config

### Config Files

In order to edit the default configuration for this package you may execute:

```
php artisan vendor:publish --provider="Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider"
```

After that, `config/laravellocalization.php` will be created. Inside this file you will find all the fields that can be edited in this package.

### Service Providers

Otherwise, you can use `ConfigServiceProviders` (check <a href="https://raw.githubusercontent.com/mcamara/laravel-localization/master/src/config/config.php">this file</a> for more info).

For example, editing the default config service provider that Laravel loads when it's installed. This file is placed in `app/providers/ConfigServicePovider.php` and would look like this:

```php
	<?php namespace App\Providers;

	use Illuminate\Support\ServiceProvider;

	class ConfigServiceProvider extends ServiceProvider {
		public function register()
		{
			config([
				'laravellocalization.supportedLocales' => [
					'ace' => array( 'name' => 'Achinese', 'script' => 'Latn', 'native' => 'Aceh' ),
					'ca'  => array( 'name' => 'Catalan', 'script' => 'Latn', 'native' => 'català' ),
					'en'  => array( 'name' => 'English', 'script' => 'Latn', 'native' => 'English' ),
				],

				'laravellocalization.useAcceptLanguageHeader' => true,

				'laravellocalization.hideDefaultLocaleInURL' => true
			]);
		}

	}
```

This config would add Catalan and Achinese as languages and override any other previous supported locales and all the other options in the package.

You can create your own config providers and add them on your application config file (check the providers array in `config/app.php`).

## Changelog
View changelog here -> [changelog](CHANGELOG.md)

## License

Laravel Localization is an open-sourced laravel package licensed under the MIT license
