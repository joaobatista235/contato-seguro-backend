<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Model\Category;
use Contatoseguro\TesteBackend\Repository\CategoryRepository;
use Contatoseguro\TesteBackend\Util\LanguageCode;
use InvalidArgumentException;

class CategoryService
{
    /**
     * @var CategoryRepository
     */
    private CategoryRepository $repository;

    /**
     * @var AdminUserService
     */
    private AdminUserService $adminUserService;

    /**
     * @var CategoryTranslationService
     */
    private CategoryTranslationService $translationService;

    public function __construct()
    {
        $this->repository = new CategoryRepository();
        $this->adminUserService = new AdminUserService();
        $this->translationService = new CategoryTranslationService();
    }

    /**
     * @param int $adminUserId
     * @return bool
     */
    public function validatePermission(int $adminUserId): bool
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        return true;
    }

    /**
     * @param int $adminUserId
     * @param array<string, mixed> $queryParams
     * @return Category[]
     */
    public function getAll(int $adminUserId, array $queryParams = []): array
    {
        $lang = LanguageCode::normalize($queryParams['lang'] ?? 'en');
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        $categories = $this->repository->findAllForCompany($companyId);

        return $this->translateCategories($categories, $lang);
    }

    /**
     * @param int $adminUserId
     * @param int $categoryId
     * @param array<string, mixed> $queryParams
     * @return Category[]
     */
    public function getOne(int $adminUserId, int $categoryId, array $queryParams = []): array
    {
        $lang = LanguageCode::normalize($queryParams['lang'] ?? 'en');
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        $categories = $this->repository->findOneForCompany($categoryId, $companyId);

        return $this->translateCategories($categories, $lang);
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @param string $lang
     * @return array<int, array{id: int, title: string}>
     */
    public function getProductCategory(int $productId, int $adminUserId, string $lang = 'en'): array
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        $categories = $this->repository->findByProduct($productId, $companyId);
        $labels = $this->translationService->getLabels(array_column($categories, 'id'), $lang);

        foreach ($categories as $index => $category) {
            $categories[$index]['title'] = $labels[$category['id']] ?? $category['title'];
        }

        return $categories;
    }

    /**
     * @param Category[] $categories
     * @param string $lang
     * @return Category[]
     */
    private function translateCategories(array $categories, string $lang): array
    {
        $labels = $this->translationService->getLabels(array_column($categories, 'id'), $lang);

        foreach ($categories as $category) {
            $category->title = $labels[$category->id] ?? $category->title;
        }

        return $categories;
    }

    /**
     * @param int $categoryId
     * @param array<string, mixed> $body
     * @param int $adminUserId
     * @return bool
     */
    public function insertTranslations(int $categoryId, array $body, int $adminUserId): bool
    {
        return $this->translationService->insertTranslations($categoryId, $body, $adminUserId);
    }

    /**
     * @param array<string, mixed> $body
     * @param int $adminUserId
     * @return bool
     */
    public function insertOne(array $body, int $adminUserId): bool
    {
        $this->validateBody($body);
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

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
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

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
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

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
