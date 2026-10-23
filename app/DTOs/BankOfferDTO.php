<?php

namespace App\DTOs;

use App\Enums\OfferStatus;

final readonly class BankOfferDTO
{
    public function __construct(
        public string $bankName,
        public OfferStatus $status,
        public ?float $offeredAmount = null,
        public ?float $interestRateMonthly = null,
        public ?float $cetMonthly = null,
        public ?float $installmentValue = null,
        public ?float $totalPayable = null,
        public ?string $rejectionReason = null,
        public ?array $rawResponse = null,
        public ?int $responseTimeMs = null,
    ) {}

    /**
     * Cria uma oferta sem proposta: reprovação, erro ou timeout.
     *
     * @param string      $bankName        Nome de exibição do banco.
     * @param OfferStatus $status          Rejected, Error ou Timeout.
     * @param string      $reason          Motivo legível (sem dados sensíveis).
     * @param int|null    $responseTimeMs  Tempo de resposta, se conhecido.
     * @param array|null  $rawResponse     Corpo bruto devolvido pelo banco, para auditoria.
     */
    public static function failed(
        string $bankName,
        OfferStatus $status,
        string $reason,
        ?int $responseTimeMs = null,
        ?array $rawResponse = null,
    ): self {
        return new self(
            bankName: $bankName,
            status: $status,
            rejectionReason: $reason,
            rawResponse: $rawResponse,
            responseTimeMs: $responseTimeMs,
        );
    }

    public function isApproved(): bool
    {
        return $this->status === OfferStatus::Approved;
    }
}
