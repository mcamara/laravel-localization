<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LaravelLocalizationDomainRedirectFilter extends LaravelLocalizationMiddlewareBase
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $localeByDomain = app('laravellocalization')->getLocaleByDomain();
        $currentLocale = app('laravellocalization')->getCurrentLocale();
        $host = parse_url(\request()->root(), PHP_URL_HOST);

        // If the URL of the request is in exceptions.
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $params = explode('/', $request->getPathInfo());

        // Dump the first element (empty string) as getPathInfo() always returns a leading slash
        array_shift($params);
        if ($currentLocale != $localeByDomain) {
            if (app('laravellocalization')->checkLocaleInSupportedLocales($currentLocale)) {
                app('laravellocalization')->setLocale($localeByDomain);
                app('session')->reflash();

                if (stripos($host, app('laravellocalization')->getDomainByLocale(App::getLocale())) === false) {
                    $redirection = app('laravellocalization')->getLocalizedURL($localeByDomain);
                    return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
                }
            }
        } else {
            app('laravellocalization')->setLocale($localeByDomain);
        }

        return $next($request);
    }
}
