<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_denials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason_code', 20)->nullable();
            $table->string('reason_description')->nullable();
            $table->decimal('denied_amount', 10, 2)->default(0);
            $table->string('status', 20)->default('open');
            $table->text('appeal_notes')->nullable();
            $table->timestamp('appealed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_denials');
    }
};
