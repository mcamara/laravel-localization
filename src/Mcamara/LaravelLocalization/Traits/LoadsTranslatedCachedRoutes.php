<?php

namespace Mcamara\LaravelLocalization\Traits;

use Illuminate\Support\Facades\Log;

/**
 * LoadsTranslatedCachedRoutes
 *
 * Add this trait to your App\RouteServiceProvider to load
 * translated cached routes for the active locale, instead
 * of the default locale's routes (irrespective of active).
 */
trait LoadsTranslatedCachedRoutes
{
    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        $localization = $this->getLaravelLocalization();

        // compute $locale from url.
        // It is null if url does not contain locale.
        $locale = $localization->setLocale();

        $localeKeys = $localization->getSupportedLanguagesKeys();

        // First, try to load the routes specifically cached for this locale
        // if they do not exist, write a warning to the log and load the default
        // routes instead. Note that this is guaranteed to exist, because the
        // 'cached routes' check in the Application checks its existence.

        $path = $this->makeLocaleRoutesPath($locale, $localeKeys);

        if ( ! file_exists($path)) {

            Log::warning("Routes cached, but no cached routes found for locale '{$locale}'!");

            $path = $this->getDefaultCachedRoutePath();
        }

        $this->app->booted(function () use ($path) {
            require $path;
        });
    }

    /**
     * Returns the path to the cached routes file for a given locale.
     *
     * @param string   $locale
     * @param string[] $localeKeys
     * @return string
     */
    protected function makeLocaleRoutesPath($locale, $localeKeys)
    {
        $path = $this->getDefaultCachedRoutePath();

        if ( ! $locale || ! in_array($locale, $localeKeys)) {
            return $path;
        }

        return substr($path, 0, -4) . '_' . $locale . '.php';
    }

    /**
     * Returns the path to the standard cached routes file.
     *
     * @return string
     */
    protected function getDefaultCachedRoutePath()
    {
        return $this->app->getCachedRoutesPath();
    }

    /**
     * @return string|null
     */
    protected function getLocaleFromRequest()
    {
        return request()->segment(1);
    }

    /**
     * @return \Mcamara\LaravelLocalization\LaravelLocalization
     */
    protected function getLaravelLocalization()
    {
        return app('laravellocalization');
    }
}
