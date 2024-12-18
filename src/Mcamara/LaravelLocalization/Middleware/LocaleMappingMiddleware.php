<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

class LocaleMappingMiddleware extends LaravelLocalizationMiddlewareBase
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
    ){
    }

    public function handle($request, Closure $next)
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        // Get the 'locale' parameter from the route
        $locale = $request->route('locale');

        // Check if this locale has a mapping
        $localesMapping = $this->configRepository->get('laravellocalization.localesMapping');
        if (array_key_exists($locale, $localesMapping)) {
            // @toDO if locale maps to default locale, and hidedefault locale is on, simply redirect to locale = null

            // @toDo needs to be tested, not sure if this works
            $mappedLocale = $localesMapping[$locale];
            $url = $request->fullUrl(); // Get the full URL

            // Replace only the first occurrence of the locale in the URL
            $newUrl = preg_replace("#/$locale#", "/$mappedLocale", $url, 1);

            return redirect($newUrl, 301); // Permanent redirect
        }

        return $next($request);
    }
}
