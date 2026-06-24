<?php

namespace App\Services\Billing;

use App\Models\Claim;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimExportService
{
    private const HEADERS = [
        'Claim Number',
        'Claim UUID',
        'Frequency Code',
        'Original Claim #',
        'Status',
        'Patient',
        'MRN',
        'Payer',
        'Service From',
        'Service To',
        'Line #',
        'CPT',
        'Modifier 1',
        'Modifier 2',
        'Units',
        'Line Amount',
        'Total Charge',
        'Paid Amount',
        'Patient Responsibility',
        'ICD-10 Codes',
        'Place of Service',
        'Rendering NPI',
        'Billing NPI',
        'Submitted At',
        'Paid At',
        'Created At',
    ];

    public function export(int $organizationId, Request $request): StreamedResponse
    {
        $query = $this->buildQuery($organizationId, $request);
        $filename = 'docrev-claims-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::HEADERS);

            $query->chunk(100, function ($claims) use ($handle) {
                foreach ($claims as $claim) {
                    $this->writeClaimRows($handle, $claim);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildQuery(int $organizationId, Request $request): Builder
    {
        $query = Claim::forOrganization($organizationId)
            ->with(['patient', 'payer', 'claimLines', 'originalClaim'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->where('service_date_from', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('service_date_to', '<=', $to);
        }

        return $query;
    }

    private function writeClaimRows($handle, Claim $claim): void
    {
        $patientName = $claim->patient
            ? trim($claim->patient->first_name.' '.$claim->patient->last_name)
            : '';
        $icd10 = is_array($claim->icd10_codes)
            ? implode('; ', $claim->icd10_codes)
            : (string) $claim->icd10_codes;

        $base = [
            $claim->claim_number,
            $claim->uuid,
            $claim->frequency_code ?? '1',
            $claim->originalClaim?->claim_number ?? '',
            $claim->status,
            $patientName,
            $claim->patient?->mrn ?? '',
            $claim->payer?->name ?? '',
            $claim->service_date_from?->format('Y-m-d') ?? '',
            $claim->service_date_to?->format('Y-m-d') ?? '',
        ];

        $lines = $claim->claimLines;
        if ($lines->isEmpty()) {
            fputcsv($handle, array_merge($base, [
                '',
                '',
                '',
                '',
                '',
                '',
                $claim->total_charge_amount,
                $claim->paid_amount,
                $claim->patient_responsibility,
                $icd10,
                $claim->place_of_service,
                $claim->rendering_provider_npi,
                $claim->billing_provider_npi,
                $claim->submitted_at?->format('Y-m-d H:i:s') ?? '',
                $claim->paid_at?->format('Y-m-d H:i:s') ?? '',
                $claim->created_at?->format('Y-m-d H:i:s') ?? '',
            ]));

            return;
        }

        foreach ($lines as $line) {
            fputcsv($handle, array_merge($base, [
                $line->line_number,
                $line->cpt_code,
                $line->modifier_1 ?? '',
                $line->modifier_2 ?? '',
                $line->units,
                $line->charge_amount,
                $claim->total_charge_amount,
                $claim->paid_amount,
                $claim->patient_responsibility,
                $icd10,
                $claim->place_of_service,
                $claim->rendering_provider_npi,
                $claim->billing_provider_npi,
                $claim->submitted_at?->format('Y-m-d H:i:s') ?? '',
                $claim->paid_at?->format('Y-m-d H:i:s') ?? '',
                $claim->created_at?->format('Y-m-d H:i:s') ?? '',
            ]));
        }
    }
}
