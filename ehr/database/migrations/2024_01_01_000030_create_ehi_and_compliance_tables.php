<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ehi_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('export_type')->default('single'); // single, bulk
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->string('file_path')->nullable();
            $table->string('requested_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ehi_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requestor_name');
            $table->string('requestor_type')->default('patient'); // patient, provider, payer, third_party_app
            $table->string('access_method')->default('ehi_export'); // fhir_api, ehi_export, patient_portal
            $table->string('status')->default('pending'); // approved, pending, denied
            $table->string('exception_reason')->nullable(); // security, privacy, infeasibility, harm_prevention, none
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ehi_requests');
        Schema::dropIfExists('ehi_exports');
    }
};
