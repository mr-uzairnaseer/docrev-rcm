<?php

namespace App\Services\Billing;

use App\Models\Claim;
use Illuminate\Support\Str;

class Edi835Builder
{
    /**
     * Build a minimal demo ERA 835 for one or more submitted claims.
     */
    public function buildForClaims(iterable $claims, bool $asDenial = false): string
    {
        $segments = [
            'ISA*00*          *00*          *ZZ*STUBCH         *ZZ*DOCREV         *'.now()->format('ymd').'*'.now()->format('Hi').'*^*00501*000000001*0*P*:',
            'GS*HP*STUBCH*DOCREV*'.now()->format('Ymd').'*'.now()->format('Hi').'*1*X*005010X221A1',
            'ST*835*0001',
            'BPR*I*0*C*ACH',
            'TRN*1*ERA-'.strtoupper(Str::random(8)).'*1234567890',
        ];

        $totalPaid = 0;

        foreach ($claims as $claim) {
            $charge = (float) $claim->total_charge_amount;
            $patientResp = $asDenial ? 0 : round($charge * 0.2, 2);
            $paid = $asDenial ? 0 : round($charge - $patientResp, 2);
            $clpStatus = $asDenial ? '4' : '1';
            $totalPaid += $paid;

            $segments[] = 'CLP*'.$claim->claim_number.'*'.$clpStatus.'*'.$charge.'*'.$paid.'*'.$patientResp;
            $segments[] = 'SVC*HC:99213*'.$charge.'*'.$paid;
            if ($asDenial) {
                $segments[] = 'CAS*CO*97*'.$charge;
            }
        }

        $segments[3] = 'BPR*I*'.$totalPaid.'*C*ACH';
        $segmentCount = count($segments) - 3;
        $segments[] = 'SE*'.$segmentCount.'*0001';
        $segments[] = 'GE*1*1';
        $segments[] = 'IEA*1*000000001';

        return implode('~', $segments).'~';
    }
}
