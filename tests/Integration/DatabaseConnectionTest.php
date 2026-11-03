<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration;

use Contatoseguro\TesteBackend\Config\DB;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function testConnectsToMysqlAndExecutesAQuery(): void
    {
        $pdo = DB::createConnection(true);

        $this->assertSame('mysql', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame(1, $pdo->query('SELECT 1')->fetchColumn());
    }
}
