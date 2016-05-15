<?php

namespace Elastica\Test\Doctrine;

use Elastica\Doctrine\TypeMap;

class TypeMapTest extends \PHPUnit_Framework_TestCase
{
    public function testTypeMap()
    {
        $typeMap = new TypeMap('index', 'type', 'stdClass', 'findStuffBy');

        $this->assertEquals('index', $typeMap->getIndex());
        $this->assertEquals('type', $typeMap->getType());
        $this->assertEquals('stdClass', $typeMap->getObjectClass());
        $this->assertEquals('findStuffBy', $typeMap->getRepositoryMethod());
    }
}
