<?php

namespace App\Services\Billing;

use App\Models\Charge;
use App\Models\Claim;
use App\Models\Payer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClaimFormBuilder
{
    private const POS_LABELS = [
        '11' => 'Office',
        '12' => 'Home',
        '21' => 'Inpatient Hospital',
        '22' => 'On Campus-Outpatient Hospital',
        '23' => 'Emergency Room',
        '02' => 'Telehealth',
    ];

    private const REVENUE_BY_CPT = [
        '99211' => '0510',
        '99212' => '0510',
        '99213' => '0510',
        '99214' => '0510',
        '99215' => '0510',
        '99203' => '0510',
        '99204' => '0510',
        '99205' => '0510',
    ];

    public function buildForEncounterUuid(string $encounterUuid, string $formType): array
    {
        $charges = Charge::query()
            ->where('encounter_external_id', $encounterUuid)
            ->with(['patient.organization'])
            ->orderBy('service_date')
            ->get();

        if ($charges->isEmpty()) {
            throw new \RuntimeException('No billing charges found for this encounter. Sync the encounter to billing first.');
        }

        $patient = $charges->first()->patient;
        $organization = $patient->organization;

        $claim = Claim::query()
            ->where('patient_id', $patient->id)
            ->whereHas('claimLines.charge', fn ($q) => $q->where('encounter_external_id', $encounterUuid))
            ->with(['payer', 'claimLines'])
            ->latest('id')
            ->first();

        $payer = $claim?->payer ?? Payer::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $context = $this->buildContext($charges, $patient, $organization, $payer, $claim, $encounterUuid);

        return match ($this->normalizeFormType($formType)) {
            'hcfa' => $this->buildHcfa($context),
            'ub04' => $this->buildUb04($context),
            default => throw new \InvalidArgumentException('Form type must be hcfa or ub04.'),
        };
    }

    public function buildForClaim(Claim $claim, string $formType): array
    {
        $claim->load(['patient.organization', 'payer', 'claimLines.charge']);

        $charges = $claim->claimLines
            ->map(fn ($line) => $line->charge)
            ->filter()
            ->values();

        if ($charges->isEmpty()) {
            throw new \RuntimeException('Claim has no charge lines to render on a paper form.');
        }

        $encounterUuid = $charges->first()->encounter_external_id ?? $claim->uuid;

        $context = $this->buildContext(
            $charges,
            $claim->patient,
            $claim->patient->organization,
            $claim->payer,
            $claim,
            $encounterUuid
        );

        return match ($this->normalizeFormType($formType)) {
            'hcfa' => $this->buildHcfa($context),
            'ub04' => $this->buildUb04($context),
            default => throw new \InvalidArgumentException('Form type must be hcfa or ub04.'),
        };
    }

    private function normalizeFormType(string $formType): string
    {
        $normalized = Str::lower(str_replace(['-', '_', ' '], '', $formType));

        return match ($normalized) {
            'hcfa', 'cms1500', '1500' => 'hcfa',
            'ub04', 'cms1450', '1450' => 'ub04',
            default => $normalized,
        };
    }

    private function buildContext(
        Collection $charges,
        $patient,
        $organization,
        ?Payer $payer,
        ?Claim $claim,
        string $encounterUuid
    ): array {
        $icd10Codes = collect($charges->first()->icd10_codes ?? [])->filter()->values()->all();
        $serviceDates = $charges->pluck('service_date')->sort();
        $placeOfService = $claim?->place_of_service ?? '11';
        $totalCharges = $charges->sum('charge_amount');

        return [
            'encounter_uuid' => $encounterUuid,
            'claim_number' => $claim?->claim_number ?? 'DRAFT-'.strtoupper(Str::substr($encounterUuid, 0, 8)),
            'generated_at' => now()->toIso8601String(),
            'patient' => $patient,
            'organization' => $organization,
            'payer' => $payer,
            'claim' => $claim,
            'charges' => $charges,
            'icd10_codes' => $icd10Codes,
            'service_date_from' => $serviceDates->first()?->format('Y-m-d'),
            'service_date_to' => $serviceDates->last()?->format('Y-m-d'),
            'place_of_service' => $placeOfService,
            'place_of_service_label' => self::POS_LABELS[$placeOfService] ?? 'Office',
            'total_charges' => round((float) $totalCharges, 2),
            'rendering_npi' => $claim?->rendering_provider_npi ?? $organization->npi,
            'billing_npi' => $claim?->billing_provider_npi ?? $organization->npi,
            'tax_id' => $organization->tax_id,
            'org_address' => $this->formatAddress($organization->address),
        ];
    }

    private function buildHcfa(array $context): array
    {
        $patient = $context['patient'];
        $payer = $context['payer'];
        $patientName = $this->patientName($patient);
        $memberId = $patient->insurance_member_id ?? $patient->mrn ?? 'MEM-'.$patient->id;

        $lines = $context['charges']->values()->map(function (Charge $charge, int $index) use ($context) {
            $procedure = $charge->cpt_code ?? $charge->hcpcs_code ?? 'UNKNOWN';
            $modifiers = collect([$charge->modifier_1, $charge->modifier_2])->filter()->implode(',');

            return [
                'line' => $index + 1,
                'service_date' => $charge->service_date?->format('Y-m-d'),
                'place_of_service' => $context['place_of_service'],
                'place_of_service_label' => $context['place_of_service_label'],
                'procedure' => $procedure,
                'modifiers' => $modifiers,
                'diagnosis_pointer' => $this->diagnosisPointerLabel($charge->diagnosis_pointers),
                'charges' => number_format((float) $charge->charge_amount, 2, '.', ''),
                'units' => (string) ($charge->units ?? 1),
                'rendering_npi' => $context['rendering_npi'],
            ];
        })->all();

        return [
            'form_type' => 'hcfa',
            'title' => 'HEALTH INSURANCE CLAIM FORM (CMS-1500 / HCFA)',
            'generated_at' => $context['generated_at'],
            'encounter_uuid' => $context['encounter_uuid'],
            'claim_number' => $context['claim_number'],
            'fields' => [
                ['box' => '1', 'label' => 'TYPE OF INSURANCE', 'value' => $this->insuranceTypeLabel($payer)],
                ['box' => '1a', 'label' => "INSURED'S I.D. NUMBER", 'value' => $memberId],
                ['box' => '2', 'label' => "PATIENT'S NAME", 'value' => $patientName],
                ['box' => '3', 'label' => "PATIENT'S BIRTH DATE / SEX", 'value' => $patient->date_of_birth?->format('m/d/Y').' / '.strtoupper(substr($patient->gender ?? 'U', 0, 1))],
                ['box' => '4', 'label' => "INSURED'S NAME", 'value' => $patientName],
                ['box' => '5', 'label' => "PATIENT'S ADDRESS", 'value' => $context['org_address']],
                ['box' => '6', 'label' => 'PATIENT RELATIONSHIP TO INSURED', 'value' => 'SELF'],
                ['box' => '11', 'label' => "INSURED'S POLICY GROUP", 'value' => $patient->insurance_group_number ?? 'GRP-'.$patient->id],
                ['box' => '21', 'label' => 'DIAGNOSIS OR NATURE OF ILLNESS (ICD-10-CM)', 'value' => $this->formatDiagnoses($context['icd10_codes'])],
                ['box' => '23', 'label' => 'PRIOR AUTHORIZATION NUMBER', 'value' => 'AUTH-'.strtoupper(Str::substr($context['encounter_uuid'], 0, 8))],
                ['box' => '25', 'label' => 'FEDERAL TAX I.D. NUMBER', 'value' => ($context['tax_id'] ?? '—').' (EIN)'],
                ['box' => '28', 'label' => 'TOTAL CHARGE', 'value' => '$'.number_format($context['total_charges'], 2)],
                ['box' => '31', 'label' => 'SIGNATURE OF PHYSICIAN', 'value' => $context['organization']->name],
                ['box' => '33', 'label' => 'BILLING PROVIDER INFO', 'value' => $context['organization']->name.', NPI: '.$context['billing_npi']],
            ],
            'service_lines' => $lines,
            'payer' => $payer ? ['name' => $payer->name, 'electronic_payer_id' => $payer->electronic_payer_id] : null,
        ];
    }

    private function buildUb04(array $context): array
    {
        $patient = $context['patient'];
        $payer = $context['payer'];
        $typeOfBill = $this->typeOfBillForPos($context['place_of_service']);

        $revenueLines = $context['charges']->values()->map(function (Charge $charge) use ($context) {
            $procedure = $charge->cpt_code ?? $charge->hcpcs_code ?? 'UNKNOWN';
            $revenueCode = self::REVENUE_BY_CPT[$procedure] ?? '0450';
            $amount = number_format((float) $charge->charge_amount, 2, '.', '');

            return [
                'fl42' => $revenueCode,
                'fl43' => $this->revenueDescription($revenueCode),
                'fl44' => 'HC:'.$procedure,
                'fl45' => (string) ($charge->units ?? 1),
                'fl46' => $amount,
                'fl47' => $amount,
            ];
        })->all();

        return [
            'form_type' => 'ub04',
            'title' => 'UNIFORM BILL (UB-04 / CMS-1450)',
            'generated_at' => $context['generated_at'],
            'encounter_uuid' => $context['encounter_uuid'],
            'claim_number' => $context['claim_number'],
            'fields' => [
                ['fl' => '1', 'label' => 'BILLING PROVIDER NAME / ADDRESS', 'value' => $context['organization']->name.' — '.$context['org_address']],
                ['fl' => '3a', 'label' => 'PATIENT CONTROL NUMBER', 'value' => $context['claim_number']],
                ['fl' => '3b', 'label' => 'MEDICAL RECORD NUMBER', 'value' => $patient->mrn ?? 'MRN-'.$patient->id],
                ['fl' => '4', 'label' => 'TYPE OF BILL', 'value' => $typeOfBill],
                ['fl' => '6', 'label' => 'STATEMENT COVERS PERIOD', 'value' => $context['service_date_from'].' to '.$context['service_date_to']],
                ['fl' => '8', 'label' => 'PATIENT NAME', 'value' => $this->patientName($patient)],
                ['fl' => '10', 'label' => 'BIRTH DATE / SEX', 'value' => $patient->date_of_birth?->format('m/d/Y').' / '.strtoupper(substr($patient->gender ?? 'U', 0, 1))],
                ['fl' => '12', 'label' => 'ADMISSION DATE', 'value' => $context['service_date_from']],
                ['fl' => '14', 'label' => 'ADMISSION TYPE', 'value' => '3 (Elective)'],
                ['fl' => '15', 'label' => 'POINT OF ORIGIN', 'value' => '1 (Physician Referral)'],
                ['fl' => '17', 'label' => 'DISCHARGE STATUS', 'value' => '01 (Discharged to home)'],
                ['fl' => '50', 'label' => 'PAYER NAME', 'value' => $payer?->name ?? 'Primary Payer'],
                ['fl' => '56', 'label' => 'BILLING PROVIDER NPI', 'value' => $context['billing_npi']],
                ['fl' => '57', 'label' => 'OTHER PROVIDER ID', 'value' => $context['tax_id'] ?? '—'],
                ['fl' => '67', 'label' => 'PRINCIPAL DIAGNOSIS (ICD-10-CM)', 'value' => $this->formatDiagnoses($context['icd10_codes'])],
                ['fl' => '80', 'label' => 'REMARKS', 'value' => 'Generated from EHR encounter '.$context['encounter_uuid']],
            ],
            'revenue_lines' => $revenueLines,
            'totals' => [
                'total_charges' => '$'.number_format($context['total_charges'], 2),
            ],
            'payer' => $payer ? ['name' => $payer->name, 'electronic_payer_id' => $payer->electronic_payer_id] : null,
        ];
    }

    private function patientName($patient): string
    {
        return trim($patient->last_name.', '.$patient->first_name);
    }

    private function formatAddress(?array $address): string
    {
        if (! is_array($address) || $address === []) {
            return '123 Medical Center Dr, Suite 100, City, ST 12345';
        }

        $line1 = $address['line1'] ?? $address['street'] ?? '';
        $line2 = $address['line2'] ?? '';
        $city = $address['city'] ?? '';
        $state = $address['state'] ?? '';
        $zip = $address['zip'] ?? $address['postal_code'] ?? '';

        return trim(implode(', ', array_filter([
            trim($line1.' '.$line2),
            trim($city.', '.$state.' '.$zip),
        ])));
    }

    private function formatDiagnoses(array $codes): string
    {
        if ($codes === []) {
            return 'Z00.00';
        }

        return collect($codes)
            ->take(12)
            ->values()
            ->map(fn ($code, $index) => chr(65 + $index).'. '.$code)
            ->implode(' | ');
    }

    private function diagnosisPointerLabel(?array $pointers): string
    {
        if (! is_array($pointers) || $pointers === []) {
            return 'A';
        }

        return collect($pointers)
            ->map(fn ($pointer) => chr(64 + (int) $pointer))
            ->implode('');
    }

    private function insuranceTypeLabel(?Payer $payer): string
    {
        if (! $payer) {
            return 'OTHER [X]';
        }

        $type = Str::lower($payer->payer_type ?? '');

        return match (true) {
            str_contains($type, 'medicare') => 'MEDICARE [X]',
            str_contains($type, 'medicaid') => 'MEDICAID [X]',
            str_contains($type, 'tricare') => 'TRICARE [X]',
            str_contains($type, 'champva') => 'CHAMPVA [X]',
            default => 'GROUP HEALTH PLAN [X]',
        };
    }

    private function typeOfBillForPos(string $placeOfService): string
    {
        return match ($placeOfService) {
            '21' => '111 (Hospital Inpatient)',
            '22', '23' => '131 (Hospital Outpatient)',
            default => '831 (Clinic / Outpatient)',
        };
    }

    private function revenueDescription(string $code): string
    {
        return match ($code) {
            '0510' => 'Clinic — General Classification',
            '0450' => 'Emergency Room — General Classification',
            '0761' => 'Treatment Room',
            default => 'Revenue Code '.$code,
        };
    }
}
