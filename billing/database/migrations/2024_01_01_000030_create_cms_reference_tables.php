<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_regions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cms_states', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->foreignId('cms_region_id')->constrained('cms_regions')->cascadeOnDelete();
            $table->string('jurisdiction_type', 20)->default('state');
            $table->string('fips_code', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_macs', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 20)->unique();
            $table->string('name');
            $table->string('mac_type', 20);
            $table->string('jurisdiction_code', 10)->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 30)->nullable();
            $table->json('address')->nullable();
            $table->boolean('processes_hhh')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mac_type', 'jurisdiction_code']);
        });

        Schema::create('cms_mac_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cms_mac_id')->constrained('cms_macs')->cascadeOnDelete();
            $table->foreignId('cms_state_id')->constrained('cms_states')->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['cms_mac_id', 'cms_state_id']);
            $table->index('cms_state_id');
        });

        Schema::create('cms_reference_payers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('program', 30);
            $table->string('ownership', 20);
            $table->foreignId('cms_state_id')->nullable()->constrained('cms_states')->nullOnDelete();
            $table->foreignId('cms_mac_id')->nullable()->constrained('cms_macs')->nullOnDelete();
            $table->string('electronic_payer_id', 40)->nullable();
            $table->string('cms_plan_id', 40)->nullable();
            $table->string('plan_type', 40)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->json('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['program', 'ownership']);
            $table->index(['cms_state_id', 'program']);
            $table->index('electronic_payer_id');
        });

        Schema::create('cms_place_of_service_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->text('definition')->nullable();
            $table->date('effective_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_taxonomy_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('grouping')->nullable();
            $table->string('classification')->nullable();
            $table->string('specialization')->nullable();
            $table->text('definition')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('payers', function (Blueprint $table) {
            $table->foreignId('cms_reference_payer_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('cms_reference_payers')
                ->nullOnDelete();
            $table->foreignId('cms_state_id')
                ->nullable()
                ->after('cms_reference_payer_id')
                ->constrained('cms_states')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cms_state_id');
            $table->dropConstrainedForeignId('cms_reference_payer_id');
        });

        Schema::dropIfExists('cms_taxonomy_codes');
        Schema::dropIfExists('cms_place_of_service_codes');
        Schema::dropIfExists('cms_reference_payers');
        Schema::dropIfExists('cms_mac_states');
        Schema::dropIfExists('cms_macs');
        Schema::dropIfExists('cms_states');
        Schema::dropIfExists('cms_regions');
    }
};
