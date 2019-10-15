# Laravel Localization

[![Join the chat at https://gitter.im/mcamara/laravel-localization](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/mcamara/laravel-localization?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Latest Stable Version](https://poser.pugx.org/mcamara/laravel-localization/version.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Total Downloads](https://poser.pugx.org/mcamara/laravel-localization/d/total.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Build Status](https://travis-ci.org/mcamara/laravel-localization.png)](https://travis-ci.org/mcamara/laravel-localization)
[![Open Source Helpers](https://www.codetriage.com/mcamara/laravel-localization/badges/users.svg)](https://www.codetriage.com/mcamara/laravel-localization)
[![Reviewed by Hound](https://img.shields.io/badge/Reviewed_by-Hound-8E64B0.svg)](https://houndci.com)

Easy i18n localization for Laravel, an useful tool to combine with Laravel localization classes.

The package offers the following:

 - Detect language from browser
 - Smart redirects (Save locale in session/cookie)
 - Smart routing (Define your routes only once, no matter how many languages you use)
 - Translatable Routes
 - Supports caching & testing
 - Option to hide default locale in url
 - Many snippets and helpers (like language selector)

## Table of Contents

- <a href="#installation">Installation</a>
- <a href="#usage">Usage</a>
    - <a href="#redirect-middleware">Redirect Middleware</a>
    - <a href="#localize-links">Localize links</a>
- <a href="#helpers">Helpers</a>
    - <a href="#route-model-binding">Route Model Binding</a>
- <a href="#translated-routes">Translated Routes</a>
- <a href="#caching-routes">Caching routes</a>
- <a href="#testing">Testing</a>
- <a href="#collaborators">Collaborators</a>
- <a href="#changelog">Changelog</a>
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

For Laravel 5.4 and below it necessary to [register the service provider](/ADDITIONS.md#for-laravel-5.4-and-below).

### Config Files

In order to edit the default configuration you may execute:

```
php artisan vendor:publish --provider="Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider"
```

After that, `config/laravellocalization.php` will be created.

The configuration options are:

 - **supportedLocales** Langauges of your app (Default: English & Spanish).
 - **useAcceptLanguageHeader** If true, then automatically detect language from browser.
 - **hideDefaultLocaleInURL** If true, then do not show default locale in url.
 - **localesOrder** Sort languages in custom order.
 - **localesMapping** Rename url locales.
 - **utf8suffix** Allow changing utf8suffix for CentOS etc.
 - **urlsIgnored** Ignore specific urls.

### Register Middleware

You may register the package middleware in the `app/Http/Kernel.php` file:

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
        'localeCookieRedirect'    => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
        'localeViewPath'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class
	];
}
```

## Usage

Add the following to your routes file:

```php
// routes/web.php

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

Once this route group is added to the routes file, a user can access all locales added into `supportedLocales` ('en' and 'es' by default).
For example, the above route file creates the following addresses:

```
// Set application language to English
http://url-to-laravel/en
http://url-to-laravel/en/test

// Set application language to Spanish
http://url-to-laravel/es
http://url-to-laravel/es/test

// Set application language to English or Spanish (depending on browsers default locales)
// if nothing found set to default locale
http://url-to-laravel
http://url-to-laravel/test
```
The package sets your application locale `App::getLocale()` according to your url. You may translate your files as explained in [Laravel Localization docs](http://laravel.com/docs/localization).

You may add

### Recommendations

***1.***: It is **strongly** recommended to use a [redirecting middleware](#redirect-middleware).
Urls without locale should only be used to determine browser/default locale and to redirect to the [localized url](#localized-urls).
Otherwise, when search engine robots crawl for example `http://url-to-laravel/test` they may get different language content for each visit.
Also having multiple urls for the same content creates a SEO duplicate-content issue.

***2.***: It is **strongly** recommended to [localize your links](#localized-urls), even if you use a redirect middleware.
Otherwise, you will cause at least one redirect each time a user clicks on a link.
Also, any action url from a post form must be localized, to prevent that it gets redirected to a get request.


### Redirect Middleware

The following redirection middleware depends on the settings of `hideDefaultLocaleInURL`
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



### Set view-base-path to current locale

Register the middleware `LaravelLocalizationViewPath` to set current locale as view-base-path.

Now you can wrap your views in language-based folders like the translation files.

`resources/views/en/`, `resources/views/fr`, ...


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

### Localized URLs
Localized URLS  taken into account [route model binding]([https://laravel.com/docs/master/routing#route-model-binding]) when generating the localized route,
aswell as the `hideDefaultLocaleInURL` and [Translated Routes](#translated-routes) settings.


#### Get localized URL

```php
// If current locale is Spanish, it returns `/es/test`
{{ LaravelLocalization::localizeURL('/test') }}#
```

Links may be localized like this:

    <a href="{{ LaravelLocalization::localizeUrl('(/test)') }}">@lang('Follow this link')</a>

A form may be localized like this:

    <form action="{{ LaravelLocalization::localizeUrl('(/update)') }}" method="POST">
        @csrf
        // ...
    </form>

#### Get localized URL for an specific locale
Get current URL in specific locale:

```php
// Returns localized url of `test` for English locale.
{{ LaravelLocalization::getLocalizedURL('en') }}
```

## Helpers

This package comes with a bunch of helpers.

### Get Clean routes

Returns a URL clean of any localization.

```php
// Returns `/test`
{{ LaravelLocalization::getNonLocalizedURL('/es/test') }}
```

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

### Get Supported Locales Custom Order

This function will return all supported locales but in the order specified in the configuration file. You can use this function to print locales in the language selector.

```php
{{ LaravelLocalization::getLocalesOrder() }}
```

### Get Supported Locales Keys

This function will return an array with all the keys for the supported locales.

```php
{{ LaravelLocalization::getSupportedLanguagesKeys() }}
```

### Get Current Locale

This function will return the key of the current locale.

```php
{{ LaravelLocalization::getCurrentLocale() }}
```

### Get Current Locale Name
This function will return current locale's name as string (English/Spanish/Arabic/ ..etc).

```php
{{ LaravelLocalization::getCurrentLocaleName() }}
```

### Get Current Locale Direction

This function will return current locale's direction as string (ltr/rtl).


```php
{{ LaravelLocalization::getCurrentLocaleDirection() }}
```



### Get Current Locale Script
This function will return the [ISO 15924](http://www.unicode.org/iso15924) code for the current locale script as a string; "Latn", "Cyrl", "Arab", etc.

```php
{{ LaravelLocalization::getCurrentLocaleScript() }}
```


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

## Collaborators
- [Adam Nielsen (iwasherefirst2)](https://github.com/iwasherefirst2)

Ask [mcamara](https://github.com/mcamara) if you want to be one of them!

## Changelog

View changelog here -> [changelog](CHANGELOG.md)

## License

Laravel Localization is an open-sourced laravel package licensed under the MIT license
