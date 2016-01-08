<?php namespace Mcamara\LaravelLocalization\Middleware;

use Illuminate\Http\RedirectResponse;
use Closure;

class LocaleCookieRedirect {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        $params = explode('/', $request->path());
        $locale = $request->cookie('locale', false);

        if ( count($params) > 0 && $locale = app('laravellocalization')->checkLocaleInSupportedLocales($params[ 0 ]) )
        {
            cookie('locale', $params[ 0 ]);

            return $next($request);
        }

        if ( $locale && !( app('laravellocalization')->getDefaultLocale() === $locale && app('laravellocalization')->hideDefaultLocaleInURL() ) )
        {
            app('session')->reflash();
            $redirection = app('laravellocalization')->getLocalizedURL($locale);

            return new RedirectResponse($redirection, 302, [ 'Vary' => 'Accept-Language' ]);
        }

        return $next($request);
    }
}