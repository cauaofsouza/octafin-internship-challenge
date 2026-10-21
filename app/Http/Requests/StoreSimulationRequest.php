<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'employer_cnpj' => preg_replace('/\D/', '', (string) $this->input('employer_cnpj')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) $this->input('full_name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'cpf' => ['required', 'string', 'cpf'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'gross_salary' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'employer_cnpj' => ['required', 'string', 'cnpj'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'requested_amount' => ['required', 'numeric', 'min:100', 'max:1000000'],
            'requested_term_months' => ['required', 'integer', 'min:1', 'max:96'],
        ];
    }
}
