<?php

use Illuminate\Support\Facades\Route;

if (!function_exists('localized_route')) {
    function localized_route(string $name, array $parameters = [], string|null $locale = null, bool $noLocale = true): string
    {
        $computedLocale ??= app()->getLocale();

        $withLocale = "trans_route_with_locale_{$computedLocale}_{$name}";
        $noLocale = "trans_route_no_locale_{$computedLocale}_{$name}";

        // In tests, routes defined in setUp() are correctly registered but sometimes not recognized by
        // Route::has(...), likely due to Laravel not populating the internal route name index (routesByName).
        // This workaround manually checks route names to ensure they exist.

        if ($locale === null && $noLocale && collect(Route::getRoutes())->pluck('action.as')->filter()->contains($noLocale)) {
            return route($noLocale, $parameters);
        }

        if (collect(Route::getRoutes())->pluck('action.as')->filter()->contains($withLocale)) {
            return route($withLocale, $parameters);
        }

        if ($noLocale && collect(Route::getRoutes())->pluck('action.as')->filter()->contains($noLocale)) {
            return route($noLocale, $parameters);
        }



        return route($name, $parameters);
    }
}
