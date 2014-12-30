<?php

class LocalizerTests extends \Orchestra\Testbench\TestCase {

    protected $test_url = 'http://localhost/';

    protected $supportedLocales = [
        "en"    =>  [   "name" => "English",  "script" => "Latin",  "dir" => "ltr", "native" => "English"  ],
        "es"    =>  [   "name" => "Spanish",  "script" => "Latin",  "dir" => "ltr", "native" => "Español"  ]
    ];

    protected $defaultLocale = "en";

    protected function getPackageProviders()
    {
        return [
            'Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider'
        ];
    }

    protected function getPackageAliases()
    {
        return [
            'LaravelLocalization'   => 'Mcamara\LaravelLocalization\Facades\LaravelLocalization'
        ];
    }

    public function setUp()
    {
        parent::setUp();

        //Route::enableFilters();
    }

    /**
     * Set routes for testing
     * 
     */ 
    protected function setRoutes($locale = false)
    {
        Route::group([
            'prefix' => LaravelLocalization::setLocale($locale),
            'before' => 'LaravelLocalizationRoutes|LaravelLocalizationTestFilter' // Route translate filter
        ], function() {
            Route::get('/', function () {
                return Lang::get('LaravelLocalization::routes.hello');
            });

            Route::get('test', function () {
                return Lang::get('LaravelLocalization::routes.test_text');
            });

            Route::get(LaravelLocalization::transRoute('LaravelLocalization::routes.about'), function () {
                return LaravelLocalization::getLocalizedURL('es') ?: "Not url available";
            });

            Route::get(LaravelLocalization::transRoute('LaravelLocalization::routes.view'), function () {
                return LaravelLocalization::getLocalizedURL('es') ?: "Not url available";
            });

            Route::get(LaravelLocalization::transRoute('LaravelLocalization::routes.view_project'), function () {
                return LaravelLocalization::getLocalizedURL('es') ?: "Not url available";
            });
        });
        Route::enableFilters();
    }

    /**
     * Refresh routes and refresh application
     */ 
    protected function refreshApplication( $locale = false )
    {
        parent::refreshApplication();

        if(!empty($this->app))
        {
            $this->setRoutes($locale);
        }
    }

    /**
     * Define environment setup.
     *
     * @param Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        Config::set('app.url', $this->test_url );

        Config::set('app.locale', $this->defaultLocale );

        Config::package('mcamara/laravel-localization', __DIR__.'/../config');

        Config::set('laravel-localization::supportedLocales', $this->supportedLocales );

        Config::set('laravel-localization::useAcceptLanguageHeader', true);

        Config::set('laravel-localization::useSessionLocale', true);

        Config::set('laravel-localization::useCookieLocale', true);

        Config::set('laravel-localization::hideDefaultLocaleInURL', false);

        Lang::getLoader()->addNamespace('LaravelLocalization', realpath(dirname(__FILE__)) . "/lang");

        Lang::load( 'LaravelLocalization' , 'routes' , 'es' );
        Lang::load( 'LaravelLocalization' , 'routes' , 'en' );

        LaravelLocalization::setBaseUrl($this->test_url);
        LaravelLocalization::getCurrentLocale($this->supportedLocales);

        $this->setRoutes();
    }

    public function testSetLocale()
    {
        $this->assertEquals('es', LaravelLocalization::setLocale('es'));
        $this->assertEquals('es', LaravelLocalization::getCurrentLocale());

        $crawler = $this->call('GET', '/', [], [], [ "HTTP_ACCEPT_LANGUAGE"  =>   "en,es" ]);

        $this->assertResponseOk();
        $this->assertEquals('Hola mundo', $crawler->getContent());
        $this->refreshApplication();


        $this->assertEquals('en', LaravelLocalization::setLocale('en'));
        $this->assertNotEquals('es', LaravelLocalization::getCurrentLocale());
        $this->assertEquals('en', LaravelLocalization::getCurrentLocale());


        $crawler = $this->call('GET', '/', [], [], [ "HTTP_ACCEPT_LANGUAGE"  =>   "en,es" ]);

        $this->assertResponseOk();
        $this->assertEquals('Hello world', $crawler->getContent());
        $this->refreshApplication();

        $this->assertNull(LaravelLocalization::setLocale('de'));
        $this->assertEquals('en', LaravelLocalization::getCurrentLocale());

    }

    public function testLocalizeURL()
    {
        $this->assertEquals(
            $this->test_url . LaravelLocalization::getCurrentLocale() ,
            LaravelLocalization::localizeURL()
        );

        $this->app['config']->set('laravel-localization::hideDefaultLocaleInURL', true);

        // testing hide default locale option
        $this->assertNotEquals(
            $this->test_url . LaravelLocalization::getDefaultLocale() ,
            LaravelLocalization::localizeURL()
        );

        $this->assertEquals(
            $this->test_url ,
            LaravelLocalization::localizeURL()
        );

        LaravelLocalization::setLocale('es');

        $this->assertEquals(
            $this->test_url . 'es' ,
            LaravelLocalization::localizeURL()
        );

        $this->assertEquals(
            $this->test_url . 'about',
            LaravelLocalization::localizeURL($this->test_url . 'about' , 'en')
        );

        $this->assertNotEquals(
            $this->test_url . 'en/about',
            LaravelLocalization::localizeURL($this->test_url . 'about' , 'en')
        );

        $this->app['config']->set('laravel-localization::hideDefaultLocaleInURL', false);

        $this->assertEquals(
            $this->test_url . 'en/about',
            LaravelLocalization::localizeURL($this->test_url . 'about' , 'en')
        );

        $this->assertNotEquals(
            $this->test_url . 'about',
            LaravelLocalization::localizeURL($this->test_url . 'about' , 'en')
        );


    }

    public function testGetLocalizedURL()
    {
        $this->assertEquals(
            $this->test_url . 'es/acerca' ,
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'en/about' )
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1' ,
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'view/1' )
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1/proyecto' ,
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'view/1/project' )
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1/proyecto/1' ,
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'view/1/project/1' )
        );

        $this->assertEquals(
            $this->test_url . 'en/about' ,
            LaravelLocalization::getLocalizedURL( 'en' , $this->test_url . 'about' )
        );

        $this->assertEquals(
            $this->test_url . LaravelLocalization::getCurrentLocale() ,
            LaravelLocalization::getLocalizedURL()
        );

        $this->app['config']->set('laravel-localization::hideDefaultLocaleInURL', true);
        // testing default language hidden

        $this->assertEquals(
            $this->test_url . 'es/acerca' ,
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'about' )
        );
        $this->assertEquals(
            $this->test_url . 'about' ,
            LaravelLocalization::getLocalizedURL( 'en' , $this->test_url . 'about' )
        );

        $this->assertEquals(
            $this->test_url ,
            LaravelLocalization::getLocalizedURL()
        );

        $this->assertNotEquals(
            $this->test_url . LaravelLocalization::getDefaultLocale() ,
            LaravelLocalization::getLocalizedURL()
        );

        LaravelLocalization::setLocale('es');

        $this->assertNotEquals(
            $this->test_url ,
            LaravelLocalization::getLocalizedURL()
        );

        $this->assertNotEquals(
            $this->test_url . LaravelLocalization::getDefaultLocale() ,
            LaravelLocalization::getLocalizedURL()
        );

        $this->assertEquals(
            $this->test_url . LaravelLocalization::getCurrentLocale() ,
            LaravelLocalization::getLocalizedURL()
        );

        $this->assertEquals(
            $this->test_url . 'es/acerca' ,
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'about' )
        );
        
        LaravelLocalization::setLocale('en');

        $crawler = $this->call(
            'GET', 
            $this->test_url . "about", 
            [], 
            [], 
            [ "HTTP_ACCEPT_LANGUAGE"  =>   "en,es" ]
        );

        $this->assertResponseOk();
        $this->assertEquals(
            $this->test_url . "es/acerca" , 
            $crawler->getContent()
        );
        $this->refreshApplication();

        $this->app['config']->set('laravel-localization::hideDefaultLocaleInURL', true);

        $this->assertEquals(
            $this->test_url . 'test', 
            LaravelLocalization::getLocalizedURL( 'en' , $this->test_url . 'test' )
        );

        $crawler = $this->call(
            'GET', 
            LaravelLocalization::getLocalizedURL( 'en' , $this->test_url . 'test' ), 
            [], 
            [], 
            [ "HTTP_ACCEPT_LANGUAGE"  =>   "en,es" ]
        );

        $this->assertResponseOk();
        $this->assertEquals(
            "Test text" , 
            $crawler->getContent()
        );

        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            $this->test_url . 'es/test', 
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'test' )
        );

        $crawler = $this->call(
            'GET', 
            LaravelLocalization::getLocalizedURL( 'es' , $this->test_url . 'test' )
        );

        $this->assertResponseOk();
        $this->assertEquals(
            "Texto de prueba" , 
            $crawler->getContent()
        );
        $this->refreshApplication();
    }

    public function testGetURLFromRouteNameTranslated()
    {

        $crawler = $this->call(
            'GET', 
            $this->test_url . "/about", 
            [], 
            [], 
            [ "HTTP_ACCEPT_LANGUAGE"  =>   "en,es" ]
        );

        $this->assertResponseOk();
        $this->assertEquals(
            $this->test_url . "es/acerca" , 
            $crawler->getContent()
        );
        $this->refreshApplication();

        $this->assertEquals(
            $this->test_url . 'es/acerca' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'es' , 'LaravelLocalization::routes.about' )
        );

        $this->assertEquals(
            $this->test_url . 'en/about' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'en' , 'LaravelLocalization::routes.about' )
        );

        $this->assertEquals(
            $this->test_url . 'en/view/1' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'en' , 'LaravelLocalization::routes.view' , ['id' => 1] )
        );

        $this->app['config']->set('laravel-localization::hideDefaultLocaleInURL', true);

        $this->assertEquals(
            $this->test_url . 'about' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'en' , 'LaravelLocalization::routes.about' )
        );
        
        $this->assertEquals(
            $this->test_url . 'es/acerca' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'es' , 'LaravelLocalization::routes.about' )
        );

        $this->assertEquals(
            $this->test_url . 'es/ver/1' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'es' , 'LaravelLocalization::routes.view' , ['id' => 1] )
        );

        $this->assertEquals(
            $this->test_url . 'view/1' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'en' , 'LaravelLocalization::routes.view' , ['id' => 1] )
        );

        $this->assertNotEquals(
            $this->test_url . 'en/view/1' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'en' , 'LaravelLocalization::routes.view' , ['id' => 1] )
        );

        $this->app['config']->set('laravel-localization::hideDefaultLocaleInURL', false);

        $this->assertNotEquals(
            $this->test_url . 'view/1' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'en' , 'LaravelLocalization::routes.view' , ['id' => 1] )
        );

        $this->assertEquals(
            $this->test_url . 'en/view/1' ,
            LaravelLocalization::getURLFromRouteNameTranslated( 'en' , 'LaravelLocalization::routes.view' , ['id' => 1] )
        );

    }

    public function testGetNonLocalizedURL()
    {
        $this->assertEquals(
            $this->test_url ,
            LaravelLocalization::getNonLocalizedURL( $this->test_url . 'en'  )
        );
        $this->assertEquals(
            $this->test_url ,
            LaravelLocalization::getNonLocalizedURL( $this->test_url . 'es'  )
        );
        $this->assertEquals(
            $this->test_url . 'view/1' ,
            LaravelLocalization::getNonLocalizedURL( $this->test_url . 'en/view/1'  )
        );
        $this->assertEquals(
            $this->test_url . 'ver/1' ,
            LaravelLocalization::getNonLocalizedURL( $this->test_url . 'es/ver/1'  )
        );

    }

    public function testGetDefaultLocale()
    {
        $this->assertEquals(
            'en' ,
            LaravelLocalization::getDefaultLocale()
        );

        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');
        
        $this->assertEquals(
            'en' ,
            LaravelLocalization::getDefaultLocale()
        );

    }

    public function testGetSupportedLocales()
    {
        $this->assertEquals(
            $this->supportedLocales ,
            LaravelLocalization::getSupportedLocales()
        );

    }

    public function testGetCurrentLocaleName()
    {
        $this->assertEquals(
            'English',
            LaravelLocalization::getCurrentLocaleName()
        );

        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');
        
        $this->assertEquals(
            'Spanish' ,
            LaravelLocalization::getCurrentLocaleName()
        );
    }

    public function testGetCurrentLocaleDirection()
    {
        $this->assertEquals(
            'ltr',
            LaravelLocalization::getCurrentLocaleDirection()
        );

        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');
        
        $this->assertEquals(
            'ltr' ,
            LaravelLocalization::getCurrentLocaleDirection()
        );
    }

    public function testGetCurrentLocaleScript()
    {
        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'Latin',
            LaravelLocalization::getCurrentLocaleScript()
        );

        LaravelLocalization::setLocale('en');
        $this->refreshApplication('en');
        
        $this->assertEquals(
            'Latin' ,
            LaravelLocalization::getCurrentLocaleScript()
        );
    }

    public function testGetCurrentLocaleNativeReading()
    {
        $this->assertEquals(
            'English',
            LaravelLocalization::getCurrentLocaleNativeReading()
        );

        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');
        
        $this->assertEquals(
            'Español' ,
            LaravelLocalization::getCurrentLocaleNativeReading()
        );
    }

    public function testGetCurrentLocale()
    {
        $this->assertEquals(
            'en',
            LaravelLocalization::getCurrentLocale()
        );

        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');
        
        $this->assertEquals(
            'es' ,
            LaravelLocalization::getCurrentLocale()
        );
        
        $this->assertNotEquals(
            'en' ,
            LaravelLocalization::getCurrentLocale()
        );
    }

    public function testGetSupportedLanguagesKeys()
    {
        $this->assertEquals(
            ['en' , 'es'],
            LaravelLocalization::getSupportedLanguagesKeys()
        );
    }

    public function testGetConfigRepository()
    {
        $this->assertEquals(
            $this->app['config'],
            LaravelLocalization::getConfigRepository('/view/1')
        );
    }

    public function testCreateUrlFromUri()
    {
        $this->assertEquals(
            'http://localhost/view/1' ,
            LaravelLocalization::createUrlFromUri('/view/1')
        );

        LaravelLocalization::setLocale('es');
        $this->refreshApplication('es');

        $this->assertEquals(
            'http://localhost/ver/1' ,
            LaravelLocalization::createUrlFromUri('/ver/1')
        );
    }


}
