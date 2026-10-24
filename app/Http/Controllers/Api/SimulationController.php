<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSimulationRequest;
use App\Http\Resources\SimulationResource;
use App\Models\Simulation;
use App\Services\SimulationService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints públicos da simulação de empréstimo consignado.
 * Mantém apenas a camada HTTP: a regra de negócio fica no SimulationService.
 */
class SimulationController extends Controller
{
    public function __construct(private readonly SimulationService $service)
    {
    }

    /**
     * Cria uma simulação e consulta os bancos.
     * Aceita o cabeçalho opcional Idempotency-Key para evitar simulações duplicadas.
     *
     * @param StoreSimulationRequest $request Requisição já validada.
     */
    public function store(StoreSimulationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $simulation = $this->service->simulate($validated, $validated['idempotency_key'] ?? null);

        return (new SimulationResource($simulation))->response()->setStatusCode(201);
    }

    /**
     * Consulta uma simulação pelo identificador público (UUID).
     *
     * @param Simulation $simulation Simulação resolvida pela rota.
     */
    public function show(Simulation $simulation): SimulationResource
    {
        return new SimulationResource($simulation->load('offers'));
    }
}