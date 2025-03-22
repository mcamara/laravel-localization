<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined;
use Mcamara\LaravelLocalization\Exceptions\UnsupportedLocaleException;
use Mcamara\LaravelLocalization\Services\LocalizedUrlGenerator;

class LaravelLocalization
{
    protected string $defaultLocale;
    protected array $supportedLocales;
    protected array $localesMapping;
    protected string $currentLocale;

    /**
     * @throws UnsupportedLocaleException
     */
    public function __construct(
        protected readonly Application $app,
        protected readonly ConfigRepository $configRepository,
        protected readonly LocalizedUrlGenerator $localizationUrlGenerator,
    ) {
        $locale = $this->configRepository->get('app.locale');
        $supportedLocales = $this->getSupportedLocales();

        if (empty($supportedLocales[$locale])) {
            throw new UnsupportedLocaleException('Laravel default locale is not in the supportedLocales array.');
        }

        $this->defaultLocale = $locale;
        $this->currentLocale = $locale;
    }

    public function isHiddenDefault(string $locale): bool
    {
        return  ($this->getDefaultLocale() === $locale && $this->hideDefaultLocaleInURL());
    }

    /**
     * Set and return supported locales.
     */
    public function setSupportedLocales(array $locales): void
    {
        $this->supportedLocales = $locales;
    }

    public function route(string $key, array $parameters = [], string|null $locale = null): string
    {
        $computedLocale = $locale ?? $this->getCurrentLocale();

        if($this->isHiddenDefault($computedLocale)){
            return route('without_locale.' . $key, $parameters);
        }

        return route($key, $parameters);
    }

    public function transRoute(string $key, array $parameters = [], string|null $locale = null): string
    {
        $computedLocale = $locale ?? $this->getCurrentLocale();

        $routeName = "trans_route_for_locale_{$computedLocale}_{$key}";

        if (!Route::has($routeName)) {
            return $key;
        }

        if(!isset($parameters['locale'])) {
            $parameters['locale'] = $computedLocale;
        }

        return route($routeName, $parameters);
    }

    /**
     * Returns an URL adapted to $locale.
     *
     *
     * @param string|null  $locale     Locale to adapt
     * @param string|null $url        URL to adapt in the current language. If not passed, the current url would be taken.
     * @param array        $attributes Attributes to add to the route, if empty, the system would try to extract them from the url.
     * @param bool         $forceDefaultLocation Force to show default location even hideDefaultLocaleInURL set as TRUE
     *
     * @throws SupportedLocalesNotDefined
     * @throws UnsupportedLocaleException
     *
     * @return string URL translated, returns same url if no route is found
     */
    public function getLocalizedURL(string|null $locale = null, string|null $url = null, array $attributes = [], bool $forceDefaultLocation = false): string
    {
        $locale = $locale ?: $this->getCurrentLocale();
        $locale = $this->getLocaleFromMapping($locale);

        if (!$this->checkLocaleInSupportedLocales($locale)) {
            throw new UnsupportedLocaleException("Locale '{$locale}' is not supported.");
        }

        if($url === null){
            // fullUrl() is including protocol, domain and query , e.g. `https://example.com/posts?page=2&sort=asc`

            // Use the request() helper instead of $this->request,
            // because the injected request may be stale if this class
            // was constructed before the current request was bound.
            $url = request()->fullUrl();
        }

        return $this->localizationUrlGenerator->getLocalizedURL(
            locale: $locale,
            url: $url,
            attributes: $attributes,
            supportedLocales: $this->getSupportedLocales(),
            forceDefaultLocation: $forceDefaultLocation,
            defaultLocale: $this->defaultLocale,
            hiddenDefault: $this->hideDefaultLocaleInURL()
        );
    }

    /**
     * Returns default locale.
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Return locales mapping.
     */
    public function getLocalesMapping(): array
    {
        if (empty($this->localesMapping)) {
            $this->localesMapping = $this->configRepository->get('laravellocalization.localesMapping');
        }

        return $this->localesMapping;
    }

    /**
     * Returns a locale from the mapping.
     */
    public function getLocaleFromMapping(string | null $locale): string | null
    {
        return $this->getLocalesMapping()[$locale] ?? $locale;
    }

    /**
     * Returns inversed locale from the mapping.
     */
    public function getInversedLocaleFromMapping(string | null $locale): string | null
    {
        return \array_flip($this->getLocalesMapping())[$locale] ?? $locale;
    }

    /**
     * Return an array of all supported Locales.
     *
     * @throws SupportedLocalesNotDefined
     */
    public function getSupportedLocales(): array
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

    /**
     * Return an array of all supported Locales but in the order the user
     * has specified in the config file. Useful for the language selector.
     */
    public function getLocalesOrder(): array
    {
        $locales = $this->getSupportedLocales();

        $order = $this->configRepository->get('laravellocalization.localesOrder');

        uksort($locales, function ($a, $b) use ($order) {
            $pos_a = array_search($a, $order);
            $pos_b = array_search($b, $order);
            return $pos_a - $pos_b;
        });

        return $locales;
    }

    /**
     * Returns current locale name.
     */
    public function getCurrentLocaleName(): string
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['name'];
    }

    /**
     * Returns current locale native name.
     */
    public function getCurrentLocaleNative(): string
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['native'];
    }

    /**
     * Returns current locale direction.
     */
    public function getCurrentLocaleDirection(): string
    {
        if (!empty($this->supportedLocales[$this->getCurrentLocale()]['dir'])) {
            return $this->supportedLocales[$this->getCurrentLocale()]['dir'];
        }

        switch ($this->getCurrentLocaleScript()) {
            // Other (historic) RTL scripts exist, but this list contains the only ones in current use.
            case 'Arab':
            case 'Hebr':
            case 'Mong':
            case 'Tfng':
            case 'Thaa':
            return 'rtl';
            default:
            return 'ltr';
        }
    }

    /**
     * Returns current locale script.
     */
    public function getCurrentLocaleScript(): string
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['script'];
    }

    /**
     * Returns current language's native reading.
     */
    public function getCurrentLocaleNativeReading(): string
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['native'];
    }

    public function setCurrentLocale(string $locale): void {
        $this->currentLocale = $locale;
    }

    /**
     * Returns current language of url request
     */
    public function getCurrentLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Returns current regional.
     */
    public function getCurrentLocaleRegional(): string | null
    {
        // need to check if it exists, since 'regional' has been added
        // after version 1.0.11 and existing users will not have it
        if (!isset($this->supportedLocales[$this->getCurrentLocale()]['regional'])) {
            return null;
        }

        return $this->supportedLocales[$this->getCurrentLocale()]['regional'];
    }

    /**
     * Returns supported languages language key.
     */
    public function getSupportedLanguagesKeys(): array
    {
        return array_keys($this->supportedLocales);
    }

    /**
     * Check if Locale exists on the supported locales array.
     *
     * @param string|bool $locale string|bool Locale to be checked
     *
     * @throws SupportedLocalesNotDefined
     *
     * @return bool is the locale supported?
     */
    public function checkLocaleInSupportedLocales($locale): bool
    {
        $inversedLocale = $this->getInversedLocaleFromMapping($locale);
        $locales = $this->getSupportedLocales();
        if ($locale !== false && empty($locales[$locale]) && empty($locales[$inversedLocale])) {
            return false;
        }

        return true;
    }

    /**
     * Returns the config repository for this instance.
     */
    public function getConfigRepository(): ConfigRepository
    {
        return $this->configRepository;
    }

    /**
     * Returns the value of useAcceptLanguageHeader in the config.
     */
    public function useAcceptLanguageHeader(): bool
    {
        return $this->configRepository->get('laravellocalization.useAcceptLanguageHeader');
    }

    public function hideUrlAndAcceptHeader(): bool
    {
      return $this->hideDefaultLocaleInURL() && $this->useAcceptLanguageHeader();
    }

    /**
     * Returns value of hideDefaultLocaleInURL in the config.
     */
    public function hideDefaultLocaleInURL(): bool
    {
        return $this->configRepository->get('laravellocalization.hideDefaultLocaleInURL');
    }
}
