<?php

namespace Elastica\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Elastica\ResultSet;
use Elastica\ResultSet\ProcessorInterface;

class DoctrineProcessor implements ProcessorInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var TypeMap[]
     */
    private $typeMap;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param TypeMap[] $typeMap
     */
    public function __construct(ManagerRegistry $managerRegistry, array $typeMap)
    {
        $this->managerRegistry = $managerRegistry;

        foreach ($typeMap as $map) {
            $this->typeMap[sprintf('%s/%s', $map->getIndex(), $map->getType())] = $map;
        }
    }

    /**
     * Inserts Doctrine objects into each Result of a ResultSet if one exists.
     *
     * @param ResultSet $resultSet
     */
    public function process(ResultSet $resultSet)
    {
        $sorted = $this->sortResultSet($resultSet);

        foreach ($sorted as $key => $identifiers) {
            $objects = $this->findByIdentifiers($key, $identifiers);

            foreach ($objects as $object) {
                $index = array_search($this->getObjectIdentifier($object), $identifiers);

                if (false !== $index) {
                    $resultSet->offsetGet($index)->setParam('doctrine', $object);
                }
            }
        }
    }

    /**
     * Retrieves objects from a single ElasticSearch type
     *
     * @param string $key
     * @param array $identifiers
     * @return array
     */
    private function findByIdentifiers($key, $identifiers)
    {
        $type = $this->getTypeMap($key);
        if (!$type) {
            return [];
        }

        $repository = $this->managerRegistry->getRepository($type->getObjectClass());
        $idField = $this->getClassMetadata($type->getObjectClass())->getIdentifier()[0];

        return $repository->{$type->getRepositoryMethod()}([
            $idField => array_values($identifiers)
        ]);
    }

    /**
     * @param string $class
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    private function getClassMetadata($class)
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        return $manager->getClassMetadata($class);
    }

    /**
     * Returns the identifier value of the supplied object.
     *
     * @param mixed $object
     * @return int
     */
    private function getObjectIdentifier($object)
    {
        $metadata = $this->getClassMetadata(get_class($object));

        return $metadata->getIdentifierValues($object)[$metadata->getIdentifier()[0]];
    }

    /**
     * Returns a defined TypeMap if one exists for the given Index/Type.
     *
     * @param string $key
     * @return TypeMap|null
     */
    private function getTypeMap($key)
    {
        if (!isset($this->typeMap[$key])) {
            return null;
        }

        return $this->typeMap[$key];
    }

    /**
     * Returns Result objects sorted into index/type buckets.
     *
     * @param ResultSet $resultSet
     * @return array
     */
    private function sortResultSet(ResultSet $resultSet)
    {
        $sorted = [];

        foreach ($resultSet as $index => $result) {
            $key = $result->getIndex().'/'.$result->getType();

            $sorted[$key][$index] = $result->getId();
        }

        return $sorted;
    }
}
