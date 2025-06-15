<?php

namespace App\Services;

use App\Repositories\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class AuditLogService
{
    /**
     * @var AuditLogRepositoryInterface
     */
    protected $auditLogRepository;

    /**
     * AuditLogService constructor.
     *
     * @param AuditLogRepositoryInterface $auditLogRepository
     */
    public function __construct(AuditLogRepositoryInterface $auditLogRepository)
    {
        $this->auditLogRepository = $auditLogRepository;
    }

    /**
     * Get filtered audit logs
     *
     * @param array $filters
     * @param int $perPage
     * @return mixed
     */
    public function getFiltered(array $filters, int $perPage = 15)
    {
        return $this->auditLogRepository->getFiltered($filters, $perPage);
    }
}
