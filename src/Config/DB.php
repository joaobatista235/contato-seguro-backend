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
        $dbPath = dirname(__DIR__, 2) . '/db/db.sqlite3';

        if (!isset(self::$pdo)) {
            self::$pdo = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        return self::$pdo;
    }
}
