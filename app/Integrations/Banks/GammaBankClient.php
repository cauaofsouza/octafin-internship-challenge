<?php


namespace App\Integrations\Banks;

use App\DTOs\BankOfferDTO;
use App\DTOs\SimulationRequestDTO;
use App\Enums\OfferStatus;

/**
 * Banco Gamma: payload aninhado, resposta dentro de "proposal".
 * Não atende pensionistas do INSS e é o banco mais lento e instável
 * (usado para demonstrar timeout e isolamento de falhas).
 */
final class GammaBankClient extends AbstractBankClient
{
    /** {@inheritDoc} */
    protected function buildPayload(SimulationRequestDTO $dto): array
    {
        return [
            'customer' => [
                'document' => $dto->cpf,
                'income' => $dto->grossSalary,
                'bond' => $dto->employmentType->value,
            ],
            'employer' => ['cnpj' => $dto->employerCnpj],
            'loan' => [
                'amount' => $dto->requestedAmount,
                'term' => $dto->termMonths,
            ],
            'constraints' => ['max_installment' => $dto->maxInstallment],
        ];
    }

    /** {@inheritDoc} */
    protected function parseBody(array $body, SimulationRequestDTO $dto, int $elapsedMs): BankOfferDTO
    {
        $approved = (bool)$this->required($body, 'proposal.approved');

        if (!$approved) {
            return BankOfferDTO::failed(
                $this->name(),
                OfferStatus::Rejected,
                (string)($body['proposal']['denial_reason'] ?? 'Reprovado pelo banco.'),
                $elapsedMs,
                $body,
            );
        }

        $installment = (float)$this->required($body, 'proposal.installment');

        return new BankOfferDTO(
            bankName: $this->name(),
            status: OfferStatus::Approved,
            offeredAmount: (float)$this->required($body, 'proposal.amount'),
            interestRateMonthly: (float)$this->required($body, 'proposal.interest.monthly_percent'),
            cetMonthly: (float)$this->required($body, 'proposal.cet.monthly_percent'),
            installmentValue: $installment,
            totalPayable: round($installment * $dto->termMonths, 2),
            rawResponse: $body,
            responseTimeMs: $elapsedMs,
        );
    }
}