<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Repository;

use Contatoseguro\TesteBackend\Config\DB;
use Contatoseguro\TesteBackend\Model\Product;
use PDO;
use Throwable;

class ProductRepository
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    /**
     * @param int $companyId
     * @param bool|null $active
     * @param int|null $categoryId
     * @param string|null $order
     * @return array<int, array<string, mixed>>
     */
    public function findAllForCompany(int $companyId, ?bool $active = null, ?int $categoryId = null, ?string $order = null): array
    {
        [$where, $join, $bind, $orderBy] = $this->buildWhere($companyId, $active, $categoryId, $order);

        $query = "
            SELECT
                DISTINCT p.id, p.company_id, p.title, p.price, p.active, p.created_at,
                c.id AS category_id, c.title AS category
            FROM product p
            {$join}
            LEFT JOIN product_category pc ON pc.product_id = p.id
            LEFT JOIN category c ON c.id = pc.cat_id AND
                c.active = 1 AND
                (c.company_id IS NULL OR c.company_id = p.company_id)
            WHERE
                {$where}
            ORDER BY {$orderBy}
        ";

        $stm = $this->pdo->prepare($query);

        foreach ($bind as $parameter => [$value, $type]) {
            $stm->bindValue($parameter, $value, $type);
        }

        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $companyId
     * @param bool|null $active
     * @param int|null $categoryId
     * @param string|null $order
     * @return array{0: string, 1: string, 2: array<string, array{0: int, 1: int}>, 3: string}
     */
    private function buildWhere(int $companyId, ?bool $active, ?int $categoryId, ?string $order): array
    {
        $join = '';
        $conditions = ['p.company_id = :company_id'];
        $bind = [':company_id' => [$companyId, PDO::PARAM_INT]];

        if ($categoryId !== null) {
            $join = "
            INNER JOIN product_category filtered_pc ON filtered_pc.product_id = p.id
            INNER JOIN category filtered_c ON filtered_c.id = filtered_pc.cat_id AND
                filtered_c.id = :category_id AND
                filtered_c.active = 1 AND
                (filtered_c.company_id IS NULL OR filtered_c.company_id = p.company_id)
            ";
            $bind[':category_id'] = [$categoryId, PDO::PARAM_INT];
        }

        if ($active !== null) {
            $conditions[] = 'p.active = :active';
            $bind[':active'] = [(int)$active, PDO::PARAM_INT];
        }

        $where = implode(" AND\n                ", $conditions);
        $orderBy = match ($order) {
            'asc' => 'p.created_at ASC, p.id ASC, c.id ASC',
            'desc' => 'p.created_at DESC, p.id ASC, c.id ASC',
            default => 'p.id, c.id',
        };

        return [$where, $join, $bind, $orderBy];
    }

    /**
     * @param int $productId
     * @param int $companyId
     * @return Product|null
     */
    public function findOneForCompany(int $productId, int $companyId): ?Product
    {
        $query = "
            SELECT
                id, company_id AS companyId, title, COALESCE(price, 0) AS price,
                active, created_at AS createdAt
            FROM product
            WHERE
                id = :product_id AND
                company_id = :company_id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        $products = $stm->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Product::class);

        return $products[0] ?? null;
    }

    /**
     * @param string $title
     * @param float $price
     * @param bool $active
     * @param int $companyId
     * @return int
     */
    public function insert(string $title, float $price, bool $active, int $companyId): int
    {
        $query = "
            INSERT INTO product (company_id, title, price, active)
            VALUES (:company_id, :title, :price, :active)
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->bindValue(':title', $title, PDO::PARAM_STR);
        $stm->bindValue(':price', (string)$price, PDO::PARAM_STR);
        $stm->bindValue(':active', (int)$active, PDO::PARAM_INT);
        $stm->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param int $productId
     * @param string $title
     * @param float $price
     * @param bool $active
     * @param int $companyId
     * @return bool
     */
    public function update(int $productId, string $title, float $price, bool $active, int $companyId): bool
    {
        $query = "
            UPDATE product
            SET
                title = :title,
                price = :price,
                active = :active
            WHERE
                id = :product_id AND
                company_id = :company_id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':title', $title, PDO::PARAM_STR);
        $stm->bindValue(':price', (string)$price, PDO::PARAM_STR);
        $stm->bindValue(':active', (int)$active, PDO::PARAM_INT);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->rowCount() > 0;
    }

    /**
     * @param int $productId
     * @param int $companyId
     * @return bool
     */
    public function delete(int $productId, int $companyId): bool
    {
        $query = "
            DELETE FROM product
            WHERE
                id = :product_id AND
                company_id = :company_id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->rowCount() > 0;
    }

    /**
     * @param int $productId
     * @param int $categoryId
     * @return void
     */
    public function insertCategory(int $productId, int $categoryId): void
    {
        $query = "
            INSERT INTO product_category (product_id, cat_id)
            VALUES (:product_id, :category_id)
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stm->execute();
    }

    /**
     * @param int $productId
     * @return void
     */
    public function deleteCategories(int $productId): void
    {
        $query = "
            DELETE FROM product_category
            WHERE
                product_id = :product_id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->execute();
    }

    /**
     * @param int $productId
     * @param int $adminUserId
     * @param string $action
     * @return void
     */
    public function insertLog(int $productId, int $adminUserId, string $action): void
    {
        $query = "
            INSERT INTO product_log (product_id, admin_user_id, action)
            VALUES (:product_id, :admin_user_id, :action)
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':admin_user_id', $adminUserId, PDO::PARAM_INT);
        $stm->bindValue(':action', $action, PDO::PARAM_STR);
        $stm->execute();
    }

    /**
     * @param int $productId
     * @param int $companyId
     * @return array<int, array<string, mixed>>
     */
    public function findLogsForCompany(int $productId, int $companyId): array
    {
        $query = "
            SELECT
                pl.id, pl.product_id, pl.admin_user_id, pl.action, pl.timestamp,
                au.name AS user_name
            FROM product_log pl
            INNER JOIN product p ON p.id = pl.product_id
            LEFT JOIN admin_user au ON au.id = pl.admin_user_id
            WHERE
                pl.product_id = :product_id AND
                p.company_id = :company_id
            ORDER BY pl.timestamp, pl.id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param callable(): bool $operation
     * @return bool
     */
    public function transaction(callable $operation): bool
    {
        $this->pdo->beginTransaction();

        try {
            if (!$operation()) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
