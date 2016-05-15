<?php

namespace Elastica\Doctrine;

final class TypeMap
{
    /**
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $objectClass;

    /**
     * @var string
     */
    private $repositoryMethod;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $index
     * @param string $type
     * @param string $objectClass
     * @param string$repositoryMethod
     */
    public function __construct($index, $type, $objectClass, $repositoryMethod = 'findBy')
    {
        $this->index = $index;
        $this->type = $type;
        $this->objectClass = $objectClass;
        $this->repositoryMethod = $repositoryMethod;
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * @return string
     */
    public function getRepositoryMethod()
    {
        return $this->repositoryMethod;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
