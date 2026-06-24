<?php

namespace App\Services\Billing;

use App\Models\Claim;

interface ClearinghouseInterface
{
    public function submitClaim(Claim $claim, string $edi837): array;
}
