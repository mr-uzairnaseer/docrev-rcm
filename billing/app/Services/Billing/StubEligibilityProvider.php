<?php

namespace App\Services\Billing;

use App\Models\Patient;
use App\Models\Payer;
use Illuminate\Support\Str;

class StubEligibilityProvider implements EligibilityProviderInterface
{
    public function checkEligibility(Patient $patient, Payer $payer, string $edi270, string $serviceDate): array
    {
        $trace = 'ELG-'.strtoupper(Str::random(8));
        $memberId = $patient->insurance_member_id ?? 'MEM-DEMO-001';
        $planName = $payer->name.' PPO';

        $segments = [
            'ISA*00*          *00*          *ZZ*'.($payer->electronic_payer_id ?? 'PAYER').'*ZZ*DOCREV         *'.now()->format('ymd').'*'.now()->format('Hi').'*^*00501*000000001*0*P*:',
            'GS*HB*'.($payer->electronic_payer_id ?? 'PAYER').'*DOCREV*'.now()->format('Ymd').'*'.now()->format('Hi').'*1*X*005010X279A1',
            'ST*271*0001',
            'BHT*0022*11*'.$trace.'*'.now()->format('Ymd').'*'.now()->format('Hi'),
            'HL*1**20*1',
            'NM1*PR*2*'.$payer->name.'*****PI*'.($payer->electronic_payer_id ?? 'PAYER'),
            'HL*2*1*22*0',
            'NM1*IL*1*'.$patient->last_name.'*'.$patient->first_name.'****MI*'.$memberId,
            'REF*6P*'.$planName,
            'EB*1**30',
            'EB*B*30*25.00',
            'EB*C*30*500.00',
            'EB*A*30*20.00',
            'MSG*Active coverage confirmed (stub clearinghouse)',
            'SE*13*0001',
            'GE*1*1',
            'IEA*1*000000001',
        ];

        return [
            'edi_271' => implode('~', $segments).'~',
            'message' => 'Eligibility verified via stub provider. Replace with Availity/Change Healthcare when configured.',
        ];
    }
}
