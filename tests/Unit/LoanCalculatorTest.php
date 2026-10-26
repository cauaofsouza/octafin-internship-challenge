<?php

namespace Tests\Unit;

use App\Support\LoanCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Testes dos cálculos financeiros (Tabela Price e CET).
 */
class LoanCalculatorTest extends TestCase
{
    /** A parcela de 10.000 em 24x a 1,79% a.m. deve ser 516,22. */
    public function test_calcula_parcela_pela_tabela_price(): void
    {
        $this->assertSame(516.22, LoanCalculator::installment(10000, 1.79, 24));
    }

    /** Com taxa zero, a parcela é o valor dividido pelo prazo. */
    public function test_parcela_com_taxa_zero(): void
    {
        $this->assertSame(100.0, LoanCalculator::installment(1200, 0, 12));
    }

    /** O valor máximo financiável é o inverso do cálculo da parcela. */
    public function test_valor_maximo_financiavel_e_o_inverso_da_parcela(): void
    {
        $this->assertEqualsWithDelta(10000, LoanCalculator::maxPrincipal(516.22, 1.79, 24), 1.0);
    }

    /** O CET deve ser maior que a taxa nominal quando há tarifa na contratação. */
    public function test_cet_maior_que_a_taxa_nominal_com_tarifa(): void
    {
        $cet = LoanCalculator::monthlyCet(10000, 516.22, 24, 1.2);

        $this->assertGreaterThan(1.79, $cet);
        $this->assertEqualsWithDelta(1.8958, $cet, 0.001);
    }
}
