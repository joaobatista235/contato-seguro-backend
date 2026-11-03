<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;
use Contatoseguro\TesteBackend\Service\ProductService;

class ReportControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('report.sql');
    }

    public function testLogsAreFormattedInChronologicalOrderIncludingMissingUsers(): void
    {
        $logs = (new ProductService())->getFormattedLogs(1);

        $this->assertSame(
            "(Bob, Criação, 20/12/2023 21:32:22),\n"
            . "(Alice <admin>, Atualização, 22/12/2023 18:12:10),\n"
            . '(Usuário #99, Remoção, 22/12/2023 18:12:10)',
            $logs
        );
    }

    public function testProductsWithoutLogsHaveAnEmptyLogColumn(): void
    {
        $this->assertSame('', (new ProductService())->getFormattedLogs(2));
    }

    public function testReportRendersLogsAndCategoriesAsEscapedHtml(): void
    {
        $response = $this->request('GET', '/report');
        $html = (string)$response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('house, furniture', $html);
        $this->assertStringContainsString('(Bob, Criação, 20/12/2023 21:32:22)', $html);
        $this->assertStringContainsString('(Alice &lt;admin&gt;, Atualização, 22/12/2023 18:12:10)', $html);
        $this->assertStringContainsString('(Usuário #99, Remoção, 22/12/2023 18:12:10)', $html);
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;Acme&gt;', $html);
        $this->assertStringContainsString('Product without logs', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('Foreign product', $html);
    }
}
