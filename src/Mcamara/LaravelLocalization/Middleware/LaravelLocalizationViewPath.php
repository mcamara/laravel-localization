<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Support\Facades\View;

class LaravelLocalizationViewPath extends LaravelLocalizationMiddlewareBase 
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next) {

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
