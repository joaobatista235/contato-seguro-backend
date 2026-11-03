<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Repository;

use Contatoseguro\TesteBackend\Config\DB;
use Contatoseguro\TesteBackend\Model\Company;
use PDO;

class CompanyRepository
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
     * @return Company[]
     */
    public function findAllForCompany(int $companyId): array
    {
        $query = "
            SELECT
                id, name, active
            FROM company
            WHERE
                id = :company_id
            ORDER BY id
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_CLASS, Company::class);
    }
}
