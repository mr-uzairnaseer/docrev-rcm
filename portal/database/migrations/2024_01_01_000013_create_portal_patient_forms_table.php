<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_patient_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_account_id')->constrained()->cascadeOnDelete();
            $table->uuid('external_form_uuid')->unique()->index();
            $table->string('form_name');
            $table->string('status', 20)->default('pending');
            $table->text('form_content')->nullable();
            $table->string('signature_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_patient_forms');
    }
};
