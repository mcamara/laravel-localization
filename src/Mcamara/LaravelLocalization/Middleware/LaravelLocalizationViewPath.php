<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class LaravelLocalizationViewPath extends LaravelLocalizationMiddlewareBase 
{
    public function handle(Request $request, Closure $next): mixed
    {

        // If the URL of the request is in exceptions.
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $app = app();
        
        $currentLocale = app('laravellocalization')->getCurrentLocale();
        $viewPath = resource_path('views/' . $currentLocale);
        
        // Add current locale-code to view-paths
        View::addLocation($viewPath);

        return $next($request);
    }

}
