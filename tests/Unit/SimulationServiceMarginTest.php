<?php

namespace Tests\Unit;

use App\Services\SimulationService;
use Tests\TestCase;

/**
 * Testes da regra de margem consignável (35% da renda bruta).
 */
class SimulationServiceMarginTest extends TestCase
{
    /** A margem é 35% do salário bruto. */
    public function test_margem_e_35_por_cento_do_salario(): void
    {
        $this->assertSame(1750.0, app(SimulationService::class)->calculateMargin(5000.0));
    }

    /** A margem é arredondada para baixo em centavos, nunca para cima. */
    public function test_margem_arredonda_para_baixo(): void
    {
        $this->assertSame(350.17, app(SimulationService::class)->calculateMargin(1000.50));
    }
}
