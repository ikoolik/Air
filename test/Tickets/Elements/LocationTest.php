<?php

namespace Tickets\Elements;

use \Tickets\Base\Location as BaseLocation;
use Tickets\Exceptions\RuntimeException;

class LocationTest extends \PHPUnit_Framework_TestCase {
    public function testMain()
    {
        $country = new BaseLocation(BaseLocation::TYPE_COUNTRY);
        $city = new BaseLocation(BaseLocation::TYPE_CITY);
        $port = new BaseLocation(BaseLocation::TYPE_PORT);

        try {
            $location = new Location($city, $country, $port);
            $this->fail('RuntimeException must be thrown on incorrect arguments order');
        } catch (RuntimeException $e) {}
        $location = new Location($country, $city, $port);
    }
}
 