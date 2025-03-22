<?php

namespace Mcamara\LaravelLocalization\Services;

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
    public function getLocalizedURL(string|null $locale = null, string $url,  string $defaultLocale, bool $hiddenDefault, array $supportedLocales, array $attributes = [], bool $forceDefaultLocation = false): string
    {
        $route = $this->matchRouteForAnyRoute($url);

        if ($route === null){
            // If hideDefaultLocale is disabled and negotiator is disabled, then only routes with locale can be matched
            $route = $this->attemptRouteMatchingWithDefaultLocale($url, $defaultLocale, $supportedLocales);
        }

        if ($route === null){
            // no route found, gracefully return $url as fallback
            return $url;
        }

        if(empty($attributes)){
            $attributes = $route->parameters();
        }

        $uri  = $route->uri();
        $urlQuery = parse_url($url, PHP_URL_QUERY);
        // e.g. `?page=2&sort=asc`
        $urlQuery = $urlQuery ? '?'.$urlQuery : '';
        $hideLocaleInUrl = ($locale === $defaultLocale && !$forceDefaultLocation && $hiddenDefault);


        // Handle transRoutes
        if ($route->getName()) {
            $routeName = $route->getName();

            if (preg_match('/^trans_route_(for|no)_locale_(.*?)_(.*)$/', $routeName, $matches)) {
                $type = ($hideLocaleInUrl) ? 'no' : 'for';
                $newRouteName = "trans_route_{$type}_locale_{$locale}_{$matches[3]}";
                if(!isset($attributes['locale'])){
                    $attributes['locale'] = $locale;
                }

                return route($newRouteName, $attributes) . $urlQuery;
            }
        }

        // Since we deal now with normal routes, we only need to modify, add or remove the locale from uri

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


    protected function attemptRouteMatchingWithDefaultLocale(string $url, string $defaultLocale, array $supportedLocales): ?Route
    {
        $uri = parse_url($url, PHP_URL_PATH);

        // Extract the first segment of the URI
        $segments = explode('/', trim($uri, '/'));
        $firstSegment = $segments[0] ?? null;

        if(!empty($supportedLocales[$firstSegment])){
            array_unshift($segments, $defaultLocale);
            $newUri = '/' . implode('/', $segments);
            $url = preg_replace('/' . preg_quote($uri, '/') . '/', $newUri, $url, 1);
            return $this->matchRouteForAnyRoute($url);
        }

        return null;
    }



}
