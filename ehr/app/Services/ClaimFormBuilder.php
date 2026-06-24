<?php

namespace App\Services;

use App\Models\Encounter;
use Illuminate\Support\Str;

class ClaimFormBuilder
{
    private const POS_LABELS = [
        '02' => 'Telehealth Provided Other than in Patient\'s Home',
        '11' => 'Office',
        '12' => 'Home',
        '21' => 'Inpatient Hospital',
        '22' => 'On Campus-Outpatient Hospital',
        '23' => 'Emergency Room — Hospital',
    ];

    private const REVENUE_BY_CPT = [
        '99211' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
        '99212' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
        '99213' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
        '99214' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
        '99215' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
        '99203' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
        '99204' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
        '99205' => ['code' => '0510', 'desc' => 'Clinic — General Classification'],
    ];

    public function buildForEncounter(Encounter $encounter, string $formType): array
    {
        $encounter->load(['patient.organization', 'provider', 'location', 'diagnoses', 'charges']);

        if ($encounter->charges->isEmpty()) {
            throw new \RuntimeException('This encounter has no charge lines to render on a claim form.');
        }

        if ($encounter->diagnoses->isEmpty()) {
            throw new \RuntimeException('This encounter has no diagnosis codes for the claim form.');
        }

        $context = $this->buildContext($encounter);

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

    private function buildContext(Encounter $encounter): array
    {
        $patient = $encounter->patient;
        $organization = $patient->organization;
        $serviceDate = $encounter->encounter_date;
        $placeOfService = $encounter->location?->place_of_service_code ?? '11';
        $diagnoses = $encounter->diagnoses->values();
        $icd10Codes = $diagnoses->pluck('icd10_code')->filter()->values()->all();
        $totalCharges = $encounter->charges->sum('charge_amount');
        $renderingNpi = $encounter->provider?->npi ?? $organization->npi;
        $patientAddr = $this->parseAddress($patient->address);
        $orgAddr = $this->parseAddress($organization->address);

        return [
            'encounter_uuid' => $encounter->uuid,
            'claim_number' => 'DRAFT-'.strtoupper(Str::substr($encounter->uuid, 0, 8)),
            'generated_at' => now()->toIso8601String(),
            'patient' => $patient,
            'organization' => $organization,
            'location' => $encounter->location,
            'charges' => $encounter->charges,
            'diagnoses' => $diagnoses,
            'icd10_codes' => $icd10Codes,
            'service_date' => $serviceDate,
            'service_date_from' => $serviceDate?->format('Y-m-d'),
            'service_date_to' => $serviceDate?->format('Y-m-d'),
            'service_date_mmddyy' => $this->splitDate($serviceDate),
            'place_of_service' => $placeOfService,
            'place_of_service_label' => self::POS_LABELS[$placeOfService] ?? 'Office',
            'total_charges' => round((float) $totalCharges, 2),
            'rendering_npi' => $renderingNpi,
            'billing_npi' => $organization->npi,
            'tax_id' => $organization->tax_id ?? '12-3456789',
            'patient_address' => $patientAddr,
            'org_address' => $orgAddr,
            'provider_name' => $encounter->provider
                ? trim($encounter->provider->first_name.' '.$encounter->provider->last_name)
                : $organization->name,
            'provider_credentials' => $encounter->provider?->credentials ?? 'MD',
        ];
    }

    private function buildHcfa(array $ctx): array
    {
        $patient = $ctx['patient'];
        $patientName = $this->patientName($patient);
        $memberId = $patient->mrn ?? 'MEM-'.$patient->id;
        $gender = strtoupper(substr($patient->gender ?? 'U', 0, 1));

        $serviceLines = $ctx['charges']->values()->map(function ($charge, int $index) use ($ctx) {
            $procedure = $charge->cpt_code ?? $charge->hcpcs_code ?? '';
            $mods = array_values(array_filter([$charge->modifier_1, $charge->modifier_2]));
            $date = $this->splitDate($ctx['service_date']);

            return [
                'line' => $index + 1,
                'from_mm' => $date['mm'],
                'from_dd' => $date['dd'],
                'from_yy' => $date['yy'],
                'to_mm' => $date['mm'],
                'to_dd' => $date['dd'],
                'to_yy' => $date['yy'],
                'place_of_service' => $ctx['place_of_service'],
                'emg' => '',
                'cpt_hcpcs' => $procedure,
                'modifier_1' => $mods[0] ?? '',
                'modifier_2' => $mods[1] ?? '',
                'modifier_3' => $mods[2] ?? '',
                'modifier_4' => $mods[3] ?? '',
                'diagnosis_pointer' => $this->diagnosisPointerLabel($charge->diagnosis_pointers),
                'charges' => number_format((float) $charge->charge_amount, 2, '.', ''),
                'units' => (string) ($charge->units ?? 1),
                'epsdt' => '',
                'rendering_npi' => $ctx['rendering_npi'],
            ];
        })->all();

        while (count($serviceLines) < 6) {
            $serviceLines[] = $this->emptyHcfaLine(count($serviceLines) + 1);
        }

        $diagnosisRows = $ctx['diagnoses']->take(12)->values()->map(function ($dx, int $i) {
            return [
                'pointer' => chr(65 + $i),
                'code' => str_replace('.', '', $dx->icd10_code),
                'description' => $dx->description ?? '',
            ];
        })->all();

        while (count($diagnosisRows) < 12) {
            $diagnosisRows[] = ['pointer' => chr(65 + count($diagnosisRows)), 'code' => '', 'description' => ''];
        }

        return [
            'form_type' => 'hcfa',
            'title' => 'CMS-1500 (02/12) — Health Insurance Claim Form',
            'standard' => 'NUCC / CMS-1500 (02/12) — AAPC professional claim format',
            'generated_at' => $ctx['generated_at'],
            'encounter_uuid' => $ctx['encounter_uuid'],
            'claim_number' => $ctx['claim_number'],
            'hcfa' => [
                'pica' => ['carrier' => '', 'carrier_area' => ''],
                'box1_insurance_types' => $this->insuranceTypeOptions('group'),
                'box1a_insured_id' => $memberId,
                'box2_patient_name' => $patientName,
                'box3_dob' => $patient->date_of_birth?->format('m d y') ?? '',
                'box3_sex' => ['M' => $gender === 'M', 'F' => $gender === 'F'],
                'box4_insured_name' => $patientName,
                'box5_patient_address' => $ctx['patient_address'],
                'box6_relationship' => $this->relationshipOptions('self'),
                'box7_insured_address' => $ctx['patient_address'],
                'box8_reserved' => '',
                'box9_other_insured' => '',
                'box9a_other_policy' => '',
                'box9b_reserved' => '',
                'box9c_reserved' => '',
                'box9d_insurance_plan' => 'Commercial PPO',
                'box10_condition' => [
                    'employment' => $this->yesNoOptions('no'),
                    'auto_accident' => $this->yesNoOptions('no'),
                    'auto_accident_place' => '',
                    'other_accident' => $this->yesNoOptions('no'),
                    'claim_codes' => '',
                ],
                'box11_insured_policy' => [
                    'group_number' => 'GRP-'.$patient->id,
                    'a_insured_dob' => $patient->date_of_birth?->format('m d y') ?? '',
                    'a_sex' => ['M' => $gender === 'M', 'F' => $gender === 'F'],
                    'b_other_claim' => '',
                    'c_insurance_plan' => 'Commercial PPO',
                    'd_another_plan' => $this->yesNoOptions('no'),
                ],
                'box12_patient_signature' => 'SIGNATURE ON FILE',
                'box12_date' => $ctx['service_date']?->format('m d y') ?? '',
                'box13_insured_signature' => 'SIGNATURE ON FILE',
                'box14_illness_date' => $ctx['service_date_mmddyy'],
                'box14_qualifier' => '431',
                'box15_other_date' => ['date' => '', 'qualifier' => ''],
                'box16_unable_work' => ['from' => '', 'to' => ''],
                'box17_referring' => [
                    'name' => '',
                    'qualifier' => 'DN',
                    'npi' => '',
                ],
                'box18_hospitalization' => ['from' => '', 'to' => ''],
                'box19_additional' => '',
                'box20_outside_lab' => $this->yesNoOptions('no'),
                'box20_charges' => '',
                'box21_icd_indicator' => '0',
                'box21_diagnoses' => $diagnosisRows,
                'box22_resubmission' => ['code' => '', 'original_ref' => ''],
                'box23_prior_auth' => 'AUTH-'.strtoupper(Str::substr($ctx['encounter_uuid'], 0, 8)),
                'box24_lines' => $serviceLines,
                'box25_tax_id' => $ctx['tax_id'],
                'box25_tax_id_type' => ['ssn' => false, 'ein' => true],
                'box26_account' => $ctx['claim_number'],
                'box27_assignment' => $this->yesNoOptions('yes'),
                'box28_total_charge' => number_format($ctx['total_charges'], 2, '.', ''),
                'box29_amount_paid' => '0.00',
                'box30_reserved' => '',
                'box31_physician' => [
                    'signature' => $ctx['provider_name'].' '.$ctx['provider_credentials'],
                    'date' => $ctx['service_date']?->format('m d y') ?? '',
                ],
                'box32_service_facility' => [
                    'name' => $ctx['location']?->name ?? $ctx['organization']->name,
                    'address' => $this->formatAddressLine($ctx['org_address']),
                    'npi' => $ctx['rendering_npi'],
                ],
                'box33_billing_provider' => [
                    'phone' => $ctx['organization']->phone ?? '',
                    'name' => $ctx['organization']->name,
                    'address' => $this->formatAddressLine($ctx['org_address']),
                    'npi' => $ctx['billing_npi'],
                ],
            ],
            'footnotes' => [
                'Form CMS-1500 (02/12) approved by NUCC — professional paper claim standard per CMS & AAPC.',
                'ICD-10-CM codes required (Box 21 indicator 0). Diagnosis pointers link Box 24 to Box 21.',
                'Place of Service codes per CMS POS code set. Accept Assignment (Box 27) indicates participating provider.',
            ],
        ];
    }

    private function buildUb04(array $ctx): array
    {
        $patient = $ctx['patient'];
        $tob = $this->typeOfBillCode($ctx['place_of_service']);

        $revenueLines = $ctx['charges']->values()->map(function ($charge) {
            $procedure = $charge->cpt_code ?? $charge->hcpcs_code ?? '';
            $rev = self::REVENUE_BY_CPT[$procedure] ?? ['code' => '0450', 'desc' => 'Emergency Room — General Classification'];
            $amount = number_format((float) $charge->charge_amount, 2, '.', '');

            return [
                'fl42' => $rev['code'],
                'fl43' => $rev['desc'],
                'fl44' => 'HC:'.$procedure,
                'fl45' => (string) ($charge->units ?? 1),
                'fl46' => $amount,
                'fl47' => $amount,
            ];
        })->all();

        while (count($revenueLines) < 23) {
            $revenueLines[] = ['fl42' => '', 'fl43' => '', 'fl44' => '', 'fl45' => '', 'fl46' => '', 'fl47' => ''];
        }

        $diagnosisRows = $ctx['diagnoses']->take(18)->values()->map(function ($dx, int $i) {
            return [
                'qualifier' => $i === 0 ? '0' : '',
                'code' => str_replace('.', '', $dx->icd10_code),
                'description' => $dx->description ?? '',
            ];
        })->all();

        return [
            'form_type' => 'ub04',
            'title' => 'UB-04 (CMS-1450) — Uniform Bill',
            'standard' => 'CMS-1450 / NUBC UB-04 — institutional claim format per CMS & AAPC',
            'generated_at' => $ctx['generated_at'],
            'encounter_uuid' => $ctx['encounter_uuid'],
            'claim_number' => $ctx['claim_number'],
            'ub04' => [
                'fl1_billing_provider' => [
                    'name' => $ctx['organization']->name,
                    'address' => $this->formatAddressLine($ctx['org_address']),
                    'city_state_zip' => trim($ctx['org_address']['city'].', '.$ctx['org_address']['state'].' '.$ctx['org_address']['zip']),
                    'phone' => $ctx['organization']->phone ?? '',
                ],
                'fl2_pay_to' => $this->formatAddressLine($ctx['org_address']),
                'fl3a_patient_control' => $ctx['claim_number'],
                'fl3b_medical_record' => $patient->mrn ?? 'MRN-'.$patient->id,
                'fl4_type_of_bill' => $tob['code'],
                'fl4_type_of_bill_description' => $tob['description'],
                'fl4_type_of_bill_options' => $this->typeOfBillOptions($ctx['place_of_service']),
                'fl5_fed_tax_id' => $ctx['tax_id'],
                'fl6_statement_period' => [
                    'from' => $ctx['service_date_from'],
                    'through' => $ctx['service_date_to'],
                ],
                'fl8_patient_name' => $this->patientName($patient),
                'fl9_patient_address' => $ctx['patient_address'],
                'fl10_birth_date' => $patient->date_of_birth?->format('m/d/Y') ?? '',
                'fl10_sex' => ['M' => strtoupper(substr($patient->gender ?? 'U', 0, 1)) === 'M', 'F' => strtoupper(substr($patient->gender ?? 'U', 0, 1)) === 'F'],
                'fl12_admission_date' => $ctx['service_date_from'] ?? '',
                'fl13_admission_hour' => '',
                'fl14_admission_type' => $this->admissionTypeOptions('3'),
                'fl15_point_of_origin' => $this->pointOfOriginOptions('1'),
                'fl16_discharge_hour' => '',
                'fl17_discharge_status' => $this->dischargeStatusOptions('01'),
                'fl18_28_condition_codes' => array_fill(0, 11, ['code' => '', 'description' => '']),
                'fl31_34_occurrence_codes' => array_fill(0, 8, ['code' => '', 'date' => '']),
                'fl39_41_value_codes' => array_fill(0, 12, ['code' => '', 'amount' => '']),
                'fl42_47_lines' => $revenueLines,
                'fl47_total_charges' => number_format($ctx['total_charges'], 2, '.', ''),
                'fl50_payers' => [
                    ['name' => 'Primary Commercial Payer', 'health_plan_id' => 'UHC', 'release_info' => 'Y', 'assign_benefits' => 'Y', 'prior_payments' => '0.00', 'estimated_amount' => number_format($ctx['total_charges'], 2, '.', '')],
                    ['name' => '', 'health_plan_id' => '', 'release_info' => '', 'assign_benefits' => '', 'prior_payments' => '', 'estimated_amount' => ''],
                    ['name' => '', 'health_plan_id' => '', 'release_info' => '', 'assign_benefits' => '', 'prior_payments' => '', 'estimated_amount' => ''],
                ],
                'fl56_billing_provider_npi' => $ctx['billing_npi'],
                'fl57_other_provider_id' => $ctx['tax_id'],
                'fl66_diagnosis_qualifier' => '0',
                'fl67_diagnoses' => $diagnosisRows,
                'fl69_admitting_diagnosis' => $diagnosisRows[0]['code'] ?? '',
                'fl76_attending' => [
                    'npi' => $ctx['rendering_npi'],
                    'name' => $ctx['provider_name'],
                    'qualifier' => 'DN',
                ],
                'fl80_remarks' => 'Institutional claim generated from EHR encounter '.$ctx['encounter_uuid'],
            ],
            'footnotes' => [
                'Form CMS-1450 (UB-04) per National Uniform Billing Committee (NUBC) specifications.',
                'Type of Bill (FL 4) uses 4-digit code: facility type + claim frequency. Revenue codes FL 42 per NUBC.',
                'ICD-10-CM diagnosis qualifier 0 on FL 66. Principal diagnosis on FL 67 first line.',
            ],
        ];
    }

    private function emptyHcfaLine(int $num): array
    {
        return [
            'line' => $num, 'from_mm' => '', 'from_dd' => '', 'from_yy' => '',
            'to_mm' => '', 'to_dd' => '', 'to_yy' => '',
            'place_of_service' => '', 'emg' => '', 'cpt_hcpcs' => '',
            'modifier_1' => '', 'modifier_2' => '', 'modifier_3' => '', 'modifier_4' => '',
            'diagnosis_pointer' => '', 'charges' => '', 'units' => '', 'epsdt' => '', 'rendering_npi' => '',
        ];
    }

    private function insuranceTypeOptions(string $selected): array
    {
        $types = [
            ['key' => 'medicare', 'label' => 'MEDICARE'],
            ['key' => 'medicaid', 'label' => 'MEDICAID'],
            ['key' => 'tricare', 'label' => 'TRICARE'],
            ['key' => 'champva', 'label' => 'CHAMPVA'],
            ['key' => 'group', 'label' => 'GROUP HEALTH PLAN'],
            ['key' => 'feca', 'label' => 'FECA BLK LUNG'],
            ['key' => 'other', 'label' => 'OTHER'],
        ];

        return array_map(fn ($t) => array_merge($t, ['checked' => $t['key'] === $selected]), $types);
    }

    private function relationshipOptions(string $selected): array
    {
        $opts = [
            ['key' => 'self', 'label' => 'Self'],
            ['key' => 'spouse', 'label' => 'Spouse'],
            ['key' => 'child', 'label' => 'Child'],
            ['key' => 'other', 'label' => 'Other'],
        ];

        return array_map(fn ($o) => array_merge($o, ['checked' => $o['key'] === $selected]), $opts);
    }

    private function yesNoOptions(string $selected): array
    {
        return ['yes' => $selected === 'yes', 'no' => $selected === 'no'];
    }

    private function admissionTypeOptions(string $selected): array
    {
        $opts = [
            ['code' => '1', 'label' => '1 — Emergency'],
            ['code' => '2', 'label' => '2 — Urgent'],
            ['code' => '3', 'label' => '3 — Elective'],
            ['code' => '4', 'label' => '4 — Newborn'],
            ['code' => '5', 'label' => '5 — Trauma'],
        ];

        return array_map(fn ($o) => array_merge($o, ['selected' => $o['code'] === $selected]), $opts);
    }

    private function pointOfOriginOptions(string $selected): array
    {
        $opts = [
            ['code' => '1', 'label' => '1 — Non-healthcare facility / Physician referral'],
            ['code' => '2', 'label' => '2 — Clinic referral'],
            ['code' => '4', 'label' => '4 — Transfer from hospital'],
            ['code' => '9', 'label' => '9 — Information not available'],
        ];

        return array_map(fn ($o) => array_merge($o, ['selected' => $o['code'] === $selected]), $opts);
    }

    private function dischargeStatusOptions(string $selected): array
    {
        $opts = [
            ['code' => '01', 'label' => '01 — Discharged to home/self-care'],
            ['code' => '02', 'label' => '02 — Discharged to short-term hospital'],
            ['code' => '03', 'label' => '03 — Discharged to SNF'],
            ['code' => '30', 'label' => '30 — Still a patient'],
        ];

        return array_map(fn ($o) => array_merge($o, ['selected' => $o['code'] === $selected]), $opts);
    }

    private function typeOfBillOptions(string $pos): array
    {
        $opts = [
            ['code' => '0111', 'label' => '0111 — Hospital Inpatient Admit'],
            ['code' => '0131', 'label' => '0131 — Hospital Outpatient'],
            ['code' => '0831', 'label' => '0831 — Clinic / Outpatient'],
            ['code' => '0137', 'label' => '0137 — Replacement claim'],
        ];
        $selected = $this->typeOfBillCode($pos)['code'];

        return array_map(fn ($o) => array_merge($o, ['selected' => $o['code'] === $selected]), $opts);
    }

    private function typeOfBillCode(string $placeOfService): array
    {
        return match ($placeOfService) {
            '21' => ['code' => '0111', 'description' => 'Hospital Inpatient — Admit through discharge claim'],
            '22', '23' => ['code' => '0131', 'description' => 'Hospital Outpatient — Attending claim'],
            default => ['code' => '0831', 'description' => 'Clinic/Outpatient — Special facility outpatient'],
        };
    }

    private function patientName($patient): string
    {
        return trim($patient->last_name.', '.$patient->first_name);
    }

    private function parseAddress(?array $address): array
    {
        if (! is_array($address) || $address === []) {
            return [
                'street' => '123 Medical Center Dr',
                'street2' => 'Suite 100',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'zip' => '19103',
                'phone' => '',
            ];
        }

        return [
            'street' => $address['line1'] ?? $address['street'] ?? '',
            'street2' => $address['line2'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'zip' => $address['zip'] ?? $address['postal_code'] ?? '',
            'phone' => $address['phone'] ?? '',
        ];
    }

    private function formatAddressLine(array $addr): string
    {
        return trim(implode(', ', array_filter([
            trim($addr['street'].' '.$addr['street2']),
            trim($addr['city'].', '.$addr['state'].' '.$addr['zip']),
        ])));
    }

    private function splitDate($date): array
    {
        if (! $date) {
            return ['mm' => '', 'dd' => '', 'yy' => ''];
        }

        return [
            'mm' => $date->format('m'),
            'dd' => $date->format('d'),
            'yy' => $date->format('y'),
        ];
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
}
