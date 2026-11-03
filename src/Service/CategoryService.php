<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Model\Category;
use Contatoseguro\TesteBackend\Repository\CategoryRepository;
use InvalidArgumentException;

class CategoryService
{
    /**
     * @var CategoryRepository
     */
    private CategoryRepository $repository;

    public function __construct()
    {
        $this->repository = new CategoryRepository();
    }

    /**
     * @param int $adminUserId
     * @return bool
     */
    public function validatePermission(int $adminUserId): bool
    {
        $companyId = $this->repository->findCompanyIdByAdminUserId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        return true;
    }

    /**
     * @param int $adminUserId
     * @return Category[]
     */
    public function getAll(int $adminUserId): array
    {
        $companyId = $this->repository->findCompanyIdByAdminUserId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        return $this->repository->findAllForCompany($companyId);
    }

    /**
     * @param int $adminUserId
     * @param int $categoryId
     * @return Category[]
     */
    public function getOne(int $adminUserId, int $categoryId): array
    {
        $companyId = $this->repository->findCompanyIdByAdminUserId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        return $this->repository->findOneForCompany($categoryId, $companyId);
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @return array<int, array{id: int, title: string}>
     */
    public function getProductCategory(int $productId, int $adminUserId): array
    {
        $companyId = $this->repository->findCompanyIdByAdminUserId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        return $this->repository->findByProduct($productId, $companyId);
    }

    /**
     * @param array<string, mixed> $body
     * @param int $adminUserId
     * @return bool
     */
    public function insertOne(array $body, int $adminUserId): bool
    {
        $this->validateBody($body);
        $companyId = $this->repository->findCompanyIdByAdminUserId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        return $this->repository->insert(trim($body['title']), (bool)$body['active'], $companyId);
    }

    /**
     * @param int $categoryId
     * @param array<string, mixed> $body
     * @param int $adminUserId
     * @return bool
     */
    public function updateOne(int $categoryId, array $body, int $adminUserId): bool
    {
        $this->validateBody($body);
        $companyId = $this->repository->findCompanyIdByAdminUserId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        return $this->repository->update($categoryId, trim($body['title']), (bool)$body['active'], $companyId);
    }

    /**
     * @param int $categoryId
     * @param int $adminUserId
     * @return bool
     */
    public function deleteOne(int $categoryId, int $adminUserId): bool
    {
        $companyId = $this->repository->findCompanyIdByAdminUserId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        return $this->repository->delete($categoryId, $companyId);
    }

    /**
     * @param array<string, mixed> $body
     * @return void
     */
    private function validateBody(array $body): void
    {
        if (!isset($body['title']) || !is_string($body['title']) || trim($body['title']) === '') {
            throw new InvalidArgumentException('title must be a non-empty string');
        }

        if (!array_key_exists('active', $body) || !in_array($body['active'], [true, false, 0, 1], true)) {
            throw new InvalidArgumentException('active must be a boolean or the integer 0 or 1');
        }
    }
}
