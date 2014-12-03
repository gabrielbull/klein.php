<?php
namespace Router\Tests;

use Router\Route;
use Router\RouteFactory;

class RouteFactoryTest extends AbstractKleinTest
{

    const TEST_CALLBACK_MESSAGE = 'yay';


    protected function getTestCallable($message = self::TEST_CALLBACK_MESSAGE)
    {
        return function () use ($message) {
            return $message;
        };
    }


    public function testBuildBasic(
        $test_namespace = null,
        $test_path = null,
        $test_paths_match = true,
        $should_match = true
    )
    {
        // Test data
        $test_path = is_string($test_path) ? $test_path : '/test';
        $test_callable = $this->getTestCallable();


        $factory = new RouteFactory($test_namespace);

        $route = $factory->build(
            $test_callable,
            $test_path
        );

        $this->assertTrue($route instanceof Route);
        $this->assertNull($route->getMethod());
        $this->assertNull($route->getName());
        $this->assertSame($test_callable(), $route());

        $this->assertSame($should_match, $route->getCountMatch());

        if ($test_paths_match) {
            $this->assertSame($test_path, $route->getPath());
        }
    }

    public function testBuildWithNamespacedPath()
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '/test';

        $this->testBuildBasic($test_namespace, $test_path, false);
    }

    public function testBuildWithNamespacedCatchAllPath()
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '*';

        $this->testBuildBasic($test_namespace, $test_path, false, false);
    }

    public function testBuildWithNamespacedNullPath()
    {
        // Test data
        $test_namespace = '/users';

        $this->testBuildBasic($test_namespace, null, false);
    }

    public function testBuildWithNamespacedEmptyPath()
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '';

        $this->testBuildBasic($test_namespace, $test_path, false, true);
    }

    public function testBuildWithNamespacedPath()
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '/test';

        $this->testBuildBasic($test_namespace, $test_path, false);
    }

    public function testBuildWithCustomRegexPath()
    {
        // Test data
        $test_path = '@/test';

        $this->testBuildBasic(null, $test_path);
    }

    public function testBuildWithCustomRegexNamespacedPath()
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '@/test';

        $this->testBuildBasic($test_namespace, $test_path, false);
    }

    public function testBuildWithCustomNegatedRegexPath()
    {
        // Test data
        $test_path = '!@/test';

        $this->testBuildBasic(null, $test_path, false);
    }

    public function testBuildWithCustomNegatedAnchoredRegexPath()
    {
        // Test data
        $test_path = '!@^/test';

        $this->testBuildBasic(null, $test_path, false);
    }
}
