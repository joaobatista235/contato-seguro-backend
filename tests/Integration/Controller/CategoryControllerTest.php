<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class CategoryControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('categories.sql');
    }

    public function testCreatePreservesTheTitleAndUsesTheAdminsCompany(): void
    {
        $title = "O'Reilly'); DROP TABLE category; --";

        $created = $this->request('POST', '/categories', ['title' => $title, 'active' => true]);
        $this->assertSame(200, $created->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories'));
        $category = array_column($categories, null, 'title')[$title];
        $details = $this->request('GET', '/categories/' . $category['id']);

        $this->assertSame(200, $details->getStatusCode());
        $this->assertSame($title, $this->responseData($details)[0]['title']);
        $this->assertSame(42, $category['company_id']);
    }

    public function testUpdateChangesTheTitleAndCanDeactivateACategory(): void
    {
        $response = $this->request('PUT', '/categories/2', ['title' => "Updated O'Reilly", 'active' => false]);
        $categories = $this->responseData($this->request('GET', '/categories'));
        $category = array_column($categories, null, 'id')[2];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("Updated O'Reilly", $category['title']);
        $this->assertSame(0, $category['active']);
    }

    public function testDeletingAndRecreatingACategoryDoesNotReassignProducts(): void
    {
        $created = $this->request('POST', '/categories', ['title' => 'Old category', 'active' => true]);
        $this->assertSame(200, $created->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories'));
        $category = array_column($categories, null, 'title')['Old category'];
        $productCreated = $this->request('POST', '/products', [
            'company_id' => 42, 'title' => 'Product using old category', 'price' => 10,
            'active' => true, 'category_id' => $category['id'],
        ]);
        $this->assertSame(200, $productCreated->getStatusCode());
        $products = $this->responseData($this->request('GET', '/products'));
        $productId = array_column($products, null, 'title')['Product using old category']['id'];

        $deleted = $this->request('DELETE', '/categories/' . $category['id']);
        $missing = $this->request('GET', '/categories/' . $category['id']);
        $afterDeletion = $this->responseData($this->request('GET', '/categories'));
        $new = $this->request('POST', '/categories', ['title' => 'New category', 'active' => true]);
        $product = $this->responseData($this->request('GET', '/products/' . $productId));

        $this->assertSame(200, $deleted->getStatusCode());
        $this->assertSame([], $this->responseData($missing));
        $this->assertNotContains('Old category', array_column($afterDeletion, 'title'));
        $this->assertSame(200, $new->getStatusCode());
        $this->assertSame([], $product['category']);
    }

    public function testFailedDeletionKeepsTheCategoryAndItsProductsUnchanged(): void
    {
        $translation = $this->request('POST', '/categories/2', ['translations' => [
            ['lang_code' => 'en', 'label' => 'Translated furniture'],
        ]]);
        $this->assertSame(200, $translation->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories'));
        $products = $this->responseData($this->request('GET', '/products'));
        $this->pdo->exec("CREATE TRIGGER reject_category_delete BEFORE DELETE ON category FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'category delete failed';
            END");

        $response = $this->request('DELETE', '/categories/2');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], $this->responseData($response));
        $this->assertSame($categories, $this->responseData($this->request('GET', '/categories')));
        $this->assertSame($products, $this->responseData($this->request('GET', '/products')));
    }

    public function testListIncludesSharedCategoriesAndOnlyTheOwnCompanyCategories(): void
    {
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
    public function testCreateAcceptsTheSupportedActiveValues(bool|int $active): void
    {
        $response = $this->request('POST', '/categories', ['title' => 'New category', 'active' => $active]);
        $categories = $this->responseData($this->request('GET', '/categories'));
        $category = array_column($categories, null, 'title')['New category'];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame((int)$active, $category['active']);
    }

    public function activeValues(): array
    {
        return ['true' => [true], 'false' => [false], 'one' => [1], 'zero' => [0]];
    }

    /** @dataProvider nonWritableCategories */
    public function testWritesCannotModifySharedForeignOrMissingCategories(string $id): void
    {
        $ownCategories = $this->responseData($this->request('GET', '/categories'));
        $foreignCategories = $this->responseData($this->request('GET', '/categories', adminUserId: '9'));
        $products = $this->responseData($this->request('GET', '/products'));

        $updated = $this->request('PUT', '/categories/' . $id, ['title' => 'Changed', 'active' => true]);
        $deleted = $this->request('DELETE', '/categories/' . $id);

        $this->assertSame(404, $updated->getStatusCode());
        $this->assertSame(404, $deleted->getStatusCode());
        $this->assertSame($ownCategories, $this->responseData($this->request('GET', '/categories')));
        $this->assertSame(
            $foreignCategories,
            $this->responseData($this->request('GET', '/categories', adminUserId: '9'))
        );
        $this->assertSame($products, $this->responseData($this->request('GET', '/products')));
    }

    public function nonWritableCategories(): array
    {
        return ['shared' => ['1'], 'foreign' => ['3'], 'missing' => ['999']];
    }

    public function testSingleCategoryReadRespectsVisibilityAndResponseFields(): void
    {
        $shared = $this->responseData($this->request('GET', '/categories/1'));
        $owned = $this->responseData($this->request('GET', '/categories/2'));

        $this->assertSame(['id' => 1, 'company_id' => null, 'title' => 'house', 'active' => 1], $shared[0]);
        $this->assertSame(['id' => 2, 'company_id' => 42, 'title' => 'furniture', 'active' => 1], $owned[0]);
        $this->assertSame([], $this->responseData($this->request('GET', '/categories/3')));
        $this->assertSame([], $this->responseData($this->request('GET', '/categories/4')));
        $this->assertSame([], $this->responseData($this->request('GET', '/categories/999')));
    }

    public function testInvalidCategoryIdsReturnBadRequest(): void
    {
        $read = $this->request('GET', '/categories/2 OR 1=1');
        $updated = $this->request('PUT', '/categories/2 OR 1=1', ['title' => 'Category', 'active' => true]);
        $deleted = $this->request('DELETE', '/categories/2 OR 1=1');

        $this->assertSame(400, $read->getStatusCode());
        $this->assertSame(400, $updated->getStatusCode());
        $this->assertSame(400, $deleted->getStatusCode());
    }

    /** @dataProvider invalidBodies */
    public function testInvalidBodiesReturnBadRequestWithoutChangingCategories(?array $body): void
    {
        $before = $this->responseData($this->request('GET', '/categories'));

        $created = $this->request('POST', '/categories', $body);
        $updated = $this->request('PUT', '/categories/2', $body);

        $this->assertSame(400, $created->getStatusCode());
        $this->assertSame(400, $updated->getStatusCode());
        $this->assertSame($before, $this->responseData($this->request('GET', '/categories')));
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
