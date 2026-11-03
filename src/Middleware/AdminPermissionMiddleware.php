<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Middleware;

use Contatoseguro\TesteBackend\Service\AdminUserService;
use Contatoseguro\TesteBackend\Util\IntegerValidator;
use Contatoseguro\TesteBackend\Util\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AdminPermissionMiddleware implements MiddlewareInterface
{
    /**
     * @var AdminUserService
     */
    private AdminUserService $service;

    public function __construct()
    {
        $this->service = new AdminUserService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminUserId = IntegerValidator::positive($request->getHeaderLine('admin_user_id'));

        if ($adminUserId === null) {
            return JsonResponse::create(new Response(), ['error' => 'admin_user_id header must be a positive integer'], 400);
        }

        if (!$this->service->validatePermission($adminUserId)) {
            return JsonResponse::create(new Response(), ['error' => 'admin user must exist and belong to a company'], 403);
        }

        $request = $request->withAttribute('admin_user_id', $adminUserId);

        return $handler->handle($request);
    }
}
