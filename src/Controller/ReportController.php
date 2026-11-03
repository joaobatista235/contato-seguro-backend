<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\ReportService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportController
{
    /**
     * @var ReportService
     */
    private ReportService $service;

    public function __construct()
    {
        $this->service = new ReportService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     */
    public function generate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = (int)$request->getAttribute('admin_user_id');
        $html = $this->service->generate($adminUserId);
        $response->getBody()->write($html);

        return $response->withStatus(200)->withHeader('Content-Type', 'text/html');
    }
}
