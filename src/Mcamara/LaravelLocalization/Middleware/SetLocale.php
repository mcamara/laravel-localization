<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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

        // The locale here cannot be an "inverse" mapping, as such cases are handled
        // earlier by the locale mapping middleware.
        if($locale == null || empty($this->laravelLocalization->getSupportedLocales()[$locale])) {
            $locale = $this->computeLocale($request);
        }

        $this->app->setLocale($locale);
        $this->translator->setLocale($locale);
        $this->laravelLocalization->setCurrentLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Configure regional locale settings (e.g., de_DE) for proper formatting in Carbon.
        $regional = $this->laravelLocalization->getCurrentLocaleRegional();
        $suffix = $this->configRepository->get('laravellocalization.utf8suffix');
        if ($regional) {
            setlocale(LC_TIME, $regional . $suffix);
            setlocale(LC_MONETARY, $regional . $suffix);
        }

        return $next($request);
    }

    protected function computeLocale(Request $request): string
    {
        $defaultLocale = $this->configRepository->get('app.locale');

        // If we reached this point and `hideDefaultLocaleInURL` is enabled, enforce the default locale.
        // In this case, `useAcceptLanguageHeader` is only considered by the `LaravelSessionRedirect` middleware
        // when no locale has been set in the session.
        if ($this->laravelLocalization->hideDefaultLocaleInURL()) {
            return $defaultLocale;
        }

        // If browser language negotiation is enabled, attempt to detect the best match.
        if ($this->laravelLocalization->useAcceptLanguageHeader()) {
            $negotiator = new LanguageNegotiator($defaultLocale, $this->laravelLocalization->getSupportedLocales(), $request);

            return $negotiator->negotiateLanguage();
        }

        return $defaultLocale;
    }
}
