<?php


namespace App\Integrations\Banks;

use App\DTOs\BankOfferDTO;
use App\DTOs\SimulationRequestDTO;
use App\Enums\OfferStatus;
use UnexpectedValueException;

/**
 * Banco Beta: payload em camelCase, taxa como fração decimal (0.0189 = 1,89%),
 * pode aprovar com valor menor que o solicitado.
 */
final class BetaBankClient extends AbstractBankClient
{
    /** {@inheritDoc} */
    protected function buildPayload(SimulationRequestDTO $dto): array
    {
        return [
            'taxId' => $dto->cpf,
            'employerTaxId' => $dto->employerCnpj,
            'monthlyIncome' => $dto->grossSalary,
            'requestedValue' => $dto->requestedAmount,
            'installments' => $dto->termMonths,
            'maxMonthlyPayment' => $dto->maxInstallment,
        ];
    }

    /** {@inheritDoc} */
    protected function parseBody(array $body, SimulationRequestDTO $dto, int $elapsedMs): BankOfferDTO
    {
        $status = $this->required($body, 'status');

        if ($status === 'REFUSED') {
            return BankOfferDTO::failed(
                $this->name(),
                OfferStatus::Rejected,
                (string)($body['error']['message'] ?? 'Reprovado pelo banco.'),
                $elapsedMs,
                $body,
            );
        }

        if ($status !== 'OK') {
            throw new UnexpectedValueException("Status desconhecido: {$status}");
        }

        $installment = (float)$this->required($body, 'data.monthlyPayment');

        return new BankOfferDTO(
            bankName: $this->name(),
            status: OfferStatus::Approved,
            offeredAmount: (float)$this->required($body, 'data.limit'),
            // Normaliza fração decimal para percentual (0.0189 -> 1.89).
            interestRateMonthly: round((float)$this->required($body, 'data.rate') * 100, 4),
            cetMonthly: round((float)$this->required($body, 'data.effectiveCost') * 100, 4),
            installmentValue: $installment,
            totalPayable: round($installment * $dto->termMonths, 2),
            rawResponse: $body,
            responseTimeMs: $elapsedMs,
        );
    }
}