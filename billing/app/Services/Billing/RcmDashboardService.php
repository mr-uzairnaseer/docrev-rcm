<?php

namespace App\Services\Billing;

use App\Models\Charge;
use App\Models\Claim;
use App\Models\ClaimDenial;
use App\Models\ClaimPayment;
use App\Models\EraRemittance;
use App\Models\PatientPayment;

class RcmDashboardService
{
    public function summary(int $organizationId): array
    {
        $claims = Claim::forOrganization($organizationId);
        $charges = Charge::forOrganization($organizationId);

        $totalCharges = (float) $charges->sum('charge_amount');
        $readyCharges = (clone $charges)->where('status', Charge::STATUS_READY)->count();
        $totalClaimed = (float) (clone $claims)->whereNotIn('status', [Claim::STATUS_DRAFT])->sum('total_charge_amount');
        $totalPaid = (float) (clone $claims)->sum('paid_amount');
        $patientBalance = (float) (clone $claims)->sum('patient_responsibility');
        $patientPayments = (float) PatientPayment::forOrganization($organizationId)->sum('amount');

        return [
            'charges' => [
                'total' => $charges->count(),
                'ready' => $readyCharges,
                'total_amount' => round($totalCharges, 2),
            ],
            'claims' => [
                'total' => $claims->count(),
                'draft' => (clone $claims)->where('status', Claim::STATUS_DRAFT)->count(),
                'submitted' => (clone $claims)->where('status', Claim::STATUS_SUBMITTED)->count(),
                'paid' => (clone $claims)->where('status', Claim::STATUS_PAID)->count(),
                'denied' => (clone $claims)->where('status', Claim::STATUS_DENIED)->count(),
                'partial' => (clone $claims)->where('status', Claim::STATUS_PARTIAL)->count(),
                'total_billed' => round($totalClaimed, 2),
                'total_paid' => round($totalPaid, 2),
            ],
            'ar' => [
                'patient_responsibility' => round($patientBalance, 2),
                'patient_payments_received' => round($patientPayments, 2),
            ],
            'eras' => [
                'total' => EraRemittance::forOrganization($organizationId)->count(),
                'total_posted' => (float) EraRemittance::forOrganization($organizationId)->sum('total_payment_amount'),
            ],
            'denials' => [
                'open' => ClaimDenial::forOrganization($organizationId)->where('status', ClaimDenial::STATUS_OPEN)->count(),
                'appealed' => ClaimDenial::forOrganization($organizationId)->where('status', ClaimDenial::STATUS_APPEALED)->count(),
            ],
            'aging' => (function() use ($claims) {
                $aging = ['0_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '91_plus' => 0.0];
                $outstandingClaims = (clone $claims)->whereIn('status', ['submitted', 'partial', 'denied'])->get();
                foreach ($outstandingClaims as $claim) {
                    $date = $claim->submitted_at ?? $claim->created_at;
                    $days = $date->diffInDays(now());
                    $outstanding = (float) $claim->total_charge_amount - (float) $claim->paid_amount;
                    if ($outstanding <= 0) continue;
                    if ($days <= 30) $aging['0_30'] += $outstanding;
                    elseif ($days <= 60) $aging['31_60'] += $outstanding;
                    elseif ($days <= 90) $aging['61_90'] += $outstanding;
                    else $aging['91_plus'] += $outstanding;
                }
                return array_map(function($v) { return round($v, 2); }, $aging);
            })(),
        ];
    }
}
