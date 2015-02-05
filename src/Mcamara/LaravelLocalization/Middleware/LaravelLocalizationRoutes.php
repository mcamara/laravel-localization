<?php namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Http\Request;

class LaravelLocalizationRoutes implements Middleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        $app = app();

        $routeName = $app[ 'laravellocalization' ]->getRouteNameFromAPath($request->getUri());

        $app[ 'laravellocalization' ]->setRouteName($routeName);

        return $next($request);
    }
}