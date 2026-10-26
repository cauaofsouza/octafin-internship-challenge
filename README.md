# Simulador de Empréstimo Consignado (OctaFin)

MVP de uma aplicação web e API para simulação de empréstimo consignado. A aplicação recebe os dados de um cliente, calcula sua margem consignável, consulta **três bancos simulados em paralelo**, normaliza as respostas recebidas em formatos diferentes e apresenta as ofertas, priorizando as propostas aprovadas com menor Custo Efetivo Total (CET).

O objetivo central é demonstrar o consumo e a interpretação de serviços de terceiros que a aplicação não controla, isolando falhas e lentidão para que a indisponibilidade de um banco não impeça a apresentação das respostas dos demais.

- **Backend:** Laravel, API REST e PostgreSQL.
- **Execução local:** Docker Compose.
- **Interface web:** tela de entrada dos dados da simulação e tela de visualização das ofertas.
- **Dados fictícios:** nenhuma API bancária real é utilizada.
- **Autenticação:** ainda não implementada nesta versão; está planejada para uma próxima entrega.

## Sumário

1. [Fluxo da simulação](#1-fluxo-da-simulação)
2. [Como executar](#2-como-executar)
3. [Usando a API](#3-usando-a-api)
4. [Regras de negócio](#4-regras-de-negócio)
5. [Bancos simulados](#5-bancos-simulados)
6. [Arquitetura e decisões técnicas](#6-arquitetura-e-decisões-técnicas)
7. [Melhorias e proposições](#7-melhorias-e-proposições)
8. [Limitações](#8-limitações)
9. [Testes](#9-testes)
10. [Uso de IA](#10-uso-de-ia)

---

## 1. Fluxo da simulação

```text
Cliente
  │
  ▼
Tela de simulação (/)
  │  Envia os dados para POST /api/simulations
  ▼
SimulationController ── valida os dados (StoreSimulationRequest)
  │
  ▼
SimulationService
  │  1. verifica a Idempotency-Key, quando informada
  │  2. calcula a margem consignável
  │  3. grava a simulação com status inicial pending
  ▼
BankIntegrationService
  │  4. consulta os três bancos em paralelo (Http::pool)
  ▼
Banco Alfa / Banco Beta / Banco Gamma
  │  Cada banco usa um formato de requisição e resposta próprio
  ▼
Clientes bancários (AlfaBankClient, BetaBankClient, GammaBankClient)
  │  5. normalizam respostas, erros e timeouts em BankOfferDTO
  ▼
SimulationService
  │  6. grava as ofertas e conclui a simulação
  ▼
SimulationResource
  │  7. retorna a simulação, o resumo e as ofertas ordenadas
  ▼
Tela de ofertas (/offers)
     8. lê a resposta armazenada temporariamente no sessionStorage
        e apresenta a melhor oferta e as demais propostas
```

A tela de ofertas depende da resposta recebida durante o envio da simulação e armazenada no `sessionStorage` do navegador. Nesta versão, ela não representa um histórico persistente de simulações do usuário.

## 2. Como executar

### Pré-requisitos

- Docker com Docker Compose v2 (`docker compose`)
- Git

Não é necessário instalar PHP, Composer ou PostgreSQL localmente.

### Passo a passo

```bash
git clone <url-do-repositorio>
cd <pasta-do-repositorio>

cp .env.example .env
# No Windows (cmd): copy .env.example .env

docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose up -d

# Aguarde o PostgreSQL aceitar conexões
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=EmployerSeeder
```

A aplicação fica disponível em:

- **Tela de simulação:** http://localhost:8000/
- **Tela de ofertas:** http://localhost:8000/offers
- **API:** http://localhost:8000/api/

A tela `/offers` é destinada ao fluxo iniciado pela tela de simulação. Abri-la diretamente, sem uma simulação concluída na mesma sessão do navegador, pode não apresentar ofertas.

Para parar os contêineres:

```bash
docker compose down
```

Para apagar também os dados persistidos no volume do banco:

```bash
docker compose down -v
```

### Tela de simulação

A página inicial contém o formulário para informar os dados necessários à análise. A interface inclui máscaras para CPF e CNPJ, estimativa da parcela máxima com base na margem consignável e apresentação de erros de validação por campo. Ao enviar o formulário, o navegador chama `POST /api/simulations`.

### Tela de ofertas

Após uma simulação concluída, a aplicação encaminha o cliente para `/offers`. A tela apresenta a melhor proposta disponível e as ofertas retornadas pelos bancos, incluindo informações como situação, valor ofertado, taxa, CET e parcela, conforme os dados enviados pela API.

A interface não realiza uma nova consulta bancária ao abrir essa página: utiliza a resposta da simulação armazenada temporariamente no `sessionStorage`.

### Variáveis de ambiente

As variáveis abaixo possuem valores padrão no ambiente de desenvolvimento. Confira o `.env.example` do repositório para os valores efetivamente configurados.

| Variável | Padrão/documentação | Descrição |
|---|---|---|
| `APP_PORT` | `8000` | Porta publicada para acessar a aplicação no computador |
| `APP_LOCALE` | `pt_BR` | Idioma das mensagens de validação |
| `DB_HOST` | `db` | Nome do serviço PostgreSQL na rede Docker; não use `localhost` dentro do contêiner da aplicação |
| `DB_PORT` | `5432` | Porta do PostgreSQL |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `octafin_db` / `postgres` / `secret` | Banco e credenciais locais |
| `CACHE_STORE` | `file` | Armazenamento de cache usado pelo limitador de requisições |
| `LOAN_MARGIN_PERCENT` | `35` | Percentual do salário bruto considerado para a margem consignável |
| `SIMULATION_RATE_LIMIT_IP` | `10` | Limite de simulações por minuto por IP |
| `SIMULATION_RATE_LIMIT_CPF` | `5` | Limite de simulações por minuto por CPF |
| `BANK_ALFA_URL`, `BANK_BETA_URL`, `BANK_GAMMA_URL` | Rotas internas de mock | Endereço configurado para cada banco simulado |
| `BANK_ALFA_API_KEY`, `BANK_BETA_API_KEY`, `BANK_GAMMA_API_KEY` | Chaves de desenvolvimento | Credenciais enviadas no cabeçalho `X-Api-Key` |
| `BANK_ALFA_TIMEOUT`, `BANK_BETA_TIMEOUT`, `BANK_GAMMA_TIMEOUT` | `3` segundos | Tempo limite configurado para cada banco |
| `BANK_GAMMA_FAILURE_RATE` | `0.2` | Probabilidade configurada para o Banco Gamma responder com HTTP 503 |

Os valores exatos podem variar conforme o `.env.example` e os arquivos de configuração do projeto. Depois de alterar variáveis de ambiente, reinicie o serviço da aplicação quando necessário:

```bash
docker compose restart app
```

## 3. Usando a API

Envie `Accept: application/json` nas requisições. A aplicação também possui tratamento para manter as respostas da API em JSON.

### `POST /api/simulations`

Cria uma simulação e consulta os bancos simulados.

| Campo | Regra |
|---|---|
| `full_name` | Obrigatório, de 3 a 255 caracteres |
| `cpf` | Obrigatório; CPF válido pelos dígitos verificadores, com ou sem máscara |
| `email` | Obrigatório; e-mail válido |
| `gross_salary` | Obrigatório, maior que 0 e até 1.000.000 |
| `employer_cnpj` | Obrigatório; CNPJ válido de empregador conveniado ativo |
| `employment_type` | Obrigatório: `clt`, `public_servant` ou `inss_pensioner` |
| `requested_amount` | Obrigatório, de 100 a 1.000.000 |
| `requested_term_months` | Obrigatório, inteiro de 1 a 96 |

Cabeçalho opcional: `Idempotency-Key`, com 8 a 64 caracteres, usando letras, números, hífen (`-`) e sublinhado (`_`).

Exemplo com `curl`:

```bash
curl -X POST http://localhost:8000/api/simulations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Idempotency-Key: demo-0001" \
  -d '{
    "full_name": "Maria Silva",
    "cpf": "529.982.247-25",
    "email": "maria@teste.com",
    "gross_salary": 5000,
    "employer_cnpj": "11.222.333/0001-81",
    "employment_type": "clt",
    "requested_amount": 10000,
    "requested_term_months": 24
  }'
```

No PowerShell, uma alternativa é salvar o corpo JSON em `body.json` e executar `curl.exe` com `-d "@body.json"`.

A resposta de sucesso usa HTTP `201` e contém um objeto `data` com o identificador público, o status da simulação, os dados do cliente com informações pessoais mascaradas, os parâmetros da solicitação, um resumo, a melhor oferta e a lista de ofertas. Exemplo abreviado:

```json
{
  "data": {
    "id": "0b6f3c2e-8a54-4f0e-9d0b-6f7f7a0c1a11",
    "status": "processed",
    "customer": {
      "full_name": "Maria Silva",
      "cpf": "***.982.247-**",
      "email": "m***@teste.com"
    },
    "request": {
      "gross_salary": 5000,
      "employer_cnpj": "11222333000181",
      "employment_type": "clt",
      "requested_amount": 10000,
      "requested_term_months": 24
    },
    "summary": {
      "approved": 2,
      "rejected": 0,
      "unavailable": 1
    },
    "best_offer": {
      "bank": "Banco Alfa",
      "status": "approved",
      "offered_amount": 10000,
      "interest_rate_monthly": 1.79,
      "cet_monthly": 1.8958,
      "installment_value": 516.22,
      "total_payable": 12389.28,
      "rejection_reason": null,
      "response_time_ms": 1574
    },
    "offers": [
      {
        "bank": "Banco Alfa",
        "status": "approved",
        "cet_monthly": 1.8958,
        "installment_value": 516.22
      },
      {
        "bank": "Banco Beta",
        "status": "approved",
        "cet_monthly": 1.9785,
        "installment_value": 522.15
      },
      {
        "bank": "Banco Gamma",
        "status": "timeout",
        "rejection_reason": "O banco não respondeu dentro do tempo limite."
      }
    ],
    "created_at": "2026-10-24T07:10:22-03:00"
  }
}
```

O exemplo é ilustrativo, e os resultados, valores e tempos variam conforme as respostas dos bancos simulados. A lista acima foi abreviada; a API retorna os campos completos definidos pelo recurso de oferta.

Status possíveis para uma oferta:

- `approved`: oferta aprovada;
- `rejected`: o banco recusou a proposta;
- `error`: ocorreu falha de integração ou a resposta foi inválida;
- `timeout`: o banco não respondeu dentro do tempo limite.

Status possíveis para uma simulação:

- `processed`: pelo menos um banco retornou uma resposta definitiva;
- `failed`: todos os bancos falharam ou ficaram indisponíveis.

### `GET /api/simulations/{id}`

Consulta uma simulação pelo identificador público UUID. Retorna `404` quando a simulação não é encontrada ou o identificador não corresponde a um registro existente.

### Códigos de resposta

| Código | Significado |
|---|---|
| `201` | Simulação criada ou registro existente devolvido para uma `Idempotency-Key` já utilizada |
| `200` | Consulta realizada |
| `404` | Simulação não encontrada |
| `422` | Dados inválidos, com detalhes de validação por campo |
| `429` | Limite de requisições excedido; a resposta pode incluir `Retry-After` |

### Dados de teste e cenários

O seeder cadastra dois empregadores conveniados: `11222333000181` (Empresa Exemplo Ltda) e `11444777000161` (Prefeitura Exemplo).

CPFs válidos fictícios para testes: `52998224725`, `12345678909`, `11144477735` e `39053344705`.

| Cenário | Como testar | Resultado esperado |
|---|---|---|
| Caminho feliz | Use o exemplo de requisição acima | Os bancos retornam suas decisões; o Gamma pode aprovar, recusar ou falhar |
| Todas as propostas recusadas | Use `gross_salary: 1200` | Margem de 420; Alfa e Gamma recusam por margem e Beta recusa por score, conforme as regras simuladas |
| Vínculo não atendido | Use `employment_type: "inss_pensioner"` | Gamma recusa esse tipo de vínculo |
| CPF inválido | Use `cpf: "111.111.111-11"` | HTTP `422`; os bancos não são consultados |
| Empregador sem convênio | Use `employer_cnpj: "12345678000195"` | HTTP `422` |
| Envio repetido | Repita a requisição com a mesma `Idempotency-Key` | A simulação existente é devolvida, sem nova consulta aos bancos |
| Falha do Gamma | Configure `BANK_GAMMA_FAILURE_RATE=1` e reinicie a aplicação | Gamma retorna erro de integração; os demais bancos podem responder normalmente |

## 4. Regras de negócio

- **Margem consignável:** a parcela não deve comprometer mais que 35% do salário bruto. O percentual é configurável por `LOAN_MARGIN_PERCENT`. A margem é arredondada para baixo em centavos, evitando ultrapassar o limite calculado.
- **Cálculo da parcela:** utiliza a Tabela Price, com parcelas fixas. O CET dos bancos simulados considera a taxa de juros e uma tarifa de contratação.
- **Empregador conveniado:** o CNPJ precisa existir na tabela `employers` e estar ativo.
- **Ordenação:** as ofertas aprovadas aparecem primeiro, da menor para a maior CET; em seguida vêm as recusadas; por último, as ofertas indisponíveis. A melhor oferta é destacada quando existe uma proposta aprovada elegível.

## 5. Bancos simulados

Os bancos são implementados como rotas da própria aplicação (`/api/mock/banks/{alfa,beta,gamma}`), mas são chamados por HTTP, com credenciais, timeout e tratamento de falhas. Para apontar a integração para outro serviço, as URLs podem ser alteradas na configuração, sem mudar o contrato interno dos clientes bancários.

| Banco | Formato da resposta | Regras simuladas | Taxa mensal | Latência simulada |
|---|---|---|---:|---|
| **Alfa** | `snake_case`; resultado `APPROVED` ou `DENIED` | Aprova o valor integral se a parcela couber na margem | 1,79% | 200 a 600 ms |
| **Beta** | `camelCase`; taxa em fração decimal, como `0.0189` | Recusa renda inferior a 1.500 por score; pode aprovar valor reduzido quando o pedido não cabe na margem ou ultrapassa 12 vezes a renda | 1,89% | 500 a 1.500 ms |
| **Gamma** | Objeto aninhado (`proposal`) | Não atende pensionistas do INSS; pode responder HTTP 503 conforme a taxa de falha configurada | 1,69% | 800 a 4.000 ms |

O Banco Gamma é instável de propósito para demonstrar o isolamento de falhas. Sua latência pode ultrapassar o timeout configurado, resultando em `timeout`.

## 6. Arquitetura e decisões técnicas

### Estrutura principal

```text
app/
├── DTOs/                    SimulationRequestDTO, BankOfferDTO
├── Enums/                   EmploymentType, OfferStatus, SimulationStatus
├── Http/
│   ├── Controllers/Api/     SimulationController
│   ├── Controllers/Mock/    MockBankController
│   ├── Middleware/          ForceJsonResponse
│   ├── Requests/            StoreSimulationRequest
│   └── Resources/           SimulationResource, BankOfferResource
├── Integrations/Banks/      BankClientInterface, AbstractBankClient, Alfa/Beta/Gamma
├── Models/                  Simulation, BankOffer, Employer
├── Providers/               SimulationServiceProvider
├── Repositories/            Contracts/ e Eloquent/
├── Services/                SimulationService, BankIntegrationService
└── Support/                 LoanCalculator
config/                      banks.php, loan.php
resources/views/              simulation.blade.php, offers.blade.php
routes/                       web.php, api.php
```

### Decisões técnicas

| Decisão | Motivo |
|---|---|
| **Camadas Controller → Service → Repository** | O controller cuida do HTTP, as regras ficam no serviço e o acesso a dados fica atrás de interfaces. |
| **`BankClientInterface` e `AbstractBankClient`** | Cada integração define seu payload e interpreta sua resposta. Um novo banco pode ser adicionado com uma implementação do contrato e configuração correspondente. |
| **DTOs imutáveis (`readonly`)** | Padronizam os dados entre camadas e evitam espalhar formatos específicos dos bancos pela aplicação. |
| **`Http::pool` para chamadas paralelas** | Consulta os bancos em paralelo, em vez de somar a latência de cada chamada sequencial. |
| **Falhas de integração representadas como ofertas** | Erros, respostas inválidas e timeouts são registrados por banco sem apagar as respostas válidas dos demais. |
| **Mocks acessados por HTTP** | Permitem exercitar cabeçalhos, latência, timeouts e falhas HTTP. |
| **PostgreSQL e `jsonb` para auditoria** | Permitem persistir informações da integração e payloads de auditoria, com os cuidados de mascaramento implementados pelo projeto. |
| **Enums para estados e vínculos** | Centralizam os valores aceitos e regras de prioridade. |
| **Validação em `FormRequest`** | Separa a validação da lógica de negócio e normaliza dados antes do processamento. A validação de CPF/CNPJ usa o pacote de validação instalado no projeto. |
| **Configuração por ambiente** | URLs, credenciais, timeouts e percentual de margem são definidos por configuração, evitando valores espalhados no código. |
| **Duas telas Blade** | Separam a coleta dos dados e a apresentação das ofertas sem exigir um framework JavaScript separado para o MVP. |

## 7. Melhorias e propostas

Esta seção separa o que já está implementado das melhorias que ainda podem ser desenvolvidas. Cada item explicita o problema, a proposta e a justificativa, conforme solicitado no desafio.

### 7.1. Implementadas

#### 1. Consulta paralela aos bancos simulados

- **Problema:** Consultar os bancos sequencialmente aumentaria o tempo total da simulação.
- **Proposta:** Disparar as consultas aos bancos simulados em paralelo.
- **Por quê:** O tempo de resposta tende a ser menor, pois as chamadas não precisam esperar umas pelas outras.

#### 2. Diferenciação entre indisponibilidade e reprovação

- **Problema:** Uma falha de comunicação poderia ser confundida com uma reprovação de crédito.
- **Proposta:** Representar separadamente respostas de reprovação e falhas ou indisponibilidades do serviço.
- **Por quê:** A interface apresenta um resultado mais fiel e evita informar ao cliente que foi reprovado quando, na verdade, não foi possível obter uma resposta.

#### 3. Padronização e ordenação das ofertas

- **Problema:** Os bancos simulados podem retornar estruturas e condições diferentes, dificultando a comparação.
- **Proposta:** Converter as respostas para um formato comum e ordenar as ofertas conforme as regras definidas pela aplicação, considerando o status e o CET.
- **Por quê:** A comparação fica mais consistente e compreensível para o cliente.

#### 4. Registro de auditoria

- **Problema:** Sem registros, fica mais difícil investigar o que aconteceu durante uma simulação.
- **Proposta:** Registrar eventos relevantes do processamento para apoiar a auditoria e a análise de falhas.
- **Por quê:** Os registros ajudam a entender o fluxo da requisição e a diagnosticar problemas.

#### 5. Idempotência da solicitação

- **Problema:** Repetições de uma mesma requisição podem gerar processamento duplicado.
- **Proposta:** Aplicar um mecanismo de idempotência para reconhecer solicitações repetidas dentro das condições implementadas.
- **Por quê:** Isso reduz duplicidades e torna o comportamento da API mais previsível.

#### 6. Proteção de dados pessoais

- **Problema:** Dados como CPF, e-mail e salário exigem cuidado para evitar exposição desnecessária.
- **Proposta:** Evitar incluir dados pessoais completos em registros e restringir sua exposição nas respostas e no processamento.
- **Por quê:** A medida reduz o risco de vazamento e segue o princípio de minimizar o uso de dados pessoais.

#### 7. Limitação de requisições

- **Problema:** Um volume excessivo de solicitações pode sobrecarregar a aplicação.
- **Proposta:** Aplicar limitação de requisições nos endpoints pertinentes.
- **Por quê:** Isso ajuda a proteger a disponibilidade do serviço contra uso excessivo.

#### 8. Interface em duas telas

- **Problema:** Misturar o preenchimento dos dados e a apresentação dos resultados pode dificultar o entendimento do fluxo.
- **Proposta:** Separar a experiência em uma tela para preencher e enviar a simulação e outra para visualizar as ofertas retornadas.
- **Por quê:** A separação torna o fluxo mais claro. A tela de ofertas utiliza os dados da simulação disponíveis no `sessionStorage`; ela não representa um histórico persistido de simulações.

### 7.2. Planejadas, em ordem de prioridade

#### 1. Circuit breaker para serviços bancários

- **Problema:** Falhas contínuas em um banco simulado ou serviço externo podem causar novas chamadas desnecessárias e prejudicar o tempo de resposta.
- **Proposta:** Implementar o padrão Circuit Breaker, interrompendo temporariamente chamadas a um serviço que apresente falhas recorrentes e permitindo novas tentativas controladas.
- **Por quê:** A aplicação pode reagir melhor a falhas persistentes e preservar recursos para as demais consultas.

#### 2. Fila assíncrona para simulações

- **Problema:** Se as consultas externas demorarem, a requisição HTTP pode permanecer aberta por muito tempo.
- **Proposta:** Processar simulações por meio de uma fila e disponibilizar o estado do processamento para consulta.
- **Por quê:** Isso desacopla a requisição do processamento e melhora a capacidade de lidar com operações demoradas.

#### 3. Autenticação e histórico de simulações

- **Problema:** Sem autenticação e persistência associadas a um usuário, não há uma área individual segura para consultar simulações anteriores.
- **Proposta:** Adicionar autenticação, associar cada simulação ao usuário responsável e criar uma tela de histórico.
- **Por quê:** O cliente poderá consultar resultados anteriores de forma organizada e controlada. Esse recurso ainda não faz parte do escopo implementado.

#### 4. Idempotência mais robusta

- **Problema:** Um mecanismo simples de idempotência pode não cobrir adequadamente concorrência, expiração de chaves e repetição de requisições em diferentes instâncias.
- **Proposta:** Definir uma chave de idempotência, armazenar o resultado associado e estabelecer regras explícitas de validade e concorrência.
- **Por quê:** Isso torna o comportamento mais confiável em cenários de repetição e de execução distribuída.

#### 5. Observabilidade

- **Problema:** Logs isolados podem não ser suficientes para acompanhar o desempenho de cada banco e localizar gargalos.
- **Proposta:** Adicionar métricas, identificadores de correlação e rastreamento distribuído, além de painéis e alertas quando apropriado.
- **Por quê:** A equipe poderá acompanhar latência, erros e disponibilidade com maior precisão.

#### 6. Ambiente de execução mais próximo da produção

- **Problema:** Diferenças entre os ambientes de desenvolvimento, teste e produção podem revelar falhas somente após a implantação.
- **Proposta:** Automatizar verificações de qualidade e testes no pipeline de integração contínua e documentar configurações por ambiente.
- **Por quê:** Isso reduz divergências e ajuda a identificar regressões antes da publicação.

#### 7. Política de retenção e descarte de dados

- **Problema:** Dados pessoais e resultados de simulação não devem ser mantidos indefinidamente sem uma finalidade definida.
- **Proposta:** Definir prazos de retenção, regras de descarte e controles de acesso para os dados armazenados.
- **Por quê:** A política melhora a governança dos dados e reduz a exposição desnecessária de informações pessoais.

## 8. Limitações

- **Servidor de desenvolvimento:** a aplicação usa `php artisan serve` no ambiente atual. Os mocks bancários compartilham o ambiente da aplicação, e a concorrência pode ser limitada. Para uma demonstração, recomenda-se executar uma simulação por vez. A latência observada pode incluir o custo de inicialização e processamento do framework.
- **Processamento síncrono:** a resposta só é devolvida depois que as consultas terminam ou atingem o timeout.
- **Sem circuit breaker nem nova tentativa:** bancos indisponíveis ainda são consultados a cada simulação.
- **Sem autenticação nesta versão:** o endpoint de simulação e a consulta por identificador não possuem associação a um usuário autenticado. Os limites de requisição não substituem controle de acesso.
- **Idempotência simples:** a chave não é associada ao conteúdo da requisição nem ao usuário. Reutilizar a chave de uma simulação que falhou devolve o registro existente; use uma nova chave para iniciar outra simulação.
- **Precisão financeira:** os cálculos usam `float`; as taxas simuladas são fixas e não variam conforme prazo ou perfil. O CET considera uma tarifa de contratação e não inclui IOF nem outros encargos.
- **Margem única:** o percentual padrão é igual para todos os vínculos, embora regras reais possam variar por convênio e categoria.
- **Validação de documentos:** CPF e CNPJ são validados matematicamente, sem consulta a uma fonte oficial. O empregador é conferido na tabela local `employers`. O modelo exige CNPJ de empregador mesmo para pensionistas do INSS.
- **Dados pessoais:** o e-mail é armazenado em texto na tabela de simulações, e ainda não há política de retenção ou exclusão.
- **Testes:** a suíte está configurada para SQLite em memória, não PostgreSQL. A concorrência da idempotência ainda não tem cobertura automatizada específica.
- **Credenciais:** as chaves dos bancos simulados são de desenvolvimento e não devem ser usadas em produção.
- **Limitação da interface:** as duas telas cobrem o fluxo de entrada e visualização da simulação. Não há login, cadastro, histórico de usuário nem recuperação automática das ofertas após perder os dados temporários do navegador.

## 9. Testes

Execute a suíte de testes com:

```bash
docker compose exec app php artisan test
```

| Arquivo | Cobertura descrita |
|---|---|
| `tests/Unit/LoanCalculatorTest.php` | Parcela pela Tabela Price, valor máximo financiável e CET |
| `tests/Unit/SimulationServiceMarginTest.php` | Margem consignável e arredondamento para baixo |
| `tests/Unit/BankClientParseTest.php` | Timeout, falha de conexão, resposta fora do formato e normalização da taxa do Banco Beta |
| `tests/Feature/SimulationApiTest.php` | Fluxo com bancos simulados via `Http::fake`, ordenação, falhas, validações, idempotência, status `failed`, limite por CPF e consulta por UUID |

Os testes utilizam `RefreshDatabase` com SQLite em memória, conforme a configuração do `phpunit.xml`, portanto não devem alterar o banco de desenvolvimento. A lista acima descreve a cobertura prevista pelos arquivos de teste; a execução do comando é necessária para confirmar o resultado no ambiente atual.

## 10. Uso de IA

Ferramentas de IA foram utilizadas como apoio à discussão da arquitetura e à elaboração de parte do código e dos testes. O projeto foi executado localmente durante o desenvolvimento.
