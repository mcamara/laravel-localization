<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined;
use Mcamara\LaravelLocalization\Exceptions\UnsupportedLocaleException;

class LaravelLocalization
{
    /**
     * Default locale.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * Supported Locales.
     *
     * @var array
     */
    protected $supportedLocales;

    /**
     * Locales mapping.
     *
     * @var array
     */
    protected $localesMapping;

    /**
     * Current locale.
     *
     * @var string
     */
    protected $currentLocale = false;


    /**
     * Name of the translation key of the current route, it is used for url translations.
     *
     * @var string
     */
    protected $routeName;

    /**
     * @throws UnsupportedLocaleException
     */
    public function __construct(
        protected readonly Application $app,
        protected readonly ConfigRepository $configRepository,
        protected readonly Translator $translator,
        protected readonly Router $router,
        protected readonly Request $request,
        protected readonly UrlGenerator $url
    ) {
        $this->defaultLocale = $this->configRepository->get('app.locale');
        $supportedLocales = $this->getSupportedLocales();

        if (empty($supportedLocales[$this->defaultLocale])) {
            throw new UnsupportedLocaleException('Laravel default locale is not in the supportedLocales array.');
        }
    }

    public function isHiddenDefault($locale): bool
    {
        return  ($this->getDefaultLocale() === $locale && $this->hideDefaultLocaleInURL());
    }

    /**
     * Set and return supported locales.
     *
     * @param array $locales Locales that the App supports
     */
    public function setSupportedLocales($locales)
    {
        $this->supportedLocales = $locales;
    }

    /**
     * Returns an URL adapted to $locale.
     *
     *
     * @param string|null  $locale     Locale to adapt, false to remove locale
     * @param string|null $url        URL to adapt in the current language. If not passed, the current url would be taken.
     * @param array        $attributes Attributes to add to the route, if empty, the system would try to extract them from the url.
     * @param bool         $forceDefaultLocation Force to show default location even hideDefaultLocaleInURL set as TRUE
     *
     * @throws SupportedLocalesNotDefined
     * @throws UnsupportedLocaleException
     *
     * @return string|false URL translated, False if url does not exist
     */
    public function getLocalizedURL(string|null $locale = null, string|null $url = null, array $attributes = [], bool $forceDefaultLocation = false): string|false
    {
        $locale = $locale ?: $this->getCurrentLocale();
        $locale = $this->getLocaleFromMapping($locale);

        if (!$this->checkLocaleInSupportedLocales($locale)) {
            throw new UnsupportedLocaleException("Locale '{$locale}' is not supported.");
        }

        if($url === null){
            // Including protocol, domain and query , e.g. `https://example.com/posts?page=2&sort=asc`
            $url = $this->request->fullUrl();
        }

        $route = $this->matchRouteForAnyRoute($url);

        if ($route === null) {
            return false;
        }

        if(empty($attributes)){
           $attributes = $route->parameters();
        }

        $uri  = $route->uri();
        $urlQuery = parse_url($url, PHP_URL_QUERY);
        // urlQuery , e.g. `?page=2&sort=asc`
        $urlQuery = $urlQuery ? '?'.$urlQuery : '';

        if (!isset($attributes['locale'])){
            if($locale === $this->getDefaultLocale()){
                return $url;
            }

            // Locale must be different from default, therefore it should not be hidden
            return $this->url->to($locale . '/' . $uri, $attributes) . $urlQuery;
        }

        $localeOfUrl = $attributes['locale'];

        if($locale === $localeOfUrl){
            return $url;
        }

        // if the locale is default and hidden by default, we need to workaround
        if ($this->isHiddenDefault($locale)){
            unset($attributes['locale']);
            $cleanedUri = preg_replace('%^/?{locale}(/|$)%', '', $uri);
            return $this->url->to($cleanedUri, $attributes) . $urlQuery;
        }

        // Update locale and move on
        $attributes['locale'] = $locale;
        return $this->url->to($uri, $attributes) . $urlQuery;
    }

    protected function matchRouteForAnyRoute(string $url): Route|null
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            try {
                $request = Request::create($url, $method);
                $route = $this->router->getRoutes()->match($request);

                return $route;
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }


    /**
     * Returns default locale.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Return locales mapping.
     *
     * @return array
     */
    public function getLocalesMapping()
    {
        if (empty($this->localesMapping)) {
            $this->localesMapping = $this->configRepository->get('laravellocalization.localesMapping');
        }

        return $this->localesMapping;
    }

    /**
     * Returns a locale from the mapping.
     *
     * @param string|null $locale
     *
     * @return string|null
     */
    public function getLocaleFromMapping($locale)
    {
        return $this->getLocalesMapping()[$locale] ?? $locale;
    }

    /**
     * Returns inversed locale from the mapping.
     *
     * @param string|null $locale
     *
     * @return string|null
     */
    public function getInversedLocaleFromMapping($locale)
    {
        return \array_flip($this->getLocalesMapping())[$locale] ?? $locale;
    }

    /**
     * Return an array of all supported Locales.
     *
     * @throws SupportedLocalesNotDefined
     *
     * @return array
     */
    public function getSupportedLocales()
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
     *
     * @return array
     */
    public function getLocalesOrder()
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
     *
     * @return string current locale name
     */
    public function getCurrentLocaleName()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['name'];
    }

    /**
     * Returns current locale native name.
     *
     * @return string current locale native name
     */
    public function getCurrentLocaleNative()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['native'];
    }

    /**
     * Returns current locale direction.
     *
     * @return string current locale direction
     */
    public function getCurrentLocaleDirection()
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
     *
     * @return string current locale script
     */
    public function getCurrentLocaleScript()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['script'];
    }

    /**
     * Returns current language's native reading.
     *
     * @return string current language's native reading
     */
    public function getCurrentLocaleNativeReading()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['native'];
    }

    public function setCurrentLocale(string $locale): void {
        $this->currentLocale = $locale;
    }

    /**
     * Returns current language.
     *
     * @return string current language
     */
    public function getCurrentLocale()
    {
        if ($this->currentLocale) {
            return $this->currentLocale;
        }

        if ($this->useAcceptLanguageHeader() && !$this->app->runningInConsole()) {
            $negotiator = new LanguageNegotiator($this->defaultLocale, $this->getSupportedLocales(), $this->request);

            return $negotiator->negotiateLanguage();
        }

        // or get application default language
        return $this->configRepository->get('app.locale');
    }

    /**
     * Returns current regional.
     *
     * @return string current regional
     */
    public function getCurrentLocaleRegional(): string|null
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
     *
     * @return array keys of supported languages
     */
    public function getSupportedLanguagesKeys()
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
    public function checkLocaleInSupportedLocales($locale)
    {
        $inversedLocale = $this->getInversedLocaleFromMapping($locale);
        $locales = $this->getSupportedLocales();
        if ($locale !== false && empty($locales[$locale]) && empty($locales[$inversedLocale])) {
            return false;
        }

        return true;
    }

    /**
     * Set current route name.
     *
     * @param string $routeName current route name
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * Returns the config repository for this instance.
     *
     * @return \Illuminate\Contracts\Config\Repository Configuration repository
     */
    public function getConfigRepository()
    {
        return $this->configRepository;
    }

    /**
     * Returns the translation key for a given path.
     *
     * @return bool Returns value of useAcceptLanguageHeader in config.
     */
    public function useAcceptLanguageHeader()
    {
        return $this->configRepository->get('laravellocalization.useAcceptLanguageHeader');
    }

    public function hideUrlAndAcceptHeader()
    {
      return $this->hideDefaultLocaleInURL() && $this->useAcceptLanguageHeader();
    }

    /**
     * Returns the translation key for a given path.
     *
     * @return bool Returns value of hideDefaultLocaleInURL in config.
     */
    public function hideDefaultLocaleInURL()
    {
        return $this->configRepository->get('laravellocalization.hideDefaultLocaleInURL');
    }

    /**
     * Create an url from the uri.
     *
     * @param string $uri Uri
     *
     * @return string Url for the given uri
     */
    public function createUrlFromUri(string $uri): string
    {
        $uri = ltrim($uri, '/');
        return app('url')->to($uri);
    }

    /**
     * Extract attributes for current url.
     *
     * @param bool|false|null|string $url    to extract attributes, if not present, the system will look for attributes in the current call
     * @param string                 $locale
     *
     * @return array Array with attributes
     */
    protected function extractAttributes($url = false, $locale = '')
    {
        if (!empty($url)) {
            $attributes = [];
            $parse = parse_url($url);
            if (isset($parse['path'])) {
                $parse['path'] = trim(str_replace('/'.$this->currentLocale.'/', '', $parse['path']), "/");
                $url = explode('/', trim($parse['path'], '/'));
            } else {
                $url = [];
            }

            foreach ($this->router->getRoutes() as $route) {
                $attributes = [];
                $path = method_exists($route, 'uri') ? $route->uri() : $route->getUri();

                if (!preg_match("/{[\w]+\??}/", $path)) {
                    continue;
                }

                $path = explode('/', $path);
                $i = 0;

                // The system's route can't be smaller
                // only the $url can be missing segments (optional parameters)
                // We can assume it's the wrong route
                if (count($path) < count($url)) {
                    continue;
                }

                $match = true;
                foreach ($path as $j => $segment) {
                    if (isset($url[$i])) {
                        if ($segment === $url[$i]) {
                            $i++;
                            continue;
                        } elseif (preg_match("/{[\w]+}/", $segment)) {
                            // must-have parameters
                            $attribute_name = preg_replace(['/}/', '/{/', "/\?/"], '', $segment);
                            $attributes[$attribute_name] = $url[$i];
                            $i++;
                            continue;
                        } elseif (preg_match("/{[\w]+\?}/", $segment)) {
                            // optional parameters
                            if (!isset($path[$j + 1]) || $path[$j + 1] !== $url[$i]) {
                                // optional parameter taken
                                $attribute_name = preg_replace(['/}/', '/{/', "/\?/"], '', $segment);
                                $attributes[$attribute_name] = $url[$i];
                                $i++;
                                continue;
                            } else {
                                $match = false;
                                break;
                            }
                        } else {
                            // As soon as one segment doesn't match, then we have the wrong route
                            $match = false;
                            break;
                        }
                    } elseif (preg_match("/{[\w]+\?}/", $segment)) {
                        $attribute_name = preg_replace(['/}/', '/{/', "/\?/"], '', $segment);
                        $attributes[$attribute_name] = null;
                        $i++;
                    } else {
                        // no optional parameters but no more $url given
                        // this route does not match the url
                        $match = false;
                        break;
                    }
                }

                if (isset($url[$i + 1])) {
                    $match = false;
                }

                if ($match) {
                    return $attributes;
                }
            }
        } else {
            if (!$this->router->current()) {
                return [];
            }

            $attributes = $this->normalizeAttributes($this->router->current()->parameters());
            $response = event('routes.translation', [$locale, $attributes]);

            if (!empty($response)) {
                $response = array_shift($response);
            }

            if (\is_array($response)) {
                $attributes = array_merge($attributes, $response);
            }
        }

        return $attributes;
    }

    /**
     * Build URL using array data from parse_url.
     *
     * @param array|false $parsed_url Array of data from parse_url function
     *
     * @return string Returns URL as string.
     */
    protected function unparseUrl($parsed_url)
    {
        if (empty($parsed_url)) {
            return '';
        }

        $url = '';
        $url .= isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $url .= $parsed_url['host'] ?? '';
        $url .= isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        $user = $parsed_url['user'] ?? '';
        $pass = isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '';
        $url .= $user.(($user || $pass) ? "$pass@" : '');

        if (!empty($url)) {
            $url .= isset($parsed_url['path']) ? '/'.ltrim($parsed_url['path'], '/') : '';
        } else {
            $url .= $parsed_url['path'] ?? '';
        }

        $url .= isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '';
        $url .= isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : '';

        return $url;
    }

    /**
    * Normalize attributes gotten from request parameters.
    *
    * @param      array  $attributes  The attributes
    * @return     array  The normalized attributes
    */
     protected function normalizeAttributes($attributes)
     {
         if (array_key_exists('data', $attributes) && \is_array($attributes['data']) && ! \count($attributes['data'])) {
             $attributes['data'] = null;
             return $attributes;
         }
         return $attributes;
     }
}
