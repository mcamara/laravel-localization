<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined;
use Mcamara\LaravelLocalization\Exceptions\UnsupportedLocaleException;
use Mcamara\LaravelLocalization\LanguageNegotiator;
use Mcamara\LaravelLocalization\LaravelLocalization;

class SetLocale extends LaravelLocalizationMiddlewareBase
{
    public function __construct(
        protected readonly ConfigRepository $configRepository,
        protected readonly Application $app,
        protected readonly Translator $translator,
        protected readonly LaravelLocalization $laravelLocalization,
    ){
    }

    public function handle(Request $request, Closure $next): mixed
    {
        // @toDo I am not 100% sure if we should skip this url for ignored urls.
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $locale = $request->route('locale');

        if($locale == null || empty($this->laravelLocalization->getSupportedLocales()[$locale])) {
            $locale = $this->fallbackLocale($request);
        }

        $this->app->setLocale($locale);
        $this->translator->setLocale($locale);
        $this->laravelLocalization->setCurrentLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Regional locale such as de_DE, so formatLocalized works in Carbon
        $regional = $this->laravelLocalization->getCurrentLocaleRegional();
        $suffix = $this->configRepository->get('laravellocalization.utf8suffix');
        if ($regional) {
            setlocale(LC_TIME, $regional . $suffix);
            setlocale(LC_MONETARY, $regional . $suffix);
        }

        return $next($request);
    }

    // if the first segment/locale passed is not valid the system would either take default locale,
    // (if hideDefaultLocaleInURL is set, or retrieve it from the browser
    protected function fallbackLocale(Request $request): string
    {
        $defaultLocale = $this->configRepository->get('app.locale');

        // if we reached this point and hideDefaultLocaleInURL is true, take default
        if ($this->laravelLocalization->hideDefaultLocaleInURL()) {
            return $defaultLocale;
        }

        // but if hideDefaultLocaleInURL is false, we may have to retrieve it from the browser...
        if ($this->laravelLocalization->useAcceptLanguageHeader()) {
            $negotiator = new LanguageNegotiator($defaultLocale, $this->laravelLocalization->getSupportedLocales(), $request);

            return $negotiator->negotiateLanguage();
        }

        return $defaultLocale;
    }
}
