<?php

namespace App\Providers;

use App\Integrations\Banks\Contracts\BankClientInterface;
use App\Repositories\Contracts\BankOfferRepositoryInterface;
use App\Repositories\Contracts\SimulationRepositoryInterface;
use App\Repositories\Eloquent\BankOfferRepository;
use App\Repositories\Eloquent\SimulationRepository;
use App\Services\BankIntegrationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Registra no contêiner as dependências do módulo de simulação
 * (repositórios e clientes bancários) e o limitador de requisições.
 */
class SimulationServiceProvider extends ServiceProvider
{
    /**
     * Liga interfaces às implementações e monta a lista de clientes bancários.
     */
    public function register(): void
    {
        $this->app->bind(SimulationRepositoryInterface::class, SimulationRepository::class);
        $this->app->bind(BankOfferRepositoryInterface::class, BankOfferRepository::class);

        $this->app->when(BankIntegrationService::class)
            ->needs('$clients')
            ->give(fn () => $this->makeBankClients());
    }

    /**
     * Define o limitador "simulations": um limite por IP e outro por CPF.
     * O CPF entra na chave de cache apenas como hash (nunca em texto plano).
     */
    public function boot(): void
    {
        RateLimiter::for('simulations', function (Request $request) {
            $limits = [
                Limit::perMinute(config('loan.rate_limit.per_ip'))->by('ip:' . $request->ip()),
            ];

            $cpf = preg_replace('/\D/', '', (string) $request->input('cpf'));

            if ($cpf !== '') {
                $limits[] = Limit::perMinute(config('loan.rate_limit.per_cpf'))
                    ->by('cpf:' . hash_hmac('sha256', $cpf, config('app.key')));
            }

            return $limits;
        });
    }

    /**
     * Instancia um cliente para cada banco habilitado em config/banks.php.
     *
     * @return BankClientInterface[]
     */
    private function makeBankClients(): array
    {
        return collect(config('banks.banks'))
            ->filter(fn (array $config) => $config['enabled'])
            ->map(fn (array $config, string $key) => new $config['client']($key, $config))
            ->values()
            ->all();
    }
}