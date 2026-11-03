<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class AdminPermissionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('categories.sql');
    }

    /** @dataProvider protectedRoutes */
    public function testEveryRouteRequiresTheAdminHeader(string $method, string $path, ?array $body): void
    {
        $missing = $this->request($method, $path, $body, adminUserId: null);

        $this->assertSame(400, $missing->getStatusCode());
        $this->assertSame('application/json', $missing->getHeaderLine('Content-Type'));
        $this->assertArrayHasKey('error', $this->responseData($missing));
    }

    public function testUnknownAndUnassignedAdminsAreRejected(): void
    {
        $unknown = $this->request('GET', '/products', adminUserId: '999');
        $unassigned = $this->request('GET', '/products', adminUserId: '10');

        $this->assertSame(403, $unknown->getStatusCode());
        $this->assertSame(403, $unassigned->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($unknown));
        $this->assertArrayHasKey('error', $this->responseData($unassigned));
    }

    public function protectedRoutes(): array
    {
        $category = ['title' => 'Category', 'active' => true];
        $product = ['company_id' => 42, 'title' => 'Product', 'price' => 10, 'active' => true, 'category_id' => 1];

        return [
            'list categories' => ['GET', '/categories', null],
            'category details' => ['GET', '/categories/2', null],
            'create category' => ['POST', '/categories', $category],
            'create translations' => [
                'POST',
                '/categories/2',
                ['translations' => [['lang_code' => 'pt', 'label' => 'Móveis']]]
            ],
            'update category' => ['PUT', '/categories/2', $category],
            'delete category' => ['DELETE', '/categories/2', null],
            'list products' => ['GET', '/products', null],
            'product details' => ['GET', '/products/1', null],
            'create product' => ['POST', '/products', $product],
            'update product' => ['PUT', '/products/1', $product],
            'delete product' => ['DELETE', '/products/1', null],
            'list comments' => ['GET', '/products/1/comments', null],
            'create comment' => ['POST', '/products/1/comments', ['content' => 'Comment']],
            'reply to comment' => ['POST', '/products/1/comments/1/replies', ['content' => 'Reply']],
            'delete comment' => ['DELETE', '/products/1/comments/1', null],
            'like comment' => ['POST', '/products/1/comments/1/likes', null],
            'list companies' => ['GET', '/companies', null],
            'company details' => ['GET', '/companies/42', null],
            'report' => ['GET', '/report', null],
        ];
    }

    /** @dataProvider invalidAdminIds */
    public function testInvalidAdminHeadersReturnBadRequest(string $adminUserId): void
    {
        $response = $this->request('GET', '/categories', adminUserId: $adminUserId);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($response));
    }

    public function invalidAdminIds(): array
    {
        return [
            'decimal' => ['1.5'],
            'zero' => ['0'],
            'multiple values' => ['7,8'],
        ];
    }
}
