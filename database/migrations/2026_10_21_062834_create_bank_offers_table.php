<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('simulations')->cascadeOnDelete();
            $table->string('bank_name', 50);
            $table->string('status', 20);              // approved, rejected, error, timeout
            $table->decimal('offered_amount', 12, 2)->nullable();
            $table->decimal('interest_rate_monthly', 6, 4)->nullable(); // percent, e.g. 1.8500
            $table->decimal('cet_monthly', 6, 4)->nullable();           // percent
            $table->decimal('installment_value', 12, 2)->nullable();
            $table->decimal('total_payable', 12, 2)->nullable();        // installment * term
            $table->string('rejection_reason')->nullable();
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            $table->unique(['simulation_id', 'bank_name']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('bank_offers');
    }
};