<?php
namespace Router\Tests;

use Router\App;

class AppTest extends AbstractKleinTest
{

    const TEST_CALLBACK_MESSAGE = 'yay';


    protected function getTestCallable($message = self::TEST_CALLBACK_MESSAGE)
    {
        return function () use ($message) {
            return $message;
        };
    }


    public function testRegisterFiller()
    {
        $func_name = 'yay_func';

        $app = new App();

        $app->register($func_name, $this->getTestCallable());

        return array(
            'app' => $app,
            'func_name' => $func_name,
        );
    }

    /**
     * @depends testRegisterFiller
     */
    public function testGet(array $args)
    {
        // Get our vars from our args
        extract($args);

        $returned = $app->$func_name;

        $this->assertNotNull($returned);
        $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
    }

    /**
     * @expectedException Klein\Exceptions\UnknownServiceException
     */
    public function testGetBadMethod()
    {
        $app = new App();
        $app->random_thing_that_doesnt_exist;
    }

    /**
     * @depends testRegisterFiller
     */
    public function testCall(array $args)
    {
        // Get our vars from our args
        extract($args);

        $returned = $app->{$func_name}();

        $this->assertNotNull($returned);
        $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testCallBadMethod()
    {
        $app = new App();
        $app->random_thing_that_doesnt_exist();
    }

    /**
     * @depends testRegisterFiller
     * @expectedException Klein\Exceptions\DuplicateServiceException
     */
    public function testRegisterDuplicateMethod(array $args)
    {
        // Get our vars from our args
        extract($args);

        $app->register($func_name, $this->getTestCallable());
    }
}
