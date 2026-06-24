<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_medicare_advantage_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 20)->unique();
            $table->string('organization_type')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('organization_name');
            $table->string('marketing_name')->nullable();
            $table->string('parent_organization')->nullable();
            $table->date('contract_effective_date')->nullable();
            $table->boolean('offers_part_d')->default(false);
            $table->unsignedInteger('ma_enrollment')->nullable();
            $table->unsignedInteger('part_d_enrollment')->nullable();
            $table->unsignedInteger('total_enrollment')->nullable();
            $table->string('ownership', 20)->default('private');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plan_type', 'organization_type']);
            $table->index('parent_organization');
        });

        Schema::create('cms_hcpcs_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('short_description');
            $table->text('long_description')->nullable();
            $table->string('category', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
        });

        Schema::create('cms_qhp_issuers', function (Blueprint $table) {
            $table->id();
            $table->string('issuer_id', 20);
            $table->string('issuer_name');
            $table->foreignId('cms_state_id')->nullable()->constrained('cms_states')->nullOnDelete();
            $table->string('market_type', 30)->nullable();
            $table->string('ownership', 20)->default('private');
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['issuer_id', 'cms_state_id']);
            $table->index(['cms_state_id', 'market_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_qhp_issuers');
        Schema::dropIfExists('cms_hcpcs_codes');
        Schema::dropIfExists('cms_medicare_advantage_contracts');
    }
};
