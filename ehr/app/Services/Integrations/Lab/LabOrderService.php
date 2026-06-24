<?php

namespace App\Services\Integrations\Lab;

use App\Models\LabOrder;
use App\Models\LabResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LabOrderService
{
    public function __construct(
        private LabInterfaceProvider $provider,
        private Hl7OrmBuilder $ormBuilder,
        private Hl7OruParser $oruParser
    ) {}

    public function send(LabOrder $order): LabOrder
    {
        if (! in_array($order->status, [LabOrder::STATUS_ORDERED], true)) {
            throw new \RuntimeException('Only ordered lab orders can be sent.');
        }

        $hl7 = $this->ormBuilder->build($order);
        $result = $this->provider->sendOrder($order, $hl7);

        $order->update([
            'status' => LabOrder::STATUS_SENT,
            'hl7_orm_message' => $hl7,
            'external_order_id' => $result['external_order_id'] ?? null,
            'sent_at' => now(),
        ]);

        return $order->fresh()->load(['patient', 'provider', 'labVendor', 'results']);
    }

    public function importResults(LabOrder $order, string $hl7Oru): LabOrder
    {
        $parsed = $this->oruParser->parse($hl7Oru);

        foreach ($parsed as $row) {
            LabResult::create([
                'lab_order_id' => $order->id,
                'result_code' => $row['result_code'],
                'result_name' => $row['result_name'],
                'value' => $row['value'],
                'unit' => $row['unit'],
                'reference_range' => $row['reference_range'],
                'abnormal_flag' => $row['abnormal_flag'],
                'status' => $row['status'],
                'hl7_oru_message' => $hl7Oru,
                'observed_at' => now(),
            ]);
        }

        $order->update(['status' => LabOrder::STATUS_RESULTED]);

        return $order->fresh()->load('results');
    }

    public function simulateResults(LabOrder $order): LabOrder
    {
        $oru = implode("\r", [
            'MSH|^~\\&|LAB|QUEST|DOCREV|DEMO|'.now()->format('YmdHis').'||ORU^R01|ORU'.$order->id.'|P|2.5.1',
            'PID|1||'.$order->patient_id.'||DOE^JANE||19900515|F',
            'ORC|RE|'.$order->uuid,
            'OBR|1|'.$order->uuid.'||'.$order->test_code.'^'.$order->test_name,
            'OBX|1|NM|'.$order->test_code.'^'.$order->test_name.'||5.4|mg/dL|3.5-5.5|N|||F',
        ])."\r";

        return $this->importResults($order, $oru);
    }
}
