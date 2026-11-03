<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class ProductStockTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('product_filters.sql');
        $this->loadFixture('product_stock.sql');
    }

    /** @dataProvider initialStocks */
    public function testCreatesProductsWithOptionalStock(array $fields, int $expected): void
    {
        $body = array_replace([
            'company_id' => 42, 'title' => 'New product', 'price' => 10,
            'active' => true, 'category_id' => 1,
        ], $fields);

        $response = $this->request('POST', '/products', $body);
        $this->assertSame(200, $response->getStatusCode());
        $products = $this->responseData($this->request('GET', '/products'));
        $created = array_column($products, null, 'title')['New product'];
        $detail = $this->responseData($this->request('GET', '/products/' . $created['id']));

        $this->assertSame($expected, $created['stock']);
        $this->assertSame($expected, $detail['stock']);
    }

    public function initialStocks(): array
    {
        return ['omitted' => [[], 0], 'zero' => [['stock' => 0], 0], 'positive' => [['stock' => 12], 12]];
    }

    /** @dataProvider updatedStocks */
    public function testUpdatesStockOrPreservesItWhenOmitted(array $fields, int $expected): void
    {
        $body = array_replace([
            'company_id' => 42, 'title' => 'Updated product', 'price' => 20,
            'active' => true, 'category_id' => 2,
        ], $fields);

        $response = $this->request('PUT', '/products/3', $body);
        $detail = $this->responseData($this->request('GET', '/products/3'));
        $products = $this->responseData($this->request('GET', '/products'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected, $detail['stock']);
        $this->assertSame($expected, array_column($products, null, 'id')[3]['stock']);
    }

    public function updatedStocks(): array
    {
        return ['omitted' => [[], 10], 'reset to zero' => [['stock' => 0], 0], 'replace' => [['stock' => 12], 12]];
    }

    /** @dataProvider minimumStocks */
    public function testFiltersByInclusiveMinimumStockWithinTheCompany(int $minimum, array $expectedIds): void
    {
        $response = $this->request('GET', '/products?min_stock=' . $minimum);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedIds, array_column($this->responseData($response), 'id'));
    }

    public function minimumStocks(): array
    {
        return [
            'zero' => [0, [1, 2, 3, 4, 5]],
            'inclusive' => [5, [2, 3, 4]],
            'no matches' => [11, []]
        ];
    }

    public function testCombinesMinimumStockWithOtherFiltersAndKeepsAllCategories(): void
    {
        $response = $this->request('GET', '/products?min_stock=5&active=1&category_id=2&order=desc');
        $products = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([3], array_column($products, 'id'));
        $this->assertSame(['furniture'], $products[0]['category']);

        $shared = $this->responseData($this->request('GET', '/products?min_stock=0&category_id=2'));
        $this->assertSame([1, 3], array_column($shared, 'id'));
        $this->assertSame(['house', 'furniture'], $shared[0]['category']);
    }

    /** @dataProvider invalidStocks */
    public function testInvalidStockIsRejectedOnCreateAndUpdateWithoutChangingData(mixed $stock): void
    {
        $before = $this->responseData($this->request('GET', '/products'));
        $report = (string)$this->request('GET', '/report')->getBody();
        $body = [
            'company_id' => 42, 'title' => 'Changed', 'price' => 10,
            'active' => true, 'category_id' => 1, 'stock' => $stock,
        ];

        $this->assertSame(400, $this->request('POST', '/products', $body)->getStatusCode());
        $this->assertSame(400, $this->request('PUT', '/products/3', $body)->getStatusCode());
        $this->assertSame($before, $this->responseData($this->request('GET', '/products')));
        $this->assertSame($report, (string)$this->request('GET', '/report')->getBody());
    }

    public function invalidStocks(): array
    {
        return [
            'negative' => [-1],
            'decimal' => [1.5],
            'null' => [null],
            'string' => ['5'],
        ];
    }

    /** @dataProvider invalidMinimumStocks */
    public function testInvalidMinimumStockReturnsBadRequest(string $query): void
    {
        $response = $this->request('GET', '/products?' . $query);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($response));
    }

    public function invalidMinimumStocks(): array
    {
        return [
            'negative' => ['min_stock=-1'], 'decimal' => ['min_stock=1.5'],
            'array' => ['min_stock[]=5'],
        ];
    }
}
