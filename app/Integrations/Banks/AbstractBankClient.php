<?php


namespace App\Integrations\Banks;

use App\DTOs\BankOfferDTO;
use App\DTOs\SimulationRequestDTO;
use App\Enums\OfferStatus;
use App\Integrations\Banks\Contracts\BankClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Throwable;
use UnexpectedValueException;

/**
 * Comportamento comum a todos os bancos: montagem da requisição HTTP
 * (credencial, timeouts) e tratamento padronizado de falhas.
 * Cada banco só precisa dizer como monta o payload e como lê a resposta.
 */
abstract class AbstractBankClient implements BankClientInterface
{
    /**
     * @param string $key Identificador do banco em config/banks.php.
     * @param array $config Configuração do banco (url, api_key, timeouts, name).
     */
    public function __construct(
        protected readonly string $key,
        protected readonly array  $config,
    )
    {
    }

    /** {@inheritDoc} */
    public function key(): string
    {
        return $this->key;
    }

    /** {@inheritDoc} */
    public function name(): string
    {
        return $this->config['name'];
    }

    /** {@inheritDoc} */
    public function buildRequest(Pool $pool, SimulationRequestDTO $dto): PromiseInterface
    {
        return $pool->as($this->key)
            ->withHeaders(['X-Api-Key' => $this->config['api_key']])
            ->acceptJson()
            ->connectTimeout($this->config['connect_timeout'])
            ->timeout($this->config['timeout'])
            ->post($this->config['url'], $this->buildPayload($dto));
    }

    /** {@inheritDoc} */
    public function parse(Response|Throwable $result, SimulationRequestDTO $dto): BankOfferDTO
    {
        if ($result instanceof Throwable) {
            return $this->fromException($result);
        }

        $elapsedMs = (int)round(($result->handlerStats()['total_time'] ?? 0) * 1000);
        $body = $result->json();
        $body = is_array($body) ? $body : null;

        if ($result->failed()) {
            return BankOfferDTO::failed(
                $this->name(),
                OfferStatus::Error,
                "O banco respondeu com HTTP {$result->status()}.",
                $elapsedMs,
                $body,
            );
        }

        try {
            return $this->parseBody($body ?? [], $dto, $elapsedMs);
        } catch (Throwable) {
            return BankOfferDTO::failed(
                $this->name(),
                OfferStatus::Error,
                'Resposta do banco fora do formato esperado.',
                $elapsedMs,
                $body,
            );
        }
    }

    /**
     * Monta o corpo da requisição no formato próprio do banco.
     *
     * @param SimulationRequestDTO $dto Dados da simulação.
     */
    abstract protected function buildPayload(SimulationRequestDTO $dto): array;

    /**
     * Interpreta o corpo JSON do banco e devolve a oferta unificada.
     * Deve lançar UnexpectedValueException se faltar algum campo obrigatório.
     *
     * @param array $body Corpo da resposta já decodificado.
     * @param SimulationRequestDTO $dto Dados da simulação.
     * @param int $elapsedMs Tempo de resposta em milissegundos.
     */
    abstract protected function parseBody(array $body, SimulationRequestDTO $dto, int $elapsedMs): BankOfferDTO;

    /**
     * Lê um campo obrigatório do corpo (aceita notação com ponto).
     *
     * @param array $body Corpo da resposta.
     * @param string $path Caminho do campo (ex.: "data.rate").
     *
     * @throws UnexpectedValueException Se o campo não existir.
     */
    protected function required(array $body, string $path): mixed
    {
        return Arr::get($body, $path)
            ?? throw new UnexpectedValueException("Campo obrigatório ausente: {$path}");
    }

    /**
     * Converte uma falha de conexão em oferta com status Timeout ou Error.
     * A mensagem da exceção não é exposta, pois pode conter URL e credenciais.
     *
     * @param Throwable $e Exceção lançada pelo cliente HTTP.
     */
    protected function fromException(Throwable $e): BankOfferDTO
    {
        // cURL error 28 = operação excedeu o tempo limite.
        $isTimeout = str_contains($e->getMessage(), 'cURL error 28');

        return BankOfferDTO::failed(
            $this->name(),
            $isTimeout ? OfferStatus::Timeout : OfferStatus::Error,
            $isTimeout
                ? 'O banco não respondeu dentro do tempo limite.'
                : 'Falha de comunicação com o banco.',
        );
    }
}