<?php


use App\Integrations\Banks\AlfaBankClient;
use App\Integrations\Banks\BetaBankClient;
use App\Integrations\Banks\GammaBankClient;

/*
|--------------------------------------------------------------------------
| Bancos integrados
|--------------------------------------------------------------------------
| Cada banco define a classe cliente, a URL, a credencial e os timeouts.
| A chave "mock" só é usada pelos endpoints simulados (MockBankController).
| Para apontar para outro serviço (ex.: container separado), basta mudar
| as URLs no .env, sem alterar código.
*/

$mockBase = env('BANK_MOCK_BASE_URL', 'http://localhost:8000/api/mock/banks');

return [
    'banks' => [
        'alfa' => [
            'enabled' => true,
            'name' => 'Banco Alfa',
            'client' => AlfaBankClient::class,
            'url' => env('BANK_ALFA_URL', $mockBase . '/alfa'),
            'api_key' => env('BANK_ALFA_API_KEY', 'alfa-dev-key'),
            'connect_timeout' => 1.0,
            'timeout' => (float)env('BANK_ALFA_TIMEOUT', 3),
            'mock' => [
                'rate' => 1.79,           // taxa mensal (%)
                'fee' => 1.2,             // tarifa na contratação (% do principal)
                'latency_ms' => [200, 600],
                'failure_rate' => 0.0,    // probabilidade de HTTP 503
            ],
        ],
        'beta' => [
            'enabled' => true,
            'name' => 'Banco Beta',
            'client' => BetaBankClient::class,
            'url' => env('BANK_BETA_URL', $mockBase . '/beta'),
            'api_key' => env('BANK_BETA_API_KEY', 'beta-dev-key'),
            'connect_timeout' => 1.0,
            'timeout' => (float)env('BANK_BETA_TIMEOUT', 3),
            'mock' => [
                'rate' => 1.89,
                'fee' => 1.0,
                'latency_ms' => [500, 1500],
                'failure_rate' => 0.0,
            ],
        ],
        'gamma' => [
            'enabled' => true,
            'name' => 'Banco Gamma',
            'client' => GammaBankClient::class,
            'url' => env('BANK_GAMMA_URL', $mockBase . '/gamma'),
            'api_key' => env('BANK_GAMMA_API_KEY', 'gamma-dev-key'),
            'connect_timeout' => 1.0,
            'timeout' => (float)env('BANK_GAMMA_TIMEOUT', 3),
            'mock' => [
                'rate' => 1.69,
                'fee' => 0.8,
                'latency_ms' => [800, 4000], // pode estourar o timeout de 3s
                'failure_rate' => (float)env('BANK_GAMMA_FAILURE_RATE', 0.2),
            ],
        ],
    ],
];