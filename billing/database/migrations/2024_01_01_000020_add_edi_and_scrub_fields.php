<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->json('icd10_codes')->nullable()->after('diagnosis_pointers');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->longText('edi_837_content')->nullable()->after('place_of_service');
            $table->timestamp('edi_generated_at')->nullable()->after('edi_837_content');
            $table->json('scrub_errors')->nullable()->after('edi_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropColumn('icd10_codes');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['edi_837_content', 'edi_generated_at', 'scrub_errors']);
        });
    }
};
