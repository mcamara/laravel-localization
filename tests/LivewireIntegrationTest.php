<?php

namespace Mcamara\LaravelLocalization\Tests;

use Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider;

final class LivewireIntegrationTest extends TestCase
{
    /**
     * Test that the correctLivewireRoutes method handles missing Livewire gracefully.
     * If the method is faulty, it would try to access Livewire and throw an exception.
     */
    public function testCorrectLivewireRoutesWithoutLivewire(): void
    {
        // Ensure Livewire is NOT bound in the container
        if ($this->app->bound('livewire')) {
            $this->app->forgetInstance('livewire');
        }

        // Create a real service provider instance
        $serviceProvider = new LaravelLocalizationServiceProvider($this->app);
        
        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('correctLivewireRoutes');
        $method->setAccessible(true);
        
        // This should NOT throw an exception if the method is correctly implemented
        try {
            $method->invoke($serviceProvider);
            $this->assertTrue(true, 'Method handled missing Livewire correctly without throwing exception');
        } catch (\Throwable $e) {
            $this->fail('Method did not properly check for Livewire existence: ' . $e->getMessage());
        }
    }

    /**
     * Test the logic flow without actually requiring Livewire.
     * This test verifies that the method correctly checks for Livewire availability via service container.
     */
    public function testLivewireServiceContainerCheck(): void
    {        
        // Get the method source to verify it contains the service container check
        $reflection = new \ReflectionClass(LaravelLocalizationServiceProvider::class);
        
        // Read the actual file to check for the bound() call
        $filename = $reflection->getFileName();
        $source = file_get_contents($filename);
        
        // Verify the method contains the expected service container checks
        $this->assertStringContainsString('$this->app->bound(\'livewire\')', $source);
        $this->assertStringContainsString('setUpdateRoute', $source);
        $this->assertStringContainsString('correctLivewireRoutes', $source);
        $this->assertStringContainsString('method_exists', $source);
    }

    /**
     * Test that simulates the scenario where Livewire is available via service container.
     * We bind a mock Livewire service and test the integration.
     */
    public function testWithMockLivewire(): void
    {
        // Create a mock Livewire class that captures the callback
        $mockLivewire = new class {
            public static $capturedCallback = null;
            
            public static function setUpdateRoute($callback) {
                self::$capturedCallback = $callback;
            }
            
            public static function getCapturedCallback() {
                return self::$capturedCallback;
            }
        };

        // Bind the mock Livewire in the service container
        $this->app->bind('livewire', function() use ($mockLivewire) {
            return $mockLivewire;
        });

        // Set a test locale
        app('laravellocalization')->setLocale('es');

        // Create a real service provider instance
        $serviceProvider = new LaravelLocalizationServiceProvider($this->app);
        
        // Call the original method
        $reflection = new \ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('correctLivewireRoutes');
        $method->setAccessible(true);
        
        // This should call our mocked setUpdateRoute
        $method->invoke($serviceProvider);
        
        // Verify that setUpdateRoute was called with a callback
        $callback = $mockLivewire::getCapturedCallback();
        $this->assertIsCallable($callback, 'setUpdateRoute should have been called with a callback');
        
        // Test the callback functionality
        $testHandle = function() { return 'test-response'; };
        $route = $callback($testHandle);
        
        // Verify the route has the expected properties for Livewire integration
        $this->assertInstanceOf(\Illuminate\Routing\Route::class, $route);
        $this->assertEquals(['POST'], $route->methods());
        $this->assertEquals('livewire/update', $route->uri());
        
        // Verify middleware is applied
        $middleware = $route->middleware();
        $this->assertContains('web', $middleware);
    }
}