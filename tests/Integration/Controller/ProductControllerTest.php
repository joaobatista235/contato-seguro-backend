<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class ProductControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('products.sql');
    }

    /** @dataProvider productCategories */
    public function testGetOneReturnsAllAvailableCategories(int $productId, array $expected): void
    {
        $response = $this->request('GET', '/products/' . $productId);
        $product = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected, $product['category']);
    }

    public function productCategories(): array
    {
        return [
            'multiple categories' => [1, ['house', 'furniture']],
            'no categories' => [2, []],
            'single shared category' => [3, ['phone']],
        ];
    }

    public function testGetAllGroupsCategoriesAndKeepsProductsWithoutCategories(): void
    {
        $response = $this->request('GET', '/products');
        $products = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([1, 2, 3], array_column($products, 'id'));
        $this->assertSame(['house', 'furniture'], $products[0]['category']);
        $this->assertSame([], $products[1]['category']);
        $this->assertSame(['phone'], $products[2]['category']);
        $this->assertArrayNotHasKey('category_id', $products[0]);
    }

    public function testGetAllReturnsAnEmptyListForAnUnknownAdmin(): void
    {
        $response = $this->request('GET', '/products', adminUserId: '999');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $this->responseData($response));
    }
}
