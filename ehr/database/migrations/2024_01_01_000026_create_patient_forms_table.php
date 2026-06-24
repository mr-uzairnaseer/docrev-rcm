<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('form_name');
            $table->string('status', 20)->default('pending');
            $table->text('form_content')->nullable();
            $table->string('signature_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_forms');
    }
};
