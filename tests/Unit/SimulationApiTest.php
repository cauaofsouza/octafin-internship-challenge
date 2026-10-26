<?php

namespace Tests\Unit;

use App\Models\Employer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Testes de ponta a ponta do endpoint de simulação, com os bancos simulados por Http::fake.
 */
class SimulationApiTest extends TestCase
{
    use RefreshDatabase;

    /** Cadastra o empregador conveniado usado nos testes. */
    protected function setUp(): void
    {
        parent::setUp();

        Employer::create([
            'corporate_name' => 'Empresa Exemplo Ltda',
            'cnpj' => '11222333000181',
            'is_active' => true,
        ]);
    }

    /**
     * Payload válido de simulação.
     *
     * @param array $overrides Campos a sobrescrever.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Maria Silva',
            'cpf' => '529.982.247-25',
            'email' => 'maria@teste.com',
            'gross_salary' => 5000,
            'employer_cnpj' => '11.222.333/0001-81',
            'employment_type' => 'clt',
            'requested_amount' => 10000,
            'requested_term_months' => 24,
        ], $overrides);
    }

    /**
     * Simula as respostas dos bancos. Por padrão: Alfa e Beta aprovam, Gamma fica fora do ar.
     *
     * @param array $overrides Respostas a sobrescrever, indexadas pelo padrão de URL.
     */
    private function fakeBanks(array $overrides = []): void
    {
        Http::fake(array_merge([
            '*/mock/banks/alfa' => Http::response([
                'result' => 'APPROVED', 'approved_value' => 10000, 'monthly_rate' => 1.79,
                'installment' => 516.22, 'cet_month' => 1.8958,
            ]),
            '*/mock/banks/beta' => Http::response([
                'status' => 'OK',
                'data' => ['limit' => 10000, 'rate' => 0.0189, 'monthlyPayment' => 522.15, 'effectiveCost' => 0.019785],
            ]),
            '*/mock/banks/gamma' => Http::response(['error' => 'indisponível'], 503),
        ], $overrides));
    }

    /** Ofertas aprovadas vêm ordenadas por menor CET e o banco fora do ar não derruba a resposta. */
    public function test_cria_simulacao_e_ordena_ofertas_por_cet(): void
    {
        $this->fakeBanks();

        $response = $this->postJson('/api/simulations', $this->payload(), ['Idempotency-Key' => 'chave-teste-0001']);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.offers.0.bank', 'Banco Alfa')
            ->assertJsonPath('data.offers.1.bank', 'Banco Beta')
            ->assertJsonPath('data.offers.2.status', 'error')
            ->assertJsonPath('data.best_offer.bank', 'Banco Alfa')
            ->assertJsonPath('data.summary.approved', 2)
            ->assertJsonPath('data.summary.unavailable', 1);

        // O CPF completo nunca aparece na resposta.
        $this->assertStringNotContainsString('52998224725', $response->getContent());
    }

    /** CPF inválido é recusado antes de qualquer chamada aos bancos. */
    public function test_recusa_cpf_invalido(): void
    {
        Http::fake();

        $this->postJson('/api/simulations', $this->payload(['cpf' => '111.111.111-11']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');

        Http::assertNothingSent();
    }

    /** CNPJ válido, mas sem convênio cadastrado, é recusado. */
    public function test_recusa_empregador_sem_convenio(): void
    {
        Http::fake();

        $this->postJson('/api/simulations', $this->payload(['employer_cnpj' => '11444777000161']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employer_cnpj');
    }

    /** A mesma Idempotency-Key não cria outra simulação nem consulta os bancos de novo. */
    public function test_idempotency_key_evita_simulacao_duplicada(): void
    {
        $this->fakeBanks();
        $headers = ['Idempotency-Key' => 'chave-teste-0002'];

        $first = $this->postJson('/api/simulations', $this->payload(), $headers)->assertCreated();
        $second = $this->postJson('/api/simulations', $this->payload(), $headers)->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('simulations', 1);
        $this->assertDatabaseCount('bank_offers', 3);
        Http::assertSentCount(3);
    }

    /** Se todos os bancos falham, a simulação termina como "failed". */
    public function test_todos_os_bancos_fora_do_ar_marca_simulacao_como_failed(): void
    {
        $this->fakeBanks([
            '*/mock/banks/alfa' => Http::response([], 503),
            '*/mock/banks/beta' => Http::response([], 503),
        ]);

        $this->postJson('/api/simulations', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.best_offer', null)
            ->assertJsonPath('data.summary.unavailable', 3);
    }

    /** Depois de 5 simulações por minuto para o mesmo CPF, a 6ª recebe 429. */
    public function test_limita_requisicoes_por_cpf(): void
    {
        $this->fakeBanks();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/simulations', $this->payload())->assertCreated();
        }

        $this->postJson('/api/simulations', $this->payload())->assertStatus(429);
    }

    /** A consulta usa o UUID público, e identificadores inválidos retornam 404. */
    public function test_consulta_simulacao_pelo_uuid(): void
    {
        $this->fakeBanks();
        $id = $this->postJson('/api/simulations', $this->payload())->json('data.id');

        $this->getJson("/api/simulations/{$id}")->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/api/simulations/1')->assertNotFound();
    }
}
