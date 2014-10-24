<?php
namespace Router\Tests\DataCollection;

use Router\DataCollection\ResponseCookieDataCollection;
use Router\ResponseCookie;
use Router\Tests\AbstractKleinTest;

class ResponseCookieDataCollectionTest extends AbstractKleinTest
{

    /**
     * Sample data provider
     *
     * @return array
     */
    public function sampleDataProvider()
    {
        $sample_cookie = new ResponseCookie(
            'Trevor',
            'is a programmer',
            3600,
            '/',
            'example.com',
            false,
            false
        );

        $sample_other_cookie = new ResponseCookie(
            'Chris',
            'is a boss',
            60,
            '/app/',
            'github.com',
            true,
            true
        );

        return array(
            array($sample_cookie, $sample_other_cookie),
        );
    }


    /**
     * @dataProvider sampleDataProvider
     */
    public function testSet($sample_cookie, $sample_other_cookie)
    {
        // Create our collection with NO data
        $data_collection = new ResponseCookieDataCollection();

        // Set our data from our test data
        $data_collection->set('first', $sample_cookie);

        $this->assertSame($sample_cookie, $data_collection->get('first'));
        $this->assertTrue($data_collection->get('first') instanceof ResponseCookie);
    }

    public function testSetStringConvertsToCookie()
    {
        // Create our collection with NO data
        $data_collection = new ResponseCookieDataCollection();

        // Set our data from our test data
        $data_collection->set('first', 'value');

        $this->assertNotSame('value', $data_collection->get('first'));
        $this->assertTrue($data_collection->get('first') instanceof ResponseCookie);
    }

    /**
     * @dataProvider sampleDataProvider
     */
    public function testConstructorRoutesThroughSet($sample_cookie, $sample_other_cookie)
    {
        $array_of_cookie_instances = array(
            $sample_cookie,
            $sample_other_cookie,
            new ResponseCookie('test'),
        );

        // Create our collection with NO data
        $data_collection = new ResponseCookieDataCollection($array_of_cookie_instances);
        $this->assertSame($array_of_cookie_instances, $data_collection->all());

        foreach ($data_collection as $cookie) {
            $this->assertTrue($cookie instanceof ResponseCookie);
        }
    }
}
