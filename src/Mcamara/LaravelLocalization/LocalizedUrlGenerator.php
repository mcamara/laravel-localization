<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined;
use Mcamara\LaravelLocalization\Exceptions\UnsupportedLocaleException;

class LocalizedUrlGenerator
{
    public function __construct(
        protected readonly Router $router,
        protected readonly UrlGenerator $urlGenerator
    ){
    }

    /**
     * Returns an URL adapted to $locale.
     *
     *
     * @param string|null  $locale     Locale to adapt
     * @param string|null $url        URL to adapt in the current language. If not passed, the current url would be taken.
     * @param array        $attributes Attributes to add to the route, if empty, the system would try to extract them from the url.
     * @param bool         $forceDefaultLocation Force to show default location even hideDefaultLocaleInURL set as TRUE
     *
     * @throws SupportedLocalesNotDefined
     * @throws UnsupportedLocaleException
     *
     * @return string|false URL translated, False if url does not exist
     */
    public function getLocalizedURL(string|null $locale = null, string $url,  string $defaultLocale, bool $hiddenDefault, array $attributes = [], bool $forceDefaultLocation = false): string|false
    {
        $route = $this->matchRouteForAnyRoute($url);

        if ($route === null) {
            return false;
        }

        if(empty($attributes)){
            $attributes = $route->parameters();
        }

        $uri  = $route->uri();
        $urlQuery = parse_url($url, PHP_URL_QUERY);
        // e.g. `?page=2&sort=asc`
        $urlQuery = $urlQuery ? '?'.$urlQuery : '';

        // If the route is a translated route, get the corresponding localized route by name.
        // Translated routes can have identical paths across languages, so we can't rely on the {locale} parameter.
        // Even using `whereIn('locale', ['de'])` wouldn't work because routes with identical URLs overwrite each other,
        // regardless of differing `whereIn` conditions.
        if ($route->getName()) {
            $routeName = $route->getName();

            if (preg_match('/^translated_route_(.*?)_(.*)$/', $routeName, $matches)) {
                $newRouteName = "translated_route_{$locale}_{$matches[2]}";
                return route($newRouteName, $attributes) . $urlQuery;
            }
        }

        $hideLocaleInUrl = ($locale === $defaultLocale && !$forceDefaultLocation && $hiddenDefault);

        if (!isset($attributes['locale'])){
            if($hideLocaleInUrl){
                // locale already hidden in url
                return $url;
            }

            return $this->urlGenerator->to($locale . '/' . $uri, $attributes) . $urlQuery;
        }

        $localeOfUrl = $attributes['locale'];
        if($locale === $localeOfUrl){
            // no need to change locale of url
            return $url;
        }

        if ($hideLocaleInUrl) {
            unset($attributes['locale']);
            $cleanedUri = preg_replace('%^/?{locale}(/|$)%', '', $uri);
            return $this->urlGenerator->to($cleanedUri, $attributes) . $urlQuery;
        }

        $attributes['locale'] = $locale;
        return $this->urlGenerator->to($uri, $attributes) . $urlQuery;
    }

    protected function matchRouteForAnyRoute(string $url): Route|null
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            try {
                $request = Request::create($url, $method);
                $route = $this->router->getRoutes()->match($request);

                return $route;
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
