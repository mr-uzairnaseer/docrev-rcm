<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('ncpdp_id', 20)->nullable()->index();
            $table->string('npi', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('surescripts_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('spi', 50)->nullable();
            $table->string('dea_number', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('enrolled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider_id']);
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pharmacy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('drug_name');
            $table->string('ndc', 20)->nullable();
            $table->string('strength', 50)->nullable();
            $table->string('dosage_form', 50)->nullable();
            $table->unsignedSmallInteger('quantity')->default(30);
            $table->unsignedSmallInteger('days_supply')->default(30);
            $table->unsignedTinyInteger('refills')->default(0);
            $table->text('sig');
            $table->string('status', 20)->default('draft');
            $table->string('surescripts_message_id')->nullable();
            $table->text('transmission_payload')->nullable();
            $table->text('transmission_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('surescripts_enrollments');
        Schema::dropIfExists('pharmacies');
    }
};
