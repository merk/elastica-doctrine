<?php

namespace Elastica\Test\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Elastica\Doctrine\DoctrineProcessor;
use Elastica\Doctrine\TypeMap;
use Elastica\Query;
use Elastica\Response;
use Elastica\Result;
use Elastica\ResultSet;

class DoctrineProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\\Common\\Persistence\\ManagerRegistry');
    }

    public function testProcessor()
    {
        $typeMap = [
            new TypeMap('index', 'type', 'Elastica\Test\Doctrine\Entity1', 'findStuffBy'),
            new TypeMap('index', 'type2', 'Elastica\Test\Doctrine\Entity2', 'differentMethod'),
        ];

        $resultSet = $this->buildResultSet([
            ['_index' => 'index', '_type' => 'type2', '_id' => 5],
            ['_index' => 'index', '_type' => 'type', '_id' => 6],
            ['_index' => 'index', '_type' => 'type', '_id' => 7],
            ['_index' => 'index2', '_type' => 'type3', '_id' => 12],
            ['_index' => 'index', '_type' => 'type2', '_id' => 8],
        ]);

        $repository1 = $this->getMock('Doctrine\\Common\\Persistence\\ObjectRepository', ['findStuffBy', 'find', 'findAll', 'findBy', 'findOneBy', 'getClassName']);
        $repository1->expects($this->once())
            ->method('findStuffBy')
            ->with(['id' => [6, 7]])
            ->willReturn([
                $e11 = new Entity1(6),
                $e12 = new Entity1(7)
            ]);

        $repository2 = $this->getMock('Doctrine\\Common\\Persistence\\ObjectRepository', ['differentMethod', 'find', 'findAll', 'findBy', 'findOneBy', 'getClassName']);
        $repository2->expects($this->once())
            ->method('differentMethod')
            ->with(['betterId' => [5, 8]])
            ->willReturn([
                $e21 = new Entity2(5),
                $e22 = new Entity2(8)
            ]);

        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['Elastica\Test\Doctrine\Entity1', null, $repository1],
                ['Elastica\Test\Doctrine\Entity2', null, $repository2],
            ]));

        $metadata1 = $this->getMock('Doctrine\\Common\\Persistence\\Mapping\\ClassMetadata');
        $metadata1->expects($this->any())
            ->method('getIdentifier')
            ->willReturn(['id']);
        $metadata1->expects($this->exactly(2))
            ->method('getIdentifierValues')
            ->withConsecutive([$e11], [$e12])
            ->willReturnOnConsecutiveCalls(
                ['id' => 6],
                ['id' => 7]
            );

        $metadata2 = $this->getMock('Doctrine\\Common\\Persistence\\Mapping\\ClassMetadata');
        $metadata2->expects($this->any())
            ->method('getIdentifier')
            ->willReturn(['betterId']);
        $metadata2->expects($this->exactly(2))
            ->method('getIdentifierValues')
            ->withConsecutive([$e21], [$e22])
            ->willReturnOnConsecutiveCalls(
                ['betterId' => 5],
                ['betterId' => 8]
            );

        $manager = $this->getMock('Doctrine\\Common\\Persistence\\ObjectManager');
        $manager->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->willReturnMap([
                ['Elastica\Test\Doctrine\Entity1', $metadata1],
                ['Elastica\Test\Doctrine\Entity2', $metadata2],
            ]);

        $this->registry->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($manager);

        $processor = new DoctrineProcessor($this->registry, $typeMap);
        $processor->process($resultSet);

        $this->assertSame($e21, $resultSet[0]->getParam('doctrine'));
        $this->assertSame($e11, $resultSet[1]->getParam('doctrine'));
        $this->assertSame($e12, $resultSet[2]->getParam('doctrine'));
        $this->assertEmpty($resultSet[3]->getParam('doctrine'));
        $this->assertSame($e22, $resultSet[4]->getParam('doctrine'));
    }

    private function buildResultSet(array $hits)
    {
        $results = array_map(function ($hit) {
            return new Result($hit);
        }, $hits);

        return new ResultSet(new Response([], 200), new Query([]), $results);
    }
}

class Entity1
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}

class Entity2
{
    public $betterId;

    public function __construct($betterId)
    {
        $this->betterId = $betterId;
    }
}
