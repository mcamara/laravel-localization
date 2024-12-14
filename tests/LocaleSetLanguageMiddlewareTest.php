<?php

namespace Mcamara\LaravelLocalization\Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class LocaleSetLanguageMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('laravellocalization.supportedLocales', [
            'en' => ['name' => 'English', 'regional' => 'en_US'],
            'es' => ['name' => 'Spanish', 'regional' => 'es_ES'],
        ]);
        Config::set('app.locale', 'en');
        Config::set('laravellocalization.hideDefaultLocaleInURL', false);
        Config::set('laravellocalization.useAcceptLanguageHeader', true);
    }

    protected function getEnvironmentSetUp($app)
    {
        app('translator')->getLoader()->addNamespace('LaravelLocalization', realpath(dirname(__FILE__)).'/lang');

        app('translator')->load('LaravelLocalization', 'routes', 'es');
        app('translator')->load('LaravelLocalization', 'routes', 'en');
    }

    /** @test */
    public function it_sets_locale_based_on_url()
    {
        app('router')->localized(function () {
            app('router')->get('/about', function () {
                return __("LaravelLocalization::routes.hello");
            })->name('about');
        }, [\Mcamara\LaravelLocalization\Middleware\LocaleSetLanguage::class]);

        $this->get('/en/about')
            ->assertResponseOk()
            ->see('Hello world');

        $this->assertEquals('en', App::getLocale());

        // Test Spanish locale
        $this->get('/es/about')
            ->assertResponseOk()
            ->see('Hola mundo');

        $this->assertEquals('es', App::getLocale());
    }

    /** @test */
    public function it_falls_back_to_default_locale_if_no_locale_is_provided()
    {

        Config::set('laravellocalization.hideDefaultLocaleInURL', true);
        app('router')->localized(function () {
            app('router')->get('/about', function () {
                return __("LaravelLocalization::routes.hello");
            })->name('about');
        }, [\Mcamara\LaravelLocalization\Middleware\LocaleSetLanguage::class]);

        $this->get('/about')
            ->assertResponseOk()
            ->see('Hello world');

        $this->assertEquals('en', App::getLocale());
    }

    /** @test */
    public function it_throws_error_for_unsupported_locale()
    {
        app('router')->localized(function () {
            app('router')->get('/about', function () {
                return __("LaravelLocalization::routes.hello");
            })->name('about');
        }, [\Mcamara\LaravelLocalization\Middleware\LocaleSetLanguage::class]);

        // Access a route with an unsupported locale
        $this->get('/fr/about')
            ->assertResponseStatus(404); // Middleware should reject unsupported locales
    }

    /** @test */
    public function it_uses_browser_language_as_fallback_es()
    {
        Config::set('laravellocalization.useAcceptLanguageHeader', true);
        app('router')->localized(function () {
            app('router')->get('/about', function () {
                return __("LaravelLocalization::routes.hello");
            })->name('about');
        }, [\Mcamara\LaravelLocalization\Middleware\LocaleSetLanguage::class]);

        $this->get('/about', ['Accept-Language' => 'es'])
            ->assertResponseOk()
            ->see('Hola mundo');

        $this->assertEquals('es', App::getLocale());
    }

    /** @test */
    public function it_uses_browser_language_as_fallback_en()
    {
        Config::set('laravellocalization.useAcceptLanguageHeader', true);
        app('router')->localized(function () {
            app('router')->get('/about', function () {
                return __("LaravelLocalization::routes.hello");
            })->name('about');
        }, [\Mcamara\LaravelLocalization\Middleware\LocaleSetLanguage::class]);

        $this->get('/about', ['Accept-Language' => 'en'])
            ->assertResponseOk()
            ->see('Hello world');

        $this->assertEquals('en', App::getLocale());
    }
}
