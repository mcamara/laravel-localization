<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Config\Repository;
use Illuminate\View\Environment;
use Illuminate\Translation\Translator;
use Request;
use Session;
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
	 * @param  string $locale	Language to set the App to (optional)
     *
	 * @return string 			Returns language (if route has any) or null (if route has not a language)
	 */
	public function setLanguage($locale = null)
	{
		$languages = $this->configRepository->get('laravel-localization::languagesAllowed');
		if(is_null($locale) || !is_string($locale))
		{
			// If the locale has not been passed through the function
			// it tries to get it from the first segment of the url
			$locale = Request::segment(1);
		}
		
		if(in_array($locale, $languages))
		{
			$this->currentLanguage = $locale;
		}
		else
		{
			// if the first segment/language passed is not valid
			// the system would ask which language have to take
			// it could be taken by session, browser or app default
			// depending on your configuration
			$locale = null;
			$this->currentLanguage = $this->getCurrentLanguage();
		}
		App::setLocale($this->currentLanguage);
		$this->configRepository->set('application.language',  $this->currentLanguage);
		if($this->configRepository->get('laravel-localization::useSessionLanguage'))
		{
			Session::put('language', $this->currentLanguage);
		}

		return $locale;
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
		if($abbr)
		{
			foreach ($this->configRepository->get('laravel-localization::languagesAllowed') as $lang)
			{
				$languages[$lang] = strtoupper($lang);
			}
		}
		else
		{
			$languages = $this->getAllowedLanguages(false);

		}
		$active = $this->currentLanguage;
		$urls = array();
		foreach ($this->configRepository->get('laravel-localization::languagesAllowed') as $lang)
		{
			$langUrl = $this->getURLLanguage($lang);

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
     * @param  string $language Language to adapt
     * @param  string $route    URL to adapt, if false, current url would be taken
     *
     * @return string           URL translated
     */
    public function getURLLanguage($language, $route = null)
    {
        if(!in_array($language, $this->configRepository->get('laravel-localization::languagesAllowed')))
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
        		return $this->getURLFromRouteNameTranslated($language);
        	}
			$route = Request::url();
        }
        return str_replace(url(), url($language), $this->getCleanRoute($route));
    }


	/**
	 * Returns an URL adapted to the route name and the language given
     *
	 * @param  string $language 		Language to adapt
	 * @param  string $transKeyName  	Translation key name of the url to adapt
	 * @param  array $attributes  			Attributes for the route (only needed if transKeyName needs them)
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

			$route = url($language."/".$translation);
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
	 * @return string        Clean URL
	 */
	public function getCleanRoute($route = null)
	{
		if(!isset($route)) $route = Request::url();
		if(substr($route, -1) !== "/") $route .= "/";
		$cleanRoute = str_replace("/".$this->currentLanguage."/","/",$route);
		return rtrim($cleanRoute, "/");
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
	 */
	public function getAllowedLanguages($abbr = true)
	{
		if($abbr)
		{
			return $this->configRepository->get('laravel-localization::languagesAllowed');
		}
		else
		{
			$languages = array_intersect_key($this->configRepository->get('laravel-localization::supportedLanguages'),
							array_flip($this->configRepository->get('laravel-localization::languagesAllowed')));
			return $languages;
		}
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
		$languages = $this->configRepository->get('laravel-localization::languagesAllowed');
		// get session language...
		if($this->configRepository->get('laravel-localization::useSessionLanguage') && Session::has('language'))
		{
			return Session::get('language');
		}
		// or get browser language...
		else if($this->configRepository->get('laravel-localization::useBrowserLanguage') &&
					isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) &&
					in_array(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2), $languages))
		{
			return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}
		// or get application default language
		else
		{
			return $this->configRepository->get('app.locale');
		}
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

}

Route::filter('LaravelLocalizationRedirectFilter', function()
{
	global $app;
	$currentLanguage = $app['laravellocalization']->getCurrentLanguage();
	$defaultLanguage = $app['laravellocalization']->getDefault();
	$params = explode('/', Request::path());
	if(count($params) > 0){
		$language = $params[0];
		$languages = Config::get('laravel-localization::languagesAllowed');

		if(in_array($language, $languages) && $language === $defaultLanguage &&
			Config::get('laravel-localization::hideDefaultLanguageInRoute') )
		{
			return Redirect::to($app['laravellocalization']->getCleanRoute(), 301);
		}

		if(!in_array($language, $languages) && 
			($currentLanguage !== $defaultLanguage || !Config::get('laravel-localization::hideDefaultLanguageInRoute'))
		)
		{
			// If the current url does not contain any language
			// The system redirect the user to the very same url "languaged"
			// we use the current language to redirect him
			return Redirect::to($currentLanguage.'/'.Request::path(), 301);
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
