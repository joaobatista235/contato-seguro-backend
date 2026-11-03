<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Model\Company;
use Contatoseguro\TesteBackend\Repository\CompanyRepository;

class CompanyService
{
    /**
     * @var CompanyRepository
     */
    private CompanyRepository $repository;

    /**
     * @var AdminUserService
     */
    private AdminUserService $adminUserService;

    public function __construct()
    {
        $this->repository = new CompanyRepository();
        $this->adminUserService = new AdminUserService();
    }

    /**
     * @param int $adminUserId
     * @return Company[]
     */
    public function getAll(int $adminUserId): array
    {
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return [];
        }

        return $this->repository->findAllForCompany($companyId);
    }

    /**
     * @param int $companyId
     * @param int $adminUserId
     * @return Company|null
     */
    public function getOne(int $companyId, int $adminUserId): ?Company
    {
        if ($companyId !== $this->adminUserService->getCompanyId($adminUserId)) {
            return null;
        }

        $companies = $this->repository->findAllForCompany($companyId);

        return $companies[0] ?? null;
    }

    /**
     * @param int $companyId
     * @param int $adminUserId
     * @return string|null
     */
    public function getNameById(int $companyId, int $adminUserId): ?string
    {
        $company = $this->getOne($companyId, $adminUserId);

        return $company?->name;
    }
}
