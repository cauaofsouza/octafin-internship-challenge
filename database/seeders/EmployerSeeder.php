<?php


namespace Database\Seeders;

use App\Models\Employer;
use Illuminate\Database\Seeder;

/**
 * Empregadores conveniados fictícios para testes.
 */
class EmployerSeeder extends Seeder
{
    /**
     * Cria (ou atualiza) os empregadores de exemplo.
     */
    public function run(): void
    {
        $employers = [
            ['cnpj' => '11222333000181', 'corporate_name' => 'Empresa Exemplo Ltda', 'is_active' => true],
            ['cnpj' => '11444777000161', 'corporate_name' => 'Prefeitura Exemplo', 'is_active' => true],
        ];

        foreach ($employers as $employer) {
            Employer::updateOrCreate(['cnpj' => $employer['cnpj']], $employer);
        }
    }
}