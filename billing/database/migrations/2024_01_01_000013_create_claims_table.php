<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('payer_id')->constrained()->restrictOnDelete();
            $table->string('claim_number')->nullable()->index();
            $table->string('claim_type', 10)->default('837P');
            $table->date('service_date_from');
            $table->date('service_date_to');
            $table->decimal('total_charge_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->json('icd10_codes')->nullable();
            $table->string('rendering_provider_npi', 10)->nullable();
            $table->string('billing_provider_npi', 10)->nullable();
            $table->string('place_of_service', 5)->default('11');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['patient_id', 'service_date_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
