<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('interface_type', 20)->default('hl7_v2');
            $table->string('vendor_code', 50)->nullable();
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('sending_application', 50)->nullable();
            $table->string('receiving_application', 50)->nullable();
            $table->string('sending_facility', 50)->nullable();
            $table->string('receiving_facility', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lab_vendor_id')->constrained()->restrictOnDelete();
            $table->string('test_code', 50);
            $table->string('test_name');
            $table->string('priority', 10)->default('routine');
            $table->string('status', 20)->default('ordered');
            $table->longText('hl7_orm_message')->nullable();
            $table->string('external_order_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->cascadeOnDelete();
            $table->string('result_code', 50)->nullable();
            $table->string('result_name');
            $table->string('value')->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('reference_range', 100)->nullable();
            $table->string('abnormal_flag', 5)->nullable();
            $table->string('status', 20)->default('final');
            $table->longText('hl7_oru_message')->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_vendors');
    }
};
