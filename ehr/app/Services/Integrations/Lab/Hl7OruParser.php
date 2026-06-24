<?php

namespace App\Services\Integrations\Lab;

class Hl7OruParser
{
    public function parse(string $message): array
    {
        $segments = preg_split('/[\r\n]+/', trim($message));
        $results = [];
        $orderId = null;

        foreach ($segments as $segment) {
            $fields = explode('|', $segment);
            $type = $fields[0] ?? '';

            if ($type === 'ORC' && isset($fields[2])) {
                $orderId = $fields[2];
            }

            if ($type === 'OBX') {
                $components = explode('^', $fields[3] ?? '');
                $results[] = [
                    'order_uuid' => $orderId,
                    'result_code' => $components[0] ?? null,
                    'result_name' => $components[1] ?? ($fields[3] ?? 'Result'),
                    'value' => $fields[5] ?? null,
                    'unit' => $fields[6] ?? null,
                    'reference_range' => $fields[7] ?? null,
                    'abnormal_flag' => $fields[8] ?? null,
                    'status' => $fields[11] ?? 'final',
                ];
            }
        }

        return $results;
    }
}
