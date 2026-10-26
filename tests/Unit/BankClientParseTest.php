<?php

namespace Tests\Unit;

use App\DTOs\SimulationRequestDTO;
use App\Enums\EmploymentType;
use App\Enums\OfferStatus;
use App\Integrations\Banks\AlfaBankClient;
use App\Integrations\Banks\BetaBankClient;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

/**
 * Testes da interpretação das respostas (e falhas) dos bancos.
 */
class BankClientParseTest extends TestCase
{
    /** Cria uma simulação de exemplo. */
    private function dto(): SimulationRequestDTO
    {
        return new SimulationRequestDTO(
            'Maria Silva', '52998224725', 'maria@teste.com', 5000.0,
            '11222333000181', EmploymentType::Clt, 10000.0, 24, 1750.0,
        );
    }

    /** Exceção de tempo limite do cURL vira oferta com status Timeout. */
    public function test_excecao_de_timeout_vira_status_timeout(): void
    {
        $client = new AlfaBankClient('alfa', config('banks.banks.alfa'));

        $offer = $client->parse(new ConnectionException('cURL error 28: Operation timed out'), $this->dto());

        $this->assertSame(OfferStatus::Timeout, $offer->status);
    }

    /** Qualquer outra falha de conexão vira oferta com status Error. */
    public function test_falha_de_conexao_vira_status_error(): void
    {
        $client = new AlfaBankClient('alfa', config('banks.banks.alfa'));

        $offer = $client->parse(new ConnectionException('Connection refused'), $this->dto());

        $this->assertSame(OfferStatus::Error, $offer->status);
    }

    /** O Banco Beta devolve a taxa como fração; o DTO deve trazê-la em percentual. */
    public function test_beta_normaliza_taxa_para_percentual(): void
    {
        $body = json_encode(['status' => 'OK', 'data' => [
            'limit' => 10000, 'rate' => 0.0189, 'monthlyPayment' => 522.15, 'effectiveCost' => 0.019785,
        ]]);
        $response = new Response(new PsrResponse(200, ['Content-Type' => 'application/json'], $body));

        $offer = (new BetaBankClient('beta', config('banks.banks.beta')))->parse($response, $this->dto());

        $this->assertSame(OfferStatus::Approved, $offer->status);
        $this->assertSame(1.89, $offer->interestRateMonthly);
    }

    /** Corpo fora do formato esperado vira Error, sem lançar exceção. */
    public function test_resposta_fora_do_formato_vira_status_error(): void
    {
        $response = new Response(new PsrResponse(200, ['Content-Type' => 'application/json'], '{"foo":"bar"}'));

        $offer = (new AlfaBankClient('alfa', config('banks.banks.alfa')))->parse($response, $this->dto());

        $this->assertSame(OfferStatus::Error, $offer->status);
    }
}
