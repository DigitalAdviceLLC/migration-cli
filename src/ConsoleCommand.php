<?php

namespace ByJG\DbMigration\Console;

use ByJG\DbMigration\Database\DblibDatabase;
use ByJG\DbMigration\Database\MySqlDatabase;
use ByJG\DbMigration\Database\PgsqlDatabase;
use ByJG\DbMigration\Database\SqliteDatabase;
use ByJG\DbMigration\Exception\InvalidMigrationFile;
use ByJG\DbMigration\Migration;
use ByJG\Util\Uri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
use Error;

abstract class ConsoleCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName("migrate-cli")
            ->addArgument(
                'connection',
                InputArgument::OPTIONAL,
                'The connection string. Ex. mysql://root:password@server/database',
                getenv('MIGRATE_CONNECTION')
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Define the path where the base.sql resides. If not set assumes the current folder'
            )
            ->addOption(
                'up-to',
                'u',
                InputOption::VALUE_REQUIRED,
                'Run up to the specified version'
            )
            ->addOption(
                'no-base',
                null,
                InputOption::VALUE_NONE,
                'Remove the check for base.sql file'
            )
            ->addUsage('')
            ->addUsage('Example: ')
            ->addUsage('   migrate reset mysql://root:password@server/database')
            ->addUsage('   migrate up mysql://root:password@server/database')
            ->addUsage('   migrate down mysql://root:password@server/database')
            ->addUsage('   migrate up --up-to=10 --path=/some/path mysql://root:password@server/database')
            ->addUsage('   migrate down --up-to=3 --path=/some/path mysql://root:password@server/database')
        ;
    }

    /**
     * @var Migration
     */
    protected $migration;

    protected $upTo;

    protected $connection;

    protected $path;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidMigrationFile
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->connection = $input->getArgument('connection');
        if (!$this->connection) {
            throw new InvalidArgumentException(
                'You need to setup the connection in the argument or setting the environment MIGRATE_CONNECTION'
            );
        }

        $this->path = $input->getOption('path');
        if (!$this->path) {
            $this->path = (!empty(getenv('MIGRATE_PATH')) ? getenv('MIGRATE_PATH') : ".");
        }
        $this->path = realpath($this->path);

        $this->upTo = $input->getOption('up-to');

        $requiredBase = !$input->getOption('no-base');

        $migrationTable = (empty(getenv('MIGRATE_TABLE')) ? "migration_version" : getenv('MIGRATE_TABLE'));
        $this->path = realpath($this->path);
        $uri = new Uri($this->connection);
        $this->migration = new Migration($uri, $this->path, $requiredBase, $migrationTable);
        $this->migration
            ->registerDatabase('sqlite', SqliteDatabase::class)
            ->registerDatabase('mysql', MySqlDatabase::class)
            ->registerDatabase('pgsql', PgsqlDatabase::class)
            ->registerDatabase('dblib', DblibDatabase::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('Connection String: ' . $this->connection);
            $output->writeln('Path: ' . $this->path);
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->migration->addCallbackProgress(function ($command, $version) use ($output) {
                $output->writeln('Doing: ' . $command . " to " . $version);
            });
        }
    }

    /**
     * @param Exception|Error $exception
     * @param OutputInterface $output
     */
    protected function handleError($exception, OutputInterface $output)
    {
        exit(1);
        /*
        $output->writeln('-- Error migrating tables --');
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(get_class($exception));
            $output->writeln($exception->getMessage());
        }
        */
    }
}
