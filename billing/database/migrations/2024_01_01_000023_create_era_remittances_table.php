<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('era_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('trace_number')->index();
            $table->text('edi_835_content');
            $table->decimal('total_payment_amount', 10, 2)->default(0);
            $table->unsignedInteger('claim_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->string('status', 20)->default('posted');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('era_remittances');
    }
};
