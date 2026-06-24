<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('encounter_external_id')->nullable()->index();
            $table->date('service_date');
            $table->string('cpt_code', 10)->nullable();
            $table->string('hcpcs_code', 10)->nullable();
            $table->string('modifier_1', 5)->nullable();
            $table->string('modifier_2', 5)->nullable();
            $table->unsignedSmallInteger('units')->default(1);
            $table->decimal('charge_amount', 10, 2);
            $table->json('diagnosis_pointers')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'service_date']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
