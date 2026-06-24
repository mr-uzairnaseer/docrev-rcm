<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('original_claim_id')
                ->nullable()
                ->after('payer_id')
                ->constrained('claims')
                ->nullOnDelete();
            $table->string('frequency_code', 2)->default('1')->after('claim_type');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('original_claim_id');
            $table->dropColumn('frequency_code');
        });
    }
};
