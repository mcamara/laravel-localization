# Laravel Localization: Caching Routes

If you want to cache the routes in all languages, you will need to use special Artisan commands. **Using `artisan route:cache`** will not work correctly!

## Setup

For the route caching solution to work, it is required to make a minor adjustment to your application route provision.

In your App's `RouteServiceProvider`, use the `LoadsTranslatedCachedRoutes` trait:

```php
<?php
class RouteServiceProvider extends ServiceProvider
{
    use \Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;
```


## Usage

To cache your routes, use:

``` bash
php artisan route:trans:cache
```

... instead of the normal `route:cache` command.

To list the routes for a given locale, use 

``` bash
php artisan route:trans:list {locale}

# for instance:
php artisan route:trans:list en
```

To clear cached routes for all locales, use

``` bash
php artisan route:trans:clear
```

### Note

Using `route:clear` will also effectively unset the cache (at the minor cost of leaving some clutter in your bootstrap/cache directory).


## History

Caching routes, before version 1.3, was done using a separate package, 
 [https://github.com/czim/laravel-localization-route-cache](https://github.com/czim/laravel-localization-route-cache).
 That separate package is no longer required, and should be removed when upgrading to 1.3 or newer. 


