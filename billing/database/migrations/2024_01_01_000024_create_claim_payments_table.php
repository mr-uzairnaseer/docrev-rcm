<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('era_remittance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->nullable()->constrained()->nullOnDelete();
            $table->string('claim_number');
            $table->string('era_status', 10)->nullable();
            $table->decimal('total_charge', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('patient_responsibility', 10, 2)->default(0);
            $table->string('payment_status', 20);
            $table->boolean('portal_synced')->default(false);
            $table->timestamps();

            $table->index(['claim_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_payments');
    }
};
