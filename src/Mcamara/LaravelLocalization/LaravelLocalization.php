<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Config\Repository;
use Illuminate\View\Factory;
use Illuminate\Translation\Translator;
use Request;
use Session;
use Cookie;
use App;
use Config;

class LaravelLocalization
{
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
	 * Default locale
	 *
	 * @var string
	 */
	protected $defaultLocale;

	/**
	 * Supported Locales
	 *
	 * @var array
	 */
	protected $supportedLocales;

	/**
	 * Current locale
	 *
	 * @var string
	 */
	protected $currentLocale = false;

	/**
	 * An array that contains all routes that should be translated
	 *
	 * @var array
	 */
	protected $translatedRoutes = array();

	/**
	 * Name of the translation key of the current route, it is used for url translations
	 *
	 * @var array
	 */
	protected $routesNames = array();

	/**
	 * Creates new instance.
     *
     * @throws UnsupportedLocaleException
	 *
	 * @param \Illuminate\Config\Repository $configRepository
	 * @param \Illuminate\View\Factory $view
	 * @param \Illuminate\Translation\Translator $translator
	 */
	public function __construct(Repository $configRepository, Factory $view, Translator $translator)
	{
		$this->configRepository = $configRepository;
		$this->view = $view;
		$this->translator = $translator;

		// set default locale
		$this->defaultLocale = Config::get('app.locale');
        $supportedLocales = $this->getSupportedLocales();
        if (empty($supportedLocales[$this->defaultLocale])) {
            throw new UnsupportedLocaleException("Laravel's default locale is not in the supportedLocales array.");
        }
	}

	/**
	 * Set and return current language
	 *
	 * @param  string $language 		Locale to set the App to (optional)
	 *
	 * @return string 					Returns locale (if route has any) or null (if route does not have a language)
	 */
	public function setLanguage($language = null)
	{
		return $this->setLocale($language);
	}

	/**
	 * Set and return current locale
	 *
	 * @param  string $locale			Locale to set the App to (optional)
	 *
	 * @return string 					Returns locale (if route has any) or null (if route does not have a locale)
	 */
	public function setLocale($locale = null)
	{
		if (is_null($locale) || !is_string($locale))
		{
			// If the locale has not been passed through the function
			// it tries to get it from the first segment of the url
			$locale = Request::segment(1);
		}

		if (!empty($this->supportedLocales[$locale]))
		{
			$this->currentLocale = $locale;
		}
		else
		{
			// if the first segment/locale passed is not valid
			// the system would ask which locale have to take
			// it could be taken by session, browser or app default
			// depending on your configuration

			$locale = null;

			// if we reached this point and hideDefaultLocaleInURL is true
			// we have to assume we are routing to a defaultLocale route.
			if ($this->hideDefaultLocaleInURL())
			{
				$this->currentLocale = $this->defaultLocale;
			}
			// but if hideDefaultLocaleInURL is false, we have
			// to retrieve it from the session/cookie/browser...
			else
			{
				$this->currentLocale = $this->getCurrentLocale();
			}
		}
		App::setLocale($this->currentLocale);

		if ($this->useSessionLocale())
		{
			Session::put('language', $this->currentLocale);
		}
		if ($this->useCookieLocale())
		{
			Cookie::queue(Cookie::forever('language', $this->currentLocale));
		}
		//Forget the language cookie if it's disabled and exists
		else if (Cookie::get('language') != null)
		{
			Cookie::forget('language');
		}
		return $locale;
	}

	/**
	 * Returns html with language selector
	 *
	 * @param  boolean $abbr 			Should languages be abbreviate to their locale codes?
	 * @param  string $customView 		Which template should the language bar have?
	 *
	 * @return \Illuminate\View\View 	Returns an html view with a language bar
     *
     * @deprecated will be removed in v1.0 please see updated readme for details on making your own language bar template.
	 */
	public function getLanguageBar($abbr = false, $customView = 'mcamara/laravel-localization/languagebar')
	{
		//START - Delete in v1.0
		$languages = array();
		$active = $this->currentLocale;
		$urls = array();

		foreach ($this->getSupportedLocales() as $lang => $properties)
		{
			$languages[$lang] = $abbr ? strtoupper($lang) : $properties['name'];

			$langUrl = $this->getLocalizedURL($lang);

			// check if the url is set for the language
			if($langUrl)
			{
				$urls[$lang] = $langUrl;
			}
			else
			{
				// the url is not set for the language (check lang/$lang/routes.php)
				unset($languages[$lang]);
			}
		}
		//END - Delete in v1.0
		if(is_string($customView) && $this->view->exists($customView))
		{
			$view = $customView;
		}
		else
		{
			$view = 'laravel-localization::languagebar';
		}
		return $this->view->make($view, compact('abbr','languages','active','urls')); //Remove 'languages','active', and 'urls' in v1.0
	}

	/**
	 * Returns an URL adapted to $language
	 *
	 * @param  string $language	 Language to adapt
	 * @param  string $route		URL to adapt. If not passed, the current url would be taken.
	 *
	 * @return string			   URL translated
	 *
	 * @deprecated will be removed in v1.0 use getLocalizedURL
	 */
	public function getURLLanguage($language, $route = null)
	{
		return $this->getLocalizedURL($language, $route);
	}

    /**
     * Returns an URL adapted to $locale or current locale
     *
     * @param  string $url				   URL to adapt. If not passed, the current url would be taken.
     * @param  string|boolean $locale	   Locale to adapt, false to remove locale
     *
     * @throws UnsupportedLocaleException
     *
     * @return string					   URL translated
     */
    public function localizeURL($url = null, $locale = null)
    {
        return $this->getLocalizedURL($locale, $url);
    }


	/**
	 * Returns an URL adapted to $locale
	 *
	 * @param  string|boolean 	$locale	   	Locale to adapt, false to remove locale
	 * @param  string 			$url		URL to adapt. If not passed, the current url would be taken.
	 *
	 * @throws UnsupportedLocaleException
	 *
	 * @return string|false				URL translated, False if url does not exist
	 */
	public function getLocalizedURL($locale = null, $url = null)
	{
		if ($locale !== false)
		{   
			if (is_null($locale))
			{
				$locale = $this->getCurrentLocale();
			}
			else
			{
				$locales = $this->getSupportedLocales();
				if (empty($locales[$locale]))
				{
					throw new UnsupportedLocaleException('Locale \'' . $locale . '\' is not in the list of supported locales.');
				}
			}
		}
		else
		{
			$locale = $this->defaultLocale;
		}

		if (is_null($url) || !is_string($url))
		{
			$url = Request::fullUrl();
			if (!empty($this->routesNames))
			{
				// if the system is going to translate the current url
				// and it is a translated route
				// the system would return the translated one
				$urlTranslated = $this->getURLFromRouteNameTranslated($locale);
				if(!$urlTranslated)
				{
					return False;
				}
				
				$url = parse_url($url);
				$urlTranslated = parse_url($urlTranslated);
				$urlTranslated = array_merge($url, $urlTranslated);
				
				return $this->unparseUrl($urlTranslated);
			}
		}

        $base_path = Request::getBaseUrl();
		$parsed_url = parse_url($url);

		if ( !$parsed_url || empty($parsed_url['path']) )
		{
			$path = $parsed_url['path'] = "";
		}
		else
		{
            $parsed_url['path'] = str_replace($base_path, '', '/'.ltrim($parsed_url['path'], '/'));
			$path = $parsed_url['path'];
			foreach ($this->getSupportedLocales() as $localeCode => $lang)
			{
				$parsed_url['path'] = preg_replace('%^/?'.$localeCode.'/%', '$1', $parsed_url['path']);
				if ($parsed_url['path'] != $path)
				{
					break;
				}
				else
				{
					$parsed_url['path'] = preg_replace('%^/?'.$localeCode.'$%', '$1', $parsed_url['path']);
					if ($parsed_url['path'] != $path)
					{
						break;
					}
				}
			}
		}

		$parsed_url['path'] = ltrim($parsed_url['path'], '/');
		if (!empty($locale) && ($locale != $this->defaultLocale || !$this->hideDefaultLocaleInURL()))
		{
			$parsed_url['path'] = $locale . '/' . ltrim($parsed_url['path'], '/');
		}
        $parsed_url['path'] = ltrim(ltrim($base_path, '/') . '/' . $parsed_url['path'], '/');
		//Make sure that the pass path is returned with a leading slash only if it come in with one.
		if (starts_with($path, '/') === true) {
			$parsed_url['path'] = '/' . $parsed_url['path'];
		}
		$parsed_url['path'] = rtrim($parsed_url['path'], '/');

		return $this->unparseUrl($parsed_url);
	}


	/**
	 * Returns an URL adapted to the route name and the locale given
	 *
	 * @param  string|boolean 	$locale 			Locale to adapt
	 * @param  string 			$transKeysNames  	Translation key name of the url to adapt
	 * @param  array 			$attributes  		Attributes for the route (only needed if transKeyName needs them)
	 *
	 * @return string|false 			URL translated
	 */
	public function getURLFromRouteNameTranslated($locale, $transKeysNames = array(), $attributes = array())
	{
		if (!in_array($locale, array_keys($this->configRepository->get('laravel-localization::supportedLocales'))))
		{
			// if a locale is not accepted, return false
			return false;
		}

		if (empty($transKeysNames))
		{
			// if translation key name is not given
			// the system would try to get the current one...
			if (empty($this->routesNames))
			{
				// ... if it is false, the route is impossible to translate
				return false;
			}
			$transKeysNames = $this->routesNames;
			if (sizeof($attributes) === 0)
			{
				// if there are no attributes and the current url has some
				// the system will take the same
				global $app;
				$router = $app['router'];
				$attributes = $router->current()->parameters();
                $response = \Event::fire('routes.translation', array('locale' => $locale, 'attributes' => $attributes));
                if(!empty($response)) $response = array_shift($response);
                if(is_array($response)) $attributes = array_merge($attributes, $response);
			}
		}

		$route = Request::getBaseUrl();
		if (!($locale === $this->defaultLocale && $this->hideDefaultLocaleInURL()))
		{
			$route = Request::getBaseUrl().'/'.$locale;
		}
		
		foreach ($transKeysNames as $transKeyName)
		{
			if ($this->translator->has($transKeyName,$locale))
			{
				$translation = $this->translator->trans($transKeyName, [], "", $locale);
				$route = $route."/".$translation;

				if (is_array($attributes))
				{
					foreach ($attributes as $key => $value)
					{
						$route = str_replace("{".$key."}", $value, $route);
						$route = str_replace("{".$key."?}", $value, $route);
					}
				}
				// delete empty optional arguments
				$route = preg_replace('/\/{[^)]+\?}/','',$route);
			}
		}

		if (!empty($route)) return rtrim($route, '/');
		
		// This locale does not have any key for this route name
		return false;

	}

	/**
	 * It returns an URL without language (if it has it)
	 *
	 * @param  string $route URL to clean, if false, current url would be taken
	 *
	 * @return string		Route with no language in path
	 *
	 * @deprecated will be removed in v1.0 use getDefaultLocale instead.
	 */
	public function getCleanRoute($route = null)
	{
		return $this->getNonLocalizedURL($route);
	}

	/**
	 * It returns an URL without locale (if it has it)
	 * Convenience function wrapping getLocalizedURL(false)
	 *
	 * @param  string $url	  URL to clean, if false, current url would be taken
	 *
	 * @return string		   URL with no locale in path
	 */
	public function getNonLocalizedURL($url = null)
	{
		return $this->getLocalizedURL(false, $url);
	}

	/**
	 * Returns default locale
	 *
	 * @return string
	 *
	 * @deprecated will be removed in v1.0 use getDefaultLocale instead.
	 */
	public function getDefault()
	{
		return $this->getDefaultLocale();
	}

	/**
	 * Returns default locale
	 *
	 * @return string
	 */
	public function getDefaultLocale()
	{
		return $this->defaultLocale;
	}

	/**
	 * Returns all allowed languages
	 *
	 * @param  boolean $abbr should the languages be abbreviated?
	 *
	 * @return array Array with all allowed languages
	 *
	 * @deprecated will be removed in v1.0 use getSupportedLocales instead.
	 */
	public function getAllowedLanguages($abbr = true)
	{
		$allowed = array();

		foreach ($this->getSupportedLocales() as $localeCode => $properties)
		{
			$allowed[$localeCode] = $abbr ? $localeCode : $properties['name'];
		}

		return $allowed;
	}

	/**
	 * Returns all supported languages
	 *
	 * @return array Array with all supported languages
	 *
	 * @deprecated will be removed in v1.0 use getSupportedLocales instead.
	 */
	public function getSupportedLanguages()
	{
		$names = array();

		foreach ($this->getSupportedLocales() as $localeCode => $properties)
		{
			if (is_string($properties))
			{
				// this is for avoiding breaking old config files
				$names[$localeCode] = $properties;
			}
			elseif (is_array($properties))
			{
				foreach ($properties as $key => $val)
				{
					if ($key == 'name')
					{
						$names[$localeCode] = $val;
					}
				}
			}
		}

		return $names;
	}

	/**
	 * Build the new supported Locales array using deprecated config options
	 *
	 * @return array
	 *
	 * @deprecated will be removed in v1.0
	 */
	private function buildDeprecatedConfig()
	{
		//Use deprecated languagesAllowed & languagesSupported to build supportedLocales.
		$allowed = $this->configRepository->get('laravel-localization::languagesAllowed');
		$supported = $this->configRepository->get('laravel-localization::supportedLanguages');

		$locales = array();
		foreach ($allowed as $localeCode)
		{
			$locales[$localeCode] = array(
				'name' => $supported[$localeCode]['name']
			);
		}

		return $locales;
	}

	/**
	 * Return an array of all supported Locales
	 *
	 * @return array
	 */
	public function getSupportedLocales()
	{
		if (!empty($this->supportedLocales))
		{
			return $this->supportedLocales;
		}

		if ($this->configRepository->has('laravel-localization::languagesAllowed') && $this->configRepository->has('laravel-localization::supportedLanguages')) {
			$locales = $this->buildDeprecatedConfig();
		}
		else
		{
			$locales = $this->configRepository->get('laravel-localization::supportedLocales');
		}

		$this->supportedLocales = $locales;

		return $locales;
	}

	/**
	 * Returns current locale name
	 *
	 * @return string current locale name
	 */
	public function getCurrentLocaleName()
	{
		return $this->supportedLocales[$this->getCurrentLocale()]['name'];
	}

	/**
	 * Returns current language direction
	 *
	 * @return string current language direction
	 *
	 * @deprecated will be removed in v1.0
	 */
	public function getCurrentLanguageDirection()
	{
		return $this->getCurrentLocaleDirection();
	}

	/**
	 * Returns current locale direction
	 *
	 * @return string current locale direction
	 */
	public function getCurrentLocaleDirection()
	{
		return $this->supportedLocales[$this->getCurrentLocale()]['dir'];
	}

	/**
	 * Returns current language script
	 *
	 * @return string current language script
	 *
	 * @deprecated will be removed in v1.0
	 */
	public function getCurrentLanguageScript()
	{
		return $this->getCurrentLocaleScript();
	}

	/**
	 * Returns current locale script
	 *
	 * @return string current locale script
	 */
	public function getCurrentLocaleScript()
	{
		return $this->supportedLocales[$this->getCurrentLocale()]['script'];
	}

	/**
	 * Returns current language's native reading
	 *
	 * @return string current language's native reading
	 */
	public function getCurrentLocaleNativeReading()
	{
		return $this->supportedLocales[$this->getCurrentLocale()]['native'];
	}

	/**
	 * Returns the class name of the language bar
	 *
	 * @return string Language bar class name
	 *
	 * @deprecated will be removed in v1.0
	 */
	public function getLanguageBarClassName()
	{
		$className = $this->configRepository->get('laravel-localization::languageBarClass');

		return empty($className) ? 'laravel_language_chooser' : $className;
	}

	/**
	 * Returns if the current language should be printed in the language bar
	 *
	 * @return boolean Should the current language be printed?
	 *
	 * @deprecated will be removed in v1.0
	 */
	public function getPrintCurrentLanguage()
	{
		$print = $this->configRepository->get('laravel-localization::printCurrentLanguageInBar');
		if (isset($print))
		{
			return $print;
		}
		return true;
	}

	/**
	 * Returns current language
	 *
	 * @return string current language
	 */
	public function getCurrentLocale()
	{
		if ($this->currentLocale)
		{
			return $this->currentLocale;
		}
		$locales = $this->getSupportedLocales();
		// get session language...
		if ($this->useSessionLocale() && Session::has('language'))
		{
			return Session::get('language');
		}
		// or get cookie language...
		else if ($this->useCookieLocale() &&
			Cookie::get('language') != null &&
			!empty($locales[Cookie::get('language')]))
		{
			return Cookie::get('language');
		}
		// or get browser language...
		else if ($this->useAcceptLanguageHeader())
		{
			return $this->negotiateLanguage();
		}

		// or get application default language
		return $this->configRepository->get('app.locale');
	}

	/**
	 * Returns an URL adapted to $language
	 *
	 * @return string current language
	 *
	 * @deprecated will be removed in v1.0 use getCurrentLocale
	 */
	public function getCurrentLanguage()
	{
		return $this->getCurrentLocale();
	}

	/**
	 * Returns translated routes
	 *
	 * @return array translated routes
	 */
	public function getTranslatedRoutes()
	{
		return $this->translatedRoutes;
	}

	/**
	 * Set current route name
	 * @param string|array $routeNames  current route name
	 */
	public function setRouteName($routeNames, $add = false)
	{
		if(!$add)
		{
			$this->routesNames = [];
		}

		if(is_string($routeNames))
		{
			$this->routesNames[] = $routeNames;
		}
		else
		{
			$this->routesNames = array_merge($this->routesNames, $routeNames);
		}
	}

	/**
	 * Translate routes and save them to the translated routes array (used in the localize route filter)
	 *
	 * @param  string $routeName key of the translated string
	 *
	 * @return string			translated string
	 */
	public function transRoute($routeName)
	{
		if (!in_array($routeName, $this->translatedRoutes))
		{
			$this->translatedRoutes[] = $routeName;
		}
		return $this->translator->trans($routeName);
	}

	/**
	 * Returns the translation key for a given path
	 *
	 * @param  string $path 	Path to get the key translated
	 *
	 * @return arrays 			Keys for translation
	 */
	public function getRouteNameFromAPath($path)
	{
		$path = str_replace(url(), "", $path);
		if ($path[0] !== '/')
		{
			$path = '/' . $path;
		}
		$path = str_replace('/' . $this->currentLocale . '/', '', $path);
		$path = trim($path,"/");
		$routesNames = [];

        foreach ($this->translatedRoutes as $route)
        {
            if ($this->translator->trans($route) == $path)
            {
                $routesNames[] = $route;
            }
        }

		return $routesNames;
	}


	/**
	 * Returns the translation key for a given path
	 *
	 * @return boolean	   Returns value of useSessionLocale in config.
	 */
	private function useSessionLocale()
	{
		return $this->configRepository->get('laravel-localization::useSessionLocale') || $this->configRepository->get('laravel-localization::useSessionLanguage');
	}

	/**
	 * Returns the translation key for a given path
	 *
	 * @return boolean	   Returns value of useCookieLocale in config.
	 */
	private function useCookieLocale()
	{
		return $this->configRepository->get('laravel-localization::useCookieLocale') || $this->configRepository->get('laravel-localization::useCookieLanguage');
	}

	/**
	 * Returns the translation key for a given path
	 *
	 * @return boolean	   Returns value of useAcceptLanguageHeader in config.
	 */
	private function useAcceptLanguageHeader()
	{
		return $this->configRepository->get('laravel-localization::useAcceptLanguageHeader') || $this->configRepository->get('laravel-localization::useBrowserLanguage');
	}

	/**
	 * Returns the translation key for a given path
	 *
	 * @return boolean	   Returns value of hideDefaultLocaleInURL in config.
	 */
	public function hideDefaultLocaleInURL()
	{
		return $this->configRepository->get('laravel-localization::hideDefaultLocaleInURL') || $this->configRepository->get('laravel-localization::hideDefaultLanguageInRoute');
	}

	/**
	 * Build URL using array data from parse_url
	 *
	 * @param array $parsed_url	 Array of data from parse_url function
	 *
	 * @return string			   Returns URL as string.
	 */
	private function unparseUrl($parsed_url) {
		$url = "";
		$url .= isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$url .= isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$url .= isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$url .= $user . (($user || $pass) ? "$pass@" : '');
		if (!empty($url)) {
			$url .= isset($parsed_url['path']) ? '/' . ltrim($parsed_url['path'], '/') : '';
		}
		else
		{
			$url .= isset($parsed_url['path']) ? $parsed_url['path'] : '';
		}
		$url .= isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$url .= isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		return $url;
	}

	/**
	 * Negotiates language with the user's browser through the Accept-Language
	 * HTTP header or the user's host address.  Language codes are generally in
	 * the form "ll" for a language spoken in only one country, or "ll-CC" for a
	 * language spoken in a particular country.  For example, U.S. English is
	 * "en-US", while British English is "en-UK".  Portuguese as spoken in
	 * Portugal is "pt-PT", while Brazilian Portuguese is "pt-BR".
	 *
	 * This function is based on negotiateLanguage from Pear HTTP2
	 * http://pear.php.net/package/HTTP2/
	 *
	 * Quality factors in the Accept-Language: header are supported, e.g.:
	 *	  Accept-Language: en-UK;q=0.7, en-US;q=0.6, no, dk;q=0.8
	 *
	 * @return string  The negotiated language result or app.locale.
	 */
	public function negotiateLanguage()
	{
		$default = $this->configRepository->get('app.locale');
		$supported = array();
		foreach ($this->configRepository->get('laravel-localization::supportedLocales') as $lang => $language)
		{
			$supported[$lang] = $lang;
		}

		if (!count($supported))
		{
			return $default;
		}

		if (Request::header('Accept-Language'))
		{
			$matches = array();
			$generic_matches = array();
			foreach (explode(',', Request::header('Accept-Language')) as $option)
			{
				$option = array_map('trim', explode(';', $option));

				$l = $option[0];
				if (isset($option[1]))
				{
					$q = (float) str_replace('q=', '', $option[1]);
				}
				else
				{
					$q = null;
					// Assign default low weight for generic values
					if ($l == '*/*')
					{
						$q = 0.01;
					}
					elseif (substr($l, -1) == '*')
					{
						$q = 0.02;
					}
				}
				// Unweighted values, get high weight by their position in the
				// list
				$q = isset($q) ? $q : 1000 - count($matches);
				$matches[$l] = $q;

				//If for some reason the Accept-Language header only sends language with country
				//we should make the language without country an accepted option, with a value
				//less than it's parent.
				$l_ops = explode('-', $l);
				array_pop($l_ops);
				while (!empty($l_ops))
				{
					//The new generic option needs to be slightly less important than it's base
					$q -= 0.001;
					$op = implode('-', $l_ops);
					if (empty($generic_matches[$op]) || $generic_matches[$op] > $q)
					{
						$generic_matches[$op] = $q;
					}
					array_pop($l_ops);
				}
			}
			$matches = array_merge($generic_matches, $matches);

			arsort($matches, SORT_NUMERIC);

			foreach ($matches as $key => $q)
			{
				if (isset($supported[$key]))
				{
					return $supported[$key];
				}
			}
			// If any (i.e. "*") is acceptable, return the first supported format
			if (isset($matches['*']))
			{
				return array_shift($supported);
			}
		}
		
	        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
	        {
	            if ($_SERVER['HTTP_ACCEPT_LANGUAGE'] != '')
	            {
                	$http_accept_language = \locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
                	if (in_array($http_accept_language, $supported))
                	{
                    		return $http_accept_language;
                	}
	            }
	        }		

		if (Request::server('REMOTE_HOST'))
		{
			$lang = strtolower( end( explode('.', Request::server('REMOTE_HOST') ) ) );
			if (isset($supported[$lang]))
			{
				return $supported[$lang];
			}
		}

		return $default;
	}

}
