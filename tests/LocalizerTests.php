<?php

use Mcamara\LaravelLocalization\LaravelLocalization;

class LocalizerTests extends \Orchestra\Testbench\BrowserKit\TestCase
{
    protected $test_url = 'http://localhost/';
    protected $test_url2 = 'http://localhost';

    protected $supportedLocales = [];

    protected $defaultLocale = 'en';

    protected function getPackageProviders($app)
    {
        return [
            'Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider',
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LaravelLocalization' => 'Mcamara\LaravelLocalization\Facades\LaravelLocalization',
        ];
    }

    public function setUp(): void
    {
        parent::setUp();
    }

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
        app('config')->set('app.url', $this->test_url);

        app('config')->set('app.locale', $this->defaultLocale);

        $packageConfigFile = __DIR__.'/../src/config/config.php';
        $config = app('files')->getRequire($packageConfigFile);

        app('config')->set('laravellocalization', $config);

        $this->supportedLocales = app('config')->get('laravellocalization.supportedLocales');

        app('translator')->getLoader()->addNamespace('LaravelLocalization', realpath(dirname(__FILE__)).'/lang');

        app('translator')->load('LaravelLocalization', 'routes', 'es');
        app('translator')->load('LaravelLocalization', 'routes', 'en');

        app('laravellocalization')->setBaseUrl($this->test_url);

        $this->setRoutes();
    }

    public function testSetLocale()
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

    public function testLocalizeURL()
    {
        $this->assertEquals(
            $this->test_url.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->localizeURL()
        );

        // Missing trailing slash in a URL
        $this->assertEquals(
            $this->test_url2.'/'.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->localizeURL()
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        // testing hide default locale option
        $this->assertNotEquals(
            $this->test_url.app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->localizeURL()
        );

        $this->assertEquals(
            $this->test_url,
            app('laravellocalization')->localizeURL()
        );

        app('laravellocalization')->setLocale('es');

        $this->assertEquals(
            $this->test_url.'es',
            app('laravellocalization')->localizeURL()
        );

        $this->assertEquals(
            $this->test_url.'about',
            app('laravellocalization')->localizeURL($this->test_url.'about', 'en')
        );

        $this->assertNotEquals(
            $this->test_url.'en/about',
            app('laravellocalization')->localizeURL($this->test_url.'about', 'en')
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', false);

        $this->assertEquals(
            $this->test_url.'en/about',
            app('laravellocalization')->localizeURL($this->test_url.'about', 'en')
        );

        $this->assertNotEquals(
            $this->test_url.'about',
            app('laravellocalization')->localizeURL($this->test_url.'about', 'en')
        );
    }

    public function testGetLocalizedURL()
    {
        $this->assertEquals(
            $this->test_url.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);
        // testing default language hidden

        $this->assertNotEquals(
            $this->test_url.app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        app('laravellocalization')->setLocale('es');

        $this->assertNotEquals(
            $this->test_url,
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertNotEquals(
            $this->test_url.app('laravellocalization')->getDefaultLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertEquals(
            $this->test_url.app('laravellocalization')->getCurrentLocale(),
            app('laravellocalization')->getLocalizedURL()
        );

        $this->assertEquals(
            $this->test_url.'es/acerca',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url.'about')
        );

        app('laravellocalization')->setLocale('en');

        $crawler = $this->call(
            'GET',
            $this->test_url.'about',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en,es']
        );

        $this->assertResponseOk();
        $this->assertEquals(
            $this->test_url.'es/acerca',
            $crawler->getContent()
        );

        $this->refreshApplication();

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        $this->assertEquals(
            $this->test_url.'test',
            app('laravellocalization')->getLocalizedURL('en', $this->test_url.'test')
        );

        $this->assertEquals(
            $this->test_url.'test?a=1',
            app('laravellocalization')->getLocalizedURL('en', $this->test_url.'test?a=1')
        );

        $crawler = $this->call(
            'GET',
            app('laravellocalization')->getLocalizedURL('en', $this->test_url.'test'),
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
            $this->test_url.'es/test',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url.'test')
        );

        $this->assertEquals(
            $this->test_url.'es/test?a=1',
            app('laravellocalization')->getLocalizedURL('es', $this->test_url.'test?a=1')
        );
    }

    public function testGetLocalizedURLWithQueryStringAndhideDefaultLocaleInURL()
    {
        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);
        $request = $this->createRequest(
            $uri = 'en/about?q=2'
        );
        $laravelLocalization = app(LaravelLocalization::class, ['request' => $request]);
        $laravelLocalization->transRoute('LaravelLocalization::routes.about');

        $this->assertEquals(
            $this->test_url . 'about?q=2',
            $laravelLocalization->getLocalizedURL()
        );
    }

    public function testGetLocalizedURLWithQueryStringAndNotTranslatedRoute()
    {
        $request = $this->createRequest(
            $uri = 'en/about?q=2'
        );
        $laravelLocalization = app(LaravelLocalization::class, ['request' => $request]);

        $this->assertEquals(
            $this->test_url . 'en/about?q=2',
            $laravelLocalization->getLocalizedURL()
        );
    }

    /**
     * @param string $path
     * @param string|bool $expectedRouteName
     *
     * @dataProvider getRouteNameFromAPathDataProvider
     */
    public function testGetRouteNameFromAPath($path, $expectedRouteName)
    {
        $this->assertEquals(
            $expectedRouteName,
            app('laravellocalization')->getRouteNameFromAPath($path)
        );
    }

    public function getRouteNameFromAPathDataProvider()
    {
        return [
            [$this->test_url,                       false],
            [$this->test_url.'es',                  false],
            [$this->test_url.'en/about',            'LaravelLocalization::routes.about'],
            [$this->test_url.'ver/1',               false],
            [$this->test_url.'view/1',              'LaravelLocalization::routes.view'],
            [$this->test_url.'view/1/project',      'LaravelLocalization::routes.view_project'],
            [$this->test_url.'view/1/project/1',    'LaravelLocalization::routes.view_project'],
            [$this->test_url.'en/view/1/project/1',    'LaravelLocalization::routes.view_project'],
            [$this->test_url.'manage/1',            'LaravelLocalization::routes.manage'],
            [$this->test_url.'manage',              'LaravelLocalization::routes.manage'],
            [$this->test_url.'manage/',             'LaravelLocalization::routes.manage'],
            [$this->test_url.'manage/0',            'LaravelLocalization::routes.manage'],
            [$this->test_url.'manage/0?ex=2&ex2=a', 'LaravelLocalization::routes.manage'],
        ];
    }

    public function testGetLocalizedUrlForIgnoredUrls() {
        $crawler = $this->call(
            'GET',
            $this->test_url2.'/skipped',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en,es']
        );

        $this->assertResponseOk();
        $this->assertEquals(
            $this->test_url2.'/skipped',
            $crawler->getContent()
        );
    }

    /**
     * @param bool $hideDefaultLocaleInURL
     * @param bool $forceDefault
     * @param string $locale
     * @param string $path
     * @param string $expectedURL
     *
     * @dataProvider getLocalizedURLDataProvider
     */
    public function testGetLocalizedURLFormat($hideDefaultLocaleInURL, $forceDefault, $locale, $path, $expectedURL)
    {
        app('config')->set('laravellocalization.hideDefaultLocaleInURL', $hideDefaultLocaleInURL);
        $this->assertEquals(
            $expectedURL,
            app('laravellocalization')->getLocalizedURL($locale, $path, [], $forceDefault)
        );

    }

    public function getLocalizedURLDataProvider()
    {
        return [
            // Do not hide default
            [false, false, 'es', $this->test_url,                       $this->test_url.'es'],
            [false, false, 'es', $this->test_url.'es',                  $this->test_url.'es'],
            [false, false, 'es', $this->test_url.'en/about',            $this->test_url.'es/acerca'],
            [false, false, 'es', $this->test_url.'ver/1',               $this->test_url.'es/ver/1'],
            [false, false, 'es', $this->test_url.'view/1/project',      $this->test_url.'es/ver/1/proyecto'],
            [false, false, 'es', $this->test_url.'view/1/project/1',    $this->test_url.'es/ver/1/proyecto/1'],
            [false, false, 'es', $this->test_url.'en/view/1/project/1', $this->test_url.'es/ver/1/proyecto/1'],
            [false, false, 'es', $this->test_url.'manage/1',            $this->test_url.'es/administrar/1'],
            [false, false, 'es', $this->test_url.'manage',              $this->test_url.'es/administrar'],
            [false, false, 'es', $this->test_url.'manage/',             $this->test_url.'es/administrar'],
            [false, false, 'es', $this->test_url.'manage/0',            $this->test_url.'es/administrar/0'],
            [false, false, 'es', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'es/administrar/0?ex=2&ex2=a'],

            // Do not hide default
            [false, false, 'en', $this->test_url.'en',                  $this->test_url.'en'],
            [false, false, 'en', $this->test_url.'about',               $this->test_url.'en/about'],
            [false, false, 'en', $this->test_url.'ver/1',               $this->test_url.'en/ver/1'],
            [false, false, 'en', $this->test_url.'view/1/project',      $this->test_url.'en/view/1/project'],
            [false, false, 'en', $this->test_url.'view/1/project/1',    $this->test_url.'en/view/1/project/1'],
            [false, false, 'en', $this->test_url.'en/view/1/project/1', $this->test_url.'en/view/1/project/1'],
            [false, false, 'en', $this->test_url.'manage/1',            $this->test_url.'en/manage/1'],
            [false, false, 'en', $this->test_url.'manage',              $this->test_url.'en/manage'],
            [false, false, 'en', $this->test_url.'manage/',             $this->test_url.'en/manage'],
            [false, false, 'en', $this->test_url.'manage/0',            $this->test_url.'en/manage/0'],
            [false, false, 'en', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'en/manage/0?ex=2&ex2=a'],

            // Hide default
            [true,  false, 'es', $this->test_url,                       $this->test_url.'es'],
            [true,  false, 'es', $this->test_url.'es',                  $this->test_url.'es'],
            [true,  false, 'es', $this->test_url.'en/about',            $this->test_url.'es/acerca'],
            [true,  false, 'es', $this->test_url.'ver/1',               $this->test_url.'es/ver/1'],
            [true,  false, 'es', $this->test_url.'view/1/project',      $this->test_url.'es/ver/1/proyecto'],
            [true,  false, 'es', $this->test_url.'view/1/project/1',    $this->test_url.'es/ver/1/proyecto/1'],
            [true,  false, 'es', $this->test_url.'en/view/1/project/1', $this->test_url.'es/ver/1/proyecto/1'],
            [true,  false, 'es', $this->test_url.'manage/1',            $this->test_url.'es/administrar/1'],
            [true,  false, 'es', $this->test_url.'manage',              $this->test_url.'es/administrar'],
            [true,  false, 'es', $this->test_url.'manage/',             $this->test_url.'es/administrar'],
            [true,  false, 'es', $this->test_url.'manage/0',            $this->test_url.'es/administrar/0'],
            [true,  false, 'es', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'es/administrar/0?ex=2&ex2=a'],

            // Hide default
            [true,  false, 'en', $this->test_url.'en',                  $this->test_url.''],
            [true,  false, 'en', $this->test_url.'about',               $this->test_url.'about'],
            [true,  false, 'en', $this->test_url.'ver/1',               $this->test_url.'ver/1'],
            [true,  false, 'en', $this->test_url.'view/1/project',      $this->test_url.'view/1/project'],
            [true,  false, 'en', $this->test_url.'view/1/project/1',    $this->test_url.'view/1/project/1'],
            [true,  false, 'en', $this->test_url.'en/view/1/project/1', $this->test_url.'view/1/project/1'],
            [true,  false, 'en', $this->test_url.'manage/1',            $this->test_url.'manage/1'],
            [true,  false, 'en', $this->test_url.'manage',              $this->test_url.'manage'],
            [true,  false, 'en', $this->test_url.'manage/',             $this->test_url.'manage'],
            [true,  false, 'en', $this->test_url.'manage/0',            $this->test_url.'manage/0'],
            [true,  false, 'en', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'manage/0?ex=2&ex2=a'],

            // Do not hide default FORCE SHOWING
            [false, true,  'es', $this->test_url,                       $this->test_url.'es'],
            [false, true,  'es', $this->test_url.'es',                  $this->test_url.'es'],
            [false, true,  'es', $this->test_url.'en/about',            $this->test_url.'es/acerca'],
            [false, true,  'es', $this->test_url.'ver/1',               $this->test_url.'es/ver/1'],
            [false, true,  'es', $this->test_url.'view/1/project',      $this->test_url.'es/ver/1/proyecto'],
            [false, true,  'es', $this->test_url.'view/1/project/1',    $this->test_url.'es/ver/1/proyecto/1'],
            [false, true,  'es', $this->test_url.'en/view/1/project/1', $this->test_url.'es/ver/1/proyecto/1'],
            [false, true,  'es', $this->test_url.'manage/1',            $this->test_url.'es/administrar/1'],
            [false, true,  'es', $this->test_url.'manage',              $this->test_url.'es/administrar'],
            [false, true,  'es', $this->test_url.'manage/',             $this->test_url.'es/administrar'],
            [false, true,  'es', $this->test_url.'manage/0',            $this->test_url.'es/administrar/0'],
            [false, true,  'es', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'es/administrar/0?ex=2&ex2=a'],

            // Do not hide default FORCE SHOWING
            [false, true,  'en', $this->test_url.'en',                  $this->test_url.'en'],
            [false, true,  'en', $this->test_url.'about',               $this->test_url.'en/about'],
            [false, true,  'en', $this->test_url.'ver/1',               $this->test_url.'en/ver/1'],
            [false, true,  'en', $this->test_url.'view/1/project',      $this->test_url.'en/view/1/project'],
            [false, true,  'en', $this->test_url.'view/1/project/1',    $this->test_url.'en/view/1/project/1'],
            [false, true,  'en', $this->test_url.'en/view/1/project/1', $this->test_url.'en/view/1/project/1'],
            [false, true,  'en', $this->test_url.'manage/1',            $this->test_url.'en/manage/1'],
            [false, true,  'en', $this->test_url.'manage',              $this->test_url.'en/manage'],
            [false, true,  'en', $this->test_url.'manage/',             $this->test_url.'en/manage'],
            [false, true,  'en', $this->test_url.'manage/0',            $this->test_url.'en/manage/0'],
            [false, true,  'en', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'en/manage/0?ex=2&ex2=a'],

            // Hide default FORCE SHOWING
            [true,  true,  'es', $this->test_url,                       $this->test_url.'es'],
            [true,  true,  'es', $this->test_url.'es',                  $this->test_url.'es'],
            [true,  true,  'es', $this->test_url.'en/about',            $this->test_url.'es/acerca'],
            [true,  true,  'es', $this->test_url.'ver/1',               $this->test_url.'es/ver/1'],
            [true,  true,  'es', $this->test_url.'view/1/project',      $this->test_url.'es/ver/1/proyecto'],
            [true,  true,  'es', $this->test_url.'view/1/project/1',    $this->test_url.'es/ver/1/proyecto/1'],
            [true,  true,  'es', $this->test_url.'en/view/1/project/1', $this->test_url.'es/ver/1/proyecto/1'],
            [true,  true,  'es', $this->test_url.'manage/1',            $this->test_url.'es/administrar/1'],
            [true,  true,  'es', $this->test_url.'manage',              $this->test_url.'es/administrar'],
            [true,  true,  'es', $this->test_url.'manage/',             $this->test_url.'es/administrar'],
            [true,  true,  'es', $this->test_url.'manage/0',            $this->test_url.'es/administrar/0'],
            [true,  true,  'es', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'es/administrar/0?ex=2&ex2=a'],

            // Hide default FORCE SHOWING
            [true,  true,  'en', $this->test_url.'en',                  $this->test_url.'en'],
            [true,  true,  'en', $this->test_url.'about',               $this->test_url.'en/about'],
            [true,  true,  'en', $this->test_url.'ver/1',               $this->test_url.'en/ver/1'],
            [true,  true,  'en', $this->test_url.'view/1/project',      $this->test_url.'en/view/1/project'],
            [true,  true,  'en', $this->test_url.'view/1/project/1',    $this->test_url.'en/view/1/project/1'],
            [true,  true,  'en', $this->test_url.'en/view/1/project/1', $this->test_url.'en/view/1/project/1'],
            [true,  true,  'en', $this->test_url.'manage/1',            $this->test_url.'en/manage/1'],
            [true,  true,  'en', $this->test_url.'manage',              $this->test_url.'en/manage'],
            [true,  true,  'en', $this->test_url.'manage/',             $this->test_url.'en/manage'],
            [true,  true,  'en', $this->test_url.'manage/0',            $this->test_url.'en/manage/0'],
            [true,  true,  'en', $this->test_url.'manage/0?ex=2&ex2=a', $this->test_url.'en/manage/0?ex=2&ex2=a'],
        ];
    }

    public function testGetURLFromRouteNameTranslated()
    {
        $this->assertEquals(
            $this->test_url.'es/acerca',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url.'en/about',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url.'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', true);

        $this->assertEquals(
            $this->test_url.'about',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url.'es/acerca',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.about')
        );

        $this->assertEquals(
            $this->test_url.'es/ver/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        $this->assertEquals(
            $this->test_url.'view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        $this->assertNotEquals(
            $this->test_url.'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        app('config')->set('laravellocalization.hideDefaultLocaleInURL', false);

        $this->assertNotEquals(
            $this->test_url.'view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );

        $this->assertEquals(
            $this->test_url.'en/view/1',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => 1])
        );
    }

    public function testLocalizedParameterFromTranslateUrl()
    {
        $model = new ModelWithTranslatableRoutes();

        $this->assertEquals(
            $this->test_url.'en/view/company',
            app('laravellocalization')->getURLFromRouteNameTranslated('en', 'LaravelLocalization::routes.view', ['id' => $model])
        );

        $this->assertEquals(
            $this->test_url.'es/ver/empresa',
            app('laravellocalization')->getURLFromRouteNameTranslated('es', 'LaravelLocalization::routes.view', ['id' => $model])
        );
    }

    public function testGetNonLocalizedURL()
    {
        $this->assertEquals(
            $this->test_url,
            app('laravellocalization')->getNonLocalizedURL($this->test_url.'en')
        );
        $this->assertEquals(
            $this->test_url,
            app('laravellocalization')->getNonLocalizedURL($this->test_url.'es')
        );
        $this->assertEquals(
            $this->test_url.'view/1',
            app('laravellocalization')->getNonLocalizedURL($this->test_url.'en/view/1')
        );
        $this->assertEquals(
            $this->test_url.'ver/1',
            app('laravellocalization')->getNonLocalizedURL($this->test_url.'es/ver/1')
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

    public function testGetCurrentLocaleRegional()
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
            ['en', 'es'],
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


    /**
     * @dataProvider accept_language_variations_data
     */
    public function testLanguageNegotiation($accept_string, $must_resolve_to, $asd = null) {

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


    public function accept_language_variations_data() {
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

    public function testLanguageNegotiationWithMapping() {

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

    public function testSetLocaleWithMapping()
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
}
