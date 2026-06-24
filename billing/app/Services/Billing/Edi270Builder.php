<?php

namespace App\Services\Billing;

use App\Models\Patient;
use App\Models\Payer;
use Illuminate\Support\Str;

class Edi270Builder
{
    public function build(Patient $patient, Payer $payer, string $serviceDate, string $memberId): string
    {
        $trace = 'ELG-'.strtoupper(Str::random(8));
        $dob = $patient->date_of_birth?->format('Ymd') ?? '';
        $segments = [
            'ISA*00*          *00*          *ZZ*DOCREV         *ZZ*'.($payer->electronic_payer_id ?? 'PAYER').'*'.now()->format('ymd').'*'.now()->format('Hi').'*^*00501*000000001*0*P*:',
            'GS*HS*DOCREV*'.($payer->electronic_payer_id ?? 'PAYER').'*'.now()->format('Ymd').'*'.now()->format('Hi').'*1*X*005010X279A1',
            'ST*270*0001',
            'BHT*0022*13*'.$trace.'*'.now()->format('Ymd').'*'.now()->format('Hi'),
            'HL*1**20*1',
            'NM1*PR*2*'.($payer->name ?? 'Payer').'*****PI*'.($payer->electronic_payer_id ?? 'PAYER'),
            'HL*2*1*21*1',
            'NM1*1P*2*Demo Medical Practice*****XX*1234567890',
            'HL*3*2*22*0',
            'TRN*1*'.$trace.'*DOCREV',
            'NM1*IL*1*'.$patient->last_name.'*'.$patient->first_name.'****MI*'.$memberId,
            'DMG*D8*'.$dob.'*'.strtoupper(substr($patient->gender ?? 'U', 0, 1)),
            'DTP*291*D8*'.str_replace('-', '', $serviceDate),
            'EQ*30',
            'SE*12*0001',
            'GE*1*1',
            'IEA*1*000000001',
        ];

        return implode('~', $segments).'~';
    }
}
