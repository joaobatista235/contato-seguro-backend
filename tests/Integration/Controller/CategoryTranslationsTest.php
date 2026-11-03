<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class CategoryTranslationsTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('product_filters.sql');
    }

    public function testCreatesTranslationsAndReturnsThemInCategoryAndProductQueries(): void
    {
        $response = $this->request('POST', '/categories/1', ['translations' => [
            ['lang_code' => 'en', 'label' => 'Home'],
            ['lang_code' => 'pt', 'label' => ' Casa d’água '],
            ['lang_code' => 'pt-BR', 'label' => 'Lar'],
        ]]);

        $this->assertSame(200, $response->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories?lang=pt'));
        $category = $this->responseData($this->request('GET', '/categories/1?lang=pt'));
        $products = $this->responseData($this->request('GET', '/products?lang=pt'));
        $product = $this->responseData($this->request('GET', '/products/1?lang=pt'));

        $this->assertSame('Casa d’água', $categories[0]['title']);
        $this->assertSame('Casa d’água', $category[0]['title']);
        $this->assertSame(['Casa d’água', 'furniture'], $products[0]['category']);
        $this->assertSame(['Casa d’água', 'furniture'], $product['category']);
        $regional = $this->responseData($this->request('GET', '/products/1?lang=PT-br'));
        $this->assertSame(['Lar', 'furniture'], $regional['category']);
        $this->assertSame([], $products[4]['category']);
    }

    /** @dataProvider englishFallbackQueries */
    public function testFallsBackToEnglishPerCategoryWhenLanguageIsMissingOrUnavailable(string $query): void
    {
        $this->assertSame(200, $this->request('POST', '/categories/1', ['translations' => [
            ['lang_code' => 'en', 'label' => 'Home'],
            ['lang_code' => 'pt', 'label' => 'Casa'],
        ]])->getStatusCode());

        $categories = $this->responseData($this->request('GET', '/categories' . $query));
        $category = $this->responseData($this->request('GET', '/categories/1' . $query));
        $products = $this->responseData($this->request('GET', '/products' . $query));
        $product = $this->responseData($this->request('GET', '/products/1' . $query));

        $this->assertSame('Home', $categories[0]['title']);
        $this->assertSame('furniture', $categories[1]['title']);
        $this->assertSame('Home', $category[0]['title']);
        $this->assertSame(['Home', 'furniture'], $products[0]['category']);
        $this->assertSame(['Home', 'furniture'], $product['category']);
    }

    public function englishFallbackQueries(): array
    {
        return ['omitted' => [''], 'unknown language' => ['?lang=de']];
    }

    public function testLanguageCombinesWithProductFiltersWithoutChangingCategoryIdsOrOrdering(): void
    {
        $this->assertSame(200, $this->request('POST', '/categories/2', ['translations' => [
            ['lang_code' => 'pt', 'label' => 'Móveis'],
        ]])->getStatusCode());

        $response = $this->request('GET', '/products?lang=pt&active=1&category_id=2&order=desc&min_stock=0');
        $products = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([3, 1], array_column($products, 'id'));
        $this->assertSame(['Móveis'], $products[0]['category']);
        $this->assertSame(['house', 'Móveis'], $products[1]['category']);
    }

    public function testRejectsDuplicateLanguagesInTheSameBatchWithoutSavingAnyTranslation(): void
    {
        $response = $this->request('POST', '/categories/2', ['translations' => [
            ['lang_code' => 'de', 'label' => 'Möbel'],
            ['lang_code' => 'pt', 'label' => 'Móveis'],
            ['lang_code' => 'PT', 'label' => 'Duplicada'],
        ]]);

        $this->assertSame(400, $response->getStatusCode());
        $category = $this->responseData($this->request('GET', '/categories/2?lang=de'));
        $this->assertSame('furniture', $category[0]['title']);
        $retry = $this->request('POST', '/categories/2', ['translations' => [
            ['lang_code' => 'de', 'label' => 'Möbel'],
            ['lang_code' => 'pt', 'label' => 'Móveis'],
        ]]);
        $this->assertSame(200, $retry->getStatusCode());
    }

    public function testExistingLanguageRejectsTheEntireBatchAndPreservesItsLabel(): void
    {
        $this->assertSame(200, $this->request('POST', '/categories/2', ['translations' => [
            ['lang_code' => 'pt', 'label' => 'Móveis'],
        ]])->getStatusCode());

        $response = $this->request('POST', '/categories/2', ['translations' => [
            ['lang_code' => 'de', 'label' => 'Möbel'],
            ['lang_code' => 'PT', 'label' => 'Substituição'],
        ]]);
        $this->assertSame(400, $response->getStatusCode());
        $pt = $this->responseData($this->request('GET', '/categories/2?lang=pt'));
        $de = $this->responseData($this->request('GET', '/categories/2?lang=de'));
        $this->assertSame('Móveis', $pt[0]['title']);
        $this->assertSame('furniture', $de[0]['title']);
        $this->assertSame(200, $this->request('POST', '/categories/2', ['translations' => [
            ['lang_code' => 'de', 'label' => 'Möbel'],
        ]])->getStatusCode());
    }

    public function testSharedTranslationsAreVisibleToBothCompaniesAndCustomOnesStayPrivate(): void
    {
        $body = ['translations' => [['lang_code' => 'pt', 'label' => 'Casa']]];
        $this->assertSame(200, $this->request('POST', '/categories/1', $body)->getStatusCode());
        $shared = $this->responseData($this->request('GET', '/categories/1?lang=pt', adminUserId: '9'));
        $this->assertSame('Casa', $shared[0]['title']);
        $this->assertSame(200, $this->request('POST', '/categories/2', $body)->getStatusCode());
        $this->assertSame([], $this->responseData($this->request('GET', '/categories/2?lang=pt', adminUserId: '9')));
        $products = $this->responseData($this->request('GET', '/products?lang=pt', adminUserId: '9'));
        $this->assertSame([6], array_column($products, 'id'));
        $this->assertSame(['Casa', 'foreign category'], $products[0]['category']);
    }

    /** @dataProvider unavailableCategories */
    public function testCannotTranslateUnavailableCategories(string $id): void
    {
        $body = ['translations' => [['lang_code' => 'pt', 'label' => 'Indevida']]];

        $this->assertSame(404, $this->request('POST', '/categories/' . $id, $body)->getStatusCode());
        $own = $this->responseData($this->request('GET', '/categories?lang=pt'));
        $other = $this->responseData($this->request('GET', '/categories?lang=pt', adminUserId: '9'));
        $this->assertNotContains('Indevida', array_column($own, 'title'));
        $this->assertNotContains('Indevida', array_column($other, 'title'));
    }

    public function unavailableCategories(): array
    {
        return ['foreign' => ['3'], 'inactive' => ['4'], 'missing' => ['999']];
    }

    /** @dataProvider invalidBodies */
    public function testRejectsInvalidTranslationsWithoutSavingAnEarlierValidItem(?array $body): void
    {
        $response = $this->request('POST', '/categories/2', $body);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($response));
        $category = $this->responseData($this->request('GET', '/categories/2?lang=pt'));
        $this->assertSame('furniture', $category[0]['title']);
    }

    public function invalidBodies(): array
    {
        $valid = ['lang_code' => 'pt', 'label' => 'Móveis'];

        return [
            'missing body' => [null], 'missing translations' => [[]],
            'empty list' => [['translations' => []]],
            'not a list' => [['translations' => ['pt' => 'Móveis']]],
            'missing language' => [['translations' => [$valid, ['label' => 'Casa']]]],
            'invalid language' => [['translations' => [$valid, ['lang_code' => '../pt', 'label' => 'Casa']]]],
            'array language' => [['translations' => [$valid, ['lang_code' => ['en'], 'label' => 'Home']]]],
            'missing label' => [['translations' => [$valid, ['lang_code' => 'en']]]],
            'empty label' => [['translations' => [$valid, ['lang_code' => 'en', 'label' => ' ']]]],
            'non-string label' => [['translations' => [$valid, ['lang_code' => 'en', 'label' => 12]]]],
        ];
    }

    public function testInvalidCategoryIdReturnsBadRequest(): void
    {
        $response = $this->request(
            'POST',
            '/categories/0',
            ['translations' => [['lang_code' => 'pt', 'label' => 'Casa']]]
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    /** @dataProvider invalidLanguageQueries */
    public function testInvalidLanguageReturnsBadRequestOnAllCategoryAndProductReads(string $query): void
    {
        foreach (['/categories', '/categories/1', '/products', '/products/1'] as $path) {
            $response = $this->request('GET', $path . '?' . $query);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertArrayHasKey('error', $this->responseData($response));
        }
    }

    public function invalidLanguageQueries(): array
    {
        return [
            'empty' => ['lang='],
            'array' => ['lang[]=pt'],
            'invalid code' => ['lang=123'],
        ];
    }

    public function testDeletedCategoryDoesNotLeaveTranslationsForAReusedId(): void
    {
        $body = ['title' => 'Temporary category', 'active' => true];
        $this->assertSame(200, $this->request('POST', '/categories', $body)->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories'));
        $id = array_column($categories, null, 'title')['Temporary category']['id'];
        $this->assertSame(200, $this->request('POST', '/categories/' . $id, ['translations' => [
            ['lang_code' => 'pt', 'label' => 'Temporária'],
        ]])->getStatusCode());
        $this->assertSame(200, $this->request('DELETE', '/categories/' . $id)->getStatusCode());
        $body['title'] = 'Replacement category';
        $this->assertSame(200, $this->request('POST', '/categories', $body)->getStatusCode());
        $categories = $this->responseData($this->request('GET', '/categories?lang=pt'));
        $this->assertContains('Replacement category', array_column($categories, 'title'));
        $this->assertNotContains('Temporária', array_column($categories, 'title'));
    }
}
