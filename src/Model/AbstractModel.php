<?php

namespace SureLv\Emails\Model;

use Doctrine\DBAL\Connection;

abstract class AbstractModel implements ModelInterface
{

    public function __construct(protected Connection $connection, protected string $tablePrefix) {}

}