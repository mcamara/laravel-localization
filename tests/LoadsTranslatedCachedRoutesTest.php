<?php

namespace Mcamara\LaravelLocalization\Tests;

use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;
use PHPUnit\Framework\Attributes\DataProvider;

final class LoadsTranslatedCachedRoutesTest extends TestCase
{
    private ?string $cacheDirectory = null;

    protected function tearDown(): void
    {
        if ($this->cacheDirectory !== null) {
            array_map('unlink', glob($this->cacheDirectory.'/*'));
            rmdir($this->cacheDirectory);
        }

        parent::tearDown();
    }

    #[DataProvider('requestPathDataProvider')]
    public function testLoadsTheRouteCacheOfALocaleOnlyWhenTheLocaleIsSupported(string $path, string $expectedCache): void
    {
        $loader = $this->makeRouteCacheLoader($this->createRouteCacheFiles());
        $this->app->instance(LaravelLocalization::class, app(LaravelLocalization::class, ['request' => Request::create($path)]));

        $this->expectOutputString($expectedCache);

        $loader->load();
    }

    public static function requestPathDataProvider(): array
    {
        return [
            'supported locale' => ['/es/acerca', 'es'],
            'unsupported locale that has a cache file' => ['/xx/about', 'default'],
        ];
    }

    /**
     * Create route cache files that print their own locale when PHP requires them.
     */
    private function createRouteCacheFiles(): string
    {
        $this->cacheDirectory = sys_get_temp_dir().'/laravellocalization-'.bin2hex(random_bytes(8));
        mkdir($this->cacheDirectory);

        file_put_contents($this->cacheDirectory.'/routes-v7.php', 'default');
        file_put_contents($this->cacheDirectory.'/routes-v7_es.php', 'es');
        file_put_contents($this->cacheDirectory.'/routes-v7_xx.php', 'xx');

        return $this->cacheDirectory.'/routes-v7.php';
    }

    private function makeRouteCacheLoader(string $defaultCachePath): object
    {
        return new class($this->app, $defaultCachePath)
        {
            use LoadsTranslatedCachedRoutes;

            public function __construct(protected $app, private string $defaultCachePath)
            {
            }

            public function load(): void
            {
                $this->loadCachedRoutes();
            }

            protected function getDefaultCachedRoutePath()
            {
                return $this->defaultCachePath;
            }
        };
    }
}
