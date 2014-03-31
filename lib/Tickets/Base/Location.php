<?php

namespace Tickets\Base;


use Tickets\Exceptions\RuntimeException;

class Location extends Element{

    const TYPE_PORT = 1;
    const TYPE_CITY = 2;
    const TYPE_COUNTRY = 3;

    private $type;

    public function __construct($type)
    {
        if(!in_array($type, [self::TYPE_PORT, self::TYPE_CITY, self::TYPE_COUNTRY])) {
            throw new RuntimeException("Unknown Location type #{$type}");
        }
        $this->type = $type;
    }
    public function getType()
    {
        return $this->type;
    }
    public function isCountry()
    {
        return $this->type === self::TYPE_COUNTRY;
    }
    public function isCity()
    {
        return $this->type === self::TYPE_CITY;
    }
    public function isPort()
    {
        return $this->type === self::TYPE_PORT;
    }
} 