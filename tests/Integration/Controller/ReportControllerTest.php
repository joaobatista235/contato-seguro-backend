<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;
use DOMDocument;
use DOMXPath;

class ReportControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('report.sql');
    }

    public function testReportDisplaysProductsCategoriesAndNewestAuditEntriesFirst(): void
    {
        $response = $this->request('GET', '/report');
        $html = (string)$response->getBody();
        $created = '(Bob, Criação, 20/12/2023 21:32:22)';
        $updated = '(Alice &lt;admin&gt;, Atualização, 22/12/2023 18:12:10)';
        $deleted = '(Usuário #99, Remoção, 22/12/2023 18:12:10)';

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('house, furniture', $html);
        $this->assertStringContainsString($created, $html);
        $this->assertStringContainsString($updated, $html);
        $this->assertStringContainsString($deleted, $html);
        $this->assertLessThan(strpos($html, $created), strpos($html, $updated));
        $this->assertLessThan(strpos($html, $updated), strpos($html, $deleted));
    }

    public function testProductsWithoutLogsAppearWithAnEmptyAuditColumn(): void
    {
        $response = $this->request('GET', '/report');
        $document = new DOMDocument();
        $document->loadHTML(
            '<?xml encoding="UTF-8">' . (string)$response->getBody(),
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        $cells = (new DOMXPath($document))->query("//tr[td[3] = 'Product without logs']/td[last()]");

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $cells);
        $this->assertSame('', trim($cells->item(0)->textContent));
    }

    public function testReportOnlyDisplaysTheOwnCompanyProductsAndLogs(): void
    {
        $response = $this->request('GET', '/report', adminUserId: '9');
        $html = (string)$response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Foreign product', $html);
        $this->assertStringContainsString('Another company', $html);
        $this->assertStringNotContainsString('Bob', $html);
        $this->assertStringNotContainsString('Alice', $html);
        $this->assertStringNotContainsString('Usuário #99', $html);
    }

    public function testReportEscapesHtmlInProductCompanyAndUserNames(): void
    {
        $response = $this->request('GET', '/report');
        $html = (string)$response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;Acme&gt;', $html);
        $this->assertStringContainsString('Alice &lt;admin&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
