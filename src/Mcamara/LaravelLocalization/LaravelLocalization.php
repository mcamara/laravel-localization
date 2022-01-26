<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Str;
use Illuminate\Support\Env;
use Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined;
use Mcamara\LaravelLocalization\Exceptions\UnsupportedLocaleException;

class LaravelLocalization
{
    /**
     * The env key that the forced locale for routing is stored in.
     */
    const ENV_ROUTE_KEY = 'ROUTING_LOCALE';

    /**
     * Config repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $configRepository;

    /**
     * Illuminate view Factory.
     *
     * @var \Illuminate\View\Factory
     */
    protected $view;

    /**
     * Illuminate translator class.
     *
     * @var \Illuminate\Translation\Translator
     */
    protected $translator;

    /**
     * Illuminate router class.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * Illuminate request class.
     *
     * @var \Illuminate\Routing\Request
     */
    protected $request;

    /**
     * Illuminate url class.
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * Illuminate request class.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Illuminate request class.
     *
     * @var string
     */
    protected $baseUrl;

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
     * An array that contains all routes that should be translated.
     *
     * @var array
     */
    protected $translatedRoutes = [];

    /**
     * Name of the translation key of the current route, it is used for url translations.
     *
     * @var string
     */
    protected $routeName;

    /**
     * An array that contains all translated routes by url
     *
     * @var array
     */
    protected $cachedTranslatedRoutesByUrl = [];

    /**
     * Creates new instance.
     *
     * @throws UnsupportedLocaleException
     */
    public function __construct()
    {
        $this->app = app();

        $this->configRepository = $this->app['config'];
        $this->view = $this->app['view'];
        $this->translator = $this->app['translator'];
        $this->router = $this->app['router'];
        $this->request = $this->app['request'];
        $this->url = $this->app['url'];

        // set default locale
        $this->defaultLocale = $this->configRepository->get('app.locale');
        $supportedLocales = $this->getSupportedLocales();

        if (empty($supportedLocales[$this->defaultLocale])) {
            throw new UnsupportedLocaleException('Laravel default locale is not in the supportedLocales array.');
        }
    }

    /**
     * Set and return current locale.
     *
     * @param string $locale Locale to set the App to (optional)
     *
     * @return string Returns locale (if route has any) or null (if route does not have a locale)
     */
    public function setLocale($locale = null)
    {
        if (empty($locale) || !\is_string($locale)) {
            // If the locale has not been passed through the function
            // it tries to get it from the first segment of the url
            $locale = $this->request->segment(1);

            // If the locale is determined by env, use that
            // Note that this is how per-locale route caching is performed.
            if ( ! $locale) {
                $locale = $this->getForcedLocale();
            }
        }

        $locale = $this->getInversedLocaleFromMapping($locale);

        if (!empty($this->supportedLocales[$locale])) {
            $this->currentLocale = $locale;
        } else {
            // if the first segment/locale passed is not valid
            // the system would ask which locale have to take
            // it could be taken by the browser
            // depending on your configuration

            $locale = null;

            // if we reached this point and hideDefaultLocaleInURL is true
            // we have to assume we are routing to a defaultLocale route.
            if ($this->hideDefaultLocaleInURL()) {
                $this->currentLocale = $this->defaultLocale;
            }
            // but if hideDefaultLocaleInURL is false, we have
            // to retrieve it from the browser...
            else {
                $this->currentLocale = $this->getCurrentLocale();
            }
        }

        $this->app->setLocale($this->currentLocale);

        // Regional locale such as de_DE, so formatLocalized works in Carbon
        $regional = $this->getCurrentLocaleRegional();
        $suffix = $this->configRepository->get('laravellocalization.utf8suffix');
        if ($regional) {
            setlocale(LC_TIME, $regional . $suffix);
            setlocale(LC_MONETARY, $regional . $suffix);
        }

        return $this->getLocaleFromMapping($locale);
    }

    /**
     * Check if $locale is default locale and supposed to be hidden in url
     *
     * @param string $locale Locale to be checked
     *
     * @return boolean Returns true if above requirement are met, otherwise false
     */

     public function isHiddenDefault($locale)
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
     * Returns an URL adapted to $locale or current locale.
     *
     * @param string      $url    URL to adapt. If not passed, the current url would be taken.
     * @param string|bool $locale Locale to adapt, false to remove locale
     *
     * @throws UnsupportedLocaleException
     *
     * @return string URL translated
     */
    public function localizeURL($url = null, $locale = null)
    {
        return $this->getLocalizedURL($locale, $url);
    }

    /**
     * Returns an URL adapted to $locale.
     *
     *
     * @param string|bool  $locale     Locale to adapt, false to remove locale
     * @param string|false $url        URL to adapt in the current language. If not passed, the current url would be taken.
     * @param array        $attributes Attributes to add to the route, if empty, the system would try to extract them from the url.
     * @param bool         $forceDefaultLocation Force to show default location even hideDefaultLocaleInURL set as TRUE
     *
     * @throws SupportedLocalesNotDefined
     * @throws UnsupportedLocaleException
     *
     * @return string|false URL translated, False if url does not exist
     */
    public function getLocalizedURL($locale = null, $url = null, $attributes = [], $forceDefaultLocation = false)
    {
        if ($locale === null) {
            $locale = $this->getCurrentLocale();
        }

        if (!$this->checkLocaleInSupportedLocales($locale)) {
            throw new UnsupportedLocaleException('Locale \''.$locale.'\' is not in the list of supported locales.');
        }

        if (empty($attributes)) {
            $attributes = $this->extractAttributes($url, $locale);
        }

        if (empty($url)) {
            $url = $this->request->fullUrl();
            $urlQuery = parse_url($url, PHP_URL_QUERY);
            $urlQuery = $urlQuery ? '?'.$urlQuery : '';

            if (!empty($this->routeName)) {
                return $this->getURLFromRouteNameTranslated($locale, $this->routeName, $attributes, $forceDefaultLocation) . $urlQuery;
            }
        } else {
            $urlQuery = parse_url($url, PHP_URL_QUERY);
            $urlQuery = $urlQuery ? '?'.$urlQuery : '';

            $url = $this->url->to($url);
        }

        $url = preg_replace('/'. preg_quote($urlQuery, '/') . '$/', '', $url);

        if ($locale && $translatedRoute = $this->findTranslatedRouteByUrl($url, $attributes, $this->currentLocale)) {
            return $this->getURLFromRouteNameTranslated($locale, $translatedRoute, $attributes, $forceDefaultLocation).$urlQuery;
        }

        $base_path = $this->request->getBaseUrl();
        $parsed_url = parse_url($url);
        $url_locale = $this->getDefaultLocale();

        if (!$parsed_url || empty($parsed_url['path'])) {
            $path = $parsed_url['path'] = '';
        } else {
            $parsed_url['path'] = str_replace($base_path, '', '/'.ltrim($parsed_url['path'], '/'));
            $path = $parsed_url['path'];
            foreach ($this->getSupportedLocales() as $localeCode => $lang) {
                $localeCode = $this->getLocaleFromMapping($localeCode);

                $parsed_url['path'] = preg_replace('%^/?'.$localeCode.'/%', '$1', $parsed_url['path']);
                if ($parsed_url['path'] !== $path) {
                    $url_locale = $localeCode;
                    break;
                }

                $parsed_url['path'] = preg_replace('%^/?'.$localeCode.'$%', '$1', $parsed_url['path']);
                if ($parsed_url['path'] !== $path) {
                    $url_locale = $localeCode;
                    break;
                }
            }
        }

        $parsed_url['path'] = ltrim($parsed_url['path'], '/');

        if ($translatedRoute = $this->findTranslatedRouteByPath($parsed_url['path'], $url_locale)) {
            return $this->getURLFromRouteNameTranslated($locale, $translatedRoute, $attributes, $forceDefaultLocation).$urlQuery;
        }

        $locale = $this->getLocaleFromMapping($locale);

        if (!empty($locale)) {
            if ($forceDefaultLocation || $locale != $this->getDefaultLocale() || !$this->hideDefaultLocaleInURL()) {
                $parsed_url['path'] = $locale.'/'.ltrim($parsed_url['path'], '/');
            }
        }
        $parsed_url['path'] = ltrim(ltrim($base_path, '/').'/'.$parsed_url['path'], '/');

        //Make sure that the pass path is returned with a leading slash only if it come in with one.
        if (Str::startsWith($path, '/') === true) {
            $parsed_url['path'] = '/'.$parsed_url['path'];
        }
        $parsed_url['path'] = rtrim($parsed_url['path'], '/');

        $url = $this->unparseUrl($parsed_url);

        if ($this->checkUrl($url)) {
            return $url.$urlQuery;
        }

        return $this->createUrlFromUri($url).$urlQuery;
    }

    /**
     * Returns an URL adapted to the route name and the locale given.
     *
     *
     * @param string|bool $locale       Locale to adapt
     * @param string      $transKeyName Translation key name of the url to adapt
     * @param array       $attributes   Attributes for the route (only needed if transKeyName needs them)
     * @param bool        $forceDefaultLocation Force to show default location even hideDefaultLocaleInURL set as TRUE
     *
     * @throws SupportedLocalesNotDefined
     * @throws UnsupportedLocaleException
     *
     * @return string|false URL translated
     */
    public function getURLFromRouteNameTranslated($locale, $transKeyName, $attributes = [], $forceDefaultLocation = false)
    {
        if (!$this->checkLocaleInSupportedLocales($locale)) {
            throw new UnsupportedLocaleException('Locale \''.$locale.'\' is not in the list of supported locales.');
        }

        if (!\is_string($locale)) {
            $locale = $this->getDefaultLocale();
        }

        $route = '';

        if ($forceDefaultLocation || !($locale === $this->defaultLocale && $this->hideDefaultLocaleInURL())) {
            $route = '/'.$locale;
        }
        if (\is_string($locale) && $this->translator->has($transKeyName, $locale)) {
            $translation = $this->translator->get($transKeyName, [], $locale);
            $route .= '/'.$translation;

            $route = $this->substituteAttributesInRoute($attributes, $route, $locale);
        }

        if (empty($route)) {
            // This locale does not have any key for this route name
            return false;
        }

        return rtrim($this->createUrlFromUri($route), '/');
    }

    /**
     * It returns an URL without locale (if it has it)
     * Convenience function wrapping getLocalizedURL(false).
     *
     * @param string|false $url URL to clean, if false, current url would be taken
     *
     * @return string URL with no locale in path
     */
    public function getNonLocalizedURL($url = null)
    {
        return $this->getLocalizedURL(false, $url);
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
    public function getCurrentLocaleRegional()
    {
        // need to check if it exists, since 'regional' has been added
        // after version 1.0.11 and existing users will not have it
        if (isset($this->supportedLocales[$this->getCurrentLocale()]['regional'])) {
            return $this->supportedLocales[$this->getCurrentLocale()]['regional'];
        } else {
            return;
        }
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
     * Change route attributes for the ones in the $attributes array.
     *
     * @param $attributes array Array of attributes
     * @param string $route string route to substitute
     *
     * @return string route with attributes changed
     */
    protected function substituteAttributesInRoute($attributes, $route, $locale = null)
    {
        foreach ($attributes as $key => $value) {
            if ($value instanceOf Interfaces\LocalizedUrlRoutable) {
                $value = $value->getLocalizedRouteKey($locale);
            }
            elseif ($value instanceOf UrlRoutable) {
                $value = $value->getRouteKey();
            }
            $route = str_replace(array('{'.$key.'}', '{'.$key.'?}'), $value, $route);
        }

        // delete empty optional arguments that are not in the $attributes array
        $route = preg_replace('/\/{[^)]+\?}/', '', $route);

        return $route;
    }

    /**
     * Returns translated routes.
     *
     * @return array translated routes
     */
    protected function getTranslatedRoutes()
    {
        return $this->translatedRoutes;
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
     * Translate routes and save them to the translated routes array (used in the localize route filter).
     *
     * @param string $routeName Key of the translated string
     *
     * @return string Translated string
     */
    public function transRoute($routeName)
    {
        if (!\in_array($routeName, $this->translatedRoutes)) {
            $this->translatedRoutes[] = $routeName;
        }

        return $this->translator->get($routeName);
    }

    /**
     * Returns the translation key for a given path.
     *
     * @param string $path Path to get the key translated
     *
     * @return string|false Key for translation, false if not exist
     */
    public function getRouteNameFromAPath($path)
    {
        $attributes = $this->extractAttributes($path);

        $path = parse_url($path)['path'];
        $path = trim(str_replace('/'.$this->currentLocale.'/', '', $path), "/");

        foreach ($this->translatedRoutes as $route) {
            if (trim($this->substituteAttributesInRoute($attributes, $this->translator->get($route), $this->currentLocale), '/') === $path) {
                return $route;
            }
        }

        return false;
    }

    /**
     * Returns the translated route for the path and the url given.
     *
     * @param string $path       Path to check if it is a translated route
     * @param string $url_locale Language to check if the path exists
     *
     * @return string|false Key for translation, false if not exist
     */
    protected function findTranslatedRouteByPath($path, $url_locale)
    {
        // check if this url is a translated url
        foreach ($this->translatedRoutes as $translatedRoute) {
            if ($this->translator->get($translatedRoute, [], $url_locale) == rawurldecode($path)) {
                return $translatedRoute;
            }
        }

        return false;
    }

    /**
     * Returns the translated route for an url and the attributes given and a locale.
     *
     *
     * @param string|false|null $url        Url to check if it is a translated route
     * @param array             $attributes Attributes to check if the url exists in the translated routes array
     * @param string            $locale     Language to check if the url exists
     *
     * @throws SupportedLocalesNotDefined
     * @throws UnsupportedLocaleException
     *
     * @return string|false Key for translation, false if not exist
     */
    protected function findTranslatedRouteByUrl($url, $attributes, $locale)
    {
        if (empty($url)) {
            return false;
        }

        if (isset($this->cachedTranslatedRoutesByUrl[$locale][$url])) {
            return $this->cachedTranslatedRoutesByUrl[$locale][$url];
        }

        // check if this url is a translated url
        foreach ($this->translatedRoutes as $translatedRoute) {
            $routeName = $this->getURLFromRouteNameTranslated($locale, $translatedRoute, $attributes);

            // We can ignore extra url parts and compare only their url_path (ignore arguments that are not attributes)
            if (parse_url($this->getNonLocalizedURL($routeName), PHP_URL_PATH) == parse_url($this->getNonLocalizedURL(urldecode($url)), PHP_URL_PATH)) {
                $this->cachedTranslatedRoutesByUrl[$locale][$url] = $translatedRoute;

                return $translatedRoute;
            }
        }

        return false;
    }

    /**
     * Returns true if the string given is a valid url.
     *
     * @param string $url String to check if it is a valid url
     *
     * @return bool Is the string given a valid url?
     */
    protected function checkUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Returns the config repository for this instance.
     *
     * @return Repository Configuration repository
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
    protected function useAcceptLanguageHeader()
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
    public function createUrlFromUri($uri)
    {
        $uri = ltrim($uri, '/');

        if (empty($this->baseUrl)) {
            return app('url')->to($uri);
        }

        return $this->baseUrl.$uri;
    }

    /**
     * Sets the base url for the site.
     *
     * @param string $url Base url for the site
     */
    public function setBaseUrl($url)
    {
        if (substr($url, -1) != '/') {
            $url .= '/';
        }

        $this->baseUrl = $url;
    }

    /**
     * Returns serialized translated routes for caching purposes.
     *
     * @return string
     */
    public function getSerializedTranslatedRoutes()
    {
        return base64_encode(serialize($this->translatedRoutes));
    }

    /**
     * Sets the translated routes list.
     * Only useful from a cached routes context.
     *
     * @param string $serializedRoutes
     */
    public function setSerializedTranslatedRoutes($serializedRoutes)
    {
        if ( ! $serializedRoutes) {
            return;
        }

        $this->translatedRoutes = unserialize(base64_decode($serializedRoutes));
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

    /**
     * Returns the forced environment set route locale.
     *
     * @return string|null
     */
    protected function getForcedLocale()
    {
        if (version_compare($this->app->version(), '6') >= 0) {
            return Env::get(static::ENV_ROUTE_KEY, function () {
                $value = getenv(static::ENV_ROUTE_KEY);

                if ($value !== false) {
                    return $value;
                }
            });
        } else {
            return env(static::ENV_ROUTE_KEY, function () {
                $value = getenv(static::ENV_ROUTE_KEY);

                if ($value !== false) {
                    return $value;
                }
            });
        }
    }
}
