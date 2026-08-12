<?php

namespace Mcamara\LaravelLocalization\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Mcamara\LaravelLocalization\Commands\RouteTranslationsCacheCommand;
use Mcamara\LaravelLocalization\Commands\RouteTranslationsClearCommand;
use Mcamara\LaravelLocalization\Commands\RouteTranslationsListCommand;
use PHPUnit\Framework\Attributes\DataProvider;

class CommandRegistrationTest extends TestCase
{
    public static function packageCommandDataProvider(): array
    {
        return [
            'cache' => ['route:trans:cache', RouteTranslationsCacheCommand::class],
            'clear' => ['route:trans:clear', RouteTranslationsClearCommand::class],
            'list' => ['route:trans:list', RouteTranslationsListCommand::class],
        ];
    }

    #[DataProvider('packageCommandDataProvider')]
    public function testCommandIsRegisteredUnderItsOwnName(string $name, string $expectedClass): void
    {
        $commands = $this->app->make(Kernel::class)->all();

        $this->assertArrayHasKey($name, $commands, "Command '{$name}' is not registered.");
        $this->assertInstanceOf($expectedClass, $commands[$name]);
    }

    public static function nativeCommandDataProvider(): array
    {
        return [
            'route:cache' => ['route:cache', RouteCacheCommand::class],
            'route:list' => ['route:list', RouteListCommand::class],
        ];
    }

    /**
     * The package's commands extend Laravel's. Asserting on the exact class rather
     * than with assertInstanceOf is deliberate: a hijacking subclass would satisfy
     * assertInstanceOf and the regression would go unnoticed.
     */
    #[DataProvider('nativeCommandDataProvider')]
    public function testLaravelNativeCommandIsNotHijacked(string $name, string $expectedClass): void
    {
        $commands = $this->app->make(Kernel::class)->all();

        $this->assertArrayHasKey($name, $commands, "Command '{$name}' is not registered.");
        $this->assertSame($expectedClass, $commands[$name]::class);
    }

    public function testTranslatedRouteListCommandKeepsTheLocaleArgumentAndParentOptions(): void
    {
        $definition = $this->app->make(Kernel::class)->all()['route:trans:list']->getDefinition();

        $this->assertTrue($definition->hasArgument('locale'));
        $this->assertTrue($definition->getArgument('locale')->isRequired());

        foreach (['json', 'method', 'name', 'path', 'except-vendor', 'only-vendor'] as $option) {
            $this->assertTrue($definition->hasOption($option), "Option '--{$option}' is missing.");
        }
    }
}
