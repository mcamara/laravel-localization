<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Config\Repository;
use Illuminate\View\Environment;
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
     * @var Illuminate\View\Environment
     */
    protected $view;

    /**
     * Default language
     *
     * @var string
     */
    protected $defaultLanguage;

    /**
     * Creates new instance.
     *
     * @param \Illuminate\Config\Repository $configRepository
     */
    public function __construct(Repository $configRepository, Environment $view)
    {
        $this->configRepository = $configRepository;
        $this->view = $view;

        // set default language
        $this->defaultLanguage = Config::get('app.locale');
    }

	/**
	 * Set and return current language
	 * @return String 			Returns language (if route has any) or null (if route has not a language)
	 */
	public function setLanguage()
	{
		$languages = $this->configRepository->get('laravel-localization::languagesAllowed');
		$locale = Request::segment(1);
		if(in_array($locale, $languages)){
			App::setLocale($locale);
			Session::put('language', $locale);
			$this->configRepository->set('application.language',  $locale);
		}
		else
		{
			$locale = null;
			$locale_app = LaravelLocalization::getCurrentLanguage();
			App::setLocale($locale_app);
			$this->configRepository->set('application.language',  $locale_app);
			if($this->configRepository->get('laravel-localization::useSessionLanguage'))
			{
				Session::put('language', $locale_app);
			}
		}
		return $locale;
	}

	/**
	 * Returns html with language selector
	 * @param  boolean $abbr 	Should languages be abbreviate (2 characters) or full named?
	 * @return String 			Returns an html view with a language bar
	 */
	public function getLanguageBar($abbr = false)
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
			$languages = array_intersect_key($this->configRepository->get('laravel-localization::supportedLanguages'),
							array_flip($this->configRepository->get('laravel-localization::languagesAllowed')));

		}
		$active = $this->configRepository->get('application.language');
		$urls = array();
		foreach ($this->configRepository->get('laravel-localization::languagesAllowed') as $lang)
		{
			$urls[$lang] = $this->getURLLanguage($lang);
		}
		if($this->view->exists('mcamara/laravel-localization/languagebar'))
		{
			return $this->view->make('mcamara/laravel-localization/languagebar', compact('languages','active','urls'));
		}
		else
		{
			return $this->view->make('laravel-localization::languagebar', compact('languages','active','urls'));
		}
	}

	/**
	 * Returns an URL adapted to $language language
	 * @param  String $language Language to adapt
	 * @param  String $route    URL to adapt, if false, current url would be taken
	 * @return String           URL translated
	 */
	public function getURLLanguage($language,$route = false)
	{
		if(!in_array($language, $this->configRepository->get('laravel-localization::languagesAllowed')))
		{
			return false;
		}
		if(!$route)
		{
			$route = Request::url();
		}
		return str_replace(url(), url($language), $this->getCleanRoute($route));
	}

	/**
	 * It returns an URL without language (if it has it)
	 * @param  String $route URL to clean, if false, current url would be taken
	 * @return String        Clean URL
	 */
	public function getCleanRoute($route = false)
	{
		$cleanRoute = "";
		if(!$route) $route = Request::url();
		if(substr($route, -1) !== "/") $route .= "/";
		$cleanRoute = str_replace("/".$this->configRepository->get('application.language')."/","/",$route);
		return rtrim($cleanRoute, "/");
	}

	/**
	 * Appends i18n language segment to the URI
	 * @param  string $uri
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
	 * @return string
	 */
	public function getDefault()
	{
		return $this->defaultLanguage;
	}

	/**
	 * Returns all allowed languages
	 * @return array Array with all allowed languages
	 */
	public function getAllowedLanguages()
	{
		return $this->configRepository->get('laravel-localization::languagesAllowed');
	}

	/**
	 * Returns the class name of the language bar
	 * @return string Language bar class name
	 */
	public function getLanguageBarClassName()
	{
		return $this->configRepository->get('laravel-localization::languageBarClass');
	}

	/**
	 * Returns if the current language should be printed in the language bar
	 * @return boolean Should the current language be printed?
	 */
	public function getPrintCurrentLanguage()
	{
		return $this->configRepository->get('laravel-localization::printCurrentLanguageInBar');
	}

	/**
	 * Returns current language
	 * @return string current language
	 */
	public static function getCurrentLanguage()
	{
		$languages = Config::get('laravel-localization::languagesAllowed');
		// get session language...
		if(Config::get('laravel-localization::useSessionLanguage') && Session::has('language'))
		{
			return Session::get('language');
		}
		// or get browser language...
		else if(Config::get('laravel-localization::useBrowserLanguage') &&
					isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) &&
					in_array(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2), $languages))
		{
			return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}
		// or get application default language
		else
		{
			return Config::get('app.locale');
		}
	}

}

Route::filter('LaravelLocalizationRedirectFilter', function()
{
	$params = explode('/', Request::path());
	if(count($params) > 0){
		$language = $params[0];
		$languages = Config::get('laravel-localization::languagesAllowed');
		if(!in_array($language, $languages))
		{
			// If the current url does not contain any language
			// The system redirect the user to the very same url "languaged"
			// we use the current language to redirect him
			$default_language = LaravelLocalization::getCurrentLanguage();
			return Redirect::to($default_language.'/'.Request::path(), 301);
		}
	}
});