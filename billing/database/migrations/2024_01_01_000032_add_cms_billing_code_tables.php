<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_icd10_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('description');
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('description');
        });

        Schema::create('cms_modifiers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('description');
            $table->string('level', 20)->default('hcpcs');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_claim_adjustment_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->string('group_code', 5)->nullable();
            $table->string('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'group_code']);
            $table->index('group_code');
        });

        Schema::create('cms_remittance_remark_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_type_of_bill_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();
            $table->string('description');
            $table->string('facility_type', 80)->nullable();
            $table->string('care_type', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_revenue_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();
            $table->string('description');
            $table->string('category', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_revenue_codes');
        Schema::dropIfExists('cms_type_of_bill_codes');
        Schema::dropIfExists('cms_remittance_remark_codes');
        Schema::dropIfExists('cms_claim_adjustment_codes');
        Schema::dropIfExists('cms_modifiers');
        Schema::dropIfExists('cms_icd10_codes');
    }
};
