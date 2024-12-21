<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Http\Request;

class LaravelLocalizationRoutes extends LaravelLocalizationMiddlewareBase
{
    public function handle(Request $request, Closure $next): mixed
    {
        // If the URL of the request is in exceptions.
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $app = app();

        $routeName = $app['laravellocalization']->getRouteNameFromAPath($request->getUri());

        $app['laravellocalization']->setRouteName($routeName);

        return $next($request);
    }
}
