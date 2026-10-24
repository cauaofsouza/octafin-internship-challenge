<?php

namespace App\Http\Resources;

use App\Enums\OfferStatus;
use App\Models\BankOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Representação pública de uma simulação: dados mascarados, ofertas ordenadas
 * por menor CET e um resumo do resultado.
 *
 * @mixin \App\Models\Simulation
 */
class SimulationResource extends JsonResource
{
    /**
     * @param Request $request Requisição atual.
     */
    public function toArray(Request $request): array
    {
        $offers = $this->rankedOffers();

        $count = fn (array $statuses): int => $offers
            ->filter(fn (BankOffer $offer) => in_array($offer->status, $statuses, true))
            ->count();

        $best = $offers->first(fn (BankOffer $offer) => $offer->status === OfferStatus::Approved);

        return [
            'id' => $this->public_id,
            'status' => $this->status->value,
            'customer' => [
                'full_name' => $this->full_name,
                'cpf' => $this->cpf_masked,
                'email' => $this->maskEmail($this->email),
            ],
            'request' => [
                'gross_salary' => (float) $this->gross_salary,
                'employer_cnpj' => $this->employer_cnpj,
                'employment_type' => $this->employment_type->value,
                'requested_amount' => (float) $this->requested_amount,
                'requested_term_months' => $this->requested_term_months,
            ],
            'summary' => [
                'approved' => $count([OfferStatus::Approved]),
                'rejected' => $count([OfferStatus::Rejected]),
                // Bancos que não responderam (erro ou timeout): o cliente pode tentar de novo.
                'unavailable' => $count([OfferStatus::Error, OfferStatus::Timeout]),
            ],
            'best_offer' => $best ? new BankOfferResource($best) : null,
            'offers' => BankOfferResource::collection($offers),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    /**
     * Mascara o e-mail mantendo a primeira letra (ex.: m****@teste.com).
     *
     * @param string $email E-mail completo.
     */
    private function maskEmail(string $email): string
    {
        $at = strpos($email, '@');

        return $at === false ? '***' : Str::mask($email, '*', 1, max($at - 1, 0));
    }
}