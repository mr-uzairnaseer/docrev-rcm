<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->string('clearinghouse', 50)->default('stub');
            $table->string('status', 30)->default('pending');
            $table->string('external_reference')->nullable();
            $table->longText('edi_837_content')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['claim_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_submissions');
    }
};
