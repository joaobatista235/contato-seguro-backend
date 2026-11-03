<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class ProductFiltersTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('product_filters.sql');
    }

    public function testWithoutFiltersKeepsTheOriginalListingBehavior(): void
    {
        $response = $this->request('GET', '/products');
        $products = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([1, 2, 3, 4, 5], array_column($products, 'id'));
        $this->assertSame([1, 0, 1, 1, 0], array_column($products, 'active'));
        $this->assertSame([], $products[4]['category']);
    }

    /** @dataProvider activeFilters */
    public function testFiltersActiveAndInactiveProducts(string $value, array $expectedIds): void
    {
        $response = $this->request('GET', '/products?active=' . $value);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedIds, array_column($this->responseData($response), 'id'));
    }

    public function activeFilters(): array
    {
        return [
            'active integer' => ['1', [1, 3, 4]],
            'active boolean' => ['true', [1, 3, 4]],
            'inactive integer' => ['0', [2, 5]],
            'inactive boolean' => ['false', [2, 5]],
        ];
    }

    public function testCategoryFilterKeepsAllCategoriesAndAvoidsDuplicateProducts(): void
    {
        $response = $this->request('GET', '/products?category_id=2');
        $products = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([1, 3], array_column($products, 'id'));
        $this->assertSame(['house', 'furniture'], $products[0]['category']);
        $this->assertSame(['furniture'], $products[1]['category']);
    }

    /** @dataProvider unavailableCategories */
    public function testUnavailableCategoryFiltersReturnAnEmptyList(string $id): void
    {
        $response = $this->request('GET', '/products?category_id=' . $id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $this->responseData($response));
    }

    public function unavailableCategories(): array
    {
        return ['foreign' => ['3'], 'inactive' => ['4'], 'missing' => ['999']];
    }

    /** @dataProvider dateOrders */
    public function testOrdersByCreationDateWithStableTies(string $order, array $expectedIds): void
    {
        $response = $this->request('GET', '/products?order=' . $order);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedIds, array_column($this->responseData($response), 'id'));
    }

    public function dateOrders(): array
    {
        return ['oldest first' => ['asc', [1, 3, 4, 2, 5]], 'newest first' => ['DESC', [5, 2, 3, 4, 1]]];
    }

    public function testCombinesFiltersAndOrderingWithoutLeakingOtherCompanies(): void
    {
        $response = $this->request('GET', '/products?active=true&category_id=2&order=desc');
        $products = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([3, 1], array_column($products, 'id'));
        $this->assertSame(['house', 'furniture'], $products[1]['category']);

        $shared = $this->request('GET', '/products?active=1&category_id=1&order=desc');
        $other = $this->request('GET', '/products?active=1&category_id=3&order=desc', adminUserId: '9');

        $this->assertSame([1], array_column($this->responseData($shared), 'id'));
        $this->assertSame([6], array_column($this->responseData($other), 'id'));
    }

    public function testValidFiltersWithNoMatchesReturnAnEmptyList(): void
    {
        $response = $this->request('GET', '/products?active=0&category_id=2&order=asc');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $this->responseData($response));
    }

    /** @dataProvider invalidQueries */
    public function testInvalidFiltersReturnBadRequestWithoutChangingProducts(string $query): void
    {
        $before = $this->responseData($this->request('GET', '/products'));

        $response = $this->request('GET', '/products?' . $query);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($response));
        $this->assertSame($before, $this->responseData($this->request('GET', '/products')));
    }

    public function invalidQueries(): array
    {
        return [
            'invalid active' => ['active=2'],
            'empty active' => ['active='],
            'array active' => ['active[]=1'],
            'zero category' => ['category_id=0'],
            'decimal category' => ['category_id=1.5'],
            'array category' => ['category_id[]=1'],
            'SQL category' => ['category_id=1%20OR%201=1'],
            'invalid order' => ['order=random'],
            'array order' => ['order[]=desc'],
            'SQL order' => ['order=desc%3B%20DROP%20TABLE%20product'],
        ];
    }
}
