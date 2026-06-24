<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hie_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('network_type', 30)->default('fhir_r4');
            $table->string('fhir_base_url')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('scopes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('agreement_signed_at')->nullable();
            $table->text('agreement_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hie_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hie_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 10);
            $table->string('resource_type', 50);
            $table->string('fhir_resource_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('response_message')->nullable();
            $table->timestamp('exchanged_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hie_exchanges');
        Schema::dropIfExists('hie_connections');
    }
};
