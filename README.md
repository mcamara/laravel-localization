# Laravel Localization

[![Join the chat at https://gitter.im/mcamara/laravel-localization](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/mcamara/laravel-localization?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Latest Stable Version](https://poser.pugx.org/mcamara/laravel-localization/version.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Total Downloads](https://poser.pugx.org/mcamara/laravel-localization/d/total.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Build Status](https://travis-ci.org/mcamara/laravel-localization.png)](https://travis-ci.org/mcamara/laravel-localization)
[![Open Source Helpers](https://www.codetriage.com/mcamara/laravel-localization/badges/users.svg)](https://www.codetriage.com/mcamara/laravel-localization)
[![Reviewed by Hound](https://img.shields.io/badge/Reviewed_by-Hound-8E64B0.svg)](https://houndci.com)

Easy i18n localization for Laravel, an useful tool to combine with Laravel localization classes.

## Collaborators
- [Adam Nielsen (iwasherefirst2)](https://github.com/iwasherefirst2)

Ask [mcamara](https://github.com/mcamara) if you want to be one of them!

## Table of Contents

- <a href="#installation">Installation</a>
    - <a href="#composer">Composer</a>
    - <a href="#manually">Manually</a>
    - <a href="#laravel">Laravel</a>
- <a href="#config">Config</a>
    - <a href="#config-files">Config files</a>
    - <a href="#service-providers">Service providers</a>
- <a href="#usage">Usage</a>
    - <a href="#middleware">Middleware</a>
- <a href="#helpers">Helpers</a>
    - <a href="#route-model-binding">Route Model Binding</a>
- <a href="#translated-routes">Translated Routes</a>
- <a href="#caching-routes">Caching routes</a>
- <a href="#changelog">Changelog</a>
- <a href="#testing">Testing</a>
- <a href="#common-issues">Common Issues</a>
    - <a href="#post-is-not-working">POST is not working</a>
    - <a href="#methodnotallowedhttpexception">MethodNotAllowedHttpException</a>
    - <a href="#validation-message-is-only-in-default-locale">Validation message is always in default locale</a>
- <a href="#license">License</a>

## Laravel compatibility

 Laravel      | laravel-localization
:-------------|:----------
 4.0.x        | 0.13.x
 4.1.x        | 0.13.x
 4.2.x        | 0.15.x
 5.0.x/5.1.x  | 1.0.x
 5.2.x-5.4.x (PHP 7 not required)  | 1.2.x
 5.2.x-5.8.x (PHP 7 required) | 1.3.x
 5.2.0-6.x (PHP 7 required) | 1.4.x

## Installation

Install the package via composer: `composer require mcamara/laravel-localization`

### For Laravel 5.4 and below:

For older versions of the framework, follow the steps below:

Register the service provider in `config/app.php`

```php
        'providers' => [
		// [...]
                Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class,
        ],
```

You may also register the `LaravelLocalization` facade:

```php
        'aliases' => [
		// [...]
                'LaravelLocalization' => Mcamara\LaravelLocalization\Facades\LaravelLocalization::class,
        ],
```

## Config

### Config Files

In order to edit the default configuration (where for e.g. you can find `supportedLocales`) for this package you may execute:

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

The packages ships with useful middleware. The behavior depends on the settings of `hideDefaultLocaleInURL`
and `useAcceptLanguageHeader` in `config/laravellocalization.php`:

#### LocaleSessionRedirect

Whenever a locale is present in the url, it will be stored in the session by this middleware.

In there is no locale present in the url, then this middleware will check the following

 - If no locale is saved in session and `useAcceptLanguageHeader` is set to true, compute locale from browser and redirect to url with locale.
 - If a locale is saved in session redirect to url with locale, unless its the default locale and `hideDefaultLocaleInURL` is set to true.

For example, if a user navigates to http://url-to-laravel/test  and `en` is the current locale, it would redirect him automatically to http://url-to-laravel/en/test.

#### LaravelLocalizationRedirectFilter

When the default locale is present in the url and `hideDefaultLocaleInURL` is set to true, then the middleware redirects to the url without locale.

For example, if `es` is the default locale, then http://url-to-laravel/es/test would be redirected to http://url-to-laravel/test and the`App::getLocale()` would be
set to `es`.

#### LaravelLocalizationViewPath

Register this middleware to set current locale as view-base-path.

Now you can wrap your views in language-based folders like the translation files.

`resources/views/en/`, `resources/views/fr`, ...

#### Register Middleware

You may register the above middleware in the `app/Http/Kernel.php` file and in the `Route:group` like this:

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
		'localize'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
		'localizationRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
		'localeSessionRedirect'   => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        'localeViewPath'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class
	];
}
```

```php
// routes/web.php

Route::group(
[
	'prefix' => LaravelLocalization::setLocale(),
	'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
],
function()
{
	//...
});

/** OTHER PAGES THAT SHOULD NOT BE LOCALIZED **/

```

### Map your own custom lang url segments

As you can modify the supportedLocales even by renaming their keys, it is possible to use the string ```uk``` instead of ```en-GB``` to provide custom lang url segments. Of course, you need to prevent any collisions with already existing keys and should stick to the convention as long as possible. But if you are using such a custom key, you have to store your mapping to the ```localesMapping``` array. This ```
localesMapping``` is needed to enable the LanguageNegotiator to correctly assign the desired locales based on HTTP Accept Language Header. Here is a quick example how to map HTTP Accept Language Header 'en-GB' to url segment 'uk':

```php
// config/laravellocalization.php

'localesMapping' => [
	'en-GB' => 'uk'
],
```

After that ```http://url-to-laravel/en-GB/a/b/c``` becomes ```http://url-to-laravel/uk/a/b/c```.

```php
LaravelLocalization::getLocalizedURL('en-GB', 'a/b/c'); // http://url-to-laravel/uk/a/b/c
LaravelLocalization::getLocalizedURL('uk', 'a/b/c'); // http://url-to-laravel/uk/a/b/c
```

## Helpers

This package comes with some useful functions, like:


### Get localized url

```php
    /**
     * Returns an URL adapted to $locale or current locale.
     *
     * @param string      $url    URL to adapt. If not passed, the current url would be taken.
     * @param string|bool $locale Locale to adapt, false to remove locale
     *
     * @throws UnsupportedLocaleException
     *
     * @return string URL translated
     */
    public function localizeURL($url = null, $locale = null)
```

//Should be called in a view like this:
{{ LaravelLocalization::localizeURL('/about') }}


It returns a URL localized to the desired locale (if no locale is given, it uses current locale).


#### Route Model Binding

Note that [route model binding]([https://laravel.com/docs/master/routing#route-model-binding]) is taken into account when generating the localized route.


### Get Clean routes

```php
/**
 * It returns an URL without locale (if it has it)
 * Convenience function wrapping getLocalizedURL(false)
 *
 * @param  string|false 	$url	  URL to clean, if false, current url would be taken
 *
 * @return stringURL 			  with no locale in path
 */
public function getNonLocalizedURL($url = null)

//Should be called in a view like this:
{{ LaravelLocalization::getNonLocalizedURL('/es/about') }}
```

It returns a URL clean of any localization.

### Get URL for an specific translation key

```php
/**
 * Returns an URL adapted to the route name and the locale given
 *
 * @throws UnsupportedLocaleException
 *
 * @param  string|boolean 		$locale 		Locale to adapt
 * @param  string 			$transKeyName  		Translation key name of the url to adapt
 * @param  array 			$attributes  		Attributes for the route (only needed if transKeyName needs them)
 *
 * @return string|false 					URL translated
 */
public function getURLFromRouteNameTranslated($locale, $transKeyName, $attributes = array())

//Should be called in a view like this:
{{ LaravelLocalization::getURLFromRouteNameTranslated('es', 'routes.about') }}
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

### Get Supported Locales Custom Order

```php
/**
 * Return an array of all supported Locales but in the order the user
 * has specified in the config file. Useful for the language selector.
 *
 * @return array
 */
public function getLocalesOrder()

//Should be called like this:
{{ LaravelLocalization::getLocalesOrder() }}
```

This function will return all supported locales but in the order specified in the configuration file. You can use this function to print locales in the language selector.

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
 * @return string 			Returns locale (if route has any) or null (if route does not have a locale)
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

```blade
<ul>
    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
        <li>
            <a rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                {{ $properties['native'] }}
            </a>
        </li>
    @endforeach
</ul>
```
Here default language will be forced in getLocalizedURL() to be present in the URL even `hideDefaultLocaleInURL = true`.

Note that <a href="#route-model-binding">Route Model Binding</a> is supported.

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
function() {
	/** ADD ALL LOCALIZED ROUTES INSIDE THIS GROUP **/
	Route::get('/', function() {
		// This routes is useless to translate
		return View::make('hello');
	});

	Route::get(LaravelLocalization::transRoute('routes.about'), function() {
		return View::make('about');
	});

	Route::get(LaravelLocalization::transRoute('routes.view'), function($id) {
		return View::make('view',['id'=>$id]);
	});
});

/** OTHER PAGES THAT SHOULD NOT BE LOCALIZED **/
```
In the routes file you just have to add the `LaravelLocalizationRoutes` filter and the `LaravelLocalization::transRoute` function to every route you want to translate using the translation key.

Then you have to create the translation files and add there every key you want to translate. I suggest to create a routes.php file inside your `resources/lang/language_abbreviation` folder. For the previous example, I have created two translations files, these two files would look like:
```php
// resources/lang/en/routes.php
return [
	"about" 	=> 	"about",
	"view" 		=> 	"view/{id}", //we add a route parameter
	// other translated routes
];
```
```php
// resources/lang/es/routes.php
return [
	"about" 	=> 	"acerca",
	"view" 		=> 	"ver/{id}", //we add a route parameter
	// other translated routes
];
```

Once files are saved, you can access to http://url/en/about , http://url/es/acerca , http://url/en/view/5 and http://url/es/ver/5 without any problem.

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

## Caching routes

More information on support on [cached (translated) routes here](CACHING.md).

Note that the separate [czim/laravel-localization-route-cache](https://github.com/czim/laravel-localization-route-cache) package is no longer required.

## Common Issues

### POST is not working

This may happen if you do not localize your action route that is inside your `Routes::group`.
This may cause a redirect, which then changes the post request into a get request.
To prevent that, simply use the [localize helper](#get-localized-url).

For example, if you use `Auth::routes()` and put them into your `Route::group` Then

```
<form action="/logout" method="POST">
<button>Logout</button>
</form>
```

will not work. Instead, one has to use

```php
<form action="{{  \LaravelLocalization::localizeURL('/logout') }} " method="POST">
<button>Logout</button>
</form>
```

### MethodNotAllowedHttpException

If you do not localize your post url and use a redirect middleware,
then the post request gets redirected as a get request.
If you have not defined such a get route, you will cause this exception.

To localize your post url see the example in [POST is not working](#post-is-not-working).

### Validation message is only in default locale

This also happens if you did not localize your post url.
If you don't localize your post url, the default locale is set while validating,
and when returning to `back()` it shows the validation message in default locale.

To localize your post url see the example in [POST is not working](#post-is-not-working).

## Testing

During the test setup, the called route is not yet known. This means no language can be set.
When a request is made during a test, this results in a 404 - without the prefix set the localized route does not seem to exist.

To fix this, you can use this function to manually set the language prefix:
```php
// TestCase.php
protected function refreshApplicationWithLocale($locale)
{
    self::tearDown();
    putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . $locale);
    self::setUp();
}

protected function tearDown()
{
    putenv(LaravelLocalization::ENV_ROUTE_KEY);
    parent::tearDown();
}

// YourTest.php
public function testBasicTest()
{
    $this->refreshApplicationWithLocale('en');
    // Testing code
}
```

## Changelog

View changelog here -> [changelog](CHANGELOG.md)

## License

Laravel Localization is an open-sourced laravel package licensed under the MIT license
