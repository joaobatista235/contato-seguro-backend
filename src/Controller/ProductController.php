<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\ProductService;
use Contatoseguro\TesteBackend\Util\IntegerValidator;
use Contatoseguro\TesteBackend\Util\JsonResponse;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController
{
    /**
     * @var ProductService
     */
    private ProductService $service;

    public function __construct()
    {
        $this->service = new ProductService();
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
            $products = $this->service->getAll($adminUserId, $request->getQueryParams());
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        return JsonResponse::create($response, $products, 200);
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

        $productId = IntegerValidator::positive($args['id']);

        if ($productId === null) {
            return JsonResponse::create($response, ['error' => 'product id must be a positive integer'], 400);
        }

        try {
            $product = $this->service->getOne($productId, $adminUserId, $request->getQueryParams());
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        if ($product === null) {
            return JsonResponse::create($response, ['error' => 'product not found'], 404);
        }

        return JsonResponse::create($response, $product, 200);
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
            return JsonResponse::create(
                $response,
                ['error' => 'request body must contain company_id, title, price, active and category_id'],
                400
            );
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
    public function updateOne(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $adminUserId = (int)$request->getAttribute('admin_user_id');

        $productId = IntegerValidator::positive($args['id']);

        if ($productId === null) {
            return JsonResponse::create($response, ['error' => 'product id must be a positive integer'], 400);
        }

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return JsonResponse::create(
                $response,
                ['error' => 'request body must contain company_id, title, price, active and category_id'],
                400
            );
        }

        try {
            $updated = $this->service->updateOne($productId, $body, $adminUserId);
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

        $productId = IntegerValidator::positive($args['id']);

        if ($productId === null) {
            return JsonResponse::create($response, ['error' => 'product id must be a positive integer'], 400);
        }

        $deleted = $this->service->deleteOne($productId, $adminUserId);

        if (!$deleted) {
            return $response->withStatus(404);
        }

        return $response->withStatus(200);
    }
}
