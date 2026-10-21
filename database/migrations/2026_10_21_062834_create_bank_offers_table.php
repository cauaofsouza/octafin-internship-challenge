<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('simulations')->onDelete('cascade');
            $table->string('bank_name'); // Ex: Banco Alfa, Banco Beta
            $table->string('status'); // approved, rejected
            $table->decimal('offered_amount', 12, 2)->nullable();
            $table->decimal('interest_rate_monthly', 5, 4)->nullable(); // Ex: 0.0185 (1.85%)
            $table->decimal('installment_value', 12, 2)->nullable();
            $table->decimal('total_payable', 12, 2)->nullable(); // CET simulado
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('bank_offers');
    }
};