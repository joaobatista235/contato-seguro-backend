<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class CompanyControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('categories.sql');
    }

    public function testListOnlyReturnsTheAdminsCompany(): void
    {
        $own = $this->request('GET', '/companies');
        $other = $this->request('GET', '/companies', adminUserId: '9');

        $this->assertSame(200, $own->getStatusCode());
        $this->assertSame([42], array_column($this->responseData($own), 'id'));
        $this->assertSame([77], array_column($this->responseData($other), 'id'));
    }

    public function testGetOneReturnsTheOwnCompanyAsAnObject(): void
    {
        $response = $this->request('GET', '/companies/42');
        $company = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(42, $company['id']);
        $this->assertSame('First company', $company['name']);
        $this->assertSame(1, $company['active']);
    }

    public function testForeignAndMissingCompaniesReturnNotFound(): void
    {
        $this->assertSame(404, $this->request('GET', '/companies/77')->getStatusCode());
        $this->assertSame(404, $this->request('GET', '/companies/999')->getStatusCode());
    }

    public function testInvalidCompanyIdsReturnBadRequest(): void
    {
        $this->assertSame(400, $this->request('GET', '/companies/42 OR 1=1')->getStatusCode());
        $this->assertSame(400, $this->request('GET', '/companies/0')->getStatusCode());
    }
}
