<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Config\Repository;
use Illuminate\View\Environment;
use Request;
use Session;
use App;
use View;

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
     * Creates new instance.
     *
     * @param \Illuminate\Config\Repository $configRepository
     */
    public function __construct(Repository $configRepository, Environment $view)
    {
        $this->configRepository = $configRepository;
        $this->view = $view;
    }

	/**
	 * Set and return current language
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
			if(Session::has('language'))
			{
				App::setLocale(Session::get('language'));
				$this->configRepository->set('application.language',  Session::get('language'));
			}
			else
			{
				//take browser language
				if($this->configRepository->get('laravel-localization::useBrowserLanguage') &&
						isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && 
						in_array(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2), $languages))
					$locale_app = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
				else
					$locale_app = $this->configRepository->get('app.locale');

				App::setLocale($locale_app);
				Session::put('language', $locale_app);
				$this->configRepository->set('application.language',  $locale_app);
			}
		}
		return $locale;
	}

	/**
	 * Returns html with language selector
	 * @param  boolean $abbr Should languages be abbreviate (2 characters) or full named?
	 * @return [type]        [description]
	 */
	public function getLanguageBar($abbr = false)
	{
		$languages = array();
		if($abbr)
		{
			foreach ($this->configRepository->get('laravel-localization::languagesAllowed') as $lang)
				$languages[$lang] = strtoupper($lang);
		}
		else
		{
			$languages = array_intersect_key($this->configRepository->get('laravel-localization::supportedLanguages'),
							array_flip($this->configRepository->get('laravel-localization::languagesAllowed')));

		}
		$active = $this->configRepository->get('application.language');
		$urls = array();
		foreach ($this->configRepository->get('laravel-localization::languagesAllowed') as $lang)
			$urls[$lang] = $this->getURLLanguage(Request::url(),$lang);
		return $this->view->make('laravel-localization::languagebar', compact('languages','active','urls'));
	}

	/**
	 * Returns an URL adapted to $language language
	 * @param  String $route    URL to adapt
	 * @param  String $language Language to adapt
	 * @return String           URL translated
	 */
	public function getURLLanguage($route,$language)
	{
		return str_replace(url(), url($language), $this->getCleanRoute($route));
	}

	/**
	 * It returns an URL without language (if it has it)
	 * @param  String $route URL to clean
	 * @return String        Clean URL
	 */
	private function getCleanRoute($route)
	{
		return str_replace("/".$this->configRepository->get('application.language')."/","/",$route);
	}
}