<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('icd10_code', 12);
            $table->string('description');
            $table->date('onset_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('rank')->default(1);
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('payer_name');
            $table->string('member_id', 64)->nullable();
            $table->string('group_number', 64)->nullable();
            $table->string('plan_type', 20)->default('primary');
            $table->string('coverage_status', 32)->default('unknown');
            $table->decimal('copay_amount', 8, 2)->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_care_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role');
            $table->string('specialty')->nullable();
            $table->string('contact')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('patient_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('temperature_f', 4, 1)->nullable();
            $table->decimal('weight_lb', 6, 1)->nullable();
            $table->decimal('height_in', 5, 1)->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'recorded_at']);
        });

        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type', 64)->default('clinical');
            $table->string('file_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patient_allergy_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('allergen');
            $table->string('reaction')->nullable();
            $table->string('severity', 20)->default('moderate');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_allergy_items');
        Schema::dropIfExists('patient_documents');
        Schema::dropIfExists('patient_vitals');
        Schema::dropIfExists('patient_care_team_members');
        Schema::dropIfExists('patient_insurances');
        Schema::dropIfExists('patient_problems');
    }
};
