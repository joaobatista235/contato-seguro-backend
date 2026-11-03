<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Util\IntegerValidator;
use Contatoseguro\TesteBackend\Util\JsonResponse;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CategoryController
{
    /**
     * @var CategoryService
     */
    private CategoryService $service;

    public function __construct()
    {
        $this->service = new CategoryService();
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

        try {
            $categories = $this->service->getAll($adminUserId, $request->getQueryParams());
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        return JsonResponse::create($response, $categories, 200);
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

        $categoryId = IntegerValidator::positive($args['id']);

        if ($categoryId === null) {
            return JsonResponse::create($response, ['error' => 'category id must be a positive integer'], 400);
        }

        try {
            $categories = $this->service->getOne($adminUserId, $categoryId, $request->getQueryParams());
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        return JsonResponse::create($response, $categories, 200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     */
    public function insertOne(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $adminUserId = (int)$request->getAttribute('admin_user_id');

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return JsonResponse::create($response, ['error' => 'request body must contain title and active'], 400);
        }

        try {
            $created = $this->service->insertOne($body, $adminUserId);
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        if (!$created) {
            return $response->withStatus(404);
        }

        return $response->withStatus(200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string} $args
     * @return ResponseInterface
     */
    public function insertTranslations(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $adminUserId = (int)$request->getAttribute('admin_user_id');
        $categoryId = IntegerValidator::positive($args['id']);

        if ($categoryId === null) {
            return JsonResponse::create($response, ['error' => 'category id must be a positive integer'], 400);
        }

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return JsonResponse::create($response, ['error' => 'request body must contain translations'], 400);
        }

        try {
            $created = $this->service->insertTranslations($categoryId, $body, $adminUserId);
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        return $response->withStatus($created ? 200 : 404);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string} $args
     * @return ResponseInterface
     */
    public function updateOne(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $adminUserId = (int)$request->getAttribute('admin_user_id');

        $categoryId = IntegerValidator::positive($args['id']);

        if ($categoryId === null) {
            return JsonResponse::create($response, ['error' => 'category id must be a positive integer'], 400);
        }

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return JsonResponse::create($response, ['error' => 'request body must contain title and active'], 400);
        }

        try {
            $updated = $this->service->updateOne($categoryId, $body, $adminUserId);
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        if (!$updated) {
            return $response->withStatus(404);
        }

        return $response->withStatus(200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string} $args
     * @return ResponseInterface
     */
    public function deleteOne(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $adminUserId = (int)$request->getAttribute('admin_user_id');

        $categoryId = IntegerValidator::positive($args['id']);

        if ($categoryId === null) {
            return JsonResponse::create($response, ['error' => 'category id must be a positive integer'], 400);
        }

        $deleted = $this->service->deleteOne($categoryId, $adminUserId);

        if (!$deleted) {
            return $response->withStatus(404);
        }

        return $response->withStatus(200);
    }
}
