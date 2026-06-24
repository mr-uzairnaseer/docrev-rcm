<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->default(0)->after('total_charge_amount');
            $table->decimal('patient_responsibility', 10, 2)->default(0)->after('paid_amount');
            $table->timestamp('paid_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'patient_responsibility', 'paid_at']);
        });
    }
};
