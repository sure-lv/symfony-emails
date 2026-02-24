<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Model\ModelInterface;
use Doctrine\DBAL\Connection;

class ModelService
{

    private string $tablePrefix;

    /**
     * @var array<string, \SureLv\Emails\Model\ModelInterface>
     */
    private array $models = [];

    public function __construct(private Connection $connection, RegistryService $registryService)
    {
        $this->tablePrefix = $registryService->getTablePrefix();
    }

    /**
     * Get model
     * 
     * @param string $name
     * @return \SureLv\Emails\Model\ModelInterface
     */
    public function getModel(string $name): ModelInterface
    {
        if (!isset($this->models[$name])) {
            $model = new $name($this->connection, $this->tablePrefix); /** @var \SureLv\Emails\Model\ModelInterface $model */
            $this->models[$name] = $model;
        }
        return $this->models[$name];
    }

    /**
     * Get connection
     * 
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

}