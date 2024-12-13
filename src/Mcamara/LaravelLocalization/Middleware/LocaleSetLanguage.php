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

class LocaleSetLanguage extends LaravelLocalizationMiddlewareBase
{
    protected array $localesMapping;
    protected array $supportedLocales;

    public function __construct(
        private readonly ConfigRepository $configRepository,
        private readonly Application $app,
        private readonly Translator $translator,
    ){
    }

    public function handle(Request $request, Closure $next)
    {
        // I am not 100% sure if we should skip setLang for
        // ignored urls.
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        // No longer required to take segment(1), as locale is now part of the url.
        $locale = $request->route('locale');

        // If locale is not supported, take a good guess.
        if($locale == null || empty($this->getSupportedLocales()[$locale])) {
            $locale = $this->guessLocale($request);
        }

        $this->app->setLocale($locale);
        $this->translator->setLocale($locale);

        // Regional locale such as de_DE, so formatLocalized works in Carbon
        $regional = $this->getCurrentLocaleRegional();
        $suffix = $this->configRepository->get('laravellocalization.utf8suffix');
        if ($regional) {
            setlocale(LC_TIME, $regional . $suffix);
            setlocale(LC_MONETARY, $regional . $suffix);
        }

        return $next($request);
    }

    protected function getSupportedLocales()
    {
        if (!empty($this->supportedLocales)) {
            return $this->supportedLocales;
        }

        $locales = $this->configRepository->get('laravellocalization.supportedLocales');

        if (empty($locales) || !\is_array($locales)) {
            throw new SupportedLocalesNotDefined();
        }

        $this->supportedLocales = $locales;

        return $locales;
    }

    protected function guessLocale(Request $request): string
    {
        // if the first segment/locale passed is not valid
        // the system would ask which locale have to take
        // it could be taken by the browser
        // depending on your configuration
        $locale = null;

        $defaultLocale = $this->configRepository->get('app.locale');

        // if we reached this point and hideDefaultLocaleInURL is true
        // we have to assume we are routing to a defaultLocale route.
        if ($this->hideDefaultLocaleInURL()) {
            return $defaultLocale;
        }

        // but if hideDefaultLocaleInURL is false, we may have
        // to retrieve it from the browser...
        if ($this->useAcceptLanguageHeader()) {
            $negotiator = new LanguageNegotiator($defaultLocale, $this->getSupportedLocales(), $request);

            return $negotiator->negotiateLanguage();
        }

        // or get application default language
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

    protected function getCurrentLocaleRegional(): string|null
    {
        // need to check if it exists, since 'regional' has been added
        // after version 1.0.11 and existing users will not have it
        if (!isset($this->supportedLocales[$this->getCurrentLocale()]['regional'])) {
            return null;
        }

        return $this->supportedLocales[$this->getCurrentLocale()]['regional'];
    }
}
