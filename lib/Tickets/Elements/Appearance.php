<?php

namespace Tickets\Elements;


class Appearance extends Location{

    /** @var \DateTime */
    private $dateTime;

    public function setDateTime(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public function getDateTime()
    {
        return $this->dateTime;
    }
} 