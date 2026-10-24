<?php

return [
    // Percentual máximo da renda bruta que pode ser comprometido com a parcela.
    'margin_percent' => (float) env('LOAN_MARGIN_PERCENT', 35),

    // Limites de requisições por minuto no endpoint de simulação.
    'rate_limit' => [
        'per_ip' => (int) env('SIMULATION_RATE_LIMIT_IP', 10),
        'per_cpf' => (int) env('SIMULATION_RATE_LIMIT_CPF', 5),
    ],
];