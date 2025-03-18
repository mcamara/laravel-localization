<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mcamara\LaravelLocalization\LaravelLocalization;

class LocaleMappingMiddleware extends LaravelLocalizationMiddlewareBase
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
        private readonly LaravelLocalization $laravelLocalization,
    ){
    }

    public function handle($request, Closure $next)
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $locale = $request->route('locale');
        $localesMapping = $this->configRepository->get('laravellocalization.localesMapping');
        if (array_key_exists($locale, $localesMapping)) {
            // @toDo needs to be tested, not sure if this works
            $mappedLocale = $localesMapping[$locale];
            $url = $request->fullUrl();

            if($this->laravelLocalization->isHiddenDefault($mappedLocale)){
                $newUrl = preg_replace("#/$locale#", "/", $url, 1);
            }else{
                $newUrl = preg_replace("#/$locale#", "/$mappedLocale", $url, 1);
            }

            return redirect($newUrl, 301);
        }

        return $next($request);
    }
}
