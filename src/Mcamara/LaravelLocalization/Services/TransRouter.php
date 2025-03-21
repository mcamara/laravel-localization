<?php

namespace Mcamara\LaravelLocalization\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Mcamara\LaravelLocalization\Middleware\SetLocale;

class TransRouter
{
    private array $allowedLocales;
    private bool $hideDefaultLocaleInURL;
    private bool $useAcceptLanguageHeader;

    public function __construct()
    {
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
        $localesMapping = array_keys(config('laravellocalization.localesMapping', []));
        $this->allowedLocales = array_unique(array_merge($supportedLocales, $localesMapping));
        $this->hideDefaultLocaleInURL = config('laravellocalization.hideDefaultLocaleInURL', false);
        $this->useAcceptLanguageHeader = config('laravellocalization.useAcceptLanguageHeader', false);
    }

    public function registerTransRoute(string $routeKey, array|callable $controller, string $methodType): void
    {
        foreach ($this->allowedLocales as $locale) {
            $key = "routes.$routeKey";

            $route = trans($key, [], $locale);
            if ($route === $key) {
                continue;
            }

            $route = ltrim($route, '/');
            $name = "trans_route_with_locale_{$locale}_{$routeKey}";

            $middleware = [SetLocale::class, LocaleSessionRedirect::class];

            if ($this->hideDefaultLocaleInURL && $locale === App::getLocale()) {
                Route::$methodType($route, $controller)
                    ->middleware($middleware)
                    ->name($name);
            } else {
                Route::$methodType($locale . '/' . $route, $controller)
                    ->middleware($middleware)
                    ->name($name);

                if ($this->useAcceptLanguageHeader) {
                    Route::$methodType($route, $controller)
                        ->middleware($middleware)
                        ->name("trans_route_no_locale_{$locale}_{$routeKey}");
                }
            }
        }


    }
}
