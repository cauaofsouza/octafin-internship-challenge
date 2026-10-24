<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida e normaliza os dados de entrada da simulação.
 */
class StoreSimulationRequest extends FormRequest
{
    /**
     * O endpoint é público no MVP.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza os dados antes da validação: remove máscaras de CPF e CNPJ,
     * padroniza e-mail e nome e lê a chave de idempotência do cabeçalho.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'employer_cnpj' => preg_replace('/\D/', '', (string) $this->input('employer_cnpj')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) $this->input('full_name')),
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['nullable', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'cpf' => ['required', 'string', 'cpf'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'gross_salary' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'employer_cnpj' => [
                'required', 'string', 'cnpj',
                Rule::exists('employers', 'cnpj')->where('is_active', true),
            ],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'requested_amount' => ['required', 'numeric', 'min:100', 'max:1000000'],
            'requested_term_months' => ['required', 'integer', 'min:1', 'max:96'],
        ];
    }

    /**
     * Mensagens de erro personalizadas.
     */
    public function messages(): array
    {
        return [
            'employer_cnpj.exists' => 'O empregador não possui convênio ativo.',
            'idempotency_key.regex' => 'O cabeçalho Idempotency-Key aceita apenas letras, números, "-" e "_".',
        ];
    }
}