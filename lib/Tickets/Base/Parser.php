<?php
/**
 * Created by PhpStorm.
 * User: ikoolik
 * Date: 25.03.14
 * Time: 22:11
 */

namespace Tickets\Base;


abstract class Parser {
    protected $type;
    public function getType()
    {
        return $this->type;
    }
} 