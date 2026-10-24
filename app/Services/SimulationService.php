<?php


namespace App\Services;

use App\DTOs\BankOfferDTO;
use App\DTOs\SimulationRequestDTO;
use App\Enums\OfferStatus;
use App\Enums\SimulationStatus;
use App\Models\Simulation;
use App\Repositories\Contracts\BankOfferRepositoryInterface;
use App\Repositories\Contracts\SimulationRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Regra de negócio da simulação: calcula a margem consignável, garante
 * idempotência, consulta os bancos e persiste o resultado para auditoria.
 */
final class SimulationService
{
    public function __construct(
        private readonly SimulationRepositoryInterface $simulations,
        private readonly BankOfferRepositoryInterface  $offers,
        private readonly BankIntegrationService        $integration,
    )
    {
    }

    /**
     * Executa uma simulação completa.
     *
     * Se a mesma chave de idempotência já foi usada, devolve a simulação
     * existente sem consultar os bancos de novo.
     *
     * @param array $validated Dados validados (FormRequest::validated()).
     * @param string|null $idempotencyKey Chave enviada no cabeçalho Idempotency-Key.
     *
     * @return Simulation Simulação com as ofertas carregadas.
     */
    public function simulate(array $validated, ?string $idempotencyKey = null): Simulation
    {
        if ($idempotencyKey !== null && ($existing = $this->simulations->findByIdempotencyKey($idempotencyKey))) {
            return $existing;
        }

        $dto = SimulationRequestDTO::fromValidated(
            $validated,
            $this->calculateMargin((float)$validated['gross_salary']),
        );

        try {
            $simulation = $this->simulations->create([
                'idempotency_key' => $idempotencyKey,
                'full_name' => $dto->fullName,
                'cpf_hash' => $dto->cpfHash(),
                'cpf_masked' => $dto->maskedCpf(),
                'email' => $dto->email,
                'gross_salary' => $dto->grossSalary,
                'employer_cnpj' => $dto->employerCnpj,
                'employment_type' => $dto->employmentType,
                'requested_amount' => $dto->requestedAmount,
                'requested_term_months' => $dto->termMonths,
                'status' => SimulationStatus::Pending,
            ]);
        } catch (QueryException $e) {
            // 23505 = violação de unicidade no PostgreSQL: duplo clique concorrente
            // com a mesma chave. Devolve a simulação que venceu a corrida.
            if ($idempotencyKey !== null && $e->getCode() === '23505') {
                return $this->simulations->findByIdempotencyKey($idempotencyKey) ?? throw $e;
            }

            throw $e;
        }

        $offers = $this->integration->fetchOffers($dto);

        DB::transaction(function () use ($simulation, $offers, $dto) {
            $this->offers->storeMany($simulation, $offers, $dto->toAuditPayload());
            $this->simulations->updateStatus($simulation, $this->resolveStatus($offers));
        });

        return $this->simulations->findWithOffers($simulation->id);
    }

    /**
     * Calcula a parcela máxima permitida (margem consignável).
     * Arredonda para baixo, em centavos, para nunca exceder o limite legal.
     *
     * @param float $grossSalary Salário bruto mensal.
     */
    public function calculateMargin(float $grossSalary): float
    {
        return floor($grossSalary * config('loan.margin_percent')) / 100;
    }

    /**
     * Define a situação final: "processed" se algum banco deu resposta definitiva
     * (aprovou ou reprovou); "failed" se todos falharam por erro ou timeout.
     *
     * @param BankOfferDTO[] $offers Ofertas retornadas pelos bancos.
     */
    private function resolveStatus(array $offers): SimulationStatus
    {
        foreach ($offers as $offer) {
            if (in_array($offer->status, [OfferStatus::Approved, OfferStatus::Rejected], true)) {
                return SimulationStatus::Processed;
            }
        }

        return SimulationStatus::Failed;
    }
}