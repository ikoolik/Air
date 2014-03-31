<?php

namespace Tickets\Base;

use Tickets\Exceptions\RuntimeException;

/**
 * Class Person
 * @package Tickets\Base
 */
class Person {
    const SEX_MALE = 1;
    const SEX_FEMALE = 2;

    /** @var  string  */
    private $firstName;
    /** @var  string  */
    private $lastName;
    /** @var  string */
    private $sex;

    /**
     * @param mixed $firstName
     * @throws RuntimeException
     */
    public function setFirstName($firstName)
    {
        if(!is_null($this->firstName)) {
            throw new RuntimeException("Persons firstName already sent to {$this->firstName}");
        }
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $lastName
     * @throws RuntimeException
     */
    public function setLastName($lastName)
    {
        if(!is_null($this->lastName)) {
            throw new RuntimeException("Persons lastName already sent to {$this->lastName}");
        }
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $sex
     * @throws RuntimeException
     */
    public function setSex($sex)
    {
        if(!in_array($sex, [self::SEX_FEMALE, self::SEX_MALE])) {
            throw new RuntimeException('Wrong Person sex. You must use Person::SEX_MALE or Person::SEX_FEMALE');
        }
        if(!is_null($this->sex)) {
            throw new RuntimeException("Persons sex already sent to {$this->getSexConstantName()}");
        }
        $this->sex = $sex;
    }

    /**
     * @return mixed
     */
    public function getSex()
    {
        return $this->sex;
    }

    private function getSexConstantName()
    {
        switch($this->getSex()) {
            case self::SEX_MALE :
                return 'Person::SEX_MALE';
            case self::SEX_FEMALE :
                return 'Person::SEX_FEMALE';
        }
    }

} 