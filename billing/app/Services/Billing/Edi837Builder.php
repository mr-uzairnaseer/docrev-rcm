<?php

namespace App\Services\Billing;

use App\Models\Claim;
use Carbon\Carbon;

class Edi837Builder
{
    public function build(Claim $claim): string
    {
        $claim->load(['patient', 'payer', 'claimLines', 'organization', 'originalClaim']);

        $controlNumber = str_pad((string) $claim->id, 9, '0', STR_PAD_LEFT);
        $now = Carbon::now();
        $segments = [];

        $segments[] = $this->segment('ISA', '00', str_repeat(' ', 10), '00', str_repeat(' ', 10), 'ZZ', $this->pad($claim->billing_provider_npi, 15), 'ZZ', $this->pad($claim->payer->electronic_payer_id ?? 'PAYER', 15), $now->format('ymd'), $now->format('Hi'), '^', '00501', $controlNumber, '0', 'P', ':');
        $segments[] = $this->segment('GS', 'HC', $claim->billing_provider_npi, $claim->payer->electronic_payer_id ?? 'PAYER', $now->format('Ymd'), $now->format('Hi'), '1', 'X', '005010X222A1');
        $segments[] = $this->segment('ST', '837', str_pad((string) $claim->id, 4, '0', STR_PAD_LEFT), '005010X222A1');
        $segments[] = $this->segment('BHT', '0019', '00', $claim->claim_number, $now->format('Ymd'), $now->format('Hi'), 'CH');

        $segments[] = $this->segment('NM1', '41', '2', $claim->organization->name, '', '', '', '', '46', $claim->billing_provider_npi);
        $segments[] = $this->segment('NM1', '40', '2', $claim->payer->name, '', '', '', '', '46', $claim->payer->electronic_payer_id ?? 'PAYER');

        $segments[] = $this->segment('HL', '1', '', '20', '1');
        $segments[] = $this->segment('NM1', '85', '2', $claim->organization->name, '', '', '', '', 'XX', $claim->billing_provider_npi);
        $segments[] = $this->segment('NM1', '82', '1', $claim->organization->name, '', '', '', '', 'XX', $claim->rendering_provider_npi);

        $segments[] = $this->segment('HL', '2', '1', '22', '0');
        $segments[] = $this->segment('SBR', 'P', '18', '', '', '', '', '', 'CI');
        $segments[] = $this->segment('NM1', 'IL', '1', $claim->patient->last_name, $claim->patient->first_name, '', '', '', 'MI', $claim->patient->mrn ?? 'UNKNOWN');
        $segments[] = $this->segment('DMG', 'D8', $claim->patient->date_of_birth->format('Ymd'), $this->mapGender($claim->patient->gender));

        $segments[] = $this->segment('CLM', $claim->claim_number, $this->formatAmount($claim->total_charge_amount), '', '', $this->claimFrequencyComposite($claim), '', 'Y', 'A', 'Y', 'Y', 'C');

        if ($claim->originalClaim) {
            $segments[] = $this->segment('REF', 'F8', $claim->originalClaim->claim_number);
        }

        $segments[] = $this->segment('DTP', '434', 'RD8', $claim->service_date_from->format('Ymd').'-'.$claim->service_date_to->format('Ymd'));

        foreach ($claim->icd10_codes ?? [] as $index => $code) {
            $segments[] = $this->segment('HI', 'ABK:'.str_replace('.', '', $code));
        }

        foreach ($claim->claimLines as $line) {
            $segments[] = $this->segment('LX', (string) $line->line_number);
            $modComposite = 'HC:'.($line->cpt_code ?? 'UNKNOWN');
            if ($line->modifier_1) {
                $modComposite .= ':'.$line->modifier_1;
            }
            $segments[] = $this->segment('SV1', $modComposite, $this->formatAmount($line->charge_amount), 'UN', (string) $line->units, $claim->place_of_service ?? '11', '', '', '', '', '0');
            $segments[] = $this->segment('DTP', '472', 'D8', $claim->service_date_from->format('Ymd'));
        }

        $segmentCount = count($segments) + 1;
        $segments[] = $this->segment('SE', (string) $segmentCount, str_pad((string) $claim->id, 4, '0', STR_PAD_LEFT));
        $segments[] = $this->segment('GE', '1', '1');
        $segments[] = $this->segment('IEA', '1', $controlNumber);

        return implode("~\n", $segments)."~\n";
    }

    private function segment(string ...$elements): string
    {
        return implode('*', $elements);
    }

    private function pad(string $value, int $length): string
    {
        return str_pad(substr($value, 0, $length), $length, ' ');
    }

    private function formatAmount(float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function mapGender(?string $gender): string
    {
        return match (strtolower((string) $gender)) {
            'male', 'm' => 'M',
            'female', 'f' => 'F',
            default => 'U',
        };
    }

    private function claimFrequencyComposite(Claim $claim): string
    {
        $pos = $claim->place_of_service ?? '11';
        $frequency = $claim->frequency_code ?? '1';

        return $pos.':B:'.$frequency;
    }
}
