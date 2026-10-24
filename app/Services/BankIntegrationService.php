<?php


namespace App\Services;

use App\DTOs\BankOfferDTO;
use App\DTOs\SimulationRequestDTO;
use App\Enums\OfferStatus;
use App\Integrations\Banks\Contracts\BankClientInterface;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Orquestra as chamadas aos bancos: dispara todas em paralelo (Http::pool)
 * e converte cada resposta no formato unificado. Um banco lento ou fora do ar
 * não impede que os demais retornem suas ofertas.
 */
final class BankIntegrationService
{
    /**
     * @param BankClientInterface[] $clients Clientes dos bancos habilitados.
     */
    public function __construct(private readonly array $clients)
    {
    }

    /**
     * Consulta todos os bancos em paralelo.
     *
     * @param SimulationRequestDTO $dto Dados da simulação.
     *
     * @return BankOfferDTO[] Uma oferta por banco, na ordem dos clientes
     *                        (inclusive as que falharam ou deram timeout).
     */
    public function fetchOffers(SimulationRequestDTO $dto): array
    {
        if ($this->clients === []) {
            return [];
        }

        $responses = Http::pool(fn(Pool $pool) => array_map(
            fn(BankClientInterface $client) => $client->buildRequest($pool, $dto),
            $this->clients,
        ));

        return array_map(
            fn(BankClientInterface $client) => $this->parseSafely(
                $client,
                $responses[$client->key()] ?? null,
                $dto,
            ),
            $this->clients,
        );
    }

    /**
     * Interpreta o resultado de um banco sem nunca lançar exceção.
     *
     * @param BankClientInterface $client Cliente do banco.
     * @param mixed $result Resposta HTTP, exceção de conexão ou null.
     * @param SimulationRequestDTO $dto Dados da simulação.
     */
    private function parseSafely(BankClientInterface $client, mixed $result, SimulationRequestDTO $dto): BankOfferDTO
    {
        try {
            $offer = $client->parse($result ?? new RuntimeException('Sem resposta do pool.'), $dto);
        } catch (Throwable) {
            $offer = BankOfferDTO::failed(
                $client->name(),
                OfferStatus::Error,
                'Falha ao interpretar a resposta do banco.',
            );
        }

        if (in_array($offer->status, [OfferStatus::Error, OfferStatus::Timeout], true)) {
            // Log sem dados pessoais: apenas banco e tipo de falha.
            Log::warning('Banco sem resposta válida', [
                'bank' => $client->key(),
                'status' => $offer->status->value,
            ]);
        }

        return $offer;
    }
}