<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\SimulationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        'status' => SimulationStatus::class,
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(BankOffer::class);
    }

    /**
     * Retorna as ofertas ordenadas para exibição ao cliente:
     * aprovadas primeiro, da menor para a maior CET; depois reprovadas;
     * por último os bancos que falharam.
     *
     * @return Collection<int, BankOffer>
     */
    public function rankedOffers(): Collection
    {
        return $this->offers->sortBy([
            fn (BankOffer $a, BankOffer $b) => $a->status->rank() <=> $b->status->rank(),
            fn (BankOffer $a, BankOffer $b) => ($a->cet_monthly ?? PHP_INT_MAX) <=> ($b->cet_monthly ?? PHP_INT_MAX),
        ])->values();
    }

    /**
     * Gera o identificador público (UUID) ao criar a simulação.
     */
    protected static function booted(): void
    {
        static::creating(function (Simulation $simulation) {
            $simulation->public_id ??= (string) Str::uuid();
        });
    }

    /**
     * Usa o UUID (e não o id sequencial) nas rotas, para evitar enumeração de simulações.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
