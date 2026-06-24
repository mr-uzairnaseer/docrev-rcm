<?php

namespace App\Services\Billing;

class Edi271Parser
{
    /**
     * Parse a basic 271 response into coverage details.
     */
    public function parse(string $edi271): array
    {
        $segments = preg_split('/[~\\n\\r]+/', trim($edi271));
        $result = [
            'coverage_status' => 'unknown',
            'plan_name' => null,
            'copay_amount' => null,
            'deductible_amount' => null,
            'coinsurance_percent' => null,
            'message' => null,
        ];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $parts = explode('*', $segment);
            $type = $parts[0] ?? '';

            if ($type === 'EB') {
                $qualifier = $parts[1] ?? '';
                $coverageCode = $parts[2] ?? '';

                if ($qualifier === '1' && in_array($coverageCode, ['1', '2', '3', '4', '5'], true)) {
                    $result['coverage_status'] = 'active';
                }

                if ($qualifier === 'B' && isset($parts[3])) {
                    $result['copay_amount'] = (float) $parts[3];
                }

                if ($qualifier === 'C' && isset($parts[3])) {
                    $result['deductible_amount'] = (float) $parts[3];
                }

                if ($qualifier === 'A' && isset($parts[3])) {
                    $result['coinsurance_percent'] = (float) $parts[3];
                }
            }

            if ($type === 'MSG' && isset($parts[1])) {
                $result['message'] = $parts[1];
            }

            if ($type === 'REF' && ($parts[1] ?? '') === '6P' && isset($parts[2])) {
                $result['plan_name'] = $parts[2];
            }
        }

        if ($result['coverage_status'] === 'unknown' && $result['message']) {
            $result['coverage_status'] = str_contains(strtolower($result['message']), 'inactive')
                ? 'inactive'
                : 'active';
        }

        return $result;
    }
}
