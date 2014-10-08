# Laravel Localization

[![Latest Stable Version](https://poser.pugx.org/mcamara/laravel-localization/version.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Total Downloads](https://poser.pugx.org/mcamara/laravel-localization/d/total.png)](https://packagist.org/packages/mcamara/laravel-localization) [![Build Status](https://travis-ci.org/mcamara/laravel-localization.png)](https://travis-ci.org/mcamara/laravel-localization)

Easy i18n localization for Laravel 4, an useful tool to combine with Laravel localization classes.


## Table of Contents

- <a href="#installation">Installation</a>
    - <a href="#composer">Composer</a>
    - <a href="#manually">Manually</a>
    - <a href="#laravel-4">Laravel 4</a>
- <a href="#usage">Usage</a>
    - <a href="#filters">Filters</a>
- <a href="#helpers">Helpers</a>
- <a href="#view">View</a>
- <a href="#translated-routes">Translated Routes</a>
- <a href="#config">Config</a>
- <a href="#changelog">Changelog</a>
- <a href="#license">License</a>

## Installation

### Composer

Add Laravel Localization to your `composer.json` file.

    "mcamara/laravel-localization": "0.14.*"

Run `composer install` to get the latest version of the package.

If you are using a laravel version lower than 4.2, you should use 0.13.* version.

### Manually

It's recommended that you use Composer, however you can download and install from this repository.

### Laravel 4

Laravel Localization comes with a service provider for Laravel 4. You'll need to add it to your `composer.json` as mentioned in the above steps, then register the service provider with your application.

Open `app/config/app.php` and find the `providers` key. Add `LaravelLocalizationServiceProvider` to the array.

```php
	...
	'Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider'
	...
```

You can also add an alias to the list of class aliases in the same app.php

```php
	...
	'LaravelLocalization'	=> 'Mcamara\LaravelLocalization\Facades\LaravelLocalization'
	...
```

To finish, publish the configuration file using the command `php artisan config:publish mcamara/laravel-localization` in your laravel root path. This will create the following file `app/config/packages/mcamara/laravel-localization/config.php`, containing the most common setting options.

## Usage

Laravel Localization uses the URL given for the request. In order to achieve this purpose, a group should be added into the routes.php file. It will filter all pages that must be localized.

```php
	// app/routes.php

	Route::group(array('prefix' => LaravelLocalization::setLocale()), function()
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

Once this group is added to the routes file, a user can access all locales added into 'supportedLocales' ('en' and 'es' by default, look at the config section to change that option). For example, a user can now access to two different locales, using the following addresses:

```
	http://url-to-laravel/en
	http://url-to-laravel/es
	http://url-to-laravel
```

If the locale is not present in the url or it is not defined in 'supportedLocales', the system will use the application default locale or the user's browser default locale (if defined in config file).

Once the locale is defined, the locale variable will be stored in a session, so it is not necessary to write the /lang/ section in the url after defining it once, using the last known locale for the user. If the user accesses to a different locale this session value would be changed, translating any other page he visits with the last chosen locale.

Templates files and all locale files should follow the [Lang class](http://laravel.com/docs/localization).

### Filters

Moreover, this package includes a filter to redirect all "non-localized" routes to a "localized" one (thanks to Sangar82).

So, if a user accesses to http://url-to-laravel/test and the system have this filter active and 'en' as a current locale for this user, it would redirect (301) him automatically to http://url-to-laravel/en/test. This is mainly used to avoid duplicate content and improve SEO performance.


```php
	// app/routes.php

	Route::group(
	array(
		'prefix' => LaravelLocalization::setLocale(),
		'before' => 'LaravelLocalizationRedirectFilter' // LaravelLocalization filter
	),
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
In order to active it, you just have to attach this filter to the routes you want to be accessible localized.

If you want to hide the default locale but always show other locales in the url, switch the 'hideDefaultLocaleInURL' config value to true. Once it's true, if the default locale is en (english) all URLs containing /en/ would be redirected to the same url without this fragment '/' but maintaining the locale as en (English).

**IMPORTANT** - When hideDefaultLocaleInURL is set to true, the unlocalized root is treated as the applications default locale ```app.locale```.  Because of this language negotiation using the Accept-Language header will **NEVER** occur when hideDefaultLocaleInURL is true.

## Helpers

This package comes with some useful functions, like:

### Get Clean routes

```php
	/**
     * It returns an URL without locale (if it has it)
     *
     * @param  string $url      URL to clean, if false, current url would be taken
     *
     * @return string           URL with no locale in path
     */
    public function getNonLocalizedURL($url = null)

	//Should be called in a view like this:
	{{ LaravelLocalization::getNonLocalizedURL(optional string $url) }}
```

It returns a URL clean of any localization.

### Get URL for an specific locale, there are two options here

```php
    /**
     * Returns an URL adapted to $locale
     *
     * @param  string $locale       Locale to adapt
     * @param  string $url          URL to adapt. If not passed, the current url would be taken
     *
     * @throws UnsupportedLocaleException
     *
     * @return string               URL translated
     */
    public function getLocalizedURL($locale, $url = null)

	//Should be called in a view like this:
	{{ LaravelLocalization::getLocalizedURL(string $locale, optional string $url) }}
```

or

```php
    /**
     * Returns an URL adapted to $locale or current locale
     *
     * @param  string $url				   URL to adapt. If not passed, the current url would be taken.
     * @param  string|boolean $locale	   Locale to adapt, false to remove locale
     *
     * @throws UnsupportedLocaleException
     *
     * @return string					   URL translated
     */
    public function localizeURL($url, $locale = null)
```

It returns a URL localized to the desired locale.

### Get URL for an specific translation key

```php
	/**
     * Returns an URL adapted to the route name and the locale given
     *
     * @param  string $locale 		    Locale to adapt
     * @param  array $transKeyNames  	Array containing the Translation key name of the url to adapt
     * @param  array $attributes  		Attributes for the route (only needed if transKeyName needs them)
     *
     * @return string|boolean  	        URL translated
     */
    public function getURLFromRouteNameTranslated($locale, $transKeyNames = array(), $attributes = array())

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

The function have to be called in the prefix of any route that should be translated (see Filters sections for further information).


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

This function will return current locale name as string (English/Spanish/Arabic/ ..etc).


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

This function will return current locale direction as string (ltr/rtl).


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

If you're supporting multiple locales in your project your going to want to provide the users with a way to change language.  Below is a simple example of blade template code you can use to create your own language selector.

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
```php
	// app/routes.php

	Route::group(
	array(
		'prefix' => LaravelLocalization::setLocale(),
		'before' => 'LaravelLocalizationRoutes' // Route translate filter
	),
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
          return View::make('view',array('id'=>$id));
      });
	});

	/** OTHER PAGES THAT SHOULD NOT BE LOCALIZED **/
```
In the routes file you just have to add the `LaravelLocalizationRoutes` filter and the `LaravelLocalization::transRoute` function to every route you want to translate using the translation key.

_Tip:_ If you want to use this filter with other filters (like `LaravelLocalizationRedirectFilter`) you just have to join them in the Laravel way, using | (eg: `'before' => 'LaravelLocalizationRoutes|LaravelLocalizationRedirectFilter'` )

Then you have to create the translation files and add there every key you want to translate. I suggest you to create a routes.php file inside your app/lang/language_abbreviation folder. For the previous example, I have created two translations files, these two files would look like:
```php
	// app/lang/en/routes.php
    return array(
      "about" 		=> 	"about",
      "view" 		=> 	"view/{id}", //we add a route parameter
      // other translated routes
	);
```
```php
	// app/lang/es/routes.php
    return array(
      "about" 		=> 	"acerca",
      "view" 		=> 	"ver/{id}", //we add a route parameter
      // other translated routes
	);
```

Once files are saved, you can access to http://url/en/about , http://url/es/acerca , http://url/en/view/5 and http://url/es/ver/5 without any problem. The getLanguageBar function would work as desired and it will translate the routes to all translated languages (don't forget to add any new route to the translation file).

## Events

You can capture the URL parameters during translation if you wish to translate them too. To do so, just create an event listener for the `routes.translation` event like so :

````
Event::listen('routes.translation', function($locale, $attributes)
{
	// Do your magic

    return $attributes;
});
````

Be sure to pass the locale and the attributes as parameters for your closure. You can also use Event Subscribers, see : [http://laravel.com/docs/events#event-subscribers](http://laravel.com/docs/events#event-subscribers)

## Config

By default only english and spanish are allowed but it can be changed using config.php file that is located at `app/config/packages/mcamara/laravel-localization/config.php` . If this file does not exist, use the following artisan command `php artisan config:publish mcamara/laravel-localization`  in order to create it.

This file have some interesting configuration settings (as the allowed locales or browser language detection, among others) feel free to play with it, all variables are self-explained.

## Changelog
View changelog here -> [changelog](CHANGELOG.md)

## License

Laravel Localization is an open-sourced laravel package licensed under the MIT license

