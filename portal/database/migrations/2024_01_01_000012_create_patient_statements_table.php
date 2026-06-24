<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_account_id')->constrained()->cascadeOnDelete();
            $table->uuid('external_claim_uuid')->nullable()->index();
            $table->date('statement_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2);
            $table->string('status', 20)->default('open');
            $table->json('line_items')->nullable();
            $table->timestamps();

            $table->index(['patient_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_statements');
    }
};
