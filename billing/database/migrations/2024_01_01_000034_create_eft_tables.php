<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eft_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('legal_name')->nullable();
            $table->string('dba')->nullable();
            $table->string('npi', 10)->nullable();
            $table->string('tax_id', 20)->nullable();
            $table->string('ptan', 20)->nullable();
            $table->json('address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('bank_routing')->nullable(); // encrypted
            $table->text('bank_account')->nullable(); // encrypted
            $table->string('bank_account_type', 20)->nullable();
            $table->string('authorized_signer')->nullable();
            $table->string('medicare_eft_status')->default('not_started'); // not_started, processing, enrolled
            $table->json('commercial_payer_status')->nullable();
            $table->json('era_enrollment_status')->nullable();
            $table->string('vcc_policy')->default('accept'); // accept, restrict
            $table->json('onboarding_checklist')->nullable();
            $table->timestamps();
        });

        Schema::create('eft_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trace_number')->index();
            $table->decimal('amount', 10, 2);
            $table->date('deposit_date');
            $table->string('matched_status')->default('unmatched'); // unmatched, matched, exception
            $table->foreignId('era_remittance_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('auto_posting_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key')->index();
            $table->json('rule_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_posting_rules');
        Schema::dropIfExists('eft_deposits');
        Schema::dropIfExists('eft_enrollments');
    }
};
