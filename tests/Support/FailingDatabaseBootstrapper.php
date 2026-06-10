<?php

declare(strict_types=1);

namespace Tests\Support;

use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;

/**
 * Simulates a tenant whose database/schema is unreachable, used to test
 * graceful recovery from tenancy()->initialize() failures.
 */
class FailingDatabaseBootstrapper implements TenancyBootstrapper
{
    public function bootstrap($tenant)
    {
        throw new TenantDatabaseDoesNotExistException('tenant_'.$tenant->getTenantKey());
    }

    public function revert()
    {
    }
}
