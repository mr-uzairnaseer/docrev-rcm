<?php

namespace App\Services\Billing;

use App\Models\Claim;
use Illuminate\Support\Str;

class StubClearinghouse implements ClearinghouseInterface
{
    public function submitClaim(Claim $claim, string $edi837): array
    {
        return [
            'success' => true,
            'external_reference' => 'STUB-'.strtoupper(Str::random(10)),
            'message' => 'Claim accepted by stub clearinghouse (sandbox). Replace with Availity/Change Healthcare when credentials are configured.',
        ];
    }
}
