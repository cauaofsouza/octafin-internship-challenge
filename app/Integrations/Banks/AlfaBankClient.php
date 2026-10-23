<?php


namespace App\Integrations\Banks;

use App\DTOs\BankOfferDTO;
use App\DTOs\SimulationRequestDTO;
use App\Enums\OfferStatus;
use UnexpectedValueException;

/**
 * Banco Alfa: payload plano em snake_case, taxa em percentual,
 * resultado textual "APPROVED" ou "DENIED".
 */
final class AlfaBankClient extends AbstractBankClient
{
    /** {@inheritDoc} */
    protected function buildPayload(SimulationRequestDTO $dto): array
    {
        return [
            'cpf' => $dto->cpf,
            'income' => $dto->grossSalary,
            'employer_cnpj' => $dto->employerCnpj,
            'amount' => $dto->requestedAmount,
            'months' => $dto->termMonths,
            'margin' => $dto->maxInstallment,
        ];
    }

    /** {@inheritDoc} */
    protected function parseBody(array $body, SimulationRequestDTO $dto, int $elapsedMs): BankOfferDTO
    {
        $result = $this->required($body, 'result');

        if ($result === 'DENIED') {
            return BankOfferDTO::failed(
                $this->name(),
                OfferStatus::Rejected,
                (string)($body['reason'] ?? 'Reprovado pelo banco.'),
                $elapsedMs,
                $body,
            );
        }

        if ($result !== 'APPROVED') {
            throw new UnexpectedValueException("Resultado desconhecido: {$result}");
        }

        $installment = (float)$this->required($body, 'installment');

        return new BankOfferDTO(
            bankName: $this->name(),
            status: OfferStatus::Approved,
            offeredAmount: (float)$this->required($body, 'approved_value'),
            interestRateMonthly: (float)$this->required($body, 'monthly_rate'),
            cetMonthly: (float)$this->required($body, 'cet_month'),
            installmentValue: $installment,
            totalPayable: round($installment * $dto->termMonths, 2),
            rawResponse: $body,
            responseTimeMs: $elapsedMs,
        );
    }
}