<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representação pública de uma oferta bancária.
 * Não expõe os payloads de auditoria (request_payload e response_payload).
 *
 * @mixin \App\Models\BankOffer
 */
class BankOfferResource extends JsonResource
{
    /**
     * @param Request $request Requisição atual.
     */
    public function toArray(Request $request): array
    {
        return [
            'bank' => $this->bank_name,
            'status' => $this->status->value,
            'offered_amount' => $this->toFloat($this->offered_amount),
            'interest_rate_monthly' => $this->toFloat($this->interest_rate_monthly),
            'cet_monthly' => $this->toFloat($this->cet_monthly),
            'installment_value' => $this->toFloat($this->installment_value),
            'total_payable' => $this->toFloat($this->total_payable),
            'rejection_reason' => $this->rejection_reason,
            'response_time_ms' => $this->response_time_ms,
        ];
    }

    /**
     * Converte colunas decimais (strings) em número, preservando nulo.
     *
     * @param string|null $value Valor vindo do banco.
     */
    private function toFloat(?string $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}