<?php

/*
|--------------------------------------------------------------------------
| Mensagens de validação (pt-BR)
|--------------------------------------------------------------------------
| Cobre apenas as regras usadas pelo StoreSimulationRequest.
| Para ativar, defina APP_LOCALE=pt_BR no .env.
*/

return [
    'required' => 'Preencha o campo :attribute.',
    'string' => 'O campo :attribute deve ser um texto.',
    'email' => 'Informe um e-mail válido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'enum' => 'Selecione uma opção válida em :attribute.',
    'exists' => 'O valor informado em :attribute não é válido.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
        'numeric' => 'O valor mínimo de :attribute é :min.',
    ],
    'max' => [
        'string' => 'O campo :attribute deve ter no máximo :max caracteres.',
        'numeric' => 'O valor máximo de :attribute é :max.',
    ],
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
    ],

    'attributes' => [
        'full_name' => 'nome completo',
        'cpf' => 'CPF',
        'email' => 'e-mail',
        'gross_salary' => 'salário bruto',
        'employer_cnpj' => 'CNPJ do empregador',
        'employment_type' => 'tipo de vínculo',
        'requested_amount' => 'valor desejado',
        'requested_term_months' => 'prazo',
    ],
];
