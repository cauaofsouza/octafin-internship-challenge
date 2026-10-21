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

    public static function failed(
        string $bankName,
        OfferStatus $status,
        string $reason,
        ?int $responseTimeMs = null,
    ): self {
        return new self(
            bankName: $bankName,
            status: $status,
            rejectionReason: $reason,
            responseTimeMs: $responseTimeMs,
        );
    }

    public function isApproved(): bool
    {
        return $this->status === OfferStatus::Approved;
    }
}
