<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankOffer extends Model
{
    protected $fillable = [
        'simulation_id',
        'bank_name',
        'status',
        'offered_amount',
        'interest_rate_monthly',
        'cet_monthly',
        'installment_value',
        'total_payable',
        'rejection_reason',
        'request_payload',
        'response_payload',
        'response_time_ms',
    ];

    protected $casts = [
        'status' => OfferStatus::class,
        'offered_amount' => 'decimal:2',
        'interest_rate_monthly' => 'decimal:4',
        'cet_monthly' => 'decimal:4',
        'installment_value' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'response_time_ms' => 'integer',
    ];

    protected $hidden = ['request_payload', 'response_payload'];

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }
}
