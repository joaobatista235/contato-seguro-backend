<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Repository;

use Contatoseguro\TesteBackend\Config\DB;
use Contatoseguro\TesteBackend\Model\Category;
use PDO;
use Throwable;

class CategoryRepository
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
     * @return Category[]
     */
    public function findAllForCompany(int $companyId): array
    {
        $query = "
            SELECT
                id, company_id, title, active
            FROM category
            WHERE
                company_id = :company_id OR
                company_id IS NULL
            ORDER BY id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_CLASS, Category::class);
    }

    /**
     * @param int $categoryId
     * @param int $companyId
     * @return Category[]
     */
    public function findOneForCompany(int $categoryId, int $companyId): array
    {
        $query = "
            SELECT
                id, company_id, title, active
            FROM category
            WHERE
                id = :category_id AND
                active = 1 AND
                (company_id = :company_id OR company_id IS NULL)
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_CLASS, Category::class);
    }

    /**
     * @param int $productId
     * @param int $companyId
     * @return array<int, array{id: int, title: string}>
     */
    public function findByProduct(int $productId, int $companyId): array
    {
        $query = "
            SELECT
                DISTINCT c.id, c.title
            FROM category c
            INNER JOIN product_category pc ON pc.cat_id = c.id
            INNER JOIN product p ON p.id = pc.product_id
            WHERE
                p.id = :product_id AND
                p.company_id = :company_id AND
                c.active = 1 AND
                (c.company_id IS NULL OR c.company_id = p.company_id)
            ORDER BY c.id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $title
     * @param bool $active
     * @param int $companyId
     * @return bool
     */
    public function insert(string $title, bool $active, int $companyId): bool
    {
        $query = "
            INSERT INTO category (company_id, title, active)
            VALUES (:company_id, :title, :active)
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->bindValue(':title', $title, PDO::PARAM_STR);
        $stm->bindValue(':active', (int)$active, PDO::PARAM_INT);
        $stm->execute();

        return $stm->rowCount() > 0;
    }

    /**
     * @param int $categoryId
     * @param string $title
     * @param bool $active
     * @param int $companyId
     * @return bool
     */
    public function update(int $categoryId, string $title, bool $active, int $companyId): bool
    {
        $query = "
            UPDATE category
            SET title = :title, active = :active
            WHERE id = :category_id AND company_id = :company_id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':title', $title, PDO::PARAM_STR);
        $stm->bindValue(':active', (int)$active, PDO::PARAM_INT);
        $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->rowCount() > 0;
    }

    /**
     * @param int $categoryId
     * @param int $companyId
     * @return bool
     */
    public function delete(int $categoryId, int $companyId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $query = "
                SELECT
                    id
                FROM category
                WHERE
                    id = :category_id AND
                    company_id = :company_id
            ";

            $stm = $this->pdo->prepare($query);
            $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stm->execute();

            if ($stm->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }

            $query = "
                DELETE FROM product_category
                WHERE
                    cat_id = :category_id
            ";

            $stm = $this->pdo->prepare($query);
            $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stm->execute();

            $query = "DELETE FROM category_translation WHERE category_id = :category_id";
            $stm = $this->pdo->prepare($query);
            $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stm->execute();

            $query = "
                DELETE FROM category
                WHERE
                    id = :category_id AND
                    company_id = :company_id
            ";

            $stm = $this->pdo->prepare($query);
            $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stm->execute();

            if ($stm->rowCount() === 0) {
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
