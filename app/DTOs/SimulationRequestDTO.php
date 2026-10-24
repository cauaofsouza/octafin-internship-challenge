<?php

namespace App\DTOs;

use App\Enums\EmploymentType;

/**
 * Dados da simulação já validados, no formato enviado aos clientes bancários.
 * Imutável: criado uma vez pelo SimulationService.
 */
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
        public float $maxInstallment, // margem consignável calculada pelo SimulationService
    ) {}

    /**
     * Cria o DTO a partir do array retornado por FormRequest::validated().
     *
     * @param array $data           Dados validados da requisição.
     * @param float $maxInstallment Parcela máxima permitida pela margem consignável.
     */
    public static function fromValidated(array $data, float $maxInstallment): self
    {
        return new self(
            fullName: $data['full_name'],
            cpf: $data['cpf'],
            email: $data['email'],
            grossSalary: (float) $data['gross_salary'],
            employerCnpj: $data['employer_cnpj'],
            employmentType: EmploymentType::from($data['employment_type']),
            requestedAmount: (float) $data['requested_amount'],
            termMonths: (int) $data['requested_term_months'],
            maxInstallment: $maxInstallment,
        );
    }

    /**
     * CPF mascarado para exibição e auditoria (ex.: ***.456.789-**).
     */
    public function maskedCpf(): string
    {
        return '***.' . substr($this->cpf, 3, 3) . '.' . substr($this->cpf, 6, 3) . '-**';
    }

    /**
     * Hash (HMAC-SHA256) do CPF, para buscas e rate limiting sem guardar o CPF.
     */
    public function cpfHash(): string
    {
        return hash_hmac('sha256', $this->cpf, config('app.key'));
    }

    /**
     * Payload seguro para persistir: sem CPF completo nem e-mail.
     */
    public function toAuditPayload(): array
    {
        return [
            'cpf' => $this->maskedCpf(),
            'gross_salary' => $this->grossSalary,
            'employer_cnpj' => $this->employerCnpj,
            'employment_type' => $this->employmentType->value,
            'requested_amount' => $this->requestedAmount,
            'term_months' => $this->termMonths,
            'max_installment' => $this->maxInstallment,
        ];
    }
}