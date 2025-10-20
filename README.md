# Laravel Localization

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mcamara/laravel-localization.svg?style=flat-square)](https://packagist.org/packages/mcamara/laravel-localization)
[![Total Downloads](https://img.shields.io/packagist/dt/mcamara/laravel-localization.svg?style=flat-square)](https://packagist.org/packages/mcamara/laravel-localization)
![GitHub Actions](https://github.com/mcamara/laravel-localization/actions/workflows/run-tests.yml/badge.svg)
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
 5.2.x-5.4.x (PHP 7 not required)  | 1.2.
 5.2.0-6.x (PHP version >= 7 required) | 1.4.x
 6.x-10.x (PHP version >= 7 required) | 1.8.x
 10.x-12.x (PHP version >= 8.2 required) | 2.0.x

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

 - **supportedLocales** Languages of your app (Default: English & Spanish).
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
    protected $middlewareAliases = [
        /**** OTHER MIDDLEWARE ****/
        'localize'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
        'localizationRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
        'localeSessionRedirect'   => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        'localeCookieRedirect'    => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
        'localeViewPath'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class
    ];
}
```

If you are using Laravel 11, you may register in `bootstrap/app.php` file in closure `withMiddleware`:

```php
return Application::configure(basePath: dirname(__DIR__))
    // Other application configurations
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            /**** OTHER MIDDLEWARE ALIASES ****/
            'localize'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect'   => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect'    => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
        ]);
    })
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

Once this route group is added to the routes file, a user can access all locales added into `supportedLocales` (`en` and `es` by default).
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

If there is no locale present in the url, then this middleware will check the following

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
**Example of a localized link using routes with attributes**

```php
// An array of attributes can be provided.
// Returns /en/archive/ghosts, /fr/archive/fantômes, /pt/arquivo/fantasmas, etc.
<a href="{{ LaravelLocalization::getURLFromRouteNameTranslated( App::currentLocale(), 'routes.archive', array('category' => 'ghosts')) }}">Ghost Stories</a>
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

### Get Current Locale Regional Name
Return current locale's regional name as string (en_GB/en_US/fr_FR/ ..etc).

```php
{{ LaravelLocalization::getCurrentLocaleRegional() }}
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

It is necessary that at least the `localize` middleware in loaded in your `Route::group` middleware (See [installation instruction](#installation)).

For each language, add a `routes.php` into `resources/lang/**/routes.php` folder.
The file contains an array with all translatable routes. For example, like this:

> Keep in mind: starting from [Laravel 9](https://laravel.com/docs/9.x/upgrade#the-lang-directory), the `resources/lang` folder is now located in the root project folder (`lang`).
> If your project has `lang` folder in the root, you must add a `routes.php` into `lang/**/routes.php` folder.

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

> [!CAUTION]
> By default, this package is not compatible with Laravel’s route caching.
> Running commands such as `php artisan route:cache` or `php artisan optimize` will cause localized routes to return 404 errors.

To enable route caching for your localized routes, you may use the `LoadsTranslatedCachedRoutes` trait provided by this package.
Depending on your Laravel version, you will need to apply the trait differently:

**Before Laravel 11**    
If your application includes a `RouteServiceProvider`, add the `LoadsTranslatedCachedRoutes` trait to it:

```php
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class RouteServiceProvider extends ServiceProvider
{
    use LoadsTranslatedCachedRoutes;
}
```

**After Laravel 11**    
For Laravel 11 and newer, add the `LoadsTranslatedCachedRoutes` trait to your `AppServiceProvider`, and register the cached routes within the boot method:

```php
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class AppServiceProvider extends ServiceProvider
{
    use LoadsTranslatedCachedRoutes;

    public function boot(): void
    {
        RouteServiceProvider::loadCachedRoutesUsing(fn () => $this->loadCachedRoutes());

        // ...
    }
}
```

Once configured, use the following command to cache your localized routes instead of `php artisan route:cache`:
```bash
php artisan route:trans:cache
```

To clear the localized route cache, use:
```bash
php artisan route:trans:clear
```

To get a list of routes for a given locale, use:
```bash
php artisan route:trans:list {locale}

# Example:
php artisan route:trans:list en
```

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


Another way to solve this is to put http method to config to 'laravellocalization.httpMethodsIgnored'
to prevent of processing this type of requests

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

In a typical request lifecycle, your application is bootstrapped automatically — allowing this package to detect the active route and set the appropriate locale.
However, when running tests, the application is bootstrapped before any request is made. As a result, the package can’t determine the current route, which often leads to a `404` error.

To handle this, you can manually define the locale prefix in your tests by refreshing the application with a specific locale:

### PHPUnit
```php
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mcamara\LaravelLocalization\LaravelLocalization;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplicationWithLocale(string $locale): void
    {
        self::tearDown();
        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . $locale);
        self::setUp();
    }

    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY);
        parent::tearDown();
    }
}
```

```php
final class HomeControllerTest extends TestCase
{
    public function it_can_visit_the_home_page()
    {
        $this->refreshApplicationWithLocale('en');

        $response = $this->get('/en');

        $response->assertStatus(200);
    }
}
```

### Pest
```php
// Pest.php
use Mcamara\LaravelLocalization\LaravelLocalization;

function refreshApplicationWithLocale(string $locale): void
{
    /** @var \Tests\TestCase $test */
    $test = test();

    $test->tearDown();
    putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . $locale);
    $test->setUp();
}

pest()->afterEach(function () {
    putenv(LaravelLocalization::ENV_ROUTE_KEY);
});
```
```php
// YourTest.php
test('it can visit the home page', function () {
    refreshApplicationWithLocale('en');

    $response = $this->get('/en');

    $response->assertStatus(200);
});
```


## Collaborators
- [Adam Nielsen (iwasherefirst2)](https://github.com/iwasherefirst2)

Ask [mcamara](https://github.com/mcamara) if you want to be one of them!

## Changelog

View changelog here -> [changelog](CHANGELOG.md)

## License

Laravel Localization is an open-sourced laravel package licensed under the MIT license
