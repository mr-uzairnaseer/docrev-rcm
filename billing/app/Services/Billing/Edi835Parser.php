<?php

namespace App\Services\Billing;

class Edi835Parser
{
    /**
     * Parse a basic ERA 835 into structured payment data.
     * Production use requires full X12 835 segment parsing.
     */
    public function parse(string $edi835): array
    {
        $segments = preg_split('/[~\\n\\r]+/', trim($edi835));
        $payments = [];
        $currentClaim = null;

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $parts = explode('*', $segment);
            $type = $parts[0] ?? '';

            if ($type === 'CLP') {
                $currentClaim = [
                    'claim_number' => $parts[1] ?? null,
                    'status' => $parts[2] ?? null,
                    'total_charge' => isset($parts[3]) ? (float) $parts[3] : 0,
                    'paid_amount' => isset($parts[4]) ? (float) $parts[4] : 0,
                    'patient_responsibility' => isset($parts[5]) ? (float) $parts[5] : 0,
                    'service_lines' => [],
                ];
                $payments[] = &$currentClaim;
            }

            if ($type === 'SVC' && $currentClaim !== null) {
                $currentClaim['service_lines'][] = [
                    'procedure' => $parts[1] ?? null,
                    'charge' => isset($parts[2]) ? (float) $parts[2] : 0,
                    'paid' => isset($parts[3]) ? (float) $parts[3] : 0,
                ];
            }
        }

        return [
            'claims' => $payments,
            'segment_count' => count($segments),
        ];
    }
}
