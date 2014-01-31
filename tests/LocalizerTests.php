<?php

class LocalizerTests extends \Orchestra\Testbench\TestCase {

    protected function getPackageProviders()
    {
        return array('Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider');
    }

    protected function getPackageAliases()
    {
        return array(
            'LaravelLocalization'   => 'Mcamara\LaravelLocalization\Facades\LaravelLocalization'
        );
    }

    public function setUp()
    {
        parent::setUp();

        //$this->app['router']->enableFilters();
    }

    /**
     * Define environment setup.
     *
     * @param Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('laravel-localization::supportedLocales',
            array(
                "en" => array("name" => "English",		"script" => "Latin",		"dir" => "ltr",		"native" => "English"),
                "es" => array("name" => "Spanish",		"script" => "Latin",		"dir" => "ltr",		"native" => "Español"),
            )
        );
        $app['config']->set('laravel-localization::useAcceptLanguageHeader', true);
        $app['config']->set('laravel-localization::useSessionLocale', true);
        $app['config']->set('laravel-localization::useCookieLocale', true);
        $app['config']->set('laravel-localization::hideDefaultLocaleInURL', false);

        $app['router']->get('/hello', function () {
            //This needs to be in here for cookie, header, and session testing.
            LaravelLocalization::setLocale();
            return 'hello world';
        });
    }

    public function testDefaultsAndSetLocale()
    {
        $this->assertEquals('es', LaravelLocalization::setLocale('es'));
        $this->assertNull(LaravelLocalization::setLocale('ja'));
        $this->assertEquals('es', LaravelLocalization::getCurrentLocale());


        $this->assertEquals('en', LaravelLocalization::setLocale('en'));
        $this->assertEquals('en', LaravelLocalization::getCurrentLocale());

        LaravelLocalization::setLocale('es');
        $this->assertEquals('es', LaravelLocalization::getCurrentLocale());
    }

    public function testAcceptLanguageDetection1()
    {
        $crawler = $this->call('GET', '/hello', array(), array(), array("HTTP_ACCEPT_LANGUAGE"=>"de,es,en"));

        $this->assertResponseOk();
        $this->assertEquals('hello world', $crawler->getContent());
        $this->assertEquals('es', LaravelLocalization::getCurrentLocale());
        $this->assertNotEquals('en', LaravelLocalization::getCurrentLocale());
        $this->assertNotEquals('de', LaravelLocalization::getCurrentLocale());
    }

    public function testAcceptLanguageDetection2()
    {
        $crawler = $this->call('GET', '/hello', array(), array(), array("HTTP_ACCEPT_LANGUAGE"=>"en,de,es"));

        $this->assertResponseOk();
        $this->assertEquals('hello world', $crawler->getContent());
        $this->assertEquals('en', LaravelLocalization::getCurrentLocale());
        $this->assertNotEquals('es', LaravelLocalization::getCurrentLocale());
        $this->assertNotEquals('de', LaravelLocalization::getCurrentLocale());
    }
}
