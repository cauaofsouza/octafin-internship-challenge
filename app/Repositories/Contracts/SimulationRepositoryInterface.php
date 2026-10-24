<?php


namespace App\Repositories\Contracts;

use App\Enums\SimulationStatus;
use App\Models\Simulation;

/**
 * Acesso a dados das simulações. A regra de negócio depende deste contrato,
 * não do Eloquent.
 */
interface SimulationRepositoryInterface
{
    /**
     * Cria uma simulação.
     *
     * @param array $data Atributos da simulação.
     */
    public function create(array $data): Simulation;

    /**
     * Busca uma simulação (com ofertas) pela chave de idempotência.
     *
     * @param string $key Valor do cabeçalho Idempotency-Key.
     */
    public function findByIdempotencyKey(string $key): ?Simulation;

    /**
     * Busca uma simulação com suas ofertas.
     *
     * @param int $id Identificador da simulação.
     */
    public function findWithOffers(int $id): ?Simulation;

    /**
     * Atualiza a situação da simulação.
     *
     * @param Simulation $simulation Simulação a atualizar.
     * @param SimulationStatus $status Nova situação.
     */
    public function updateStatus(Simulation $simulation, SimulationStatus $status): void;
}