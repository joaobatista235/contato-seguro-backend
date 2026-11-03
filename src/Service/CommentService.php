<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Repository\CommentRepository;
use Contatoseguro\TesteBackend\Repository\ProductRepository;
use DomainException;
use InvalidArgumentException;

class CommentService
{
    /**
     * @var CommentRepository
     */
    private CommentRepository $repository;

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var AdminUserService
     */
    private AdminUserService $adminUserService;

    public function __construct()
    {
        $this->repository = new CommentRepository();
        $this->productRepository = new ProductRepository();
        $this->adminUserService = new AdminUserService();
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @return array<string, mixed>|null
     */
    public function getAll(int $productId, int $adminUserId): ?array
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null || $this->productRepository->findOneForCompany($productId, $companyId) === null) {
            return null;
        }

        $rows = $this->repository->findAllForProduct($productId, $companyId);
        $children = [];

        foreach ($rows as $row) {
            $row['deleted'] = (bool)$row['deleted'];
            $parentId = $row['parent_id'] ?? 0;
            $children[$parentId][] = $row;
        }

        return ['product_id' => $productId, 'comments' => $this->buildHierarchy($children)];
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $children
     * @param int $parentId
     * @return array<int, array<string, mixed>>
     */
    private function buildHierarchy(array $children, int $parentId = 0): array
    {
        $comments = [];

        foreach ($children[$parentId] ?? [] as $comment) {
            $comment['replies'] = $this->buildHierarchy($children, $comment['id']);
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * @param int $productId
     * @param array<string, mixed> $body
     * @param int $adminUserId
     * @param int|null $parentId
     * @return int|null
     */
    public function insertOne(int $productId, array $body, int $adminUserId, ?int $parentId = null): ?int
    {
        if (!isset($body['content']) || !is_string($body['content']) || trim($body['content']) === '') {
            throw new InvalidArgumentException('content must be a non-empty string');
        }

        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return null;
        }

        return $this->repository->insert($productId, $companyId, $adminUserId, trim($body['content']), $parentId);
    }

    /**
     * @param int $commentId
     * @param int $productId
     * @param int $adminUserId
     * @return bool
     */
    public function deleteOne(int $commentId, int $productId, int $adminUserId): bool
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        $comment = $this->repository->findOneForProduct($commentId, $productId, $companyId);

        if (empty($comment)) {
            return false;
        }

        if ($comment['admin_user_id'] !== $adminUserId) {
            throw new DomainException('only the author can delete this comment');
        }

        return $this->repository->delete($commentId, $productId, $adminUserId);
    }

    /**
     * @param int $commentId
     * @param int $productId
     * @param int $adminUserId
     * @return bool
     */
    public function like(int $commentId, int $productId, int $adminUserId): bool
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        $comment = $this->repository->findOneForProduct($commentId, $productId, $companyId);

        if (empty($comment)) {
            return false;
        }

        $this->repository->insertLike($commentId, $productId, $companyId, $adminUserId);

        return true;
    }
}
