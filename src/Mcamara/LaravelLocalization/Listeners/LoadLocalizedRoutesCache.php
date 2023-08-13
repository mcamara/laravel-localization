<?php

namespace Mcamara\LaravelLocalization\Listeners;

use Illuminate\Foundation\Application;
use Laravel\Octane\Events\RequestReceived;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class LoadLocalizedRoutesCache
{
    private static $lastLocale;


    public function handle(RequestReceived $event): void
    {
        // passing request segment is crucial because the package doesn't
        // know the current locale as it was instantiated in service provider

        // (there is also an option to don't pass the request segment in case
        // you don't use translatable routes (transRoute() in web.php) in your project
        // in this case the package will correctly resolve the locale and you
        // don't need to pass the 3rd param when binding in service provider)
        $locale = LaravelLocalization::setLocale($event->request->segment(1));

        $path = $this->makeLocaleRoutesPath($event->sandbox, $locale);

        if (self::$lastLocale != $locale && is_file($path)) {
            self::$lastLocale = $locale;
            include $path;
        }
    }

    protected function makeLocaleRoutesPath(Application $app, $locale = ''): string
    {
        $path = $app->getCachedRoutesPath();

        if (! $locale) {
            return $path;
        }

        return substr($path, 0, -4) . '_' . $locale . '.php';
    }
}
