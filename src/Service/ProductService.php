<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Model\Product;
use Contatoseguro\TesteBackend\Repository\ProductRepository;
use Contatoseguro\TesteBackend\Util\IntegerValidator;
use InvalidArgumentException;

class ProductService
{
    /**
     * @var ProductRepository
     */
    private ProductRepository $repository;

    /**
     * @var AdminUserService
     */
    private AdminUserService $adminUserService;

    /**
     * @var CategoryService
     */
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->repository = new ProductRepository();
        $this->adminUserService = new AdminUserService();
        $this->categoryService = new CategoryService();
    }

    /**
     * @param int $adminUserId
     * @param array<string, mixed> $queryParams
     * @return array<int, array<string, mixed>>
     */
    public function getAll(int $adminUserId, array $queryParams = []): array
    {
        $filters = $this->validateFilters($queryParams);
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        $rows = $this->repository->findAllForCompany(
            $companyId,
            $filters['active'],
            $filters['category_id'],
            $filters['order']
        );
        $products = [];

        foreach ($rows as $row) {
            $productId = $row['id'];

            if (!isset($products[$productId])) {
                $products[$productId] = $row;
                $products[$productId]['category'] = [];
                unset($products[$productId]['category_id']);
            }

            if ($row['category_id'] !== null) {
                $products[$productId]['category'][] = $row['category'];
            }
        }

        return array_values($products);
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @return Product|null
     */
    public function getOne(int $productId, int $adminUserId): ?Product
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return null;
        }

        $product = $this->repository->findOneForCompany($productId, $companyId);

        if ($product === null) {
            return null;
        }

        $categories = $this->categoryService->getProductCategory($productId, $adminUserId);
        $product->setCategory(array_column($categories, 'title'));

        return $product;
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

        if ($companyId === null || $companyId !== (int)$body['company_id']) {
            return false;
        }

        $categoryId = (int)$body['category_id'];

        if ($this->categoryService->getOne($adminUserId, $categoryId) === []) {
            return false;
        }

        return $this->repository->transaction(function () use ($body, $companyId, $categoryId, $adminUserId): bool {
            $productId = $this->repository->insert(trim($body['title']), (float)$body['price'], (bool)$body['active'], $companyId);
            $this->repository->insertCategory($productId, $categoryId);
            $this->repository->insertLog($productId, $adminUserId, 'create');

            return true;
        });
    }

    /**
     * @param int $productId
     * @param array<string, mixed> $body
     * @param int $adminUserId
     * @return bool
     */
    public function updateOne(int $productId, array $body, int $adminUserId): bool
    {
        $this->validateBody($body);
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null || $companyId !== (int)$body['company_id']) {
            return false;
        }

        $categoryId = (int)$body['category_id'];

        if ($this->categoryService->getOne($adminUserId, $categoryId) === []) {
            return false;
        }

        return $this->repository->transaction(function () use ($productId, $body, $companyId, $categoryId, $adminUserId): bool {
            $updated = $this->repository->update($productId, trim($body['title']), (float)$body['price'], (bool)$body['active'], $companyId);

            if (!$updated) {
                return false;
            }

            $this->repository->deleteCategories($productId);
            $this->repository->insertCategory($productId, $categoryId);
            $this->repository->insertLog($productId, $adminUserId, 'update');

            return true;
        });
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @return bool
     */
    public function deleteOne(int $productId, int $adminUserId): bool
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null || $this->repository->findOneForCompany($productId, $companyId) === null) {
            return false;
        }

        return $this->repository->transaction(function () use ($productId, $companyId, $adminUserId): bool {
            $this->repository->deleteCategories($productId);

            if (!$this->repository->delete($productId, $companyId)) {
                return false;
            }

            $this->repository->insertLog($productId, $adminUserId, 'delete');

            return true;
        });
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @return array<int, array<string, mixed>>
     */
    public function getLog(int $productId, int $adminUserId): array
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        return $this->repository->findLogsForCompany($productId, $companyId);
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @return string
     */
    public function getFormattedLogs(int $productId, int $adminUserId): string
    {
        $actions = ['create' => 'Criação', 'update' => 'Atualização', 'delete' => 'Remoção'];
        $entries = [];

        foreach ($this->getLog($productId, $adminUserId) as $log) {
            $userName = $log['user_name'] ?? 'Usuário #' . $log['admin_user_id'];
            $action = $actions[$log['action']] ?? $log['action'];
            $timestamp = (new \DateTimeImmutable($log['timestamp']))->format('d/m/Y H:i:s');
            $entries[] = "({$userName}, {$action}, {$timestamp})";
        }

        return implode(",\n", $entries);
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

        if (
            !isset($body['price']) ||
            (!is_int($body['price']) && !is_float($body['price'])) ||
            $body['price'] < 0
        ) {
            throw new InvalidArgumentException('price must be a non-negative number');
        }

        if (!array_key_exists('active', $body) || !in_array($body['active'], [true, false, 0, 1], true)) {
            throw new InvalidArgumentException('active must be a boolean or the integer 0 or 1');
        }

        if (IntegerValidator::positive($body['company_id'] ?? null) === null) {
            throw new InvalidArgumentException('company_id must be a positive integer');
        }

        if (IntegerValidator::positive($body['category_id'] ?? null) === null) {
            throw new InvalidArgumentException('category_id must be a positive integer');
        }
    }

    /**
     * @param array<string, mixed> $queryParams
     * @return array{active: bool|null, category_id: int|null, order: string|null}
     */
    private function validateFilters(array $queryParams): array
    {
        $active = null;
        $categoryId = null;
        $order = null;

        if (array_key_exists('active', $queryParams)) {
            if (!in_array($queryParams['active'], ['0', '1', 'false', 'true'], true)) {
                throw new InvalidArgumentException('active must be 0, 1, false or true');
            }

            $active = in_array($queryParams['active'], ['1', 'true'], true);
        }

        if (array_key_exists('category_id', $queryParams)) {
            $categoryId = IntegerValidator::positive($queryParams['category_id']);

            if ($categoryId === null) {
                throw new InvalidArgumentException('category_id must be a positive integer');
            }
        }

        if (array_key_exists('order', $queryParams)) {
            if (!is_string($queryParams['order'])) {
                throw new InvalidArgumentException('order must be asc or desc');
            }

            $order = strtolower($queryParams['order']);

            if (!in_array($order, ['asc', 'desc'], true)) {
                throw new InvalidArgumentException('order must be asc or desc');
            }
        }

        return ['active' => $active, 'category_id' => $categoryId, 'order' => $order];
    }
}
