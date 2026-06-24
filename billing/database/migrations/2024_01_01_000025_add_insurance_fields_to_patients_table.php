<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('insurance_member_id')->nullable()->after('mrn');
            $table->string('insurance_group_number')->nullable()->after('insurance_member_id');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['insurance_member_id', 'insurance_group_number']);
        });
    }
};
