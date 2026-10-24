<?php


namespace App\Repositories\Eloquent;

use App\DTOs\BankOfferDTO;
use App\Models\Simulation;
use App\Repositories\Contracts\BankOfferRepositoryInterface;

/**
 * Implementação Eloquent do repositório de ofertas bancárias.
 */
class BankOfferRepository implements BankOfferRepositoryInterface
{
    /** {@inheritDoc} */
    public function storeMany(Simulation $simulation, array $offers, array $requestPayload): void
    {
        $simulation->offers()->createMany(array_map(
            fn(BankOfferDTO $offer) => [
                'bank_name' => $offer->bankName,
                'status' => $offer->status,
                'offered_amount' => $offer->offeredAmount,
                'interest_rate_monthly' => $offer->interestRateMonthly,
                'cet_monthly' => $offer->cetMonthly,
                'installment_value' => $offer->installmentValue,
                'total_payable' => $offer->totalPayable,
                'rejection_reason' => $offer->rejectionReason,
                'request_payload' => $requestPayload,
                'response_payload' => $offer->rawResponse,
                'response_time_ms' => $offer->responseTimeMs,
            ],
            $offers,
        ));
    }
}