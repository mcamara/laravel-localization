<?php namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\Middleware;

class LaravelLocalizationRedirectFilter implements Middleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        $currentLocale = app('laravellocalization')->getCurrentLocale();
        $defaultLocale = app('laravellocalization')->getDefaultLocale();
        $params = explode('/', $request->path());

        if ( count($params) > 0 )
        {
            $localeCode = $params[ 0 ];
            $locales = app('laravellocalization')->getSupportedLocales();
            $hideDefaultLocale = app('laravellocalization')->hideDefaultLocaleInURL();
            $redirection = false;

            if ( !empty( $locales[ $localeCode ] ) )
            {
                if ( $localeCode === $defaultLocale && $hideDefaultLocale )
                {
                    $redirection = app('laravellocalization')->getNonLocalizedURL();
                }
            } else if ( $currentLocale !== $defaultLocale || !$hideDefaultLocale )
            {
                // If the current url does not contain any locale
                // The system redirect the user to the very same url "localized"
                // we use the current locale to redirect him
                $redirection = app('laravellocalization')->getLocalizedURL();
            }

            if ( $redirection )
            {
                // Save any flashed data for redirect
                app('session')->reflash();

                return new RedirectResponse($redirection, 307, [ 'Vary', 'Accept-Language' ]);
            }
        }

        return $next($request);
    }
}