<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use PDO;
use PDOException;

/**
 * Class CreateCommand
 * @package App\Console\Commands\Database
 */
class CreateCommand extends Command
{
    const CONNECTION_MSSQL = 'sqlsrv';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates a new database';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'db:create';

    /**
     * Execute the console command.
     *
     * @throws PDOException
     */
    public function handle()
    {
        $database = env('DB_DATABASE', false);

        if (!$database) {
            $this->info('Skipping creation of database as env(DB_DATABASE) is empty');
            return;
        }

        try {
            $pdo = $this->getPDOConnection(
                env('DB_CONNECTION'),
                env('DB_HOST'),
                env('DB_PORT'),
                env('DB_USERNAME'),
                env('DB_PASSWORD')
            );

            $result = $pdo->exec(sprintf(
                'CREATE DATABASE %s;',
                $database
            ));

            $getError = $pdo->errorInfo();

            if (!$result && $getError[0] != '00000') {
                throw new PDOException(
                    json_encode($getError)
                );
            }


            $this->info(sprintf('Successfully created %s database', $database));
        } catch (PDOException $exception) {
            $this->error(sprintf('Failed to create %s database, %s', $database, $exception->getMessage()));
        }
    }

    /**
     * @param  string $connection
     * @param  string $host
     * @param  integer $port
     * @param  string $username
     * @param  string $password
     * @return PDO
     */
    private function getPDOConnection($connection, $host, $port, $username, $password): PDO
    {
        $dsnString = sprintf('mysql:host=%s;port=%d;', $host, $port);
        if (self::CONNECTION_MSSQL === $connection) { // mssql server dsn
            $dsnString = sprintf('sqlsrv:Server=%s,%s', $host, $port);
        }

        return new PDO($dsnString, $username, $password);
    }
}
