<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('encounter_date');
            $table->string('encounter_type', 50)->default('office_visit');
            $table->string('status', 30)->default('scheduled');
            $table->text('chief_complaint')->nullable();
            $table->longText('clinical_notes')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'encounter_date']);
            $table->index(['patient_id', 'encounter_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
