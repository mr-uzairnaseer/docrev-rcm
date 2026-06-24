<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained()->cascadeOnDelete();
            $table->string('trace_number')->index();
            $table->date('service_date');
            $table->string('member_id')->nullable();
            $table->text('edi_270_content')->nullable();
            $table->text('edi_271_content')->nullable();
            $table->string('coverage_status', 20);
            $table->string('plan_name')->nullable();
            $table->decimal('copay_amount', 8, 2)->nullable();
            $table->decimal('deductible_amount', 10, 2)->nullable();
            $table->decimal('coinsurance_percent', 5, 2)->nullable();
            $table->string('response_message')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'payer_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_inquiries');
    }
};
