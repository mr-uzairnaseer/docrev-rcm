<?php

namespace App\Services\Integrations\Lab;

use App\Models\LabOrder;
use Carbon\Carbon;

class Hl7OrmBuilder
{
    public function build(LabOrder $order): string
    {
        $order->load(['patient', 'provider', 'labVendor', 'organization']);
        $vendor = $order->labVendor;
        $now = Carbon::now();
        $msgId = 'ORM'.$order->id.str_pad((string) $now->timestamp, 10, '0');

        $segments = [
            $this->seg('MSH', '|', '^~\\&',
                $vendor->sending_application ?? config('lab.hl7.sending_application'),
                $vendor->sending_facility ?? config('lab.hl7.sending_facility'),
                $vendor->receiving_application ?? 'LAB',
                $vendor->receiving_facility ?? $vendor->name,
                $now->format('YmdHis'),
                '', 'ORM^O01^ORM_O01', $msgId, 'P', config('lab.hl7.version')),
            $this->seg('PID', '1', '', $order->patient->mrn ?? (string) $order->patient_id,
                '', $order->patient->last_name, $order->patient->first_name, '',
                $order->patient->date_of_birth->format('Ymd'), $this->mapGender($order->patient->gender)),
            $this->seg('ORC', 'NW', $order->uuid, '', '', 'SC', '', '', $now->format('YmdHis'),
                '', '', $order->provider->npi ?? '', '^'.$order->provider->last_name.'^'.$order->provider->first_name),
            $this->seg('OBR', '1', $order->uuid, '', $order->test_code.'^'.$order->test_name,
                '', '', '', $now->format('YmdHis'), '', '', '', '', '', '', '', '', '', $order->priority),
        ];

        return implode("\r", $segments)."\r";
    }

    private function seg(string ...$fields): string
    {
        return implode('|', $fields);
    }

    private function mapGender(?string $gender): string
    {
        return match (strtolower((string) $gender)) {
            'male', 'm' => 'M',
            'female', 'f' => 'F',
            default => 'U',
        };
    }
}
