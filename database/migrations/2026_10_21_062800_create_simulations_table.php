<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('cpf', 11);
            $table->string('email');
            $table->decimal('gross_salary', 12, 2);
            $table->string('employer_cnpj', 14);
            $table->decimal('requested_amount', 12, 2);
            $table->integer('requested_term_months');
            $table->string('status')->default('pending'); // pending, processed, failed
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('simulations');
    }
};