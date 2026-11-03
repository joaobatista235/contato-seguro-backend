<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Repository\AdminUserRepository;

class AdminUserService
{
    /**
     * @var AdminUserRepository
     */
    private AdminUserRepository $repository;

    public function __construct()
    {
        $this->repository = new AdminUserRepository();
    }

    /**
     * @param int $adminUserId
     * @return int|null
     */
    public function getCompanyId(int $adminUserId): ?int
    {
        return $this->repository->findCompanyIdByAdminUserId($adminUserId);
    }

    /**
     * @param int $adminUserId
     * @return bool
     */
    public function validatePermission(int $adminUserId): bool
    {
        return $this->getCompanyId($adminUserId) !== null;
    }
}
