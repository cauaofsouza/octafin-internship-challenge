<?php

namespace App\Models;

use App\Enums\EmploymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Simulation extends Model
{
    protected $fillable = [
        'idempotency_key',
        'full_name',
        'cpf_hash',
        'cpf_masked',
        'email',
        'gross_salary',
        'employer_cnpj',
        'employment_type',
        'requested_amount',
        'requested_term_months',
        'status',
    ];

    protected $hidden = ['cpf_hash', 'idempotency_key'];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'requested_term_months' => 'integer',
        'employment_type' => EmploymentType::class,
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(BankOffer::class);
    }
}
