<?php

namespace App\Services;

use App\Models\CorporateOrganization;
use App\Models\User;

class CorporateCreditService
{
    public function organizationFor(?User $user): ?CorporateOrganization
    {
        $organizationId = $user?->corporate_organization_id;

        if (! $organizationId) {
            return null;
        }

        $organization = CorporateOrganization::find($organizationId);

        return $organization?->is_credit_enabled ? $organization : null;
    }
}
