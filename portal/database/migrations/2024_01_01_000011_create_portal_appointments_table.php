<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_account_id')->constrained()->cascadeOnDelete();
            $table->string('external_appointment_id')->nullable()->index();
            $table->string('provider_name');
            $table->string('location_name')->nullable();
            $table->dateTime('appointment_at');
            $table->string('appointment_type', 50)->default('office_visit');
            $table->string('status', 20)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_account_id', 'appointment_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_appointments');
    }
};
