<?php
namespace Tickets\Base;


abstract class Element {
    protected $IATACode;

    public function iataCode($code = null)
    {
        if(!is_null($code)) {
            $this->IATACode = $code;
        }
        return $this->IATACode;
    }
} 