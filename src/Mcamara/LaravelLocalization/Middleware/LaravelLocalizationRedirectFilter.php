<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LaravelLocalizationRedirectFilter extends LaravelLocalizationMiddlewareBase 
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
        // If the URL of the request is in exceptions.
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $currentLocale = app('laravellocalization')->getCurrentLocale();
        $defaultLocale = app('laravellocalization')->getDefaultLocale();
        $params = explode('/', $request->getPathInfo());
        // Dump the first element (empty string) as getPathInfo() always returns a leading slash
        array_shift($params);

        if (count($params) > 0) {
            $localeCode = $params[0];
            $locales = app('laravellocalization')->getSupportedLocales();
            $hideDefaultLocale = app('laravellocalization')->hideDefaultLocaleInURL();
            $redirection = false;

            if (!empty($locales[$localeCode])) {
                if ($localeCode === $defaultLocale && $hideDefaultLocale) {
                    $redirection = app('laravellocalization')->getNonLocalizedURL();
                }
            } elseif ($currentLocale !== $defaultLocale || !$hideDefaultLocale) {
                // If the current url does not contain any locale
                // The system redirect the user to the very same url "localized"
                // we use the current locale to redirect him
                $redirection = app('laravellocalization')->getLocalizedURL(session('locale'), $request->fullUrl());
            }

            if ($redirection) {
                // Save any flashed data for redirect
                app('session')->reflash();

                return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
            }
        }

        return $next($request);
    }
}
