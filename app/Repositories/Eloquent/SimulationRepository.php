<?php


namespace App\Repositories\Eloquent;

use App\Enums\SimulationStatus;
use App\Models\Simulation;
use App\Repositories\Contracts\SimulationRepositoryInterface;

/**
 * Implementação Eloquent do repositório de simulações.
 */
class SimulationRepository implements SimulationRepositoryInterface
{
    /** {@inheritDoc} */
    public function create(array $data): Simulation
    {
        return Simulation::create($data);
    }

    /** {@inheritDoc} */
    public function findByIdempotencyKey(string $key): ?Simulation
    {
        return Simulation::with('offers')->where('idempotency_key', $key)->first();
    }

    /** {@inheritDoc} */
    public function findWithOffers(int $id): ?Simulation
    {
        return Simulation::with('offers')->find($id);
    }

    /** {@inheritDoc} */
    public function updateStatus(Simulation $simulation, SimulationStatus $status): void
    {
        $simulation->update(['status' => $status]);
    }
}