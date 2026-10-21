<?php

namespace App\DTOs;

use App\Enums\EmploymentType;

final readonly class SimulationRequestDTO
{
    public function __construct(
        public string $fullName,
        public string $cpf,
        public string $email,
        public float $grossSalary,
        public string $employerCnpj,
        public EmploymentType $employmentType,
        public float $requestedAmount,
        public int $termMonths,
        public float $maxInstallment, // consignable margin, calculated by SimulationService
    ) {}

    /** Payload safe to persist: no raw CPF or email. */
    public function toAuditPayload(): array
    {
        return [
            'cpf' => '***.' . substr($this->cpf, 3, 3) . '.' . substr($this->cpf, 6, 3) . '-**',
            'gross_salary' => $this->grossSalary,
            'employer_cnpj' => $this->employerCnpj,
            'employment_type' => $this->employmentType->value,
            'requested_amount' => $this->requestedAmount,
            'term_months' => $this->termMonths,
            'max_installment' => $this->maxInstallment,
        ];
    }
}
