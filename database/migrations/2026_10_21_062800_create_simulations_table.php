<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('full_name');
            $table->string('cpf_hash', 64)->index();   // HMAC-SHA256, for lookups and rate limiting
            $table->string('cpf_masked', 14);          // e.g. ***.456.789-**
            $table->string('email');
            $table->decimal('gross_salary', 12, 2);
            $table->string('employer_cnpj', 14);
            $table->string('employment_type', 30);     // clt, public_servant, inss_pensioner
            $table->decimal('requested_amount', 12, 2);
            $table->unsignedSmallInteger('requested_term_months');
            $table->string('status', 20)->default('pending'); // pending, processed, failed
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('simulations');
    }
};