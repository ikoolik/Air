<?php

namespace Tickets\Elements;

use \Tickets\Base\Location as BaseLocation;
use Tickets\Exceptions\RuntimeException;

class Location {

    private $country;
    private $city;
    private $port;

    public function __construct(BaseLocation $country, BaseLocation $city, BaseLocation $port)
    {
        if(!$country->isCountry()) {
            throw new RuntimeException('First argument must be country');
        }
        if(!$city->isCity()) {
            throw new RuntimeException('Second argument must be city');
        }
        if(!$port->isPort()) {
            throw new RuntimeException('Third argument must be port');
        }
        $this->country = $country;
        $this->city = $city;
        $this->port = $port;
    }
} 