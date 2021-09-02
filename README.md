# Laravel Localization

## About Fork
This package is a fork of [mcamara/laravel-localization](https://github.com/mcamara/laravel-localization) with a support for localization switch by domain names like to

 - exmaple.com - english version
 - example.es - versión en español
 - example.ru - русская версия

or

 - en.exmaple.com - english version
 - es.example.com - versión en español
 - ru.example.com - русская версия
 
Read a more information about new feature [Localization Switch By Domain Names](#localization-switch-by-domain-names)

## Introduction

Easy i18n localization for Laravel, an useful tool to combine with Laravel localization classes.

The package offers the following:

 - Detect language from browser
 - Smart redirects (Save locale in session/cookie)
 - Smart routing (Define your routes only once, no matter how many languages you use)
 - Translatable Routes
 - Supports caching & testing
 - Option to hide default locale in url
 - Many snippets and helpers (like language selector)
 - **Smart redirects between domain names**

## Table of Contents

- <a href="#installation">Installation</a>
- <a href="#one-domain-usage">One Domain Usage</a>
- <a href="#localization-switch-by-domain-names">Localization Switch By Domain Names</a>
    - <a href="#domains-env-properties">Domains .env properties</a>
- <a href="#redirect-middleware">Redirect Middleware</a>
- <a href="#helpers">Helpers</a>
- <a href="#translated-routes">Translated Routes</a>
- <a href="#caching-routes">Caching routes</a>
- <a href="#testing">Testing</a>
- <a href="#common-issues">Common Issues</a>
    - <a href="#post-is-not-working">POST is not working</a>
    - <a href="#methodnotallowedhttpexception">MethodNotAllowedHttpException</a>
    - <a href="#validation-message-is-only-in-default-locale">Validation message is always in default locale</a>
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
 5.2.0-8.x (PHP 7 required) | 1.6.x

## Installation

Install the package via composer: `composer require movemoveapp/laravel-localization`

For Laravel 5.4 and below it necessary to [register the service provider](/ADDITIONS.md#for-laravel-5.4-and-below).

### Config Files

In order to edit the default configuration you may execute:

```
php artisan vendor:publish --provider="MoveMoveIO\LaravelLocalization\LaravelLocalizationServiceProvider"
```

After that, `config/laravellocalization.php` will be created.

The configuration options are:

 - **supportedLocales** Languages of your app (Default: English & Spanish) and localization domain name.
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
        'localize'                      => \Move\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
        'localizationRedirect'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
        'localeSessionRedirect'         => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        'localeCookieRedirect'          => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
        'localeViewPath'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
        /*** A new featrue with domain names ***/
        'localizationDomainRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationDomainRedirectFilter::class,
    ];
}
```

## One Domain Usage

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

Once this route group is added to the routes file, a user can access all locales added into `supportedLocales` (`en`, `es` and `ru` by default).
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
The package sets your application locale `App::getLocale()` according to your url. The locale may then be used for [Laravel's localization features](http://laravel.com/docs/localization).

You may add middleware to your group like this:

```php
Route::group(
[
	'prefix' => LaravelLocalization::setLocale(),
	'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
], function(){ //...
});
```

### Localization Switch By Domain Names
A first step, you should set up domains. For example, your project suppored tree locales:
 
- `en` is [example.com](https://example.com/) 
- `es` is [example.es](https://example.es/)
- `ru` is [example.ru](https://example.ru/)

In your `.env` configuration files you should call about domains as:

```php
LOCALIZATION_DOMAIN_NAME_EN=example.com
LOCALIZATION_DOMAIN_NAME_ES=example.es
LOCALIZATION_DOMAIN_NAME_RU=example.ru
```

Don't forget uncommented your locales in the `config/laravellocalization.php` config file in `supportedLocales`.

Add the following to your routes file:

```php
// routes/web.php

Route::group([
    'middleware' => [ 'localizationDomainRedirect' ]
], function()
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

For example, the above route file creates the following addresses:

```
// Set application language to English
https://example.com/
https://example.com/test

// Set application language to Spanish
https://example.es/
https://example.es/test

// Set application language to Russian
https://example.ru/
https://example.ru/test

```

#### Domains .env properties
To declare a domain name for a special location, you must declare the variable as `LOCALIZATION_DOMAIN_NAME_` and the location in uppercase. If the location uses a dash, it must be replaced with an underscore.

 - Locale `en` is declared as `LOCALIZATION_DOMAIN_NAME_EN`
 - Locale `uz-Arab` is declared as `LOCALIZATION_DOMAIN_NAME_UZ_ARAB`
 - Locale `ca-valencia` is declared as `LOCALIZATION_DOMAIN_NAME_CA_VALENCIA`
 - etc...

### Recommendations

***1.***: It is **strongly** recommended to use a [redirecting middleware](#redirect-middleware).
Urls without locale should only be used to determine browser/default locale and to redirect to the [localized url](#localized-urls).
Otherwise, when search engine robots crawl for example `http://url-to-laravel/test` they may get different language content for each visit.
Also having multiple urls for the same content creates a SEO duplicate-content issue.

***2.***: It is **strongly** recommended to [localize your links](#localized-urls), even if you use a redirect middleware.
Otherwise, you will cause at least one redirect each time a user clicks on a link.
Also, any action url from a post form must be localized, to prevent that it gets redirected to a get request.


## Redirect Middleware

The following redirection middleware depends on the settings of `hideDefaultLocaleInURL`
and `useAcceptLanguageHeader` in `config/laravellocalization.php`:

### LocaleSessionRedirect

Whenever a locale is present in the url, it will be stored in the session by this middleware.

In there is no locale present in the url, then this middleware will check the following

 - If no locale is saved in session and `useAcceptLanguageHeader` is set to true, compute locale from browser and redirect to url with locale.
 - If a locale is saved in session redirect to url with locale, unless its the default locale and `hideDefaultLocaleInURL` is set to true.

For example, if a user navigates to http://url-to-laravel/test  and `en` is the current locale, it would redirect him automatically to http://url-to-laravel/en/test.

### LocaleCookieRedirect

Similar to LocaleSessionRedirect, but it stores value in a cookie instead of a session.

Whenever a locale is present in the url, it will be stored in the cookie by this middleware.

In there is no locale present in the url, then this middleware will check the following

 - If no locale is saved in cookie and `useAcceptLanguageHeader` is set to true, compute locale from browser and redirect to url with locale.
 - If a locale is saved in cookie redirect to url with locale, unless its the default locale and `hideDefaultLocaleInURL` is set to true.

For example, if a user navigates to http://url-to-laravel/test  and `de` is the current locale, it would redirect him automatically to http://url-to-laravel/de/test.


### LaravelLocalizationRedirectFilter

When the default locale is present in the url and `hideDefaultLocaleInURL` is set to true, then the middleware redirects to the url without locale.

For example, if `es` is the default locale, then http://url-to-laravel/es/test would be redirected to http://url-to-laravel/test and the`App::getLocale()` would be
set to `es`.


## Helpers

This package comes with a bunch of helpers.

### Localized URLs
Localized URLS  taken into account [route model binding]([https://laravel.com/docs/master/routing#route-model-binding]) when generating the localized route,
aswell as the `hideDefaultLocaleInURL` and [Translated Routes](#translated-routes) settings.


#### Get localized URL

```php
    // If current locale is Spanish, it returns `/es/test`
    <a href="{{ LaravelLocalization::localizeUrl('/test') }}">@lang('Follow this link')</a>
```

#### Get localized URL for an specific locale
Get current URL in specific locale:

```php
// Returns current url with English locale.
{{ LaravelLocalization::getLocalizedURL('en') }}
```

### Get Clean routes

Returns a URL clean of any localization.

```php
// Returns /about
{{ LaravelLocalization::getNonLocalizedURL('/es/about') }}
```

### Get URL for an specific translation key

Returns a route, [localized](#translated-routes) to the desired locale. If the translation key does not exist in the locale given, this function will return false.


```php
// Returns /es/acerca
{{ LaravelLocalization::getURLFromRouteNameTranslated('es', 'routes.about') }}
```


### Get Supported Locales

Return all supported locales and their properties as an array.

```php
{{ LaravelLocalization::getSupportedLocales() }}
```



### Get Supported Locales Custom Order

Return all supported locales but in the order specified in the configuration file. You can use this function to print locales in the language selector.

```php
{{ LaravelLocalization::getLocalesOrder() }}
```

### Get Supported Locales Keys

Return an array with all the keys for the supported locales.

```php
{{ LaravelLocalization::getSupportedLanguagesKeys() }}
```

### Get Current Locale

Return the key of the current locale.

```php
{{ LaravelLocalization::getCurrentLocale() }}
```

### Get Current Locale Name
Return current locale's name as string (English/Spanish/Arabic/ ..etc).

```php
{{ LaravelLocalization::getCurrentLocaleName() }}
```

### Get Current Locale Native Name
Return current locale's native name as string (English/Español/عربى/ ..etc).

```php
{{ LaravelLocalization::getCurrentLocaleNative() }}
```

### Get Current Locale Direction

Return current locale's direction as string (ltr/rtl).


```php
{{ LaravelLocalization::getCurrentLocaleDirection() }}
```



### Get Current Locale Script
Return the [ISO 15924](http://www.unicode.org/iso15924) code for the current locale script as a string; "Latn", "Cyrl", "Arab", etc.

```php
{{ LaravelLocalization::getCurrentLocaleScript() }}
```

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

Note that Route Model Binding is supported.

## Translated Routes

You may translate your routes. For example, http://url/en/about and http://url/es/acerca (acerca is about in spanish)
or http://url/en/article/important-article and http://url/es/articulo/important-article (article is articulo in spanish) would be redirected to the same controller/view as follows:

It is necessary that at least the `localize` middleware in loaded in your `Route::group` middleware (See [installation instruction](#LaravelLocalizationRoutes)).

For each language, add a `routes.php` into `resources/lang/**/routes.php` folder.
The file contains an array with all translatable routes. For example, like this:

```php
<?php
// resources/lang/en/routes.php
return [
    "about"    =>  "about",
    "article"  =>  "article/{article}",
];
```
```php
<?php
// resources/lang/es/routes.php
return [
    "about"    =>  "acerca",
    "article"  =>  "articulo/{article}",
];
```

You may add the routes in `routes/web.php` like this:

```php
Route::group(['prefix' => LaravelLocalization::setLocale(),
              'middleware' => [ 'localize' ]], function () {

    Route::get(LaravelLocalization::transRoute('routes.about'), function () {
        return view('about');
    });

    Route::get(LaravelLocalization::transRoute('routes.article'), function (\App\Article $article) {
        return $article;
    });

    //,...
});
```

Once files are saved, you can access http://url/en/about , http://url/es/acerca , http://url/en/article/important-article and http://url/es/articulo/important-article without any problem.

### Translatable route parameters

Maybe you noticed in the previous example the English slug in the Spanish url:

    http://url/es/articulo/important-article

It is possible to have translated slugs, for example like this:

    http://url/en/article/important-change
    http://url/es/articulo/cambio-importante

However, in order to do this, each article must have many slugs (one for each locale).
Its up to you how you want to implement this relation. The only requirement for translatable route parameters is, that the relevant model implements the interface `LocalizedUrlRoutable`.

#### Implementing LocalizedUrlRoutable

To implement `\Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable`,
one has to create the function `getLocalizedRouteKey($locale)`, which must return for a given locale the translated slug. In the above example, inside the model article, `getLocalizedRouteKey('en')` should return `important-change` and `getLocalizedRouteKey('es')` should return `cambio-importante`.

#### Route Model Binding

To use [route-model-binding](https://laravel.com/docs/routing#route-model-binding), one  should overwrite the function `resolveRouteBinding($slug)`
in the model. The function should return the model that belongs to the translated slug `$slug`.
For example:

```php
public function resolveRouteBinding($slug)
{
        return static::findByLocalizedSlug($slug)->first() ?? abort(404);
}
```

#### Tutorial Video

You may want to checkout this [video](https://youtu.be/B1AUqCdizgc) which demonstrates how one may set up translatable route parameters.

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

To cache your routes, use:

``` bash
php artisan route:trans:cache
```

... instead of the normal `route:cache` command. Using `artisan route:cache` will **not** work correctly!

For the route caching solution to work, it is required to make a minor adjustment to your application route provision.

In your App's `RouteServiceProvider`, use the `LoadsTranslatedCachedRoutes` trait:

```php
<?php
class RouteServiceProvider extends ServiceProvider
{
    use \Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;
```


For more details see [here](CACHING.md).

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

## Collaborators
- [Adam Nielsen (iwasherefirst2)](https://github.com/iwasherefirst2)

Ask [mcamara](https://github.com/mcamara) if you want to be one of them!

## Changelog

View changelog here -> [changelog](CHANGELOG.md)

## License

Laravel Localization is an open-sourced laravel package licensed under the MIT license
