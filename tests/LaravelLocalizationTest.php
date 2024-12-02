<?php

namespace Mcamara\LaravelLocalization\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Request;
use Mcamara\LaravelLocalization\LaravelLocalization;

final class LaravelLocalizationTest extends TestCase
{
    protected const TEST_URL = 'http://localhost/';

    protected $supportedLocales = [];

    protected $defaultLocale = 'en';

    /**
     * Set routes for testing.
     *
     * @param bool|string $locale
     */
    protected function setRoutes($locale = false)
    {
        if ($locale) {
            app('laravellocalization')->setLocale($locale);
        }

        app('router')->group([
            'prefix'     => app('laravellocalization')->setLocale(),
            'middleware' => [
                'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes',
                'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter',
            ],
        ], function () {
            app('router')->get('/', ['as'=> 'index', function () {
                return app('translator')->get('LaravelLocalization::routes.hello');
            }, ]);

            app('router')->get('test', ['as'=> 'test', function () {
                return app('translator')->get('LaravelLocalization::routes.test_text');
            }, ]);

            app('router')->get(app('laravellocalization')->transRoute('LaravelLocalization::routes.about'), ['as'=> 'about', function () {
                return app('laravellocalization')->getLocalizedURL('es') ?: 'Not url available';
            }, ]);

            app('router')->get(app('laravellocalization')->transRoute('LaravelLocalization::routes.view'), ['as'=> 'view', function () {
                return app('laravellocalization')->getLocalizedURL('es') ?: 'Not url available';
            }, ]);

            app('router')->get(app('laravellocalization')->transRoute('LaravelLocalization::routes.view_project'), ['as'=> 'view_project', function () {
                return app('laravellocalization')->getLocalizedURL('es') ?: 'Not url available';
            }, ]);

            app('router')->get(app('laravellocalization')->transRoute('LaravelLocalization::routes.manage'), ['as'=> 'manage', function () {
                return app('laravellocalization')->getLocalizedURL('es') ?: 'Not url available';
            }, ]);
        });

        app('router')->get('/skipped', ['as'=> 'skipped', function () {
            return Request::url();
        }, ]);
    }

    /**
     * Refresh routes and refresh application.
     *
     * @param bool|string $locale
     */
    protected function refreshApplication($locale = false)
    {
        parent::refreshApplication();
        $this->setRoutes($locale);
    }

    /**
     * Create fake request
     * @param  [type] $method     [description]
     * @param  [type] $content    [description]
     * @param  string $uri        [description]
     * @param  array  $server     [description]
     * @param  array  $parameters [description]
     * @param  array  $cookies    [description]
     * @param  array  $files      [description]
     * @return [type]             [description]
     */
    protected function createRequest(
        $uri = '/test',
        $method = 'GET',
        $parameters = [],
        $cookies = [],
        $files = [],
        $server = ['CONTENT_TYPE' => 'application/json'],
        $content = null
    )
    {
        $request = new \Illuminate\Http\Request;
        return $request->createFromBase(
            \Symfony\Component\HttpFoundation\Request::create(
                $uri,
                'GET',
                [],
                [],
                [],
                $server,
                $content
            )
        );
    }

    /**
     * Define environment setup.
     *
     * @param Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        app('config')->set('app.url', self::TEST_URL);

        app('config')->set('app.locale', $this->defaultLocale);

        $packageConfigFile = __DIR__.'/../src/config/config.php';
        $config = app('files')->getRequire($packageConfigFile);

        app('config')->set('laravellocalization', $config);

        $this->supportedLocales = app('config')->get('laravellocalization.supportedLocales');

        app('translator')->getLoader()->addNamespace('LaravelLocalization', realpath(dirname(__FILE__)).'/lang');

        app('translator')->load('LaravelLocalization', 'routes', 'es');
        app('translator')->load('LaravelLocalization', 'routes', 'en');

        app('laravellocalization')->setBaseUrl(self::TEST_URL);

        $this->setRoutes();
    }

    public function testSetLocale(): void
    {
        $this->assertEquals(route('about'), 'http://localhost/about');

        $this->refreshApplication('es');
        $this->assertEquals('es', app('laravellocalization')->setLocale('es'));
        $this->assertEquals('es', app('laravellocalization')->getCurrentLocale());
        $this->assertEquals(route('about'), 'http://localhost/acerca');

        $this->refreshApplication();

        $this->assertEquals('en', app('laravellocalization')->setLocale('en'));

        $this->assertEquals(route('about'), 'http://localhost/about');

        $this->assertNull(app('laravellocalization')->setLocale('de'));
        $this->assertEquals('en', app('laravellocalization')->getCurrentLocale());
    }

    public function testLocalizeURL(): void
    {
        $this->assertEquals(
            self::TEST_URL.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->localizeURL()
        );

        // Missing trailing slash in a URL
        $this->assertEquals(
            self::TEST_URL.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->localizeURL()
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        // testing hide default locale option
        $this->assertNotEquals(
            self::TEST_URL.app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->localizeURL()
        );

        $this->assertEquals(
            self::TEST_URL,
            app('laravellocalization')->localizeURL()
        );

        app('laravellocalization')->setLocale('es');

        $this->assertEquals(
            self::TEST_URL.'es',
            app('laravellocalization')->localizeURL()
        );

        $this->assertEquals(
            self::TEST_URL.'about',
            app('laravellocalization')->localizeURL(self::TEST_URL.'about', 'en')
        );

        $this->assertNotEquals(
            self::TEST_URL.'en/about',
            app('laravellocalization')->localizeURL(self::TEST_URL.'about', 'en')
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', false);

        $this->assertEquals(
            self::TEST_URL.'en/about',
            app('laravellocalization')->localizeURL(self::TEST_URL.'about', 'en')
        );

        $this->assertNotEquals(
            self::TEST_URL.'about',
            app('laravellocalization')->localizeURL(self::TEST_URL.'about', 'en')
        );
    }

    public function testGetLocalizedURL(): void
    {
        $this->assertEquals(
            self::TEST_URL.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);
        // testing default language hidden

        $this->assertNotEquals(
            self::TEST_URL.app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        app('laravellocalization')->setLocale('es');

        $this->assertNotEquals(
            self::TEST_URL,
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertNotEquals(
            self::TEST_URL.app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertEquals(
            self::TEST_URL.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertEquals(
            self::TEST_URL.'es/acerca',
            app('laravellocalization')->getLocalizedURL('es', self::TEST_URL.'about')
        );

        app('laravellocalization')->setLocale('en');

        $crawler = $this->call(
            'GET',
            self::TEST_URL.'about',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en,es']
        );

        $this->assertResponseOk();
        $this->assertEquals(
            self::TEST_URL.'es/acerca',
            $crawler->getContent()
        );

        $this->refreshApplication();

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        $this->assertEquals(
            self::TEST_URL.'test',
            app('laravellocalization')->getLocalizedURL('en', self::TEST_URL.'test')
        );

        $this->assertEquals(
            self::TEST_URL.'test?a=1',
            app('laravellocalization')->getLocalizedURL('en', self::TEST_URL.'test?a=1')
        );

        $crawler = $this->call(
            'GET',
            app('laravellocalization')->getLocalizedURL('en', self::TEST_URL.'test'),
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en,es']
        );

        $this->assertResponseOk();
        $this->assertEquals(
            'Test text',
            $crawler->getContent()
        );

        $this->refreshApplication('es');

        $this->assertEquals(
            self::TEST_URL.'es/test',
            app('laravellocalization')->getLocalizedURL('es', self::TEST_URL.'test')
        );

        $this->assertEquals(
            self::TEST_URL.'es/test?a=1',
            app('laravellocalization')->getLocalizedURL('es', self::TEST_URL.'test?a=1')
        );
    }

    public function testGetLocalizedURLWithQueryStringAndhideDefaultLocaleInURL(): void
    {
        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);
        $request = $this->createRequest(
            $uri = 'en/about?q=2'
        );
        $laravelLocalization = app(LaravelLocalization::class, ['request' => $request]);
        $laravelLocalization->transRoute('LaravelLocalization::routes.about');

        $this->assertEquals(
            self::TEST_URL . 'about?q=2',
            $laravelLocalization->getLocalizedURL()
        );
    }

    public function testGetLocalizedURLWithQueryStringAndNotTranslatedRoute(): void
    {
        $request = $this->createRequest(
            $uri = 'en/about?q=2'
        );
        $laravelLocalization = app(LaravelLocalization::class, ['request' => $request]);

        $this->assertEquals(
            self::TEST_URL . 'en/about?q=2',
            $laravelLocalization->getLocalizedURL()
        );
    }

    /**
     * @param string $path
     * @param string|bool $expectedRouteName
     */
    #[DataProvider('getRouteNameFromAPathDataProvider')]
    public function testGetRouteNameFromAPath($path, $expectedRouteName): void
    {
        $this->assertEquals(
            $expectedRouteName,
            app('laravellocalization')->getRouteNameFromAPath($path)
        );
    }

    public static function getRouteNameFromAPathDataProvider(): array
    {
        return [
            [self::TEST_URL,                       false],
            [self::TEST_URL.'es',                  false],
            [self::TEST_URL.'en/about',            'LaravelLocalization::routes.about'],
            [self::TEST_URL.'ver/1',               false],
            [self::TEST_URL.'view/1',              'LaravelLocalization::routes.view'],
            [self::TEST_URL.'view/1/project',      'LaravelLocalization::routes.view_project'],
            [self::TEST_URL.'view/1/project/1',    'LaravelLocalization::routes.view_project'],
            [self::TEST_URL.'en/view/1/project/1',    'LaravelLocalization::routes.view_project'],
            [self::TEST_URL.'manage/1',            'LaravelLocalization::routes.manage'],
            [self::TEST_URL.'manage',              'LaravelLocalization::routes.manage'],
            [self::TEST_URL.'manage/',             'LaravelLocalization::routes.manage'],
            [self::TEST_URL.'manage/0',            'LaravelLocalization::routes.manage'],
            [self::TEST_URL.'manage/0?ex=2&ex2=a', 'LaravelLocalization::routes.manage'],
        ];
    }

    public function testGetLocalizedUrlForIgnoredUrls(): void {
        $crawler = $this->call(
            'GET',
            self::TEST_URL.'skipped',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en,es']
        );

        $this->assertResponseOk();
        $this->assertEquals(
            self::TEST_URL.'skipped',
            $crawler->getContent()
        );
    }

    /**
     * @param bool $hideDefaultLocaleInURL
     * @param bool $forceDefault
     * @param string $locale
     * @param string $path
     * @param string $expectedURL
     */
    #[DataProvider('getLocalizedURLDataProvider')]
    public function testGetLocalizedURLFormat($hideDefaultLocaleInURL, $forceDefault, $locale, $path, $expectedURL): void
    {
        app('config')->set('laravellocalization.hideDefaultLocaleInURL', $hideDefaultLocaleInURL);
        $this->assertEquals(
            $expectedURL,
            app('laravellocalization')->getLocalizedURL($locale, $path, [], $forceDefault)
        );

    }

    public static function getLocalizedURLDataProvider(): array
    {
        return [
            // Do not hide default
            [false, false, 'es', self::TEST_URL,                       self::TEST_URL.'es'],
            [false, false, 'es', self::TEST_URL.'es',                  self::TEST_URL.'es'],
            [false, false, 'es', self::TEST_URL.'en/about',            self::TEST_URL.'es/acerca'],
            [false, false, 'es', self::TEST_URL.'ver/1',               self::TEST_URL.'es/ver/1'],
            [false, false, 'es', self::TEST_URL.'view/1/project',      self::TEST_URL.'es/ver/1/proyecto'],
            [false, false, 'es', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'es/ver/1/proyecto/1'],
            [false, false, 'es', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'es/ver/1/proyecto/1'],
            [false, false, 'es', self::TEST_URL.'manage/1',            self::TEST_URL.'es/administrar/1'],
            [false, false, 'es', self::TEST_URL.'manage',              self::TEST_URL.'es/administrar'],
            [false, false, 'es', self::TEST_URL.'manage/',             self::TEST_URL.'es/administrar'],
            [false, false, 'es', self::TEST_URL.'manage/0',            self::TEST_URL.'es/administrar/0'],
            [false, false, 'es', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'es/administrar/0?ex=2&ex2=a'],

            // Do not hide default
            [false, false, 'en', self::TEST_URL.'en',                  self::TEST_URL.'en'],
            [false, false, 'en', self::TEST_URL.'about',               self::TEST_URL.'en/about'],
            [false, false, 'en', self::TEST_URL.'ver/1',               self::TEST_URL.'en/ver/1'],
            [false, false, 'en', self::TEST_URL.'view/1/project',      self::TEST_URL.'en/view/1/project'],
            [false, false, 'en', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'en/view/1/project/1'],
            [false, false, 'en', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'en/view/1/project/1'],
            [false, false, 'en', self::TEST_URL.'manage/1',            self::TEST_URL.'en/manage/1'],
            [false, false, 'en', self::TEST_URL.'manage',              self::TEST_URL.'en/manage'],
            [false, false, 'en', self::TEST_URL.'manage/',             self::TEST_URL.'en/manage'],
            [false, false, 'en', self::TEST_URL.'manage/0',            self::TEST_URL.'en/manage/0'],
            [false, false, 'en', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'en/manage/0?ex=2&ex2=a'],

            // Hide default
            [true,  false, 'es', self::TEST_URL,                       self::TEST_URL.'es'],
            [true,  false, 'es', self::TEST_URL.'es',                  self::TEST_URL.'es'],
            [true,  false, 'es', self::TEST_URL.'en/about',            self::TEST_URL.'es/acerca'],
            [true,  false, 'es', self::TEST_URL.'ver/1',               self::TEST_URL.'es/ver/1'],
            [true,  false, 'es', self::TEST_URL.'view/1/project',      self::TEST_URL.'es/ver/1/proyecto'],
            [true,  false, 'es', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'es/ver/1/proyecto/1'],
            [true,  false, 'es', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'es/ver/1/proyecto/1'],
            [true,  false, 'es', self::TEST_URL.'manage/1',            self::TEST_URL.'es/administrar/1'],
            [true,  false, 'es', self::TEST_URL.'manage',              self::TEST_URL.'es/administrar'],
            [true,  false, 'es', self::TEST_URL.'manage/',             self::TEST_URL.'es/administrar'],
            [true,  false, 'es', self::TEST_URL.'manage/0',            self::TEST_URL.'es/administrar/0'],
            [true,  false, 'es', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'es/administrar/0?ex=2&ex2=a'],

            // Hide default
            [true,  false, 'en', self::TEST_URL.'en',                  self::TEST_URL.''],
            [true,  false, 'en', self::TEST_URL.'about',               self::TEST_URL.'about'],
            [true,  false, 'en', self::TEST_URL.'ver/1',               self::TEST_URL.'ver/1'],
            [true,  false, 'en', self::TEST_URL.'view/1/project',      self::TEST_URL.'view/1/project'],
            [true,  false, 'en', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'view/1/project/1'],
            [true,  false, 'en', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'view/1/project/1'],
            [true,  false, 'en', self::TEST_URL.'manage/1',            self::TEST_URL.'manage/1'],
            [true,  false, 'en', self::TEST_URL.'manage',              self::TEST_URL.'manage'],
            [true,  false, 'en', self::TEST_URL.'manage/',             self::TEST_URL.'manage'],
            [true,  false, 'en', self::TEST_URL.'manage/0',            self::TEST_URL.'manage/0'],
            [true,  false, 'en', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'manage/0?ex=2&ex2=a'],

            // Do not hide default FORCE SHOWING
            [false, true,  'es', self::TEST_URL,                       self::TEST_URL.'es'],
            [false, true,  'es', self::TEST_URL.'es',                  self::TEST_URL.'es'],
            [false, true,  'es', self::TEST_URL.'en/about',            self::TEST_URL.'es/acerca'],
            [false, true,  'es', self::TEST_URL.'ver/1',               self::TEST_URL.'es/ver/1'],
            [false, true,  'es', self::TEST_URL.'view/1/project',      self::TEST_URL.'es/ver/1/proyecto'],
            [false, true,  'es', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'es/ver/1/proyecto/1'],
            [false, true,  'es', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'es/ver/1/proyecto/1'],
            [false, true,  'es', self::TEST_URL.'manage/1',            self::TEST_URL.'es/administrar/1'],
            [false, true,  'es', self::TEST_URL.'manage',              self::TEST_URL.'es/administrar'],
            [false, true,  'es', self::TEST_URL.'manage/',             self::TEST_URL.'es/administrar'],
            [false, true,  'es', self::TEST_URL.'manage/0',            self::TEST_URL.'es/administrar/0'],
            [false, true,  'es', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'es/administrar/0?ex=2&ex2=a'],

            // Do not hide default FORCE SHOWING
            [false, true,  'en', self::TEST_URL.'en',                  self::TEST_URL.'en'],
            [false, true,  'en', self::TEST_URL.'about',               self::TEST_URL.'en/about'],
            [false, true,  'en', self::TEST_URL.'ver/1',               self::TEST_URL.'en/ver/1'],
            [false, true,  'en', self::TEST_URL.'view/1/project',      self::TEST_URL.'en/view/1/project'],
            [false, true,  'en', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'en/view/1/project/1'],
            [false, true,  'en', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'en/view/1/project/1'],
            [false, true,  'en', self::TEST_URL.'manage/1',            self::TEST_URL.'en/manage/1'],
            [false, true,  'en', self::TEST_URL.'manage',              self::TEST_URL.'en/manage'],
            [false, true,  'en', self::TEST_URL.'manage/',             self::TEST_URL.'en/manage'],
            [false, true,  'en', self::TEST_URL.'manage/0',            self::TEST_URL.'en/manage/0'],
            [false, true,  'en', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'en/manage/0?ex=2&ex2=a'],

            // Hide default FORCE SHOWING
            [true,  true,  'es', self::TEST_URL,                       self::TEST_URL.'es'],
            [true,  true,  'es', self::TEST_URL.'es',                  self::TEST_URL.'es'],
            [true,  true,  'es', self::TEST_URL.'en/about',            self::TEST_URL.'es/acerca'],
            [true,  true,  'es', self::TEST_URL.'ver/1',               self::TEST_URL.'es/ver/1'],
            [true,  true,  'es', self::TEST_URL.'view/1/project',      self::TEST_URL.'es/ver/1/proyecto'],
            [true,  true,  'es', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'es/ver/1/proyecto/1'],
            [true,  true,  'es', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'es/ver/1/proyecto/1'],
            [true,  true,  'es', self::TEST_URL.'manage/1',            self::TEST_URL.'es/administrar/1'],
            [true,  true,  'es', self::TEST_URL.'manage',              self::TEST_URL.'es/administrar'],
            [true,  true,  'es', self::TEST_URL.'manage/',             self::TEST_URL.'es/administrar'],
            [true,  true,  'es', self::TEST_URL.'manage/0',            self::TEST_URL.'es/administrar/0'],
            [true,  true,  'es', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'es/administrar/0?ex=2&ex2=a'],

            // Hide default FORCE SHOWING
            [true,  true,  'en', self::TEST_URL.'en',                  self::TEST_URL.'en'],
            [true,  true,  'en', self::TEST_URL.'about',               self::TEST_URL.'en/about'],
            [true,  true,  'en', self::TEST_URL.'ver/1',               self::TEST_URL.'en/ver/1'],
            [true,  true,  'en', self::TEST_URL.'view/1/project',      self::TEST_URL.'en/view/1/project'],
            [true,  true,  'en', self::TEST_URL.'view/1/project/1',    self::TEST_URL.'en/view/1/project/1'],
            [true,  true,  'en', self::TEST_URL.'en/view/1/project/1', self::TEST_URL.'en/view/1/project/1'],
            [true,  true,  'en', self::TEST_URL.'manage/1',            self::TEST_URL.'en/manage/1'],
            [true,  true,  'en', self::TEST_URL.'manage',              self::TEST_URL.'en/manage'],
            [true,  true,  'en', self::TEST_URL.'manage/',             self::TEST_URL.'en/manage'],
            [true,  true,  'en', self::TEST_URL.'manage/0',            self::TEST_URL.'en/manage/0'],
            [true,  true,  'en', self::TEST_URL.'manage/0?ex=2&ex2=a', self::TEST_URL.'en/manage/0?ex=2&ex2=a'],
        ];
    }

    public function testGetURLFromRouteNameTranslated(): void
    {
        $this->assertEquals(
            self::TEST_URL.'es/acerca',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            self::TEST_URL.'en/about',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            self::TEST_URL.'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        $this->assertEquals(
            self::TEST_URL.'about',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            self::TEST_URL.'es/acerca',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            self::TEST_URL.'es/ver/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        $this->assertEquals(
            self::TEST_URL.'view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        $this->assertNotEquals(
            self::TEST_URL.'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', false);

        $this->assertNotEquals(
            self::TEST_URL.'view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        $this->assertEquals(
            self::TEST_URL.'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );
    }

    public function testLocalizedParameterFromTranslateUrl(): void
    {
        $model = new ModelWithTranslatableRoutes();

        $this->assertEquals(
            self::TEST_URL.'en/view/company',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => $model])
        );

        $this->assertEquals(
            self::TEST_URL.'es/ver/empresa',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.view', ['id' => $model])
        );
    }

    public function testGetNonLocalizedURL(): void
    {
        $this->assertEquals(
            self::TEST_URL,
            app('laravellocalization')->getNonLocalizedURL(self::TEST_URL.'en')
        );
        $this->assertEquals(
            self::TEST_URL,
            app('laravellocalization')->getNonLocalizedURL(self::TEST_URL.'es')
        );
        $this->assertEquals(
            self::TEST_URL.'view/1',
            app('laravellocalization')->getNonLocalizedURL(self::TEST_URL.'en/view/1')
        );
        $this->assertEquals(
            self::TEST_URL.'ver/1',
            app('laravellocalization')->getNonLocalizedURL(self::TEST_URL.'es/ver/1')
        );
    }

    public function testGetDefaultLocale(): void
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

    public function testGetSupportedLocales(): void
    {
        $this->assertEquals(
            $this->supportedLocales,
            app('laravellocalization')->getSupportedLocales()
        );
    }

    public function testGetCurrentLocaleName(): void
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

    public function testGetCurrentLocaleRegional(): void
    {
        $this->assertEquals(
            'en_GB',
            app('laravellocalization')->getCurrentLocaleRegional()
        );

        $this->refreshApplication('es');

        $this->assertEquals(
            'es_ES',
            app('laravellocalization')->getCurrentLocaleRegional()
        );
    }

    public function testGetCurrentLocaleDirection(): void
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

    public function testGetCurrentLocaleScript(): void
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

    public function testGetCurrentLocaleNativeReading(): void
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

    public function testGetCurrentLocale(): void
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

    public function testGetSupportedLanguagesKeys(): void
    {
        $this->assertEquals(
            ['en', 'es'],
            app('laravellocalization')->getSupportedLanguagesKeys()
        );
    }

    public function testGetConfigRepository(): void
    {
        $this->assertEquals(
            app('config'),
            app('laravellocalization')->getConfigRepository('/view/1')
        );
    }

    public function testCreateUrlFromUri(): void
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


    #[DataProvider('accept_language_variations_data')]
    public function testLanguageNegotiation($accept_string, $must_resolve_to, $asd = null): void {

        $full_config = include __DIR__ . '/full-config/config.php';

        $request = $this->createMock(\Illuminate\Http\Request::class);
        $request->expects($this->any())->method('header')->with('Accept-Language')->willReturn($accept_string);

        $negotiator = app(\Mcamara\LaravelLocalization\LanguageNegotiator::class,
            [
                    'defaultLocale' => 'wrong',
                    'supportedLanguages' => $full_config['supportedLocales'],
                    'request' => $request
            ]
        );

        $language = $negotiator->negotiateLanguage();


        $this->assertEquals($must_resolve_to, $language);
    }


    public static function accept_language_variations_data(): array {
        $variations = [
            ['en-GB', 'en-GB'],
            ['en-US', 'en-US'],
            ['en-ZA', 'en'],
            ['en', 'en'],
            ['az-AZ', 'az'],
            ['fr-CA,fr;q=0.8', 'fr-CA'],
            ['fr-150', 'fr'],
            ['zh-Hant-cn', 'zh-Hant'],
            ['zh-cn', 'zh'],
        ];

        $dataset = [];
        foreach ($variations as $variation) {
            $dataset[$variation[0]] = $variation;
        }

        return $dataset;
    }

    public function testLanguageNegotiationWithMapping(): void {

        $accept_string = 'en-GB';
        $must_resolve_to = 'uk';

        $mapping = [
            $accept_string => $must_resolve_to
        ];

        $full_config = include __DIR__ . '/full-config/config.php';

        $full_config['supportedLocales']['uk'] = $full_config['supportedLocales']['en-GB'];
        unset($full_config['supportedLocales']['en-GB']);

        app('config')->set('laravellocalization.localesMapping', $mapping);

        $request = $this->createMock(\Illuminate\Http\Request::class);
        $request->expects($this->any())->method('header')->with('Accept-Language')->willReturn($accept_string);

        $negotiator = app(\Mcamara\LaravelLocalization\LanguageNegotiator::class,
            [
                'defaultLocale' => 'wrong',
                'supportedLanguages' => $full_config['supportedLocales'],
                'request' => $request
            ]
        );

        $language = $negotiator->negotiateLanguage();

        $this->assertEquals($must_resolve_to, $language);
    }

    public function testSetLocaleWithMapping(): void
    {
        app('config')->set('laravellocalization.localesMapping', [
            'en' => 'custom',
        ]);

        $this->assertEquals('custom', app('laravellocalization')->setLocale('custom'));
        $this->assertEquals('en', app('laravellocalization')->getCurrentLocale());

        $this->assertTrue(app('laravellocalization')->checkLocaleInSupportedLocales('en'));
        $this->assertTrue(app('laravellocalization')->checkLocaleInSupportedLocales('custom'));

        $this->assertEquals('http://localhost/custom/some-route', app('laravellocalization')->localizeURL('some-route', 'en'));
        $this->assertEquals('http://localhost/custom/some-route', app('laravellocalization')->localizeURL('some-route', 'custom'));
        $this->assertEquals('http://localhost/custom', app('laravellocalization')->localizeURL('http://localhost/custom', 'en'));
    }



    public function testRedirectWithHiddenDefaultLocaleInUrlAndSavedLocale()
    {
        app('router')->group([
            'prefix'     => app('laravellocalization')->setLocale(),
            'middleware' => [
                'Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter',
                'Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect',
            ],
        ], function (){
            app('router')->get('/', ['as'=> 'index', function () {
                return 'Index page';
            }, ]);
        });

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        $savedLocale = 'es';

        $crawler = $this->call(
            'GET',
            self::TEST_URL,
            [],
            ['locale' => $savedLocale],
            [],
            []
        );

        $this->assertResponseStatus(302);
        $this->assertRedirectedTo(self::TEST_URL . $savedLocale);

        $localeCookie = $crawler->headers->getCookies()[0];
        $this->assertEquals($savedLocale, $localeCookie->getValue());
    }
}
