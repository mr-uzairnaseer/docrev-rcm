<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->string('cpt_code', 10)->nullable();
            $table->string('hcpcs_code', 10)->nullable();
            $table->string('modifier_1', 5)->nullable();
            $table->string('modifier_2', 5)->nullable();
            $table->unsignedSmallInteger('units')->default(1);
            $table->decimal('charge_amount', 10, 2);
            $table->json('diagnosis_pointers')->nullable();
            $table->boolean('synced_to_billing')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->timestamp('billing_synced_at')->nullable()->after('signed_at');
            $table->string('billing_sync_status', 20)->nullable()->after('billing_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['billing_synced_at', 'billing_sync_status']);
        });

        Schema::dropIfExists('encounter_charges');
    }
};
