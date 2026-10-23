<?php


namespace App\Support;

/**
 * Cálculos financeiros da Tabela Price usados pelos bancos simulados
 * e, depois, pela regra de margem consignável.
 *
 * Observação: usa float por simplicidade (MVP). Em produção, o ideal é
 * trabalhar com centavos inteiros ou bcmath.
 */
final class LoanCalculator
{
    /**
     * Calcula o valor da parcela mensal fixa (Tabela Price).
     *
     * @param float $principal Valor financiado.
     * @param float $monthlyRatePercent Taxa mensal em percentual (ex.: 1.79).
     * @param int $months Número de parcelas.
     */
    public static function installment(float $principal, float $monthlyRatePercent, int $months): float
    {
        $i = $monthlyRatePercent / 100;

        if ($i <= 0.0) {
            return round($principal / $months, 2);
        }

        return round($principal * $i / (1 - (1 + $i) ** -$months), 2);
    }

    /**
     * Calcula o maior valor financiável para uma parcela máxima (inverso da Price).
     *
     * @param float $installment Parcela máxima que cabe na margem.
     * @param float $monthlyRatePercent Taxa mensal em percentual.
     * @param int $months Número de parcelas.
     */
    public static function maxPrincipal(float $installment, float $monthlyRatePercent, int $months): float
    {
        $i = $monthlyRatePercent / 100;

        if ($i <= 0.0) {
            return round($installment * $months, 2);
        }

        return round($installment * (1 - (1 + $i) ** -$months) / $i, 2);
    }

    /**
     * Calcula o CET mensal (Custo Efetivo Total) por bisseção.
     *
     * O CET é a taxa que iguala o valor líquido recebido (principal menos
     * tarifas cobradas na contratação) ao valor presente das parcelas.
     *
     * @param float $principal Valor financiado.
     * @param float $installment Valor da parcela.
     * @param int $months Número de parcelas.
     * @param float $upfrontFeePercent Tarifa cobrada na contratação, em percentual do principal.
     *
     * @return float CET mensal em percentual, com 4 casas decimais.
     */
    public static function monthlyCet(
        float $principal,
        float $installment,
        int   $months,
        float $upfrontFeePercent,
    ): float
    {
        $netAmount = $principal * (1 - $upfrontFeePercent / 100);
        $low = 0.0;
        $high = 1.0; // limite superior: 100% ao mês
        $mid = 0.5;

        for ($step = 0; $step < 100; $step++) {
            $mid = ($low + $high) / 2;
            $presentValue = $installment * (1 - (1 + $mid) ** -$months) / $mid;

            // Valor presente maior que o líquido: a taxa testada está baixa demais.
            if ($presentValue > $netAmount) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return round($mid * 100, 4);
    }
}