<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Models\Tenant;
use App\Support\TenantFeatures;

final class UpdateTenantFeatures
{
    public function __construct(
        private readonly SyncFeatureCeiling $syncFeatureCeiling,
    ) {}

    /**
     * @param  list<string>  $features
     */
    public function execute(Tenant $tenant, array $features): Tenant
    {
        $normalized = array_values(array_intersect(TenantFeatures::keys(), array_map('strval', $features)));

        $tenant->enabled_features = $normalized;
        $tenant->save();

        tenancy()->initialize($tenant);
        try {
            $this->syncFeatureCeiling->execute($tenant);
        } finally {
            tenancy()->end();
        }

        return $tenant->refresh();
    }
}
