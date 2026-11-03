<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;
use Contatoseguro\TesteBackend\Model\Category;
use Contatoseguro\TesteBackend\Repository\CategoryRepository;
use Contatoseguro\TesteBackend\Service\CategoryService;

class CategoryControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('categories.sql');
    }

    public function testCreatePreservesQuotesAndSqlLikeText(): void
    {
        $title = "O'Reilly'); DROP TABLE category; --";

        $response = $this->request('POST', '/categories', ['title' => $title, 'active' => true]);

        $this->assertSame(200, $response->getStatusCode());
        $id = $this->pdo->lastInsertId();
        $categories = $this->responseData($this->request('GET', '/categories/' . $id));
        $this->assertSame($title, $categories[0]['title']);
        $this->assertSame(42, $categories[0]['company_id']);
    }

    public function testUpdatePreservesQuotesAndAcceptsInactiveState(): void
    {
        $response = $this->request('PUT', '/categories/2', ['title' => "Updated O'Reilly", 'active' => false]);

        $this->assertSame(200, $response->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories'));
        $category = array_column($categories, null, 'id')[2];
        $this->assertSame("Updated O'Reilly", $category['title']);
        $this->assertSame(0, $category['active']);
    }

    public function testDeleteRemovesAnOwnedCategory(): void
    {
        $response = $this->request('DELETE', '/categories/2');

        $this->assertSame(200, $response->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories'));
        $this->assertNotContains(2, array_column($categories, 'id'));
        $this->assertSame([], $this->responseData($this->request('GET', '/categories/2')));
    }

    public function testListIncludesAllSharedCategoriesForBothCompanies(): void
    {
        $this->pdo->exec("
            INSERT INTO category (id, company_id, title, active) VALUES
                (5, NULL, 'clothing', 1), (6, NULL, 'phone', 1), (7, NULL, 'computer', 1)
        ");

        $ownCategories = $this->responseData($this->request('GET', '/categories', adminUserId: '7'));
        $otherCategories = $this->responseData($this->request('GET', '/categories', adminUserId: '9'));

        $this->assertSame(
            ['house', 'furniture', 'inactive category', 'clothing', 'phone', 'computer'],
            array_column($ownCategories, 'title')
        );
        $this->assertSame(
            ['house', 'foreign category', 'clothing', 'phone', 'computer'],
            array_column($otherCategories, 'title')
        );
    }

    /** @dataProvider activeValues */
    public function testCreateBindsActiveValuesAsIntegers(bool|int $active): void
    {
        $response = $this->request('POST', '/categories', ['title' => 'New category', 'active' => $active]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame((int)$active, (int)$this->pdo->query('SELECT active FROM category ORDER BY id DESC LIMIT 1')->fetchColumn());
    }

    public function activeValues(): array
    {
        return ['true' => [true], 'false' => [false], 'one' => [1], 'zero' => [0]];
    }

    /** @dataProvider nonWritableCategories */
    public function testWritesCannotModifySharedForeignOrMissingCategories(string $id): void
    {
        $before = $this->pdo->query('SELECT * FROM category ORDER BY id')->fetchAll();

        $updated = $this->request('PUT', '/categories/' . $id, ['title' => 'Changed', 'active' => true]);
        $deleted = $this->request('DELETE', '/categories/' . $id);

        $this->assertSame(404, $updated->getStatusCode());
        $this->assertSame(404, $deleted->getStatusCode());
        $this->assertEquals($before, $this->pdo->query('SELECT * FROM category ORDER BY id')->fetchAll());
    }

    public function nonWritableCategories(): array
    {
        return ['shared' => ['1'], 'foreign' => ['3'], 'missing' => ['999']];
    }

    public function testSingleCategoryReadRespectsVisibility(): void
    {
        $shared = $this->responseData($this->request('GET', '/categories/1'));
        $owned = $this->responseData($this->request('GET', '/categories/2'));

        $this->assertSame('house', $shared[0]['title']);
        $this->assertSame('furniture', $owned[0]['title']);
        $this->assertSame([], $this->responseData($this->request('GET', '/categories/3')));
        $this->assertSame([], $this->responseData($this->request('GET', '/categories/4')));
    }

    public function testProductCategoriesCannotBeReadForAnotherCompany(): void
    {
        $service = new CategoryService();

        $this->assertSame(['house', 'furniture'], array_column($service->getProductCategory(1, 7), 'title'));
        $this->assertSame([], $service->getProductCategory(2, 7));
    }

    public function testRepositoryReturnsCategoryModelsAndAssociativeJoinRows(): void
    {
        $repository = new CategoryRepository();
        $categories = $repository->findAllForCompany(42);

        $this->assertCount(3, $categories);
        $this->assertContainsOnlyInstancesOf(Category::class, $categories);
        $this->assertSame(1, $categories[0]->id);
        $this->assertNull($categories[0]->company_id);
        $this->assertSame(42, $categories[1]->company_id);
        $this->assertSame(0, $categories[2]->active);
        $category = $repository->findOneForCompany(2, 42)[0];
        $this->assertInstanceOf(Category::class, $category);
        $this->assertSame('furniture', $category->title);
        $this->assertSame(
            [['id' => 1, 'title' => 'house'], ['id' => 2, 'title' => 'furniture']],
            $repository->findByProduct(1, 42)
        );
    }

    /** @dataProvider protectedRoutes */
    public function testEveryRouteRequiresAnAdminHeader(string $method, string $path): void
    {
        $response = $this->request($method, $path, adminUserId: null);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertArrayHasKey('error', $this->responseData($response));
    }

    /** @dataProvider protectedRoutes */
    public function testEveryRouteRejectsUnknownOrCompanylessAdmins(string $method, string $path): void
    {
        $unknown = $this->request($method, $path, adminUserId: '999');
        $unassigned = $this->request($method, $path, adminUserId: '10');

        $this->assertSame(403, $unknown->getStatusCode());
        $this->assertSame(403, $unassigned->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($unknown));
        $this->assertArrayHasKey('error', $this->responseData($unassigned));
    }

    public function protectedRoutes(): array
    {
        return [
            'list' => ['GET', '/categories'],
            'details' => ['GET', '/categories/2'],
            'create' => ['POST', '/categories'],
            'update' => ['PUT', '/categories/2'],
            'delete' => ['DELETE', '/categories/2'],
        ];
    }

    /** @dataProvider invalidAdminIds */
    public function testAdminHeaderRejectsNonIntegerValues(string $adminUserId): void
    {
        $response = $this->request('GET', '/categories', adminUserId: $adminUserId);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function invalidAdminIds(): array
    {
        return [
            'decimal' => ['1.5'], 'scientific notation' => ['1e2'],
            'zero' => ['0'], 'negative' => ['-1'], 'SQL text' => ['7 OR 1=1'],
        ];
    }

    public function testCategoryIdsMustBePositiveIntegers(): void
    {
        $read = $this->request('GET', '/categories/2 OR 1=1');
        $updated = $this->request('PUT', '/categories/2 OR 1=1', ['title' => 'Category', 'active' => true]);
        $deleted = $this->request('DELETE', '/categories/2 OR 1=1');

        $this->assertSame(400, $read->getStatusCode());
        $this->assertSame(400, $updated->getStatusCode());
        $this->assertSame(400, $deleted->getStatusCode());
    }

    /** @dataProvider invalidBodies */
    public function testInvalidWriteBodiesReturnBadRequest(?array $body): void
    {
        $created = $this->request('POST', '/categories', $body);
        $updated = $this->request('PUT', '/categories/2', $body);

        $this->assertSame(400, $created->getStatusCode());
        $this->assertSame(400, $updated->getStatusCode());
        $this->assertSame(4, (int)$this->pdo->query('SELECT COUNT(*) FROM category')->fetchColumn());
    }

    public function invalidBodies(): array
    {
        return [
            'missing body' => [null],
            'empty body' => [[]],
            'blank title' => [['title' => ' ', 'active' => true]],
            'invalid title type' => [['title' => [], 'active' => true]],
            'missing active' => [['title' => 'Valid title']],
            'invalid active type' => [['title' => 'Valid title', 'active' => 'false']],
        ];
    }
}
