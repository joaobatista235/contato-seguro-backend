<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Repository;

use Contatoseguro\TesteBackend\Config\DB;
use PDO;

class AdminUserRepository
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
     * @param int $adminUserId
     * @return int|null
     */
    public function findCompanyIdByAdminUserId(int $adminUserId): ?int
    {
        $query = "
            SELECT
                company_id
            FROM admin_user
            WHERE
                id = :admin_user_id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':admin_user_id', $adminUserId, PDO::PARAM_INT);
        $stm->execute();

        $companyId = $stm->fetchColumn();
        return !empty($companyId) ? (int)$companyId : null;
    }
}
