<?php


namespace App\Integrations\Banks\Contracts;

use App\DTOs\BankOfferDTO;
use App\DTOs\SimulationRequestDTO;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Contrato que todo banco integrado precisa cumprir.
 *
 * A chamada é dividida em duas etapas para permitir concorrência:
 * primeiro monta-se a requisição dentro de um Http::pool, depois
 * interpreta-se a resposta (ou a falha) já com todas as chamadas concluídas.
 */
interface BankClientInterface
{
    /**
     * Identificador curto e estável do banco (ex.: "alfa").
     * Também é a chave da resposta dentro do pool.
     */
    public function key(): string;

    /**
     * Nome de exibição do banco (ex.: "Banco Alfa").
     */
    public function name(): string;

    /**
     * Registra a requisição do banco dentro do pool, sem aguardar a resposta.
     *
     * @param Pool $pool Pool de requisições concorrentes.
     * @param SimulationRequestDTO $dto Dados da simulação.
     */
    public function buildRequest(Pool $pool, SimulationRequestDTO $dto): PromiseInterface;

    /**
     * Converte a resposta do banco (ou a falha de conexão) no formato unificado.
     * Nunca lança exceção: qualquer problema vira um BankOfferDTO com status
     * Error ou Timeout, para que um banco com falha não derrube os outros.
     *
     * @param Response|Throwable $result Resposta HTTP ou exceção de conexão.
     * @param SimulationRequestDTO $dto Dados da simulação.
     */
    public function parse(Response|Throwable $result, SimulationRequestDTO $dto): BankOfferDTO;
}