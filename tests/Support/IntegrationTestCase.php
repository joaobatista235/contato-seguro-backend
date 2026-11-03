<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Support;

use Contatoseguro\TesteBackend\Config\DB;
use PDO;
use Phinx\Db\Adapter\SQLiteAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

abstract class IntegrationTestCase extends TestCase
{
    private PDO $previousConnection;
    protected PDO $pdo;
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousConnection = DB::connect();
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        DB::$pdo = $this->pdo;
        $this->loadFixture('schema.sql');
        $this->applyMigrations();

        $app = AppFactory::create();
        require dirname(__DIR__, 2) . '/src/Config/middlewares.php';
        require dirname(__DIR__, 2) . '/src/Config/routes.php';
        $this->app = $app;
    }

    protected function tearDown(): void
    {
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
    private function applyMigrations(): void
    {
        require_once dirname(__DIR__, 2) . '/db/migrations/20261102000000_prevent_product_id_reuse.php';
        require_once dirname(__DIR__, 2) . '/db/migrations/20261103000000_add_product_stock.php';
        require_once dirname(__DIR__, 2) . '/db/migrations/20261103010000_add_category_translations.php';
        require_once dirname(__DIR__, 2) . '/db/migrations/20261103020000_add_product_comments.php';
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        try {
            $adapter = new SQLiteAdapter(['memory' => true]);
            $adapter->setConnection($this->pdo);
            $migrations = [
                new \PreventProductIdReuse('testing', 20261102000000),
                new \AddProductStock('testing', 20261103000000),
                new \AddCategoryTranslations('testing', 20261103010000),
                new \AddProductComments('testing', 20261103020000),
            ];

            foreach ($migrations as $migration) {
                $migration->setAdapter($adapter);
                $migration->up();
            }
        } finally {
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
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
