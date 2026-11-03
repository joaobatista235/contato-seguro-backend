<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class ErrorHandlingTest extends IntegrationTestCase
{
    public function testUnknownRoutesReturnJsonWithNotFoundStatus(): void
    {
        $response = $this->request('GET', '/nonexistent');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertArrayHasKey('error', $this->responseData($response));
        $this->assertStringNotContainsString('Fatal error', (string)$response->getBody());
        $this->assertStringNotContainsString('/app/', (string)$response->getBody());
    }

    public function testUnsupportedMethodsReturnJsonAndAllowedMethods(): void
    {
        $response = $this->request('POST', '/report');

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('GET', $response->getHeaderLine('Allow'));
        $this->assertArrayHasKey('error', $this->responseData($response));
    }
}
