<?php

namespace Tickets\Base;


use Tickets\Exceptions\RuntimeException;

class PersonTest extends \PHPUnit_Framework_TestCase {
    
    public function testFull()
    {
        $person = new Person();
        $this->assertNull($person->getSex());
        try {
            $person->setSex('балалайка');
            $this->fail('Person must throw RuntimeException on wrong sex');
        } catch (RuntimeException $e) {}

        $person->setSex(Person::SEX_MALE);
        $this->assertEquals(Person::SEX_MALE, $person->getSex());
        try {
            $person->setSex(Person::SEX_FEMALE);
            $this->fail('Person must throw RuntimeException on attempts to rewrite its sex');
        } catch (RuntimeException $e) {
            $this->assertEquals('Persons sex already sent to Person::SEX_MALE', $e->getMessage());
        }

        $person->setFirstName('Ivan');
        $this->assertEquals('Ivan', $person->getFirstName());

        try {
            $person->setFirstName('Marina');
            $this->fail('Person must throw RuntimeException on attempts to rewrite its firstName');
        } catch (RuntimeException $e) {}

        $person->setLastName('Kulikov');
        $this->assertEquals('Kulikov', $person->getLastName());
        try {
            $person->setLastName('Petrova');
            $this->fail('Person must throw RuntimeException on attempts to rewrite its firstName');
        } catch (RuntimeException $e) {}
    }
}
 