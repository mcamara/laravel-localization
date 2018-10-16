<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;

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

        $params = explode('/', $request->getPathInfo());

        // Dump the first element (empty string) as getPathInfo() always returns a leading slash
        array_shift($params);

        if (\count($params) > 0) {
            $locale = $params[0];

            if (app('laravellocalization')->checkLocaleInSupportedLocales($locale)) {
                if (app('laravellocalization')->isHiddenDefault($locale)) {
                    $redirection = app('laravellocalization')->getNonLocalizedURL();

                    // Save any flashed data for redirect
                    app('session')->reflash();

                    return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
                }
            }
        }

        return $next($request);
    }
}
