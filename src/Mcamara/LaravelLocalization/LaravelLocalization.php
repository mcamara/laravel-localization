<?php namespace Mcamara\LaravelLocalization;

use Illuminate\Config\Repository;
use Request;
use Session;
use App;

class LaravelLocalization 
{
    /**
     * Config repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $configRepository;

    /**
     * Creates new instance.
     *
     * @param \Illuminate\Config\Repository $configRepository
     */
    public function __construct(Repository $configRepository)
    {
        $this->configRepository = $configRepository;
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
					$locale_app = $this->configRepository->get('application.locale');

				App::setLocale($locale_app);
				Session::put('language', $locale_app);
				$this->configRepository->set('application.language',  $locale_app);
			}
		}
		return $locale;
	}
}