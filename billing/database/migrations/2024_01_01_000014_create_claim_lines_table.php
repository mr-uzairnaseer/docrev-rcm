<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->string('cpt_code', 10)->nullable();
            $table->string('modifier_1', 5)->nullable();
            $table->string('modifier_2', 5)->nullable();
            $table->unsignedSmallInteger('units')->default(1);
            $table->decimal('charge_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('adjustment_amount', 10, 2)->default(0);
            $table->decimal('patient_responsibility', 10, 2)->default(0);
            $table->json('diagnosis_pointers')->nullable();
            $table->timestamps();

            $table->unique(['claim_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_lines');
    }
};
