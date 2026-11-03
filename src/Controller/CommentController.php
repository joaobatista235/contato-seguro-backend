<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CommentService;
use Contatoseguro\TesteBackend\Util\IntegerValidator;
use Contatoseguro\TesteBackend\Util\JsonResponse;
use DomainException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CommentController
{
    /**
     * @var CommentService
     */
    private CommentService $service;

    public function __construct()
    {
        $this->service = new CommentService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string} $args
     * @return ResponseInterface
     */
    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $productId = IntegerValidator::positive($args['id']);

        if ($productId === null) {
            return JsonResponse::create($response, ['error' => 'product id must be a positive integer'], 400);
        }

        $adminUserId = (int)$request->getAttribute('admin_user_id');
        $comments = $this->service->getAll($productId, $adminUserId);

        if ($comments === null) {
            return JsonResponse::create($response, ['error' => 'product not found'], 404);
        }

        return JsonResponse::create($response, $comments, 200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string} $args
     * @return ResponseInterface
     */
    public function insertOne(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        return $this->createComment($request, $response, $args);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string, comment_id: string} $args
     * @return ResponseInterface
     */
    public function reply(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $parentId = IntegerValidator::positive($args['comment_id']);

        if ($parentId === null) {
            return JsonResponse::create($response, ['error' => 'comment id must be a positive integer'], 400);
        }

        return $this->createComment($request, $response, $args, $parentId);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @param int|null $parentId
     * @return ResponseInterface
     */
    private function createComment(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
        ?int $parentId = null
    ): ResponseInterface {
        $productId = IntegerValidator::positive($args['id']);

        if ($productId === null) {
            return JsonResponse::create($response, ['error' => 'product id must be a positive integer'], 400);
        }

        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return JsonResponse::create($response, ['error' => 'request body must contain content'], 400);
        }

        $adminUserId = (int)$request->getAttribute('admin_user_id');

        try {
            $commentId = $this->service->insertOne($productId, $body, $adminUserId, $parentId);
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 400);
        }

        if ($commentId === null) {
            return JsonResponse::create($response, ['error' => 'product or parent comment not found'], 404);
        }

        return JsonResponse::create($response, ['id' => $commentId], 201);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string, comment_id: string} $args
     * @return ResponseInterface
     */
    public function deleteOne(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $productId = IntegerValidator::positive($args['id']);
        $commentId = IntegerValidator::positive($args['comment_id']);

        if ($productId === null || $commentId === null) {
            return JsonResponse::create(
                $response,
                ['error' => 'product and comment ids must be positive integers'],
                400
            );
        }

        $adminUserId = (int)$request->getAttribute('admin_user_id');

        try {
            $deleted = $this->service->deleteOne($commentId, $productId, $adminUserId);
        } catch (DomainException $exception) {
            return JsonResponse::create($response, ['error' => $exception->getMessage()], 403);
        }

        if (!$deleted) {
            return JsonResponse::create($response, ['error' => 'comment not found'], 404);
        }

        return $response->withStatus(200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array{id: string, comment_id: string} $args
     * @return ResponseInterface
     */
    public function like(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $productId = IntegerValidator::positive($args['id']);
        $commentId = IntegerValidator::positive($args['comment_id']);

        if ($productId === null || $commentId === null) {
            return JsonResponse::create(
                $response,
                ['error' => 'product and comment ids must be positive integers'],
                400
            );
        }

        $adminUserId = (int)$request->getAttribute('admin_user_id');

        if (!$this->service->like($commentId, $productId, $adminUserId)) {
            return JsonResponse::create($response, ['error' => 'comment not found'], 404);
        }

        return JsonResponse::create($response, ['liked' => true], 200);
    }
}
