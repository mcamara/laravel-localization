<?php

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;

class LocalizerTests extends \Orchestra\Testbench\TestCase {

    protected $test_url  = 'http://localhost/';
    protected $test_url2 = 'http://localhost';

    protected $supportedLocales = [ ];

    protected $defaultLocale = "en";

    protected function getPackageProviders( $app )
    {
        return [
            'Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider'
        ];
    }

    protected function getPackageAliases( $app )
    {
        return [
            'LaravelLocalization' => 'Mcamara\LaravelLocalization\Facades\LaravelLocalization'
        ];
    }

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Set routes for testing
     * @param bool|string $locale
     */
    protected function setRoutes( $locale = false )
    {
        if ( $locale )
        {
            app('laravellocalization')->setLocale($locale);
        }

        app('router')->group([
            'prefix'     => app('laravellocalization')->setLocale(),
            'middleware' => [
                'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes',
                'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter'
            ]
        ], function ()
        {
            app('router')->get('/', [ 'as'=>'index', function ()
            {
                return app('translator')->get('LaravelLocalization::routes.hello');
            }]);

            app('router')->get('test', [ 'as'=>'test', function ()
            {
                return app('translator')->get('LaravelLocalization::routes.test_text');
            }]);

            app('router')->get(app('laravellocalization')->transRoute('LaravelLocalization::routes.about'), [ 'as'=>'about', function ()
            {
                return app('laravellocalization')->getLocalizedURL('es') ? : "Not url available";
            }]);

            app('router')->get(app('laravellocalization')->transRoute('LaravelLocalization::routes.view'), [ 'as'=>'view', function ()
            {
                return app('laravellocalization')->getLocalizedURL('es') ? : "Not url available";
            }]);

            app('router')->get(app('laravellocalization')->transRoute('LaravelLocalization::routes.view_project'), [ 'as'=>'view_project', function ()
            {
                return app('laravellocalization')->getLocalizedURL('es') ? : "Not url available";
            }]);
        });
    }

    /**
     * Refresh routes and refresh application
     * @param bool|string $locale
     */
    protected function refreshApplication( $locale = false )
    {
        parent::refreshApplication();
        $this->setRoutes($locale);
    }

    /**
     * Define environment setup.
     *
     * @param Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp( $app )
    {
        app('config')->set('app.url', $this->test_url);

        app('config')->set('app.locale', $this->defaultLocale);

        $packageConfigFile = __DIR__ . '/../src/config/config.php';
        $config = app('files')->getRequire($packageConfigFile);

        app('config')->set('laravellocalization', $config);

        $this->supportedLocales = app('config')->get('laravellocalization.supportedLocales');

        app('translator')->getLoader()->addNamespace('LaravelLocalization', realpath(dirname(__FILE__)) . "/lang");

        app('translator')->load('LaravelLocalization', 'routes', 'es');
        app('translator')->load('LaravelLocalization', 'routes', 'en');

        app('laravellocalization')->setBaseUrl($this->test_url);

        $this->setRoutes();

    }

    public function testSetLocale()
    {
        $this->assertEquals(route('about'), "http://localhost/about");

        $this->refreshApplication('es');
        $this->assertEquals('es', app('laravellocalization')->setLocale('es'));
        $this->assertEquals('es', app('laravellocalization')->getCurrentLocale());
        $this->assertEquals(route('about'), "http://localhost/acerca");

        $this->refreshApplication();

        $this->assertEquals('en', app('laravellocalization')->setLocale('en'));

        $this->assertEquals(route('about'), "http://localhost/about");

        $this->assertNull(app('laravellocalization')->setLocale('de'));
        $this->assertEquals('en', app('laravellocalization')->getCurrentLocale());

    }

    public function testLocalizeURL()
    {
        $this->assertEquals(
            $this->test_url . app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->localizeURL()
        );

        // Missing trailing slash in a URL
        $this->assertEquals(
            $this->test_url2 . '/' . app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->localizeURL()
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        // testing hide default locale option
        $this->assertNotEquals(
            $this->test_url . app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->localizeURL()
        );

        $this->assertEquals(
            $this->test_url,
            app('laravellocalization')->localizeURL()
        );

        app('laravellocalization')->setLocale('es');

        $this->assertEquals(
            $this->test_url . 'es',
            app('laravellocalization')->localizeURL()
        );

        $this->assertEquals(
            $this->test_url . 'about',
            app('laravellocalization')->localizeURL($this->test_url . 'about', 'en')
        );

        $this->assertNotEquals(
            $this->test_url . 'en/about',
            app('laravellocalization')->localizeURL($this->test_url . 'about', 'en')
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', false);

        $this->assertEquals(
            $this->test_url . 'en/about',
            app('laravellocalization')->localizeURL($this->test_url . 'about', 'en')
        );

        $this->assertNotEquals(
            $this->test_url . 'about',
            app('laravellocalization')->localizeURL($this->test_url . 'about', 'en')
        );


    }

    public function testGetLocalizedURL()
    {
        $this->assertEquals(
            $this->test_url . 'es/acerca',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url . 'en/about')
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url . 'view/1')
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1/proyecto',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url . 'view/1/project')
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1/proyecto/1',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url . 'view/1/project/1')
        );

        $this->assertEquals(
            $this->test_url . 'en/about',
            app('laravellocalization')->getLocalizedURL('en', $this->test_url . 'about')
        );

        $this->assertEquals(
            $this->test_url . app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);
        // testing default language hidden

        $this->assertEquals(
            $this->test_url . 'es/acerca',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url . 'about')
        );
        $this->assertEquals(
            $this->test_url . 'about',
            app('laravellocalization')->getLocalizedURL('en', $this->test_url . 'about')
        );

        $this->assertEquals(
            $this->test_url,
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertNotEquals(
            $this->test_url . app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        app('laravellocalization')->setLocale('es');

        $this->assertNotEquals(
            $this->test_url,
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertNotEquals(
            $this->test_url . app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertEquals(
            $this->test_url . app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertEquals(
            $this->test_url . 'es/acerca',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url . 'about')
        );

        app('laravellocalization')->setLocale('en');

        $crawler = $this->call(
            'GET',
            $this->test_url . "about",
            [ ],
            [ ],
            [ ],
            [ "HTTP_ACCEPT_LANGUAGE" => "en,es" ]
        );

        $this->assertResponseOk();
        $this->assertEquals(
            $this->test_url . "es/acerca",
            $crawler->getContent()
        );

        $this->refreshApplication();

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        $this->assertEquals(
            $this->test_url . 'test',
            app('laravellocalization')->getLocalizedURL('en', $this->test_url . 'test')
        );

        $crawler = $this->call(
            'GET',
            app('laravellocalization')->getLocalizedURL('en', $this->test_url . 'test'),
            [ ],
            [ ],
            [ ],
            [ "HTTP_ACCEPT_LANGUAGE" => "en,es" ]
        );

        $this->assertResponseOk();
        $this->assertEquals(
            "Test text",
            $crawler->getContent()
        );

        $this->refreshApplication('es');

        $this->assertEquals(
            $this->test_url . 'es/test',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url . 'test')
        );

    }

    public function testGetURLFromRouteNameTranslated()
    {
        $this->assertEquals(
            $this->test_url . 'es/acerca',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url . 'en/about',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url . 'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', [ 'id' => 1 ])
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        $this->assertEquals(
            $this->test_url . 'about',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url . 'es/acerca',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.view', [ 'id' => 1 ])
        );

        $this->assertEquals(
            $this->test_url . 'view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', [ 'id' => 1 ])
        );

        $this->assertNotEquals(
            $this->test_url . 'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', [ 'id' => 1 ])
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', false);

        $this->assertNotEquals(
            $this->test_url . 'view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', [ 'id' => 1 ])
        );

        $this->assertEquals(
            $this->test_url . 'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', [ 'id' => 1 ])
        );

    }

    public function testGetNonLocalizedURL()
    {
        $this->assertEquals(
            $this->test_url,
            app('laravellocalization')->getNonLocalizedURL($this->test_url . 'en')
        );
        $this->assertEquals(
            $this->test_url,
            app('laravellocalization')->getNonLocalizedURL($this->test_url . 'es')
        );
        $this->assertEquals(
            $this->test_url . 'view/1',
            app('laravellocalization')->getNonLocalizedURL($this->test_url . 'en/view/1')
        );
        $this->assertEquals(
            $this->test_url . 'ver/1',
            app('laravellocalization')->getNonLocalizedURL($this->test_url . 'es/ver/1')
        );

    }

    public function testGetDefaultLocale()
    {
        $this->assertEquals(
            'en',
            app('laravellocalization')->getDefaultLocale()
        );

        app('laravellocalization')->setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'en',
            app('laravellocalization')->getDefaultLocale()
        );

    }

    public function testGetSupportedLocales()
    {
        $this->assertEquals(
            $this->supportedLocales,
            app('laravellocalization')->getSupportedLocales()
        );

    }

    public function testGetCurrentLocaleName()
    {
        $this->assertEquals(
            'English',
            app('laravellocalization')->getCurrentLocaleName()
        );

        $this->refreshApplication('es');

        $this->assertEquals(
            'Spanish',
            app('laravellocalization')->getCurrentLocaleName()
        );
    }

    public function testGetCurrentLocaleDirection()
    {
        $this->assertEquals(
            'ltr',
            app('laravellocalization')->getCurrentLocaleDirection()
        );

        app('laravellocalization')->setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'ltr',
            app('laravellocalization')->getCurrentLocaleDirection()
        );
    }

    public function testGetCurrentLocaleScript()
    {
        app('laravellocalization')->setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'Latn',
            app('laravellocalization')->getCurrentLocaleScript()
        );

        app('laravellocalization')->setLocale('en');
        $this->refreshApplication('en');

        $this->assertEquals(
            'Latn',
            app('laravellocalization')->getCurrentLocaleScript()
        );
    }

    public function testGetCurrentLocaleNativeReading()
    {
        $this->assertEquals(
            'English',
            app('laravellocalization')->getCurrentLocaleNativeReading()
        );

        app('laravellocalization')->setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'español',
            app('laravellocalization')->getCurrentLocaleNativeReading()
        );
    }

    public function testGetCurrentLocale()
    {
        $this->assertEquals(
            'en',
            app('laravellocalization')->getCurrentLocale()
        );

        app('laravellocalization')->setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'es',
            app('laravellocalization')->getCurrentLocale()
        );

        $this->assertNotEquals(
            'en',
            app('laravellocalization')->getCurrentLocale()
        );
    }

    public function testGetSupportedLanguagesKeys()
    {
        $this->assertEquals(
            [ 'en', 'es' ],
            app('laravellocalization')->getSupportedLanguagesKeys()
        );
    }

    public function testGetConfigRepository()
    {
        $this->assertEquals(
            app('config'),
            app('laravellocalization')->getConfigRepository('/view/1')
        );
    }

    public function testCreateUrlFromUri()
    {
        $this->assertEquals(
            'http://localhost/view/1',
            app('laravellocalization')->createUrlFromUri('/view/1')
        );

        app('laravellocalization')->setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'http://localhost/ver/1',
            app('laravellocalization')->createUrlFromUri('/ver/1')
        );
    }


}
