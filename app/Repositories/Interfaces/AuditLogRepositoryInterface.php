<?php

namespace App\Repositories\Interfaces;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    /**
     * Get all audit logs with optional filtering
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Create a new audit log
     *
     * @param array $data
     * @return AuditLog
     */
    public function create(array $data): AuditLog;
}
