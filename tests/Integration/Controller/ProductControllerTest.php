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
    public function testGetOneReturnsTheProductFieldsAndAvailableCategories(int $id, array $categories): void
    {
        $response = $this->request('GET', '/products/' . $id);
        $product = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($id, $product['id']);
        $this->assertSame(42, $product['companyId']);
        $this->assertSame('2023-12-01 00:00:00', $product['createdAt']);
        $this->assertSame($categories, $product['category']);
    }

    public function productCategories(): array
    {
        return [
            'multiple categories' => [1, ['house', 'furniture']],
            'no categories' => [2, []],
            'single shared category' => [3, ['phone']],
        ];
    }

    public function testListGroupsCategoriesAndOnlyReturnsTheOwnCompanyProducts(): void
    {
        $response = $this->request('GET', '/products');
        $products = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([1, 2, 3], array_column($products, 'id'));
        $this->assertSame(['house', 'furniture'], $products[0]['category']);
        $this->assertSame([], $products[1]['category']);
        $this->assertSame(['phone'], $products[2]['category']);
        $this->assertSame(42, $products[0]['company_id']);
        $this->assertSame('2023-12-01 00:00:00', $products[0]['created_at']);
        $this->assertArrayNotHasKey('category_id', $products[0]);
    }

    public function testCreatePreservesTheTitleCentsActiveStateAndAuditAuthor(): void
    {
        $title = "O'Reilly desk";
        $created = $this->request('POST', '/products', [
            'company_id' => 42, 'title' => $title, 'price' => 99.99,
            'active' => false, 'category_id' => 1,
        ]);
        $this->assertSame(200, $created->getStatusCode());
        $products = $this->responseData($this->request('GET', '/products'));
        $id = array_column($products, null, 'title')[$title]['id'];
        $product = $this->responseData($this->request('GET', '/products/' . $id));
        $report = (string)$this->request('GET', '/report')->getBody();

        $this->assertSame($id, $product['id']);
        $this->assertSame(42, $product['companyId']);
        $this->assertSame($title, $product['title']);
        $this->assertSame(99.99, $product['price']);
        $this->assertFalse($product['active']);
        $this->assertSame(['house'], $product['category']);
        $this->assertStringContainsString('(Alice, Criação,', $report);
    }

    public function testUpdateChangesProductDataCategoriesAndAuditAuthor(): void
    {
        $body = [
            'company_id' => 42, 'title' => "Updated O'Reilly", 'price' => 123.45,
            'active' => 0, 'category_id' => 5,
        ];
        $response = $this->request('PUT', '/products/1', $body);
        $repeated = $this->request('PUT', '/products/1', $body);
        $product = $this->responseData($this->request('GET', '/products/1'));
        $report = (string)$this->request('GET', '/report')->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $repeated->getStatusCode());
        $this->assertSame("Updated O'Reilly", $product['title']);
        $this->assertSame(123.45, $product['price']);
        $this->assertFalse($product['active']);
        $this->assertSame(['phone'], $product['category']);
        $this->assertStringContainsString('(Alice, Atualização,', $report);
    }

    public function testDeleteRemovesTheProductFromDetailsAndListing(): void
    {
        $response = $this->request('DELETE', '/products/1');
        $products = $this->responseData($this->request('GET', '/products'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(404, $this->request('GET', '/products/1')->getStatusCode());
        $this->assertNotContains(1, array_column($products, 'id'));
    }

    /** @dataProvider unavailableProducts */
    public function testCannotReadUpdateOrDeleteUnavailableProducts(string $id): void
    {
        $own = $this->responseData($this->request('GET', '/products'));
        $foreign = $this->responseData($this->request('GET', '/products', adminUserId: '9'));
        $report = (string)$this->request('GET', '/report')->getBody();

        $read = $this->request('GET', '/products/' . $id);
        $updated = $this->request('PUT', '/products/' . $id, [
            'company_id' => 42, 'title' => 'Changed', 'price' => 10,
            'active' => true, 'category_id' => 1, 'stock' => 12,
        ]);
        $deleted = $this->request('DELETE', '/products/' . $id);

        $this->assertSame(404, $read->getStatusCode());
        $this->assertSame(404, $updated->getStatusCode());
        $this->assertSame(404, $deleted->getStatusCode());
        $this->assertSame($own, $this->responseData($this->request('GET', '/products')));
        $this->assertSame($foreign, $this->responseData($this->request('GET', '/products', adminUserId: '9')));
        $this->assertSame($report, (string)$this->request('GET', '/report')->getBody());
    }

    public function unavailableProducts(): array
    {
        return ['foreign' => ['4'], 'missing' => ['999']];
    }

    public function testCannotCreateOrMoveProductsToAnotherCompany(): void
    {
        $own = $this->responseData($this->request('GET', '/products'));
        $foreign = $this->responseData($this->request('GET', '/products', adminUserId: '9'));
        $body = ['company_id' => 77, 'title' => 'Changed', 'price' => 10, 'active' => true, 'category_id' => 1];

        $created = $this->request('POST', '/products', $body);
        $updated = $this->request('PUT', '/products/1', $body);

        $this->assertSame(404, $created->getStatusCode());
        $this->assertSame(404, $updated->getStatusCode());
        $this->assertSame($own, $this->responseData($this->request('GET', '/products')));
        $this->assertSame($foreign, $this->responseData($this->request('GET', '/products', adminUserId: '9')));
    }

    /** @dataProvider unavailableCategories */
    public function testWritesRejectUnavailableCategoriesWithoutChangingProducts(int $categoryId): void
    {
        $products = $this->responseData($this->request('GET', '/products'));
        $report = (string)$this->request('GET', '/report')->getBody();
        $body = [
            'company_id' => 42,
            'title' => 'Changed',
            'price' => 10,
            'active' => true,
            'category_id' => $categoryId
        ];

        $created = $this->request('POST', '/products', $body);
        $updated = $this->request('PUT', '/products/1', $body);

        $this->assertSame(404, $created->getStatusCode());
        $this->assertSame(404, $updated->getStatusCode());
        $this->assertSame($products, $this->responseData($this->request('GET', '/products')));
        $this->assertSame($report, (string)$this->request('GET', '/report')->getBody());
    }

    public function unavailableCategories(): array
    {
        return ['foreign' => [3], 'inactive' => [4], 'missing' => [999]];
    }

    /** @dataProvider invalidProductFields */
    public function testInvalidFieldsReturnBadRequestWithoutChangingProducts(array $fields): void
    {
        $products = $this->responseData($this->request('GET', '/products'));
        $report = (string)$this->request('GET', '/report')->getBody();
        $body = array_replace(
            ['company_id' => 42, 'title' => 'Changed', 'price' => 10, 'active' => true, 'category_id' => 1],
            $fields
        );

        $created = $this->request('POST', '/products', $body);
        $updated = $this->request('PUT', '/products/1', $body);

        $this->assertSame(400, $created->getStatusCode());
        $this->assertSame(400, $updated->getStatusCode());
        $this->assertSame($products, $this->responseData($this->request('GET', '/products')));
        $this->assertSame($report, (string)$this->request('GET', '/report')->getBody());
    }

    public function invalidProductFields(): array
    {
        return [
            'blank title' => [['title' => ' ']],
            'negative price' => [['price' => -1]],
            'non-numeric price' => [['price' => 'abc']],
            'invalid active' => [['active' => 'false']],
            'invalid company' => [['company_id' => '42 OR 1=1']],
            'invalid category' => [['category_id' => 1.5]],
        ];
    }

    public function testMissingBodyAndInvalidIdsReturnBadRequest(): void
    {
        $this->assertSame(400, $this->request('POST', '/products')->getStatusCode());
        $this->assertSame(400, $this->request('PUT', '/products/1', [])->getStatusCode());
        $this->assertSame(400, $this->request('GET', '/products/1 OR 1=1')->getStatusCode());
        $this->assertSame(400, $this->request('PUT', '/products/1.5')->getStatusCode());
        $this->assertSame(400, $this->request('DELETE', '/products/0')->getStatusCode());
    }

    public function testPricesOutsideNumericRangeReturnBadRequest(): void
    {
        $body = <<<'JSON'
            {
                "company_id": 42,
                "title": "Product",
                "price": 1e309,
                "active": true,
                "category_id": 1
            }
            JSON;

        $this->assertSame(400, $this->request('POST', '/products', $body)->getStatusCode());
        $this->assertSame(400, $this->request('PUT', '/products/1', $body)->getStatusCode());
    }

    public function testNewProductsDoNotInheritTheIdentityOrHistoryOfDeletedProducts(): void
    {
        $body = ['company_id' => 42, 'title' => 'Old product', 'price' => 10, 'active' => true, 'category_id' => 1];
        $created = $this->request('POST', '/products', $body);
        $this->assertSame(200, $created->getStatusCode());
        $products = $this->responseData($this->request('GET', '/products'));
        $oldId = array_column($products, null, 'title')['Old product']['id'];
        $deleted = $this->request('DELETE', '/products/' . $oldId);
        $this->assertSame(200, $deleted->getStatusCode());

        $body['title'] = 'New product';
        $created = $this->request('POST', '/products', $body);
        $this->assertSame(200, $created->getStatusCode());
        $products = $this->responseData($this->request('GET', '/products'));
        $newId = array_column($products, null, 'title')['New product']['id'];
        $report = (string)$this->request('GET', '/report')->getBody();

        $this->assertNotSame($oldId, $newId);
        $this->assertSame(404, $this->request('GET', '/products/' . $oldId)->getStatusCode());
        $this->assertStringContainsString('New product', $report);
        $this->assertStringNotContainsString('Old product', $report);
        $this->assertSame(1, substr_count($report, ', Criação,'));
        $this->assertStringNotContainsString(', Remoção,', $report);
    }
}
