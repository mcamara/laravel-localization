<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Config\Repository;
use Illuminate\View\Environment;
use Illuminate\Translation\Translator;
use Request;
use Session;
use Cookie;
use App;
use View;
use Config;
use Redirect;
use Route;

class LaravelLocalization
{
    /**
     * Config repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $configRepository;

	/**
     * Illuminate view environment.
     *
     * @var \Illuminate\View\Environment
     */
    protected $view;

	/**
     * Illuminate translator class.
     *
     * @var \Illuminate\Translation\Translator
     */
    protected $translator;

    /**
     * Default language
     *
     * @var string
     */
    protected $defaultLanguage;

    /**
     * Supported Locales
     *
     * @var array
     */
    protected $supportedLocales;

    /**
     * Current language
     *
     * @var string
     */
    protected $currentLanguage = false;

    /**
     * An array that contains all routes that should be translated
     *
     * @var array
     */
    protected $translatedRoutes = array();

    /**
     * Name of the translation key of the current route, it is used for url translations
     *
     * @var string
     */
    protected $routeName = false;

    /**
     * Creates new instance.
     *
     * @param \Illuminate\Config\Repository $configRepository
     * @param \Illuminate\View\Environment $view
     * @param \Illuminate\Translation\Translator $translator
     */
    public function __construct(Repository $configRepository, Environment $view, Translator $translator)
    {
        $this->configRepository = $configRepository;
        $this->view = $view;
        $this->translator = $translator;

        // set default language
        $this->defaultLanguage = Config::get('app.locale');
    }

	/**
	 * Set and return current language
     *
	 * @param  string $localeCode	Language to set the App to (optional)
     *
	 * @return string 			Returns language (if route has any) or null (if route has not a language)
	 */
	public function setLanguage($localeCode = null)
	{
		$languages = $this->getSupportedLocales();
		if(is_null($localeCode) || !is_string($localeCode))
		{
			// If the locale has not been passed through the function
			// it tries to get it from the first segment of the url
            $localeCode = Request::segment(1);
		}

		if(!empty($languages[$localeCode]))
		{
			$this->currentLanguage = $localeCode;
		}
		else
		{
			// if the first segment/language passed is not valid
			// the system would ask which language have to take
			// it could be taken by session, browser or app default
			// depending on your configuration

            $localeCode = null;

			// if we reached this point and hideDefaultLanguageInRoute is true
			// we have to assume we are routing to a defaultLanguage route.
			if( Config::get('laravel-localization::hideDefaultLanguageInRoute') )
			{
				$this->currentLanguage = $this->defaultLanguage;
			}
			// but if hideDefaultLanguageInRoute is false, we have
			// to retrieve it from the session/cookie/browser...
			else
			{
				$this->currentLanguage = $this->getCurrentLanguage();
			}
		}
		App::setLocale($this->currentLanguage);
		$this->configRepository->set('application.language',  $this->currentLanguage);
		if($this->configRepository->get('laravel-localization::useSessionLanguage'))
		{
			Session::put('language', $this->currentLanguage);
		}
        if($this->configRepository->get('laravel-localization::useCookieLanguage'))
        {
            Cookie::queue(Cookie::forever('language', $this->currentLanguage));
        }
        //Forget the language cookie if it's disabled and exists
        else if (Cookie::get('language') != null)
        {
            Cookie::forget('language');
        }
		return $localeCode;
	}

	/**
	 * Returns html with language selector
     *
	 * @param  boolean $abbr 		Should languages be abbreviate (2 characters) or full named?
	 * @param  string $customView 	Which template should the language bar have?
     *
	 * @return string 				Returns an html view with a language bar
	 */
    public function getLanguageBar($abbr = false, $customView = 'mcamara/laravel-localization/languagebar')
    {
        $languages = array();
        $active = $this->currentLanguage;
        $urls = array();

        foreach ($this->getSupportedLocales() as $localeCode => $language) {
            if ($abbr) {
                $languages[$localeCode] = $localeCode;
            } else if (!empty($language['native'])) {
                $languages[$localeCode] = $language['native'];
            } else {
                $languages[$localeCode] = $language['name'];
            }

            $langUrl = $this->getURLLanguage($localeCode);

            // check if the url is set for the language
            if($langUrl) {
                $urls[$localeCode] = $langUrl;
            } else  {
                // the url is not set for the language (check lang/$lang/routes.php)
                unset($languages[$localeCode]);
            }
        }

        if(is_string($customView) && $this->view->exists($customView))
        {
            $view = $customView;
        }
        else
        {
            $view = 'laravel-localization::languagebar';
        }
        return $this->view->make($view, compact('languages','active','urls'));
    }

    /**
     * Returns an URL adapted to $language language
     *
     * @param  string $localeCode Language to adapt
     * @param  string $route    URL to adapt, if false, current url would be taken
     *
     * @return string           URL translated
     */
    public function getURLLanguage($localeCode, $route = null)
    {
        $locales = $this->getSupportedLocales();
        if(empty($locales[$localeCode]))
        {
			return false;
        }
        if(!isset($route))
        {
        	if($this->routeName)
        	{
        		// if the system is going to translate the current url
        		// and it is a translated route
        		// the system would return the translated one
        		return $this->getURLFromRouteNameTranslated($localeCode);
        	}
			$route = Request::url();
        }
        return str_replace(url(), url($localeCode), $this->getCleanRoute($route));
    }


	/**
	 * Returns an URL adapted to the route name and the language given
     *
	 * @param  string $language 		Language to adapt
	 * @param  string $transKeyName  	Translation key name of the url to adapt
	 * @param  array $attributes  		Attributes for the route (only needed if transKeyName needs them)
     *
	 * @return string 	             	URL translated
	 */
	public function getURLFromRouteNameTranslated($language, $transKeyName = null, $attributes = array())
	{
		if(!in_array($language, $this->configRepository->get('laravel-localization::languagesAllowed')))
		{
			// if a language is not accepted, return false
			return false;
		}

		if(!isset($transKeyName))
		{
			// if translation key name is not given
			// the system would try to get the current one...
			if(!$this->routeName)
			{
				// ... if it is false, the route is impossible to translate
				return false;
			}
			$transKeyName = $this->routeName;
			if(sizeof($attributes) === 0)
			{
				// if there are no attributes and the current url has some
				// the system will take the same
				global $app;
				$router = $app['router'];
				if(App::make('laravel-localization.4.1'))
				{
					// Laravel 4.1
					$attributes = $router->current()->parameters();
				}
				else
				{
					// Laravel 4.0
					$attributes = $router->getCurrentRoute()->getParameters();
				}
			}
		}

		if($this->translator->has($transKeyName,$language))
		{
			$translation = $this->translator->trans($transKeyName,array(),array(),$language);

			// If hideDefaultLanguageInRoute is true, make sure not to include the default locale in the transalted url
			if($language === $this->defaultLanguage && Config::get('laravel-localization::hideDefaultLanguageInRoute') )
			{
				$route = url($translation);
			}
			else
			{
				$route = url($language."/".$translation);
			}

			if(is_array($attributes))
			{
				foreach ($attributes as $key => $value)
				{
					$route = str_replace("{".$key."}", $value, $route);
					$route = str_replace("{".$key."?}", $value, $route);
				}
			}
			// delete empty optional arguments
			$route = preg_replace('/\/{[^)]+\?}/','',$route);
			return rtrim($route, '/');
		}
		// This language does not have any key for this route name
		return false;

	}

	/**
	 * It returns an URL without language (if it has it)
     *
	 * @param  string $route URL to clean, if false, current url would be taken
     *
	 * @return string        Route with no language path
	 */
	public function getCleanRoute($route = null)
	{
        if (empty($route)) {
            $route = Request::url();
        }
        $parsed_route = parse_url($route);
        if(empty($parsed_route['path']))
        {
        	$path = "";
        }
        else
        {
        	$path = $parsed_route['path'];
        }
        $new_path = preg_replace('%^/?'.$this->currentLanguage.'(/?)%', '$1', $path);

        return str_replace($path, $new_path, $route);
    }

	/**
	 * Appends i18n language segment to the URI
     *
	 * @param  string $uri
     * @param  boolean $append_default  If true, append the default language to the path
     *
	 * @return string
	 */
	public function getURI($uri, $append_default = false)
	{
		$current = Config::get('app.locale');
		if ($this->defaultLanguage === $current && $append_default === false)
		{
			return $uri;
		}
		return $current . '/' . $uri;
	}

	/**
	 * Returns default language
     *
	 * @return string
	 */
	public function getDefault()
	{
		return $this->defaultLanguage;
	}

	/**
	 * Returns all allowed languages
     *
	 * @param  boolean $abbr should the languages be abbreviated?
     *
	 * @return array Array with all allowed languages
     *
     * @deprecated use getSupportedLocales instead.
	 */
	public function getAllowedLanguages($abbr = true)
	{
		$allowed = array();

        foreach ($this->getSupportedLocales() as $localeCode => $properties) {
            $allowed[$localeCode] = $abbr ? $localeCode : $properties['name'];
        }

        return $allowed;
	}

	/**
	 * Returns all supported languages
	 *
	 * @return array Array with all supported languages
     *
     * @deprecated use getSupportedLocales instead.
	 */
	public function getSupportedLanguages()
	{
		$names = array();

		foreach ($this->getSupportedLocales() as $localeCode => $properties)
		{
			if(is_string($properties))
			{
				// this is for avoiding breaking old config files
				$names[$localeCode] = $properties;
			}
			elseif (is_array($properties)) {
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
     * @return array|boolean
     */
    private function buildDeprecatedConfig() {
        //Use deprecated languagesAllowed & languagesSupported to build supportedLocales.
        $allowed = $this->configRepository->get('laravel-localization::languagesAllowed');
        if (empty($allowed)) {
            return false;
        }

        $supported = $this->configRepository->get('laravel-localization::supportedLanguages');

        $locales = array();
        foreach ($allowed as $localeCode) {
            $locales[$localeCode] = array(
                'name' => $supported[$localeCode]
            );
        }
        return $locales;
    }

    /**
     * Return an array of all supported Locales
     *
     * @return array
     */
    public function getSupportedLocales() {
        if (!empty($this->supportedLocales)) {
            return $this->supportedLocales;
        }

        $locales = $this->buildDeprecatedConfig();
        if (empty($locales)) {
            $locales = $this->configRepository->get('laravel-localization::supportedLocales');
        }

        $this->supportedLocales = $locales;

        return $locales;
    }

	/**
	 * Returns current language direction
	 *
	 * @return string current language direction
	 */
	public function getCurrentLanguageDirection()
	{
		return $this->supportedLocales[$this->getCurrentLanguage()]['dir'];
	}

	/**
	 * Returns current language script
	 *
	 * @return string current language script
	 */
	public function getCurrentLanguageScript()
	{
		return $this->supportedLocales[$this->getCurrentLanguage()]['script'];
	}

    /**
     * Returns current language's native reading
     *
     * @return string current language's native reading
     */
    public function getCurrentLanguageNativeReading()
    {
        return $this->supportedLocales[$this->getCurrentLanguage()]['native'];
    }

	/**
	 * Returns the class name of the language bar
     *
	 * @return string Language bar class name
	 */
	public function getLanguageBarClassName()
	{
		return $this->configRepository->get('laravel-localization::languageBarClass');
	}

	/**
	 * Returns if the current language should be printed in the language bar
     *
	 * @return boolean Should the current language be printed?
	 */
	public function getPrintCurrentLanguage()
	{
		return $this->configRepository->get('laravel-localization::printCurrentLanguageInBar');
	}

	/**
	 * Returns current language
     *
	 * @return string current language
	 */
	public function getCurrentLanguage()
	{
		if($this->currentLanguage)
		{
			return $this->currentLanguage;
		}
		$locales = $this->getSupportedLocales();
		// get session language...
		if($this->configRepository->get('laravel-localization::useSessionLanguage') && Session::has('language'))
		{
			return Session::get('language');
		}
        // or get cookie language...
        else if($this->configRepository->get('laravel-localization::useCookieLanguage') &&
            Cookie::get('language') != null &&
            !empty($locales[Cookie::get('language')]))
        {
            return Cookie::get('language');
        }
		// or get browser language...
		else if($this->configRepository->get('laravel-localization::useBrowserLanguage'))
		{
			return $this->negotiateLanguage();
		}

		// or get application default language
		return $this->configRepository->get('app.locale');
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
	 * @param string $name  current route name
	 */
	public function setRouteName($name)
	{
		$this->routeName = $name;
	}

	/**
	 * Translate routes and save them to the translated routes array (used in the localize route filter)
     *
	 * @param  string $routeName key of the translated string
     *
	 * @return string            translated string
	 */
	public function transRoute($routeName)
	{
		$this->translatedRoutes[] = $routeName;
		return $this->translator->trans($routeName);
	}

	/**
	 * Returns the translation key for a given path
     *
	 * @param  string $path [description]
     *
	 * @return string       [description]
	 */
	public function getRouteNameFromAPath($path)
	{
		$path = str_replace(url(), "", $path);
		if($path[0] !== '/')
		{
			$path = '/' . $path;
		}
		$path = str_replace('/' . $this->currentLanguage . '/', '', $path);
	    $path = trim($path,"/");

	    foreach ($this->translatedRoutes as $route) {
	    	if($this->translator->trans($route) === $path)
	    	{
	    		return $route;
	    	}
	    }
	    return false;
	}

    /**
     * Negotiates language with the user's browser through the Accept-Language
     * HTTP header or the user's host address.  Language codes are generally in
     * the form "ll" for a language spoken in only one country, or "ll-CC" for a
     * language spoken in a particular country.  For example, U.S. English is
     * "en-US", while British English is "en-UK".  Portugese as spoken in
     * Portugal is "pt-PT", while Brazilian Portugese is "pt-BR".
     *
     * This function is based on negotiateLanguage from Pear HTTP2
     * http://pear.php.net/package/HTTP2/
     *
     * Quality factors in the Accept-Language: header are supported, e.g.:
     *      Accept-Language: en-UK;q=0.7, en-US;q=0.6, no, dk;q=0.8
     *
     * @return string  The negotiated language result or app.locale.
     */
    public function negotiateLanguage()
    {
        $default = $this->configRepository->get('app.locale');
        $supported = array();
        foreach ($this->configRepository->get('laravel-localization::languagesAllowed') as $lang) {
            $supported[strtolower($lang)] = $lang;
        }

        if (!count($supported)) {
            return $default;
        }

        if (Request::header('Accept-Language')) {
            $matches = array();
            $generic_matches = array();
            foreach (explode(',', Request::header('Accept-Language')) as $option) {
                $option = array_map('trim', explode(';', $option));

                $l = strtolower($option[0]);
                if (isset($option[1])) {
                    $q = (float) str_replace('q=', '', $option[1]);
                } else {
                    $q = null;
                    // Assign default low weight for generic values
                    if ($l == '*/*') {
                        $q = 0.01;
                    } elseif (substr($l, -1) == '*') {
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
                while (!empty($l_ops)) {
                    //The new generic option needs to be slightly less important than it's base
                    $q -= 0.001;
                    $op = implode('-', $l_ops);
                    if (empty($generic_matches[$op]) || $generic_matches[$op] > $q) {
                        $generic_matches[$op] = $q;
                    }
                    array_pop($l_ops);
                }
            }
            $matches = array_merge($generic_matches, $matches);

            arsort($matches, SORT_NUMERIC);

            foreach ($matches as $key => $q) {
                if (isset($supported[$key])) {
                    return $supported[$key];
                }
            }
            // If any (i.e. "*") is acceptable, return the first supported format
            if (isset($matches['*'])) {
                return array_shift($supported);
            }
        }

        if (Request::server('REMOTE_HOST')) {
            $lang = strtolower(end($h = explode('.', Request::server('REMOTE_HOST'))));
            if (isset($supported[$lang])) {
                return $supported[$lang];
            }
        }

        return $default;
    }

}

Route::filter('LaravelLocalizationRedirectFilter', function()
{
	global $app;
	$currentLocale = $app['laravellocalization']->getCurrentLanguage();
	$defaultLocale = $app['laravellocalization']->getDefault();
	$params = explode('/', Request::path());
	if(count($params) > 0){
        $localeCode = $params[0];
        $locales = $app['laravellocalization']->getSupportedLocales();

		if (!empty($locales[$localeCode]))
        {
            if ($localeCode === $defaultLocale && Config::get('laravel-localization::hideDefaultLanguageInRoute'))
            {
                return Redirect::to($app['laravellocalization']->getCleanRoute(), 302)->header('Vary','Accept-Language');
            }
        }
		else if ($currentLocale !== $defaultLocale || !Config::get('laravel-localization::hideDefaultLanguageInRoute'))
		{
			// If the current url does not contain any language
			// The system redirect the user to the very same url "languaged"
			// we use the current language to redirect him
			return Redirect::to($currentLocale.'/'.Request::path(), 302)->header('Vary','Accept-Language');
		}
	}
});

/**
 * 	This filter would set the translated route name
 */
Route::filter('LaravelLocalizationRoutes', function()
{
	global $app;
	$router = $app['router'];
	if(App::make('laravel-localization.4.1'))
	{
		// Laravel 4.1
		$routeName = $app['laravellocalization']->getRouteNameFromAPath($router->current()->uri());
	}
	else
	{
		// Laravel 4.0
		$routeName = $app['laravellocalization']->getRouteNameFromAPath($router->getCurrentRoute()->getPath());
	}
	$app['laravellocalization']->setRouteName($routeName);
	return;
});
