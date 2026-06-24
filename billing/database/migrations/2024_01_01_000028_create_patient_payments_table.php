<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('external_claim_uuid')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 20)->default('card');
            $table->string('reference_number')->nullable();
            $table->string('status', 20)->default('posted');
            $table->boolean('portal_synced')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_payments');
    }
};
