<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Repository;

use Contatoseguro\TesteBackend\Config\DB;
use PDO;

class CommentRepository
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
     * @param int $productId
     * @param int $companyId
     * @return array<int, array<string, mixed>>
     */
    public function findAllForProduct(int $productId, int $companyId): array
    {
        $query = "
            SELECT
                cm.id, cm.parent_id, cm.admin_user_id, au.name AS user_name,
                cm.content, cm.created_at, cm.deleted_at IS NOT NULL AS deleted,
                COUNT(cl.comment_id) AS likes
            FROM product_comment cm
            INNER JOIN product p ON p.id = cm.product_id
            LEFT JOIN admin_user au ON au.id = cm.admin_user_id
            LEFT JOIN product_comment_like cl ON cl.comment_id = cm.id AND
                cm.deleted_at IS NULL
            WHERE
                cm.product_id = :product_id AND
                p.company_id = :company_id
            GROUP BY
                cm.id, cm.parent_id, cm.admin_user_id, au.name,
                cm.content, cm.created_at, cm.deleted_at
            ORDER BY cm.created_at, cm.id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $commentId
     * @param int $productId
     * @param int $companyId
     * @return array<string, mixed>|null
     */
    public function findOneForProduct(int $commentId, int $productId, int $companyId): ?array
    {
        $query = "
            SELECT
                cm.id, cm.admin_user_id
            FROM product_comment cm
            INNER JOIN product p ON p.id = cm.product_id
            WHERE
                cm.id = :comment_id AND
                cm.product_id = :product_id AND
                p.company_id = :company_id AND
                cm.deleted_at IS NULL
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':comment_id', $commentId, PDO::PARAM_INT);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();
        $comments = $stm->fetchAll(PDO::FETCH_ASSOC);

        return $comments[0] ?? null;
    }

    /**
     * @param int $productId
     * @param int $companyId
     * @param int $adminUserId
     * @param string $content
     * @param int|null $parentId
     * @return int|null
     */
    public function insert(int $productId, int $companyId, int $adminUserId, string $content, ?int $parentId): ?int
    {
        $query = "
            INSERT INTO product_comment (product_id, parent_id, admin_user_id, content)
            SELECT
                p.id, :parent_id, :admin_user_id, :content
            FROM product p
            LEFT JOIN product_comment parent ON parent.id = :parent_id AND
                parent.product_id = p.id AND
                parent.deleted_at IS NULL
            WHERE
                p.id = :product_id AND
                p.company_id = :company_id AND
                (:parent_id IS NULL OR parent.id IS NOT NULL)
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->bindValue(':admin_user_id', $adminUserId, PDO::PARAM_INT);
        $stm->bindValue(':content', $content, PDO::PARAM_STR);
        $stm->bindValue(':parent_id', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stm->execute();

        return $stm->rowCount() > 0 ? (int)$this->pdo->lastInsertId() : null;
    }

    /**
     * @param int $commentId
     * @param int $productId
     * @param int $adminUserId
     * @return bool
     */
    public function delete(int $commentId, int $productId, int $adminUserId): bool
    {
        $query = "
            UPDATE product_comment
            SET content = NULL, deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :comment_id AND
                product_id = :product_id AND
                admin_user_id = :admin_user_id AND
                deleted_at IS NULL
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':comment_id', $commentId, PDO::PARAM_INT);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':admin_user_id', $adminUserId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->rowCount() > 0;
    }

    /**
     * @param int $commentId
     * @param int $productId
     * @param int $companyId
     * @param int $adminUserId
     * @return void
     */
    public function insertLike(int $commentId, int $productId, int $companyId, int $adminUserId): void
    {
        $query = "
            INSERT INTO product_comment_like (comment_id, admin_user_id)
            SELECT
                cm.id, :admin_user_id
            FROM product_comment cm
            INNER JOIN product p ON p.id = cm.product_id
            WHERE
                cm.id = :comment_id AND
                cm.product_id = :product_id AND
                p.company_id = :company_id AND
                cm.deleted_at IS NULL
            ON CONFLICT (comment_id, admin_user_id) DO NOTHING
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':comment_id', $commentId, PDO::PARAM_INT);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->bindValue(':admin_user_id', $adminUserId, PDO::PARAM_INT);
        $stm->execute();
    }

    /**
     * @param int $productId
     * @return void
     */
    public function deleteForProduct(int $productId): void
    {
        $query = "
            DELETE FROM product_comment_like
            WHERE
                comment_id IN (SELECT id FROM product_comment WHERE product_id = :product_id)
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->execute();

        $query = "DELETE FROM product_comment WHERE product_id = :product_id";
        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stm->execute();
    }

}
