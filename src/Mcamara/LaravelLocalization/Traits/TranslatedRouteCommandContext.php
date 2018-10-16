<?php

namespace Mcamara\LaravelLocalization\Traits;

trait TranslatedRouteCommandContext
{
    /**
     * Returns whether a given locale is supported.
     *
     * @param string $locale
     * @return bool
     */
    protected function isSupportedLocale($locale)
    {
        return in_array($locale, $this->getSupportedLocales());
    }

    /**
     * @return string[]
     */
    protected function getSupportedLocales()
    {
        return $this->getLaravelLocalization()->getSupportedLanguagesKeys();
    }

    /**
     * @return \Mcamara\LaravelLocalization\LaravelLocalization
     */
    protected function getLaravelLocalization()
    {
        return $this->laravel->make('laravellocalization');
    }

    /**
     * @return string
     */
    protected function getBootstrapPath()
    {
        if (method_exists($this->laravel, 'bootstrapPath')) {
            return $this->laravel->bootstrapPath();
        }

        return $this->laravel->basePath() . DIRECTORY_SEPARATOR . 'bootstrap';
    }

    /**
     * @param string $locale
     * @return string
     */
    protected function makeLocaleRoutesPath($locale = '')
    {
        $path = $this->laravel->getCachedRoutesPath();

        if ( ! $locale ) {
            return $path;
        }

        return substr($path, 0, -4) . '_' . $locale . '.php';
    }
}
