<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

class ReportService
{
    /**
     * @var ProductService
     */
    private ProductService $productService;

    /**
     * @var CompanyService
     */
    private CompanyService $companyService;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->companyService = new CompanyService();
    }

    /**
     * @param int $adminUserId
     * @return string
     */
    public function generate(int $adminUserId): string
    {
        $rows = [[
            'Id do produto',
            'Nome da Empresa',
            'Nome do Produto',
            'Valor do Produto',
            'Categorias do Produto',
            'Data de Criação',
            'Logs de Alterações',
        ]];
        $company = $this->companyService->getAll($adminUserId)[0] ?? null;
        $companyName = $company->name ?? '';

        foreach ($this->productService->getAll($adminUserId) as $product) {
            $rows[] = [
                $product['id'],
                $companyName,
                $product['title'],
                $product['price'],
                implode(', ', $product['category']),
                $product['created_at'],
                $this->productService->getFormattedLogs($product['id'], $adminUserId),
            ];
        }

        return $this->render($rows);
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     * @return string
     */
    private function render(array $rows): string
    {
        $tableRows = '';

        foreach ($rows as $row) {
            $cells = '';

            foreach ($row as $column) {
                $escaped = htmlspecialchars((string)$column, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $cells .= <<<HTML
                    <td style="white-space: pre-line;">{$escaped}</td>
                    HTML;
            }

            $tableRows .= <<<HTML
                <tr>
                    {$cells}
                </tr>
                HTML;
        }

        return <<<HTML
            <table style="font-size: 10px;">
                {$tableRows}
            </table>
            HTML;
    }
}
