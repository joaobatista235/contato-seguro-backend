<?php

namespace ContatoSeguro\Tests\Integration\Controller;

use Contatoseguro\TesteBackend\Config\DB;
use Contatoseguro\TesteBackend\Controller\ProductController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class ProductControllerTest extends TestCase
{
    private \PDO $previousConnection;

    protected function setUp(): void
    {
        $this->previousConnection = DB::connect();
        DB::$pdo = new \PDO('sqlite::memory:', null, null, [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        DB::$pdo->exec("
            CREATE TABLE admin_user (id INTEGER PRIMARY KEY, company_id INTEGER);
            CREATE TABLE product (
                id INTEGER PRIMARY KEY, company_id INTEGER, title TEXT,
                price REAL, active INTEGER, created_at TEXT
            );
            CREATE TABLE category (
                id INTEGER PRIMARY KEY, company_id INTEGER, title TEXT, active INTEGER
            );
            CREATE TABLE product_category (id INTEGER PRIMARY KEY, product_id INTEGER, cat_id INTEGER);

            INSERT INTO admin_user VALUES (7, 42);
            INSERT INTO product VALUES
                (1, 42, 'king size bed', 999.99, 1, '2023-12-01'),
                (2, 42, 'uncategorized product', 10, 1, '2023-12-01'),
                (3, 42, 'iphone 8', 399.99, 1, '2023-12-01'),
                (4, 77, 'another company product', 100, 1, '2023-12-01');
            INSERT INTO category VALUES
                (1, NULL, 'house', 1),
                (2, 42, 'furniture', 1),
                (3, 77, 'another company category', 1),
                (4, 42, 'inactive category', 0),
                (5, NULL, 'phone', 1);
            INSERT INTO product_category VALUES
                (11, 1, 2), (12, 1, 1), (13, 1, 3), (14, 1, 4), (15, 3, 5),
                (16, 4, 1), (17, 1, 2);
        ");
    }

    protected function tearDown(): void
    {
        DB::$pdo = $this->previousConnection;
    }

    /** @dataProvider productCategories */
    public function testGetOneReturnsAllAvailableCategories(int $productId, array $expected): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/products/' . $productId)
            ->withHeader('admin_user_id', '7');

        $response = (new ProductController())->getOne($request, new Response(), ['id' => $productId]);
        $product = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected, $product['category']);
    }

    public function productCategories(): array
    {
        return [
            'shared and company categories, excluding foreign and inactive' => [1, ['house', 'furniture']],
            'no categories' => [2, []],
            'single shared category' => [3, ['phone']],
        ];
    }

    public function testGetAllGroupsCategoriesAndKeepsProductsWithoutCategories(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/products')
            ->withHeader('admin_user_id', '7');

        $response = (new ProductController())->getAll($request, new Response(), []);
        $products = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([1, 2, 3], array_column($products, 'id'));
        $this->assertSame(['house', 'furniture'], $products[0]['category']);
        $this->assertSame([], $products[1]['category']);
        $this->assertSame(['phone'], $products[2]['category']);
        $this->assertArrayNotHasKey('category_id', $products[0]);
    }

    public function testGetAllReturnsAnEmptyListForAnUnknownAdmin(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/products')
            ->withHeader('admin_user_id', '999');

        $response = (new ProductController())->getAll($request, new Response(), []);

        $this->assertSame('[]', (string)$response->getBody());
    }
}
