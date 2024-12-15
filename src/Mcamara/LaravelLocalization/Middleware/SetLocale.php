<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined;
use Mcamara\LaravelLocalization\Exceptions\UnsupportedLocaleException;
use Mcamara\LaravelLocalization\LanguageNegotiator;
use Mcamara\LaravelLocalization\LaravelLocalization;

class SetLocale extends LaravelLocalizationMiddlewareBase
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
        private readonly Application $app,
        private readonly Translator $translator,
        private readonly LaravelLocalization $laravelLocalization,
    ){
    }

    public function handle(Request $request, Closure $next)
    {
        // @toDo I am not 100% sure if we should skip this url for ignored urls.
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $locale = $request->route('locale');

        if($locale == null || empty($this->getSupportedLocales()[$locale])) {
            $locale = $this->fallbackLocale($request);
        }

        $this->app->setLocale($locale);
        $this->translator->setLocale($locale);
        $this->laravelLocalization->setCurrentLocale($locale);

        // Regional locale such as de_DE, so formatLocalized works in Carbon
        $regional = $this->getLocaleRegional($locale);
        $suffix = $this->configRepository->get('laravellocalization.utf8suffix');
        if ($regional) {
            setlocale(LC_TIME, $regional . $suffix);
            setlocale(LC_MONETARY, $regional . $suffix);
        }

        return $next($request);
    }

    protected function getSupportedLocales()
    {
        $locales = $this->configRepository->get('laravellocalization.supportedLocales');

        if (empty($locales) || !\is_array($locales)) {
            throw new SupportedLocalesNotDefined();
        }

        return $locales;
    }

    // if the first segment/locale passed is not valid the system would either take default locale,
    // (if hideDefaultLocaleInURL is set, or retrieve it from the browser
    protected function fallbackLocale(Request $request): string
    {
        $defaultLocale = $this->configRepository->get('app.locale');

        // if we reached this point and hideDefaultLocaleInURL is true, take default
        if ($this->hideDefaultLocaleInURL()) {
            return $defaultLocale;
        }

        // but if hideDefaultLocaleInURL is false, we may have to retrieve it from the browser...
        if ($this->useAcceptLanguageHeader()) {
            $negotiator = new LanguageNegotiator($defaultLocale, $this->getSupportedLocales(), $request);

            return $negotiator->negotiateLanguage();
        }

        return $defaultLocale;
    }


    protected function hideDefaultLocaleInURL()
    {
        return $this->configRepository->get('laravellocalization.hideDefaultLocaleInURL');
    }


    protected function useAcceptLanguageHeader()
    {
        return $this->configRepository->get('laravellocalization.useAcceptLanguageHeader');
    }

    protected function getLocaleRegional(string $locale): string|null
    {
        // need to check if it exists, since 'regional' has been added
        // after version 1.0.11 and existing users will not have it
        if (!isset($this->getSupportedLocales()[$locale]['regional'])) {
            return null;
        }

        return $this->getSupportedLocales()[$locale]['regional'];
    }
}
