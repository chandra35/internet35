<?php

namespace App\Helpers;

use App\Services\ActivityLogService;

class ActivityLogger
{
    protected ActivityLogService $service;

    public function __construct(ActivityLogService $service)
    {
        $this->service = $service;
    }

    /**
     * Log a create action
     */
    public function logCreate(string $module, string $description, ?array $data = null): void
    {
        $this->service->log('create', $module, $description, null, $data);
    }

    /**
     * Log an update action
     */
    public function logUpdate(string $module, string $description, ?array $oldData = null, ?array $newData = null): void
    {
        $this->service->log('update', $module, $description, $oldData, $newData);
    }

    /**
     * Log a delete action
     */
    public function logDelete(string $module, string $description, ?array $data = null): void
    {
        $this->service->log('delete', $module, $description, $data, null);
    }

    /**
     * Log a view action
     */
    public function logView(string $module, string $description): void
    {
        $this->service->log('view', $module, $description);
    }

    /**
     * Log a custom action
     */
    public function log(string $action, string $module, string $description, ?array $oldData = null, ?array $newData = null): void
    {
        $this->service->log($action, $module, $description, $oldData, $newData);
    }
}
