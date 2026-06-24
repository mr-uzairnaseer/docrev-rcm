<?php

namespace App\Jobs;

use App\Models\Encounter;
use App\Services\BillingSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSignedEncounterToBilling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Encounter $encounter)
    {
    }

    public function handle(BillingSyncService $billingSyncService): void
    {
        try {
            $billingSyncService->syncSignedEncounter($this->encounter);
        } catch (\Throwable $e) {
            $this->encounter->update(['billing_sync_status' => 'failed']);

            Log::error('Encounter billing sync job failed', [
                'encounter_id' => $this->encounter->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
