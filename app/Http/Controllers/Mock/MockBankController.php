<?php

namespace App\Http\Controllers\Mock;

use App\Http\Controllers\Controller;
use App\Support\LoanCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Simula as APIs externas dos bancos. Existe apenas para o MVP:
 * cada banco tem formato de payload, regras, latência e taxa de falha próprios,
 * configuráveis em config/banks.php.
 */
class MockBankController extends Controller
{
    /**
     * Banco Alfa: aprova o valor pedido se a parcela couber na margem.
     *
     * @param Request $request Requisição recebida do cliente Alfa.
     */
    public function alfa(Request $request): JsonResponse
    {
        $config = config('banks.banks.alfa');

        if ($earlyResponse = $this->applyMockBehavior($request, $config)) {
            return $earlyResponse;
        }

        $data = $request->validate([
            'amount' => 'required|numeric',
            'months' => 'required|integer|min:1',
            'margin' => 'required|numeric',
        ]);

        $amount = (float) $data['amount'];
        $term = (int) $data['months'];
        $mock = $config['mock'];
        $installment = LoanCalculator::installment($amount, $mock['rate'], $term);

        if ($installment > (float) $data['margin']) {
            return response()->json([
                'result' => 'DENIED',
                'reason' => 'Parcela excede a margem consignável',
            ]);
        }

        return response()->json([
            'result' => 'APPROVED',
            'approved_value' => $amount,
            'monthly_rate' => $mock['rate'],
            'installment' => $installment,
            'cet_month' => LoanCalculator::monthlyCet($amount, $installment, $term, $mock['fee']),
        ]);
    }

    /**
     * Banco Beta: reprova renda baixa (score) e aprova valor reduzido
     * quando o pedido não cabe na margem ou no limite de 12x a renda.
     *
     * @param Request $request Requisição recebida do cliente Beta.
     */
    public function beta(Request $request): JsonResponse
    {
        $config = config('banks.banks.beta');

        if ($earlyResponse = $this->applyMockBehavior($request, $config)) {
            return $earlyResponse;
        }

        $data = $request->validate([
            'monthlyIncome' => 'required|numeric',
            'requestedValue' => 'required|numeric',
            'installments' => 'required|integer|min:1',
            'maxMonthlyPayment' => 'required|numeric',
        ]);

        $income = (float) $data['monthlyIncome'];
        $term = (int) $data['installments'];
        $mock = $config['mock'];

        if ($income < 1500) {
            return response()->json([
                'status' => 'REFUSED',
                'error' => ['code' => 'LOW_SCORE', 'message' => 'Score interno insuficiente'],
            ]);
        }

        $limit = min(
            (float) $data['requestedValue'],
            $income * 12,
            LoanCalculator::maxPrincipal((float) $data['maxMonthlyPayment'], $mock['rate'], $term),
        );

        if ($limit < 500) {
            return response()->json([
                'status' => 'REFUSED',
                'error' => ['code' => 'MARGIN_EXCEEDED', 'message' => 'Margem insuficiente'],
            ]);
        }

        $installment = LoanCalculator::installment($limit, $mock['rate'], $term);

        return response()->json([
            'status' => 'OK',
            'data' => [
                'limit' => $limit,
                'rate' => $mock['rate'] / 100, // fração decimal
                'monthlyPayment' => $installment,
                'effectiveCost' => LoanCalculator::monthlyCet($limit, $installment, $term, $mock['fee']) / 100,
            ],
        ]);
    }

    /**
     * Banco Gamma: não atende pensionistas do INSS; é lento e instável.
     *
     * @param Request $request Requisição recebida do cliente Gamma.
     */
    public function gamma(Request $request): JsonResponse
    {
        $config = config('banks.banks.gamma');

        if ($earlyResponse = $this->applyMockBehavior($request, $config)) {
            return $earlyResponse;
        }

        $data = $request->validate([
            'customer.bond' => 'required|string',
            'loan.amount' => 'required|numeric',
            'loan.term' => 'required|integer|min:1',
            'constraints.max_installment' => 'required|numeric',
        ]);

        $amount = (float) $data['loan']['amount'];
        $term = (int) $data['loan']['term'];
        $mock = $config['mock'];
        $installment = LoanCalculator::installment($amount, $mock['rate'], $term);

        $denialReason = null;
        if ($data['customer']['bond'] === 'inss_pensioner') {
            $denialReason = 'Perfil de vínculo não atendido';
        } elseif ($installment > (float) $data['constraints']['max_installment']) {
            $denialReason = 'Parcela excede a margem consignável';
        }

        if ($denialReason !== null) {
            return response()->json(['proposal' => ['approved' => false, 'denial_reason' => $denialReason]]);
        }

        return response()->json([
            'proposal' => [
                'approved' => true,
                'amount' => $amount,
                'installment' => $installment,
                'interest' => ['monthly_percent' => $mock['rate']],
                'cet' => ['monthly_percent' => LoanCalculator::monthlyCet($amount, $installment, $term, $mock['fee'])],
            ],
        ]);
    }

    /**
     * Etapas comuns a todos os mocks: valida a credencial, aplica a latência
     * simulada e sorteia falhas (HTTP 503) conforme a configuração do banco.
     *
     * @param Request $request Requisição recebida.
     * @param array   $config  Configuração do banco em config/banks.php.
     *
     * @return JsonResponse|null Resposta de erro, ou null para seguir o fluxo normal.
     */
    private function applyMockBehavior(Request $request, array $config): ?JsonResponse
    {
        if ($request->header('X-Api-Key') !== $config['api_key']) {
            return response()->json(['error' => 'Credencial inválida'], 401);
        }

        [$minLatencyMs, $maxLatencyMs] = $config['mock']['latency_ms'];
        usleep(random_int($minLatencyMs, $maxLatencyMs) * 1000);

        if (random_int(0, 9999) / 10000 < $config['mock']['failure_rate']) {
            return response()->json(['error' => 'Serviço indisponível'], 503);
        }

        return null;
    }
}