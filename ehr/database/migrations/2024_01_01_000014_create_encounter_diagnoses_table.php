<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->string('icd10_code', 10);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['encounter_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_diagnoses');
    }
};
