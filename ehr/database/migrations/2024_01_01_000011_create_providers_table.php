<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('npi', 10)->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('credentials')->nullable();
            $table->string('specialty')->nullable();
            $table->string('taxonomy_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'npi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
