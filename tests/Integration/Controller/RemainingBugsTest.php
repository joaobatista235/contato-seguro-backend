<?php

namespace ContatoSeguro\Tests\Integration\Controller;

use Contatoseguro\TesteBackend\Config\DB;
use Contatoseguro\TesteBackend\Controller\CategoryController;
use Contatoseguro\TesteBackend\Controller\ReportController;
use Contatoseguro\TesteBackend\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class RemainingBugsTest extends TestCase
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
            CREATE TABLE company (id INTEGER PRIMARY KEY, name TEXT);
            CREATE TABLE admin_user (id INTEGER PRIMARY KEY, company_id INTEGER, name TEXT);
            CREATE TABLE product (
                id INTEGER PRIMARY KEY, company_id INTEGER, title TEXT,
                price REAL, active INTEGER, created_at TEXT
            );
            CREATE TABLE category (
                id INTEGER PRIMARY KEY, company_id INTEGER, title TEXT, active INTEGER
            );
            CREATE TABLE product_category (id INTEGER PRIMARY KEY, product_id INTEGER, cat_id INTEGER);
            CREATE TABLE product_log (
                id INTEGER PRIMARY KEY, product_id INTEGER, admin_user_id INTEGER,
                action TEXT, timestamp TEXT
            );

            INSERT INTO company VALUES (42, '<Acme>'), (77, 'Another company');
            INSERT INTO admin_user VALUES (7, 42, 'Alice <admin>'), (8, 42, 'Bob'), (9, 77, 'Eve');
            INSERT INTO product VALUES
                (1, 42, '<script>unsafe</script>', 999.99, 1, '2023-12-01'),
                (2, 42, 'Product without logs', 10, 1, '2023-12-01'),
                (3, 77, 'Foreign product', 100, 1, '2023-12-01');
            INSERT INTO category VALUES
                (1, NULL, 'clothing', 1), (2, NULL, 'phone', 1),
                (3, NULL, 'computer', 1), (4, NULL, 'house', 1),
                (5, 42, 'furniture', 1), (6, 77, 'foreign category', 1);
            INSERT INTO product_category VALUES (1, 1, 4), (2, 1, 5);
            INSERT INTO product_log VALUES
                (20, 1, 7, 'update', '2023-12-22 18:12:10'),
                (10, 1, 8, 'create', '2023-12-20 21:32:22'),
                (21, 1, 99, 'delete', '2023-12-22 18:12:10');
        ");
    }

    protected function tearDown(): void
    {
        DB::$pdo = $this->previousConnection;
    }

    /** @dataProvider companyCategories */
    public function testCategoryListIncludesSharedAndCompanyCategories(string $adminId, array $expected): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/categories')
            ->withHeader('admin_user_id', $adminId);

        $response = (new CategoryController())->getAll($request, new Response(), []);
        $categories = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected, array_column($categories, 'title'));
    }

    public function companyCategories(): array
    {
        return [
            'first company' => ['7', ['clothing', 'phone', 'computer', 'house', 'furniture']],
            'second company' => ['9', ['clothing', 'phone', 'computer', 'house', 'foreign category']],
            'unknown admin' => ['999', []],
        ];
    }

    public function testLogsAreFormattedInChronologicalOrderIncludingMissingUsers(): void
    {
        $logs = (new ProductService())->getFormattedLogs(1);

        $this->assertSame(
            "(Bob, Criação, 20/12/2023 21:32:22),\n"
            . "(Alice <admin>, Atualização, 22/12/2023 18:12:10),\n"
            . '(Usuário #99, Remoção, 22/12/2023 18:12:10)',
            $logs
        );
    }

    public function testProductsWithoutLogsHaveAnEmptyLogColumn(): void
    {
        $this->assertSame('', (new ProductService())->getFormattedLogs(2));
    }

    public function testReportRendersLogsAndCategoriesAsEscapedHtml(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/report')
            ->withHeader('admin_user_id', '7');

        $response = (new ReportController())->generate($request, new Response(), []);
        $html = (string)$response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('house, furniture', $html);
        $this->assertStringContainsString('(Bob, Criação, 20/12/2023 21:32:22)', $html);
        $this->assertStringContainsString('(Alice &lt;admin&gt;, Atualização, 22/12/2023 18:12:10)', $html);
        $this->assertStringContainsString('(Usuário #99, Remoção, 22/12/2023 18:12:10)', $html);
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;Acme&gt;', $html);
        $this->assertStringContainsString('Product without logs', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('Foreign product', $html);
    }
}
