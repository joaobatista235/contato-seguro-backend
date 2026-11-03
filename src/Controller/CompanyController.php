<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CompanyService;
use Contatoseguro\TesteBackend\Util\IntegerValidator;
use Contatoseguro\TesteBackend\Util\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CompanyController
{
    /**
     * @var CompanyService
     */
    private CompanyService $service;

    public function __construct()
    {
        $this->service = new CompanyService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     */
    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = (int)$request->getAttribute('admin_user_id');
        $companies = $this->service->getAll($adminUserId);

        return JsonResponse::create($response, $companies, 200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string} $args
     * @return ResponseInterface
     */
    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = (int)$request->getAttribute('admin_user_id');
        $companyId = IntegerValidator::positive($args['id']);

        if ($companyId === null) {
            return JsonResponse::create($response, ['error' => 'company id must be a positive integer'], 400);
        }

        $company = $this->service->getOne($companyId, $adminUserId);

        if ($company === null) {
            return JsonResponse::create($response, ['error' => 'company not found'], 404);
        }

        return JsonResponse::create($response, $company, 200);
    }
}
