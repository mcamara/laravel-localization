<?php

namespace Mcamara\LaravelLocalization\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isHiddenDefault(string $locale)
 * @method static void setSupportedLocales(array $locales)
 * @method static string localizeURL(string|null $url = null, string|bool|null $locale = null)
 * @method static string getLocalizedURL(string|null $locale = null, string|null $url = null, array $attributes = [], bool $forceDefaultLocation = false)
 * @method static string|false getURLFromRouteNameTranslated(string|bool $locale, string $transKeyName, array $attributes = [], bool $forceDefaultLocation = false)
 * @method static string getNonLocalizedURL(string|false|null $url = null)
 * @method static string getDefaultLocale()
 * @method static array getLocalesMapping()
 * @method static string|null getLocaleFromMapping(string|null $locale)
 * @method static string|null getInversedLocaleFromMapping(string|null $locale)
 * @method static array getSupportedLocales()
 * @method static array getLocalesOrder()
 * @method static string getCurrentLocaleName()
 * @method static string getCurrentLocaleNative()
 * @method static string getCurrentLocaleDirection()
 * @method static string getCurrentLocaleScript()
 * @method static string getCurrentLocaleNativeReading()
 * @method static void setCurrentLocale(string $locale)
 * @method static string getCurrentLocale()
 * @method static string|null getCurrentLocaleRegional()
 * @method static array getSupportedLanguagesKeys()
 * @method static bool checkLocaleInSupportedLocales(string|bool $locale)
 * @method static void setRouteName(string $routeName)
 * @method static string transRoute(string $routeName, array $parameters = [], string|null $locale = null)
 * @method static string route(string $key, array $parameters = [], string|null $locale = null)
 * @method static string|false getRouteNameFromAPath(string $path)
 * @method static \Illuminate\Contracts\Config\Repository getConfigRepository()
 * @method static bool useAcceptLanguageHeader()
 * @method static bool hideUrlAndAcceptHeader()
 * @method static bool hideDefaultLocaleInURL()
 * @method static string createUrlFromUri(string $uri)
 * @method static void setBaseUrl(string $url)
 * @method static string getSerializedTranslatedRoutes()
 * @method static void setSerializedTranslatedRoutes(string $serializedRoutes)
 *
 * @see \Mcamara\LaravelLocalization\LaravelLocalization
 */
class LaravelLocalization extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravellocalization';
    }
}
