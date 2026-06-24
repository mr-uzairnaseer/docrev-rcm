<?php

namespace App\Services\Billing;

use App\Models\Patient;
use App\Models\Payer;

interface EligibilityProviderInterface
{
    /**
     * Submit a 270 inquiry and return raw 271 response.
     *
     * @return array{edi_271: string, message: string}
     */
    public function checkEligibility(Patient $patient, Payer $payer, string $edi270, string $serviceDate): array;
}
