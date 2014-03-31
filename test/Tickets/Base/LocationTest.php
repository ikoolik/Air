<?php

namespace Tickets\Base;


use Tickets\Exceptions\RuntimeException;

class LocationTest extends \PHPUnit_Framework_TestCase {

    public function testMain()
    {
        try {
            $location = new Location('балалайка');
            fail('RuntimeException must be thrown on incorrect type');
        } catch (RuntimeException $e) {}
        $location = new Location(Location::TYPE_COUNTRY);

        $this->assertEquals(Location::TYPE_COUNTRY, $location->getType());
        $this->assertTrue($location->isCountry());
        $this->assertFalse($location->isPort());
    }
}
 