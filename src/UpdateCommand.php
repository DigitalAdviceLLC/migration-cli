<?php

namespace ByJG\DbMigration\Console;

use ByJG\DbMigration\Exception\DatabaseDoesNotRegistered;
use ByJG\DbMigration\Exception\DatabaseIsIncompleteException;
use ByJG\DbMigration\Exception\DatabaseNotVersionedException;
use ByJG\DbMigration\Exception\InvalidMigrationFile;
use ByJG\DbMigration\Exception\OldVersionSchemaException;

class UpdateCommand extends UpdateCommandBase
{
    public function name()
    {
        return 'update';
    }

    public function description()
    {
        return 'Migrate Up or Down the database version based on the current database version and the ' .
               'migration scripts available';
    }

    /**
     * @throws DatabaseDoesNotRegistered
     * @throws DatabaseIsIncompleteException
     * @throws DatabaseNotVersionedException
     * @throws InvalidMigrationFile
     * @throws OldVersionSchemaException
     */
    protected function callMigrate()
    {
        $this->migration->update($this->upTo, true);
    }
}
