<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Config;

use PDO;

class DB
{
    /**
     * @var PDO
     */
    public static PDO $pdo;

    /**
     * @return PDO
     */
    public static function connect(): PDO
    {
        if (!isset(self::$pdo)) {
            self::$pdo = self::createConnection();
        }

        return self::$pdo;
    }

    /**
     * @param bool $testing
     * @return PDO
     */
    public static function createConnection(bool $testing = false): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '3306';
        $prefix = $testing ? 'DB_TEST_' : 'DB_';
        $database = getenv($prefix . 'DATABASE') ?: ($testing ? 'backend_test' : 'backend');
        $username = getenv($prefix . 'USERNAME') ?: ($testing ? 'backend_test' : 'backend');
        $password = getenv($prefix . 'PASSWORD') ?: ($testing ? 'backend_test_dev' : 'backend_dev');

        if (
            $testing &&
            ($database === (getenv('DB_DATABASE') ?: 'backend') || !str_ends_with($database, '_test'))
        ) {
            throw new \RuntimeException('The test database must be separate from the application database');
        }

        return new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ]);
    }
}
