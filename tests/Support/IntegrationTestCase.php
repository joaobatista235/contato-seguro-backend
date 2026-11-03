<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Support;

use Contatoseguro\TesteBackend\Config\DB;
use PDO;
use Phinx\Db\Adapter\MysqlAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

abstract class IntegrationTestCase extends TestCase
{
    private static bool $schemaReady = false;
    private static ?PDO $testConnection = null;
    private PDO $previousConnection;
    protected PDO $pdo;
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousConnection = DB::connect();
        if (self::$testConnection === null) {
            self::$testConnection = DB::createConnection(true);
        }

        $this->pdo = self::$testConnection;
        DB::$pdo = $this->pdo;
        $this->prepareDatabase();

        $app = AppFactory::create();
        require dirname(__DIR__, 2) . '/src/Config/middlewares.php';
        require dirname(__DIR__, 2) . '/src/Config/routes.php';
        $this->app = $app;
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        DB::$pdo = $this->previousConnection;

        parent::tearDown();
    }

    /**
     * @param string $filename
     * @return void
     */
    protected function loadFixture(string $filename): void
    {
        $sql = file_get_contents(dirname(__DIR__) . '/Fixtures/' . $filename);

        if ($sql === false) {
            throw new \RuntimeException('Could not read test fixture: ' . $filename);
        }

        $this->pdo->exec($sql);
    }

    /**
     * @return void
     */
    private function prepareDatabase(): void
    {
        $tables = [
            'product_comment_like', 'product_comment', 'category_translation', 'product_log',
            'product_category', 'product', 'category', 'admin_user', 'company',
        ];

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tables as $table) {
                $query = self::$schemaReady ? "TRUNCATE TABLE {$table}" : "DROP TABLE IF EXISTS {$table}";
                $this->pdo->exec($query);
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        if (!self::$schemaReady) {
            require_once dirname(__DIR__, 2) . '/db/migrations/20261103030000_create_mysql_schema.php';
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            try {
                $adapter = new MysqlAdapter(['name' => getenv('DB_TEST_DATABASE') ?: 'backend_test']);
                $adapter->setConnection($this->pdo);
                $migration = new \CreateMysqlSchema('testing', 20261103030000);
                $migration->setAdapter($adapter);
                $migration->up();
                self::$schemaReady = true;
            } finally {
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            }
        }

        $triggers = $this->pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC);

        foreach ($triggers as $trigger) {
            $name = str_replace('`', '``', $trigger['Trigger']);
            $this->pdo->exec("DROP TRIGGER `{$name}`");
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<string, mixed>|null $body
     * @param string|null $adminUserId
     * @return ResponseInterface
     */
    protected function request(
        string $method,
        string $path,
        ?array $body = null,
        ?string $adminUserId = '7'
    ): ResponseInterface {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path);

        if ($adminUserId !== null) {
            $request = $request->withHeader('admin_user_id', $adminUserId);
        }

        if ($body !== null) {
            $stream = (new StreamFactory())->createStream(json_encode($body, JSON_THROW_ON_ERROR));
            $request = $request->withHeader('Content-Type', 'application/json')->withBody($stream);
        }

        return $this->app->handle($request);
    }

    /**
     * @param ResponseInterface $response
     * @return array<array-key, mixed>
     */
    protected function responseData(ResponseInterface $response): array
    {
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
